<?php
require_once 'db.php';
$pdo = db();

try {
    echo "Starting Schema Update V2...\n";
    
    // 1. Create Users Table
    echo "Creating 'users' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('Admin', 'Warehouse Staff', 'Cashier') NOT NULL DEFAULT 'Warehouse Staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Done.\n";

    // 2. Insert Default Admin (if not exists)
    echo "Checking for default admin... ";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        // Default password: admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmtInsert->execute(['admin', $hash, 'Admin']);
        echo "Inserted default admin (admin/admin123).\n";
    } else {
        echo "Admin exists. Skipping.\n";
    }

    // 3. Create Suppliers Table
    echo "Creating 'suppliers' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Done.\n";

    // 4. Update Products Table
    echo "Updating 'products' table schema... \n";

    // Check columns and add if missing
    $cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
    
    // Add supplier_id
    if (!in_array('supplier_id', $cols)) {
        echo " - Adding 'supplier_id' column... ";
        $pdo->exec("ALTER TABLE products ADD COLUMN supplier_id INT NULL");
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT fk_product_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL");
        echo "Done.\n";
    }

    // Ensure status column exists (already in setup, but checking just in case)
    if (!in_array('status', $cols)) {
        echo " - Adding 'status' column... ";
        $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'");
        echo "Done.\n";
    } else {
        echo " - 'status' column already exists.\n";
    }

    // Add image column (nice to have for future)
    if (!in_array('image_path', $cols)) {
        echo " - Adding 'image_path' column... ";
        $pdo->exec("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL");
        echo "Done.\n";
    }

    echo "\nSchema Update V2 Completed Successfully!\n";

} catch (PDOException $e) {
    die("Error updating schema: " . $e->getMessage());
}
?>
