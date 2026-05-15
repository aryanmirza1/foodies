<?php
// admin/order-view.php  — view a single order inside the admin UI
require_once("../assets/inc/admin-top.php");
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

/* ---------------- helpers ---------------- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }
function parse_items($json){ $a = json_decode((string)$json, true); return is_array($a) ? $a : []; }

/* Prefix ../ for files stored relative to web root (e.g. "uploads/...") since this page lives in /admin/ */
function media_url($path){
  $p = trim((string)$path);
  if ($p === '') return '';
  if (preg_match('#^(?:https?:)?//#i', $p) || strncmp($p, 'data:', 5) === 0) return $p; // absolute or data URI
  $p = str_replace('\\', '/', $p);
  if (strpos($p, './') === 0) $p = substr($p, 2);
  $p = ltrim($p, '/');
  if (strpos($p, '../') === 0) return $p;
  return '../' . $p;
}

/* ---------------- load order ---------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); die("Invalid order id."); }

$sql = "SELECT id,user_id,contact_name,contact_email,contact_phone,
               address_street,address_city,address_zip,
               items_json,subtotal,delivery,total,
               payment_method,payment_id,payment_proof,notes,status,fulfillment_status,
               created_at,updated_at
        FROM `order` WHERE id=? LIMIT 1";
$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$r  = mysqli_stmt_get_result($st);
$ord = mysqli_fetch_assoc($r);
mysqli_stmt_close($st);

if (!$ord) { http_response_code(404); die("Order not found."); }

/* ---------------- derived fields ---------------- */
$items        = parse_items($ord['items_json']);
$fulfillment  = $ord['fulfillment_status'] ?: 'processing';
$isDelivered  = (strtolower($fulfillment) === 'delivered');

$toggleLabel  = $isDelivered ? 'Delivered' : 'Processing';
$toggleClass  = $isDelivered ? 'btn-success' : 'btn-outline-primary';

$pmLabel = ($ord['payment_method']==='cod')
  ? 'COD'
  : (($ord['status']==='paid') ? 'Online • Approved'
    : (($ord['status']==='cancelled') ? 'Online • Rejected' : 'Online • Pending'));

