<?php
// admin/orders-print-bulk.php
require_once(__DIR__ . '/../includes/db.php');
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

function items_full_html($json){
  $arr = json_decode((string)$json, true);
  if (!is_array($arr) || !$arr) return '—';
  $h = '<ul style="margin:0;padding-left:16px">';
  foreach ($arr as $it) {
    $name = e($it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item');
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    $h   .= '<li><strong>'.$name.'</strong> × '.$qty.'</li>';
  }
  return $h.'</ul>';
}
function full_address($street='', $city='', $zip=''){
  $parts = array_filter([trim((string)$street), trim((string)$city), trim((string)$zip)], fn($v)=>$v!=='');
  return $parts ? implode(', ', $parts) : '—';
}
function payment_status_text($m){ return ($m==='cod') ? 'COD' : 'Paid'; }

/* ---------- Fetch rows ---------- */
$ids_csv = trim($_GET['ids'] ?? '');
$rows=[]; $sumTotal=0.0; $cod=0; $online=0; $delivered=0; $processing=0; $q=null;

if ($ids_csv !== '') {
  $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $ids_csv)))));
  if ($ids) {
    $orderField = 'FIELD(id,'.implode(',',$ids).')';
    $sql = "SELECT id, contact_name, contact_phone, address_street, address_city, address_zip,
                   items_json, total, payment_method, fulfillment_status, created_at
            FROM `order`
            WHERE status='paid' AND id IN (".implode(',',$ids).")
            ORDER BY $orderField";
    $q = mysqli_query($conn, $sql);
  }
}
if (empty($q)) {
  $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
  $date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';
  $where = ["status='paid'"];
  if ($date_from !== '') $where[] = "DATE(created_at) >= '".mysqli_real_escape_string($conn,$date_from)."'";
  if ($date_to   !== '') $where[] = "DATE(created_at) <= '".mysqli_real_escape_string($conn,$date_to)."'";
  $sql = "SELECT id, contact_name, contact_phone, address_street, address_city, address_zip,
                 items_json, total, payment_method, fulfillment_status, created_at
          FROM `order` ".('WHERE '.implode(' AND ',$where))." ORDER BY created_at DESC";
  $q = mysqli_query($conn, $sql);
}

