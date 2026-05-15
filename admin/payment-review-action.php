<?php
// admin/payment-review-action.php — handle Approve/Reject and email customer via PHPMailer
session_start();
require_once("../assets/inc/admin-top.php");
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn,'utf8mb4'); }

/* ------------------------------- helpers ------------------------------- */

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

/* Canonical redirect to avoid loops (adjust if you use .php URLs) */
function review_url(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'];
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /food-app/admin
  // If you prefer the .php URL, change to '/payments-review.php'
  return $scheme . '://' . $host . $base . '/payments-review';
}

function msg(string $html): void {
  $_SESSION['msg'] = $html;
  session_write_close();
  header('Location: ' . review_url(), true, 303); // PRG pattern
  exit;
}

/* Find composer autoload (up to 6 dirs up) */
function find_autoload(string $start): ?string {
  $dir = $start;
  for ($i = 0; $i < 6; $i++) {
    $p = $dir . '/vendor/autoload.php';
    if (is_file($p)) return $p;
    $dir = dirname($dir);
  }
  return null;
}
$autoload = find_autoload(__DIR__);
if (!$autoload) {
  // Do NOT redirect here — we want to avoid redirect loops if the page itself redirects
  header('Content-Type: text/html; charset=utf-8');
  echo '<div style="font:14px/1.5 system-ui;max-width:640px;margin:2rem auto;padding:1rem;border:1px solid #eee;border-radius:8px;">
          <h3 style="margin:0 0 .5rem 0;color:#b02a37;">PHPMailer not installed</h3>
          <p>Run: <code>composer require phpmailer/phpmailer</code></p>
        </div>';
  exit;
}
require_once $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/* ------------------------------- mail config ------------------------------- */
/* Replace with your SMTP credentials (Gmail example shown) */
const MAIL_FROM_EMAIL = 'aryanmirza112233@gmail.com';
const MAIL_FROM_NAME  = 'Foodies';
const SMTP_HOST       = 'smtp.gmail.com';
const SMTP_PORT       = 587;           // 465 for SSL, 587 for TLS
const SMTP_USER       = 'aryanmirza112233@gmail.com';
const SMTP_PASS       = 'kpamtdcmwlhlkofb';  // app password
const SMTP_SECURE     = 'tls';         // 'tls' or 'ssl'

function send_mail_smtp(string $to, string $subject, string $html): bool {
  if ($to === '') return false;
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = trim(strip_tags(str_replace(['</tr>','</p>','<br>'], ["\n","\n","\n"], $html)));

    return $mail->send();
  } catch (MailException $e) {
    // You could log $e->getMessage() here
    return false;
  }
}

/* ------------------------------- order + email builders ------------------------------- */

function fetch_order(mysqli $conn, int $id): ?array {
  $sql = "SELECT id, user_id, contact_name, contact_email, contact_phone,
                 address_street, address_city, address_zip,
                 items_json, subtotal, delivery, total,
                 payment_method, status, fulfillment_status, created_at
          FROM `order`
          WHERE id=? LIMIT 1";
  if (!$st = mysqli_prepare($conn, $sql)) return null;
  mysqli_stmt_bind_param($st, "i", $id);
  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $row = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($st);
  return $row ?: null;
}

function items_table_html(?string $json): string {
  $arr = json_decode((string)$json, true);
  if (!is_array($arr) || !$arr) {
    return '<tr><td colspan="4" style="color:#666;padding:10px 0">No items captured.</td></tr>';
  }
  $h = '';
  foreach ($arr as $it) {
    $name = e($it['name'] ?? $it['product_name'] ?? 'Item');
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    $unit = (float)($it['price'] ?? $it['unit_price'] ?? 0);
    $line = (float)($it['line_total'] ?? ($qty * $unit));
    $h .= '<tr>'
        . '<td>'.$name.'</td>'
        . '<td style="text-align:center">'.$qty.'</td>'
        . '<td style="text-align:right">$ '.money($unit).'</td>'
        . '<td style="text-align:right">$ '.money($line).'</td>'
        . '</tr>';
  }
  return $h;
}

