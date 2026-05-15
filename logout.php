<?php
// logout.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// wipe session
$_SESSION = [];

// delete the session cookie using same params
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $p['path'] ?: '/',
    $p['domain'] ?? '',
    $p['secure'] ?? false,
    $p['httponly'] ?? true
  );
}

// destroy server-side session
session_destroy();

// redirect wherever you want post-logout
header('Location: index.php');
exit;
