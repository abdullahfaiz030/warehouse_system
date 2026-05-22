<?php
require_once 'auth_check.php';
requireRole(['Admin', 'Warehouse Staff']);
require_once 'db.php';
$pdo = db();

$msg = "";

// Helper to get semantic icons based on category keywords
function getCategoryIcon($category)
{
  $cat = strtolower($category);
  // 1. Precise Phone Mapping
  if (strpos($cat, 'phone') !== false || strpos($cat, 'mobile') !== false)
    return 'fa-mobile-screen-button';

  // 2. Precise TV Mapping
  if (strpos($cat, 'tv') !== false || strpos($cat, 'screen') !== false || strpos($cat, 'display') !== false)
    return 'fa-tv';

  // 3. Precise Electronics Mapping
  if (strpos($cat, 'electronic') !== false || strpos($cat, 'electric') !== false || strpos($cat, 'hardware') !== false)
    return 'fa-plug-circle-bolt';

  if (strpos($cat, 'laptop') !== false || strpos($cat, 'computer') !== false)
    return 'fa-laptop';
  if (strpos($cat, 'food') !== false || strpos($cat, 'drink') !== false)
    return 'fa-utensils';
  if (strpos($cat, 'furniture') !== false)
    return 'fa-couch';
  if (strpos($cat, 'cloth') !== false || strpos($cat, 'wear') !== false)
    return 'fa-shirt';

  return 'fa-box'; // Default fallback
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  $sku = trim($_POST['sku'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $brand = trim($_POST['brand'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
  $selling_price = (float) ($_POST['price'] ?? 0);
  $min_stock = (int) ($_POST['min_stock_level'] ?? 5);
  $supplier_input = trim($_POST['supplier_name'] ?? '');
  $status = $_POST['status'] ?? 'Active';
  $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
  $initial_qty = (int) ($_POST['initial_qty'] ?? 0);
  $location_id = (int) ($_POST['location_id'] ?? 0);

  // File Upload Logic
  $image_path = null;
  if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/products/";
    if (!is_dir($target_dir))
      mkdir($target_dir, 0777, true);

    $file_ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($file_ext, $allowed_exts)) {
      $new_filename = uniqid('prod_', true) . '.' . $file_ext;
      $target_file = $target_dir . $new_filename;

      if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
        $image_path = $target_file;
      }
    }
  }

  if ($sku === '' || $name === '' || $brand === '' || $location_id <= 0) {
    $msg = "All fields marked with * are strictly required (SKU, Name, Brand, Location).";
  } elseif ($selling_price <= 0 || $purchase_price <= 0) {
    $msg = "Purchase and Selling prices must be greater than 0.";
  } else {
    try {
      $pdo->beginTransaction();

      // Handle Supplier
      $supplier_id = null;
      $supplier_name_val = $supplier_input;

      if ($supplier_input !== '') {
        // Check if it's an existing supplier name
        $s_stmt = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
        $s_stmt->execute([$supplier_input]);
        $existing_s = $s_stmt->fetch();

        if ($existing_s) {
          $supplier_id = $existing_s['id'];
        } else {
          // Create new supplier if it doesn't exist
          $ins_s = $pdo->prepare("INSERT INTO suppliers (name) VALUES (?)");
          $ins_s->execute([$supplier_input]);
          $supplier_id = $pdo->lastInsertId();
        }
      }

      if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO products (sku, name, brand, category, description, purchase_price, selling_price, unit_price, min_stock_level, supplier_id, supplier_name, status, expiry_date, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $name, $brand, $category, $description, $purchase_price, $selling_price, $selling_price, $min_stock, $supplier_id, $supplier_name_val, $status, $expiry_date, $image_path]);
        $product_id = $pdo->lastInsertId();

        if ($initial_qty > 0) {
          // Record movement
          $stmt = $pdo->prepare("INSERT INTO movements (product_id, location_id, movement_type, qty, note) VALUES (?, ?, 'IN', ?, 'Initial stock from product page')");
          $stmt->execute([$product_id, $location_id, $initial_qty]);

          // Update stock table
          $stmt = $pdo->prepare("INSERT INTO stock (product_id, location_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
          $stmt->execute([$product_id, $location_id, $initial_qty, $initial_qty]);
        }
        $msg = "Product added successfully!";
      } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];

        $sql = "UPDATE products SET sku=?, name=?, brand=?, category=?, description=?, purchase_price=?, selling_price=?, unit_price=?, min_stock_level=?, supplier_id=?, supplier_name=?, status=?, expiry_date=?";
        $params = [$sku, $name, $brand, $category, $description, $purchase_price, $selling_price, $selling_price, $min_stock, $supplier_id, $supplier_name_val, $status, $expiry_date];

        if ($image_path) {
          $sql .= ", image_path=?";
          $params[] = $image_path;
        }

        $sql .= " WHERE id=?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Stock Adjustment Logic for the SELECTED location
        $current_loc_stock_stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ? AND location_id = ?");
        $current_loc_stock_stmt->execute([$id, $location_id]);
        $current_qty = (int) ($current_loc_stock_stmt->fetch()['quantity'] ?? 0);

        $adjustment = $initial_qty - $current_qty;
        if ($adjustment != 0) {
          $type = ($adjustment > 0) ? 'IN' : 'OUT';
          $abs_adj = abs($adjustment);

          // Record movement
          $stmt = $pdo->prepare("INSERT INTO movements (product_id, location_id, movement_type, qty, note) VALUES (?, ?, ?, ?, 'Adjustment from product page')");
          $stmt->execute([$id, $location_id, $type, $abs_adj]);

          // Update stock
          if ($type === 'IN') {
            $stmt = $pdo->prepare("INSERT INTO stock (product_id, location_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            $stmt->execute([$id, $location_id, $abs_adj, $abs_adj]);
          } else {
            $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ? AND location_id = ?");
            $stmt->execute([$abs_adj, $id, $location_id]);
          }
        }
        $msg = "Product updated successfully!";
      }
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
      $msg = "Error: " . $e->getMessage();
    }
  }
}