function build_email_html(array $o, string $decision): string {
  $niceDecision = ($decision === 'approved') ? 'Approved' : 'Rejected';
  $badgeColor   = ($decision === 'approved') ? '#198754' : '#dc3545';
  $pm           = ($o['payment_method']==='cod') ? 'Cash on Delivery' : 'Online Payment';
  $created      = !empty($o['created_at']) ? date('M d, Y H:i', strtotime($o['created_at'])) : '—';

  ob_start(); ?>
  <!doctype html>
  <html>
  <body style="margin:0;background:#f5f5f5;color:#111;font:14px/1.5 -apple-system,Segoe UI,Roboto,Arial,sans-serif">
    <div style="max-width:640px;margin:24px auto;padding:0 14px">
      <div style="background:#fff;border:1px solid #eee;border-radius:10px;overflow:hidden">
        <div style="padding:16px 18px;background:#fafafa;border-bottom:1px solid #eee">
          <div style="font-size:18px;font-weight:700;color:#6E3B16;">Order #<?= (int)$o['id'] ?></div>
          <div style="color:#666;font-size:12px;margin-top:4px">Placed: <?= e($created) ?></div>
        </div>

        <div style="padding:18px">
          <p style="margin:0 0 10px 0">Hi <strong><?= e($o['contact_name'] ?: 'there') ?></strong>,</p>
          <p style="margin:0 0 16px 0">
            Your order has been
            <span style="display:inline-block;padding:2px 8px;border-radius:999px;background:<?= $badgeColor ?>;color:#fff;font-weight:700;">
              <?= $niceDecision ?>
            </span>.
          </p>

          <div style="margin:14px 0 8px 0;font-weight:700">Order Summary</div>
          <table style="width:100%;border-collapse:collapse">
            <thead>
              <tr>
                <th style="text-align:left;border-bottom:1px solid #eee;padding:6px 0">Item</th>
                <th style="text-align:center;border-bottom:1px solid #eee;padding:6px 0;width:80px">Qty</th>
                <th style="text-align:right;border-bottom:1px solid #eee;padding:6px 0;width:110px">Unit</th>
                <th style="text-align:right;border-bottom:1px solid #eee;padding:6px 0;width:130px">Line Total</th>
              </tr>
            </thead>
            <tbody><?= items_table_html($o['items_json']) ?></tbody>
            <tfoot>
              <tr>
                <td colspan="3" style="text-align:right;padding-top:8px;font-weight:700">Subtotal</td>
                <td style="text-align:right;padding-top:8px;font-weight:700">$ <?= money($o['subtotal']) ?></td>
              </tr>
              <tr>
                <td colspan="3" style="text-align:right">Delivery</td>
                <td style="text-align:right">$ <?= money($o['delivery']) ?></td>
              </tr>
              <tr>
                <td colspan="3" style="text-align:right;font-size:16px;font-weight:800">Total</td>
                <td style="text-align:right;font-size:16px;font-weight:800">$ <?= money($o['total']) ?></td>
              </tr>
            </tfoot>
          </table>

          <div style="margin:16px 0 6px 0;font-weight:700">Delivery Address</div>
          <div style="color:#333">
            <?= e($o['address_street'] ?: '') ?><br>
            <?= e($o['address_city'] ?: '') ?> <?= e($o['address_zip'] ?: '') ?>
          </div>

          <div style="margin:16px 0 6px 0;font-weight:700">Payment</div>
          <div><?= e($pm) ?></div>

          <?php if ($decision === 'approved'): ?>
            <p style="margin:16px 0 0 0">We’ll start processing your order shortly. You’ll receive another update when it’s out for delivery.</p>
          <?php else: ?>
            <p style="margin:16px 0 0 0">We’re sorry—this order has been rejected. If you think this was in error, please reply to this email.</p>
          <?php endif; ?>

          <p style="margin:16px 0 0 0;color:#666;font-size:12px">— <?= e(MAIL_FROM_NAME) ?></p>
        </div>
      </div>
    </div>
  </body>
  </html>
  <?php
  return ob_get_clean();
}

/* ------------------------------- handle request ------------------------------- */

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($id <= 0 || !in_array($action, ['approve','reject'], true)) {
  msg('<div class="alert alert-danger msg"><strong>Error:</strong> Invalid request.</div>');
}

$order = fetch_order($conn, $id);
if (!$order) {
  msg('<div class="alert alert-warning msg">Order not found.</div>');
}
$email = trim((string)($order['contact_email'] ?? ''));

/* Approve: confirm any NEW/PENDING/CANCELLED (both COD and Online) */
if ($action === 'approve') {
  $sql = "UPDATE `order`
          SET status='paid', updated_at=NOW()
          WHERE id=? AND status IN ('new','pending','cancelled')";
  if ($st = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $rows = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);

    // Re-fetch for accurate email
    $order = fetch_order($conn, $id) ?: $order;

    if ($email !== '') {
      @send_mail_smtp($email, 'Order #'.$order['id'].' Confirmed', build_email_html($order, 'approved'));
    }

    if ($rows > 0) {
      msg('<div class="alert alert-success msg"><strong>Approved:</strong> Order confirmed and customer notified by email.</div>');
    } else {
      msg('<div class="alert alert-info msg">No change made. The order may already be confirmed. Email was still attempted.</div>');
    }
  } else {
    msg('<div class="alert alert-danger msg">Could not update order.</div>');
  }
}

/* Reject: email then delete the order */
if ($action === 'reject') {
  if ($email !== '') {
    @send_mail_smtp($email, 'Order #'.$order['id'].' Rejected', build_email_html($order, 'rejected'));
  }

  if ($st = mysqli_prepare($conn, "DELETE FROM `order` WHERE id=? LIMIT 1")) {
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $rows = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);

    if ($rows > 0) {
      msg('<div class="alert alert-success msg"><strong>Rejected:</strong> Order deleted and customer notified by email.</div>');
    } else {
      msg('<div class="alert alert-warning msg">Could not delete (maybe already removed). Email was still attempted.</div>');
    }
  } else {
    msg('<div class="alert alert-danger msg">Could not delete order.</div>');
  }
}

/* Fallback */
msg('<div class="alert alert-danger msg">Unhandled action.</div>');
