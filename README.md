# 🏢 SmartStock Warehouse Management System

**Version:** 2.0.0 | **Release Date:** May 22, 2026

[![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🌐 Live Demo

**Try the live system here:** 🔗 [https://warehousesystem.infinityfreeapp.com](https://warehousesystem.infinityfreeapp.com)

### Test Credentials

| Role | Username | Password |
|------|----------|----------|
| 👑 **Admin** | `admin` | `admin123` |
| 📦 **Warehouse Staff** | `warehouse` | `warehouse123` |
| 💰 **Cashier** | `cashier` | `cashier123` |

> ⚠️ **Note:** This is a demo system. Please be respectful when testing.

---

## 📋 Overview

A comprehensive, production-ready warehouse management system built with PHP, MySQL, and Bootstrap. Perfect for small to medium businesses managing inventory across multiple locations.

## ✨ Features

### Core Features
- ✅ **Multi-role Authentication** (Admin, Warehouse Staff, Cashier)
- ✅ **Product Management** with image upload
- ✅ **Multi-location Stock Tracking**
- ✅ **Sales & Billing System** with receipt printing
- ✅ **Stock Movement History** (IN/OUT/TRANSFER)
- ✅ **Low Stock Alerts** on dashboard
- ✅ **Sales Reports & Analytics** with Chart.js
- ✅ **Supplier Management**
- ✅ **User Management** (Admin only)

### Technical Features
- 🔒 **PDO Database Layer** with prepared statements (SQL injection protected)
- 🔐 **Session-based Authentication** with role-based access control
- 📱 **Responsive Design** with Bootstrap 5
- 🖨️ **Print-friendly Receipts**
- 📊 **Real-time Stock Validation**

## 🚀 Quick Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP/WAMP/MAMP

### Steps

1. **Clone the repository**
```bash
git clone https://github.com/abdullahfaiz030/warehouse_system.git
cd warehouse_system
```

2. **Move the project into your local web root** if needed:
- For XAMPP: place the folder at `C:\xampp\htdocs\warehouse_system`
- For WAMP: place the folder at `C:\wamp64\www\warehouse_system`

3. **Start Apache and MySQL** from your local server control panel.

4. **Verify local database settings** in `config.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'warehouse_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

5. **Create the database tables**:
- Open your browser at `http://localhost/warehouse_system/setup_database.php`
- Or use phpMyAdmin to import `warehouse_db.sql`

6. **Ensure the upload folder exists**:
- `uploads/products/`
- Make sure it is writable so product images can be saved

7. **Open the app in your browser**:
- `http://localhost/warehouse_system/login.php`

### Default local login
- Username: `admin`
- Password: `admin123`

> If your `config.php` is missing, copy `config.example.php` to `config.php` and update the values above.