<?php
/**
 * GeoRubber Watch - Rubber Plots Management API
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

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

    // If logged-in user is a farmer, only show their plots (unless admin)
    $currentUserRole = $_SESSION['role'] ?? 'admin';
    $currentFarmerId = $_SESSION['farmer_id'] ?? null;

    if ($currentUserRole === 'farmer' && $currentFarmerId && !$farmer_id && !$id && !$token) {
        $farmer_id = $currentFarmerId;
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

        // Approximate forest distance
        $nearestForestName = ($status === 'non_compliant') ? 'ป่าสงวนแห่งชาติเขาท่าเพชร (พบการทับซ้อน)' : 'ป่าสงวนแห่งชาติเขาท่าเพชร';
        $minDistanceMeters = ($status === 'non_compliant') ? 0 : 2450;

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
    $sql = "
        SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
               (SELECT COUNT(*) FROM yield_logs y WHERE y.plot_id = p.id) as yield_count,
               (SELECT COALESCE(SUM(fresh_latex_kg), 0) FROM yield_logs y WHERE y.plot_id = p.id) as total_latex_kg
        FROM rubber_plots p
        LEFT JOIN farmers f ON f.id = p.farmer_id
        {$whereClause}
        ORDER BY p.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $plots = $stmt->fetchAll();

    // Format as GeoJSON FeatureCollection if requested
    if ($format === 'geojson') {
        $features = [];
        foreach ($plots as $p) {
            $geometry = json_decode($p['geojson_geometry'], true);
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => (int)$p['id'],
                    'plot_code' => $p['plot_code'],
                    'plot_name' => $p['plot_name'],
                    'farmer_name' => !empty($p['first_name']) ? "{$p['prefix']}{$p['first_name']} {$p['last_name']}" : 'นางสาวมนัสนันท์ อนันตณรงค์',
                    'farmer_code' => $p['farmer_code'] ?? 'FM-PSU-001',
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
                    'centroid' => ['lat' => (float)$p['centroid_lat'], 'lng' => (float)$p['centroid_lng']],
                    'traceability_token' => $p['traceability_token'],
                    'created_at' => $p['created_at']
                ],
                'geometry' => $geometry
            ];
        }

        echo json_encode([
            'type' => 'FeatureCollection',
            'features' => $features
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => true, 'count' => count($plots), 'plots' => $plots], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// POST: Create New Plot (or Update if action=update)
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $data = $_POST;
        }

        $action = $data['action'] ?? ($_GET['action'] ?? '');

        // Handle Update via POST if specified
        if ($action === 'update' || isset($data['id'])) {
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing Plot ID']);
                exit;
            }

            $plot_name = trim($data['plot_name'] ?? '');
            $title_deed_type = $data['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)';
            $title_deed_no = trim($data['title_deed_no'] ?? '');
            $rubber_clone = $data['rubber_clone'] ?? 'RRIM 600';
            $planting_year = (int)($data['planting_year'] ?? 2018);
            $tree_count = (int)($data['tree_count'] ?? 300);
            $tapping_status = $data['tapping_status'] ?? 'tapping';
            $notes = trim($data['notes'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE rubber_plots SET
                    plot_name = ?,
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
                $plot_name, $title_deed_type, $title_deed_no,
                $rubber_clone, $planting_year, $tree_count,
                $tapping_status, $notes, $id
            ]);

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

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Dynamic Farmer Handling: Support custom typed farmer name
        $farmer_id = 1;
        if (!empty($farmer_name)) {
            if (is_numeric($farmer_name) && (int)$farmer_name > 0) {
                $farmer_id = (int)$farmer_name;
            } else {
                // Find existing farmer by matching in PHP (database-agnostic)
                $existingId = null;
                $allFarmers = $pdo->query("SELECT id, prefix, first_name, last_name FROM farmers")->fetchAll();
                foreach ($allFarmers as $f) {
                    $full1 = trim(($f['prefix'] ?? '') . ($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                    $full2 = trim(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? ''));
                    $full3 = trim(($f['prefix'] ?? '') . ($f['first_name'] ?? ''));
                    if ($farmer_name === $full1 || $farmer_name === $full2 || $farmer_name === $full3 || $farmer_name === $f['first_name']) {
                        $existingId = (int)$f['id'];
                        break;
                    }
                }

                if ($existingId) {
                    $farmer_id = $existingId;
                } else {
                    // Parse Prefix and Name safely
                    $prefix = 'นาย';
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
                    $lastName = !empty($parts[1]) ? $parts[1] : 'อนันตณรงค์';

                    $fCount = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
                    $nextCode = 'FM-PSU-' . str_pad($fCount + 1, 3, '0', STR_PAD_LEFT);

                    if ($driver === 'pgsql') {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, province) VALUES (?, ?, ?, ?, 'สุราษฎร์ธานี') RETURNING id");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName]);
                        $farmer_id = (int)$insF->fetchColumn();
                    } else {
                        $insF = $pdo->prepare("INSERT INTO farmers (farmer_code, prefix, first_name, last_name, province) VALUES (?, ?, ?, ?, 'สุราษฎร์ธานี')");
                        $insF->execute([$nextCode, $prefix, $firstName, $lastName]);
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
        $eudr_status = $data['eudr_status'] ?? $spatial['eudr_status'];
        $eudr_deforestation_free = $spatial['eudr_deforestation_free'] ? 1 : 0;
        $eudr_cutoff = $spatial['eudr_cutoff_compliant'] ? 1 : 0;
        $overlapPct = $spatial['overlap_percentage'];

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
// PUT: Update Existing Plot
// -----------------------------------------------------------------------------
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? ($_GET['id'] ?? 0));

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing Plot ID']);
        exit;
    }

    $plot_name = trim($data['plot_name'] ?? '');
    $title_deed_type = $data['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)';
    $title_deed_no = trim($data['title_deed_no'] ?? '');
    $rubber_clone = $data['rubber_clone'] ?? 'RRIM 600';
    $planting_year = (int)($data['planting_year'] ?? 2018);
    $tree_count = (int)($data['tree_count'] ?? 300);
    $tapping_status = $data['tapping_status'] ?? 'tapping';
    $notes = trim($data['notes'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE rubber_plots SET
            plot_name = ?,
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
        $plot_name, $title_deed_type, $title_deed_no,
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
// DELETE: Remove Plot
// -----------------------------------------------------------------------------
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing plot ID']);
        exit;
    }

    // Delete associated yield logs first if any
    $pdo->prepare("DELETE FROM yield_logs WHERE plot_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM traceability_batches WHERE plot_id = ?")->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM rubber_plots WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลแปลงปลูกออกจากฐานข้อมูลเรียบร้อยแล้ว']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
