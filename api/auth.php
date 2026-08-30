<?php
/**
 * GeoRubber Watch - Authentication API
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Check current user session
if ($action === 'me') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['authenticated' => false]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role,
               f.id as farmer_id, f.farmer_code, f.prefix, f.first_name, f.last_name,
               f.address, f.subdistrict, f.district, f.province
        FROM users u
        LEFT JOIN farmers f ON f.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'authenticated' => true,
            'user' => $user
        ]);
    } else {
        session_destroy();
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

// Login
if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT u.*, f.id as farmer_id, f.farmer_code
        FROM users u
        LEFT JOIN farmers f ON f.user_id = u.id
        WHERE u.username = ? OR u.email = ?
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['farmer_id'] = $user['farmer_id'];

        unset($user['password_hash']);
        echo json_encode([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
    }
    exit;
}

// Quick Switch Role (For easy demo & testing between Admin and Farmer)
if ($method === 'POST' && $action === 'switch_demo_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    $role = $data['role'] ?? 'admin'; // 'admin' or 'farmer'

    $stmt = $pdo->prepare("
        SELECT u.*, f.id as farmer_id, f.farmer_code
        FROM users u
        LEFT JOIN farmers f ON f.user_id = u.id
        WHERE u.role = ?
        LIMIT 1
    ");
    $stmt->execute([$role]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['farmer_id'] = $user['farmer_id'];

        unset($user['password_hash']);
        echo json_encode([
            'success' => true,
            'message' => "สลับไปยังบทบาท: " . ($role === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'เกษตรกร (Farmer)'),
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้']);
    }
    exit;
}

// Logout
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'ออกจากระบบเรียบร้อยแล้ว']);
    exit;
}

// Fallback
http_response_code(404);
echo json_encode(['error' => 'Action not found']);
