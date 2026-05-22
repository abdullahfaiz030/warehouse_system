<?php
require_once 'auth_check.php';
requireRole(['Admin', 'Warehouse Staff']);
require_once 'db.php';
$pdo = db();

$msg = "";
$status = "info";

// Load dropdown data
$products = $pdo->query("SELECT id, sku, name FROM products ORDER BY name")->fetchAll();
$locations = $pdo->query("SELECT id, name FROM locations ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_id = (int) ($_POST['product_id'] ?? 0);
  $location_id = (int) ($_POST['location_id'] ?? 0); // Source Location
  $dest_location_id = (int) ($_POST['dest_location_id'] ?? 0); // Destination (only for Transfers)
  $type = $_POST['type'] ?? '';
  $qty = (int) ($_POST['qty'] ?? 0);
  $note = trim($_POST['note'] ?? '');

  if ($product_id <= 0 || $location_id <= 0 || !in_array($type, ['IN', 'OUT', 'TRANSFER'], true) || $qty <= 0) {
    $msg = "Please fill all required fields correctly.";
    $status = "warning";
  } elseif ($type === 'TRANSFER' && ($dest_location_id <= 0 || $dest_location_id === $location_id)) {
    $msg = "Please select a different destination branch for transfer.";
    $status = "warning";
  } else {
    try {
      $pdo->beginTransaction();

      if ($type === 'TRANSFER') {
        // Check source stock
        $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ? AND location_id = ? FOR UPDATE");
        $stmt->execute([$product_id, $location_id]);
        $source_qty = (int) ($stmt->fetch()['quantity'] ?? 0);

        if ($source_qty < $qty) {
          throw new Exception("Insufficient stock at source branch. Available: $source_qty units.");
        }

        // Get branch names for note
        $stmt = $pdo->prepare("SELECT name FROM locations WHERE id IN (?, ?)");
        $stmt->execute([$location_id, $dest_location_id]);
        $loc_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $from_name = $loc_names[0];
        $to_name = $loc_names[1];
        $transfer_note = "Transferred from $from_name to $to_name. " . ($note ? "($note)" : "");

        // 1. Deduct from Source (OUT)
        $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ? AND location_id = ?");
        $stmt->execute([$qty, $product_id, $location_id]);
        $stmt = $pdo->prepare("INSERT INTO movements (product_id, location_id, movement_type, qty, note) VALUES (?, ?, 'OUT', ?, ?)");
        $stmt->execute([$product_id, $location_id, $qty, $transfer_note]);

        // 2. Add to Destination (IN)
        $stmt = $pdo->prepare("INSERT INTO stock (product_id, location_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->execute([$product_id, $dest_location_id, $qty, $qty]);
        $stmt = $pdo->prepare("INSERT INTO movements (product_id, location_id, movement_type, qty, note) VALUES (?, ?, 'IN', ?, ?)");
        $stmt->execute([$product_id, $dest_location_id, $qty, $transfer_note]);

        $msg = "Successfully transferred $qty units between branches.";
        $status = "success";

      } else {
        // Standard IN / OUT logic
        $stmt = $pdo->prepare("INSERT INTO movements (product_id, location_id, movement_type, qty, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $location_id, $type, $qty, $note]);

        $stmt = $pdo->prepare("INSERT INTO stock (product_id, location_id, quantity) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE quantity = quantity");
        $stmt->execute([$product_id, $location_id]);

        if ($type === 'IN') {
          $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity + ? WHERE product_id = ? AND location_id = ?");
          $stmt->execute([$qty, $product_id, $location_id]);
          $msg = "Stock successfully increased (IN) by $qty units.";
          $status = "success";
        } else {
          $stmt = $pdo->prepare("SELECT quantity FROM stock WHERE product_id = ? AND location_id = ? FOR UPDATE");
          $stmt->execute([$product_id, $location_id]);
          $current = (int) ($stmt->fetch()['quantity'] ?? 0);

          if ($current < $qty) {
            throw new Exception("Insufficient stock. Availability: $current units.");
          }
          $stmt = $pdo->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ? AND location_id = ?");
          $stmt->execute([$qty, $product_id, $location_id]);
          $msg = "Stock successfully reduced (OUT) by $qty units.";
          $status = "success";
        }
      }

      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      $msg = $e->getMessage();
      $status = "danger";
    }
  }
}

// Recent movements
$movements = $pdo->query("
    SELECT m.*, p.name AS product_name, l.name AS location_name
    FROM movements m
    JOIN products p ON p.id = m.product_id
    JOIN locations l ON l.id = m.location_id
    ORDER BY m.id DESC
    LIMIT 20
")->fetchAll();

require_once 'header.php';
?>

<div class="container animate-fade-in">
  <div class="mb-4 mt-2">
    <h2 class="fw-bold mb-0">Stock Movements & Transfers</h2>
    <p class="text-muted small">Update inventory levels or shift stock between branches</p>
  </div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $status ?> border-0 shadow-sm rounded-4 mb-4">
      <i class="fas <?= ($status == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-5">
    <div class="col-lg-5">
      <div class="glass-card p-4 border-0">
        <h5 class="fw-bold mb-4">New Transaction</h5>
        <form method="post" class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-bold">Select Product *</label>
            <select class="form-select rounded-3" name="product_id" required>
              <option value="">Product...</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold" id="sourceLabel">Branch / Source *</label>
            <select class="form-select rounded-3" name="location_id" required>
              <option value="">Location...</option>
              <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12" id="destContainer" style="display: none;">
            <label class="form-label small fw-bold">Destination Branch *</label>
            <select class="form-select rounded-3 border-primary" name="dest_location_id">
              <option value="">Select Target Branch...</option>
              <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="small text-primary mt-1">Shift stock from Source to Destination</div>
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold">Action Type *</label>
            <div class="btn-group w-100 shadow-sm rounded-3 overflow-hidden">
              <input type="radio" class="btn-check" name="type" id="typeIn" value="IN" checked
                onchange="toggleTransfer(false)">
              <label class="btn btn-outline-success border-0 py-2" for="typeIn"><i class="fas fa-plus-circle me-1"></i>
                IN</label>

              <input type="radio" class="btn-check" name="type" id="typeOut" value="OUT"
                onchange="toggleTransfer(false)">
              <label class="btn btn-outline-danger border-0 py-2" for="typeOut"><i class="fas fa-minus-circle me-1"></i>
                OUT</label>

              <input type="radio" class="btn-check" name="type" id="typeTransfer" value="TRANSFER"
                onchange="toggleTransfer(true)">
              <label class="btn btn-outline-primary border-0 py-2" for="typeTransfer"><i
                  class="fas fa-exchange-alt me-1"></i> TRANSFER</label>
            </div>
          </div>

          <div class="col-md-12">
            <label class="form-label small fw-bold">Quantity *</label>
            <input class="form-control rounded-3" name="qty" type="number" min="1" required placeholder="Amount">
          </div>

          <div class="col-12">
            <label class="form-label small fw-bold">Internal Note</label>
            <textarea class="form-control rounded-3" name="note" rows="2"
              placeholder="Reference or reason..."></textarea>
          </div>

          <div class="col-12 mt-4 text-center">
            <button class="btn btn-primary px-5 py-2 rounded-pill fw-bold shadow-sm">
              Process Movement
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="glass-card p-0 border-0 overflow-hidden shadow-sm">
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Live Movement Feed</h5>
          <span class="badge bg-light text-muted fw-normal px-3 py-2 rounded-pill border">Last 20 updates</span>
        </div>
        <div class="table-responsive" style="max-height: 550px;">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light sticky-top">
              <tr>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted">Type</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted">Product & Location</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted text-center">Qty</th>
                <th class="px-4 py-3 border-0 small text-uppercase fw-bold text-muted text-end">Time</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($movements)): ?>
                <tr>
                  <td colspan="4" class="text-center py-5 text-muted">Awaiting first movement...</td>
                </tr>
              <?php else: ?>
                <?php foreach ($movements as $m): ?>
                  <tr>
                    <td class="px-4 py-3">
                      <?php
                      $badgeClass = ($m['movement_type'] === 'IN') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                      $icon = ($m['movement_type'] === 'IN') ? 'fa-arrow-down' : 'fa-arrow-up';
                      if (stripos($m['note'], 'Transferred') !== false) {
                        $badgeClass = 'bg-primary-subtle text-primary';
                        $icon = ($m['movement_type'] === 'IN') ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
                      }
                      ?>
                      <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2 border-0">
                        <i class="fas <?= $icon ?> me-1"></i> <?= $m['movement_type'] ?>
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="fw-bold text-truncate" style="max-width: 200px;">
                        <?= htmlspecialchars($m['product_name']) ?></div>
                      <div class="small text-muted mb-1"><?= htmlspecialchars($m['location_name']) ?></div>
                      <?php if ($m['note']): ?>
                        <div
                          class="small text-muted fst-italic py-1 px-2 bg-light rounded-2 border-start border-3 border-secondary"
                          style="font-size: 0.7rem;">
                          <?= htmlspecialchars($m['note']) ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center fw-bold fs-5"><?= (int) $m['qty'] ?></td>
                    <td class="px-4 py-3 text-end small text-muted">
                      <?= date('M d, H:i', strtotime($m['created_at'])) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function toggleTransfer(isTransfer) {
    const dest = document.getElementById('destContainer');
    const label = document.getElementById('sourceLabel');
    dest.style.display = isTransfer ? 'block' : 'none';
    label.innerText = isTransfer ? 'Source Branch *' : 'Branch / Location *';
    document.querySelector('[name="dest_location_id"]').required = isTransfer;
  }
</script>

<?php require_once 'footer.php'; ?>