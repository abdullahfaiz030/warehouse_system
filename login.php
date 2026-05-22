<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $pdo = db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="glass-card login-card p-5 animate-fade-in">
            <div class="text-center mb-4">
                <i class="fas fa-warehouse text-primary fs-1 mb-3"></i>
                <h3 class="fw-bold">Welcome Back</h3>
                <p class="text-muted small">Sign in to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 text-center small rounded-pill border-0 bg-danger-subtle text-danger mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Username</label>
                    <input type="text" name="username" class="form-control rounded-pill px-3" required autofocus placeholder="Enter your username">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-3" required placeholder="••••••••">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary rounded-pill py-2 shadow-sm">
                        Sign In <i class="fas fa-arrow-right ms-2 small"></i>
                    </button>
                </div>
            </form>
            
            <div class="mt-4 text-center">
                <small class="text-muted opacity-50">Warehouse Management System v2.0</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
