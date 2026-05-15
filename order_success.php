<?php
// order_success.php
session_start();

// DB (needed to read current fulfillment_status and latest payment status)
require __DIR__.'/includes/db.php';
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

include __DIR__.'/includes/header.php';
include __DIR__.'/includes/page-hero.php';
include __DIR__ . "/includes/loader.php";

$order = $_SESSION['last_order'] ?? null;

// Helpers for badges
function badge($status){
  $map = [
    'new'       => '<span class="badge bg-secondary">cod</span>',
    'pending'   => '<span class="badge bg-warning text-dark">pending (Waiting for Approval)</span>',
    'paid'      => '<span class="badge bg-success">paid</span>',
    'cancelled' => '<span class="badge bg-danger">cancelled</span>',
  ];
  $s = strtolower((string)$status);
  return $map[$s] ?? '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
}
function fbadge($fulfillment){
  $f = strtolower((string)$fulfillment);
  if ($f === 'delivered')  return '<span class="badge bg-success">delivered</span>';
  if ($f === 'processing') return '<span class="badge bg-warning text-dark">processing</span>';
  return '<span class="badge bg-secondary">'.htmlspecialchars($fulfillment ?: '—').'</span>';
}

// Figure out which order we’re showing
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : (is_array($order) ? (int)$order['id'] : 0);

// Pull latest status/method/fulfillment from DB (so it reflects admin updates)
$payment_method_db = null;
$status_db         = null;
$fulfillment_db    = null;

if ($orderId > 0) {
  if ($st = mysqli_prepare($conn, "SELECT payment_method, status, IFNULL(fulfillment_status,'') AS fs FROM `order` WHERE id=? LIMIT 1")) {
    mysqli_stmt_bind_param($st, "i", $orderId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if ($row = mysqli_fetch_assoc($res)) {
      $payment_method_db = $row['payment_method'] ?: null;
      $status_db         = $row['status'] ?: null;
      $fulfillment_db    = $row['fs'] ?: null;
    }
    mysqli_stmt_close($st);
  }
}

// Compose values with sensible fallbacks
$method = $payment_method_db ?? ($order['payment']['method'] ?? 'cod');
$status = $status_db         ?? ($order['status']          ?? 'new');
$fulfill= $fulfillment_db    ?? ($order['fulfillment']     ?? 'processing'); // default to processing on create
?>
<main class="py-4" style="background:var(--bg)">
  <div class="container" style="max-width:880px">
    <?php if(!$order): ?>
      <div class="alert alert-warning rounded-3">No recent order found.</div>
    <?php else: ?>
      <h2 class="section-title mb-3">Order placed</h2>

      <div class="p-4 glass-card rounded-4">
        <h3 class="mb-1 text-center h4" style="font-family: 'Petit Formal Script', cursive;">Thanks for purchasing</h3>
        <div class="text-muted mb-3">Order #<?= htmlspecialchars($orderId ?: $order['id']) ?> • <?= htmlspecialchars($order['created_at']) ?></div>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="bg-white border rounded-4 p-3 h-100">
              <div class="fw-semibold mb-1">Contact</div> 
              <div class="small"><?= htmlspecialchars($order['contact']['name']) ?></div>
              <div class="small"><?= htmlspecialchars($order['contact']['phone']) ?></div>
              <?php if($order['contact']['email']): ?>
                <div class="small"><?= htmlspecialchars($order['contact']['email']) ?></div>
              <?php endif; ?>
              <hr>
              <div class="fw-semibold mb-1">Delivery address</div>
              <div class="small"><?= htmlspecialchars($order['address']['street']) ?></div>
              <div class="small"><?= htmlspecialchars($order['address']['city']) ?> <?= htmlspecialchars($order['address']['zip']) ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="bg-white border rounded-4 p-3 h-100">
              <div class="fw-semibold mb-2">Summary</div>
              <div class="d-flex justify-content-between small"><span>Subtotal</span><span>$ <?= number_format($order['amounts']['subtotal']) ?></span></div>
              <div class="d-flex justify-content-between small"><span>Delivery</span><span>$ <?= number_format($order['amounts']['delivery']) ?></span></div>
              <div class="d-flex justify-content-between fw-semibold"><span>Total</span><span>$ <?= number_format($order['amounts']['total']) ?></span></div>
              <hr>

              <!-- Payment line -->
              <div class="small">
                Payment: <strong><?= $method==='online' ? 'Online' : 'Cash on Delivery' ?></strong>
                <?= ($method==='online' && $status==='pending') ? ' — waiting for verification' : '' ?>
              </div>
              <?php if(!empty($order['payment']['proof'])): ?>
                <div class="small mt-1">Proof: <a href="<?= htmlspecialchars($order['payment']['proof']) ?>" target="_blank" style="text-decoration:none; color:#6E3B16;">view file</a></div>
              <?php endif; ?>

              <!-- Fulfillment badge (processing / delivered) -->
              <div class="small mt-1">Fulfillment: <?= fbadge($fulfill) ?></div>

              <!-- Payment status badge (pending / paid / cancelled / new) -->
              <div class="small mt-1 text-muted">Payment status: <?= badge($status) ?></div>
            </div>
          </div>
        </div>

        <?php if($order['notes']): ?>
          <div class="mt-3 small text-muted">Notes: “<?= htmlspecialchars($order['notes']) ?>”</div>
        <?php endif; ?>

        <div class="mt-4 d-flex justify-content-center">
          <a href="products.php" class="btn col-12 col-md-6 mx-auto my-md-4 py-2 btn-sm btn-outline-success rounded-pill">Continue shopping</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__.'/includes/footer.php'; ?>
