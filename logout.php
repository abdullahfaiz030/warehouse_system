<?php
ob_start();
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session.
session_unset();
session_destroy();

// Immediate redirect
header("Location: login.php");
echo '<script>window.location.href="login.php";</script>';
echo '<meta http-equiv="refresh" content="0;url=login.php">';
exit;