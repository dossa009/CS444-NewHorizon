<?php
require __DIR__ . '/DB.php';

echo json_encode([
    'success' => true,
    'message' => 'DB connection OK',
]);
