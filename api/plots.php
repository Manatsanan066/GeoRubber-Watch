<?php
/**
 * GeoRubber Watch - Rubber Plots Management API
 */
require_once __DIR__ . '/../includes/auth_check.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];
$currentUser = getCurrentUser();
$isUserAdmin = isAdmin();
$currentFarmerId = $currentUser['farmer_id'] ?? null;
if (!$currentFarmerId && isset($_SESSION['user_id'])) {
    $fStmt = $pdo->prepare("SELECT id FROM farmers WHERE user_id = ? ORDER BY id ASC LIMIT 1");
    $fStmt->execute([$_SESSION['user_id']]);
    $foundFId = $fStmt->fetchColumn();
    if ($foundFId) {
        $currentFarmerId = (int)$foundFId;
        $_SESSION['farmer_id'] = $currentFarmerId;
    }
}

// Clean up unwanted auto-prefixes for custom farmer names if any
try {
    $pdo->exec("UPDATE farmers SET prefix = '' WHERE prefix = 'นาย' AND (first_name LIKE '%แปลงปลูก%' OR first_name LIKE '%MNSN%')");
} catch (Throwable $eIgnore) {}

// Helper: Calculate polygon area in Sqm from Lat/Lng coordinates
function calculatePolygonAreaSqm($coords) {
    $area = 0.0;
    $n = count($coords);
    if ($n < 3) return 0.0;

    $earthRadius = 6378137; // WGS84 major axis
    for ($i = 0; $i < $n - 1; $i++) {
        $p1 = $coords[$i];
        $p2 = $coords[$i + 1];
        $area += deg2rad($p2[0] - $p1[0]) * (2 + sin(deg2rad($p1[1])) + sin(deg2rad($p2[1])));
    }
    $area = abs($area * $earthRadius * $earthRadius / 2.0);
    return round($area, 2);
}

// Helper: Convert Sqm to Thai Land Measurement (Rai - Ngan - Sq.Wah)
function sqmToThaiArea($sqm) {
    $totalSqWah = $sqm / 4.0;
    $rai = floor($totalSqWah / 400.0);
    $remSqWah = $totalSqWah - ($rai * 400.0);
    $ngan = floor($remSqWah / 100.0);
    $sqwah = round($remSqWah - ($ngan * 100.0), 2);
    $hectare = round($sqm / 10000.0, 4);

    return [
        'rai' => (int)$rai,
        'ngan' => (int)$ngan,
        'sqwah' => (float)$sqwah,
        'sqm' => (float)$sqm,
        'hectare' => (float)$hectare,
        'formatted' => "{$rai} ไร่ {$ngan} งาน {$sqwah} ตร.ว."
    ];
}

