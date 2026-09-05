<?php
/**
 * GeoRubber Watch • Authentication & Session Guard
 * ตรวจสอบสิทธิ์การเข้าสู่ระบบก่อนเข้าใช้งานทุกหน้า
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the current user is authenticated
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user details
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'ผู้ใช้งาน',
        'role' => $_SESSION['role'] ?? 'farmer',
        'farmer_id' => $_SESSION['farmer_id'] ?? null,
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? ''
    ];
}

/**
 * Enforce authentication: Redirect to login.php if not authenticated
 */
function requireAuth(?string $customRedirect = null): void {
    if (!isLoggedIn()) {
        $currentPage = $customRedirect ?? basename($_SERVER['PHP_SELF'] ?? 'index.php');
        if (!empty($_SERVER['QUERY_STRING'])) {
            $currentPage .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: login.php?redirect=" . urlencode($currentPage));
        exit;
    }
}

// Automatically enforce login guard on all files that include this file
requireAuth();
