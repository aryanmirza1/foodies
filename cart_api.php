<?php
// cart_api.php (compatible with frontend expecting d.summary)
session_start();
header('Content-Type: application/json');

require __DIR__ . '/includes/db.php'; // mysqli $conn

function json_out($arr){ echo json_encode($arr); exit; }
function get_body(){ $b = json_decode(file_get_contents('php://input'), true); return is_array($b)?$b:[]; }
function cart_id_to_menu_id($cid){ return (int)preg_replace('/\D+/', '', (string)$cid); }

/** Compute subtotal + delivery(from DB) + total + count */
function compute_summary(mysqli $conn){
  $items = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
  $subtotal = 0; $count = 0; $menuIds = [];

  foreach ($items as $it){
    $qty   = (int)($it['qty'] ?? 0);
    $price = (int)($it['price'] ?? 0);
    $subtotal += $price * $qty;
    $count += $qty;
    $mid = cart_id_to_menu_id($it['id'] ?? '');
    if ($mid > 0) $menuIds[$mid] = true;
  }

  // Delivery policy: use HIGHEST delivery fee among items (change to += to sum)
  $delivery = 0;
  if (!empty($menuIds)) {
    $in = implode(',', array_map('intval', array_keys($menuIds)));
    $sql = "SELECT delivery FROM menu WHERE id IN ($in) AND status=1";
    if ($res = mysqli_query($conn, $sql)) {
      while ($row = mysqli_fetch_assoc($res)) {
        $fee = (int)($row['delivery'] ?? 0);
        if ($fee > $delivery) $delivery = $fee; // or $delivery += $fee;
      }
      mysqli_free_result($res);
    }
  }

  $_SESSION['delivery'] = $delivery;

  return [
    'subtotal' => $subtotal,
    'delivery' => $delivery,
    'total'    => $subtotal + $delivery,
    'count'    => $count,
  ];
}

function ok_summary(mysqli $conn, array $extra = []){
  return array_merge(['ok'=>true, 'summary'=>compute_summary($conn)], $extra);
}

$body = get_body();
$action = $body['action'] ?? 'summary';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

/* ADD */
if ($action === 'add') {
  $it = $body['item'] ?? [];
  $id = (string)($it['id'] ?? '');
  if ($id === '') json_out(['ok'=>false,'error'=>'Missing id']);

  if (!isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] = [
      'id'       => $id,
      'name'     => (string)($it['name'] ?? ''),
      'price'    => (int)($it['price'] ?? 0),
      'image'    => (string)($it['image'] ?? ''),
      'category' => (string)($it['category'] ?? ''),
      'qty'      => max(1, (int)($it['qty'] ?? 1)),
    ];
  } else {
    $_SESSION['cart'][$id]['qty'] += max(1, (int)($it['qty'] ?? 1));
  }

  json_out(ok_summary($conn, ['item'=>$_SESSION['cart'][$id]]));
}

/* SET QTY */
if ($action === 'setQty') {
  $id  = (string)($body['id'] ?? '');
  $qty = max(1, (int)($body['qty'] ?? 1));
  if ($id === '' || !isset($_SESSION['cart'][$id])) json_out(['ok'=>false,'error'=>'Not found']);

  $_SESSION['cart'][$id]['qty'] = $qty;
  json_out(ok_summary($conn, ['item'=>$_SESSION['cart'][$id]]));
}

/* REMOVE */
if ($action === 'remove') {
  $id = (string)($body['id'] ?? '');
  if (isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
  json_out(ok_summary($conn));
}

/* CLEAR */
if ($action === 'clear') {
  $_SESSION['cart'] = [];
  json_out(ok_summary($conn));
}

/* SUMMARY (default) */
json_out(ok_summary($conn));
