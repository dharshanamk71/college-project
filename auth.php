<?php
/**
 * Authentication Guard: auth.php
 * Protects administrative pages from unauthorized access.
 * Checks if admin session is active; redirects to login.html if not.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.html?error=session_expired");
    exit();
}
?>