// Delete product
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Product deleted.";
  } catch (PDOException $e) {
    $msg = "Error: Could not delete product.";
  }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
  // Search handling
  $search = trim($_GET['search'] ?? '');
  $params = [];
  $query = "
        SELECT p.*, 
               s.name as supplier_name_linked,
               COALESCE((SELECT SUM(quantity) FROM stock WHERE product_id = p.id), 0) as total_stock,
               COALESCE((SELECT SUM(qty) FROM sales_items WHERE product_id = p.id), 0) as total_sold,
               (SELECT MAX(s.created_at) FROM sales s JOIN sales_items si ON si.sale_id = s.id WHERE si.product_id = p.id) as last_sold
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
    ";

  if ($search !== '') {
    $query .= " WHERE p.name LIKE ? OR p.sku LIKE ? OR p.category LIKE ? OR p.brand LIKE ? ";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
  }

  $query .= " ORDER BY p.id DESC";
  $products_stmt = $pdo->prepare($query);
  $products_stmt->execute($params);
  $products = $products_stmt->fetchAll();

  // Fetch locations for dropdown
  $locations = $pdo->query("SELECT * FROM locations ORDER BY name")->fetchAll();
  // Fetch suppliers for dropdown
  $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
} catch (Exception $e) {
  die('<div class="container mt-5"><div class="alert alert-danger"><h1>System Error</h1><p>' . $e->getMessage() . '</p></div></div>');
}

require_once 'header.php';
?>

