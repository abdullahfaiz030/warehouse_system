<?php
require_once 'auth_check.php';
requireLogin();
require_once 'db.php';
$pdo = db();

// Fetch summary stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_stock = $pdo->query("SELECT SUM(quantity) FROM stock")->fetchColumn();
// Fetch Low Stock Items (Total Stock <= Min Level)
// Fetch Low Stock Items (Total Stock <= Min Level)
$lowStockQuery = "
    SELECT p.name, p.min_stock_level, COALESCE(SUM(s.quantity), 0) as current_qty
    FROM products p
    LEFT JOIN stock s ON p.id = s.product_id
    GROUP BY p.id, p.name, p.min_stock_level
    HAVING current_qty <= p.min_stock_level
    LIMIT 5
";
$lowStockItems = $pdo->query($lowStockQuery)->fetchAll();
$low_stock = count($lowStockItems); // This is just top 5, but for count we should use a separate query or just rely on this for "alerts". 
// Actually let's get the real full count for the card
$countLowQuery = "
    SELECT COUNT(*) FROM (
        SELECT p.id
        FROM products p
        LEFT JOIN stock s ON p.id = s.product_id
        GROUP BY p.id, p.min_stock_level
        HAVING COALESCE(SUM(s.quantity), 0) <= p.min_stock_level
    ) as t
";
$low_stock_count = $pdo->query($countLowQuery)->fetchColumn();
$total_locations = $pdo->query("SELECT COUNT(*) FROM locations")->fetchColumn();

require_once 'header.php';
?>

<div class="container mt-5 animate-fade-in">
    <div class="row align-items-center mb-5">
        <div class="col-md-8">
            <h1 class="display-4 fw-bold text-gradient">Overview</h1>
            <p class="text-muted lead">Smart Warehouse Management Dashboard</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="sales.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-lg fw-bold">
                <i class="fas fa-plus me-2"></i> New Sale
            </a>
        </div>
    </div>

    <!-- Low Stock Alert Banner -->
    <?php if (!empty($lowStockItems)): ?>
        <div
            class="alert alert-danger border-0 shadow-sm rounded-4 mb-5 p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center mb-2">
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                        style="width: 48px; height: 48px;">
                        <i class="fas fa-exclamation-triangle fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-danger mb-0">Critical Stock Alert</h4>
                        <p class="mb-0 text-danger-emphasis">The following items are running low:</p>
                    </div>
                </div>
                <div class="mt-3 ps-5">
                    <ul class="mb-0 list-unstyled">
                        <?php foreach ($lowStockItems as $item): ?>
                            <li class="mb-1">
                                <i class="fas fa-angle-right me-2 opacity-50"></i>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <span class="badge bg-white text-danger border ms-2"><?= (int) $item['current_qty'] ?>
                                    Left</span>
                                <span class="small text-muted ms-1">(Min: <?= (int) $item['min_stock_level'] ?>)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <a href="stock.php" class="btn btn-light text-danger fw-bold rounded-pill px-4 shadow-sm">Restock Now</a>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <!-- Products Card -->
        <div class="col-md-3">
            <div class="glass-card p-4 text-center h-100 stat-card-vibrant stat-card-blue">
                <div class="position-relative z-1">
                    <i class="fas fa-box fa-3x mb-3 opacity-80"></i>
                    <h2 class="fw-bold mb-0"><?= number_format($total_products) ?></h2>
                    <p class="small text-uppercase fw-bold mb-0 opacity-80">Total Products</p>
                    <a href="products.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Stock Card -->
        <div class="col-md-3">
            <div class="glass-card p-4 text-center h-100 stat-card-vibrant stat-card-emerald">
                <div class="position-relative z-1">
                    <i class="fas fa-cubes fa-3x mb-3 opacity-80"></i>
                    <h2 class="fw-bold mb-0"><?= number_format((int) $total_stock) ?></h2>
                    <p class="small text-uppercase fw-bold mb-0 opacity-80">Total Items in Stock</p>
                    <a href="stock.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Low Stock Card -->
        <div class="col-md-3">
            <div class="glass-card p-4 text-center h-100 stat-card-vibrant stat-card-rose">
                <div class="position-relative z-1">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 opacity-80"></i>
                    <h2 class="fw-bold mb-0"><?= number_format((int) $low_stock_count) ?></h2>
                    <p class="small text-uppercase fw-bold mb-0 opacity-80">Low Stock Alerts</p>
                    <a href="products.php?s=low" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Locations Card -->
        <div class="col-md-3">
            <div class="glass-card p-4 text-center h-100 stat-card-vibrant stat-card-violet">
                <div class="position-relative z-1">
                    <i class="fas fa-map-marker-alt fa-3x mb-3 opacity-80"></i>
                    <h2 class="fw-bold mb-0"><?= number_format($total_locations) ?></h2>
                    <p class="small text-uppercase fw-bold mb-0 opacity-80">Warehouses</p>
                    <a href="locations.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="glass-card p-4 h-100">
                <h5 class="fw-bold mb-4"><i class="fas fa-bolt me-2 text-warning"></i> Quick Actions</h5>
                <div class="d-grid gap-3">
                    <a href="products.php" class="btn btn-light text-start py-3 border shadow-sm">
                        <i class="fas fa-plus-circle me-3 text-success"></i> Add New Product
                    </a>
                    <a href="stock.php" class="btn btn-light text-start py-3 border shadow-sm">
                        <i class="fas fa-sync me-3 text-primary"></i> Update Stock Levels
                    </a>
                    <a href="report.php" class="btn btn-light text-start py-3 border shadow-sm">
                        <i class="fas fa-file-alt me-3 text-secondary"></i> View Sales Reports
                    </a>
                    <?php if (currentRole() === 'Admin'): ?>
                        <a href="users.php" class="btn btn-light text-start py-3 border shadow-sm">
                            <i class="fas fa-users-cog me-3 text-dark"></i> Manage Users
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="glass-card p-4 h-100 d-flex flex-column justify-content-center text-center">
                <img src="https://cdni.iconscout.com/illustration/premium/thumb/warehouse-management-5473796-4554366.png"
                    class="img-fluid mx-auto mb-3" style="max-height: 200px; opacity: 0.8;" alt="Warehouse">
                <h4 class="fw-bold">Efficient Management</h4>
                <p class="text-muted">Manage your inventory, track sales, and monitor stock levels across multiple
                    locations with ease.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>