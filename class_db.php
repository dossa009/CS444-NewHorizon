<?php
// Database connection for class MySQL server

// Use the same info you use in the mysql CLI:
//   mysql -u group8 -p
$DB_HOST = 'localhost';
$DB_USER = 'group8';
$DB_PASS = '5godqlhp';   
$DB_NAME = 'group8';
$DB_PORT = 3306;

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    // For debugging
    die("Database connection failed: " . htmlspecialchars($mysqli->connect_error));
}

$mysqli->set_charset('utf8mb4');