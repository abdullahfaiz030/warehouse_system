<?php
require_once 'auth_check.php';
requireRole(['Admin']);
require_once 'db.php';
$pdo = db();

$message = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Check if exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        $message = "<div class='alert alert-danger'>Username already exists!</div>";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $hash, $role])) {
            $message = "<div class='alert alert-success'>User added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error adding user.</div>";
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent deleting self (simple check)
    if ($id == $_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>You cannot delete yourself!</div>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='alert alert-success'>User deleted successfully!</div>";
    }
}

// Fetch Users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

require_once 'header.php';
?>

<div class="container animate-fade-in pt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-gradient">User Management</h2>
            <p class="text-muted small">Manage system access and roles</p>
        </div>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <!-- Add User Form -->
        <div class="col-md-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-user-plus me-2 text-primary"></i> Add New User</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Username</label>
                        <input type="text" name="username" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">Role</label>
                        <select name="role" class="form-select rounded-pill" required>
                            <option value="Warehouse Staff">Warehouse Staff</option>
                            <option value="Cashier">Cashier</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Create User</button>
                </form>
            </div>
        </div>

        <!-- User List -->
        <div class="col-md-8">
            <div class="glass-card p-0 overflow-hidden">
                <div class="p-3 bg-light border-bottom">
                    <h6 class="fw-bold mb-0">Existing Users</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-secondary">ID</th>
                                <th class="px-4 py-3 text-secondary">Username</th>
                                <th class="px-4 py-3 text-secondary">Role</th>
                                <th class="px-4 py-3 text-secondary">Created At</th>
                                <th class="px-4 py-3 text-secondary text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="px-4 py-3 text-muted">#<?= $u['id'] ?></td>
                                    <td class="px-4 py-3 fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="px-4 py-3">
                                        <?php 
                                            $badgeClass = 'bg-secondary';
                                            if($u['role'] === 'Admin') $badgeClass = 'bg-danger';
                                            if($u['role'] === 'Cashier') $badgeClass = 'bg-success';
                                            if($u['role'] === 'Warehouse Staff') $badgeClass = 'bg-primary';
                                        ?>
                                        <span class="badge <?= $badgeClass ?> rounded-pill px-3"><?= $u['role'] ?></span>
                                    </td>
                                    <td class="px-4 py-3 small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    <td class="px-4 py-3 text-end">
                                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'footer.php'; ?>
