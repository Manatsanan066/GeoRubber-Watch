<?php
/**
 * GeoRubber Watch - Rubber Yield & Latex Production Management API
 * Role-Based Access Control (RBAC): Farmer (Own Data) vs Admin (Full System)
 */
require_once __DIR__ . '/../includes/auth_check.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();
$isUserAdmin = isAdmin();
$farmerId = $currentUser['farmer_id'] ?? null;

if (!$isUserAdmin && !$farmerId && isset($_SESSION['user_id'])) {
    $fStmt = $pdo->prepare("SELECT id FROM farmers WHERE user_id = ?");
    $fStmt->execute([$_SESSION['user_id']]);
    $farmerId = (int)$fStmt->fetchColumn();
    if ($farmerId) {
        $_SESSION['farmer_id'] = $farmerId;
    }
}

// -----------------------------------------------------------------------------
// GET: Fetch Yield Logs or Plot Dropdown Options
// -----------------------------------------------------------------------------
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';

    // 1. Single Yield Record Query (For Edit Modal)
    if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("
            SELECT y.*, p.plot_code, p.plot_name, p.rubber_clone, p.title_deed_no,
                   f.farmer_code, f.prefix, f.first_name, f.last_name
            FROM yield_logs y
            LEFT JOIN rubber_plots p ON p.id = y.plot_id
            LEFT JOIN farmers f ON f.id = y.farmer_id
            WHERE y.id = ?
        ");
        $stmt->execute([$id]);
        $yield = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$yield) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผลผลิต'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$isUserAdmin && (int)$yield['farmer_id'] !== (int)$farmerId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่อนุญาต: ท่านสามารถดู/แก้ไขเฉพาะผลผลิตของตนเองเท่านั้น'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => true, 'yield' => $yield], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Dropdown options for selecting rubber plot
    if ($action === 'dropdown_plots') {
        if (!$isUserAdmin) {
            $stmt = $pdo->prepare("
                SELECT id, plot_code, plot_name, rubber_clone, area_rai, title_deed_no
                FROM rubber_plots
                WHERE farmer_id = ?
                ORDER BY plot_name ASC
            ");
            $stmt->execute([$farmerId ?: -1]);
        } else {
            $stmt = $pdo->query("
                SELECT p.id, p.plot_code, p.plot_name, p.rubber_clone, p.area_rai, p.title_deed_no,
                       f.prefix, f.first_name, f.last_name, f.farmer_code
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                ORDER BY p.plot_name ASC
            ");
        }
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'plots' => $plots], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Query Yield Logs
    $plot_id = $_GET['plot_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 100);

    $where = [];
    $params = [];

    // RBAC: Farmers only view their own yield history
    if (!$isUserAdmin) {
        $where[] = "y.farmer_id = :farmer_id";
        $params[':farmer_id'] = $farmerId ?: -1;
    } else {
        // Admin Search & Filter Options
        if (!empty($_GET['search_name'])) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
            $where[] = "(f.first_name {$likeOp} :s_name OR f.last_name {$likeOp} :s_name OR (f.first_name || ' ' || f.last_name) {$likeOp} :s_name OR f.farmer_code {$likeOp} :s_name)";
            $params[':s_name'] = '%' . trim($_GET['search_name']) . '%';
        }
        if (!empty($_GET['search_plot_code'])) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
            $where[] = "(p.plot_code {$likeOp} :s_code OR p.plot_name {$likeOp} :s_code)";
            $params[':s_code'] = '%' . trim($_GET['search_plot_code']) . '%';
        }
        if (!empty($_GET['search_title_deed'])) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
            $where[] = "p.title_deed_no {$likeOp} :s_deed";
            $params[':s_deed'] = '%' . trim($_GET['search_title_deed']) . '%';
        }
        if (!empty($_GET['farmer_id'])) {
            $where[] = "y.farmer_id = :f_id";
            $params[':f_id'] = (int)$_GET['farmer_id'];
        }
    }

    if ($plot_id) {
        $where[] = "y.plot_id = :p_id";
        $params[':p_id'] = (int)$plot_id;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT y.*, p.plot_code, p.plot_name, p.rubber_clone, p.title_deed_no,
               f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone
        FROM yield_logs y
        LEFT JOIN rubber_plots p ON p.id = y.plot_id
        LEFT JOIN farmers f ON f.id = y.farmer_id
        {$whereClause}
        ORDER BY y.harvest_date DESC, y.id DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary statistics for the filtered dataset
    $sumSql = "
        SELECT COUNT(*) as total_records,
               COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_latex,
               COALESCE(SUM(dry_rubber_kg), 0) as total_dry_rubber,
               COALESCE(SUM(total_revenue), 0) as total_revenue,
               COALESCE(AVG(drc_percent), 0) as avg_drc,
               COALESCE(AVG(price_per_kg), 0) as avg_price
        FROM yield_logs y
        LEFT JOIN rubber_plots p ON p.id = y.plot_id
        LEFT JOIN farmers f ON f.id = y.farmer_id
        {$whereClause}
    ";
    $sumStmt = $pdo->prepare($sumSql);
    $sumStmt->execute($params);
    $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'role' => $currentUser['role'],
        'can_delete' => $isUserAdmin,
        'summary' => $summary,
        'yields' => $logs
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// POST: Add New Yield Log
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;

        $plot_id = (int)($data['plot_id'] ?? 0);
        $harvest_date = $data['harvest_date'] ?? date('Y-m-d');
        $tapping_round = (int)($data['tapping_round'] ?? 1);
        $fresh_latex_kg = (float)($data['fresh_latex_kg'] ?? 0);
        $drc_percent = (float)($data['drc_percent'] ?? 33.5);
        $price_per_kg = (float)($data['price_per_kg'] ?? 65.0);
        $buyer_name = trim($data['buyer_name'] ?? 'จุดรับซื้อน้ำยางสดประจำตำบล');
        $notes = trim($data['notes'] ?? '');

        if ($plot_id <= 0 || $fresh_latex_kg <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณาเลือกแปลงปลูกและระบุน้ำหนักน้ำยางสด']);
            exit;
        }

        // Get plot info & verify ownership
        $plotStmt = $pdo->prepare("SELECT id, farmer_id FROM rubber_plots WHERE id = ?");
        $plotStmt->execute([$plot_id]);
        $plot = $plotStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plot) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูก']);
            exit;
        }

        // RBAC: Farmers can only record yields for their own plots
        if (!$isUserAdmin && (int)$plot['farmer_id'] !== (int)$farmerId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่อนุญาต: ท่านสามารถบันทึกผลผลิตได้เฉพาะแปลงของตนเองเท่านั้น'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $target_farmer_id = (int)$plot['farmer_id'];
        
        // Auto Calculate Total Revenue (Fresh Latex kg * Price per kg)
        $total_revenue = round($fresh_latex_kg * $price_per_kg, 2);
        $dry_rubber_kg = round($fresh_latex_kg * ($drc_percent / 100.0), 2);

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
                $plot_id, $target_farmer_id, $harvest_date, $tapping_round,
                $fresh_latex_kg, $drc_percent, $dry_rubber_kg,
                $price_per_kg, $total_revenue, $buyer_name, $notes
            ]);
            $newId = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $plot_id, $target_farmer_id, $harvest_date, $tapping_round,
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
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -----------------------------------------------------------------------------
// PUT: Update Existing Yield Log (Farmers can edit their own records)
// -----------------------------------------------------------------------------
if ($method === 'PUT') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        // RBAC: Check ownership for Farmers
        if (!$isUserAdmin) {
            $chkStmt = $pdo->prepare("SELECT id FROM yield_logs WHERE id = ? AND farmer_id = ?");
            $chkStmt->execute([$id, $farmerId]);
            if (!$chkStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'ไม่อนุญาต: ท่านสามารถแก้ไขได้เฉพาะผลผลิตของตนเองเท่านั้น'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $plot_id = (int)($data['plot_id'] ?? 0);
        $harvest_date = $data['harvest_date'] ?? date('Y-m-d');
        $tapping_round = (int)($data['tapping_round'] ?? 1);
        $fresh_latex_kg = (float)($data['fresh_latex_kg'] ?? 0);
        $drc_percent = (float)($data['drc_percent'] ?? 33.5);
        $price_per_kg = (float)($data['price_per_kg'] ?? 65.0);
        $buyer_name = trim($data['buyer_name'] ?? '');
        $notes = trim($data['notes'] ?? '');

        $total_revenue = round($fresh_latex_kg * $price_per_kg, 2);
        $dry_rubber_kg = round($fresh_latex_kg * ($drc_percent / 100.0), 2);

        $stmt = $pdo->prepare("
            UPDATE yield_logs SET
                plot_id = ?,
                harvest_date = ?,
                tapping_round = ?,
                fresh_latex_kg = ?,
                drc_percent = ?,
                dry_rubber_kg = ?,
                price_per_kg = ?,
                total_revenue = ?,
                buyer_name = ?,
                notes = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $plot_id, $harvest_date, $tapping_round,
            $fresh_latex_kg, $drc_percent, $dry_rubber_kg, $price_per_kg,
            $total_revenue, $buyer_name, $notes, $id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'แก้ไขข้อมูลผลผลิตเรียบร้อยแล้ว',
            'id' => $id,
            'total_revenue' => $total_revenue
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการแก้ไข: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -----------------------------------------------------------------------------
// DELETE: Delete Yield Log (Only Admin & SUPER_ADMIN allowed)
// -----------------------------------------------------------------------------
if ($method === 'DELETE') {
    try {
        if (!$isUserAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่อนุญาต: เกษตรกรไม่มีสิทธิ์ลบประวัติผลผลิต กรุณาติดต่อผู้ดูแลระบบ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM yield_logs WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลผลผลิตเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

