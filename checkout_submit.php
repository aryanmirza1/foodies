<?php
// checkout_submit.php
session_start();
require __DIR__ . '/includes/db.php';
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

function clean($v){ return trim((string)$v); }
function cart_menu_id($cartId){ return (int)preg_replace('/\D+/', '', (string)$cartId); }
function back($msg){ $_SESSION['msg'] = $msg; header("Location: checkout.php"); exit; }
function column_exists(mysqli $conn, string $table, string $col): bool {
  $res = mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '".mysqli_real_escape_string($conn,$col)."'");
  $ok  = $res && mysqli_num_rows($res) > 0;
  if ($res) mysqli_free_result($res);
  return $ok;
}

/* ---- guards ---- */
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) { header("Location: cart.php"); exit; }
$cart = $_SESSION['cart'];

$uid = 0;
if (!empty($_SESSION['user']['id']))          $uid = (int)$_SESSION['user']['id'];
elseif (!empty($_SESSION['site_user']['id'])) $uid = (int)$_SESSION['site_user']['id'];
elseif (!empty($_SESSION['user_id']))         $uid = (int)$_SESSION['user_id'];
if ($uid <= 0) { header("Location: login.php?msg=login_required"); exit; }

/* ---- request sanity ---- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') back('Invalid request.');
// If POST got dropped (usually file too big)
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
  back('Upload exceeded server limit (post_max_size=' . ini_get('post_max_size') . '). Try a smaller file or raise the limit.');
}

/* ---- totals (recompute server-side) ---- */
$subtotal = 0; $menuIds = [];
foreach ($cart as $it) {
  $subtotal += ((int)($it['price'] ?? 0)) * ((int)($it['qty'] ?? 1));
  $mid = cart_menu_id($it['id'] ?? '');
  if ($mid > 0) $menuIds[$mid] = true;
}
$delivery = 0; // highest among items
if ($menuIds) {
  $in  = implode(',', array_map('intval', array_keys($menuIds)));
  $sql = "SELECT delivery FROM menu WHERE id IN ($in) AND status=1";
  if ($r = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($r)) {
      $fee = (int)($row['delivery'] ?? 0);
      if ($fee > $delivery) $delivery = $fee;
    }
    mysqli_free_result($r);
  }
}
$total = $subtotal + $delivery;

/* ---- read POST (accept both old & new field names) ---- */
$name   = clean($_POST['name']  ?? '');
$phone  = clean($_POST['phone'] ?? '');
$email  = clean($_POST['email'] ?? '');
// IMPORTANT: map form names → server names
$street = clean($_POST['street'] ?? ($_POST['address_line1'] ?? '')); // fallback
$city   = clean($_POST['city']  ?? '');
$zip    = clean($_POST['zip']   ?? ($_POST['postal_code'] ?? ''));   // fallback
$notes  = clean($_POST['notes'] ?? '');
$pay    = (($_POST['pay'] ?? 'cod') === 'online') ? 'online' : 'cod';
$payment_id = $pay === 'online' ? (int)($_POST['payment_id'] ?? 0) : 0;

/* ---- minimal validation (zip/state optional) ---- */
$missing = [];
if ($name   === '') $missing[] = 'name';
if ($phone  === '') $missing[] = 'phone';
if ($street === '') $missing[] = 'address line 1';
if ($city   === '') $missing[] = 'city';

if ($pay === 'online') {
  if ($payment_id <= 0) $missing[] = 'payment method';
}

if ($missing) back('Missing: ' . implode(', ', $missing) . '.');

