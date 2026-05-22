<?php
/**
 * Warehouse Management System - Database Setup Script
 * Version: 2.0
 * Author: Abdullah Faiz
 * Description: Complete database installation with safety checks
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

class DatabaseSetup {
    private $pdo;
    private $errors = [];
    private $success = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function run() {
        $this->log("Starting database setup...", "info");
        
        $this->createLocationsTable();
        $this->createSuppliersTable();
        $this->createProductsTable();
        $this->createStockTable();
        $this->createMovementsTable();
        $this->createSalesTables();
        $this->createUsersTable();
        $this->insertDefaultData();
        $this->updateProductSchema();
        
        $this->displayResults();
        return empty($this->errors);
    }
    
    private function createLocationsTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) UNIQUE,
                name VARCHAR(100) NOT NULL UNIQUE,
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Locations table created";
            
            // Insert default location
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM locations");
            if ($stmt->fetchColumn() == 0) {
                $this->pdo->exec("INSERT INTO locations (code, name) VALUES 
                    ('WH-001', 'Main Warehouse'),
                    ('STORE-001', 'Retail Store - City Center')");
                $this->success[] = "  → Added default locations";
            }
        } catch (PDOException $e) {
            $this->errors[] = "✗ Locations table: " . $e->getMessage();
        }
    }
    
    private function createSuppliersTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                contact_person VARCHAR(100),
                phone VARCHAR(20),
                email VARCHAR(100),
                address TEXT,
                tax_number VARCHAR(50),
                payment_terms VARCHAR(100),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Suppliers table created";
            
            // Add sample supplier
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM suppliers");
            if ($stmt->fetchColumn() == 0) {
                $this->pdo->exec("INSERT INTO suppliers (name, contact_person, phone, email) VALUES 
                    ('Tech Distributors Inc.', 'John Smith', '+94 77 123 4567', 'john@techdist.com'),
                    ('Global Electronics Ltd.', 'Sarah Johnson', '+94 77 234 5678', 'sarah@globalelec.com')");
                $this->success[] = "  → Added sample suppliers";
            }
        } catch (PDOException $e) {
            $this->errors[] = "✗ Suppliers table: " . $e->getMessage();
        }
    }
    
    private function createProductsTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sku VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                brand VARCHAR(100),
                category VARCHAR(100),
                description TEXT,
                purchase_price DECIMAL(15,2) DEFAULT 0.00,
                selling_price DECIMAL(15,2) DEFAULT 0.00,
                unit_price DECIMAL(15,2) DEFAULT 0.00,
                min_stock_level INT DEFAULT 5,
                max_stock_level INT DEFAULT 100,
                supplier_id INT NULL,
                supplier_name VARCHAR(255),
                status ENUM('Active', 'Inactive', 'Discontinued') DEFAULT 'Active',
                expiry_date DATE NULL,
                image_path VARCHAR(255) NULL,
                weight DECIMAL(10,2) NULL,
                unit VARCHAR(20) DEFAULT 'pcs',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_sku (sku),
                INDEX idx_name (name),
                INDEX idx_category (category),
                INDEX idx_status (status),
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Products table created";
        } catch (PDOException $e) {
            $this->errors[] = "✗ Products table: " . $e->getMessage();
        }
    }
    
    private function createStockTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS stock (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                location_id INT NOT NULL,
                quantity INT UNSIGNED DEFAULT 0,
                reserved_quantity INT UNSIGNED DEFAULT 0,
                reorder_point INT DEFAULT 10,
                last_counted DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_stock (product_id, location_id),
                INDEX idx_quantity (quantity),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Stock table created";
        } catch (PDOException $e) {
            $this->errors[] = "✗ Stock table: " . $e->getMessage();
        }
    }
    
    private function createMovementsTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS movements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                location_id INT NOT NULL,
                movement_type ENUM('IN', 'OUT', 'TRANSFER', 'ADJUST', 'RETURN') NOT NULL,
                qty INT NOT NULL,
                reference_no VARCHAR(100),
                note TEXT,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product (product_id),
                INDEX idx_location (location_id),
                INDEX idx_type (movement_type),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Movements table created";
        } catch (PDOException $e) {
            $this->errors[] = "✗ Movements table: " . $e->getMessage();
        }
    }
    
    private function createSalesTables() {
        try {
            // Sales header
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS sales (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_no VARCHAR(50) UNIQUE NOT NULL,
                location_id INT NOT NULL,
                customer_name VARCHAR(100),
                customer_phone VARCHAR(20),
                payment_method ENUM('Cash', 'Card', 'Transfer', 'Mobile Money') NOT NULL,
                payment_status ENUM('Paid', 'Pending', 'Partial') DEFAULT 'Paid',
                total_amount DECIMAL(15,2) DEFAULT 0.00,
                discount_amount DECIMAL(15,2) DEFAULT 0.00,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                tax_amount DECIMAL(15,2) DEFAULT 0.00,
                tax_percent DECIMAL(5,2) DEFAULT 0,
                net_amount DECIMAL(15,2) DEFAULT 0.00,
                created_by INT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_invoice (invoice_no),
                INDEX idx_created_at (created_at),
                INDEX idx_payment_method (payment_method),
                FOREIGN KEY (location_id) REFERENCES locations(id),
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            // Sales items
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS sales_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sale_id INT NOT NULL,
                product_id INT NOT NULL,
                qty INT NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                discount_percent DECIMAL(5,2) DEFAULT 0,
                discount_amount DECIMAL(15,2) DEFAULT 0,
                subtotal DECIMAL(15,2) NOT NULL,
                INDEX idx_sale (sale_id),
                INDEX idx_product (product_id),
                FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Sales tables created";
        } catch (PDOException $e) {
            $this->errors[] = "✗ Sales tables: " . $e->getMessage();
        }
    }
    
    private function createUsersTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                role ENUM('Admin', 'Warehouse Staff', 'Cashier', 'Manager') NOT NULL DEFAULT 'Warehouse Staff',
                is_active BOOLEAN DEFAULT TRUE,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_role (role),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            
            $this->success[] = "✓ Users table created";
        } catch (PDOException $e) {
            $this->errors[] = "✗ Users table: " . $e->getMessage();
        }
    }
    
    private function insertDefaultData() {
        try {
            // Insert default admin
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
            if ($stmt->fetchColumn() == 0) {
                $hash = password_hash('admin123', PASSWORD_DEFAULT);
                $this->pdo->exec("INSERT INTO users (username, password, role, full_name, is_active) VALUES 
                    ('admin', '$hash', 'Admin', 'System Administrator', 1),
                    ('warehouse', '" . password_hash('warehouse123', PASSWORD_DEFAULT) . "', 'Warehouse Staff', 'Warehouse Manager', 1),
                    ('cashier', '" . password_hash('cashier123', PASSWORD_DEFAULT) . "', 'Cashier', 'Sales Associate', 1)");
                $this->success[] = "  → Created default users (admin/admin123, warehouse/warehouse123, cashier/cashier123)";
            }
            
            // Insert sample product categories
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM products");
            if ($stmt->fetchColumn() == 0) {
                $this->pdo->exec("INSERT INTO products (sku, name, brand, category, purchase_price, selling_price, min_stock_level, status) VALUES 
                    ('SKU-001', 'Wireless Optical Mouse', 'Logitech', 'Electronics', 15.00, 25.00, 10, 'Active'),
                    ('SKU-002', 'Mechanical Gaming Keyboard', 'Corsair', 'Electronics', 45.00, 75.00, 5, 'Active'),
                    ('SKU-003', 'USB-C Fast Charging Cable', 'Anker', 'Accessories', 3.00, 8.00, 20, 'Active'),
                    ('SKU-004', '27\" 4K Monitor', 'Dell', 'Electronics', 250.00, 350.00, 3, 'Active'),
                    ('SKU-005', 'Wireless Headphones', 'Sony', 'Audio', 60.00, 99.00, 8, 'Active')");
                $this->success[] = "  → Added sample products";
            }
            
        } catch (PDOException $e) {
            $this->errors[] = "✗ Default data: " . $e->getMessage();
        }
    }
    
    private function updateProductSchema() {
        try {
            $cols = $this->pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
            
            $missingCols = array();
            if (!in_array('max_stock_level', $cols)) {
                $this->pdo->exec("ALTER TABLE products ADD COLUMN max_stock_level INT DEFAULT 100");
                $missingCols[] = "max_stock_level";
            }
            if (!in_array('weight', $cols)) {
                $this->pdo->exec("ALTER TABLE products ADD COLUMN weight DECIMAL(10,2) NULL");
                $missingCols[] = "weight";
            }
            if (!in_array('unit', $cols)) {
                $this->pdo->exec("ALTER TABLE products ADD COLUMN unit VARCHAR(20) DEFAULT 'pcs'");
                $missingCols[] = "unit";
            }
            
            if (!empty($missingCols)) {
                $this->success[] = "  → Added missing columns: " . implode(', ', $missingCols);
            }
        } catch (PDOException $e) {
            // Non-critical, don't add to errors
        }
    }
    
    private function log($message, $type = "info") {
        // Could implement logging to file
    }
    
    private function displayResults() {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Database Setup - Warehouse System</title>
            <style>
                body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; }
                .container { max-width: 800px; margin: 50px auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
                h1 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
                .success { color: #28a745; background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #28a745; }
                .error { color: #dc3545; background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
                .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3; }
                .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                .btn:hover { background: #5a67d8; }
                .stats { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .stats h3 { margin-top: 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🏗️ Warehouse System Database Setup</h1>';
                
        if (!empty($this->success)) {
            echo '<h3>✅ Setup Completed Successfully</h3>';
            foreach ($this->success as $msg) {
                echo '<div class="success">' . htmlspecialchars($msg) . '</div>';
            }
        }
        
        if (!empty($this->errors)) {
            echo '<h3>⚠️ Issues Detected</h3>';
            foreach ($this->errors as $msg) {
                echo '<div class="error">' . htmlspecialchars($msg) . '</div>';
            }
        }
        
        $dbVersion = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        
        echo '<div class="stats">
                <h3>📊 System Information</h3>
                <ul>
                    <li>PHP Version: ' . phpversion() . '</li>
                    <li>MySQL Version: ' . $dbVersion . '</li>
                    <li>Database: ' . DB_NAME . '</li>
                    <li>Setup Time: ' . date('Y-m-d H:i:s') . '</li>
                </ul>
              </div>
              
              <div class="info">
                <strong>🔐 Default Login Credentials:</strong><br>
                • Admin: admin / admin123<br>
                • Warehouse Staff: warehouse / warehouse123<br>
                • Cashier: cashier / cashier123<br><br>
                <strong>⚠️ Important:</strong> Please change these passwords after first login!
              </div>
              
              <a href="login.php" class="btn">🔑 Go to Login Page</a>
              <a href="index.php" class="btn" style="background:#6c757d; margin-left:10px;">📊 Go to Dashboard</a>
            </div>
        </body>
        </html>';
    }
}

// Run the setup
$setup = new DatabaseSetup(db());
$setup->run();
?>