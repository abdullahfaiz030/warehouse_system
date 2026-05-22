<?php
require_once 'auth_check.php';
requireRole(['Admin']);
require_once 'db.php';
$pdo = db();

$message = '';
$editMode = false;
$editData = null;

// Handle Add/Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=? WHERE id=?");
        if ($stmt->execute([$name, $contact, $phone, $email, $address, $_POST['id']])) {
            $message = "<div class='alert alert-success'>Supplier updated successfully!</div>";
        }
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $contact, $phone, $email, $address])) {
            $message = "<div class='alert alert-success'>Supplier added successfully!</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Check if supplier has products linked?
    // FK constraint is ON DELETE SET NULL, so it's safe to delete.
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    $message = "<div class='alert alert-success'>Supplier deleted!</div>";
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    $editData = $stmt->fetch();
    if ($editData) $editMode = true;
}

// Fetch Suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();

require_once 'header.php';
?>

<div class="container animate-fade-in pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-gradient">Supplier Management</h2>
            <p class="text-muted small">Manage vendor relationships</p>
        </div>
        <?php if($editMode): ?>
            <a href="suppliers.php" class="btn btn-outline-secondary rounded-pill"><i class="fas fa-times me-2"></i> Cancel Edit</a>
        <?php endif; ?>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <!-- Add/Edit Form -->
        <div class="col-md-4">
            <div class="glass-card p-4 sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3"><i class="fas fa-truck me-2 text-primary"></i> <?= $editMode ? 'Edit Supplier' : 'Add New Supplier' ?></h5>
                <form method="POST" action="suppliers.php">
                    <?php if($editMode): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Company Name</label>
                        <input type="text" name="name" class="form-control rounded-pill" required value="<?= $editData['name'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Contact Person</label>
                        <input type="text" name="contact" class="form-control rounded-pill" value="<?= $editData['contact_person'] ?? '' ?>">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                             <label class="form-label small fw-bold text-muted">Phone</label>
                             <input type="text" name="phone" class="form-control rounded-pill" value="<?= $editData['phone'] ?? '' ?>">
                        </div>
                        <div class="col-6 mb-3">
                             <label class="form-label small fw-bold text-muted">Email</label>
                             <input type="email" name="email" class="form-control rounded-pill" value="<?= $editData['email'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Address</label>
                        <textarea name="address" class="form-control" rows="2" style="border-radius: 15px;"><?= $editData['address'] ?? '' ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill"><?= $editMode ? 'Update Supplier' : 'Save Supplier' ?></button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="col-md-8">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom">
                    <h6 class="fw-bold mb-0">Partner List</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-secondary">Company</th>
                                <th class="px-4 py-3 text-secondary">Contact</th>
                                <th class="px-4 py-3 text-secondary">Details</th>
                                <th class="px-4 py-3 text-secondary text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($suppliers)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No suppliers found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $s): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($s['address']) ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="fw-semibold"><?= htmlspecialchars($s['contact_person']) ?></div>
                                        </td>
                                        <td class="px-4 py-3 small">
                                            <?php if($s['phone']): ?>
                                                <div><i class="fas fa-phone me-1 opacity-50"></i> <?= htmlspecialchars($s['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if($s['email']): ?>
                                                <div><i class="fas fa-envelope me-1 opacity-50"></i> <?= htmlspecialchars($s['email']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Delete this supplier?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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
<?php require_once 'footer.php'; ?>
