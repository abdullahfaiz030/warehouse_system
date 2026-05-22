<?php
require_once 'auth_check.php';

// If not logged in, we might want to redirect, OR just show limited header. 
// For this system, let's allow public index or just redirect?
// Plan says "Login / logout", implying protected system.
// Let's assume pages that include header are protected.
if (!isLoggedIn()) {
    // Do nothing here, allow individual pages to redirect if needed, 
    // OR just show a "Login" button if on a public page.
    // For now, let's assume we are logged in if we see the nav, 
    // typically header is used after auth check.
}

$role = currentRole();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Warehouse System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php if (isLoggedIn()): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm backdrop-blur-md"
            style="background: rgba(15, 23, 42, 0.95) !important;">
            <div class="container-fluid px-4">
                <a class="navbar-brand fw-bold text-uppercase tracking-wider" href="index.php">
                    <i class="fas fa-warehouse me-2 text-primary"></i>SmartStock <span class="text-primary">System</span>
                </a>

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navContent">
                    <div class="navbar-nav mx-auto">
                        <?php if ($role === 'Admin' || $role === 'Warehouse Staff'): ?>
                            <a class="nav-link fw-medium" href="index.php"><i class="fas fa-home me-1 opacity-50"></i>
                                Dashboard</a>
                            <a class="nav-link fw-medium" href="products.php"><i class="fas fa-box me-1 opacity-50"></i>
                                Products</a>
                            <a class="nav-link fw-medium" href="stock.php"><i
                                    class="fas fa-arrow-right-arrow-left me-1 opacity-50"></i> Stock In/Out</a>
                            <a class="nav-link fw-medium" href="locations.php"><i
                                    class="fas fa-map-marker-alt me-1 opacity-50"></i> Locations</a>
                            <!-- Future: Suppliers -->
                        <?php endif; ?>

                        <?php if ($role === 'Admin' || $role === 'Cashier'): ?>
                            <a class="nav-link fw-medium" href="sales.php"><i class="fas fa-cash-register me-1 opacity-50"></i>
                                Sales</a>
                        <?php endif; ?>

                        <?php if ($role === 'Admin' || $role === 'Warehouse Staff'): ?>
                            <a class="nav-link fw-medium" href="report.php"><i class="fas fa-chart-line me-1 opacity-50"></i>
                                Report</a>
                        <?php endif; ?>
                    </div>

                    <div class="navbar-nav ms-auto align-items-center">
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown">
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-2"
                                    style="width: 32px; height: 32px; font-weight: bold;">
                                    <?= strtoupper(substr(currentUser(), 0, 1)) ?>
                                </div>
                                <div class="d-none d-lg-block text-start">
                                    <div class="small fw-bold text-white"><?= htmlspecialchars(currentUser()) ?></div>
                                    <div class="very-small text-muted text-uppercase"
                                        style="font-size: 0.7rem; line-height: 1;"><?= htmlspecialchars($role) ?></div>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 my-2">
                                <?php if ($role === 'Admin'): ?>
                                    <li><a class="dropdown-item" href="users.php"><i
                                                class="fas fa-users-cog me-2 text-muted"></i> User Management</a></li>
                                    <li><a class="dropdown-item" href="suppliers.php"><i
                                                class="fas fa-truck me-2 text-muted"></i> Suppliers</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="logout.php"
                                        onclick="window.location.href='logout.php';"><i
                                            class="fas fa-right-from-bracket me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    <div class="main-content">