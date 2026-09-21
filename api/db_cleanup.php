<?php
/**
 * GeoRubber Watch - Database Cleanup & Optimization Script
 * Drops deprecated/unused tables and verifies the 6 core active tables.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDatabaseConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    $dropped = [];
    $errors = [];

    // List of redundant / unused tables to drop
    $tablesToDrop = [
        'citizen_identities',
        'farmer_identities',
        'farmer_id_cards',
        'temp_plots',
        'test_plots',
        'temp_yields'
    ];

    foreach ($tablesToDrop as $tbl) {
        try {
            if ($driver === 'pgsql') {
                $pdo->exec("DROP TABLE IF EXISTS {$tbl} CASCADE;");
            } else {
                $pdo->exec("DROP TABLE IF EXISTS `{$tbl}`;");
            }
            $dropped[] = $tbl;
        } catch (Throwable $e) {
            $errors[] = "Error dropping {$tbl}: " . $e->getMessage();
        }
    }

    // Ensure columns id_card_num exist in core tables
    try {
        if ($driver === 'pgsql') {
            $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS id_card_num VARCHAR(20);");
            $pdo->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS id_card_num VARCHAR(20);");
            
            // Sync any existing un-synced id_card_num between farmers and users
            $pdo->exec("
                UPDATE users u
                SET id_card_num = f.id_card_num
                FROM farmers f
                WHERE f.user_id = u.id 
                  AND (u.id_card_num IS NULL OR u.id_card_num = '') 
                  AND f.id_card_num IS NOT NULL AND f.id_card_num <> '';
            ");
        } elseif ($driver === 'sqlite') {
            @$pdo->exec("ALTER TABLE users ADD COLUMN id_card_num VARCHAR(20);");
            @$pdo->exec("ALTER TABLE farmers ADD COLUMN id_card_num VARCHAR(20);");
        }
    } catch (Throwable $e) {}

    // Fetch active tables in database
    $activeTables = [];
    if ($driver === 'pgsql') {
        $stmt = $pdo->query("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
              AND table_type = 'BASE TABLE'
            ORDER BY table_name ASC
        ");
        $activeTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($driver === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name ASC");
        $activeTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->query("SHOW TABLES");
        $activeTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Filter and annotate core tables
    $coreTablesMap = [
        'users' => '✅ จัดการบัญชีผู้ใช้งาน, สิทธิ์การเข้าถึง และเลขบัตร ปชช. 13 หลัก',
        'farmers' => '✅ ทะเบียนเกษตรกรชาวสวนยาง, ข้อมูลส่วนบุคคล และเลขบัตร ปชช. 13 หลัก',
        'forest_reserves' => '✅ แนวเขตป่าสงวนแห่งชาติ 26 แห่ง จ.สุราษฎร์ธานี (Zone C)',
        'rubber_plots' => '✅ ทะเบียนแปลงปลูกยางพารา, ขอบเขต GIS Polygon และการประเมิน EUDR',
        'yield_logs' => '✅ ประวัติผลผลิตน้ำยางสด, DRC และระบบตรวจจับการสวมสิทธิ์',
        'traceability_batches' => '✅ รหัสตรวจสอบย้อนกลับดิจิทัลและใบรับรอง DDS',
        'spatial_ref_sys' => '🛡️ ตารางระบบ PostGIS พิกัดภูมิศาสตร์ WGS84 (System Table)'
    ];

    $coreStatus = [];
    foreach ($activeTables as $tblName) {
        $coreStatus[$tblName] = $coreTablesMap[$tblName] ?? 'Custom Table';
    }

    echo json_encode([
        'success' => true,
        'driver' => $driver,
        'message' => 'ล้างตารางที่ไม่เกี่ยวข้องและปรับปรุงฐานข้อมูลเรียบร้อยแล้ว',
        'dropped_tables' => $dropped,
        'active_tables_count' => count($activeTables),
        'active_tables' => $coreStatus,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $eGlobal) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $eGlobal->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
