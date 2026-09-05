<?php
/**
 * GeoRubber Watch - Decision Support System (DSS) & Analytics API
 * Role-Based Access Control (RBAC): Farmer (Personal Stats & 2 Trend Charts) vs Admin (Macro Provincial Analytics)
 */
require_once __DIR__ . '/../includes/auth_check.php';
header('Content-Type: application/json; charset=utf-8');

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

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $dateExpr = ($driver === 'pgsql') ? "TO_CHAR(harvest_date, 'YYYY-MM-DD')" : "DATE(harvest_date)";
    $monthExpr = ($driver === 'pgsql') ? "TO_CHAR(harvest_date, 'YYYY-MM')" : "strftime('%Y-%m', harvest_date)";

    if (!$isUserAdmin) {
        // =====================================================================
        // 1. FARMER PERSONAL DASHBOARD
        // =====================================================================
        $fId = $farmerId ?: -1;

        // Farmer Plot KPIs
        $plotStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_plots,
                COALESCE(SUM(area_rai), 0) as total_rai,
                COALESCE(SUM(area_ngan), 0) as total_ngan,
                COALESCE(SUM(area_sqwah), 0) as total_sqwah,
                COALESCE(SUM(area_hectare), 0) as total_hectare,
                COALESCE(SUM(tree_count), 0) as total_trees,
                SUM(CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END) as compliant_plots,
                SUM(CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant_plots,
                SUM(CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END) as review_plots,
                SUM(CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END) as tapping_plots
            FROM rubber_plots
            WHERE farmer_id = ?
        ");
        $plotStmt->execute([$fId]);
        $plotStats = $plotStmt->fetch(PDO::FETCH_ASSOC);

        // Farmer Yield KPIs
        $yieldStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_yield_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_latex_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_rubber_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue_thb,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
            WHERE farmer_id = ?
        ");
        $yieldStmt->execute([$fId]);
        $yieldStats = $yieldStmt->fetch(PDO::FETCH_ASSOC);

        // Chart 1: Yield Trend (กก. ตามรอบการกรีด/วันที่)
        $trendStmt1 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, SUM(fresh_latex_kg) as daily_kg, AVG(drc_percent) as avg_drc
            FROM yield_logs
            WHERE farmer_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 20
        ");
        $trendStmt1->execute([$fId]);
        $yieldTrend = $trendStmt1->fetchAll(PDO::FETCH_ASSOC);

        // Chart 2: Price & Revenue Trend (ราคารับซื้อ และ รายได้รวม)
        $trendStmt2 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, AVG(price_per_kg) as avg_price, SUM(total_revenue) as daily_revenue
            FROM yield_logs
            WHERE farmer_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 20
        ");
        $trendStmt2->execute([$fId]);
        $priceIncomeTrend = $trendStmt2->fetchAll(PDO::FETCH_ASSOC);

        $totalPlots = (int)$plotStats['total_plots'];
        $compliantPlots = (int)$plotStats['compliant_plots'];
        $complianceRate = $totalPlots > 0 ? round(($compliantPlots / $totalPlots) * 100, 1) : 100.0;

        echo json_encode([
            'success' => true,
            'mode' => 'farmer',
            'role' => $currentUser['role'],
            'kpis' => [
                'total_plots' => $totalPlots,
                'total_area_rai' => (float)$plotStats['total_rai'],
                'total_area_ha' => round((float)$plotStats['total_hectare'], 2),
                'total_trees' => (int)$plotStats['total_trees'],
                'tapping_plots' => (int)$plotStats['tapping_plots'],
                'compliant_plots' => $compliantPlots,
                'non_compliant_plots' => (int)$plotStats['non_compliant_plots'],
                'review_plots' => (int)$plotStats['review_plots'],
                'eudr_compliance_rate' => $complianceRate,
                'total_yield_records' => (int)$yieldStats['total_yield_records'],
                'total_fresh_latex_kg' => round((float)$yieldStats['total_fresh_latex_kg'], 1),
                'total_dry_rubber_kg' => round((float)$yieldStats['total_dry_rubber_kg'], 1),
                'total_revenue_thb' => round((float)$yieldStats['total_revenue_thb'], 2),
                'avg_drc_percent' => round((float)$yieldStats['avg_drc'], 1),
                'avg_price_per_kg' => round((float)$yieldStats['avg_price'], 2)
            ],
            'charts' => [
                'yield_trend' => $yieldTrend,
                'price_income_trend' => $priceIncomeTrend,
                'eudr_status_breakdown' => [
                    ['status' => 'Compliant (ปลอดการตัดไม้ทำลายป่า 100%)', 'count' => $compliantPlots, 'color' => '#10b981'],
                    ['status' => 'Non-Compliant (ทับซ้อนแนวเขตป่าสงวน)', 'count' => (int)$plotStats['non_compliant_plots'], 'color' => '#ef4444'],
                    ['status' => 'Under Review (โซนกันชนเฝ้าระวัง)', 'count' => (int)$plotStats['review_plots'], 'color' => '#f59e0b']
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } else {
        // =====================================================================
        // 2. ADMIN MACRO DASHBOARD (Aggregate Data)
        // =====================================================================
        $plotStats = $pdo->query("
            SELECT 
                COUNT(*) as total_plots,
                COALESCE(SUM(area_rai), 0) as total_rai,
                COALESCE(SUM(area_ngan), 0) as total_ngan,
                COALESCE(SUM(area_sqwah), 0) as total_sqwah,
                COALESCE(SUM(area_sqm), 0) as total_sqm,
                COALESCE(SUM(area_hectare), 0) as total_hectare,
                COALESCE(SUM(tree_count), 0) as total_trees,
                SUM(CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END) as compliant_plots,
                SUM(CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant_plots,
                SUM(CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END) as review_plots,
                SUM(CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END) as tapping_plots
            FROM rubber_plots
        ")->fetch(PDO::FETCH_ASSOC);

        $farmerCount = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();

        $yieldStats = $pdo->query("
            SELECT 
                COUNT(*) as total_yield_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_latex_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_rubber_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue_thb,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
        ")->fetch(PDO::FETCH_ASSOC);

        $totalPlots = (int)$plotStats['total_plots'];
        $compliantPlots = (int)$plotStats['compliant_plots'];
        $complianceRate = $totalPlots > 0 ? round(($compliantPlots / $totalPlots) * 100, 1) : 100.0;

        // Clone Distribution
        $cloneStats = $pdo->query("
            SELECT rubber_clone, COUNT(*) as count, SUM(area_rai) as total_rai
            FROM rubber_plots
            GROUP BY rubber_clone
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Tree Age Distribution
        $currentYear = (int)date('Y');
        $plots = $pdo->query("SELECT planting_year, area_rai FROM rubber_plots")->fetchAll(PDO::FETCH_ASSOC);
        $ageGroups = [
            'immature_1_6' => ['label' => 'ยังไม่เปิดกรีด (1-6 ปี)', 'count' => 0, 'rai' => 0],
            'prime_7_15' => ['label' => 'ช่วงให้ผลผลิตสูงสุด (7-15 ปี)', 'count' => 0, 'rai' => 0],
            'mature_16_25' => ['label' => 'ช่วงโตเต็มที่ (16-25 ปี)', 'count' => 0, 'rai' => 0],
            'old_25_plus' => ['label' => 'ควรตัดโค่น/ปลูกแทน (>25 ปี)', 'count' => 0, 'rai' => 0]
        ];

        foreach ($plots as $p) {
            $age = $currentYear - (int)$p['planting_year'];
            if ($age <= 6) {
                $ageGroups['immature_1_6']['count']++;
                $ageGroups['immature_1_6']['rai'] += (float)$p['area_rai'];
            } elseif ($age <= 15) {
                $ageGroups['prime_7_15']['count']++;
                $ageGroups['prime_7_15']['rai'] += (float)$p['area_rai'];
            } elseif ($age <= 25) {
                $ageGroups['mature_16_25']['count']++;
                $ageGroups['mature_16_25']['rai'] += (float)$p['area_rai'];
            } else {
                $ageGroups['old_25_plus']['count']++;
                $ageGroups['old_25_plus']['rai'] += (float)$p['area_rai'];
            }
        }

        // Monthly Yield Production Trend
        $monthlyYields = $pdo->query("
            SELECT 
                {$monthExpr} as harvest_month,
                SUM(fresh_latex_kg) as monthly_fresh_kg,
                SUM(dry_rubber_kg) as monthly_dry_kg,
                SUM(total_revenue) as monthly_revenue
            FROM yield_logs
            GROUP BY {$monthExpr}
            ORDER BY harvest_month ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // At-Risk / Non-Compliant Plots
        $atRiskPlots = $pdo->query("
            SELECT p.*, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone
            FROM rubber_plots p
            JOIN farmers f ON f.id = p.farmer_id
            WHERE p.eudr_status != 'compliant' OR p.eudr_overlap_pct > 0
            ORDER BY p.eudr_overlap_pct DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'mode' => 'admin',
            'role' => $currentUser['role'],
            'kpis' => [
                'total_plots' => $totalPlots,
                'total_farmers' => (int)$farmerCount,
                'total_area_rai' => (float)$plotStats['total_rai'],
                'total_area_ha' => round((float)$plotStats['total_hectare'], 2),
                'total_trees' => (int)$plotStats['total_trees'],
                'tapping_plots' => (int)$plotStats['tapping_plots'],
                'compliant_plots' => $compliantPlots,
                'non_compliant_plots' => (int)$plotStats['non_compliant_plots'],
                'review_plots' => (int)$plotStats['review_plots'],
                'eudr_compliance_rate' => $complianceRate,
                'total_fresh_latex_kg' => round((float)$yieldStats['total_fresh_latex_kg'], 1),
                'total_dry_rubber_kg' => round((float)$yieldStats['total_dry_rubber_kg'], 1),
                'total_revenue_thb' => round((float)$yieldStats['total_revenue_thb'], 2),
                'avg_drc_percent' => round((float)$yieldStats['avg_drc'], 1),
                'avg_price_per_kg' => round((float)$yieldStats['avg_price'], 2)
            ],
            'charts' => [
                'clone_distribution' => $cloneStats,
                'age_distribution' => array_values($ageGroups),
                'monthly_yields' => $monthlyYields,
                'eudr_status_breakdown' => [
                    ['status' => 'Compliant (ผ่านเกณฑ์ 100%)', 'count' => $compliantPlots, 'color' => '#10b981'],
                    ['status' => 'Non-Compliant (ทับซ้อนป่าสงวน)', 'count' => (int)$plotStats['non_compliant_plots'], 'color' => '#ef4444'],
                    ['status' => 'Under Review (โซนเฝ้าระวัง)', 'count' => (int)$plotStats['review_plots'], 'color' => '#f59e0b']
                ]
            ],
            'at_risk_plots' => $atRiskPlots
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

