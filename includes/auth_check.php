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
 * Check if the current user has administrative rights
 */
function isAdmin(): bool {
    $role = $_SESSION['role'] ?? '';
    $adminRoles = ['admin', 'SUPER_ADMIN', 'FORESTRY_ADMIN', 'LAND_ADMIN', 'RAOT_ADMIN', 'COOP_ADMIN', 'RABBER_ADMIN'];
    return in_array($role, $adminRoles, true);
}

/**
 * Check if the current user is a farmer
 */
function isFarmer(): bool {
    $role = $_SESSION['role'] ?? 'farmer';
    return !isAdmin();
}

/**
 * Get current authenticated user details
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'ผู้ใช้งาน',
        'role' => $_SESSION['role'] ?? 'farmer',
        'farmer_id' => isset($_SESSION['farmer_id']) ? (int)$_SESSION['farmer_id'] : null,
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'is_admin' => isAdmin()
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
