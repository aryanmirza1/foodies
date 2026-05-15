<?php
// admin/orders-print-bulk-pdf.php — BULK ORDERS → PDF (no fulfillment col, Address column)

require_once(__DIR__ . '/../includes/db.php');
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

function find_autoload(string $start): ?string {
  $dir = $start;
  for ($i=0; $i<6; $i++) {
    $p = $dir.'/vendor/autoload.php';
    if (is_file($p)) return $p;
    $dir = dirname($dir);
  }
  return null;
}
$autoload = find_autoload(__DIR__);
if (!$autoload) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "dompdf not installed (vendor/autoload.php not found).\nRun: composer require dompdf/dompdf";
  exit;
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

/* helpers */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

/* NEW: full items list for direct rendering */
function items_full_html($json){
  $arr = json_decode((string)$json, true);
  if (!is_array($arr) || !$arr) return '—';
  $h = '<ul class="items-list">';
  foreach ($arr as $it) {
    $name = e($it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item');
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    if ($qty < 0) $qty = 0;
    $h .= '<li class="items-list-row"><span class="items-name">'.$name.'</span> <span class="items-qty">× '.$qty.'</span></li>';
  }
  return $h . '</ul>';
}

function full_address($s='', $c='', $z=''){
  $parts = array_filter([trim((string)$s), trim((string)$c), trim((string)$z)], fn($v)=>$v!=='');
  return $parts ? implode(', ', $parts) : '—';
}
function payment_status_text($method, $status){
  $m = strtolower(trim((string)$method));
  $s = strtolower(trim((string)$status));
  if ($m === 'cod')       return 'COD';
  if ($s === 'paid')      return 'Paid';
  if ($s === 'cancelled') return 'Rejected';
  return 'Pending';
}


// … (top & helpers same as you had, keep items_preview or switch to full list if you like)

$ids_csv = trim($_GET['ids'] ?? '');
$rows = []; $q = null;

if ($ids_csv !== '') {
  $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $ids_csv)))));
  if ($ids) {
    $orderField = 'FIELD(id,'.implode(',', $ids).')';
    $sql = "SELECT id, contact_name, contact_phone, address_street, address_city, address_zip,
                   items_json, total, payment_method, status, created_at
            FROM `order`
            WHERE status='paid' AND id IN (".implode(',', $ids).")
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
                 items_json, total, payment_method, status, created_at
          FROM `order` ".('WHERE '.implode(' AND ',$where))."
          ORDER BY created_at DESC";
  $q = mysqli_query($conn, $sql);
}

// … (build $rows and HTML exactly as your working PDF version)


if ($q) {
  while ($r = mysqli_fetch_assoc($q)) {
    $rows[] = [
      'id'       => (int)$r['id'],
      'customer' => $r['contact_name'] ?? '—',
      'phone'    => $r['contact_phone'] ?? '—',
      'address'  => full_address($r['address_street'] ?? '', $r['address_city'] ?? '', $r['address_zip'] ?? ''),
      // CHANGED: full items instead of preview
      'items_html' => items_full_html($r['items_json']),
      'total'    => (float)($r['total'] ?? 0),
      'payment'  => payment_status_text($r['payment_method'], $r['status']), // COD / Pending / Paid / Rejected
      'created'  => !empty($r['created_at']) ? date('M d, Y H:i', strtotime($r['created_at'])) : '—',
    ];
  }
}

/* build HTML (A4 landscape) */
ob_start(); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { size: A4 landscape; margin: 12mm 10mm; }
  body{ font:11px/1.4 DejaVu Sans, Arial, sans-serif; color:#111; }
  .title{ font-weight:700; font-size:15px; color:#6E3B16; margin:0 0 6px 0; }
  .sub{ color:#666; font-size:11px; margin:0 0 8px 0; }

  table{ width:100%; border-collapse:collapse; table-layout:fixed; }
  colgroup col:nth-child(1){ width:7%; }   /* Order */
  colgroup col:nth-child(2){ width:16%; }  /* Customer */
  colgroup col:nth-child(3){ width:12%; }  /* Phone */
  colgroup col:nth-child(4){ width:24%; }  /* Address */
  colgroup col:nth-child(5){ width:19%; }  /* Items */
  colgroup col:nth-child(6){ width:8%; }   /* Total */
  colgroup col:nth-child(7){ width:8%; }   /* Payment */
  colgroup col:nth-child(8){ width:6%; }   /* Created */
  th,td{ border:1px solid #e5e5e5; padding:5px 6px; vertical-align:top; word-break:break-word; }
  th{ background:#f6f6f6; text-align:left; font-weight:600; }
  .right{ text-align:right; }
  .pill{ border:1px solid #e5e5e5; border-radius:999px; padding:1px 5px; font-size:10px; display:inline-block; white-space:nowrap; }
  .pb{ page-break-after: always; } .pb:last-child{ page-break-after: auto; }

  /* NEW: full items list styling */
  .items-list{ margin:0; padding-left:14px; }
  .items-list-row{ margin:0; }
  .items-name{ font-weight:600; }
  .items-qty{ color:#222; }
</style>
</head>
<body>
  <div class="title">Orders — Bulk View</div>
  <div class="sub">Generated <?= e(date('M d, Y H:i')) ?> • Total <?= count($rows) ?> order<?= count($rows)==1?'':'s' ?></div>

  <?php if (!count($rows)): ?>
    <div class="sub">No orders.</div>
  <?php else: foreach (array_chunk($rows, 15) as $chunk): ?>
    <table class="pb">
      <colgroup>
        <col><col><col><col><col><col><col><col>
      </colgroup>
      <thead>
        <tr>
          <th>Order</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Address</th>
          <th>Items</th><!-- CHANGED: show full items -->
          <th class="right">Total ($)</th>
          <th>Payment</th>
          <th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($chunk as $r): ?>
          <tr>
            <td>#<?= (int)$r['id'] ?></td>
            <td><?= e($r['customer']) ?></td>
            <td><?= e($r['phone']) ?></td>
            <td><?= e($r['address']) ?></td>
            <td><?= $r['items_html'] ?></td><!-- CHANGED -->
            <td class="right"><?= money($r['total']) ?></td>
            <td><span class="pill"><?= e($r['payment']) ?></span></td>
            <td><?= e($r['created']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();

/* render PDF */
$opts = new Options();
$opts->set('isRemoteEnabled', true);
$opts->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($opts);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$attachment = isset($_GET['attachment']) ? (int)$_GET['attachment'] : 1;
$fname = 'orders_'.date('Ymd_Hi').'.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($attachment ? 'attachment' : 'inline') . '; filename="'.$fname.'"');
echo $dompdf->output();
