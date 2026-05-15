<?php
// account.php — Payment labels: COD→COD (Approved) after approval; Online→Paid after approval

session_start();

/* ---------- Item preview helpers ---------- */
function items_preview(?string $json): string {
  if (!$json) return '—';
  $arr = json_decode($json, true);
  if (!is_array($arr) || !$arr) return '—';
  $out = []; $shown = 0;
  foreach ($arr as $it) {
    $name = $it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item';
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    $out[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ' × ' . $qty;
    if (++$shown >= 2) break;
  }
  $more = max(0, count($arr) - $shown);
  return $out ? implode(', ', $out) . ($more ? " +{$more} more" : "") : '—';
}
function items_full_html(?string $json): string {
  $arr = json_decode((string)$json, true);
  if (!is_array($arr) || !$arr) return '<div class="text-muted">No items.</div>';
  $h = '<div style="min-width:220px"><ul class="list-unstyled m-0">';
  foreach ($arr as $it) {
    $name = htmlspecialchars($it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item', ENT_QUOTES, 'UTF-8');
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    $h   .= '<li class="d-flex justify-content-between border-bottom py-1"><span>'.$name.'</span><span class="fw-semibold">× '.$qty.'</span></li>';
  }
  return $h . '</ul></div>';
}

/* ---------- Normalize session keys ---------- */
if (empty($_SESSION['user_id']) && !empty($_SESSION['USER']['id'])) {
  $_SESSION['user_id'] = (int)$_SESSION['USER']['id'];
}
if (empty($_SESSION['user_id']) && !empty($_SESSION['user']['id'])) {
  $_SESSION['user_id'] = (int)$_SESSION['user']['id'];
}

/* ---------- Auth gate BEFORE includes ---------- */
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$userId = (int)$_SESSION['user_id'];

require __DIR__ . '/includes/db.php';

/* ---------- Payment label helpers ---------- */
/* EXACT rules:
   - COD: start "COD"; after admin approval show "COD (Approved)" (never "Paid").
            Treat status in {approved, confirmed, processing, shipped, delivered, completed, paid} OR
            fulfillment in {processing, shipped, delivered, completed} as approved.
   - Online: start "Online (Waiting for approval)"; after approval/capture/paid show "Paid".
*/
function order_payment_label(?string $status, ?string $method, ?string $fulfillment = ''): string {
  $s = strtolower(trim((string)$status));
  $m = strtolower(trim((string)$method));
  $f = strtolower(trim((string)$fulfillment));

  // Normalize some common method aliases
  if (in_array($m, ['cash on delivery','cash_on_delivery','cod'], true)) $m = 'cod';
  if (in_array($m, ['card','stripe','paypal','online_payment','online'], true)) $m = 'online';

  // COD
  if ($m === 'cod' || $m === '') {
    if ($s === 'cancelled') return 'Cancelled';
    $approvedStatuses = ['approved','confirmed','processing','shipped','delivered','completed','paid'];
    $approvedFulfill  = ['processing','shipped','delivered','completed'];
    if (in_array($s, $approvedStatuses, true) || in_array($f, $approvedFulfill, true)) {
      return 'COD ';
    }
    return 'COD';
  }
 
  // Online
  if ($m === 'online') {
    if ($s === 'cancelled') return 'Cancelled';
    $paidStatuses   = ['paid','captured','completed','succeeded','approved'];
    $waitingStatuses= ['new','pending','authorized','awaiting_payment','awaiting-approval','awaiting approval'];
    if (in_array($s, $paidStatuses, true)) return 'Paid';
    if (in_array($s, $waitingStatuses, true) || $s === '') return 'Online (Waiting for approval)';
  }

  // Fallback
  if ($s === '') return '—';
  return ucfirst($s);
}

/* Pick Bootstrap color for final label text (and for fulfillment keywords) */
function order_status_bscolor(string $label): string {
  $k = strtolower(trim($label));
  $map = [
    'cod'                         => 'dark',
    'cod (approved)'              => 'primary',
    'online (waiting for approval)'=> 'warning',
    'paid'                        => 'success',
    'cancelled'                   => 'danger',
    'failed'                      => 'danger',
    'processing'                  => 'primary',
    'shipped'                     => 'primary',
    'delivered'                   => 'success',
    'completed'                   => 'success',
  ];
  return $map[$k] ?? 'secondary';
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf'];
function require_csrf(){
  if (empty($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(403); exit('Invalid CSRF');
  }
}

/* ---------- POST actions (mysqli) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  $act = $_POST['action'] ?? '';

  if ($act === 'profile') {
    $name   = trim($_POST['name'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $phone  = trim($_POST['phone'] ?? '');
    $a1     = trim($_POST['address_line1'] ?? '');
    $a2     = trim($_POST['address_line2'] ?? '');
    $city   = trim($_POST['city'] ?? '');
    $state  = trim($_POST['state'] ?? '');
    $zip    = trim($_POST['postal_code'] ?? '');

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION['flash_error'] = 'Please enter a valid name and email.';
      header('Location: account.php#tab-profile'); exit;
    }

    // unique email for other users
    $stmt = mysqli_prepare($conn, "SELECT id FROM user WHERE email=? AND id<>? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $email, $userId);
    mysqli_stmt_execute($stmt);
    $dup = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($dup) { $_SESSION['flash_error'] = 'This email is already in use.'; header('Location: account.php#tab-profile'); exit; }

    // update profile
    $sql = "UPDATE user
            SET name=?, email=?, phone=?, address_line1=?, address_line2=?, city=?, state=?, postal_code=?
            WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssssi", $name, $email, $phone, $a1, $a2, $city, $state, $zip, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // sync session
    $_SESSION['USER']['id']    = $userId;
    $_SESSION['USER']['name']  = $name;
    $_SESSION['USER']['email'] = $email;

    $_SESSION['flash_success'] = 'Profile updated.';
    header('Location: account.php#tab-profile'); exit;
  }

  if ($act === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $new !== $confirm) {
      $_SESSION['flash_error'] = 'New passwords do not match.';
      header('Location: account.php#tab-password'); exit;
    }

    $stmt = mysqli_prepare($conn, "SELECT password_hash FROM user WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || !password_verify($current, $row['password_hash'])) {
      $_SESSION['flash_error'] = 'Current password is incorrect.';
      header('Location: account.php#tab-password'); exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = mysqli_prepare($conn, "UPDATE user SET password_hash=? WHERE id=?");
    mysqli_stmt_bind_param($upd, "si", $newHash, $userId);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);

    $_SESSION['flash_success'] = 'Password updated.';
    header('Location: account.php#tab-password'); exit;
  }
}

/* ---------- Reads ---------- */
$user = [
  'name'=>'','email'=>'','phone'=>'',
  'address_line1'=>'','address_line2'=>'','city'=>'','state'=>'','postal_code'=>''
];
$stmt = mysqli_prepare($conn, "
  SELECT id, name, email, phone, address_line1, address_line2, city, state, postal_code
  FROM user WHERE id=? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res && ($row = mysqli_fetch_assoc($res))) $user = $row;
mysqli_stmt_close($stmt);

/* ---------- Latest 10 orders (include method + fulfillment_status) ---------- */
$orders = [];
$stmt = mysqli_prepare($conn, "
  SELECT  o.id,
          o.total,
          o.status,
          o.payment_method,
          o.fulfillment_status,
          o.created_at,
          o.items_json
  FROM `order` o
  WHERE o.user_id = ?
  ORDER BY o.created_at DESC
  LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

/* helper: table exists */
function table_exists(mysqli $conn, string $name): bool {
  $q = mysqli_prepare($conn, "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
  if (!$q) return false;
  mysqli_stmt_bind_param($q, "s", $name);
  mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q);
  $ok = $r && mysqli_num_rows($r) > 0;
  if ($r) mysqli_free_result($r);
  mysqli_stmt_close($q);
  return $ok;
}
$has_items_table = table_exists($conn, 'order_items');

while ($res && ($r = mysqli_fetch_assoc($res))) {
  $itemsCount = 0;
  if (!empty($r['items_json'])) {
    $arr = json_decode($r['items_json'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
      foreach ($arr as $it) $itemsCount += (int)($it['qty'] ?? 1);
    }
  }
  if ($itemsCount === 0 && $has_items_table) {
    $stmt2 = mysqli_prepare($conn, "SELECT COALESCE(SUM(qty),0) AS items FROM order_items WHERE order_id=?");
    mysqli_stmt_bind_param($stmt2, "i", $r['id']);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $row2 = $res2 ? mysqli_fetch_assoc($res2) : ['items'=>0];
    mysqli_stmt_close($stmt2);
    $itemsCount = (int)($row2['items'] ?? 0);
  }

  $r['items'] = $itemsCount;
  $orders[] = $r;
}
mysqli_stmt_close($stmt);

/* ---------- Flash ---------- */
$flash_error   = $_SESSION['flash_error']   ?? '';
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

/* ---------- Initials avatar ---------- */
function initials_of(string $name): string {
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') return 'U';
  $parts = explode(' ', $name);
  $first  = strtoupper(mb_substr($parts[0], 0, 1));
  $second = isset($parts[1]) ? strtoupper(mb_substr($parts[1], 0, 1)) : '';
  return $first . $second;
}
$USER_INITIALS = initials_of($user['name'] ?? 'User');

/* ---------- View ---------- */
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
?>
<?php include __DIR__ . "/includes/loader.php"; ?>

<style>
  :root{ --brand: var(--brand, #6E3B16); --glass-bg: rgba(255,255,255,.55); }
  .account-wrap{ max-width:1024px; }
  .glass-card{ backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); background:var(--glass-bg); border:1px solid rgba(255,255,255,.4); box-shadow:0 8px 24px rgba(0,0,0,.05); border-radius:1rem; }
  .pill-input{ border:1.5px solid rgba(110,59,22,.25); background:transparent; border-radius:999px; padding:.6rem 1.5rem; box-shadow:inset 0 1px 0 rgba(255,255,255,.35); transition:.2s; }
  .pill-input:focus{ border-color:var(--brand); box-shadow:0 0 0 .2rem rgba(110,59,22,.15); background:rgba(255,255,255,.35); }
  .avatar-initial{ width:60px; height:50px; border-radius:50%; display:grid; place-items:center; font-weight:700; color:#fff; background:linear-gradient(135deg,#6E3B16,#8b5329); box-shadow:0 4px 14px rgba(110,59,22,.25); }
  .account-nav{ gap:.5rem; }
  .account-nav .nav-link{ border-radius:12px; padding:.6rem 1rem; font-weight:500; color:#444; display:flex; align-items:center; transition:.25s; }
  .account-nav .nav-link:hover{ background:rgba(110,59,22,.08); color:var(--brand); }
  .account-nav .nav-link.active{ background:rgba(110,59,22,.08); color:var(--brand); }
  .btn-brand{ background:#6E3B16 !important; color:#fff; border:none; border-radius:999px; }
  .btn-brand:hover{ filter:brightness(.96); color:#fff; }
  .text-brand{ color:var(--brand)!important; }
</style>

<main class="py-1" style="background: var(--bg)">
  <div class="container account-wrap">
    <h2 class="section-title">My Account</h2>

    <?php if ($flash_error):   ?><div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>
    <?php if ($flash_success): ?><div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>

    <div class="row g-4">
      <!-- Sidebar -->
      <div class="col-lg-3">
        <div class="glass-card p-3">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="avatar-initial"><?= htmlspecialchars($USER_INITIALS) ?></div>
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
            </div>
          </div>

          <div class="d-lg-block d-none">
            <div class="nav flex-column account-nav" id="acctTabs" role="tablist" aria-orientation="vertical">
              <button class="nav-link active" data-bs-target="#tab-overview" data-bs-toggle="tab" role="tab"><i class="bi bi-speedometer2 me-2"></i> Overview</button>
              <button class="nav-link" data-bs-target="#tab-orders" data-bs-toggle="tab" role="tab"><i class="bi bi-bag-check me-2"></i> Orders</button>
              <button class="nav-link" data-bs-target="#tab-profile" data-bs-toggle="tab" role="tab"><i class="bi bi-person me-2"></i> Profile</button>
              <button class="nav-link" data-bs-target="#tab-password" data-bs-toggle="tab" role="tab"><i class="bi bi-lock me-2"></i> Password</button>
              <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </div>
          </div>

          <div class="d-lg-none">
            <div class="nav account-nav flex-wrap" id="acctTabsMobile" role="tablist">
              <button class="nav-link active" data-bs-target="#tab-overview" data-bs-toggle="tab" role="tab">Overview</button>
              <button class="nav-link" data-bs-target="#tab-orders" data-bs-toggle="tab" role="tab">Orders</button>
              <button class="nav-link" data-bs-target="#tab-profile" data-bs-toggle="tab" role="tab">Profile</button>
              <button class="nav-link" data-bs-target="#tab-password" data-bs-toggle="tab" role="tab">Password</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="col-lg-9">
        <div class="glass-card p-3 p-md-4">
          <div class="tab-content">

            <!-- Overview -->
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="rounded-4 bg-white border p-3 h-100">
                    <div class="small text-muted">Name</div>
                    <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                    <hr>
                    <div class="small text-muted">Email</div>
                    <div class="fw-semibold"><?= htmlspecialchars($user['email']) ?></div>
                    <?php if (!empty($user['phone'])): ?>
                      <hr><div class="small text-muted">Phone</div><div class="fw-semibold"><?= htmlspecialchars($user['phone']) ?></div>
                    <?php endif; ?>
                    <?php
                      $addrParts = array_filter([
                        $user['address_line1'] ?? '',
                        $user['address_line2'] ?? '',
                        trim(($user['city'] ?? '') . ' ' . ($user['state'] ?? '') . ' ' . ($user['postal_code'] ?? ''))
                      ]);
                      $addrFull = implode("\n", $addrParts);
                    ?>
                    <?php if ($addrFull): ?>
                      <hr>
                      <div class="small text-muted">Address</div>
                      <div class="fw-semibold" style="white-space:pre-line"><?= htmlspecialchars($addrFull) ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="rounded-4 bg-white border p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="small text-muted">Recent Orders</div>
                      <a href="#tab-orders" class="small text-brand" data-go-orders>View all</a>
                    </div>
                    <ul class="list-unstyled m-0 mt-2">
                      <?php foreach (array_slice($orders, 0, 2) as $i => $o): ?>
                        <li class="py-2 <?= $i===0 ? 'border-bottom' : '' ?>">
                          <div class="d-flex justify-content-between">
                            <span>#<?= (int)$o['id'] ?> • <?= (int)$o['items'] ?> item<?= $o['items']>1?'s':'' ?></span>
                            <span class="fw-semibold">$ <?= number_format((float)$o['total'], 2) ?></span>
                          </div>
                          <div class="small text-muted">
                            <?php $pLabel = order_payment_label($o['status'] ?? '', $o['payment_method'] ?? '', $o['fulfillment_status'] ?? ''); ?>
                            <?= htmlspecialchars($pLabel) ?> • <?= date('d M', strtotime($o['created_at'])) ?>
                          </div>
                        </li>
                      <?php endforeach; ?>
                      <?php if (empty($orders)): ?><li class="py-2 text-muted">No orders yet.</li><?php endif; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Orders -->
            <div class="tab-pane fade" id="tab-orders" role="tabpanel">
              <div class="glass-card p-3 rounded-4 shadow-sm">
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Order</th>
                        <th>Items</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php if ($orders): foreach ($orders as $o): ?>
                      <?php
                        $pLabel = order_payment_label($o['status'] ?? '', $o['payment_method'] ?? '', $o['fulfillment_status'] ?? '');
                        $pColor = order_status_bscolor($pLabel);

                        $fsRaw   = strtolower($o['fulfillment_status'] ?? '');
                        $fsKey   = in_array($fsRaw, ['delivered','completed'], true) ? 'delivered' : (in_array($fsRaw, ['processing','shipped'], true) ? $fsRaw : 'processing');
                        $fsLabel = ucfirst($fsKey);
                        $fsColor = order_status_bscolor($fsKey);

                        $itemsPrev = items_preview($o['items_json'] ?? null);
                        $itemsFull = items_full_html($o['items_json'] ?? null);
                      ?>
                      <tr>
                        <td class="fw-semibold">#<?= (int)$o['id'] ?></td>
                        <td>
                          <span class="order-items text-decoration-underline" role="button" tabindex="0"><?= $itemsPrev ?></span>
                          <div class="d-none items-popover"><?= $itemsFull ?></div>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d', strtotime($o['created_at']))) ?></td>
                        <td>
                          <span class="badge bg-<?= $pColor ?>-subtle text-<?= $pColor ?> border rounded-pill px-3 py-2">
                            <?= htmlspecialchars($pLabel) ?>
                          </span>
                        </td>
                        <td>
                          <span class="badge bg-<?= $fsColor ?>-subtle text-<?= $fsColor ?> border rounded-pill px-3 py-2">
                            <?= htmlspecialchars($fsLabel) ?>
                          </span>
                        </td>
                        <td class="text-end fw-semibold">$ <?= number_format((float)$o['total'], 2) ?></td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                          <i class="bi bi-bag-x fs-4 d-block mb-2"></i>
                          No orders found.
                        </td>
                      </tr>
                    <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Profile -->
            <div class="tab-pane fade" id="tab-profile" role="tabpanel">
              <form class="row g-3 needs-validation" novalidate method="post" action="account.php">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="profile">

                <div class="col-md-6">
                  <input type="text" class="form-control pill-input" name="name" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Full name" required>
                  <div class="invalid-feedback">Name required.</div>
                </div>

                <div class="col-md-6">
                  <input type="email" class="form-control pill-input" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
                  <div class="invalid-feedback">Valid email required.</div>
                </div>

                <div class="col-md-6">
                  <input type="tel" class="form-control pill-input" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Phone">
                </div>

                <div class="col-md-6">
                  <input type="text" class="form-control pill-input" name="state" value="<?= htmlspecialchars($user['state']) ?>" placeholder="State / Province">
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control pill-input" name="city" value="<?= htmlspecialchars($user['city']) ?>" placeholder="City">
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control pill-input" name="postal_code" value="<?= htmlspecialchars($user['postal_code']) ?>" placeholder="Postal code">
                </div>

                <div class="col-12">
                  <input type="text" class="form-control pill-input" name="address_line1" value="<?= htmlspecialchars($user['address_line1']) ?>" placeholder="Address line 1">
                </div>
                <div class="col-12">
                  <input type="text" class="form-control pill-input" name="address_line2" value="<?= htmlspecialchars($user['address_line2']) ?>" placeholder="Address line 2 (optional)">
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-brand my-3 px-4 col-12 add-btn">Save Changes</button>
                </div>
              </form>
            </div>

            <!-- Password -->
            <div class="tab-pane fade" id="tab-password" role="tabpanel">
              <form class="row g-3 needs-validation" novalidate method="post" action="account.php" id="passwordForm">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="password">
                <div class="col-12">
                  <div class="position-relative">
                    <input type="password" class="form-control pill-input" name="current_password" placeholder="Current password" required>
                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 px-3 toggle-pass" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
                  </div>
                  <div class="invalid-feedback">Enter your current password.</div>
                </div>
                <div class="col-md-6">
                  <div class="position-relative">
                    <input type="password" class="form-control pill-input" id="newPass" name="new_password" placeholder="New password" required minlength="6" autocomplete="new-password">
                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 px-3 toggle-pass" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
                  </div>
                  <div class="invalid-feedback">Min 6 characters.</div>
                </div>
                <div class="col-md-6">
                  <div class="position-relative">
                    <input type="password" class="form-control pill-input" id="newPass2" name="confirm_password" placeholder="Confirm new password" required autocomplete="new-password">
                    <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-2 px-3 toggle-pass" aria-label="Toggle password"><i class="bi bi-eye"></i></button>
                  </div>
                  <div class="invalid-feedback">Passwords must match.</div>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-brand my-3 px-4 col-12">Update Password</button>
                </div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Enable item popovers
  document.querySelectorAll('#tab-orders .order-items').forEach(el => {
    const html = el.parentElement.querySelector('.items-popover')?.innerHTML || '';
    if (!html || !window.bootstrap) return;
    const ex = bootstrap.Popover.getInstance(el); if (ex) ex.dispose();
    new bootstrap.Popover(el, {container:'body', html:true, trigger:'hover focus', placement:'auto', content:html});
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });

  const p1=document.getElementById('newPass'), p2=document.getElementById('newPass2');
  function match(){
    if(!p1||!p2) return;
    if(!p2.value){ p2.setCustomValidity(''); p2.classList.remove('is-invalid','is-valid'); return; }
    if(p1.value!==p2.value){ p2.setCustomValidity('Passwords must match.'); p2.classList.add('is-invalid'); p2.classList.remove('is-valid'); }
    else { p2.setCustomValidity(''); p2.classList.remove('is-invalid'); p2.classList.add('is-valid'); }
  }
  ['input','change','keyup','blur'].forEach(ev=>{ p1?.addEventListener(ev,match); p2?.addEventListener(ev,match); });

  document.querySelectorAll('.toggle-pass').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const input = btn.parentElement.querySelector('input'); if(!input) return;
      input.type = input.type==='password' ? 'text' : 'password';
      const i = btn.querySelector('i'); if(i) i.className = input.type==='password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
  });
});

document.addEventListener('click', e=>{
  const go=e.target.closest('[data-go-orders]'); if(!go) return; e.preventDefault();
  const desktopBtn=document.querySelector('.account-nav .nav-link[data-bs-target="#tab-orders"]');
  const mobileBtn=document.querySelector('#acctTabsMobile .nav-link[data-bs-target="#tab-orders"]');
  if(window.bootstrap){
    if(desktopBtn) new bootstrap.Tab(desktopBtn).show();
    if(mobileBtn) new bootstrap.Tab(mobileBtn).show();
  } else {
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('show','active'));
    document.querySelector('#tab-orders')?.classList.add('show','active');
  }
  document.querySelector('#tab-orders')?.scrollIntoView({behavior:'smooth'});
});
</script>
