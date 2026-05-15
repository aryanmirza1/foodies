<?php
// admin/print-order.php  — STANDALONE single-order print page (no admin CSS)
require_once(__DIR__ . '/../includes/db.php'); // must define $conn (mysqli)
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }
function items_arr($json){ $a = json_decode((string)$json, true); return is_array($a) ? $a : []; }

/* NEW: turn a DB path like "uploads/..." into "../uploads/..." relative to /admin/
   Leaves absolute and data URLs as-is. */
function media_url($path){
  $p = trim((string)$path);
  if ($p === '') return '';
  // absolute (http/https or protocol-relative) or data URI → return as-is
  if (preg_match('#^(?:https?:)?//#i', $p) || strncmp($p, 'data:', 5) === 0) return $p;

  // normalize slashes and remove leading "./" or "/"
  $p = str_replace('\\', '/', $p);
  if (strpos($p, './') === 0) $p = substr($p, 2);
  $p = ltrim($p, '/');

  // if it already starts with ../, keep it; else prefix ../ (because this file lives in /admin/)
  if (strpos($p, '../') === 0) return $p;
  return '../' . $p;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(404); die('Invalid order id'); }

$sql = "SELECT id,user_id,contact_name,contact_email,contact_phone,
               address_street,address_city,address_zip,
               items_json,subtotal,delivery,total,
               payment_method,payment_id,payment_proof,notes,status,fulfillment_status,
               created_at,updated_at
        FROM `order`
        WHERE id=? LIMIT 1";
$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$o = mysqli_fetch_assoc($r);
mysqli_stmt_close($st);

if (!$o) { http_response_code(404); die('Order not found'); }

$items = items_arr($o['items_json']);
$ful   = ($o['fulfillment_status']==='delivered') ? 'Delivered' : 'Processing';
$pm    = ($o['payment_method']==='cod') ? 'COD'
        : (($o['status']==='paid') ? 'Online — Approved'
          : (($o['status']==='cancelled') ? 'Online — Rejected' : 'Online — Pending'));

// Build safe proof URL (may be empty)
$proofUrl = media_url($o['payment_proof'] ?? '');
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order #<?= (int)$o['id'] ?> — Print</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{ --ink:#111; --muted:#666; --border:#e5e5e5; --bg:#f5f5f5; --card:#fff; --accent:#6E3B16; }
  *{ box-sizing:border-box; }
  body{ margin:0; background:var(--bg); color:var(--ink);
        font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  .wrap{ max-width:1000px; margin:24px auto 40px; padding:0 16px; }
  .header{ background:var(--card); border:1px solid var(--border); border-radius:12px;
           padding:12px; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
  .title{ margin:0; font-size:18px; font-weight:700; color:var(--accent); }
  .sub{ color:var(--muted); font-size:12px; margin-top:4px; }
  .btns{ display:flex; gap:8px; flex-wrap:wrap; }
  .btn{ border:1px solid var(--border); background:#fff; color:#111; text-decoration:none;
        padding:8px 12px; border-radius:10px; font-size:13px; cursor:pointer; }
  .btn.primary{ background:var(--accent); color:#fff; border-color:var(--accent); }
  .grid{ display:grid; grid-template-columns: 1.2fr .8fr; gap:14px; margin:14px 0; }
  .card{ background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
  .card-h{ background:#fafafa; padding:10px 12px; font-weight:600; }
  .card-b{ padding:12px; }
  .muted{ color:var(--muted); }
  .badge{ display:inline-block; padding:4px 10px; border-radius:999px; border:1px solid var(--border); font-size:12px; }
  .proof{ max-width:100%; height:auto; border:1px solid var(--border); border-radius:8px; }
  table{ width:100%; border-collapse:collapse; }
  th,td{ padding:10px; border-bottom:1px solid var(--border); }
  thead th{ background:#fafafa; text-align:left; }
  tfoot td{ font-weight:600; }
  .right{ text-align:right; }
  @media print{
    .header{ display:none; }
    .wrap{ margin:0; max-width:none; padding:0 12mm; }
    .card{ break-inside: avoid; }
  }
</style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div>
        <h1 class="title">Order #<?= (int)$o['id'] ?></h1>
        <div class="sub">
          Placed: <?= e(date('M d, Y H:i', strtotime($o['created_at']))) ?> •
          Fulfillment: <?= e($ful) ?>
        </div>
      </div>
      <div class="btns">
        <a class="btn" href="manage-orders.php">Back</a>
        <button class="btn" onclick="window.print()">Print</button>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <div class="card-h">Customer</div>
        <div class="card-b">
          <div><strong><?= e($o['contact_name'] ?: '—') ?></strong></div>
          <div class="muted"><?= e($o['contact_email'] ?: '—') ?></div>
          <div class="muted"><?= e($o['contact_phone'] ?: '—') ?></div>
          <hr>
          <div class="muted">
            <?= e($o['address_street'] ?: '') ?><br>
            <?= e($o['address_city'] ?: '') ?> <?= e($o['address_zip'] ?: '') ?>
          </div>
          <?php if (!empty($o['notes'])): ?>
            <hr>
            <div><strong>Notes</strong></div>
            <div class="muted"><?= nl2br(e($o['notes'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-h">Payment</div>
        <div class="card-b">
          <div class="badge"><?= e($pm) ?></div>
          <?php if ($proofUrl !== ''): ?>
            <div class="muted" style="margin-top:10px;">Payment screenshot:</div>
            <a href="<?= e($proofUrl) ?>" target="_blank">
              <img class="proof" src="<?= e($proofUrl) ?>" alt="Payment proof">
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-h">Items</div>
      <div class="card-b" style="padding:0;">
        <table>
          <thead>
            <tr>
              <th>Item</th>
              <th class="right" style="width:120px;">Qty</th>
              <th class="right" style="width:140px;">Unit</th>
              <th class="right" style="width:160px;">Line Total</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$items): ?>
              <tr><td colspan="4" class="muted">No items captured.</td></tr>
            <?php else: foreach ($items as $it):
              $name = $it['name'] ?? $it['product_name'] ?? 'Item';
              $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
              $unit = (float)($it['price'] ?? $it['unit_price'] ?? 0);
              $line = (float)($it['line_total'] ?? ($qty * $unit));
            ?>
              <tr>
                <td><?= e($name) ?></td>
                <td class="right"><?= $qty ?></td>
                <td class="right">$ <?= money($unit) ?></td>
                <td class="right">$ <?= money($line) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="right">Subtotal</td>
              <td class="right">$ <?= money($o['subtotal']) ?></td>
            </tr>
            <tr>
              <td colspan="3" class="right">Delivery</td>
              <td class="right">$ <?= money($o['delivery']) ?></td>
            </tr>
            <tr>
              <td colspan="3" class="right"><strong>Total</strong></td>
              <td class="right"><strong>$ <?= money($o['total']) ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="muted" style="margin-top:10px;">
      Updated: <?= e(date('M d, Y H:i', strtotime($o['updated_at']))) ?>
    </div>
  </div>
</body>
</html>
