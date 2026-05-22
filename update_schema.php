<?php
require_once 'db.php';
$pdo = db();

echo "<h2>Updating Database Schema...</h2>";

$columnsToAdd = [
    "ADD COLUMN purchase_price DECIMAL(15,2) DEFAULT 0",
    "ADD COLUMN selling_price DECIMAL(15,2) DEFAULT 0",
    "ADD COLUMN unit_price DECIMAL(15,2) DEFAULT 0",
    "ADD COLUMN min_stock_level INT DEFAULT 5",
    "ADD COLUMN supplier_name VARCHAR(255)",
    "ADD COLUMN expiry_date DATE NULL",
    "ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'",
    "ADD COLUMN brand VARCHAR(100)",
    "ADD COLUMN category VARCHAR(100)",
    "ADD COLUMN description TEXT",
    "ALTER TABLE sales_items ADD COLUMN item_discount DECIMAL(15,2) DEFAULT 0"
];

foreach ($columnsToAdd as $sql) {
    try {
        // Try adding the column. If it exists, it might throw an error (depending on MySQL version support for IF NOT EXISTS in ALTER)
        // We wrap in try-catch to safely ignore "Duplicate column name" errors.
        $pdo->exec("ALTER TABLE products $sql");
        echo "<p style='color: green'>Executed: $sql</p>";
    } catch (PDOException $e) {
        // Check for "Duplicate column name" error code (1060)
        if (strpos($e->getMessage(), "Duplicate column") !== false || $e->getCode() == '42S21') {
             echo "<p style='color: gray'>Skipped (Column exists): $sql</p>";
        } else {
             echo "<p style='color: red'>Error: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h3>Update Complete. Please refresh products.php</h3>";
