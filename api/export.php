<?php
/**
 * GeoRubber Watch - Data Export API (GeoJSON, CSV)
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
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

$type = $_GET['type'] ?? 'geojson'; // 'geojson' | 'plots_csv' | 'yields_csv'

// 1. Export GeoJSON
if ($type === 'geojson') {
    header('Content-Type: application/geo+json; charset=utf-8');
    header('Content-Disposition: attachment; filename="georubber_plots_' . date('Ymd_His') . '.geojson"');

    $stmt = $pdo->query("
        SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone
        FROM rubber_plots p
        JOIN farmers f ON f.id = p.farmer_id
        ORDER BY p.id ASC
    ");
    $plots = $stmt->fetchAll();

    $features = [];
    foreach ($plots as $p) {
        $geometry = json_decode($p['geojson_geometry'], true);
        $features[] = [
            'type' => 'Feature',
            'properties' => [
                'plot_code' => $p['plot_code'],
                'plot_name' => $p['plot_name'],
                'farmer_name' => "{$p['prefix']}{$p['first_name']} {$p['last_name']}",
                'farmer_code' => $p['farmer_code'],
                'title_deed' => $p['title_deed_type'] . ' ' . $p['title_deed_no'],
                'area_rai' => (int)$p['area_rai'],
                'area_ngan' => (int)$p['area_ngan'],
                'area_sqwah' => (float)$p['area_sqwah'],
                'area_hectare' => (float)$p['area_hectare'],
                'rubber_clone' => $p['rubber_clone'],
                'planting_year' => (int)$p['planting_year'],
                'tree_count' => (int)$p['tree_count'],
                'tapping_status' => $p['tapping_status'],
                'eudr_status' => $p['eudr_status'],
                'eudr_overlap_pct' => (float)$p['eudr_overlap_pct'],
                'eudr_deforestation_free' => (bool)$p['eudr_deforestation_free'],
                'traceability_token' => $p['traceability_token'],
                'centroid_lat' => (float)$p['centroid_lat'],
                'centroid_lng' => (float)$p['centroid_lng']
            ],
            'geometry' => $geometry
        ];
    }

    echo json_encode([
        'type' => 'FeatureCollection',
        'name' => 'GeoRubber_Watch_Export_SuratThani',
        'crs' => [
            'type' => 'name',
            'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']
        ],
        'features' => $features
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Export Plots CSV
if ($type === 'plots_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="georubber_plots_' . date('Ymd') . '.csv"');
    
    // Add UTF-8 BOM for Thai text in Excel
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'รหัสแปลง', 'ชื่อแปลง', 'รหัสเกษตรกร', 'ชื่อ-สกุลเกษตรกร', 'เบอร์โทร',
        'เอกสารสิทธิ์', 'เลขที่เอกสารสิทธิ์', 'เนื้อที่ (ไร่)', 'เนื้อที่ (งาน)',
        'เนื้อที่ (ตร.ว.)', 'เนื้อที่ (เฮกตาร์)', 'พันธุ์ยาง', 'ปีที่ปลูก',
        'จำนวนต้น', 'สถานะการกรีด', 'สถานะ EUDR', '% ทับซ้อนป่า', 'ละติจูด', 'ลองจิจูด', 'Traceability Token'
    ]);

    $stmt = $pdo->query("
        SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone
        FROM rubber_plots p
        JOIN farmers f ON f.id = p.farmer_id
        ORDER BY p.id ASC
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['plot_code'],
            $row['plot_name'],
            $row['farmer_code'],
            $row['prefix'] . $row['first_name'] . ' ' . $row['last_name'],
            $row['farmer_phone'],
            $row['title_deed_type'],
            $row['title_deed_no'],
            $row['area_rai'],
            $row['area_ngan'],
            $row['area_sqwah'],
            $row['area_hectare'],
            $row['rubber_clone'],
            $row['planting_year'],
            $row['tree_count'],
            $row['tapping_status'] === 'tapping' ? 'เปิดกรีดแล้ว' : 'ยังไม่เปิดกรีด',
            $row['eudr_status'],
            $row['eudr_overlap_pct'] . '%',
            $row['centroid_lat'],
            $row['centroid_lng'],
            $row['traceability_token']
        ]);
    }
    fclose($output);
    exit;
}

// 3. Export Yields CSV (Filtered by Role and Selected Plot)
if ($type === 'yields_csv') {
    $plotId = isset($_GET['plot_id']) && (int)$_GET['plot_id'] > 0 ? (int)$_GET['plot_id'] : null;
    $filenameSuffix = $plotId ? "_plot{$plotId}_" : "_";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="georubber_yields' . $filenameSuffix . date('Ymd_His') . '.csv"');
    
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'ลำดับ', 'วันที่รับซื้อ/บันทึก', 'รหัสแปลงปลูก', 'ชื่อแปลงปลูก', 'เอกสารสิทธิ์', 'เลขที่เอกสารสิทธิ์',
        'ชื่อ-สกุลเกษตรกร', 'เลขบัตรประชาชน', 'ผู้รับซื้อ/โรงงาน', 'รอบกรีดที่',
        'น้ำหนักน้ำยางสด (กก.)', '% DRC (ยางแห้ง)', 'เนื้อยางแห้ง (กก.)', 'ราคารับซื้อต่อ กก. (บาท)',
        'ยอดรวมสุทธิ (บาท)', 'รหัส EUDR Traceability Token', 'รหัสล็อต DDS', 'หมายเหตุ'
    ]);

    $where = [];
    $params = [];

    if (!$isUserAdmin) {
        $where[] = "(y.farmer_id = ? OR p.farmer_id = ?)";
        $params[] = $farmerId ?: -1;
        $params[] = $farmerId ?: -1;
    }

    if ($plotId) {
        $where[] = "y.plot_id = ?";
        $params[] = $plotId;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "
        SELECT y.*, p.plot_code, p.plot_name, p.title_deed_type, p.title_deed_no, p.traceability_token as plot_token,
               f.prefix, f.first_name, f.last_name, f.id_card_num
        FROM yield_logs y
        LEFT JOIN rubber_plots p ON p.id = y.plot_id
        LEFT JOIN farmers f ON f.id = y.farmer_id
        {$whereSql}
        ORDER BY y.harvest_date DESC, y.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $i = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $yId = (int)$row['id'];
        $pId = (int)$row['plot_id'];
        $hDate = $row['harvest_date'];
        $traceToken = !empty($row['traceability_token']) ? $row['traceability_token'] : ('EUDR-TX-' . strtoupper(substr(md5($yId . $pId . $hDate), 0, 8)));
        $batchCode = !empty($row['batch_code']) ? $row['batch_code'] : ('DDS-TH-ST-' . date('Ymd', strtotime($hDate)) . '-' . str_pad((string)$yId, 4, '0', STR_PAD_LEFT));

        fputcsv($output, [
            $i++,
            $row['harvest_date'],
            $row['plot_code'],
            $row['plot_name'],
            $row['title_deed_type'] ?: 'โฉนดที่ดิน',
            $row['title_deed_no'] ?: '-',
            $row['prefix'] . $row['first_name'] . ' ' . $row['last_name'],
            $row['id_card_num'] ?: '-',
            $row['buyer_name'] ?: 'จุดรับซื้อน้ำยางสด',
            $row['tapping_round'] ?: 1,
            $row['fresh_latex_kg'],
            $row['drc_percent'] . '%',
            $row['dry_rubber_kg'],
            $row['price_per_kg'],
            $row['total_revenue'],
            $traceToken,
            $batchCode,
            $row['notes'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}
