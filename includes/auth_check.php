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
    $adminRoles = ['admin', 'SUPER_ADMIN', 'FORESTRY_ADMIN', 'LAND_ADMIN', 'RAOT_ADMIN', 'COOP_ADMIN', 'RABBER_ADMIN', 'factory', 'buyer', 'trader'];
    return in_array($role, $adminRoles, true);
}

/**
 * Check if the current user is a factory or buyer
 */
function isFactory(): bool {
    $role = $_SESSION['role'] ?? '';
    return in_array($role, ['factory', 'buyer', 'trader', 'COOP_ADMIN'], true);
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
        'id_card_num' => $_SESSION['id_card_num'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'is_admin' => isAdmin()
    ];
}

/**
 * Enforce authentication: Redirect to login.php if not authenticated (or return JSON 401 for API/AJAX)
 */
function requireAuth(?string $customRedirect = null): void {
    if (!isLoggedIn()) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
        $isApi = (strpos($uri, '/api/') !== false || strpos($script, '/api/') !== false);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        $wantsJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isApi || $isAjax || $wantsJson) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'กรุณาเข้าสู่ระบบก่อนทำรายการ',
                'redirect' => 'login.php'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $currentPage = $customRedirect ?? basename($script ?: 'index.php');
        if (!empty($_SERVER['QUERY_STRING'])) {
            $currentPage .= '?' . $_SERVER['QUERY_STRING'];
        }
        header("Location: login.php?redirect=" . urlencode($currentPage));
        exit;
    }
}

// Automatically enforce login guard on protected files
$currentScriptName = basename($_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? ''));
$publicAccessPages = [
    'index.php',
    'overview.php',
    'contact.php',
    'login.php',
    'register.php',
    'trace.php',
    'certificate.php',
    'qr.php',
    'verify.php',
    'public_trace.php',
    'forests.php',
    'plots.php'
];

if (!defined('ALLOW_PUBLIC_ACCESS') && !in_array($currentScriptName, $publicAccessPages, true)) {
    requireAuth();
}