if ($q) while ($r = mysqli_fetch_assoc($q)) {
  $ful = ($r['fulfillment_status'] === 'delivered') ? 'Delivered' : 'Processing';
  $rows[] = [
    'id'      => (int)$r['id'],
    'customer'=> $r['contact_name'] ?? '—',
    'phone'   => $r['contact_phone'] ?? '—',
    'address' => full_address($r['address_street'] ?? '', $r['address_city'] ?? '', $r['address_zip'] ?? ''),
    'items'   => items_full_html($r['items_json']),
    'total'   => (float)($r['total'] ?? 0),
    'payment' => payment_status_text($r['payment_method'] ?? ''),
    'created' => !empty($r['created_at']) ? date('M d, Y H:i', strtotime($r['created_at'])) : '—',
    'ful'     => $ful,
  ];
  $sumTotal += (float)($r['total'] ?? 0);
  ($r['payment_method']==='cod') ? $cod++ : $online++;
  ($ful==='Delivered') ? $delivered++ : $processing++;
}
$chunks = array_chunk($rows, 15);
$qsStr = http_build_query($_GET);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders — Bulk View</title>
<style>
  :root{ --ink:#111; --muted:#666; --border:#e6e6e6; --bg:#f5f5f5; --card:#fff; --accent:#6E3B16; }
  html,body{ margin:0; background:var(--bg); color:var(--ink); font:13px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  .container{ max-width:1200px; margin:24px auto 40px; padding:0 16px; }
  .header{ background:#fff; border:1px solid var(--border); border-radius:12px; padding:12px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
  .title{ font-size:18px; font-weight:700; color:var(--accent); margin:0; }
  .sub{ color:var(--muted); font-size:12px; }
  .btn{ border:1px solid var(--border); background:#fff; padding:8px 12px; border-radius:10px; text-decoration:none; color:#111; }
  .btn.primary{ background:var(--accent); border-color:var(--accent); color:#fff; }
  .stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin:16px 0; }
  .stat{ background:#fff; border:1px solid var(--border); border-radius:12px; padding:12px; }

  .page{ background:#fff; border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:16px; }

  /* Horizontal scroll wrapper for small screens */
  .table-wrap{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .scroll-hint{ display:none; color:var(--muted); font-size:12px; margin:4px 0 8px; }

  /* Table */
  table{ width:100%; border-collapse:collapse; table-layout:auto; }
  thead th{ background:#fafafa; padding:10px; border-bottom:1px solid var(--border); text-align:left; font-weight:600; }
  tbody td{ padding:10px; border-bottom:1px solid var(--border); vertical-align:top; white-space:normal; word-break:normal; overflow-wrap:anywhere; }
  .right{text-align:right}
  .nowrap{ white-space:nowrap; word-break:keep-all; overflow-wrap:normal; }

  /* Nice column plan on wide screens */
  .w-order{width:6%}.w-cust{width:18%}.w-phone{width:12%}.w-addr{width:22%}.w-items{width:22%}.w-total{width:8%}.w-pay{width:6%}.w-created{width:10%}

  /* Make the table wider than viewport on small screens so it scrolls */
  @media screen and (max-width: 768px){
    .container{ max-width:none; padding:0 10px; }
    .scroll-hint{ display:block; }
    table{ min-width: 1100px; } /* adjust if you add/remove columns */
  }

  /* PRINT stays classic */
  @media print{
    @page { size: A4 portrait; margin: 10mm 10mm 12mm 10mm; }
    .header,.stats{display:none}
    .container{margin:0; max-width:none; padding:0 8mm;}
    thead{display:table-header-group}
    .page{border:none;border-radius:0;margin:0 0 10mm 0}
    .table-wrap{ overflow:visible; } /* no scrollbars in print */
    table{ table-layout:auto; min-width:auto; }
    td,th{ font-size:12px; }
  }
</style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div>
        <div class="title">Orders — Bulk View</div>
        <div class="sub">Generated <?= e(date('M d, Y H:i')) ?> • Showing <?= count($rows) ?> order(s)</div>
      </div>
      <div>
        <a class="btn" href="manage-orders.php">Back</a>
        <button class="btn" onclick="window.print()">Print</button>
        <a class="btn primary" href="orders-print-bulk-pdf.php?<?= e($qsStr) ?>">Download PDF</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat"><div>Total Orders</div><div style="font-weight:700;font-size:20px"><?= count($rows) ?></div></div>
      <div class="stat"><div>Total Amount ($)</div><div style="font-weight:700;font-size:20px"><?= money($sumTotal) ?></div></div>
      <div class="stat"><div>Payment (COD / Online)</div><div style="font-weight:700;font-size:20px"><?= (int)$cod ?> / <?= (int)$online ?></div></div>
      <div class="stat"><div>Fulfillment (Delivered / Processing)</div><div style="font-weight:700;font-size:20px"><?= (int)$delivered ?> / <?= (int)$processing ?></div></div>
    </div>

    <?php if (!count($rows)): ?>
      <div class="page"><div style="padding:14px;color:#666;">No orders to show.</div></div>
    <?php else: foreach ($chunks as $chunk): ?>
      <div class="page">
        <div class="scroll-hint">Swipe horizontally to see all columns →</div>
        <div class="table-wrap">
          <table>
            <colgroup>
              <col class="w-order"><col class="w-cust"><col class="w-phone"><col class="w-addr">
              <col class="w-items"><col class="w-total"><col class="w-pay"><col class="w-created">
            </colgroup>
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Items</th>
                <th class="right">Total ($)</th>
                <th>Payment</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($chunk as $r): ?>
              <tr>
                <td class="nowrap">#<?= (int)$r['id'] ?></td>
                <td><?= e($r['customer']) ?></td>
                <td class="nowrap"><?= e($r['phone']) ?></td>
                <td><?= e($r['address']) ?></td>
                <td><?= $r['items'] ?></td>
                <td class="right nowrap"><?= money($r['total']) ?></td>
                <td class="nowrap"><?= e($r['payment']) ?></td>
                <td class="nowrap"><?= e($r['created']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</body>
</html>
