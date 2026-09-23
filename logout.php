<?php
/**
 * GeoRubber Watch - Logout Endpoint
 * ทำลาย Session และ Cookie ทั้งหมด แล้วนำกลับไปหน้า Login
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

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

session_unset();
session_destroy();

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'redirect' => 'login.php?msg=logged_out']);
    exit;
}

header("Location: login.php?msg=logged_out");
exit;