/* ---- upload proof if online (required) ---- */
$payment_proof = '';
if ($pay === 'online') {
  if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] === UPLOAD_ERR_NO_FILE) {
    back('Please attach a payment screenshot.');
  }
  // Handle PHP upload errors explicitly
  if ($_FILES['payment_proof']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['payment_proof']['error'] === UPLOAD_ERR_FORM_SIZE) {
    back('Payment screenshot is too large (limit: ' . ini_get('upload_max_filesize') . ').');
  }
  if ($_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    back('File upload error (code ' . (int)$_FILES['payment_proof']['error'] . ').');
  }

  $tmp   = $_FILES['payment_proof']['tmp_name'];
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = $finfo ? finfo_file($finfo, $tmp) : '';
  if ($finfo) finfo_close($finfo);

  $allow = [
    'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',
    'application/pdf'=>'pdf'
  ];
  if (!isset($allow[$mime])) back('Unsupported file type for payment proof (use JPG/PNG/WebP/GIF/PDF).');

  $dir = __DIR__ . '/uploads/payment_proofs';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $nameSafe = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allow[$mime];
  if (!move_uploaded_file($tmp, $dir.'/'.$nameSafe)) back('Could not save payment screenshot.');
  $payment_proof = 'uploads/payment_proofs/' . $nameSafe;
}

/* ---- cart items → JSON ---- */
$items = [];
foreach ($cart as $it) {
  $items[] = [
    'menu_id' => cart_menu_id($it['id'] ?? ''),
    'name'    => (string)($it['name'] ?? ''),
    'price'   => (float)($it['price'] ?? 0),
    'qty'     => (int)($it['qty'] ?? 1),
    'image'   => (string)($it['image'] ?? '')
  ];
}
$items_json = json_encode($items, JSON_UNESCAPED_UNICODE);

/* ---- status ---- */
$status  = ($pay === 'online') ? 'pending' : 'new';

/* ---- insert order (optionally with fulfillment_status) ---- */
$hasFulfill = column_exists($conn, 'order', 'fulfillment_status');

if ($hasFulfill) {
  $sql = "INSERT INTO `order`
    (user_id, contact_name, contact_phone, contact_email,
     address_street, address_city, address_zip,
     items_json, subtotal, delivery, total,
     payment_method, payment_id, payment_proof, notes,
     status, fulfillment_status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())";

  $stmt = mysqli_prepare($conn, $sql);
  $fulfill = 'processing'; // default

  // i + 7s + 3d + s + i + 4s  = 17 params
  mysqli_stmt_bind_param(
    $stmt,
    'isssssssdddsissss',
    $uid, $name, $phone, $email,
    $street, $city, $zip,
    $items_json, $subtotal, $delivery, $total,
    $pay, $payment_id, $payment_proof, $notes,
    $status, $fulfill
  );
} else {
  $sql = "INSERT INTO `order`
    (user_id, contact_name, contact_phone, contact_email,
     address_street, address_city, address_zip,
     items_json, subtotal, delivery, total,
     payment_method, payment_id, payment_proof, notes,
     status, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())";

  $stmt = mysqli_prepare($conn, $sql);
  // i + 7s + 3d + s + i + 3s = 16 params
  mysqli_stmt_bind_param(
    $stmt,
    'isssssssdddsisss',
    $uid, $name, $phone, $email,
    $street, $city, $zip,
    $items_json, $subtotal, $delivery, $total,
    $pay, $payment_id, $payment_proof, $notes,
    $status
  );
}

if (!$stmt || !mysqli_stmt_execute($stmt)) {
  if ($stmt) mysqli_stmt_close($stmt);
  back('Could not place order. Please try again.');
}

$order_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

/* ---- last_order summary for success page ---- */
$_SESSION['last_order'] = [
  'id'         => $order_id,
  'created_at' => date('Y-m-d H:i:s'),
  'contact'    => ['name'=>$name, 'phone'=>$phone, 'email'=>$email],
  'address'    => ['street'=>$street, 'city'=>$city, 'zip'=>$zip],
  'amounts'    => ['subtotal'=>$subtotal, 'delivery'=>$delivery, 'total'=>$total],
  'payment'    => ['method'=>$pay, 'proof'=>$payment_proof, 'payment_id'=>$payment_id],
  'status'     => $status,
  'notes'      => $notes,
  'items'      => $items
];

/* ---- clear cart & go to success ---- */
unset($_SESSION['cart']);
header("Location: order_success.php?order_id=".$order_id);
exit;
