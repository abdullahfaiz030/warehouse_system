<?php
require_once 'db.php';
$pdo = db();

try {
    // 1. Locations
    echo "Creating locations table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB;");

    // Insert default location if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM locations");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO locations (name) VALUES ('Main Warehouse')");
    }

    // 2. Products
    echo "Creating products table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        brand VARCHAR(100),
        category VARCHAR(100),
        description TEXT,
        purchase_price DECIMAL(15,2) DEFAULT 0,
        selling_price DECIMAL(15,2) DEFAULT 0,
        unit_price DECIMAL(15,2) DEFAULT 0,
        min_stock_level INT DEFAULT 0,
        supplier_name VARCHAR(255),
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        expiry_date DATE NULL
    ) ENGINE=InnoDB;");

    // 3. Stock
    echo "Creating stock table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        location_id INT NOT NULL,
        quantity INT DEFAULT 0,
        UNIQUE KEY unique_stock (product_id, location_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 4. Movements (History)
    echo "Creating movements table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        location_id INT NOT NULL,
        movement_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUST') NOT NULL,
        qty INT NOT NULL,
        note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 5. Sales Header
    echo "Creating sales table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_no VARCHAR(50) UNIQUE NOT NULL,
        location_id INT NOT NULL,
        payment_method ENUM('Cash', 'Card', 'Transfer') NOT NULL,
        total_amount DECIMAL(15,2) DEFAULT 0,
        discount_amount DECIMAL(15,2) DEFAULT 0,
        tax_amount DECIMAL(15,2) DEFAULT 0,
        net_amount DECIMAL(15,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES locations(id)
    ) ENGINE=InnoDB;");

    // 6. Sales Items
    echo "Creating sales_items table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        qty INT NOT NULL,
        unit_price DECIMAL(15,2) NOT NULL,
        subtotal DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB;");

    // 7. Users
    echo "Creating users table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('Admin', 'Warehouse Staff', 'Cashier') NOT NULL DEFAULT 'Warehouse Staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Default Admin
    echo "Inserting default admin...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$hash', 'Admin')");
    }

    // 8. Suppliers
    echo "Creating suppliers table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Add columns to Products if missing
    echo "Checking products columns...\n";
    $cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('supplier_id', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN supplier_id INT NULL");
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT fk_product_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL");
    }
    if (!in_array('status', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'");
    }
    if (!in_array('image_path', $cols)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL");
    }

    echo "Success!\n";

} catch (PDOException $e) {
    echo "Error caught: " . $e->getMessage() . "\n";
    die("Setup Error: " . $e->getMessage());
}
?>