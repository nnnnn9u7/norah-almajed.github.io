<?php
session_start();

/**
 * Get user data if logged in, returns Guest data if not
 */
function checkUserSession() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'] ?? 'User Name',
            'username' => $_SESSION['username'] ?? 'User',
            'email' => $_SESSION['email'] ?? ''
        ];
    } else {
        return [
            'id' => 0,
            'full_name' => 'User Name',
            'username' => 'Guest',
            'email' => ''
        ];
    }
}

/**
 * Check if admin is logged in
 */
function checkAdminSession() {
    if (isset($_SESSION['admin_id'])) {
        return [
            'admin_id' => $_SESSION['admin_id'],
            'admin_username' => $_SESSION['admin_username'] ?? 'Admin',
            'admin_role' => $_SESSION['admin_role'] ?? 'admin',
            'admin_name' => $_SESSION['admin_name'] ?? 'Administrator'
        ];
    } else {
        return null; // Returns null if not admin
    }
}

/**
 * Check if user is logged in (simple boolean)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in (simple boolean)
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Require login for protected pages
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Require admin login for admin pages
 */
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }
}