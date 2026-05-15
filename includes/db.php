<?php
// includes/db.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host     = 'localhost';
$user     = 'root';
$password = '';
$dbname   = 'food_web';

try {
    $conn = mysqli_connect($host, $user, $password, $dbname);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database error.');
}
