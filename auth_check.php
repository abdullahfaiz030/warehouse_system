<?php
ob_start();
session_start();

// Redirect if not logged in
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Check for specific roles
function requireRole($allowedRoles)
{
    requireLogin();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        die("<div style='color: red; font-family: sans-serif; padding: 2rem; text-align: center;'>
                <h1>Access Denied</h1>
                <p>You do not have permission to view this page.</p>
                <a href='index.php'>Go Back</a>
             </div>");
    }
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function currentUser()
{
    return $_SESSION['username'] ?? 'Guest';
}

function currentRole()
{
    return $_SESSION['role'] ?? '';
}
