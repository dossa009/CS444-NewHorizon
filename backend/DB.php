<?php
#db configs
const DB_HOST     = 'localhost';
const DB_USER     = 'root';
const DB_PASSWORD = '';              
const DB_NAME     = 'new_horizon_db';
const DB_PORT     = 3306;

#create connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $mysqli->connect_error,
    ]);
    exit;
}

$mysqli->set_charset('utf8mb4');
