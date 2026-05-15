<?php
// checkout.php
session_start();
require __DIR__ . '/includes/db.php';
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

/* Auth guard */
if (empty($_SESSION['user_id']) && empty($_SESSION['user']['id']) && empty($_SESSION['site_user']['id'])) {
  header("Location: login.php?msg=login_required"); exit;
}

/* Helpers */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cart_menu_id($cartId){ return (int)preg_replace('/\D+/', '', (string)$cartId); }

/* Cart */
$cart = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

/* User id */
$uid = 0;
if (!empty($_SESSION['user']['id']))          $uid = (int)$_SESSION['user']['id'];
elseif (!empty($_SESSION['site_user']['id'])) $uid = (int)$_SESSION['site_user']['id'];
elseif (!empty($_SESSION['user_id']))         $uid = (int)$_SESSION['user_id'];

/* Prefill user */
$userRow = ['name'=>'','email'=>'','phone'=>'','address_line1'=>'','address_line2'=>'','city'=>'','state'=>'','postal_code'=>''];
if ($uid > 0) {
  if ($st = mysqli_prepare($conn, "SELECT name,email,phone,address_line1,address_line2,city,state,postal_code FROM `user` WHERE id=? LIMIT 1")) {
    mysqli_stmt_bind_param($st, "i", $uid);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if ($row = mysqli_fetch_assoc($res)) $userRow = array_merge($userRow, $row);
    mysqli_stmt_close($st);
  }
}
$hasSavedContact = ($userRow['name'] || $userRow['phone'] || $userRow['email']);
$hasSavedAddress = ($userRow['address_line1'] || $userRow['address_line2'] || $userRow['city'] || $userRow['state'] || $userRow['postal_code']);
$addrLabel = trim(
  trim(($userRow['address_line1'] ?: $userRow['address_line2'])) .
  (($userRow['city']) ? ', '.$userRow['city'] : '') .
  (($userRow['state']) ? ' '.$userRow['state'] : '') .
  (($userRow['postal_code']) ? ' '.$userRow['postal_code'] : '')
);

/* Online payment methods */
$payments = [];
if ($r = mysqli_query($conn, "SELECT id,bank,title,`no`,contact FROM payment WHERE status=1 ORDER BY id DESC")) {
  while ($row = mysqli_fetch_assoc($r)) $payments[] = $row;
  mysqli_free_result($r);
}

/* Totals */
$subtotal = 0; $menuIds = [];
foreach ($cart as $it) {
  $qty   = (int)($it['qty'] ?? 1);
  $price = (int)($it['price'] ?? 0);
  $subtotal += $price * $qty;
  $mid = cart_menu_id($it['id'] ?? '');
  if ($mid>0) $menuIds[$mid] = true;
}
$delivery = 0;
if ($menuIds) {
  $in = implode(',', array_map('intval', array_keys($menuIds)));
  $q  = "SELECT delivery FROM menu WHERE id IN ($in) AND status=1";
  if ($r = mysqli_query($conn,$q)) {
    while ($row = mysqli_fetch_assoc($r)) {
      $fee = (int)($row['delivery'] ?? 0);
      if ($fee > $delivery) $delivery = $fee;
    }
    mysqli_free_result($r);
  }
}
$grand = $subtotal + $delivery;

