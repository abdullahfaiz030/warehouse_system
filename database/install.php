<?php
// database/install.php - Complete setup script
require_once '../config.php';
require_once '../db.php';

$pdo = db();

// Run setup_database.php
include '../setup_database.php';

// Run updates in order
include '../update_schema.php';
include '../update_schema_v2.php';
include '../fix_stock_schema.php';
include '../migrate_sales.php';

echo "<h3>✅ Database fully installed!</h3>";
echo "<p>Default login: admin / admin123</p>";