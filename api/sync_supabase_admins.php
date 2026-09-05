<?php
/**
 * GeoRubber Watch - Supabase Cloud Admin Accounts Synchronizer
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$admins = [
    [
        'username' => 'admin',
        'full_name' => 'ผู้ดูแลระบบสูงสุด (Super Admin)',
        'role' => 'SUPER_ADMIN',
        'email' => 'admin@georubberwatch.com',
        'phone' => '085-077-7847',
        'password' => 'admin123'
    ],
    [
        'username' => 'forest_surat',
        'full_name' => 'กรมป่าไม้ (Royal Forest Department)',
        'role' => 'FORESTRY_ADMIN',
        'email' => 'suratthani.forest@forest.go.th',
        'phone' => '077-282-141',
        'password' => 'admin123'
    ],
    [
        'username' => 'land_surat',
        'full_name' => 'กรมที่ดิน (Department of Lands)',
        'role' => 'LAND_ADMIN',
        'email' => 'land.surat@dol.go.th',
        'phone' => '077-272-581',
        'password' => 'admin123'
    ],
    [
        'username' => 'raot_surat',
        'full_name' => 'การยางแห่งประเทศไทย (RAOT)',
        'role' => 'RAOT_ADMIN',
        'email' => 'surat.eudr@raot.co.th',
        'phone' => '077-283-421',
        'password' => 'admin123'
    ],
    [
        'username' => 'coop_manager',
        'full_name' => 'สหกรณ์กองทุนสวนยางสุราษฎร์ธานี',
        'role' => 'COOP_ADMIN',
        'email' => 'manager@suratrubber-coop.com',
        'phone' => '077-381-120',
        'password' => 'admin123'
    ],
    [
        'username' => 'rabber_admin',
        'full_name' => 'ผู้ดูแลระบบ',
        'role' => 'RABBER_ADMIN',
        'email' => 'georubber.admin@psu.ac.th',
        'phone' => '077-278-888',
        'password' => 'adminrabber@123'
    ]
];

$pdo = getDatabaseConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

// Ensure users table exists
if ($driver === 'pgsql') {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            role VARCHAR(30) DEFAULT 'farmer',
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
        );
    ");
} else {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            role VARCHAR(30) DEFAULT 'farmer',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

$results = [];

foreach ($admins as $adm) {
    $hash = password_hash($adm['password'], PASSWORD_DEFAULT);
    
    // Check if user exists by email or username
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)");
    $checkStmt->execute([$adm['email'], $adm['username']]);
    $existingId = $checkStmt->fetchColumn();

    if ($existingId) {
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, password_hash = ?, full_name = ?, email = ?, phone = ?, role = ?
            WHERE id = ?
        ");
        $updateStmt->execute([$adm['username'], $hash, $adm['full_name'], $adm['email'], $adm['phone'], $adm['role'], $existingId]);
        $results[] = [
            'status' => 'updated',
            'id' => $existingId,
            'role' => $adm['role'],
            'email' => $adm['email'],
            'full_name' => $adm['full_name']
        ];
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, full_name, email, phone, role)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$adm['username'], $hash, $adm['full_name'], $adm['email'], $adm['phone'], $adm['role']]);
        $results[] = [
            'status' => 'inserted',
            'role' => $adm['role'],
            'email' => $adm['email'],
            'full_name' => $adm['full_name']
        ];
    }
}

// Fetch all users to verify
$allUsers = $pdo->query("SELECT id, username, full_name, email, phone, role, created_at FROM users ORDER BY id ASC")->fetchAll();

echo json_encode([
    'success' => true,
    'driver' => $driver,
    'message' => 'สร้างและอัปเดตข้อมูลบัญชีแอดมินทั้ง 6 ในฐานข้อมูลเรียบร้อยสมบูรณ์',
    'sync_results' => $results,
    'total_users' => count($allUsers),
    'users' => $allUsers
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
