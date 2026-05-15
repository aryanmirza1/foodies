<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();

require_once("config.php");
require_once("functions.php");

$pageName = basename($_SERVER["PHP_SELF"], '.php');

// ---------- AUTH GUARD (skip on login) ----------
$isLoginPage = strtolower(basename($_SERVER['PHP_SELF'])) === 'index.php';
$skipAuth    = !empty($SKIP_ADMIN_AUTH) || $isLoginPage;

if (!$skipAuth) {
  if (empty($_SESSION["ADMIN_LOGIN"]["ADMIN_ID"])) {
    // not logged in → go to admin login
    header("Location: index.php");
    exit;
  }
}
// -----------------------------------------------

// (optional) prevent caching of authed pages
if (!$skipAuth) {
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Pragma: no-cache");
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?php echo ucwords(str_replace("-", " ", $pageName)) ?></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="all,follow">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/choices.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.3/css/dataTables.dataTables.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.default.css" id="theme-stylesheet">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/custom.css">
  <link rel="shortcut icon" href="<?= ASSETS_URL ?>/img/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer" />


</head>
<style>
  /* Add this to your main CSS file */
:root {
  --primary-color: #6E3B16; /* Dark brown accent color */
  --secondary-color: #ffffff; /* White for text contrast */
  --bg-light: #f8f9fa; /* Light background color */
  --bg-dark: #212529; /* Dark background color */
}
  .table-hover tbody tr:hover {
  background-color: #f8f9fa;
}

.card {
  border-radius: 12px;
  overflow: hidden;
}

.badge {
  font-weight: 600;
  font-size: 0.85em;
}
.form-control:focus {
    color: var(--bs-body-color);
    background-color: var(--bs-body-bg);
    border-color: #6E3B16;
    outline: 0;
    -webkit-box-shadow: 0 0 0 0.25rem rgba(51, 179, 90, 0.25);
    box-shadow: 0 0 0 0.25rem rgb(159 112 22 / 36%);
}
</style>