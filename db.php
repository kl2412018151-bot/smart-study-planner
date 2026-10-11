<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'sql206.infinityfree.com';
$user = 'if0_43062488';
$pass = 'zvahZ2CID5';
$dbname = 'if0_43062488_smart_planner';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to connect to the database. Please try again later.');
}