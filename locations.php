<?php
require_once 'auth_check.php';
requireRole(['Admin', 'Warehouse Staff']);
require_once 'db.php';
$pdo = db();

$msg = "";

// Add location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
  $code = trim($_POST['code'] ?? '');
  $name = trim($_POST['name'] ?? '');

  if ($code === '' || $name === '') {
    $msg = "Code and Name are required.";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO locations (code, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
      $stmt->execute([$code, $name]);
      $msg = "Location configured successfully.";
    } catch (PDOException $e) {
      $msg = "Error: " . $e->getMessage();
    }
  }
}

// Delete location
if (isset($_GET['delete'])) {
  $id = (int) $_GET['delete'];
  try {
    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Location removed.";
  } catch (PDOException $e) {
    $msg = "Error: Could not remove location (it may have stock associated).";
  }
}

$locations = $pdo->query("SELECT * FROM locations ORDER BY id DESC")->fetchAll();

// View Inventory Logic
$view_loc = null;
$inventory = [];
if (isset($_GET['view'])) {
  $view_id = (int) $_GET['view'];
  $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
  $stmt->execute([$view_id]);
  $view_loc = $stmt->fetch();

  if ($view_loc) {
    $stmt = $pdo->prepare("
            SELECT p.*, s.quantity
            FROM stock s
            JOIN products p ON p.id = s.product_id
            WHERE s.location_id = ? AND s.quantity > 0
            ORDER BY p.name ASC
        ");
    $stmt->execute([$view_id]);
    $inventory = $stmt->fetchAll();
  }
}

require_once 'header.php';
?>

<div class="container animate-fade-in">
  <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
      <h2 class="fw-bold mb-0">Branch Management</h2>
      <p class="text-muted small">Manage your warehouse locations and shop branches</p>
    </div>
    <div class="d-flex gap-2">
      <?php if ($view_loc): ?>
        <a href="locations.php" class="btn btn-light px-4 py-2 rounded-pill border">
          <i class="fas fa-arrow-left me-2"></i> Back to Branches
        </a>
      <?php endif; ?>
      <button class="btn btn-primary px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="collapse"
        data-bs-target="#addLocForm">
        <i class="fas fa-plus me-2"></i> Add New Branch
      </button>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4">
      <i class="fas fa-info-circle me-2"></i> <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <!-- View Inventory Section -->
  <?php if ($view_loc): ?>
    <div class="glass-card p-4 mb-5 border-0 bg-primary text-white shadow-lg">
      <div class="d-flex align-items-center mb-4">
        <div class="avatar-sm bg-white text-primary rounded-3 d-flex align-items-center justify-content-center me-3"
          style="width: 50px; height: 50px;">
          <i class="fas fa-warehouse fa-lg"></i>
        </div>
        <div>
          <h6 class="fw-bold mb-0 text-uppercase small opacity-75"><?= htmlspecialchars($view_loc['code']) ?></h6>
          <h3 class="fw-bold mb-0"><?= htmlspecialchars($view_loc['name']) ?> Inventory</h3>
        </div>
      </div>

      <div class="bg-white rounded-4 overflow-hidden text-dark">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="bg-light">
              <tr>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted">Product</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted text-center">SKU</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted text-center">Quantity</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted text-end">Price</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($inventory)): ?>
                <tr>
                  <td colspan="4" class="text-center py-5 text-muted">No stock found at this branch.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($inventory as $i): ?>
                  <tr>
                    <td class="px-4 py-3 border-light">
                      <div class="fw-bold"><?= htmlspecialchars($i['name']) ?></div>
                      <div class="small text-muted"><?= htmlspecialchars($i['brand']) ?></div>
                    </td>
                    <td class="px-4 py-3 border-light text-center">
                      <span class="badge bg-light text-dark border"><?= htmlspecialchars($i['sku']) ?></span>
                    </td>
                    <td class="px-4 py-3 border-light text-center">
                      <div class="fw-bold fs-5"><?= (int) $i['quantity'] ?></div>
                    </td>
                    <td class="px-4 py-3 border-light text-end">
                      <span class="fw-bold text-success">LKR <?= number_format($i['selling_price'], 2) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="collapse mb-4" id="addLocForm">
    <div class="glass-card p-4">
      <h5 class="fw-bold mb-4">Branch Specifications</h5>
      <form method="post" class="row g-3">
        <input type="hidden" name="action" value="add" />
        <div class="col-md-4">
          <label class="form-label small fw-bold">Branch Code *</label>
          <input class="form-control rounded-3" name="code" required placeholder="e.g. CMB-01">
        </div>
        <div class="col-md-8">
          <label class="form-label small fw-bold">Branch Name *</label>
          <input class="form-control rounded-3" name="name" required placeholder="Main Branch - Colombo">
        </div>
        <div class="col-12 mt-3">
          <button class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm">Save Branch</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Branch Cards Grid -->
  <div class="row g-4">
    <?php foreach ($locations as $l): ?>
      <div class="col-md-6 col-lg-4">
        <div class="glass-card p-4 h-100 position-relative hover-shadow transition" style="cursor: pointer;"
          onclick="window.location.href='locations.php?view=<?= (int) $l['id'] ?>'">
          <div class="d-flex align-items-center mb-3">
            <div
              class="avatar-sm bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center me-3"
              style="width: 45px; height: 45px;">
              <i class="fas fa-building fa-lg"></i>
            </div>
            <div>
              <h6 class="fw-bold mb-0 text-uppercase small text-muted"><?= htmlspecialchars($l['code']) ?></h6>
              <h5 class="fw-bold mb-0"><?= htmlspecialchars($l['name']) ?></h5>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <div class="text-primary small fw-bold">
              <i class="fas fa-eye me-1"></i> View Inventory
            </div>
            <a href="locations.php?delete=<?= (int) $l['id'] ?>" class="text-danger text-decoration-none small"
              onclick="event.stopPropagation(); return confirm('Remove this branch?')">
              <i class="fas fa-trash-alt"></i>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
  .hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
  }

  .transition {
    transition: all 0.3s ease;
  }
</style>

<?php require_once 'footer.php'; ?>