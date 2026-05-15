<?php
session_start();

include __DIR__.'/includes/header.php';
include __DIR__.'/includes/page-hero.php';
include __DIR__ . "/includes/loader.php";
require __DIR__ . '/includes/db.php'; // mysqli $conn

// --- CART DATA ---
$items = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];

// --- HELPERS ---
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function cart_menu_id($cartId){
  // cart ids look like "p123" -> strip non-digits -> 123
  return (int)preg_replace('/\D+/', '', (string)$cartId);
}

// --- SUBTOTAL ---
$subtotal = 0;
foreach ($items as $it) {
  $subtotal += ((int)$it['price']) * ((int)$it['qty']);
}

// --- DELIVERY FEE FROM DB (menu.delivery) ---
// Strategy: take the HIGHEST delivery fee among the cart's menu items.
// If you want to SUM per-item fees instead, change "$delivery = max(...)" logic below to +=.
$delivery = 0;
$menuIds = [];
foreach ($items as $it) {
  $mid = cart_menu_id($it['id'] ?? '');
  if ($mid > 0) $menuIds[$mid] = true; // unique ids
}
$menuIds = array_keys($menuIds);

if (!empty($menuIds)) {
  $in = implode(',', array_map('intval', $menuIds));
  $sql = "SELECT id, delivery FROM menu WHERE id IN ($in) AND status=1";
  if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
      $fee = (int)($row['delivery'] ?? 0);
      if ($fee > $delivery) $delivery = $fee;   // take HIGHEST
      // To SUM instead: $delivery += $fee;
    }
    mysqli_free_result($res);
  }
}
// store for use on checkout if you want
$_SESSION['delivery'] = $delivery;

$grand = $subtotal + $delivery;
?>

<style>
  /* Ensure a hidden loader overlay never eats clicks */
  .loader-overlay.is-hidden { pointer-events: none; }
</style>

