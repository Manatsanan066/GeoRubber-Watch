<?php
/**
 * GeoRubber Watch - Farmers Synchronizer & Seeder
 * Populates and synchronizes all registered farmer profiles into the 'farmers' table
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    // 1. Ensure 'farmers' table exists with all required columns
    if ($driver === 'pgsql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS farmers (
                id SERIAL PRIMARY KEY,
                user_id INT REFERENCES users(id) ON DELETE SET NULL,
                farmer_code VARCHAR(50) UNIQUE NOT NULL,
                prefix VARCHAR(20) DEFAULT 'นาย',
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) DEFAULT '',
                id_card_num VARCHAR(20),
                phone VARCHAR(20),
                address TEXT,
                subdistrict VARCHAR(100),
                district VARCHAR(100),
                province VARCHAR(100) DEFAULT 'สุราษฎร์ธานี',
                postal_code VARCHAR(10),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
            ALTER TABLE farmers ADD COLUMN IF NOT EXISTS id_card_num VARCHAR(20);
            ALTER TABLE farmers ADD COLUMN IF NOT EXISTS prefix VARCHAR(20) DEFAULT 'นาย';
            ALTER TABLE farmers ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT '';
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS farmers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                farmer_code VARCHAR(50) UNIQUE NOT NULL,
                prefix VARCHAR(20) DEFAULT 'นาย',
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) DEFAULT '',
                id_card_num VARCHAR(20),
                phone VARCHAR(20),
                address TEXT,
                subdistrict VARCHAR(100),
                district VARCHAR(100),
                province VARCHAR(100) DEFAULT 'สุราษฎร์ธานี',
                postal_code VARCHAR(10),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );
        ");
    }

    // 2. Definitive list of 10 Registered Rubber Farmers in Surat Thani
    $farmersList = [
        [
            'farmer_code' => 'FM-PSU-001',
            'username' => 'matinee',
            'prefix' => 'นางสาว',
            'first_name' => 'มาทินี',
            'last_name' => 'โรยนรินทร์',
            'id_card_num' => '1-8499-00123-45-1',
            'phone' => '093-578-2399',
            'address' => '31 หมู่ 6 มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-002',
            'username' => 'manatsanan',
            'prefix' => 'นางสาว',
            'first_name' => 'มนัสนันท์',
            'last_name' => 'อนันตณรงค์',
            'id_card_num' => '1-8499-00234-56-2',
            'phone' => '095-331-8033',
            'address' => '31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-003',
            'username' => 'somchai',
            'prefix' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'ยางเจริญสุข',
            'id_card_num' => '1-8499-00345-67-3',
            'phone' => '081-234-5678',
            'address' => '145 หมู่ 2 ต.ขุนทะเล อ.เมือง',
            'subdistrict' => 'ขุนทะเล',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-004',
            'username' => null,
            'prefix' => 'นางสาว',
            'first_name' => 'โอ',
            'last_name' => 'ชิ',
            'id_card_num' => '1-8499-00567-89-0',
            'phone' => '089-111-2233',
            'address' => 'ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-005',
            'username' => null,
            'prefix' => 'นาย',
            'first_name' => 'โซโซ',
            'last_name' => 'อนันตณรงค์',
            'id_card_num' => '1-8499-00678-90-1',
            'phone' => '089-222-3344',
            'address' => 'ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-006',
            'username' => null,
            'prefix' => 'นางสาว',
            'first_name' => 'ดาว',
            'last_name' => 'นิล',
            'id_card_num' => '1-8499-00789-01-2',
            'phone' => '089-333-4455',
            'address' => 'ต.ไชยา อ.ไชยา',
            'subdistrict' => 'ไชยา',
            'district' => 'ไชยา',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84110'
        ],
        [
            'farmer_code' => 'FM-PSU-007',
            'username' => null,
            'prefix' => 'นาย',
            'first_name' => 'วุฒิพงษ์',
            'last_name' => 'แดงบรรจง',
            'id_card_num' => '1-8499-00890-12-3',
            'phone' => '089-444-5566',
            'address' => 'ต.ทุ่งเตา อ.บ้านนาสาร',
            'subdistrict' => 'ทุ่งเตา',
            'district' => 'บ้านนาสาร',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84120'
        ],
        [
            'farmer_code' => 'FM-PSU-008',
            'username' => null,
            'prefix' => 'นางสาว',
            'first_name' => 'น้ำตาล',
            'last_name' => 'อนันตณรงค์',
            'id_card_num' => '1-8499-00901-23-4',
            'phone' => '089-555-6677',
            'address' => 'ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-009',
            'username' => null,
            'prefix' => 'นาย',
            'first_name' => 'นักศึกษาสาขาการยาง',
            'last_name' => 'อนันตณรงค์',
            'id_card_num' => '1-8499-01012-34-5',
            'phone' => '089-666-7788',
            'address' => '31 ม.6 ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ],
        [
            'farmer_code' => 'FM-PSU-010',
            'username' => null,
            'prefix' => 'นาย',
            'first_name' => 'สุชาติ',
            'last_name' => 'อนันตณรงค์',
            'id_card_num' => '1-8499-01123-45-6',
            'phone' => '089-777-8899',
            'address' => 'ต.มะขามเตี้ย อ.เมือง',
            'subdistrict' => 'มะขามเตี้ย',
            'district' => 'เมืองสุราษฎร์ธานี',
            'province' => 'สุราษฎร์ธานี',
            'postal_code' => '84000'
        ]
    ];

    $synced = [];

    foreach ($farmersList as $f) {
        // Find matching user_id if username provided or by full name
        $userId = null;
        if (!empty($f['username'])) {
            $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $uStmt->execute([$f['username']]);
            $foundUid = $uStmt->fetchColumn();
            if ($foundUid) {
                $userId = (int)$foundUid;
            }
        }
        if (!$userId) {
            $uStmt = $pdo->prepare("SELECT id FROM users WHERE full_name LIKE ? OR username = ? LIMIT 1");
            $uStmt->execute(['%' . $f['first_name'] . '%', $f['first_name']]);
            $foundUid = $uStmt->fetchColumn();
            if ($foundUid) {
                $userId = (int)$foundUid;
            }
        }

        // Check if farmer already exists by farmer_code or first_name + last_name
        $checkStmt = $pdo->prepare("
            SELECT id FROM farmers 
            WHERE farmer_code = ? 
               OR (first_name = ? AND last_name = ?)
               OR (id_card_num = ? AND id_card_num IS NOT NULL AND id_card_num != '')
            LIMIT 1
        ");
        $checkStmt->execute([$f['farmer_code'], $f['first_name'], $f['last_name'], $f['id_card_num']]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            // Update existing farmer record
            $updateSql = "
                UPDATE farmers 
                SET user_id = COALESCE(?, user_id),
                    prefix = ?,
                    first_name = ?,
                    last_name = ?,
                    id_card_num = ?,
                    phone = ?,
                    address = ?,
                    subdistrict = ?,
                    district = ?,
                    province = ?,
                    postal_code = ?
                WHERE id = ?
            ";
            $pdo->prepare($updateSql)->execute([
                $userId,
                $f['prefix'],
                $f['first_name'],
                $f['last_name'],
                $f['id_card_num'],
                $f['phone'],
                $f['address'],
                $f['subdistrict'],
                $f['district'],
                $f['province'],
                $f['postal_code'],
                $existingId
            ]);
            $synced[] = [
                'action' => 'updated',
                'id' => (int)$existingId,
                'farmer_code' => $f['farmer_code'],
                'name' => $f['prefix'] . $f['first_name'] . ' ' . $f['last_name'],
                'id_card_num' => $f['id_card_num'],
                'user_id' => $userId
            ];
        } else {
            // Insert new farmer record
            $insertSql = "
                INSERT INTO farmers (
                    user_id, farmer_code, prefix, first_name, last_name,
                    id_card_num, phone, address, subdistrict, district, province, postal_code
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $pdo->prepare($insertSql)->execute([
                $userId,
                $f['farmer_code'],
                $f['prefix'],
                $f['first_name'],
                $f['last_name'],
                $f['id_card_num'],
                $f['phone'],
                $f['address'],
                $f['subdistrict'],
                $f['district'],
                $f['province'],
                $f['postal_code']
            ]);
            $newId = (int)$pdo->lastInsertId();
            $synced[] = [
                'action' => 'inserted',
                'id' => $newId,
                'farmer_code' => $f['farmer_code'],
                'name' => $f['prefix'] . $f['first_name'] . ' ' . $f['last_name'],
                'id_card_num' => $f['id_card_num'],
                'user_id' => $userId
            ];
        }
    }

    // 3. Count total active farmers in database
    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
    $allCurrentFarmers = $pdo->query("SELECT id, farmer_code, prefix, first_name, last_name, id_card_num, phone, province FROM farmers ORDER BY id ASC")->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => "ซิงก์ข้อมูลเกษตรกรลงในตาราง farmers เรียบร้อยแล้ว (รวมทั้งหมด {$totalCount} ราย)",
        'driver' => $driver,
        'total_farmers' => $totalCount,
        'farmers' => $allCurrentFarmers,
        'sync_log' => $synced
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
