<?php
require 'class_db.php';

// Check Exercises table
$result = $mysqli->query("SELECT COUNT(*) AS cnt FROM Exercises");
if (!$result) {
    die("Query failed: " . htmlspecialchars($mysqli->error));
}
$row = $result->fetch_assoc();

echo "<h1>DB Test</h1>";
echo "<p>Exercises rows in group8: " . (int)$row['cnt'] . "</p>";

$mysqli->close();