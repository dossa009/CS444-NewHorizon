<?php
/**
 * Database Setup Script
 * Run this file once to create the database tables
 */

require_once __DIR__ . '/DataBase.php';

echo "Setting up New Horizon Database...\n\n";

// Read SQL schema file
$sqlFile = __DIR__ . '/schema.sql';
if (!file_exists($sqlFile)) {
    die("Error: schema.sql file not found!\n");
}

$sql = file_get_contents($sqlFile);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && substr($stmt, 0, 2) !== '--';
    }
);

// Execute each statement
$success = 0;
$errors = 0;

foreach ($statements as $statement) {
    if (empty(trim($statement))) continue;

    try {
        if ($mysqli->query($statement)) {
            $success++;
            // Extract table name or action from statement
            if (preg_match('/(?:CREATE TABLE|DROP TABLE IF EXISTS)\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Processed: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Inserted data into: {$matches[1]}\n";
            } else {
                echo "✓ Statement executed\n";
            }
        } else {
            throw new Exception($mysqli->error);
        }
    } catch (Exception $e) {
        $errors++;
        echo "✗ Error: " . $e->getMessage() . "\n";
        // Show first 100 chars of failed statement
        echo "  Statement: " . substr($statement, 0, 100) . "...\n\n";
    }
}

echo "\n===========================================\n";
echo "Database setup complete!\n";
echo "Success: $success | Errors: $errors\n";
echo "===========================================\n\n";

if ($errors === 0) {
    echo "You can now use the following test accounts:\n\n";
    echo "ADMIN ACCOUNT:\n";
    echo "  Email: admin@newhorizon.com\n";
    echo "  Password: Admin123!\n\n";
    echo "TEST USER ACCOUNTS (all have password: Admin123!):\n";
    echo "  - john@example.com\n";
    echo "  - jane@example.com\n";
    echo "  - mike@example.com\n\n";
} else {
    echo "⚠ Some errors occurred. Please check the output above.\n";
}

$mysqli->close();
