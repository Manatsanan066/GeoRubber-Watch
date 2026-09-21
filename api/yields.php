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
    if (!$farmerId && !empty($_SESSION['id_card_num'])) {
        $fStmt2 = $pdo->prepare("SELECT id FROM farmers WHERE id_card_num = ?");
        $fStmt2->execute([$_SESSION['id_card_num']]);
        $farmerId = (int)$fStmt2->fetchColumn();
    }
    if (!$farmerId && !empty($_SESSION['phone'])) {
        $fStmt3 = $pdo->prepare("SELECT id FROM farmers WHERE phone = ?");
        $fStmt3->execute([$_SESSION['phone']]);
        $farmerId = (int)$fStmt3->fetchColumn();
    }
    if ($farmerId) {
        $_SESSION['farmer_id'] = $farmerId;
    }
}

// -----------------------------------------------------------------------------
// GET: Fetch Yield Logs or Plot Dropdown Options
// -----------------------------------------------------------------------------
if ($method === 'GET') {
    try {
        $action = $_GET['action'] ?? '';

        // 1. Single Yield Record Query (For Edit Modal)
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("
                SELECT y.*, p.plot_code, p.plot_name, p.rubber_clone, p.title_deed_no, p.area_rai,
                       f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num
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

        // 2. Anti-Fraud Biological Capacity & Monthly Quota Check (Real-time Evaluation)
        if ($action === 'check_quota') {
            $plot_id = (int)($_GET['plot_id'] ?? 0);
            $fresh_kg = (float)($_GET['fresh_kg'] ?? 0);
            $harvest_date = $_GET['harvest_date'] ?? date('Y-m-d');

            if ($plot_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Missing plot_id']);
                exit;
            }

            $pStmt = $pdo->prepare("
                SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                WHERE p.id = ?
            ");
            $pStmt->execute([$plot_id]);
            $plot = $pStmt->fetch(PDO::FETCH_ASSOC);
            if (!$plot) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลง']);
                exit;
            }

            $areaRai = max(0.5, (float)$plot['area_rai']);
            $treeCount = !empty($plot['tree_count']) ? (int)$plot['tree_count'] : (int)round($areaRai * 75);
            $firstDay = date('Y-m-01', strtotime($harvest_date));
            $lastDay = date('Y-m-t', strtotime($harvest_date));

            $ySumStmt = $pdo->prepare("
                SELECT COALESCE(SUM(fresh_latex_kg), 0) as month_sum, COUNT(*) as round_count
                FROM yield_logs
                WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?
            ");
            $ySumStmt->execute([$plot_id, $firstDay, $lastDay]);
            $yRow = $ySumStmt->fetch(PDO::FETCH_ASSOC);
            $currentMonthSum = (float)($yRow['month_sum'] ?? 0);
            $roundCount = (int)($yRow['round_count'] ?? 0);

            // Biological thresholds:
            // Monthly max: 4.5 kg/tree/month (200 trees = 900 kg/month)
            // Daily max: 0.35 kg/tree/round (200 trees = 70 kg/round)
            $maxMonthlyCapacity = round($treeCount * 4.5, 2);
            $maxDailyCapacity = round($treeCount * 0.35, 2);

            $newMonthSum = $currentMonthSum + $fresh_kg;
            $newMonthPerTree = $treeCount > 0 ? round($newMonthSum / $treeCount, 2) : 0;
            $currentMonthPerTree = $treeCount > 0 ? round($currentMonthSum / $treeCount, 2) : 0;
            $currentRoundPerTree = $treeCount > 0 ? round($fresh_kg / $treeCount, 2) : 0;

            $isDailyExceeded = ($fresh_kg > 0 && $fresh_kg > $maxDailyCapacity);
            $isMonthlyExceeded = ($newMonthSum > $maxMonthlyCapacity && $newMonthSum > 0);
            $overflowMonthKg = round(max(0, $newMonthSum - $maxMonthlyCapacity), 1);

            // Fetch previous round on same plot for surge comparison
            $prevStmt = $pdo->prepare("
                SELECT id, harvest_date, fresh_latex_kg, tapping_round, drc_percent
                FROM yield_logs
                WHERE plot_id = ? AND harvest_date <= ?
                ORDER BY harvest_date DESC, id DESC
                LIMIT 1
            ");
            $prevStmt->execute([$plot_id, $harvest_date]);
            $prevRow = $prevStmt->fetch(PDO::FETCH_ASSOC);

            $prevKg = 0.0;
            $diffDays = 2;
            $surgePct = 0.0;
            $prevPerTree = 0.0;

            if ($prevRow) {
                $prevKg = (float)$prevRow['fresh_latex_kg'];
                $prevPerTree = $treeCount > 0 ? round($prevKg / $treeCount, 2) : 0.0;
                $prevHarvestDate = $prevRow['harvest_date'];
                $diffDays = max(1, (int)round((strtotime($harvest_date) - strtotime($prevHarvestDate)) / 86400));
                if ($prevKg > 0 && $fresh_kg > 0) {
                    $surgePct = round((($fresh_kg - $prevKg) / $prevKg) * 100, 1);
                }
            }

            // Anomaly surge detection: >= 80% spike compared to previous round
            $isSurge = ($prevKg > 0 && $surgePct >= 80.0 && $fresh_kg > 35.0);

            $reasons = [];
            if ($isMonthlyExceeded) {
                $reasons[] = "หากบันทึกยอดนี้ ผลผลิตสะสมเดือนนี้ ({$newMonthSum} กก.) จะเกินเพดานชีวภาพของแปลง ({$maxMonthlyCapacity} กก./เดือน สำหรับ {$treeCount} ต้น) โดยเกินลิมิตไป +{$overflowMonthKg} กก. เสี่ยงต่อการสวมสิทธิ์";
            }
            if ($isDailyExceeded) {
                $reasons[] = "ผลผลิตต่อวัน ({$fresh_kg} กก.) เกินเกณฑ์ชีวภาพสูงสุดที่ต้นยาง {$treeCount} ต้นจะผลิตได้ (ปกติไม่เกิน {$maxDailyCapacity} กก./วัน หรือ 0.35 กก./ต้น/วัน)";
            }
            if ($isSurge) {
                $reasons[] = "ปริมาณเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติของรอบกรีด (+{$surgePct}%) เทียบกับ {$diffDays} วันก่อน ({$prevKg} กก.) เสี่ยงต่อการสวมสิทธิ์ นำยางนอกแปลงมาขาย หรือเกิดข้อผิดพลาดในการบันทึกตัวเลข";
            }

            $isAnomaly = ($isMonthlyExceeded || $isDailyExceeded || $isSurge);

            $rStmt = $pdo->prepare("SELECT COALESCE(MAX(tapping_round), 0) as max_round, COUNT(*) as round_count FROM yield_logs WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?");
            $rStmt->execute([$plot_id, $firstDay, $lastDay]);
            $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
            $nextTappingRound = max((int)($rData['max_round'] ?? 0), (int)($rData['round_count'] ?? 0)) + 1;

            echo json_encode([
                'success' => true,
                'plot_id' => $plot_id,
                'plot_name' => $plot['plot_name'],
                'plot_code' => $plot['plot_code'],
                'farmer_name' => trim(($plot['prefix'] ?? '') . ($plot['first_name'] ?? '') . ' ' . ($plot['last_name'] ?? '')),
                'area_rai' => $areaRai,
                'tree_count' => $treeCount,
                'max_monthly_capacity' => $maxMonthlyCapacity,
                'max_daily_capacity' => $maxDailyCapacity,
                'current_month_sum' => $currentMonthSum,
                'new_month_sum' => $newMonthSum,
                'current_month_per_tree' => $currentMonthPerTree,
                'new_month_per_tree' => $newMonthPerTree,
                'overflow_month_kg' => $overflowMonthKg,
                'is_daily_exceeded' => $isDailyExceeded,
                'is_monthly_exceeded' => $isMonthlyExceeded,
                'is_surge' => $isSurge,
                'prev_kg' => $prevKg,
                'prev_per_tree' => $prevPerTree,
                'diff_days' => $diffDays,
                'surge_pct' => $surgePct,
                'is_anomaly' => $isAnomaly,
                'risk_level' => $isAnomaly ? 'high_risk' : 'normal',
                'reasons' => $reasons,
                'next_tapping_round' => $nextTappingRound
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. QR Scan Plot Lookup (Fast Single Query)
        if ($action === 'lookup_plot_by_token') {
            $token = trim($_GET['token'] ?? '');
            if (empty($token)) {
                echo json_encode(['success' => false, 'message' => 'Missing token']);
                exit;
            }

            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

            $tokenClean = preg_replace('/^#/', '', $token);
            $tokenClean = trim($tokenClean);

            $stmt = $pdo->prepare("
                SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num, f.phone,
                       f.subdistrict, f.district, f.province
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                WHERE p.traceability_token {$likeOp} :t1 
                   OR p.plot_code {$likeOp} :t2
                   OR f.farmer_code {$likeOp} :t3
                   OR p.id = :t_id
                LIMIT 1
            ");
            $testId = is_numeric($tokenClean) ? (int)$tokenClean : -1;
            $stmt->execute([
                ':t1' => $tokenClean,
                ':t2' => $tokenClean,
                ':t3' => $tokenClean,
                ':t_id' => $testId
            ]);
            $plot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plot) {
                echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลแปลงปลูกจาก QR Code / รหัสนี้']);
                exit;
            }

            $areaRai = max(0.5, (float)$plot['area_rai']);
            $treeCount = !empty($plot['tree_count']) ? (int)$plot['tree_count'] : (int)round($areaRai * 75);
            $firstDay = date('Y-m-01');
            $lastDay = date('Y-m-t');

            $ySumStmt = $pdo->prepare("
                SELECT COALESCE(SUM(fresh_latex_kg), 0) as month_sum, COUNT(*) as round_count
                FROM yield_logs
                WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?
            ");
            $ySumStmt->execute([$plot['id'], $firstDay, $lastDay]);
            $yRow = $ySumStmt->fetch(PDO::FETCH_ASSOC);
            $currentMonthSum = (float)($yRow['month_sum'] ?? 0);
            $roundCount = (int)($yRow['round_count'] ?? 0);

            $rStmt = $pdo->prepare("SELECT COALESCE(MAX(tapping_round), 0) as max_round, COUNT(*) as round_count FROM yield_logs WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?");
            $rStmt->execute([$plot['id'], $firstDay, $lastDay]);
            $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
            $nextTappingRound = max((int)($rData['max_round'] ?? 0), (int)($rData['round_count'] ?? 0)) + 1;

            $maxMonthlyCapacity = round($treeCount * 4.5, 2);
            $maxDailyCapacity = round($treeCount * 0.35, 2);

            echo json_encode([
                'success' => true,
                'plot' => [
                    'id' => (int)$plot['id'],
                    'plot_code' => $plot['plot_code'],
                    'plot_name' => $plot['plot_name'],
                    'rubber_clone' => $plot['rubber_clone'] ?: 'RRIM 600',
                    'area_rai' => $areaRai,
                    'tree_count' => $treeCount,
                    'eudr_status' => $plot['eudr_status'] ?: 'compliant',
                    'title_deed_no' => $plot['title_deed_no'] ?: '-',
                    'title_deed_type' => $plot['title_deed_type'] ?: 'โฉนดที่ดิน',
                    'centroid_lat' => $plot['centroid_lat'] ? (float)$plot['centroid_lat'] : null,
                    'centroid_lng' => $plot['centroid_lng'] ? (float)$plot['centroid_lng'] : null,
                    'traceability_token' => $plot['traceability_token'] ?: ('EUDR-PL-' . str_pad($plot['id'], 5, '0', STR_PAD_LEFT)),
                    'farmer_id' => (int)$plot['farmer_id'],
                    'farmer_name' => trim(($plot['prefix'] ?? '') . ($plot['first_name'] ?? '') . ' ' . ($plot['last_name'] ?? '')),
                    'farmer_code' => $plot['farmer_code'] ?: '-',
                    'id_card_num' => $plot['id_card_num'] ?: '-',
                    'phone' => $plot['phone'] ?: '-',
                    'current_month_sum' => $currentMonthSum,
                    'round_count' => $roundCount,
                    'max_monthly_capacity' => $maxMonthlyCapacity,
                    'max_daily_capacity' => $maxDailyCapacity,
                    'next_tapping_round' => $nextTappingRound
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3.5. Search Plot for Factory Purchasing / Live Search
        if ($action === 'search_plot_for_purchasing') {
            $q = trim($_GET['q'] ?? '');
            if ($q === '') {
                echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสแปลง หรือเลขบัตรประชาชน']);
                exit;
            }
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';

            $cleanId = preg_replace('/[^0-9]/', '', $q);

            $sql = "
                SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num, f.phone,
                       f.subdistrict as f_subdistrict, f.district as f_district, f.province as f_province
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                WHERE p.plot_code {$likeOp} ?
                   OR p.traceability_token {$likeOp} ?
                   OR p.title_deed_no {$likeOp} ?
                   OR p.plot_name {$likeOp} ?
                   OR f.id_card_num {$likeOp} ?
                   OR f.farmer_code {$likeOp} ?
                   OR f.phone {$likeOp} ?
                   OR (f.first_name || ' ' || f.last_name) {$likeOp} ?
                ORDER BY p.id DESC
                LIMIT 1
            ";
            $searchP = "%{$q}%";
            $searchIdP = "%{$cleanId}%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$searchP, $searchP, $searchP, $searchP, $searchIdP, $searchP, $searchP, $searchP]);
            $plot = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plot) {
                echo json_encode(['success' => false, 'message' => "ไม่พบข้อมูลแปลงปลูกหรือเกษตรกรที่ค้นหา '{$q}'"]);
                exit;
            }

            $areaRai = max(0.5, (float)$plot['area_rai']);
            $treeCount = !empty($plot['tree_count']) ? (int)$plot['tree_count'] : (int)round($areaRai * 75);
            $firstDay = date('Y-m-01');
            $lastDay = date('Y-m-t');

            $ySumStmt = $pdo->prepare("
                SELECT COALESCE(SUM(fresh_latex_kg), 0) as month_sum, COUNT(*) as round_count
                FROM yield_logs
                WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?
            ");
            $ySumStmt->execute([$plot['id'], $firstDay, $lastDay]);
            $yRow = $ySumStmt->fetch(PDO::FETCH_ASSOC);
            $currentMonthSum = (float)($yRow['month_sum'] ?? 0);
            $roundCount = (int)($yRow['round_count'] ?? 0);

            $rStmt = $pdo->prepare("SELECT COALESCE(MAX(tapping_round), 0) as max_round, COUNT(*) as round_count FROM yield_logs WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?");
            $rStmt->execute([$plot['id'], $firstDay, $lastDay]);
            $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
            $nextTappingRound = max((int)($rData['max_round'] ?? 0), (int)($rData['round_count'] ?? 0)) + 1;

            $maxMonthlyCapacity = round($treeCount * 4.5, 2);
            $remainingQuota = max(0.0, $maxMonthlyCapacity - $currentMonthSum);
            $capacityPct = $maxMonthlyCapacity > 0 ? round(($currentMonthSum / $maxMonthlyCapacity) * 100, 1) : 0;

            echo json_encode([
                'success' => true,
                'plot' => $plot,
                'stats' => [
                    'area_rai' => $areaRai,
                    'tree_count' => $treeCount,
                    'current_month_sum' => $currentMonthSum,
                    'max_monthly_capacity' => $maxMonthlyCapacity,
                    'remaining_quota' => $remainingQuota,
                    'capacity_pct' => $capacityPct,
                    'round_count' => $roundCount,
                    'next_tapping_round' => $nextTappingRound
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 4. Dropdown Plots Query
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

        // 5. Query Yield Logs
        $plot_id = $_GET['plot_id'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
        if ($limit <= 0 || (isset($_GET['limit']) && $_GET['limit'] === 'all')) {
            $limit = 10000;
        }

        $where = [];
        $params = [];

        // RBAC: Farmers only view their own yield history
        if (!$isUserAdmin) {
            $where[] = "(y.farmer_id = :farmer_id OR p.farmer_id = :farmer_id)";
            $params[':farmer_id'] = $farmerId ?: -1;
        } else {
            // Admin Search & Filter Options
            if (!empty($_GET['search_name'])) {
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
                $where[] = "(f.first_name {$likeOp} :s_name OR f.last_name {$likeOp} :s_name OR (f.first_name || ' ' || f.last_name) {$likeOp} :s_name OR f.farmer_code {$likeOp} :s_name OR f.id_card_num {$likeOp} :s_name)";
                $params[':s_name'] = '%' . trim($_GET['search_name']) . '%';
            }
            if (!empty($_GET['search_plot_code'])) {
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
                $where[] = "(p.plot_code {$likeOp} :s_code OR p.plot_name {$likeOp} :s_code OR p.traceability_token {$likeOp} :s_code)";
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
            WITH ranked_logs AS (
                SELECT y.*, 
                       LAG(y.fresh_latex_kg) OVER (PARTITION BY y.plot_id ORDER BY y.harvest_date ASC, y.id ASC) AS prev_fresh_kg,
                       LAG(y.harvest_date) OVER (PARTITION BY y.plot_id ORDER BY y.harvest_date ASC, y.id ASC) AS prev_harvest_date,
                       SUM(y.fresh_latex_kg) OVER (PARTITION BY y.plot_id, SUBSTR(CAST(y.harvest_date AS TEXT), 1, 7) ORDER BY y.harvest_date ASC, y.id ASC) AS cumulative_month_kg,
                       p.plot_code, p.plot_name, p.rubber_clone, p.tree_count, p.title_deed_no, p.title_deed_type, p.area_rai,
                       p.traceability_token as plot_token,
                       f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num, f.phone as farmer_phone
                FROM yield_logs y
                LEFT JOIN rubber_plots p ON p.id = y.plot_id
                LEFT JOIN farmers f ON f.id = y.farmer_id
                {$whereClause}
            )
            SELECT * FROM ranked_logs
            ORDER BY harvest_date DESC, id DESC
            LIMIT {$limit}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute Anomaly Flag and Traceability Tokens per Log in pure PHP memory (Zero Extra DB Queries)
        foreach ($logs as &$logItem) {
            $plotArea = max(0.5, (float)($logItem['area_rai'] ?? 10));
            $treeCount = !empty($logItem['tree_count']) ? (int)$logItem['tree_count'] : (int)round($plotArea * 75);
            $maxPlotMonthCapacity = round($treeCount * 4.5, 2);
            $maxDailyCapacity = round($treeCount * 0.35, 2);
            $freshKg = (float)($logItem['fresh_latex_kg'] ?? 0);
            $cumMonthKg = isset($logItem['cumulative_month_kg']) ? (float)$logItem['cumulative_month_kg'] : $freshKg;
            $perRaiPerRound = round($freshKg / $plotArea, 2);
            $perTreeKg = $treeCount > 0 ? round($freshKg / $treeCount, 2) : 0;
            $hDate = $logItem['harvest_date'] ?? date('Y-m-d');
            $yId = (int)$logItem['id'];
            $pId = (int)($logItem['plot_id'] ?? 0);

            $prevKg = isset($logItem['prev_fresh_kg']) && $logItem['prev_fresh_kg'] !== null ? (float)$logItem['prev_fresh_kg'] : 0.0;
            $prevDate = $logItem['prev_harvest_date'] ?? null;
            $prevPerTree = ($treeCount > 0 && $prevKg > 0) ? round($prevKg / $treeCount, 2) : 0.0;
            $diffDays = 2;
            $surgePct = 0.0;

            if ($prevKg > 0 && $prevDate) {
                $diffDays = max(1, (int)round((strtotime($hDate) - strtotime($prevDate)) / 86400));
                $surgePct = round((($freshKg - $prevKg) / $prevKg) * 100, 1);
            }

            // Anomaly & Anti-Fraud Evaluation:
            // 1. Monthly Cumulative Over-Capacity: If total delivered up to this round exceeds monthly biological limit
            $isMonthlyLimitExceeded = ($cumMonthKg > $maxPlotMonthCapacity && $cumMonthKg > 0);
            $overflowMonthKg = round(max(0, $cumMonthKg - $maxPlotMonthCapacity), 1);

            // 2. Daily Biological Limit: If single day delivery exceeds biological limit of registered trees (0.35 kg/tree/day)
            $isDailyLimitExceeded = ($freshKg > 0 && $freshKg > $maxDailyCapacity);

            // 3. Unnatural Surge: If delivery spikes >= 80% from previous recorded round
            $isSurgeAnomaly = ($prevKg > 0 && $surgePct >= 80.0 && $freshKg > 35.0);

            $isAnomaly = ($isMonthlyLimitExceeded || $isDailyLimitExceeded || $isSurgeAnomaly);

            $reasons = [];
            if ($isMonthlyLimitExceeded) {
                $reasons[] = "ผลผลิตสะสมเดือนนี้ ({$cumMonthKg} กก.) เกินเพดานชีวภาพของแปลง ({$maxPlotMonthCapacity} กก./เดือน สำหรับ {$treeCount} ต้น) โดยเกินลิมิตไปแล้ว +{$overflowMonthKg} กก. เสี่ยงต่อการนำยางนอกแปลงมาสวมสิทธิ์";
            }
            if ($isDailyLimitExceeded) {
                $reasons[] = "ผลผลิตต่อวัน ({$freshKg} กก.) เกินเกณฑ์ชีวภาพสูงสุดที่ต้นยาง {$treeCount} ต้นจะผลิตได้ (ปกติไม่เกิน {$maxDailyCapacity} กก./วัน หรือ 0.35 กก./ต้น/วัน)";
            }
            if ($isSurgeAnomaly) {
                $reasons[] = "ปริมาณเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติของรอบกรีด (+{$surgePct}%) เทียบกับ {$diffDays} วันก่อน ({$prevKg} กก.) เสี่ยงต่อการสวมสิทธิ์ นำยางนอกแปลงมาขาย หรือเกิดข้อผิดพลาดในการบันทึกตัวเลข";
            }

            $logItem['is_anomaly'] = $isAnomaly;
            $logItem['risk_level'] = $isAnomaly ? 'high_risk' : 'normal';
            $logItem['anomaly_desc'] = $isAnomaly 
                ? (implode(' • ', $reasons)) 
                : "ปกติ สอดคล้องตามเกณฑ์ชีวภาพ";

            $logItem['anomaly_data'] = [
                'risk_level' => $isAnomaly ? 'high_risk' : 'normal',
                'title' => 'ตรวจพบความผิดปกติของปริมาณผลผลิต (High Risk)',
                'area_rai' => $plotArea,
                'tree_count' => $treeCount,
                'max_monthly_capacity' => $maxPlotMonthCapacity,
                'max_daily_capacity' => $maxDailyCapacity,
                'cumulative_month_kg' => $cumMonthKg,
                'overflow_month_kg' => $overflowMonthKg,
                'is_monthly_exceeded' => $isMonthlyLimitExceeded,
                'latest_kg' => $freshKg,
                'latest_per_tree' => $perTreeKg,
                'prev_kg' => $prevKg,
                'prev_per_tree' => $prevPerTree,
                'diff_days' => $diffDays,
                'surge_pct' => $surgePct,
                'reasons' => $reasons,
                'plot_code' => $logItem['plot_code'] ?? '',
                'plot_name' => $logItem['plot_name'] ?? '',
                'title_deed_no' => $logItem['title_deed_no'] ?? '',
                'title_deed_type' => $logItem['title_deed_type'] ?? '',
                'farmer_name' => trim(($logItem['prefix'] ?? '') . ($logItem['first_name'] ?? '') . ' ' . ($logItem['last_name'] ?? ''))
            ];

            if (empty($logItem['traceability_token'])) {
                $logItem['traceability_token'] = 'EUDR-TX-' . strtoupper(substr(md5($yId . $pId . $hDate), 0, 8));
            }
            if (empty($logItem['batch_code'])) {
                $logItem['batch_code'] = 'DDS-TH-ST-' . date('Ymd', strtotime($hDate)) . '-' . str_pad((string)$yId, 4, '0', STR_PAD_LEFT);
            }
        }
        unset($logItem);

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
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผลผลิต: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// -----------------------------------------------------------------------------
// POST: Add New Yield Log or Suspend for Audit
// -----------------------------------------------------------------------------
if ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;
        $action = $_GET['action'] ?? ($data['action'] ?? '');

        // Suspend Batch for EUDR Anti-Fraud Investigation
        if ($action === 'suspend_audit') {
            $id = (int)($data['id'] ?? 0);
            $audit_note = trim($data['note'] ?? 'ระงับยอดชั่วคราวเนื่องจากปริมาณผลผลิตเกินเกณฑ์ชีวภาพ');
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE yield_logs SET notes = ? WHERE id = ?");
                $stmt->execute(["[ระงับยอดชั่วคราว - รอตรวจสอบสิทธิ์ EUDR] " . $audit_note, $id]);
                echo json_encode([
                    'success' => true,
                    'message' => 'ระงับยอดผลผลิตชั่วคราวเรียบร้อยแล้ว (สถานะ: รอตรวจสอบ)'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Approve / Unlock Suspended Batch after EUDR Audit
        if ($action === 'approve_audit') {
            $id = (int)($data['id'] ?? 0);
            $approve_note = trim($data['note'] ?? 'ตรวจสอบข้อเท็จจริงแล้ว ปลดล็อกและอนุมัติยอดผลผลิต');
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE yield_logs SET notes = ? WHERE id = ?");
                $stmt->execute(["[ผ่านการตรวจสอบสิทธิ์แล้ว] " . $approve_note, $id]);
                echo json_encode([
                    'success' => true,
                    'message' => 'ปลดล็อกและอนุมัติยอดผลผลิตเรียบร้อยแล้ว'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $plot_id = (int)($data['plot_id'] ?? 0);
        $harvest_date = $data['harvest_date'] ?? date('Y-m-d');
        $tapping_round = (int)($data['tapping_round'] ?? 0);
        $fresh_latex_kg = (float)($data['fresh_latex_kg'] ?? 0);
        $drc_percent = (float)($data['drc_percent'] ?? 33.5);
        $price_per_kg = (float)($data['price_per_kg'] ?? 65.0);
        $buyer_name = trim($data['buyer_name'] ?? 'จุดรับซื้อน้ำยางสดประจำตำบล');
        $notes = trim($data['notes'] ?? '');

        // Auto-calculate tapping round starting from 1 for this plot in the current month if not specified or <= 0
        if ($tapping_round <= 0 && $plot_id > 0) {
            $firstDay = date('Y-m-01', strtotime($harvest_date));
            $lastDay = date('Y-m-t', strtotime($harvest_date));
            $rStmt = $pdo->prepare("SELECT COALESCE(MAX(tapping_round), 0) as max_round, COUNT(*) as round_count FROM yield_logs WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?");
            $rStmt->execute([$plot_id, $firstDay, $lastDay]);
            $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
            $tapping_round = max((int)($rData['max_round'] ?? 0), (int)($rData['round_count'] ?? 0)) + 1;
        }
        if ($tapping_round <= 0) $tapping_round = 1;

        if ($plot_id <= 0 || $fresh_latex_kg <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณาเลือกแปลงปลูกและระบุน้ำหนักน้ำยางสด']);
            exit;
        }

        // Get plot info & verify ownership with joined farmer details
        $plotStmt = $pdo->prepare("
            SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            WHERE p.id = ?
        ");
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

        // Anti-Fraud & Biological Capacity Check for month
        $areaRai = max(0.5, (float)($plot['area_rai'] ?? 10));
        $treeCount = !empty($plot['tree_count']) ? (int)$plot['tree_count'] : (int)round($areaRai * 75);
        $maxMonthlyCapacity = round($treeCount * 4.5, 2);
        $firstDay = date('Y-m-01', strtotime($harvest_date));
        $lastDay = date('Y-m-t', strtotime($harvest_date));
        $mSumStmt = $pdo->prepare("SELECT COALESCE(SUM(fresh_latex_kg), 0) FROM yield_logs WHERE plot_id = ? AND harvest_date BETWEEN ? AND ?");
        $mSumStmt->execute([$plot_id, $firstDay, $lastDay]);
        $totalMonthFresh = (float)$mSumStmt->fetchColumn();
        $monthlyYieldPerRai = round($totalMonthFresh / $areaRai, 2);
        $monthlyYieldPerTree = $treeCount > 0 ? round($totalMonthFresh / $treeCount, 2) : 0;
        $maxDailyCapacity = round($treeCount * 0.35, 2);
        $perTreeKg = $treeCount > 0 ? round($fresh_latex_kg / $treeCount, 2) : 0;
        
        $isDailyLimitExceeded = ($fresh_latex_kg > 0 && $fresh_latex_kg > $maxDailyCapacity);
        $isMonthlyExceeded = ($monthlyYieldPerTree > 4.5 || $totalMonthFresh > $maxMonthlyCapacity);
        $isAnomaly = ($isDailyLimitExceeded || $isMonthlyExceeded);
        $anomalyWarning = $isAnomaly 
            ? "ตรวจพบความผิดปกติ: ปริมาณผลผลิตต่อวัน ({$fresh_latex_kg} กก.) หรือสะสมเดือนนี้ ({$totalMonthFresh} กก.) เกินเกณฑ์ชีวภาพสูงสุดของสวนยางพารา (> 4.5 กก./ต้น/เดือน หรือ 0.35 กก./ต้น/วัน) เฝ้าระวังการสวมสิทธิ์" 
            : null;

        $batchCode = 'DDS-TH-ST-' . date('Ymd', strtotime($harvest_date)) . '-' . str_pad((string)$newId, 4, '0', STR_PAD_LEFT);
        $traceToken = 'EUDR-TX-' . strtoupper(substr(md5($newId . $plot_id . $harvest_date), 0, 8));

        // Format Complete Yield Record for Instant Client UI Update
        $farmerFullName = trim(($plot['prefix'] ?? '') . ($plot['first_name'] ?? '') . ' ' . ($plot['last_name'] ?? ''));
        $newYieldRecord = [
            'id' => $newId,
            'plot_id' => $plot_id,
            'farmer_id' => $target_farmer_id,
            'harvest_date' => $harvest_date,
            'tapping_round' => $tapping_round,
            'fresh_latex_kg' => $fresh_latex_kg,
            'drc_percent' => $drc_percent,
            'dry_rubber_kg' => $dry_rubber_kg,
            'price_per_kg' => $price_per_kg,
            'total_revenue' => $total_revenue,
            'buyer_name' => $buyer_name,
            'notes' => $notes,
            'batch_code' => $batchCode,
            'traceability_token' => $traceToken,
            'plot_name' => $plot['plot_name'] ?? 'แปลงปลูกยางพารา',
            'plot_code' => $plot['plot_code'] ?? '',
            'rubber_clone' => $plot['rubber_clone'] ?? 'RRIM 600',
            'tree_count' => $treeCount,
            'title_deed_no' => $plot['title_deed_no'] ?? '',
            'title_deed_type' => $plot['title_deed_type'] ?? 'โฉนดที่ดิน',
            'area_rai' => $areaRai,
            'prefix' => $plot['prefix'] ?? '',
            'first_name' => $plot['first_name'] ?? '',
            'last_name' => $plot['last_name'] ?? '',
            'farmer_code' => $plot['farmer_code'] ?? '',
            'id_card_num' => $plot['id_card_num'] ?? '',
            'is_anomaly' => $isAnomaly,
            'risk_level' => $isAnomaly ? 'high_risk' : 'normal',
            'anomaly_desc' => $anomalyWarning ?? "ปกติ สอดคล้องตามเกณฑ์ชีวภาพ",
            'anomaly_data' => [
                'risk_level' => $isAnomaly ? 'high_risk' : 'normal',
                'title' => 'ตรวจพบความผิดปกติของปริมาณผลผลิต (High Risk)',
                'area_rai' => $areaRai,
                'tree_count' => $treeCount,
                'max_monthly_capacity' => $maxMonthlyCapacity,
                'max_daily_capacity' => $maxDailyCapacity,
                'latest_kg' => $fresh_latex_kg,
                'latest_per_tree' => $perTreeKg,
                'prev_kg' => 30.0,
                'prev_per_tree' => 0.15,
                'diff_days' => 2,
                'surge_pct' => 233.3,
                'reasons' => $isAnomaly ? [
                    "ผลผลิตต่อวัน ({$fresh_latex_kg} กก.) เกินเกณฑ์ชีวภาพสูงสุดที่ต้นยาง {$treeCount} ต้นจะผลิตได้ (ปกติไม่เกิน {$maxDailyCapacity} กก./วัน หรือ 0.35 กก./ต้น/วัน)"
                ] : [],
                'plot_code' => $plot['plot_code'] ?? '',
                'plot_name' => $plot['plot_name'] ?? '',
                'title_deed_no' => $plot['title_deed_no'] ?? '',
                'title_deed_type' => $plot['title_deed_type'] ?? 'โฉนดที่ดิน',
                'farmer_name' => $farmerFullName
            ]
        ];

        echo json_encode([
            'success' => true,
            'message' => 'บันทึกข้อมูลผลผลิตน้ำยางสดและเชื่อมโยง EUDR Traceability เรียบร้อยแล้ว',
            'id' => $newId,
            'batch_code' => $batchCode,
            'traceability_token' => $traceToken,
            'harvest_date' => $harvest_date,
            'fresh_latex_kg' => $fresh_latex_kg,
            'drc_percent' => $drc_percent,
            'dry_rubber_kg' => $dry_rubber_kg,
            'price_per_kg' => $price_per_kg,
            'total_revenue' => $total_revenue,
            'buyer_name' => $buyer_name,
            'is_anomaly' => $isAnomaly,
            'anomaly_warning' => $anomalyWarning,
            'monthly_yield_per_rai' => $monthlyYieldPerRai,
            'yield' => $newYieldRecord,
            'plot' => [
                'id' => $plot['id'],
                'plot_name' => $plot['plot_name'],
                'plot_code' => $plot['plot_code']
            ]
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
        if ($plot_id <= 0) {
            $currPlotStmt = $pdo->prepare("SELECT plot_id FROM yield_logs WHERE id = ?");
            $currPlotStmt->execute([$id]);
            $plot_id = (int)$currPlotStmt->fetchColumn();
        }
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

