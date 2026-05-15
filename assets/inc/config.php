<?php
// config.php

// Build URLs from the actual document root so the app works from either
// a domain root or an XAMPP subfolder such as /Food App.
if (!defined('APP_BASE_URL')) {
  $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
  $appRoot = realpath(dirname(__DIR__, 2));

  $documentRoot = $documentRoot ? rtrim(str_replace('\\', '/', $documentRoot), '/') : '';
  $appRoot = $appRoot ? rtrim(str_replace('\\', '/', $appRoot), '/') : '';
  $basePath = '';

  if ($documentRoot !== '' && $appRoot !== '') {
    if ($appRoot === $documentRoot) {
      $basePath = '';
    } elseif (strpos($appRoot, $documentRoot . '/') === 0) {
      $relativePath = trim(substr($appRoot, strlen($documentRoot)), '/');
      $segments = array_map('rawurlencode', explode('/', $relativePath));
      $basePath = '/' . implode('/', $segments);
    }
  }

  define('APP_BASE_URL', $basePath);
}

if (!defined('ASSETS_URL')) {
  define('ASSETS_URL', APP_BASE_URL . '/assets');
}

// Database (Hostinger)
define('SERVER',   'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DATABASE', 'food_web');

// Connect
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect(SERVER, USERNAME, PASSWORD, DATABASE);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection error.');
}