// -----------------------------------------------------------------------------
// GET: Fetch Plots
// -----------------------------------------------------------------------------
if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    $token = $_GET['token'] ?? null;
    $farmer_id = $_GET['farmer_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $format = $_GET['format'] ?? 'json'; // 'json' or 'geojson'

    // RBAC: Farmers only view their own plots
    if (!$isUserAdmin) {
        if (!$currentFarmerId && isset($_SESSION['user_id'])) {
            $fStmt = $pdo->prepare("SELECT id FROM farmers WHERE user_id = ?");
            $fStmt->execute([$_SESSION['user_id']]);
            $currentFarmerId = $fStmt->fetchColumn();
            if ($currentFarmerId) {
                $_SESSION['farmer_id'] = (int)$currentFarmerId;
            }
        }
        $farmer_id = $currentFarmerId ?: -1;
    }

    // Action: Farmer Lookup by National ID (13 digits) or Query
    if (isset($_GET['action']) && $_GET['action'] === 'lookup_farmer') {
        $q = trim($_GET['q'] ?? ($_GET['id_card'] ?? ($_GET['id_card_num'] ?? '')));
        if ($q === '') {
            echo json_encode(['success' => true, 'found' => false, 'message' => 'กรุณาระบุเลขประจำตัวประชาชน 13 หลัก หรือรหัสเกษตรกร'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $cleanDigits = preg_replace('/\D/', '', $q);
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

        $farmer = null;
        if (!empty($cleanDigits) && strlen($cleanDigits) >= 4) {
            $stmt = $pdo->prepare("
                SELECT f.*, COUNT(p.id) as plot_count
                FROM farmers f
                LEFT JOIN rubber_plots p ON p.farmer_id = f.id
                WHERE REPLACE(REPLACE(REPLACE(COALESCE(f.id_card_num,''), '-', ''), ' ', ''), '.', '') {$likeOp} ?
                   OR f.id_card_num {$likeOp} ?
                   OR f.farmer_code {$likeOp} ?
                GROUP BY f.id
                LIMIT 1
            ");
            $stmt->execute(["%{$cleanDigits}%", "%{$q}%", "%{$q}%"]);
            $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$farmer && !empty($q)) {
            $stmt = $pdo->prepare("
                SELECT f.*, COUNT(p.id) as plot_count
                FROM farmers f
                LEFT JOIN rubber_plots p ON p.farmer_id = f.id
                WHERE f.first_name {$likeOp} ?
                   OR f.last_name {$likeOp} ?
                   OR (f.first_name || ' ' || f.last_name) {$likeOp} ?
                   OR f.farmer_code {$likeOp} ?
                GROUP BY f.id
                LIMIT 1
            ");
            $stmt->execute(["%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"]);
            $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($farmer) {
            $fullName = trim(($farmer['prefix'] ?? '') . $farmer['first_name'] . (!empty($farmer['last_name']) ? ' ' . $farmer['last_name'] : ''));
            $digits = preg_replace('/\D/', '', $farmer['id_card_num'] ?? '');
            $formattedId = $farmer['id_card_num'] ?? '';
            if (strlen($digits) === 13) {
                $formattedId = substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-' . substr($digits, 5, 5) . '-' . substr($digits, 10, 2) . '-' . substr($digits, 12, 1);
            }

            echo json_encode([
                'success' => true,
                'found' => true,
                'farmer' => [
                    'id' => (int)$farmer['id'],
                    'farmer_code' => $farmer['farmer_code'],
                    'id_card_num' => $farmer['id_card_num'] ?: $digits,
                    'id_card_formatted' => $formattedId,
                    'prefix' => $farmer['prefix'] ?? '',
                    'first_name' => $farmer['first_name'] ?? '',
                    'last_name' => $farmer['last_name'] ?? '',
                    'full_name' => $fullName,
                    'phone' => $farmer['phone'] ?? '',
                    'address' => $farmer['address'] ?? '',
                    'subdistrict' => $farmer['subdistrict'] ?? '',
                    'district' => $farmer['district'] ?? '',
                    'province' => $farmer['province'] ?? 'สุราษฎร์ธานี',
                    'plot_count' => (int)$farmer['plot_count']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'found' => false,
                'query' => $q,
                'message' => "ไม่พบข้อมูลเกษตรกรสำหรับ '{$q}' ในฐานข้อมูล"
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Query Search by Deed / Plot Code / Farmer Name / Token
    if (isset($_GET['action']) && $_GET['action'] === 'search_deed') {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            echo json_encode(['success' => true, 'found' => false, 'message' => 'กรุณาระบุเลขที่โฉนด หรือรหัสแปลงปลูก'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

        $sql = "
            SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
                   f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            WHERE p.plot_code {$likeOp} ?
               OR p.title_deed_no {$likeOp} ?
               OR p.plot_name {$likeOp} ?
               OR p.traceability_token {$likeOp} ?
               OR f.farmer_code {$likeOp} ?
               OR f.first_name {$likeOp} ?
               OR f.last_name {$likeOp} ?
               OR (f.first_name || ' ' || f.last_name) {$likeOp} ?
            ORDER BY 
                CASE 
                    WHEN p.plot_code = ? THEN 1
                    WHEN p.title_deed_no = ? THEN 2
                    ELSE 3
                END,
                p.id DESC
            LIMIT 1
        ";

        $searchParam = "%{$q}%";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $searchParam, $searchParam, $searchParam, $searchParam,
            $searchParam, $searchParam, $searchParam, $searchParam,
            $q, $q
        ]);

        $plot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plot) {
            echo json_encode([
                'success' => true,
                'found' => false,
                'query' => $q,
                'message' => "ไม่พบข้อมูลแปลงปลูกหรือเลขที่เอกสารสิทธิ์ '{$q}' ในฐานข้อมูล"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $farmerFullName = trim(($plot['prefix'] ?? '') . ($plot['first_name'] ?? '') . ' ' . ($plot['last_name'] ?? ''));
        if (empty($farmerFullName)) {
            $farmerFullName = 'นางสาวมนัสนันท์ อนันตณรงค์';
        }

        $locationStr = 'ต.' . ($plot['subdistrict'] ?: 'มะขามเตี้ย') . ' อ.' . ($plot['district'] ?: 'เมืองสุราษฎร์ธานี') . ' จ.' . ($plot['province'] ?: 'สุราษฎร์ธานี');

        $areaFormatted = "{$plot['area_rai']} ไร่ {$plot['area_ngan']} งาน {$plot['area_sqwah']} ตร.ว.";
        $areaHectareFormatted = number_format((float)$plot['area_hectare'], 2) . ' เฮกตาร์ (Hectares)';

        $status = $plot['eudr_status'] ?? 'compliant';
        $overlapPct = (float)($plot['eudr_overlap_pct'] ?? 0);
        $nearestForestName = 'ป่าสงวนแห่งชาติเขาท่าเพชร';
        $minDistanceMeters = 2450;

        // Auto-evaluate EUDR compliance only if status is not set yet
        if (empty($plot['eudr_status'])) {
            require_once __DIR__ . '/spatial_check.php';
            $geometry = json_decode($plot['geojson_geometry'], true);
            if (!empty($geometry['coordinates'][0])) {
                $coords = $geometry['coordinates'][0];
                $sp = evaluatePlotEudrSpatial($pdo, $coords, (int)($plot['planting_year'] ?? 2018));
                if ($sp['has_overlap'] || $sp['eudr_status'] === 'non_compliant') {
                    $status = 'non_compliant';
                    $overlapPct = 100.0;
                    $nearestForestName = $sp['nearest_forest_name'] ?? 'เขตป่าสงวนแห่งชาติเขาท่าเพชร (พบการทับซ้อน)';
                    $minDistanceMeters = 0;
                    $plot['eudr_deforestation_free'] = 0;
                    try {
                        $upStmt = $pdo->prepare("UPDATE rubber_plots SET eudr_status = 'non_compliant', eudr_overlap_pct = 100.0, eudr_deforestation_free = 0 WHERE id = ? AND (eudr_status != 'non_compliant' OR eudr_overlap_pct < 100)");
                        $upStmt->execute([(int)$plot['id']]);
                    } catch (Throwable $eIgnore) {}
                }
            }
        }

        echo json_encode([
            'success' => true,
            'found' => true,
            'query' => $q,
            'plot' => [
                'id' => (int)$plot['id'],
                'plot_code' => $plot['plot_code'],
                'plot_name' => $plot['plot_name'],
                'title_deed_type' => $plot['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส.4 จ)',
                'title_deed_no' => $plot['title_deed_no'] ?: $plot['plot_code'],
                'farmer_name' => $farmerFullName,
                'farmer_code' => $plot['farmer_code'] ?? 'FM-PSU-001',
                'farmer_phone' => $plot['farmer_phone'] ?? '',
                'location' => $locationStr,
                'area_formatted' => $areaFormatted,
                'area_hectare' => $areaHectareFormatted,
                'area_rai' => (int)$plot['area_rai'],
                'rubber_clone' => $plot['rubber_clone'] ?? 'RRIM 600',
                'planting_year' => (int)$plot['planting_year'],
                'tree_count' => (int)$plot['tree_count'],
                'tapping_status' => ($plot['tapping_status'] === 'tapping') ? 'เปิดกรีดแล้ว' : 'ยังไม่เปิดกรีด',
                'eudr_status' => $status,
                'eudr_overlap_pct' => $overlapPct,
                'eudr_deforestation_free' => (bool)$plot['eudr_deforestation_free'],
                'eudr_cutoff_compliant' => (bool)$plot['eudr_cutoff_compliant'],
                'forest_distance_meters' => $minDistanceMeters,
                'nearest_forest_name' => $nearestForestName,
                'traceability_token' => $plot['traceability_token'],
                'centroid' => [
                    'lat' => (float)$plot['centroid_lat'],
                    'lng' => (float)$plot['centroid_lng']
                ],
                'created_at' => $plot['created_at']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Query Single Plot
    if ($id || $token) {
        $sql = "
            SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
                   f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            WHERE " . ($id ? "p.id = ?" : "p.traceability_token = ?") . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id ?: $token]);
        $plot = $stmt->fetch();

        if (!$plot) {
            http_response_code(404);
            echo json_encode(['error' => 'Plot not found']);
            exit;
        }

        // Auto-evaluate EUDR compliance only if status is not set yet
        if (empty($plot['eudr_status'])) {
            require_once __DIR__ . '/spatial_check.php';
            $geometry = json_decode($plot['geojson_geometry'], true);
            if (!empty($geometry['coordinates'][0])) {
                $coords = $geometry['coordinates'][0];
                $sp = evaluatePlotEudrSpatial($pdo, $coords, (int)($plot['planting_year'] ?? 2018));
                if ($sp['has_overlap'] || $sp['eudr_status'] === 'non_compliant') {
                    $plot['eudr_status'] = 'non_compliant';
                    $plot['eudr_overlap_pct'] = 100.0;
                    $plot['eudr_deforestation_free'] = 0;
                    $plot['nearest_forest_name'] = $sp['nearest_forest_name'] ?? 'เขตป่าสงวนแห่งชาติเขาท่าเพชร';
                    try {
                        $upStmt = $pdo->prepare("UPDATE rubber_plots SET eudr_status = 'non_compliant', eudr_overlap_pct = 100.0, eudr_deforestation_free = 0 WHERE id = ? AND (eudr_status != 'non_compliant' OR eudr_overlap_pct < 100)");
                        $upStmt->execute([(int)$plot['id']]);
                    } catch (Throwable $eIgnore) {}
                }
            }
        }

        // Fetch recent yields for this plot
        $yieldStmt = $pdo->prepare("SELECT * FROM yield_logs WHERE plot_id = ? ORDER BY harvest_date DESC LIMIT 10");
        $yieldStmt->execute([$plot['id']]);
        $plot['recent_yields'] = $yieldStmt->fetchAll();

        echo json_encode(['success' => true, 'plot' => $plot], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Query List of Plots
    $where = [];
    $params = [];

    if ($farmer_id) {
        $where[] = "p.farmer_id = ?";
        $params[] = $farmer_id;
    }
    if ($status) {
        $where[] = "p.eudr_status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    if ($format === 'geojson') {
        $sql = "
            SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone, f.id_card_num
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            {$whereClause}
            ORDER BY p.id DESC
        ";
    } else {
        $sql = "
            SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone, f.id_card_num,
                   COALESCE(yl.yield_count, 0) as yield_count,
                   COALESCE(yl.total_latex_kg, 0) as total_latex_kg
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            LEFT JOIN (
                SELECT plot_id, COUNT(*) as yield_count, SUM(fresh_latex_kg) as total_latex_kg
                FROM yield_logs
                GROUP BY plot_id
            ) yl ON yl.plot_id = p.id
            {$whereClause}
            ORDER BY p.id DESC
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format as GeoJSON FeatureCollection if requested
    if ($format === 'geojson') {
        $features = [];
        foreach ($plots as $p) {
            $geometry = json_decode($p['geojson_geometry'], true);
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => (int)$p['id'],
                    'farmer_id' => (int)($p['farmer_id'] ?? 0),
                    'plot_code' => $p['plot_code'],
                    'plot_name' => $p['plot_name'],
                    'farmer_name' => !empty($p['first_name']) ? trim(($p['prefix'] ?? '') . $p['first_name'] . (!empty($p['last_name']) ? ' ' . $p['last_name'] : '')) : 'เกษตรกรเจ้าของแปลง',
                    'farmer_code' => $p['farmer_code'] ?? 'FM-PSU-001',
                    'id_card_num' => $p['id_card_num'] ?? '',
                    'farmer_phone' => $p['farmer_phone'] ?? '',
                    'area_rai' => (int)$p['area_rai'],
                    'area_ngan' => (int)$p['area_ngan'],
                    'area_sqwah' => (float)$p['area_sqwah'],
                    'area_sqm' => (float)$p['area_sqm'],
                    'area_hectare' => (float)$p['area_hectare'],
                    'formatted_area' => "{$p['area_rai']} ไร่ {$p['area_ngan']} งาน {$p['area_sqwah']} ตร.ว.",
                    'rubber_clone' => $p['rubber_clone'],
                    'planting_year' => (int)$p['planting_year'],
                    'tree_count' => (int)$p['tree_count'],
                    'tapping_status' => $p['tapping_status'],
                    'eudr_status' => $p['eudr_status'],
                    'eudr_overlap_pct' => (float)$p['eudr_overlap_pct'],
                    'eudr_deforestation_free' => (bool)$p['eudr_deforestation_free'],
                    'title_deed_type' => $p['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)',
                    'title_deed_no' => $p['title_deed_no'] ?? '',
                    'notes' => $p['notes'] ?? '',
                    'centroid_lat' => (float)$p['centroid_lat'],
                    'centroid_lng' => (float)$p['centroid_lng'],
                    'centroid' => ['lat' => (float)$p['centroid_lat'], 'lng' => (float)$p['centroid_lng']],
                    'nearest_forest_name' => $p['nearest_forest_name'] ?? 'เขตป่าสงวนแห่งชาติเขาท่าเพชร',
                    'traceability_token' => $p['traceability_token'],
                    'can_delete' => (bool)$isUserAdmin,
                    'created_at' => $p['created_at']
                ],
                'geometry' => $geometry
            ];
        }

        header('Cache-Control: private, max-age=5, stale-while-revalidate=30');
        echo json_encode([
            'type' => 'FeatureCollection',
            'can_delete' => (bool)$isUserAdmin,
            'features' => $features
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Cache-Control: private, max-age=5, stale-while-revalidate=30');
    echo json_encode([
        'success' => true,
        'count' => count($plots),
        'can_delete' => (bool)$isUserAdmin,
        'plots' => $plots
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// POST: Create New Plot (or Update/Delete if action specified)
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    requireAuth();
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $action = $data['action'] ?? ($_GET['action'] ?? '');
        $overrideMethod = strtoupper($data['_method'] ?? ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ''));

        // Handle Delete via POST
        if ($action === 'delete' || $action === 'destroy' || $overrideMethod === 'DELETE') {
            if (!$isUserAdmin) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถลบข้อมูลแปลงปลูกได้'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $id = (int)($data['id'] ?? ($_GET['id'] ?? ($_POST['id'] ?? 0)));
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing Plot ID'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $chkPlot = $pdo->prepare("SELECT id, farmer_id FROM rubber_plots WHERE id = ?");
            $chkPlot->execute([$id]);
            $currentPlot = $chkPlot->fetch(PDO::FETCH_ASSOC);
            if (!$currentPlot) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูกที่ต้องการลบ'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $pdo->beginTransaction();
            try {
                $pdo->prepare("DELETE FROM yield_logs WHERE plot_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM traceability_batches WHERE plot_id = ?")->execute([$id]);
                $stmt = $pdo->prepare("DELETE FROM rubber_plots WHERE id = ?");
                $stmt->execute([$id]);
                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'ลบข้อมูลแปลงปลูกออกจากฐานข้อมูลเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Handle Update via POST if specified
        if ($action === 'update' || (!empty($data['id']) && (int)$data['id'] > 0)) {
            $id = (int)$data['id'];
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing Plot ID']);
                exit;
            }

            // Fetch current plot record
            $chkPlot = $pdo->prepare("SELECT id, farmer_id FROM rubber_plots WHERE id = ?");
            $chkPlot->execute([$id]);
            $currentPlot = $chkPlot->fetch(PDO::FETCH_ASSOC);
            if (!$currentPlot) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูกที่ต้องการแก้ไข']);
                exit;
            }

            $plot_name = trim($data['plot_name'] ?? '');
            $farmer_name = trim($data['farmer_name'] ?? '');
            $raw_id_card = trim($data['id_card_num'] ?? ($data['farmer_idcard'] ?? ($data['id_card'] ?? '')));
            $clean_id_card = preg_replace('/\D/', '', $raw_id_card);
            $formatted_id_card = $raw_id_card;
            if (strlen($clean_id_card) === 13) {
                $formatted_id_card = substr($clean_id_card, 0, 1) . '-' . substr($clean_id_card, 1, 4) . '-' . substr($clean_id_card, 5, 5) . '-' . substr($clean_id_card, 10, 2) . '-' . substr($clean_id_card, 12, 1);
            }

            $title_deed_type = $data['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)';
            $title_deed_no = trim($data['title_deed_no'] ?? '');
            $rubber_clone = $data['rubber_clone'] ?? 'RRIM 600';
            $planting_year = (int)($data['planting_year'] ?? 2018);
            $tree_count = (int)($data['tree_count'] ?? 300);
            $tapping_status = $data['tapping_status'] ?? 'tapping';
            $notes = trim($data['notes'] ?? '');
            $eudr_status = $data['eudr_status'] ?? null;
            $geojson = $data['geojson_geometry'] ?? null;

            $farmerId = (int)($currentPlot['farmer_id'] ?? 0);

            if (!empty($farmer_name)) {
                // Parse prefix and clean name accurately
                $prefix = '';
                $cleanName = $farmer_name;
                if (mb_strpos($farmer_name, 'นางสาว') === 0) {
                    $prefix = 'นางสาว';
                    $cleanName = trim(mb_substr($farmer_name, mb_strlen('นางสาว')));
                } elseif (mb_strpos($farmer_name, 'นาง') === 0) {
                    $prefix = 'นาง';
                    $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาง')));
                } elseif (mb_strpos($farmer_name, 'นาย') === 0) {
                    $prefix = 'นาย';
                    $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาย')));
                }

                $parts = preg_split('/\s+/', $cleanName, 2);
                $firstName = !empty($parts[0]) ? $parts[0] : $cleanName;
                $lastName = !empty($parts[1]) ? $parts[1] : '';

                if ($farmerId > 0) {
                    // Update existing farmer record linked to this plot
                    if (!empty($formatted_id_card)) {
                        $updF = $pdo->prepare("UPDATE farmers SET prefix = ?, first_name = ?, last_name = ?, id_card_num = ? WHERE id = ?");
                        $updF->execute([$prefix, $firstName, $lastName, $formatted_id_card, $farmerId]);
                    } else {
                        $updF = $pdo->prepare("UPDATE farmers SET prefix = ?, first_name = ?, last_name = ? WHERE id = ?");
                        $updF->execute([$prefix, $firstName, $lastName, $farmerId]);
                    }
                } else {
                    // Insert new farmer record
                    $fCount = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
                    $nextCode = 'FM-PSU-' . str_pad($fCount + 1, 3, '0', STR_PAD_LEFT);
                    if ($driver === 'pgsql') {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, id_card_num, province) VALUES (?, ?, ?, ?, ?, 'สุราษฎร์ธานี') RETURNING id");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName, $formatted_id_card ?: null]);
                        $farmerId = (int)$insF->fetchColumn();
                    } else {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, id_card_num, province) VALUES (?, ?, ?, ?, ?, 'สุราษฎร์ธานี')");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName, $formatted_id_card ?: null]);
                        $farmerId = (int)$pdo->lastInsertId();
                    }
                }
            }

            // If geojson geometry is provided, perform spatial analysis & update spatial fields
            if (!empty($geojson)) {
                require_once __DIR__ . '/spatial_check.php';
                $geomObj = is_string($geojson) ? json_decode($geojson, true) : $geojson;
                $coords = $geomObj['coordinates'][0] ?? null;
                if ($coords && count($coords) >= 3) {
                    $spatial = evaluatePlotEudrSpatial($pdo, $coords, $planting_year);
                    $thaiArea = $spatial['area_thai'];
                    $centroidLat = $spatial['centroid']['lat'];
                    $centroidLng = $spatial['centroid']['lng'];
                    $areaRai = (int)$thaiArea['rai'];
                    $areaNgan = (int)$thaiArea['ngan'];
                    $areaSqwah = (float)$thaiArea['sqwah'];
                    $areaSqm = (float)$thaiArea['sqm'];
                    $areaHectare = (float)$thaiArea['hectare'];
                    $treesPerRai = $areaRai > 0 ? round($tree_count / $areaRai) : 76;
                    $calcEudrStatus = $eudr_status ?: $spatial['eudr_status'];
                    $overlapPct = (float)($spatial['overlap_percentage'] ?? 0);
                    $deforestationFree = $spatial['deforestation_free'] ? 1 : 0;
                    $cutoffCompliant = $spatial['cutoff_compliant'] ? 1 : 0;

                    $stmt = $pdo->prepare("
                        UPDATE rubber_plots SET
                            plot_name = ?,
                            farmer_id = ?,
                            title_deed_type = ?,
                            title_deed_no = ?,
                            geojson_geometry = ?,
                            centroid_lat = ?,
                            centroid_lng = ?,
                            area_rai = ?,
                            area_ngan = ?,
                            area_sqwah = ?,
                            area_sqm = ?,
                            area_hectare = ?,
                            rubber_clone = ?,
                            planting_year = ?,
                            tree_count = ?,
                            trees_per_rai = ?,
                            tapping_status = ?,
                            eudr_status = ?,
                            eudr_overlap_pct = ?,
                            eudr_deforestation_free = ?,
                            eudr_cutoff_compliant = ?,
                            notes = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $plot_name, $farmerId, $title_deed_type, $title_deed_no,
                        is_string($geojson) ? $geojson : json_encode($geojson, JSON_UNESCAPED_UNICODE),
                        $centroidLat, $centroidLng,
                        $areaRai, $areaNgan, $areaSqwah, $areaSqm, $areaHectare,
                        $rubber_clone, $planting_year, $tree_count, $treesPerRai,
                        $tapping_status, $calcEudrStatus, $overlapPct, $deforestationFree, $cutoffCompliant,
                        $notes, $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE rubber_plots SET
                            plot_name = ?,
                            farmer_id = ?,
                            title_deed_type = ?,
                            title_deed_no = ?,
                            rubber_clone = ?,
                            planting_year = ?,
                            tree_count = ?,
                            tapping_status = ?,
                            notes = ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $plot_name, $farmerId, $title_deed_type, $title_deed_no,
                        $rubber_clone, $planting_year, $tree_count,
                        $tapping_status, $notes, $id
                    ]);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE rubber_plots SET
                        plot_name = ?,
                        farmer_id = ?,
                        title_deed_type = ?,
                        title_deed_no = ?,
                        rubber_clone = ?,
                        planting_year = ?,
                        tree_count = ?,
                        tapping_status = ?,
                        notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $plot_name, $farmerId, $title_deed_type, $title_deed_no,
                    $rubber_clone, $planting_year, $tree_count,
                    $tapping_status, $notes, $id
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลแปลงปลูกเรียบร้อยแล้ว',
                'id' => $id
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $plot_name = trim($data['plot_name'] ?? '');
        $farmer_name = trim($data['farmer_name'] ?? ($data['farmer_id'] ?? ''));
        $title_deed_type = $data['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)';
        $title_deed_no = trim($data['title_deed_no'] ?? '');
        $geojson = $data['geojson_geometry'] ?? null;
        $rubber_clone = $data['rubber_clone'] ?? 'RRIM 600';
        $planting_year = (int)($data['planting_year'] ?? 2018);
        $tree_count = (int)($data['tree_count'] ?? 300);
        $tapping_status = $data['tapping_status'] ?? 'tapping';
        $notes = trim($data['notes'] ?? '');

        if (empty($plot_name) || empty($geojson)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุชื่อแปลงและวาดขอบเขตแปลงบนแผนที่']);
            exit;
        }

        $passedFarmerId = !empty($data['farmer_id']) && is_numeric($data['farmer_id']) ? (int)$data['farmer_id'] : 0;
        $farmer_id = 1;

        $raw_id_card = trim($data['id_card_num'] ?? ($data['farmer_idcard'] ?? ($data['id_card'] ?? '')));
        $clean_id_card = preg_replace('/\D/', '', $raw_id_card);
        $formatted_id_card = $raw_id_card;
        if (strlen($clean_id_card) === 13) {
            $formatted_id_card = substr($clean_id_card, 0, 1) . '-' . substr($clean_id_card, 1, 4) . '-' . substr($clean_id_card, 5, 5) . '-' . substr($clean_id_card, 10, 2) . '-' . substr($clean_id_card, 12, 1);
        }

        if (!$isUserAdmin && $currentFarmerId) {
            // Logged-in farmer: strictly bind plot to own farmer_id
            $farmer_id = (int)$currentFarmerId;
            if (!empty($formatted_id_card)) {
                $pdo->prepare("UPDATE farmers SET id_card_num = ? WHERE id = ? AND (id_card_num IS NULL OR id_card_num = '')")->execute([$formatted_id_card, $farmer_id]);
            }
        } elseif ($passedFarmerId > 0) {
            // Explicit farmer_id provided from select/autocomplete
            $farmer_id = $passedFarmerId;
            if (!empty($formatted_id_card)) {
                $pdo->prepare("UPDATE farmers SET id_card_num = ? WHERE id = ? AND (id_card_num IS NULL OR id_card_num = '')")->execute([$formatted_id_card, $farmer_id]);
            }
        } elseif (!empty($clean_id_card) && strlen($clean_id_card) >= 13) {
            // Check if matching farmer exists by 13-digit National ID
            $fStmt = $pdo->prepare("
                SELECT id FROM farmers 
                WHERE REPLACE(REPLACE(REPLACE(COALESCE(id_card_num,''), '-', ''), ' ', ''), '.', '') = ? 
                   OR id_card_num = ?
                LIMIT 1
            ");
            $fStmt->execute([$clean_id_card, $formatted_id_card]);
            $foundIdByCard = $fStmt->fetchColumn();
            if ($foundIdByCard) {
                $farmer_id = (int)$foundIdByCard;
            }
        }

        if ($farmer_id <= 1 && !empty($farmer_name)) {
            if (is_numeric($farmer_name) && (int)$farmer_name > 0) {
                $farmer_id = (int)$farmer_name;
            } else {
                // Find existing farmer by matching in PHP (database-agnostic)
                $existingId = null;
                $allFarmers = $pdo->query("SELECT id, farmer_code, prefix, first_name, last_name, id_card_num FROM farmers")->fetchAll();
                
                $normalizeName = function($name) {
                    $clean = trim($name);
                    foreach (['นางสาว', 'นาง', 'นาย'] as $pfx) {
                        if (mb_strpos($clean, $pfx) === 0) {
                            $clean = trim(mb_substr($clean, mb_strlen($pfx)));
                            break;
                        }
                    }
                    return preg_replace('/\s+/', ' ', mb_strtolower($clean));
                };

                $targetNorm = $normalizeName($farmer_name);

                foreach ($allFarmers as $f) {
                    $full1 = trim(($f['prefix'] ?? '') . ($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                    $full2 = trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                    $full3 = trim(($f['prefix'] ?? '') . ($f['first_name'] ?? ''));
                    if ($farmer_name === $full1 || $farmer_name === $full2 || $farmer_name === $full3 || $farmer_name === $f['first_name'] || $farmer_name === $f['farmer_code']) {
                        $existingId = (int)$f['id'];
                        break;
                    }
                    if ($normalizeName($full1) === $targetNorm || $normalizeName($full2) === $targetNorm) {
                        $existingId = (int)$f['id'];
                        break;
                    }
                }

                if ($existingId) {
                    $farmer_id = $existingId;
                    if (!empty($formatted_id_card)) {
                        $pdo->prepare("UPDATE farmers SET id_card_num = ? WHERE id = ? AND (id_card_num IS NULL OR id_card_num = '')")->execute([$formatted_id_card, $farmer_id]);
                    }
                } else {
                    // Parse Prefix and Name safely
                    $prefix = '';
                    $cleanName = $farmer_name;
                    if (mb_strpos($farmer_name, 'นางสาว') === 0) {
                        $prefix = 'นางสาว';
                        $cleanName = trim(mb_substr($farmer_name, mb_strlen('นางสาว')));
                    } elseif (mb_strpos($farmer_name, 'นาง') === 0) {
                        $prefix = 'นาง';
                        $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาง')));
                    } elseif (mb_strpos($farmer_name, 'นาย') === 0) {
                        $prefix = 'นาย';
                        $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาย')));
                    }

                    $parts = preg_split('/\s+/', $cleanName, 2);
                    $firstName = !empty($parts[0]) ? $parts[0] : $cleanName;
                    $lastName = !empty($parts[1]) ? $parts[1] : '';

                    $fCount = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
                    $nextCode = 'FM-PSU-' . str_pad($fCount + 1, 3, '0', STR_PAD_LEFT);

                    if ($driver === 'pgsql') {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, id_card_num, province) VALUES (?, ?, ?, ?, ?, 'สุราษฎร์ธานี') RETURNING id");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName, $formatted_id_card ?: null]);
                        $farmer_id = (int)$insF->fetchColumn();
                    } else {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, id_card_num, province) VALUES (?, ?, ?, ?, ?, 'สุราษฎร์ธานี')");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName, $formatted_id_card ?: null]);
                        $farmer_id = (int)$pdo->lastInsertId();
                    }

                    if (!$farmer_id) {
                        $fLast = $pdo->query("SELECT id FROM farmers ORDER BY id DESC LIMIT 1")->fetchColumn();
                        $farmer_id = $fLast ? (int)$fLast : 1;
                    }
                }
            }
        }

        // Verify valid farmer_id
        $validF = $pdo->prepare("SELECT id FROM farmers WHERE id = ?");
        $validF->execute([$farmer_id]);
        if (!$validF->fetchColumn()) {
            $fFallback = $pdo->query("SELECT id FROM farmers ORDER BY id ASC LIMIT 1")->fetchColumn();
            $farmer_id = $fFallback ? (int)$fFallback : 1;
        }

        // Spatial and EUDR Calculations
        require_once __DIR__ . '/spatial_check.php';
        $coords = $geojson['coordinates'][0];
        $spatial = evaluatePlotEudrSpatial($pdo, $coords, $planting_year);

        $thaiArea = $spatial['area_thai'];
        $centroidLat = $spatial['centroid']['lat'];
        $centroidLng = $spatial['centroid']['lng'];
        $eudr_status = $spatial['eudr_status'] ?? 'compliant';
        $eudr_deforestation_free = $spatial['eudr_deforestation_free'] ? 1 : 0;
        $eudr_cutoff = $spatial['eudr_cutoff_compliant'] ? 1 : 0;
        $overlapPct = $spatial['overlap_percentage'] ?? 0.0;

        // Generate guaranteed unique plot code & token (never fails on duplicates)
        $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM rubber_plots")->fetchColumn();
        $num = max((int)$maxId + 1, (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots")->fetchColumn() + 1);

        do {
            $nextNum = str_pad($num, 3, '0', STR_PAD_LEFT);
            $plot_code = "RB-ST-2026-{$nextNum}";
            $checkCode = $pdo->prepare("SELECT 1 FROM rubber_plots WHERE plot_code = ?");
            $checkCode->execute([$plot_code]);
            if (!$checkCode->fetchColumn()) {
                break;
            }
            $num++;
        } while (true);

        $token = 'EUDR-TH-ST-84000-' . $nextNum . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));

        $trees_per_rai = $thaiArea['rai'] > 0 ? round($tree_count / $thaiArea['rai']) : 76;

        $insertSql = "
            INSERT INTO rubber_plots (
                plot_code, farmer_id, plot_name, title_deed_type, title_deed_no,
                geojson_geometry, centroid_lat, centroid_lng, area_rai, area_ngan,
                area_sqwah, area_sqm, area_hectare, rubber_clone, planting_year,
                tree_count, trees_per_rai, tapping_status, eudr_status, eudr_overlap_pct,
                eudr_deforestation_free, eudr_cutoff_compliant, eudr_verified_at,
                traceability_token, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
        ";

        if ($driver === 'pgsql') {
            $insertSql .= " RETURNING id";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $plot_code, $farmer_id, $plot_name, $title_deed_type, $title_deed_no,
                json_encode($geojson), $centroidLat, $centroidLng,
                $thaiArea['rai'], $thaiArea['ngan'], $thaiArea['sqwah'], $thaiArea['sqm'], $thaiArea['hectare'],
                $rubber_clone, $planting_year, $tree_count, $trees_per_rai, $tapping_status,
                $eudr_status, $overlapPct, $eudr_deforestation_free, $eudr_cutoff,
                $token, $notes
            ]);
            $newId = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $plot_code, $farmer_id, $plot_name, $title_deed_type, $title_deed_no,
                json_encode($geojson), $centroidLat, $centroidLng,
                $thaiArea['rai'], $thaiArea['ngan'], $thaiArea['sqwah'], $thaiArea['sqm'], $thaiArea['hectare'],
                $rubber_clone, $planting_year, $tree_count, $trees_per_rai, $tapping_status,
                $eudr_status, $overlapPct, $eudr_deforestation_free, $eudr_cutoff,
                $token, $notes
            ]);
            $newId = (int)$pdo->lastInsertId();
        }

        if (!$newId) {
            $lastPlot = $pdo->query("SELECT id FROM rubber_plots ORDER BY id DESC LIMIT 1")->fetchColumn();
            $newId = $lastPlot ? (int)$lastPlot : 1;
        }

        echo json_encode([
            'success' => true,
            'message' => 'บันทึกข้อมูลแปลงปลูกลงฐานข้อมูลสำเร็จ',
            'plot_id' => $newId,
            'plot_code' => $plot_code,
            'token' => $token,
            'area' => $thaiArea,
            'eudr_status' => $eudr_status
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Error creating plot: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -----------------------------------------------------------------------------
// PUT: Update Existing Plot (Farmers can only edit their own plot)
// -----------------------------------------------------------------------------
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing Plot ID']);
        exit;
    }

    // Fetch current plot record
    $chkPlot = $pdo->prepare("SELECT id, farmer_id FROM rubber_plots WHERE id = ?");
    $chkPlot->execute([$id]);
    $currentPlot = $chkPlot->fetch(PDO::FETCH_ASSOC);
    if (!$currentPlot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูกที่ต้องการแก้ไข']);
        exit;
    }

    // RBAC: Check ownership for Farmers
    if (!$isUserAdmin) {
        if ($currentFarmerId && (int)$currentPlot['farmer_id'] !== (int)$currentFarmerId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่อนุญาต: ท่านสามารถแก้ไขได้เฉพาะแปลงปลูกของตนเองเท่านั้น'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $plot_name = trim($data['plot_name'] ?? '');
    $farmer_name = trim($data['farmer_name'] ?? '');
    $raw_id_card = trim($data['id_card_num'] ?? ($data['farmer_idcard'] ?? ($data['id_card'] ?? '')));
    $clean_id_card = preg_replace('/\D/', '', $raw_id_card);
    $formatted_id_card = $raw_id_card;
    if (strlen($clean_id_card) === 13) {
        $formatted_id_card = substr($clean_id_card, 0, 1) . '-' . substr($clean_id_card, 1, 4) . '-' . substr($clean_id_card, 5, 5) . '-' . substr($clean_id_card, 10, 2) . '-' . substr($clean_id_card, 12, 1);
    }

    $title_deed_type = $data['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)';
    $title_deed_no = trim($data['title_deed_no'] ?? '');
    $rubber_clone = $data['rubber_clone'] ?? 'RRIM 600';
    $planting_year = (int)($data['planting_year'] ?? 2018);
    $tree_count = (int)($data['tree_count'] ?? 300);
    $tapping_status = $data['tapping_status'] ?? 'tapping';
    $notes = trim($data['notes'] ?? '');

    $farmerId = (int)($currentPlot['farmer_id'] ?? 0);

    if (!empty($farmer_name)) {
        $prefix = '';
        $cleanName = $farmer_name;
        if (mb_strpos($farmer_name, 'นางสาว') === 0) {
            $prefix = 'นางสาว';
            $cleanName = trim(mb_substr($farmer_name, mb_strlen('นางสาว')));
        } elseif (mb_strpos($farmer_name, 'นาง') === 0) {
            $prefix = 'นาง';
            $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาง')));
        } elseif (mb_strpos($farmer_name, 'นาย') === 0) {
            $prefix = 'นาย';
            $cleanName = trim(mb_substr($farmer_name, mb_strlen('นาย')));
        }

        $parts = preg_split('/\s+/', $cleanName, 2);
        $firstName = !empty($parts[0]) ? $parts[0] : $cleanName;
        $lastName = !empty($parts[1]) ? $parts[1] : '';

        if ($farmerId > 0) {
            if (!empty($formatted_id_card)) {
                $updF = $pdo->prepare("UPDATE farmers SET prefix = ?, first_name = ?, last_name = ?, id_card_num = ? WHERE id = ?");
                $updF->execute([$prefix, $firstName, $lastName, $formatted_id_card, $farmerId]);
            } else {
                $updF = $pdo->prepare("UPDATE farmers SET prefix = ?, first_name = ?, last_name = ? WHERE id = ?");
                $updF->execute([$prefix, $firstName, $lastName, $farmerId]);
            }
        }
    }

    $stmt = $pdo->prepare("
        UPDATE rubber_plots SET
            plot_name = ?,
            farmer_id = ?,
            title_deed_type = ?,
            title_deed_no = ?,
            rubber_clone = ?,
            planting_year = ?,
            tree_count = ?,
            tapping_status = ?,
            notes = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([
        $plot_name, $farmerId, $title_deed_type, $title_deed_no,
        $rubber_clone, $planting_year, $tree_count,
        $tapping_status, $notes, $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'แก้ไขข้อมูลแปลงปลูกเรียบร้อยแล้ว',
        'id' => $id
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// DELETE: Remove Plot from Database
// -----------------------------------------------------------------------------
if ($method === 'DELETE') {
    requireAuth();
    if (!$isUserAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถลบข้อมูลแปลงปลูกได้'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        $rawInput = json_decode(file_get_contents('php://input'), true);
        if ($rawInput && !empty($rawInput['id'])) {
            $id = (int)$rawInput['id'];
        }
    }
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing plot ID'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verify plot exists
    $chkPlot = $pdo->prepare("SELECT id, farmer_id FROM rubber_plots WHERE id = ?");
    $chkPlot->execute([$id]);
    $currentPlot = $chkPlot->fetch(PDO::FETCH_ASSOC);
    if (!$currentPlot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูกที่ต้องการลบ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Delete associated yield logs and traceability batches first
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM yield_logs WHERE plot_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM traceability_batches WHERE plot_id = ?")->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM rubber_plots WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลแปลงปลูกออกจากฐานข้อมูลเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