<div class="container animate-fade-inpt-4">
  <!-- Header & Title -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="fw-bold text-gradient mb-0">Product Management</h2>
      <p class="text-muted small">Centralized control for your warehouse inventory</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary rounded-pill shadow-sm" onclick="showTab('manage-tab')">
        <i class="fas fa-plus-circle me-2"></i> Add Product
      </button>
    </div>
  </div>

  <!-- Global Stats Summary -->
  <?php
  $totalInventory = array_sum(array_column($products, 'total_stock'));
  $totalValue = 0;
  $lowStockCount = 0;
  foreach ($products as $p) {
    $totalValue += ($p['total_stock'] * $p['selling_price']);
    if ($p['total_stock'] <= $p['min_stock_level'])
      $lowStockCount++;
  }
  $totalUnitsSold = array_sum(array_column($products, 'total_sold'));
  ?>

  <!-- Navigation Tabs -->
  <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-pill shadow-sm d-inline-flex border" id="productTabs"
    role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active rounded-pill px-4" id="inventory-tab" data-bs-toggle="tab"
        data-bs-target="#inventory-pane" type="button" role="tab">
        <i class="fas fa-table-list me-2"></i>Inventory
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4" id="manage-tab" data-bs-toggle="tab" data-bs-target="#manage-pane"
        type="button" role="tab">
        <i class="fas fa-edit me-2"></i>Manage Product
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link rounded-pill px-4" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts-pane"
        type="button" role="tab">
        <i class="fas fa-triangle-exclamation me-2"></i>Stock Alerts
        <span class="badge bg-danger ms-1"><?= $lowStockCount ?></span>
      </button>
    </li>
  </ul>

  <?php if ($msg): ?>
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 alert-dismissible fade show" role="alert">
      <i class="fas fa-circle-info me-2"></i> <?= htmlspecialchars($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="tab-content" id="productTabContent">
    <!-- Tab 1: Inventory Overview -->
    <div class="tab-pane fade show active" id="inventory-pane" role="tabpanel" tabindex="0">
      <div class="row g-4 mb-4">
        <div class="col-md-3">
          <div class="glass-card p-3 border-0 text-center" style="border-top: 5px solid var(--accent-blue) !important;">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Total Stock</div>
            <div class="h4 fw-bold mb-0 text-primary"><?= number_format($totalInventory) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="glass-card p-3 border-0 text-center"
            style="border-top: 5px solid var(--accent-emerald) !important;">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Inventory Value</div>
            <div class="h5 fw-bold mb-0 text-success">LKR <?= number_format($totalValue, 0) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="glass-card p-3 border-0 text-center"
            style="border-top: 5px solid var(--accent-amber) !important;">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Sales Volume</div>
            <div class="h4 fw-bold mb-0 text-warning"><?= number_format($totalUnitsSold) ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="glass-card p-3 border-0 text-center"
            style="border-top: 5px solid var(--accent-violet) !important;">
            <div class="small text-muted text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Unique SKUs</div>
            <div class="h4 fw-bold mb-0 text-info"><?= count($products) ?></div>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="glass-card p-3 mb-4 border-0">
        <form method="get" class="row g-2 align-items-center">
          <div class="col-md-10 position-relative">
            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            <input type="text" name="search" class="form-control rounded-pill ps-5 border-0 bg-light"
              placeholder="Filter by SKU, name, category, or brand..." value="<?= htmlspecialchars($search) ?>">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-dark w-100 rounded-pill">Filter</button>
          </div>
        </form>
      </div>

      <!-- Product Grid -->
      <div class="row g-4">
        <?php if (empty($products)): ?>
          <div class="col-12 text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-20"></i>
            <p class="text-muted">No products found in the warehouse.</p>
          </div>
        <?php endif; ?>
        <?php foreach ($products as $p):
          $stockStatus = '';
          if ($p['total_stock'] <= 0)
            $stockStatus = 'Out of Stock';
          elseif ($p['total_stock'] <= $p['min_stock_level'])
            $stockStatus = 'Low Stock';
          $isExpired = $p['expiry_date'] && strtotime($p['expiry_date']) < time();

          // Vibrant accents mapping
          $accents = ['--accent-blue', '--accent-emerald', '--accent-amber', '--accent-violet', '--accent-rose'];
          $accent = $accents[$p['id'] % count($accents)];
          ?>
          <div class="col-xl-4 col-md-6">
            <div class="glass-card h-100 p-0 position-relative overflow-hidden border-0"
              style="border-bottom: 5px solid var(<?= $accent ?>) !important;">
              <div class="position-absolute top-0 end-0 mt-3 me-3 z-1">
                <?php if ($isExpired): ?>
                  <span class="badge bg-danger rounded-pill px-3 shadow-sm">Expired</span>
                <?php elseif ($stockStatus): ?>
                  <span
                    class="badge <?= ($stockStatus == 'Low Stock') ? 'bg-warning text-dark' : 'bg-danger' ?> rounded-pill px-3 shadow-sm"><?= $stockStatus ?></span>
                <?php endif; ?>
              </div>

              <div class="p-4 pt-5">
                <div class="d-flex align-items-start mb-3">
                  <?php if (!empty($p['image_path'])): ?>
                    <div class="product-img-wrapper me-3 shadow-sm rounded-4 overflow-hidden"
                      style="width: 50px; height: 50px; flex-shrink: 0;">
                      <img src="<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                        class="w-100 h-100 object-fit-cover">
                    </div>
                  <?php else: ?>
                    <div class="avatar-lg rounded-4 d-flex align-items-center justify-content-center me-3 shadow-sm"
                      style="width: 50px; height: 50px; flex-shrink: 0; background: rgba(0,0,0,0.03); color: var(<?= $accent ?>);">
                      <i class="fas <?= getCategoryIcon($p['category'] ?? '') ?> fa-lg"></i>
                    </div>
                  <?php endif; ?>
                  <div class="overflow-hidden">
                    <h5 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($p['name']) ?></h5>
                    <p class="text-muted small mb-0 text-truncate"><?= htmlspecialchars($p['brand'] ?? '') ?> •
                      <?= htmlspecialchars($p['category'] ?? '') ?>
                    </p>
                    <?php if (!empty($p['supplier_name'])): ?>
                      <p class="text-info mb-0" style="font-size: 0.72rem;"><i class="fas fa-truck-field me-1"></i>
                        <?= htmlspecialchars($p['supplier_name']) ?></p>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="small text-muted mb-4 text-truncate-2"
                  style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                  <?= htmlspecialchars($p['description'] ?? 'No additional description for this item.') ?>
                </div>

                <div class="row g-2 mb-4 bg-light rounded-4 ms-0 me-0 py-2">
                  <div class="col-4 text-center border-end">
                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.55rem;">Available</div>
                    <div class="fw-bold fs-6 <?= ($stockStatus) ? 'text-danger' : 'text-success' ?>">
                      <?= (int) $p['total_stock'] ?>
                    </div>
                  </div>
                  <div class="col-4 text-center border-end">
                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.55rem;">Sales</div>
                    <div class="fw-bold fs-6 text-primary"><?= (int) $p['total_sold'] ?></div>
                  </div>
                  <div class="col-4 text-center">
                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.55rem;">Price</div>
                    <div class="fw-bold fs-6 text-truncate">LKR <?= number_format($p['selling_price'], 0) ?></div>
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                  <div class="small">
                    <span class="text-muted">SKU:</span> <span
                      class="fw-bold fs-7"><?= htmlspecialchars($p['sku']) ?></span>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2"
                      onclick='editProduct(<?= json_encode($p) ?>)' title="Edit Product">
                      <i class="fas fa-pen-to-square"></i>
                    </button>
                    <a href="products.php?delete=<?= $p['id'] ?>"
                      class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2"
                      onclick="return confirm('Archive this product?')" title="Archive Product">
                      <i class="fas fa-trash-can"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Tab 2: Manage Product Form -->
    <div class="tab-pane fade" id="manage-pane" role="tabpanel" tabindex="0">
      <div class="glass-card p-4 border-0" style="border-left: 8px solid var(--accent-violet) !important;">
        <div class="d-flex align-items-center mb-4">
          <div class="bg-primary text-white p-3 rounded-4 me-3 shadow-sm"
            style="background: linear-gradient(135deg, var(--accent-violet) 0%, var(--primary) 100%) !important;">
            <i class="fas fa-file-invoice fa-xl"></i>
          </div>
          <div>
            <h4 class="fw-bold mb-0" id="formTitle">Product Specifications</h4>
            <p class="text-muted small">Enter precise product data to sync with branch stocks</p>
          </div>
        </div>

        <form method="post" id="productForm" enctype="multipart/form-data">
          <input type="hidden" name="action" id="formAction" value="add" />
          <input type="hidden" name="id" id="productId" value="" />

          <div class="row g-4">
            <div class="col-md-3">
              <label class="form-label small fw-bold">SKU / Barcode *</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fas fa-barcode"></i></span>
                <input class="form-control rounded-3 border-0 bg-light" name="sku" id="sku" required
                  placeholder="SKU001">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Commercial Name *</label>
              <input class="form-control rounded-3 border-0 bg-light" name="name" id="name" required
                placeholder="Full Product Title">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Product Image</label>
              <input class="form-control rounded-3 border-0 bg-light" name="product_image" id="product_image"
                type="file" accept="image/*">
              <div id="imageHint" class="small text-muted mt-1" style="display:none;">Leave empty to keep current</div>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Brand *</label>
              <input class="form-control rounded-3 border-0 bg-light" name="brand" id="brand" required
                placeholder="Brand Name">
            </div>
            <div class="col-md-5">
              <label class="form-label small fw-bold">Supplier Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fas fa-truck-ramp-box"></i></span>
                <input class="form-control rounded-3 border-0 bg-light" name="supplier_name" id="supplier_name"
                  list="supplierList" placeholder="Select or type new supplier">
                <datalist id="supplierList">
                  <?php foreach ($suppliers as $s): ?>
                    <option value="<?= htmlspecialchars($s['name']) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Expiry Date</label>
              <input class="form-control rounded-3 border-0 bg-light" name="expiry_date" id="expiry_date" type="date">
            </div>

            <div class="col-md-12">
              <label class="form-label small fw-bold">Detailed Description</label>
              <textarea class="form-control rounded-4 border-0 bg-light" name="description" id="description" rows="3"
                placeholder="Key features, specifications..."></textarea>
            </div>

            <div class="col-md-4">
              <div class="p-3 bg-primary-subtle rounded-4 h-100">
                <label class="form-label small fw-bold text-primary">Target Branch *</label>
                <select class="form-select rounded-3 border-0" name="location_id" id="location_id" required>
                  <option value="">Select Branch</option>
                  <?php foreach ($locations as $l): ?>
                    <option value="<?= (int) $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="mt-3">
                  <label class="form-label small fw-bold text-primary">Initial Quantity</label>
                  <input class="form-control rounded-3 border-0" name="initial_qty" id="initial_qty" type="number"
                    value="0" required min="0">
                </div>
              </div>
            </div>

            <div class="col-md-8">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Min Stock Alert</label>
                  <input class="form-control rounded-3 border-0 bg-light" name="min_stock_level" id="min_stock_level"
                    type="number" value="5">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-danger">Purchase Price (LKR)</label>
                  <input class="form-control rounded-3 border-0 bg-danger-subtle fw-bold" name="purchase_price"
                    id="purchase_price" type="number" step="0.01" value="0.00" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold text-success">Price (LKR)</label>
                  <input class="form-control rounded-3 border-0 bg-success-subtle fw-bold" name="selling_price"
                    id="selling_price" type="number" step="0.01" value="0.00" required>
                </div>
                <div class="col-md-12">
                  <label class="form-label small fw-bold">Operational Status</label>
                  <select class="form-select rounded-3 border-0 bg-light" name="status" id="status">
                    <option value="Active">Active - Product is available for sale</option>
                    <option value="Inactive">Inactive - Product is archived/discontinued</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="col-12 mt-4 d-flex gap-3">
              <button type="submit" class="btn btn-primary px-5 py-3 rounded-pill fw-bold shadow-sm">
                <i class="fas fa-save me-2"></i> Confirm Changes
              </button>
              <button type="button" class="btn btn-light px-5 py-3 rounded-pill fw-bold border" onclick="resetForm()">
                <i class="fas fa-rotate-left me-2"></i> Reset Form
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Tab 3: Stock Alerts -->
    <div class="tab-pane fade" id="alerts-pane" role="tabpanel" tabindex="0">
      <div class="glass-card p-4 border-0" style="border-left: 8px solid var(--accent-rose) !important;">
        <h4 class="fw-bold mb-4"><i class="fas fa-triangle-exclamation text-danger me-2"></i>Low Stock Inventory</h4>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="bg-light">
              <tr>
                <th class="border-0 rounded-start">Item Name</th>
                <th class="border-0">SKU</th>
                <th class="border-0">Current Stock</th>
                <th class="border-0">Min Level</th>
                <th class="border-0 text-center">Status</th>
                <th class="border-0 text-end rounded-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $alerts = array_filter($products, function ($p) {
                return $p['total_stock'] <= $p['min_stock_level'];
              });
              if (empty($alerts)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">All items are sufficiently stocked.</td>
                </tr>
              <?php else:
                foreach ($alerts as $p): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><code class="text-dark"><?= htmlspecialchars($p['sku']) ?></code></td>
                    <td class="fw-bold text-danger"><?= (int) $p['total_stock'] ?></td>
                    <td class="text-muted"><?= (int) $p['min_stock_level'] ?></td>
                    <td class="text-center">
                      <span class="badge <?= $p['total_stock'] <= 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                        <?= $p['total_stock'] <= 0 ? 'Out of Stock' : 'Critical' ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-dark rounded-pill px-3"
                        onclick='editProduct(<?= json_encode($p) ?>)'>Restock</button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function showTab(tabId) {
    const tabTriggerEl = document.querySelector(`#productTabs button[id="${tabId}"]`);
    if (tabTriggerEl) bootstrap.Tab.getOrCreateInstance(tabTriggerEl).show();
  }

  function editProduct(p) {
    showTab('manage-tab');
    document.getElementById('formTitle').innerText = 'Syncing: ' + p.name;
    document.getElementById('formAction').value = 'edit';
    document.getElementById('productId').value = p.id;
    document.getElementById('imageHint').style.display = 'block';
    document.getElementById('sku').value = p.sku;
    document.getElementById('name').value = p.name;
    document.getElementById('brand').value = p.brand || '';
    document.getElementById('category').value = p.category || '';
    document.getElementById('description').value = p.description || '';
    document.getElementById('purchase_price').value = p.purchase_price;
    document.getElementById('selling_price').value = p.selling_price;
    document.getElementById('min_stock_level').value = p.min_stock_level;
    document.getElementById('supplier_name').value = p.supplier_name || '';
    document.getElementById('status').value = p.status;
    document.getElementById('expiry_date').value = p.expiry_date || '';

    // Adjust for locations
    document.getElementById('location_id').value = '';
    document.getElementById('initial_qty').value = p.total_stock;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('formTitle').innerText = 'Product Specifications';
    document.getElementById('formAction').value = 'add';
    document.getElementById('imageHint').style.display = 'none';
    showTab('inventory-tab');
  }

  // Handle URL hash for tab activation
  window.addEventListener('load', () => {
    if (window.location.hash) {
      const tabId = window.location.hash.replace('#', '') + '-tab';
      showTab(tabId);
    }
  });
</script>

<?php require_once 'footer.php'; ?>