/* Flash */
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
include __DIR__ . '/includes/loader.php';
$flash = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<style>
  .glass-card{backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);background:rgba(255,255,255,.55);border:1px solid rgba(255,255,255,.4);box-shadow:0 8px 24px rgba(0,0,0,.05);border-radius:1rem}
  .pill-input,.dp-dn{border:1.5px solid rgba(219,125,49,.45);background:transparent;border-radius:999px;padding:.6rem 1.2rem;outline:none}
  .pill-input:focus,.dp-dn:focus{border-color:var(--brand,#DB7D31);box-shadow:0 0 0 .2rem rgba(219,125,49,.15);background:rgba(255,255,255,.35)}
  .radio-tile{border:1px solid #eee;border-radius:14px;padding:.75rem 1rem;cursor:pointer;display:flex;align-items:center;gap:.6rem}
  .radio-tile input{accent-color:var(--brand,#DB7D31)}
  .summary{position:sticky;top:88px}
  .summary .line{display:flex;justify-content:space-between;margin:.25rem 0}
  .summary .line.total{font-weight:600}
  .item-thumb{width:52px;height:52px;border-radius:10px;object-fit:cover}
  .bank-box{border:1px dashed rgba(0,0,0,.15);border-radius:12px;padding:12px;background:rgba(255,255,255,.45)}
  .btn-submit[disabled]{opacity:.7;cursor:wait}
</style>

<main class="py-5" style="background:var(--bg)">
  <div class="container">
    <h2 class="section-title mb-4" style="font-family:'Petit Formal Script',cursive;">Checkout</h2>

    <?php if ($flash): ?>
      <div class="alert alert-warning rounded-3"><?= e($flash) ?></div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
      <div class="glass-card p-4 text-center">
        <p class="mb-2">Your cart is empty.</p>
        <a href="products.php" class="btn btn-outline-success rounded-pill px-4">Browse Menu</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="glass-card p-3 p-md-4">
            <form id="checkoutForm" method="post" action="checkout_submit.php" enctype="multipart/form-data" class="needs-validation" novalidate>
              <h5 class="mb-2">Contact</h5>

              <div class="mb-2">
                <select id="fillProfile" class="form-select dp-dn" <?= $hasSavedContact ? '' : 'disabled' ?>>
                  <option value=""><?= $hasSavedContact ? 'Use saved contact…' : 'No saved contact' ?></option>
                  <?php if ($hasSavedContact): ?>
                    <option value="saved"><?= e($userRow['name']) ?><?= $userRow['phone'] ? ' ('.e($userRow['phone']).')' : '' ?></option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <input type="text" name="name" id="cName" class="form-control pill-input"
                         value="<?= e($userRow['name']) ?>" placeholder="Full name" autocomplete="name" required>
                  <div class="invalid-feedback">Name required.</div>
                </div>
                <div class="col-md-6">
                  <input type="tel" name="phone" id="cPhone" class="form-control pill-input"
                         value="<?= e($userRow['phone']) ?>" placeholder="Phone (e.g., 04xx xxx xxx)"
                         pattern="^0[45]\d{8}$" autocomplete="tel" required>
                  <div class="invalid-feedback">Valid phone required (04xx xxx xxx).</div>
                </div>
                <div class="col-12">
                  <input type="email" name="email" id="cEmail" class="form-control pill-input"
                         value="<?= e($userRow['email']) ?>" placeholder="Email (optional)" autocomplete="email">
                </div>
              </div>

              <hr class="my-4">

              <h5 class="mb-2">Delivery address</h5>
              <div class="mb-2">
                <select id="fillAddress" class="form-select dp-dn" <?= $hasSavedAddress ? '' : 'disabled' ?>>
                  <option value=""><?= $hasSavedAddress ? 'Use saved address…' : 'No saved address' ?></option>
                  <?php if ($hasSavedAddress): ?>
                    <option value="saved"><?= e($addrLabel) ?></option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="row g-3">
                <div class="col-12">
                  <input type="text" name="address_line1" id="aAddr1" class="form-control pill-input"
                         value="<?= e($userRow['address_line1']) ?>" placeholder="Address line 1" required>
                  <div class="invalid-feedback">Address line 1 required.</div>
                </div>
                <div class="col-12">
                  <input type="text" name="address_line2" id="aAddr2" class="form-control pill-input"
                         value="<?= e($userRow['address_line2']) ?>" placeholder="Address line 2 (optional)">
                </div>
                <div class="col-md-4">
                  <input type="text" name="city" id="aCity" class="form-control pill-input"
                         value="<?= e($userRow['city']) ?>" placeholder="Suburb" required>
                  <div class="invalid-feedback">Suburb required.</div>
                </div>
                <div class="col-md-4">
                  <input type="text" name="state" id="aState" class="form-control pill-input"
                         value="<?= e($userRow['state']) ?>" placeholder="State / Territory">
                </div>
                <div class="col-md-4">
                  <input type="text" name="postal_code" id="aZip" class="form-control pill-input"
                         value="<?= e($userRow['postal_code']) ?>" placeholder="Postal code">
                </div>
              </div>

              <hr class="my-4">

              <h5 class="mb-3">Payment</h5>
              <div class="d-grid gap-2">
                <label class="radio-tile"><input type="radio" name="pay" class="form-check-input me-1" value="cod" checked> Cash on Delivery</label>
                <label class="radio-tile"><input type="radio" name="pay" class="form-check-input me-1" value="online"> Online payment (bank transfer)</label>
              </div>

              <div id="onlineBox" class="mt-3 d-none">
                <div class="bank-box">
                  <div class="fw-semibold mb-2">Choose bank</div>
                  <?php if ($payments): ?>
                    <select id="bankSelect" name="payment_id" class="form-select mb-3">
                      <?php foreach ($payments as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['bank']) ?> — <?= e($p['title']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div id="bankDetail" class="small mb-3"></div>
                    <div class="small fw-medium mb-2">Upload payment proof (screenshot/receipt):</div>
                    <input type="file" name="payment_proof" accept="image/*,application/pdf" class="form-control" id="payment_proof">
                    <div class="form-text">We’ll mark the order <strong>Pending</strong> until verified.</div>
                  <?php else: ?>
                    <div class="alert alert-light border mb-0">No online payment methods available right now.</div>
                  <?php endif; ?>
                </div>
              </div>

              <hr class="my-4">

              <div class="mb-3">
                <textarea name="notes" class="form-control pill-input" style="border-radius:18px" rows="3" placeholder="Notes (no onions, call on arrival, etc.)"></textarea>
              </div>

              <button class="btn btn-primary rounded-pill px-4 btn-submit" type="submit">Place Order</button>
            </form>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="glass-card p-3 p-md-4 summary">
            <h5 class="mb-3">Order Summary</h5>
            <ul class="list-unstyled m-0">
              <?php foreach ($cart as $id=>$it): ?>
                <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= e($it['image']) ?>" class="item-thumb" alt="">
                    <div>
                      <div class="fw-semibold small"><?= e($it['name']) ?></div>
                      <div class="text-muted small">x<?= (int)($it['qty'] ?? 1) ?></div>
                    </div>
                  </div>
                  <div class="fw-semibold small">$ <?= number_format(((int)($it['price'] ?? 0))*((int)($it['qty'] ?? 1))) ?></div>
                </li>
              <?php endforeach; ?>
            </ul>

            <div class="mt-3">
              <div class="line"><span class="text-muted">Subtotal</span><span>$ <span id="sum-sub"><?= number_format($subtotal) ?></span></span></div>
              <div class="line"><span class="text-muted">Delivery</span><span>$ <span id="sum-del"><?= number_format($delivery) ?></span></span></div>
              <div class="line total mt-2"><span>Total</span><span>$ <span id="sum-total"><?= number_format($grand) ?></span></span></div>
            </div>
            <p class="small text-muted mt-3 mb-0">Kitchen hours: 11:00 AM – 11:00 PM</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(() => {
  const form = document.getElementById('checkoutForm');
  const submitBtn = document.querySelector('.btn-submit');
  form?.addEventListener('submit', (e) => {
    if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    else { submitBtn?.setAttribute('disabled','disabled'); }
    form.classList.add('was-validated');
  });

  const onlineBox  = document.getElementById('onlineBox');
  const proofInput = document.getElementById('payment_proof');
  const bankSelect = document.getElementById('bankSelect');
  function syncPaymentUI(){
    const online = document.querySelector('input[name="pay"]:checked')?.value === 'online';
    onlineBox?.classList.toggle('d-none', !online);
    if (proofInput) online ? proofInput.setAttribute('required','required') : proofInput.removeAttribute('required');
    if (bankSelect) online ? bankSelect.setAttribute('required','required') : bankSelect.removeAttribute('required');
  }
  document.querySelectorAll('input[name="pay"]').forEach(r=>r.addEventListener('change', syncPaymentUI));
  syncPaymentUI();

  const savedContact = <?= json_encode(['name'=>$userRow['name'],'phone'=>$userRow['phone'],'email'=>$userRow['email']], JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById('fillProfile')?.addEventListener('change', e=>{
    if (e.target.value==='saved'){
      document.getElementById('cName').value  = savedContact.name  || '';
      document.getElementById('cPhone').value = savedContact.phone || '';
      document.getElementById('cEmail').value = savedContact.email || '';
    }
  });

  const savedAddress = <?= json_encode(['address_line1'=>$userRow['address_line1'],'address_line2'=>$userRow['address_line2'],'city'=>$userRow['city'],'state'=>$userRow['state'],'postal_code'=>$userRow['postal_code']], JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById('fillAddress')?.addEventListener('change', e=>{
    if (e.target.value==='saved'){
      document.getElementById('aAddr1').value = savedAddress.address_line1 || '';
      document.getElementById('aAddr2').value = savedAddress.address_line2 || '';
      document.getElementById('aCity').value  = savedAddress.city || '';
      document.getElementById('aState').value = savedAddress.state || '';
      document.getElementById('aZip').value   = savedAddress.postal_code || '';
    }
  });

  const bankDetail = document.getElementById('bankDetail');
  const banks = <?= json_encode($payments, JSON_UNESCAPED_UNICODE) ?>;
  function renderBankDetail(id){
    const b = banks.find(x => String(x.id)===String(id));
    bankDetail && (bankDetail.innerHTML = b ? `
      <div><strong>Bank:</strong> ${b.bank||''}</div>
      <div><strong>Account Title:</strong> ${b.title||''}</div>
      <div><strong>Account / IBAN / No:</strong> ${b.no||''}</div>
      <div><strong>Contact:</strong> ${b.contact||'-'}</div>
      <div class="text-muted mt-1">Reference: use your phone number</div>
    ` : '');
  }
  bankSelect?.addEventListener('change', ()=>renderBankDetail(bankSelect.value));
  if (bankSelect) renderBankDetail(bankSelect.value);
})();
</script>
