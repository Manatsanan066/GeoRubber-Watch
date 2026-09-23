<?php
/**
 * GeoRubber Watch - Logout Endpoint
 * ทำลาย Session และ Cookie ทั้งหมด แล้วนำกลับไปหน้า Login ทันที
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

// Clear standard session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    setcookie(session_name(), '', time() - 86400, '/');
    setcookie('PHPSESSID', '', time() - 86400, '/');
}

// Clear all incoming session-related cookies
if (!empty($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $c) {
        $parts = explode('=', $c);
        $cName = trim($parts[0]);
        if ($cName === session_name() || $cName === 'PHPSESSID' || stripos($cName, 'sess') !== false) {
            setcookie($cName, '', time() - 86400, '/');
            setcookie($cName, '', time() - 86400);
        }
    }
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