<main class="py-3">
  <div class="container">
    <h3 class="section-title">Your Cart</h3>

    <?php if (empty($items)): ?>
      <div class="p-4 rounded-4 bg-white shadow-sm text-center">
        <div class="mb-3">
          <img src="images/empty-cart.png" alt="Empty Cart" style="width:100px;height:100px;">
        </div>
        <h5 class="fw-bold mb-1">Your cart is empty</h5>
        <p class="text-muted small mb-3">Looks like you haven’t added anything yet. Browse our tasty menu and add your favorites!</p>
        <a href="products.php" class="btn col-6 col-md-3 my-md-4 py-2 btn-sm btn-outline-success rounded-pill">
          <i class="bi bi-arrow-right-circle me-1"></i> Go to Menu
        </a>
      </div>

    <?php else: ?>
      <div class="table-responsive bg-white rounded-3 shadow-sm p-3">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Item</th>
              <th class="text-center" style="width:170px;">Qty</th>
              <th class="text-end">Unit</th>
              <th class="text-end">Line Total</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody id="cartBody">
            <?php foreach($items as $id=>$it): ?>
              <tr data-id="<?= e($id) ?>">
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <img src="<?= e($it['image']) ?>" class="rounded" style="width:64px;height:64px;object-fit:cover;" alt="">
                    <div>
                      <div class="fw-semibold"><?= e($it['name']) ?></div>
                      <div class="small text-muted"><?= e($it['category']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="input-group input-group-sm" style="max-width:170px;margin:auto;">
                    <button class="btn btn-outline-secondary cart-dec" type="button">−</button>
                    <input class="form-control text-center cart-qty" type="number" value="<?= (int)$it['qty'] ?>" min="1" style="min-width:48px;">
                    <button class="btn btn-outline-secondary cart-inc" type="button">+</button>
                  </div>
                </td>
                <td class="text-end">$ <?= number_format((int)$it['price']) ?></td>
                <td class="text-end fw-semibold line-total">$ <?= number_format(((int)$it['price'])*((int)$it['qty'])) ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger cart-remove"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2"></td>
              <td class="text-end text-muted">Subtotal</td>
              <td class="text-end">$ <span id="subtotal"><?= number_format($subtotal) ?></span></td>
              <td></td>
            </tr>
            <tr>
              <td colspan="2"></td>
              <td class="text-end text-muted">Delivery</td>
              <td class="text-end">$ <span id="delivery"><?= number_format($delivery) ?></span></td>
              <td></td>
            </tr>
            <tr>
              <td colspan="2"></td>
              <td class="text-end fw-semibold">Total</td>
              <td class="text-end fw-semibold">$ <span id="grandTotal"><?= number_format($grand) ?></span></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <button id="clearCart" class="btn btn-outline-danger">Clear Cart</button>
        <?php if (empty($_SESSION['user_id'])): ?>
  <a href="login.php?msg=login_required" class="btn btn-success px-4">Checkout</a>
<?php else: ?>
  <a href="checkout.php" class="btn btn-success px-4">Checkout</a>
<?php endif; ?>

      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__.'/includes/footer.php'; ?>

<script>
/* ===== Safety: kill any overlay/backdrop that could swallow clicks ===== */
document.addEventListener('DOMContentLoaded', () => {
  try {
    const seen = localStorage.getItem('siteLoaderSeen') === '1';
    document.querySelectorAll('.loader-overlay').forEach(el => {
      if (seen) { el.remove(); }
      else { el.classList.add('is-hidden'); el.style.pointerEvents = 'none'; }
    });
  } catch (e) {}

  const wrap = document.getElementById('offcanvas');
  const backdrop = document.getElementById('offcanvasBackdrop');
  wrap && wrap.classList.remove('show');
  backdrop && backdrop.classList.remove('show');
  document.body.classList.remove('nav-open');
  document.body.style.overflow = '';
});

/* ===== Cart logic (guarded; no hard crashes) ===== */
document.addEventListener('DOMContentLoaded', () => {
  const rs = n => Number(n||0).toLocaleString('en-PK');

  async function cartApi(body){
    const r = await fetch('cart_api.php', {
      method:'POST',
      credentials:'same-origin',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    const text = await r.text();
    try { return JSON.parse(text); }
    catch(e){ console.error('cart_api bad JSON:', text); return {ok:false}; }
  }

  function updateTotals(s){
    const sub = document.getElementById('subtotal');
    const del = document.getElementById('delivery');
    const grd = document.getElementById('grandTotal');
    if (sub) sub.textContent = rs(s.subtotal);
    if (del) del.textContent = rs(s.delivery);
    if (grd) grd.textContent = rs(s.total);

    const badge = document.getElementById('cartCount');
    if (badge && typeof s.count !== 'undefined') badge.textContent = s.count;
  }

  async function syncQty(tr, qty){
    const id = tr.getAttribute('data-id');
    const d = await cartApi({action:'setQty', id, qty: Math.max(1, Number(qty||1))});
    if (d?.ok){
      const lt = tr.querySelector('.line-total');
      if (lt) lt.textContent = '$ ' + rs(d.item.price * d.item.qty);
      updateTotals(d.summary);
    }
  }

  const bodyEl = document.getElementById('cartBody');

  bodyEl?.addEventListener('click', async (e)=>{
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;

    if (e.target.closest('.cart-inc')) {
      const input = tr.querySelector('.cart-qty');
      input.value = Number(input.value||1) + 1;
      await syncQty(tr, input.value);
    }
    if (e.target.closest('.cart-dec')) {
      const input = tr.querySelector('.cart-qty');
      input.value = Math.max(1, Number(input.value||1) - 1);
      await syncQty(tr, input.value);
    }
    if (e.target.closest('.cart-remove')) {
      const id = tr.getAttribute('data-id');
      const d = await cartApi({action:'remove', id});
      if (d?.ok){
        tr.remove();
        updateTotals(d.summary);
        if (d.summary.count === 0) location.reload();
      }
    }
  });

  bodyEl?.addEventListener('input', (e)=>{
    const tr = e.target.closest('tr[data-id]');
    if (!tr) return;
    if (e.target.matches('.cart-qty')){
      const v = Math.max(1, Number(e.target.value||1));
      e.target.value = v;
      syncQty(tr, v);
    }
  });

  document.getElementById('clearCart')?.addEventListener('click', async ()=>{
    if (!confirm('Clear all items?')) return;
    const d = await cartApi({action:'clear'});
    if (d?.ok) location.reload();
  });
});

/* ===== Mobile menu delegation ===== */
document.addEventListener('click', (e) => {
  const openBtn  = e.target.closest('#hamburger');
  const closeBtn = e.target.closest('#menuClose, #offcanvasBackdrop');
  const wrap = document.getElementById('offcanvas');
  const backdrop = document.getElementById('offcanvasBackdrop');
  if (openBtn) {
    wrap?.classList.add('show');
    backdrop?.classList.add('show');
    document.body.classList.add('nav-open');
  }
  if (closeBtn) {
    wrap?.classList.remove('show');
    backdrop?.classList.remove('show');
    document.body.classList.remove('nav-open');
  }
});

/* ===== Add-to-cart badge bump ===== */
document.addEventListener('click', function(e){
  const add = e.target.closest('.js-add-cart');
  if (!add) return;
  const badge = document.getElementById('cartCount');
  if (!badge) return;
  const n = parseInt(badge.textContent, 10) || 0;
  badge.textContent = n + 1;
  badge.classList.add('pop');
  setTimeout(() => badge.classList.remove('pop'), 220);
});

/* ===== Only init Owl if present ===== */
document.addEventListener('DOMContentLoaded', function () {
  const hasJq = !!window.jQuery;
  if (!hasJq) return;
  const $owl = $('#reviewsOwl');
  if(!$owl.length || !$.fn.owlCarousel) return;

  $owl.on('initialized.owl.carousel changed.owl.carousel', function(e){
    const idx = e.item && e.item.index;
    $owl.find('.owl-item').removeClass('is-active');
    $owl.find('.owl-item').eq(idx).addClass('is-active')
         .find('.review-card').addClass('review-appear');
    setTimeout(()=> $owl.find('.review-card').removeClass('review-appear'), 600);
  });

  $owl.owlCarousel({
    loop:true,
    autoplay:true,
    autoplayTimeout:3500,
    autoplayHoverPause:true,
    smartSpeed:550,
    margin:16,
    dots:true,
    nav:true,
    navText:['<i class="bi bi-chevron-left"></i>','<i class="bi bi-chevron-right"></i>'],
    autoHeight:true,
    responsive:{ 0:{items:1}, 768:{items:2}, 1200:{items:3} }
  });
});
</script>

</body>
</html>
