<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /tourist/index.php");
        exit();
    }
}

/**
 * Redirect if role doesn't match
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: /tourist/index.php?error=unauthorized");
        exit();
    }
}

/**
 * Logout function
 */
function logout() {
    session_unset();
    session_destroy();
    header("Location: /tourist/index.php");
    exit();
}
?>