$proofUrl = media_url($ord['payment_proof'] ?? '');
?>
<body>
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>
  <div class="page">
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <section class="py-4">
      <div class="container-fluid">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="m-0">
            <i class="fas fa-file-invoice me-2"></i>
            Order #<?= (int)$ord['id'] ?>
          </h3>
          <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="print-order.php?id=<?= (int)$ord['id'] ?>">
              <i class="fas fa-print me-1"></i> Print
            </a>
            <a class="btn btn-sm btn-outline-dark" href="manage-orders.php">Back to Orders</a>
          </div>
        </div>

        <div class="row g-3">
          <!-- Left: Items -->
          <div class="col-lg-8">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Items</strong>
                <span class="text-muted small">
                  Placed: <?= e(date('M d, Y H:i', strtotime($ord['created_at']))) ?>
                </span>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Item</th>
                        <th class="text-center" style="width:120px;">Qty</th>
                        <th class="text-end" style="width:140px;">Unit</th>
                        <th class="text-end" style="width:160px;">Line Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!$items): ?>
                        <tr>
                          <td colspan="4" class="text-muted text-center py-4">No items captured.</td>
                        </tr>
                      <?php else: foreach ($items as $it):
                        $name = $it['name'] ?? $it['product_name'] ?? 'Item';
                        $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
                        $unit = (float)($it['price'] ?? $it['unit_price'] ?? 0);
                        $line = (float)($it['line_total'] ?? ($qty*$unit));
                      ?>
                        <tr>
                          <td><?= e($name) ?></td>
                          <td class="text-center"><?= $qty ?></td>
                          <td class="text-end">$ <?= money($unit) ?></td>
                          <td class="text-end">$ <?= money($line) ?></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                        <td class="text-end">$ <?= money($ord['subtotal']) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Delivery</strong></td>
                        <td class="text-end">$ <?= money($ord['delivery']) ?></td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                        <td class="text-end"><strong>$ <?= money($ord['total']) ?></strong></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <div class="text-muted small mt-2">
              Updated: <?= e(date('M d, Y H:i', strtotime($ord['updated_at']))) ?>
            </div>
          </div>

          <!-- Right: Customer + Payment + Fulfillment -->
          <div class="col-lg-4">
            <!-- Customer -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header bg-white"><strong>Customer</strong></div>
              <div class="card-body">
                <div><strong><?= e($ord['contact_name'] ?: '—') ?></strong></div>
                <div class="text-muted small"><?= e($ord['contact_email'] ?: '—') ?></div>
                <div class="text-muted small"><?= e($ord['contact_phone'] ?: '—') ?></div>
                <hr>
                <div class="small">
                  <?= e($ord['address_street'] ?: '') ?><br>
                  <?= e($ord['address_city'] ?: '') ?> <?= e($ord['address_zip'] ?: '') ?>
                </div>
                <?php if (!empty($ord['notes'])): ?>
                  <hr>
                  <div><strong>Notes</strong></div>
                  <div class="small text-muted"><?= nl2br(e($ord['notes'])) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Payment -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header bg-white"><strong>Payment</strong></div>
              <div class="card-body">
                <div class="mb-2">
                  <?php if ($ord['payment_method']==='cod'): ?>
                    <span class="badge bg-dark">COD</span>
                    <div class="text-muted small mt-1">Doesn’t require approval</div>
                  <?php else: ?>
                    <?php if (in_array($ord['status'], ['new','pending'], true)): ?>
                      <span class="badge bg-warning text-dark">Online • Pending</span>
                      <div class="small mt-2">
                        Review on <a href="payments-review.php">Payment Review</a>.
                      </div>
                    <?php elseif ($ord['status']==='paid'): ?>
                      <span class="badge bg-success">Online • Approved</span>
                    <?php elseif ($ord['status']==='cancelled'): ?>
                      <span class="badge bg-danger">Online • Rejected</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>

                <?php if ($proofUrl !== ''): ?>
                  <div class="mb-2 small text-muted">Payment screenshot</div>
                  <a href="<?= e($proofUrl) ?>" target="_blank" rel="noopener">
                    <img src="<?= e($proofUrl) ?>" alt="Payment proof"
                         style="max-width:100%;height:auto;border:1px solid #eee;border-radius:8px;">
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Fulfillment -->
            <div class="card shadow-sm border-0">
              <div class="card-header bg-white"><strong>Fulfillment</strong></div>
              <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="text-muted small">Toggle Processing ↔ Delivered</div>
                  <button id="toggleBtn"
                          class="btn <?= $toggleClass ?> btn-sm"
                          data-id="<?= (int)$ord['id'] ?>">
                    <?= $toggleLabel ?>
                  </button>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </section>

    <?php require_once("../assets/inc/admin-footer.php"); ?>
  </div>

  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <script>
    // Fulfillment toggle → ajax-fulfillment-toggle.php, then update button UI
    document.getElementById('toggleBtn')?.addEventListener('click', function(){
      const btn = this;
      const id = btn.getAttribute('data-id');
      btn.disabled = true;
      fetch('ajax-fulfillment-toggle.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id),
        cache: 'no-store'
      })
      .then(r => r.json())
      .then(j => {
        btn.disabled = false;
        if (!j || !j.ok) { alert(j?.msg || 'Failed to update.'); return; }
        const delivered = (j.fulfillment_status === 'delivered');
        btn.textContent = delivered ? 'Delivered' : 'Processing';
        btn.className = 'btn btn-sm ' + (delivered ? 'btn-success' : 'btn-outline-primary');
      })
      .catch(() => { btn.disabled = false; alert('Network error'); });
    });
  </script>
</body>
