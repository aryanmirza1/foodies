<?php
session_start();
require_once("../assets/inc/admin-top.php");
header('Content-Type: application/json');
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Bad id']); exit; }

$q = "SELECT fulfillment_status FROM `order` WHERE id=?";
if ($st = mysqli_prepare($conn, $q)){
  mysqli_stmt_bind_param($st, "i", $id);
  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $row = mysqli_fetch_assoc($res);
  mysqli_stmt_close($st);

  if (!$row) { echo json_encode(['ok'=>false,'msg'=>'Not found']); exit; }
  $next = ($row['fulfillment_status']==='delivered') ? 'processing' : 'delivered';

  $u = "UPDATE `order` SET fulfillment_status=?, updated_at=NOW() WHERE id=?";
  if ($st2 = mysqli_prepare($conn, $u)){
    mysqli_stmt_bind_param($st2, "si", $next, $id);
    $ok = mysqli_stmt_execute($st2);
    mysqli_stmt_close($st2);
    echo json_encode(['ok'=>$ok, 'fulfillment_status'=>$next]);
    exit;
  }
}

echo json_encode(['ok'=>false,'msg'=>'DB error']);
