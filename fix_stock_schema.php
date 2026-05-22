<?php
require_once 'db.php';
$pdo = db();

try {
    echo "Applying UNSIGNED constraint to stock table...\n";
    
    // First, ensure no negative values exist basically (reset to 0 if any)
    $pdo->exec("UPDATE stock SET quantity = 0 WHERE quantity < 0");
    
    // Alter table to UNSIGNED
    $pdo->exec("ALTER TABLE stock MODIFY quantity INT UNSIGNED DEFAULT 0");
    
    echo "SUCCESS: Stock table quantity is now UNSIGNED (cannot be negative).\n";
    
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
