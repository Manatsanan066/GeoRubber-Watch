<?php
/**
 * GeoRubber Watch - Rubber Yield & Latex Production Management API
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Fetch Yield Logs
if ($method === 'GET') {
    $plot_id = $_GET['plot_id'] ?? null;
    $farmer_id = $_GET['farmer_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);

    // If logged in as farmer, restrict to own data unless admin
    $role = $_SESSION['role'] ?? 'admin';
    if ($role === 'farmer' && isset($_SESSION['farmer_id'])) {
        $farmer_id = $_SESSION['farmer_id'];
    }

    $where = [];
    $params = [];

    if ($plot_id) {
        $where[] = "y.plot_id = ?";
        $params[] = $plot_id;
    }
    if ($farmer_id) {
        $where[] = "y.farmer_id = ?";
        $params[] = $farmer_id;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT y.*, p.plot_code, p.plot_name, p.rubber_clone,
               f.farmer_code, f.prefix, f.first_name, f.last_name
        FROM yield_logs y
        LEFT JOIN rubber_plots p ON p.id = y.plot_id
        LEFT JOIN farmers f ON f.id = y.farmer_id
        {$whereClause}
        ORDER BY y.harvest_date DESC, y.id DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Summary statistics
    $sumSql = "
        SELECT COUNT(*) as total_records,
               COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_latex,
               COALESCE(SUM(dry_rubber_kg), 0) as total_dry_rubber,
               COALESCE(SUM(total_revenue), 0) as total_revenue,
               COALESCE(AVG(drc_percent), 0) as avg_drc,
               COALESCE(AVG(price_per_kg), 0) as avg_price
        FROM yield_logs y
        {$whereClause}
    ";
    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch();

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'yields' => $logs
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// POST: Add New Yield Log
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $plot_id = (int)($data['plot_id'] ?? 0);
    $harvest_date = $data['harvest_date'] ?? date('Y-m-d');
    $tapping_round = (int)($data['tapping_round'] ?? 1);
    $fresh_latex_kg = (float)($data['fresh_latex_kg'] ?? 0);
    $drc_percent = (float)($data['drc_percent'] ?? 33.5);
    $price_per_kg = (float)($data['price_per_kg'] ?? 65.0);
    $buyer_name = trim($data['buyer_name'] ?? 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด');
    $notes = trim($data['notes'] ?? '');

    if ($plot_id <= 0 || $fresh_latex_kg <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'กรุณาเลือกแปลงปลูกและระบุน้ำหนักน้ำยางสด']);
        exit;
    }

    // Get farmer_id from plot
    $plotStmt = $pdo->prepare("SELECT farmer_id FROM rubber_plots WHERE id = ?");
    $plotStmt->execute([$plot_id]);
    $plot = $plotStmt->fetch();

    if (!$plot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูก']);
        exit;
    }

    $farmer_id = $plot['farmer_id'];
    
    // Calculate Total Revenue directly (Fresh Latex kg * Price per kg)
    $total_revenue = round($fresh_latex_kg * $price_per_kg, 2);
    $dry_rubber_kg = round($fresh_latex_kg, 2);
    $drc_percent = (float)($data['drc_percent'] ?? 100.0);

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $insertSql = "
        INSERT INTO yield_logs (
            plot_id, farmer_id, harvest_date, tapping_round,
            fresh_latex_kg, drc_percent, dry_rubber_kg,
            price_per_kg, total_revenue, buyer_name, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    if ($driver === 'pgsql') {
        $insertSql .= " RETURNING id";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            $plot_id, $farmer_id, $harvest_date, $tapping_round,
            $fresh_latex_kg, $drc_percent, $dry_rubber_kg,
            $price_per_kg, $total_revenue, $buyer_name, $notes
        ]);
        $newId = (int)$stmt->fetchColumn();
    } else {
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            $plot_id, $farmer_id, $harvest_date, $tapping_round,
            $fresh_latex_kg, $drc_percent, $dry_rubber_kg,
            $price_per_kg, $total_revenue, $buyer_name, $notes
        ]);
        $newId = (int)$pdo->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกข้อมูลผลผลิตน้ำยางสดเรียบร้อยแล้ว',
        'id' => $newId,
        'fresh_latex_kg' => $fresh_latex_kg,
        'total_revenue' => $total_revenue
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// PUT: Update Existing Yield Log
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    $plot_id = (int)($data['plot_id'] ?? 0);
    $harvest_date = $data['harvest_date'] ?? date('Y-m-d');
    $tapping_round = (int)($data['tapping_round'] ?? 1);
    $fresh_latex_kg = (float)($data['fresh_latex_kg'] ?? 0);
    $price_per_kg = (float)($data['price_per_kg'] ?? 65.0);
    $buyer_name = trim($data['buyer_name'] ?? '');
    $notes = trim($data['notes'] ?? '');

    $total_revenue = round($fresh_latex_kg * $price_per_kg, 2);
    $dry_rubber_kg = round($fresh_latex_kg, 2);

    $stmt = $pdo->prepare("
        UPDATE yield_logs SET
            plot_id = ?,
            harvest_date = ?,
            tapping_round = ?,
            fresh_latex_kg = ?,
            dry_rubber_kg = ?,
            price_per_kg = ?,
            total_revenue = ?,
            buyer_name = ?,
            notes = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $plot_id, $harvest_date, $tapping_round,
        $fresh_latex_kg, $dry_rubber_kg, $price_per_kg,
        $total_revenue, $buyer_name, $notes, $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'แก้ไขข้อมูลผลผลิตเรียบร้อยแล้ว',
        'id' => $id,
        'total_revenue' => $total_revenue
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// DELETE: Delete Yield Log
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM yield_logs WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลผลผลิตเรียบร้อยแล้ว']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
