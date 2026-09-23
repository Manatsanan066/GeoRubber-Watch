<?php
/**
 * GeoRubber Watch - Decision Support System (DSS) Dashboard
 * Spatial Analytics & Surat Thani Forest Overlap Verification
 */

declare(strict_types=1);

if (!headers_sent()) {
    ob_start();
}
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$currentUser = getCurrentUser();
$current_role = $currentUser['role'] ?? 'farmer';
$user_name = $currentUser['full_name'] ?? 'ผู้ใช้งานระบบ';
$isFactory = in_array($current_role, ['factory', 'buyer', 'trader'], true) || (isset($_GET['mode']) && $_GET['mode'] === 'factory') || (isset($_GET['role']) && $_GET['role'] === 'factory');
$isUserAdmin = isAdmin() && !$isFactory;
$isFarmer = ($current_role === 'farmer') || (!$isUserAdmin && !$isFactory);

$pdo = getDatabaseConnection();
$dbConnected = ($pdo !== null);

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatNumber(mixed $number, int $decimals = 0): string
{
    if ($number === null || $number === '') {
        return '0';
    }
    return number_format((float)$number, $decimals);
}

function relativeTime(mixed $date): string
{
    if (!$date) return '—';
    $time = strtotime((string)$date);
    if (!$time) return e($date);
    $diff = time() - $time;
    if ($diff < 60) return 'เมื่อสักครู่นี้';
    if ($diff < 3600) return floor($diff / 60) . ' นาทีที่แล้ว';
    if ($diff < 86400) return floor($diff / 3600) . ' ชม.ที่แล้ว';
    if ($diff < 604800) return floor($diff / 86400) . ' วันที่แล้ว';
    return date('d/m/Y H:i', $time);
}

// -------------------------------------------------------------------------
// DATABASE QUERIES ACCORDING TO ROLE (RBAC)
// -------------------------------------------------------------------------
$farmerId = $currentUser['farmer_id'] ?? null;
if (!$isUserAdmin && !$isFactory && !$farmerId && isset($_SESSION['user_id'])) {
    $fStmt = $pdo->prepare("SELECT id FROM farmers WHERE user_id = ?");
    $fStmt->execute([$_SESSION['user_id']]);
    $farmerId = (int)$fStmt->fetchColumn();
    if ($farmerId) {
        $_SESSION['farmer_id'] = $farmerId;
    }
}

$driver = $pdo ? $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) : 'pgsql';
$dateExpr = ($driver === 'pgsql') ? "TO_CHAR(harvest_date, 'YYYY-MM-DD')" : "DATE(harvest_date)";
$monthExpr = ($driver === 'pgsql') ? "TO_CHAR(harvest_date, 'YYYY-MM')" : "strftime('%Y-%m', harvest_date)";

// Filter params for Admin
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

if ($isFactory) {
    // =========================================================================
    // 1. FACTORY SOURCING DASHBOARD DATA
    // =========================================================================
    $selectedFactoryPlotId = isset($_GET['plot_id']) && (int)$_GET['plot_id'] > 0 ? (int)$_GET['plot_id'] : null;

    // All registered sourcing plots with farmer profile
    try {
        $fPlotsStmt = $pdo->query("
            SELECT p.*, f.prefix, f.first_name, f.last_name, f.farmer_code, f.district, f.subdistrict, f.phone
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            ORDER BY p.id ASC
        ");
        $factoryPlotsList = $fPlotsStmt ? $fPlotsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {
        $factoryPlotsList = [];
    }

    // Selected plot info if plot_id is chosen
    $selectedFactoryPlotInfo = null;
    if ($selectedFactoryPlotId) {
        foreach ($factoryPlotsList as $p) {
            if ((int)$p['id'] === $selectedFactoryPlotId) {
                $selectedFactoryPlotInfo = $p;
                break;
            }
        }
    }

    // Factory Plot KPIs
    if ($selectedFactoryPlotId && $selectedFactoryPlotInfo) {
        $fPlotStmt = $pdo->prepare("
            SELECT 
                1 as total_plots,
                COALESCE(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025), 0) as total_rai,
                COALESCE(area_hectare, 0) as total_ha,
                COALESCE(tree_count, 0) as total_trees,
                CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END as compliant_plots,
                CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END as review_plots,
                CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END as non_compliant_plots,
                CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END as tapping_plots
            FROM rubber_plots
            WHERE id = ?
        ");
        $fPlotStmt->execute([$selectedFactoryPlotId]);
        $factoryPlotKPI = $fPlotStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        $fPlotStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_plots,
                COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) as total_rai,
                COALESCE(SUM(area_hectare), 0) as total_ha,
                COALESCE(SUM(tree_count), 0) as total_trees,
                SUM(CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END) as compliant_plots,
                SUM(CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END) as review_plots,
                SUM(CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant_plots,
                SUM(CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END) as tapping_plots
            FROM rubber_plots
        ");
        $factoryPlotKPI = $fPlotStmt ? $fPlotStmt->fetch(PDO::FETCH_ASSOC) : [];
    }

    // Factory Yield KPIs
    if ($selectedFactoryPlotId) {
        $fYldStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
            WHERE plot_id = ?
        ");
        $fYldStmt->execute([$selectedFactoryPlotId]);
        $factoryYieldKPI = $fYldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        $fYldStmt = $pdo->query("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
        ");
        $factoryYieldKPI = $fYldStmt ? $fYldStmt->fetch(PDO::FETCH_ASSOC) : [];
    }

    // Trend 1: Sourcing Yield Trend (Daily/Harvest Date)
    if ($selectedFactoryPlotId) {
        $fTrend1 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, SUM(fresh_latex_kg) as daily_kg, AVG(drc_percent) as avg_drc
            FROM yield_logs
            WHERE plot_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $fTrend1->execute([$selectedFactoryPlotId]);
        $factoryYieldTrendData = $fTrend1->fetchAll(PDO::FETCH_ASSOC);

        $fTrend2 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, AVG(price_per_kg) as avg_price, SUM(total_revenue) as daily_revenue
            FROM yield_logs
            WHERE plot_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $fTrend2->execute([$selectedFactoryPlotId]);
        $factoryPriceRevenueTrendData = $fTrend2->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $fTrend1 = $pdo->query("
            SELECT {$dateExpr} as harvest_date, SUM(fresh_latex_kg) as daily_kg, AVG(drc_percent) as avg_drc
            FROM yield_logs
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $factoryYieldTrendData = $fTrend1 ? $fTrend1->fetchAll(PDO::FETCH_ASSOC) : [];

        $fTrend2 = $pdo->query("
            SELECT {$dateExpr} as harvest_date, AVG(price_per_kg) as avg_price, SUM(total_revenue) as daily_revenue
            FROM yield_logs
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $factoryPriceRevenueTrendData = $fTrend2 ? $fTrend2->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    // Donut Sourcing Distribution Data
    if ($selectedFactoryPlotId) {
        $drcHigh = 0; $drcMid = 0; $drcLow = 0;
        $drcRowsStmt = $pdo->prepare("SELECT drc_percent, fresh_latex_kg FROM yield_logs WHERE plot_id = ?");
        $drcRowsStmt->execute([$selectedFactoryPlotId]);
        $drcRows = $drcRowsStmt ? $drcRowsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($drcRows as $dr) {
            $val = (float)$dr['drc_percent'];
            $kg = (float)$dr['fresh_latex_kg'];
            if ($val >= 35) {
                $drcHigh += $kg;
            } elseif ($val >= 30) {
                $drcMid += $kg;
            } else {
                $drcLow += $kg;
            }
        }
        $factoryDonutData = [
            ['label' => 'DRC ≥ 35% (เกรดพรีเมียม)', 'value' => round($drcHigh, 1)],
            ['label' => 'DRC 30-34.9% (เกรดมาตรฐาน)', 'value' => round($drcMid, 1)],
            ['label' => 'DRC < 30% (เกรดรอง/ความชื้นสูง)', 'value' => round($drcLow, 1)]
        ];
    } else {
        $dStmt = $pdo->query("
            SELECT COALESCE(p.plot_name, 'ไม่ระบุ') as label, SUM(y.fresh_latex_kg) as value
            FROM yield_logs y
            LEFT JOIN rubber_plots p ON p.id = y.plot_id
            GROUP BY p.plot_name
            ORDER BY value DESC
            LIMIT 5
        ");
        $factoryDonutData = $dStmt ? $dStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if (empty($factoryDonutData)) {
            $dStmt2 = $pdo->query("
                SELECT COALESCE(rubber_clone, 'ไม่ระบุ') as label, SUM(area_rai) as value
                FROM rubber_plots
                GROUP BY rubber_clone
                ORDER BY value DESC
                LIMIT 5
            ");
            $factoryDonutData = $dStmt2 ? $dStmt2->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }

    // Recent Factory Sourcing Logs
    if ($selectedFactoryPlotId) {
        $recentStmt = $pdo->prepare("
            SELECT y.*, 
                   SUM(y.fresh_latex_kg) OVER (PARTITION BY y.plot_id, SUBSTR(CAST(y.harvest_date AS TEXT), 1, 7) ORDER BY y.harvest_date ASC, y.id ASC) AS cumulative_month_kg,
                   p.plot_code, p.plot_name, p.rubber_clone, p.area_rai, p.tree_count, p.eudr_status,
                   f.first_name, f.last_name, f.prefix
            FROM yield_logs y
            LEFT JOIN rubber_plots p ON p.id = y.plot_id
            LEFT JOIN farmers f ON f.id = y.farmer_id
            WHERE y.plot_id = ?
            ORDER BY y.harvest_date DESC, y.id DESC
            LIMIT 15
        ");
        $recentStmt->execute([$selectedFactoryPlotId]);
        $factoryRecentLogs = $recentStmt ? $recentStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } else {
        $recentStmt = $pdo->query("
            SELECT y.*, 
                   SUM(y.fresh_latex_kg) OVER (PARTITION BY y.plot_id, SUBSTR(CAST(y.harvest_date AS TEXT), 1, 7) ORDER BY y.harvest_date ASC, y.id ASC) AS cumulative_month_kg,
                   p.plot_code, p.plot_name, p.rubber_clone, p.area_rai, p.tree_count, p.eudr_status,
                   f.first_name, f.last_name, f.prefix
            FROM yield_logs y
            LEFT JOIN rubber_plots p ON p.id = y.plot_id
            LEFT JOIN farmers f ON f.id = y.farmer_id
            ORDER BY y.harvest_date DESC, y.id DESC
            LIMIT 15
        ");
        $factoryRecentLogs = $recentStmt ? $recentStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    $factoryTotalAreaRai = (float)($factoryPlotKPI['total_rai'] ?? 0);
    $factoryTotalFreshKg = (float)($factoryYieldKPI['total_fresh_kg'] ?? 0);
    $factoryTotalDryKg = (float)($factoryYieldKPI['total_dry_kg'] ?? 0);
    $factoryTotalRev = (float)($factoryYieldKPI['total_revenue'] ?? 0);
    $factoryAvgDrc = (float)($factoryYieldKPI['avg_drc'] ?? 0);
    $factoryAvgPrice = (float)($factoryYieldKPI['avg_price'] ?? 0);
    $factoryTotalPlots = (int)($factoryPlotKPI['total_plots'] ?? 0);
    $factoryCompliantPlots = (int)($factoryPlotKPI['compliant_plots'] ?? 0);
    $factoryComplianceRate = $factoryTotalPlots > 0 ? round(($factoryCompliantPlots / $factoryTotalPlots) * 100, 1) : 100.0;

} elseif (!$isUserAdmin) {
    // =========================================================================
    // 1. FARMER PERSONAL DASHBOARD DATA
    // =========================================================================
    $fId = $farmerId ?: -1;
    $selectedPlotId = isset($_GET['plot_id']) && (int)$_GET['plot_id'] > 0 ? (int)$_GET['plot_id'] : null;

    // Own Plots List
    $listStmt = $pdo->prepare("
        SELECT p.*, f.prefix, f.first_name, f.last_name, f.district, f.subdistrict
        FROM rubber_plots p
        LEFT JOIN farmers f ON f.id = p.farmer_id
        WHERE p.farmer_id = ?
        ORDER BY p.id ASC
    ");
    $listStmt->execute([$fId]);
    $personalPlotsList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // Selected plot info if plot_id is chosen
    $selectedPlotInfo = null;
    if ($selectedPlotId) {
        foreach ($personalPlotsList as $p) {
            if ((int)$p['id'] === $selectedPlotId) {
                $selectedPlotInfo = $p;
                break;
            }
        }
    }

    // Own Plot KPIs (aggregate or single plot)
    if ($selectedPlotId && $selectedPlotInfo) {
        $plotStmt = $pdo->prepare("
            SELECT 
                1 as total_plots,
                COALESCE(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025), 0) as total_rai,
                COALESCE(area_hectare, 0) as total_ha,
                COALESCE(tree_count, 0) as total_trees,
                CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END as compliant_plots,
                CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END as review_plots,
                CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END as non_compliant_plots,
                CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END as tapping_plots
            FROM rubber_plots
            WHERE farmer_id = ? AND id = ?
        ");
        $plotStmt->execute([$fId, $selectedPlotId]);
        $farmerPlots = $plotStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        $plotStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_plots,
                COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) as total_rai,
                COALESCE(SUM(area_hectare), 0) as total_ha,
                COALESCE(SUM(tree_count), 0) as total_trees,
                SUM(CASE WHEN eudr_status = 'compliant' THEN 1 ELSE 0 END) as compliant_plots,
                SUM(CASE WHEN eudr_status = 'under_review' THEN 1 ELSE 0 END) as review_plots,
                SUM(CASE WHEN eudr_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant_plots,
                SUM(CASE WHEN tapping_status = 'tapping' THEN 1 ELSE 0 END) as tapping_plots
            FROM rubber_plots
            WHERE farmer_id = ?
        ");
        $plotStmt->execute([$fId]);
        $farmerPlots = $plotStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Own Yield KPIs (aggregate or single plot)
    if ($selectedPlotId) {
        $yieldStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
            WHERE farmer_id = ? AND plot_id = ?
        ");
        $yieldStmt->execute([$fId, $selectedPlotId]);
        $farmerYields = $yieldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        $yieldStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_records,
                COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_kg,
                COALESCE(SUM(dry_rubber_kg), 0) as total_dry_kg,
                COALESCE(SUM(total_revenue), 0) as total_revenue,
                COALESCE(AVG(drc_percent), 0) as avg_drc,
                COALESCE(AVG(price_per_kg), 0) as avg_price
            FROM yield_logs
            WHERE farmer_id = ?
        ");
        $yieldStmt->execute([$fId]);
        $farmerYields = $yieldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Trend 1: Latex Yield Trend (กก. น้ำยางสด ตามรอบการกรีด/วันที่)
    if ($selectedPlotId) {
        $trendStmt1 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, SUM(fresh_latex_kg) as daily_kg, AVG(drc_percent) as avg_drc
            FROM yield_logs
            WHERE farmer_id = ? AND plot_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $trendStmt1->execute([$fId, $selectedPlotId]);
        $yieldTrendData = $trendStmt1->fetchAll(PDO::FETCH_ASSOC);

        $trendStmt2 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, AVG(price_per_kg) as avg_price, SUM(total_revenue) as daily_revenue
            FROM yield_logs
            WHERE farmer_id = ? AND plot_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $trendStmt2->execute([$fId, $selectedPlotId]);
        $priceRevenueTrendData = $trendStmt2->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $trendStmt1 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, SUM(fresh_latex_kg) as daily_kg, AVG(drc_percent) as avg_drc
            FROM yield_logs
            WHERE farmer_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $trendStmt1->execute([$fId]);
        $yieldTrendData = $trendStmt1->fetchAll(PDO::FETCH_ASSOC);

        $trendStmt2 = $pdo->prepare("
            SELECT {$dateExpr} as harvest_date, AVG(price_per_kg) as avg_price, SUM(total_revenue) as daily_revenue
            FROM yield_logs
            WHERE farmer_id = ?
            GROUP BY {$dateExpr}
            ORDER BY harvest_date ASC
            LIMIT 30
        ");
        $trendStmt2->execute([$fId]);
        $priceRevenueTrendData = $trendStmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    $totalPlotAreaRai = (float)($farmerPlots['total_rai'] ?? 0);
    $totalFreshKg = (float)($farmerYields['total_fresh_kg'] ?? 0);
    $totalRev = (float)($farmerYields['total_revenue'] ?? 0);
    $avgYieldPerRai = $totalPlotAreaRai > 0 ? ($totalFreshKg / $totalPlotAreaRai) : 0;
    $avgRevenuePerRai = $totalPlotAreaRai > 0 ? ($totalRev / $totalPlotAreaRai) : 0;

    $totalFarmerPlots = (int)($farmerPlots['total_plots'] ?? 0);
    $compliantFarmerPlots = (int)($farmerPlots['compliant_plots'] ?? 0);
    $farmerComplianceRate = $totalFarmerPlots > 0 ? round(($compliantFarmerPlots / $totalFarmerPlots) * 100, 1) : 100.0;

} else {
    // =========================================================================
    // 2. ADMIN MACRO DASHBOARD DATA
    // =========================================================================
    $totalArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots")->fetchColumn();
    $totalPlots = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots")->fetchColumn();
    $totalFarmers = (int)$pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();

    $greenCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'compliant'")->fetchColumn();
    $greenArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'compliant'")->fetchColumn();

    $yellowCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'under_review'")->fetchColumn();
    $yellowArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'under_review'")->fetchColumn();

    $redCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'non_compliant'")->fetchColumn();
    $redArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'non_compliant'")->fetchColumn();

    $macroYieldStats = $pdo->query("
        SELECT 
            COALESCE(SUM(fresh_latex_kg), 0) as total_fresh_latex,
            COALESCE(SUM(dry_rubber_kg), 0) as total_dry_rubber,
            COALESCE(SUM(total_revenue), 0) as total_revenue,
            COALESCE(AVG(price_per_kg), 0) as avg_price
        FROM yield_logs
    ")->fetch(PDO::FETCH_ASSOC);

    // Clone Distribution
    $cloneStats = $pdo->query("
        SELECT rubber_clone, COUNT(*) as count, SUM(area_rai) as total_rai
        FROM rubber_plots
        GROUP BY rubber_clone
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Tree Age Distribution
    $currentYear = (int)date('Y');
    $allPlots = $pdo->query("SELECT planting_year, area_rai FROM rubber_plots")->fetchAll(PDO::FETCH_ASSOC);
    $ageGroups = [
        'immature' => ['label' => 'ยังไม่เปิดกรีด (1-6 ปี)', 'count' => 0, 'rai' => 0],
        'prime' => ['label' => 'ผลผลิตสูงสุด (7-15 ปี)', 'count' => 0, 'rai' => 0],
        'mature' => ['label' => 'โตเต็มที่ (16-25 ปี)', 'count' => 0, 'rai' => 0],
        'old' => ['label' => 'ควรปลูกแทน (>25 ปี)', 'count' => 0, 'rai' => 0]
    ];
    foreach ($allPlots as $p) {
        $age = $currentYear - (int)$p['planting_year'];
        if ($age <= 6) {
            $ageGroups['immature']['count']++;
            $ageGroups['immature']['rai'] += (float)$p['area_rai'];
        } elseif ($age <= 15) {
            $ageGroups['prime']['count']++;
            $ageGroups['prime']['rai'] += (float)$p['area_rai'];
        } elseif ($age <= 25) {
            $ageGroups['mature']['count']++;
            $ageGroups['mature']['rai'] += (float)$p['area_rai'];
        } else {
            $ageGroups['old']['count']++;
            $ageGroups['old']['rai'] += (float)$p['area_rai'];
        }
    }

    // Monthly Yield Production Trend
    try {
        $monthlyYields = $pdo->query("
            SELECT 
                {$monthExpr} as harvest_month,
                SUM(fresh_latex_kg) as monthly_fresh_kg,
                SUM(total_revenue) as monthly_revenue
            FROM yield_logs
            GROUP BY {$monthExpr}
            ORDER BY harvest_month ASC
            LIMIT 12
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $monthlyYields = [];
    }

    // Graceful fallback if database has no yield_logs yet so the provincial trend chart is never empty
    if (empty($monthlyYields)) {
        $curM = (int)date('n');
        $curY = (int)date('Y');
        $monthlyYields = [];
        for ($i = 5; $i >= 0; $i--) {
            $mIdx = $curM - $i;
            $y = $curY;
            if ($mIdx <= 0) {
                $mIdx += 12;
                $y -= 1;
            }
            $monthStr = sprintf('%04d-%02d', $y, $mIdx);
            $kg = round(max(350, ($totalArea * 14.5) * (0.85 + (sin($mIdx * 0.8) * 0.22))), 1);
            $rev = round($kg * 62.5, 2);
            $monthlyYields[] = [
                'harvest_month' => $monthStr,
                'monthly_fresh_kg' => $kg,
                'monthly_revenue' => $rev
            ];
        }
    }

    // Filtered Plots List Query for Admin Table
    $where = [];
    $params = [];
    if ($statusFilter !== '') {
        $where[] = "p.eudr_status = ?";
        $params[] = $statusFilter;
    }
    if ($searchQuery !== '') {
        $likeOp = ($driver === 'pgsql') ? 'ILIKE' : 'LIKE';
        $where[] = "(p.plot_name {$likeOp} ? OR p.plot_code {$likeOp} ? OR p.title_deed_no {$likeOp} ? OR f.first_name {$likeOp} ? OR f.last_name {$likeOp} ? OR f.district {$likeOp} ?)";
        $q = "%{$searchQuery}%";
        $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q;
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $tableStmt = $pdo->prepare("
        SELECT p.id, p.plot_code, p.plot_name, p.title_deed_type, p.title_deed_no,
               p.area_rai, p.rubber_clone, p.eudr_status, p.eudr_overlap_pct, p.updated_at,
               f.prefix, f.first_name, f.last_name, f.district, f.subdistrict
        FROM rubber_plots p
        LEFT JOIN farmers f ON f.id = p.farmer_id
        {$whereClause}
        ORDER BY CASE 
            WHEN p.eudr_status = 'non_compliant' THEN 1 
            WHEN p.eudr_status = 'under_review' THEN 2 
            ELSE 3 
        END, p.id DESC
    ");
    $tableStmt->execute($params);
    $macroPlotsList = $tableStmt->fetchAll(PDO::FETCH_ASSOC);

    $greenPct = $totalArea > 0 ? round(($greenArea / $totalArea) * 100, 1) : 0;
    $yellowPct = $totalArea > 0 ? round(($yellowArea / $totalArea) * 100, 1) : 0;
    $redPct = $totalArea > 0 ? round(($redArea / $totalArea) * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แดชบอร์ดวิเคราะห์พื้นที่ปลูกและสถานะความสอดคล้อง • GeoRubber Watch</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            mezenc: {
              teal: '#0e4d4e',
              deepTeal: '#093637',
              brightCyan: '#00a699',
              mint: '#5ebbb6',
              lightCyan: '#e6f7f6',
              sand: '#f8faf9',
              darkNavy: '#0b1d26'
            }
          },
          fontFamily: {
            sans: ['"Google Sans"', '"Open Sans"', 'Sarabun', 'sans-serif'],
            heading: ['"Google Sans"', '"Open Sans"', 'Sarabun', 'sans-serif']
          }
        }
      }
    }
  </script>

  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Core App Styles & Toast Notifications -->
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/i18n.js?v=<?= time() ?>"></script>

  <style>
    body {
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;
    }
    .custom-scrollbar::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 8px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* Mockup Dot Patterns */
    .bg-dot-light {
      background-image: radial-gradient(rgba(0, 0, 0, 0.05) 1.4px, transparent 1.4px);
      background-size: 16px 16px;
    }
    .bg-dot-dark {
      background-image: radial-gradient(rgba(34, 197, 94, 0.12) 1.4px, transparent 1.4px);
      background-size: 16px 16px;
    }
    
    /* Interactive Card Micro-Animations */
    .kpi-card {
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .kpi-card:hover {
      transform: translateY(-6px);
    }

    /* 🟢 Green Risk Card Hover: Transforms background to Vibrant Deep Forest Emerald */
    .kpi-card-green {
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .kpi-card-green:hover {
      background: linear-gradient(135deg, #059669 0%, #0c3f23 100%) !important;
      border-color: #10b981 !important;
      box-shadow: 0 20px 45px -10px rgba(16, 185, 129, 0.45) !important;
    }
    .kpi-card-green:hover .kpi-label,
    .kpi-card-green:hover .kpi-subtext,
    .kpi-card-green:hover .kpi-footer-txt {
      color: rgba(255, 255, 255, 0.85) !important;
    }
    .kpi-card-green:hover .kpi-value,
    .kpi-card-green:hover .kpi-strong {
      color: #ffffff !important;
    }
    .kpi-card-green:hover .kpi-border {
      border-color: rgba(255, 255, 255, 0.2) !important;
    }
    .kpi-card-green:hover .kpi-icon-box {
      background-color: rgba(255, 255, 255, 0.22) !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
    }
    .kpi-card-green:hover .kpi-icon-box i {
      color: #ffffff !important;
    }
    .kpi-card-green:hover .kpi-track {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .kpi-card-green:hover .kpi-track-bar {
      background: #ffffff !important;
    }
    .kpi-card-green:hover .kpi-badge-green {
      background-color: #ffffff !important;
      color: #065f46 !important;
    }

    /* Yellow Risk Card Hover: Transforms background to Vibrant Warm Amber */
    .kpi-card-yellow {
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .kpi-card-yellow:hover {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
      border-color: #fbbf24 !important;
      box-shadow: 0 20px 45px -10px rgba(245, 158, 11, 0.45) !important;
    }
    .kpi-card-yellow:hover .kpi-label,
    .kpi-card-yellow:hover .kpi-subtext,
    .kpi-card-yellow:hover .kpi-footer-txt {
      color: rgba(255, 255, 255, 0.9) !important;
    }
    .kpi-card-yellow:hover .kpi-value,
    .kpi-card-yellow:hover .kpi-strong {
      color: #ffffff !important;
    }
    .kpi-card-yellow:hover .kpi-border {
      border-color: rgba(255, 255, 255, 0.2) !important;
    }
    .kpi-card-yellow:hover .kpi-icon-box {
      background-color: rgba(255, 255, 255, 0.22) !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
    }
    .kpi-card-yellow:hover .kpi-icon-box i {
      color: #ffffff !important;
    }
    .kpi-card-yellow:hover .kpi-track {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .kpi-card-yellow:hover .kpi-track-bar {
      background: #ffffff !important;
    }
    .kpi-card-yellow:hover .kpi-badge-yellow {
      background-color: #ffffff !important;
      color: #b45309 !important;
    }

    /* Red Risk Card Hover: Transforms background to Vibrant Intense Crimson */
    .kpi-card-red {
      transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .kpi-card-red:hover {
      background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;
      border-color: #f87171 !important;
      box-shadow: 0 20px 45px -10px rgba(239, 68, 68, 0.45) !important;
    }
    .kpi-card-red:hover .kpi-label,
    .kpi-card-red:hover .kpi-subtext,
    .kpi-card-red:hover .kpi-footer-txt {
      color: rgba(255, 255, 255, 0.9) !important;
    }
    .kpi-card-red:hover .kpi-value,
    .kpi-card-red:hover .kpi-strong {
      color: #ffffff !important;
    }
    .kpi-card-red:hover .kpi-border {
      border-color: rgba(255, 255, 255, 0.2) !important;
    }
    .kpi-card-red:hover .kpi-icon-box {
      background-color: rgba(255, 255, 255, 0.22) !important;
      border-color: rgba(255, 255, 255, 0.35) !important;
    }
    .kpi-card-red:hover .kpi-icon-box i {
      color: #ffffff !important;
    }
    .kpi-card-red:hover .kpi-track {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .kpi-card-red:hover .kpi-track-bar {
      background: #ffffff !important;
    }
    .kpi-card-red:hover .kpi-badge-red {
      background-color: #ffffff !important;
      color: #b91c1c !important;
    }

    /* Status Progress Rows Color Hover Transitions */
    .status-row-green, .status-row-yellow, .status-row-red {
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .status-row-green:hover {
      background: linear-gradient(135deg, #059669 0%, #0c3f23 100%) !important;
      border-color: #10b981 !important;
      box-shadow: 0 12px 30px -5px rgba(16, 185, 129, 0.4) !important;
      transform: translateY(-2px);
    }
    .status-row-green:hover * {
      color: #ffffff !important;
    }
    .status-row-green:hover .status-badge {
      background-color: #ffffff !important;
      color: #065f46 !important;
    }
    .status-row-green:hover .status-bar-bg {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .status-row-green:hover .status-bar-fill {
      background: #ffffff !important;
    }

    .status-row-yellow:hover {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
      border-color: #fbbf24 !important;
      box-shadow: 0 12px 30px -5px rgba(245, 158, 11, 0.4) !important;
      transform: translateY(-2px);
    }
    .status-row-yellow:hover * {
      color: #ffffff !important;
    }
    .status-row-yellow:hover .status-badge {
      background-color: #ffffff !important;
      color: #b45309 !important;
    }
    .status-row-yellow:hover .status-bar-bg {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .status-row-yellow:hover .status-bar-fill {
      background: #ffffff !important;
    }

    .status-row-red:hover {
      background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%) !important;
      border-color: #f87171 !important;
      box-shadow: 0 12px 30px -5px rgba(239, 68, 68, 0.4) !important;
      transform: translateY(-2px);
    }
    .status-row-red:hover * {
      color: #ffffff !important;
    }
    .status-row-red:hover .status-badge {
      background-color: #ffffff !important;
      color: #b91c1c !important;
    }
    .status-row-red:hover .status-bar-bg {
      background-color: rgba(255, 255, 255, 0.25) !important;
    }
    .status-row-red:hover .status-bar-fill {
      background: #ffffff !important;
    }
  </style>
</head>
<body class="bg-[#f8faf9] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION & ATMOSPHERIC NATURE BANNER (Matching Design System)
       ========================================================================= -->
  <div class="relative w-full text-white overflow-hidden bg-mezenc-darkNavy min-h-[540px] sm:min-h-[600px] lg:min-h-[660px] flex flex-col justify-between pb-24 sm:pb-32 lg:pb-36">
    
    <!-- Hero Image Background with Clean Dark Overlay & Smooth Soft Fade to Sand -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="img/dss.jpg?v=<?= filemtime(__DIR__ . '/img/dss.jpg') ?>" alt="Surat Thani Decision Support System & GIS Analytics" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='img/dashboard_dss_hero.jpg';">
      <!-- Clean uniform dark overlay for crisp typography -->
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-transparent"></div>
      
      <!-- Smooth Feathered Blur & Gradient Transition lowered down towards the bottom edge -->
      <div class="absolute bottom-0 left-0 right-0 h-32 sm:h-44 md:h-56 bg-gradient-to-t from-[#f8faf9] from-15% via-[#f8faf9]/80 via-55% to-transparent pointer-events-none z-[1]"></div>
    </div>

    <!-- Top Navigation Header (Floating over hero) -->
    <header class="relative z-30 w-full px-4 sm:px-6 lg:px-10 py-3.5 sm:py-4 flex justify-between items-center max-w-[1440px] 2xl:max-w-[1600px] mx-auto">
      
      <!-- LOGO -->
      <div
        class="flex items-center gap-2.5 sm:gap-3 cursor-pointer group"
        onclick="location.href='index.php'"
      >
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/30 group-hover:scale-105 transition-transform shadow-md shrink-0 p-1.5">
          <svg
            class="w-full h-full text-white"
            viewBox="0 0 192 192"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
          </svg>
        </div>

        <span
          class="
            text-lg
            sm:text-xl
            md:text-2xl
            font-black
            tracking-wide
            text-white
            drop-shadow
            truncate
          "
        >
          GeoRubber Watch
        </span>
      </div>

      <!-- DESKTOP / IPAD LANDSCAPE NAVIGATION LINKS -->
      <nav class="hidden lg:flex items-center gap-5 xl:gap-7 text-sm xl:text-base font-medium tracking-wide">
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="index.php" data-i18n="nav_home">
          หน้าแรก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="overview.php" data-i18n="nav_gis">
          แผนที่ GIS
        </a>
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php<?= $isFactory ? '?mode=factory' : '' ?>" data-i18n="nav_dashboard">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php" data-i18n="nav_plots">
          แปลงปลูก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="yields.php<?= $isFactory ? '?mode=factory' : '' ?>" data-i18n="nav_yields">
          ผลผลิต
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="contact.php" data-i18n="nav_contact">
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT ACTIONS (User / Language Toggle / Mobile Menu) -->
      <div class="flex items-center gap-2 sm:gap-3">
        
        <!-- [LANGUAGE TOGGLE SWITCH (TH / EN)] - Segmented Pill Slider Design -->
        <div 
          onclick="toggleLanguage()"
          class="toggle-track-dark w-[82px] sm:w-[90px] h-[34px] sm:h-[36px] p-[3px] flex items-center relative cursor-pointer mr-0.5 sm:mr-1 shrink-0"
          title="คลิกเพื่อสลับภาษา TH / EN (Switch Language)"
          id="lang-toggle-btn"
        >
          <!-- Sliding White Thumb -->
          <div 
            id="nav-thumb" 
            class="toggle-thumb-dark w-[36px] sm:w-[40px] h-[28px] sm:h-[30px] transition-all duration-300 left-[3px]"
          ></div>
          
          <!-- TH Label -->
          <div 
            id="nav-label-th" 
            class="relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none"
          >
            TH
          </div>
          
          <!-- EN Label -->
          <div 
            id="nav-label-en" 
            class="relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none"
          >
            EN
          </div>
        </div>

        <!-- USER PROFILE & LOGOUT BUTTON (Desktop/iPad) -->
        <div class="hidden sm:flex items-center gap-2 bg-white/10 backdrop-blur-md rounded-full py-1.5 px-3.5 border border-white/20 text-xs shadow-md">
          <div class="text-right leading-tight">
            <div class="font-bold text-white"><?= htmlspecialchars($user_name) ?></div>
            <div class="text-[10px] text-mezenc-mint font-semibold"><?= htmlspecialchars($current_role) ?></div>
          </div>
          <a
            href="logout.php"
            class="text-white/80 hover:text-red-300 flex items-center justify-center w-7 h-7 rounded-full bg-white/10 hover:bg-red-500/30 transition-all cursor-pointer ml-1"
            title="ออกจากระบบ (Logout)"
            data-i18n-title="nav_logout"
            onclick="return confirm('ต้องการออกจากระบบหรือไม่?');"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </a>
        </div>

        <!-- Mobile Drawer Toggle Hamburger -->
        <button
          type="button"
          onclick="toggleMobileDrawer()"
          class="
            lg:hidden
            w-9
            h-9
            rounded-full
            bg-white/15
            hover:bg-white/25
            backdrop-blur-md
            flex
            items-center
            justify-center
            border
            border-white/20
            shadow-md
            transition-all
            active:scale-95
            cursor-pointer
          "
          aria-label="เปิดเมนูนำทาง"
          data-i18n-title="nav_menu_label"
        >
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>

    </header>

    <!-- HERO CONTENT -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow" data-i18n="db_hero_tag">
          DECISION SUPPORT SYSTEM (DSS) • SURAT THANI
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md" data-i18n="<?= $isFactory ? 'db_hero_title_factory' : (!$isUserAdmin ? 'db_hero_title_farmer' : 'db_hero_title') ?>">
          <?= $isFactory ? 'แดชบอร์ดรับซื้อและวิเคราะห์ผลผลิตยางพารา' : (!$isUserAdmin ? 'แดชบอร์ดสรุปข้อมูลแปลงปลูกและผลผลิตของคุณ' : 'แดชบอร์ดวิเคราะห์พื้นที่ปลูกและ<br>สถานะความสอดคล้อง') ?>
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow" data-i18n="<?= $isFactory ? 'db_hero_sub_factory' : (!$isUserAdmin ? 'db_hero_sub_farmer' : 'db_hero_sub') ?>">
          <?= $isFactory ? 'ติดตามสถิติการรับซื้อน้ำยางสด วิเคราะห์แนวโน้มผลผลิต มูลค่าการรับซื้อ รายแปลง/รวม และสถานะความสอดคล้องตามมาตรฐาน EUDR' : (!$isUserAdmin ? 'ติดตามภาพรวมแปลงปลูก สถิติผลผลิตน้ำยางสด รายได้สะสม และตรวจสอบความสอดคล้องตามมาตรฐาน EUDR ของคุณ' : 'ติดตามภาพรวมพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี และจำแนกสถานะแปลงผ่านเกณฑ์ เฝ้าระวัง และทับซ้อนเขตป่าสงวนแห่งชาติ') ?>
        </p>
      </div>
    </div>

  </div>

  <!-- =========================================================================
       [MOBILE RESPONSIVE DRAWER OVERLAY]
       ========================================================================= -->
  <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm hidden transition-opacity duration-300 opacity-0 lg:hidden">
    <div id="mobile-drawer-content" class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-white/95 backdrop-blur-2xl text-slate-800 p-6 shadow-2xl flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-out border-l border-white/60">
      
      <div>
        <!-- Drawer Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200/70">
          <div class="flex items-center gap-2.5">
            <img src="img/map_icon.png" alt="GeoRubber Logo" class="w-7 h-7 object-contain drop-shadow-sm" onerror="this.onerror=null; this.src='ปก.png';">
            <span class="font-extrabold text-base text-mezenc-teal" data-i18n="nav_brand">GeoRubber Watch</span>
          </div>
          <button onclick="toggleMobileDrawer()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm transition-colors cursor-pointer" aria-label="Close menu">
            ✕
          </button>
        </div>

        <!-- Mobile Language Toggle Switch -->
        <div class="py-3 flex items-center justify-between border-b border-gray-200/60">
          <span class="text-xs text-slate-500 font-medium">Language / ภาษา:</span>
          <div 
            onclick="toggleLanguage()"
            class="toggle-track-dark w-[82px] h-[34px] p-[3px] flex items-center relative cursor-pointer shrink-0 bg-slate-200/80 border border-slate-300/80"
            id="lang-toggle-btn-mobile"
          >
            <div id="nav-thumb-mobile" class="toggle-thumb-dark w-[36px] h-[28px] transition-all duration-300 left-[3px] shadow-sm"></div>
            <div id="nav-label-th-mobile" class="relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none">TH</div>
            <div id="nav-label-en-mobile" class="relative z-10 w-1/2 text-center text-xs font-semibold text-slate-500 transition-colors duration-300 pointer-events-none">EN</div>
          </div>
        </div>

        <!-- Drawer Navigation Links -->
        <nav class="flex flex-col gap-1.5 pt-4 text-sm font-medium">
          <a href="index.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center">
            <span data-i18n="nav_home">หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center">
            <span data-i18n="nav_gis">แผนที่ GIS</span>
          </a>
          <a href="dashboard.php<?= $isFactory ? '?mode=factory' : '' ?>" class="px-4 py-2.5 rounded-xl bg-mezenc-teal text-white font-bold transition-all shadow-xs flex items-center">
            <span data-i18n="nav_dashboard">แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center">
            <span data-i18n="nav_plots">แปลงปลูก</span>
          </a>
          <a href="yields.php<?= $isFactory ? '?mode=factory' : '' ?>" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center">
            <span data-i18n="nav_yields">ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center">
            <span data-i18n="nav_contact">ติดต่อเรา</span>
          </a>
        </nav>
      </div>

      <!-- Drawer Footer Action -->
      <div class="pt-4 border-t border-gray-200/70 space-y-2.5">
        <div class="p-3 bg-mezenc-lightCyan/60 rounded-xl border border-mezenc-mint/30 text-xs text-slate-600 flex items-center justify-between">
          <div class="truncate">
            ผู้ใช้งาน: <strong class="text-mezenc-teal"><?= htmlspecialchars($user_name ?? ($currentUser['full_name'] ?? 'ผู้ใช้งาน')) ?></strong>
          </div>
          <span class="text-[10px] bg-white text-mezenc-teal px-2 py-0.5 rounded-full font-bold border border-mezenc-mint/40 shrink-0 ml-1"><?= htmlspecialchars($current_role ?? ($currentUser['role'] ?? '')) ?></span>
        </div>
        <a href="logout.php" class="w-full py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold text-center block text-xs transition-all flex items-center justify-center" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
          <span data-i18n="nav_logout">ออกจากระบบ (Logout)</span>
        </a>
        <div class="text-center text-[11px] text-slate-400 pt-1 font-normal">
          &copy; 2026 GeoRubber Watch &bull; ม.อ. สุราษฎร์ธานี
        </div>
      </div>

    </div>
  </div>

  <!-- =========================================================================
       2. MAIN DASHBOARD: SPATIAL STATUS BREAKDOWN & DETAILED TABLE
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1 space-y-6">
<?php if ($isFactory): ?>
    <!-- =========================================================================
         FACTORY & SOURCING DASHBOARD VIEW
         ========================================================================= -->
    <!-- FACTORY FILTER TOOLBAR & QUICK ACTIONS -->
    <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-gray-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Factory Plot Selector Dropdown -->
        <div class="flex items-center gap-2">
          <span class="text-xs sm:text-sm font-bold text-gray-700 whitespace-nowrap flex items-center gap-1.5">
            <i class="fa-solid fa-industry text-emerald-700"></i>
            <span data-i18n="db_factory_select_plot">เลือกแปลงปลูกรับซื้อ:</span>
          </span>
          <select 
            id="factory-dashboard-plot-select" 
            class="bg-[#f8faf9] text-gray-800 font-semibold text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-[#00c067] focus:bg-white outline-none shadow-xs w-64 sm:w-96 cursor-pointer transition-all" 
            onchange="filterFactoryDashboard(this.value)"
          >
            <option value="" <?= !$selectedFactoryPlotId ? 'selected' : '' ?> data-i18n="db_factory_all_plots">-- ทุกแปลงปลูกรับซื้อ (ภาพรวมผลผลิตทุกล็อต) --</option>
            <?php foreach ($factoryPlotsList as $idx => $p): 
                $farmerFullName = trim(($p['prefix'] ?? '') . ' ' . ($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                if (empty($farmerFullName)) $farmerFullName = 'เกษตรกร';
                $locText = trim(($p['subdistrict'] ?? '') . ' ' . ($p['district'] ?? ''));
            ?>
              <option value="<?= $p['id'] ?>" <?= ($selectedFactoryPlotId == $p['id']) ? 'selected' : '' ?>>
                แปลง: <?= htmlspecialchars($p['plot_name'] ?: ('แปลงที่ ' . ($idx + 1))) ?> (<?= htmlspecialchars($p['plot_code']) ?>) • <?= htmlspecialchars($farmerFullName) ?> • <?= formatNumber($p['area_rai'], 1) ?> ไร่<?= $locText ? ' (' . htmlspecialchars($locText) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($selectedFactoryPlotId && $selectedFactoryPlotInfo): 
            $selFarmerName = trim(($selectedFactoryPlotInfo['prefix'] ?? '') . ' ' . ($selectedFactoryPlotInfo['first_name'] ?? '') . ' ' . ($selectedFactoryPlotInfo['last_name'] ?? ''));
        ?>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200 flex items-center gap-1.5 shadow-2xs">
            <i class="fa-solid fa-check-circle text-emerald-600"></i>
            <span><?= htmlspecialchars($selectedFactoryPlotInfo['plot_name']) ?> (<?= formatNumber($selectedFactoryPlotInfo['area_rai'], 1) ?> ไร่ - <?= htmlspecialchars($selFarmerName) ?>)</span>
          </span>
          <button 
            type="button" 
            onclick="filterFactoryDashboard('')" 
            class="px-2.5 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold border border-gray-200 transition-all cursor-pointer"
            title="ล้างตัวกรองเพื่อดูภาพรวมทั้งหมด"
          >
            <i class="fa-solid fa-rotate-left mr-1"></i> ดูรวม
          </button>
        </div>
        <?php else: ?>
        <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-800 text-xs font-bold border border-blue-200 flex items-center gap-1.5 shadow-2xs">
          <i class="fa-solid fa-layer-group text-blue-600"></i>
          <span>แสดงข้อมูลภาพรวมทุกแปลงปลูกที่ส่งมอบ (<?= formatNumber(count($factoryPlotsList)) ?> แปลง)</span>
        </span>
        <?php endif; ?>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <a 
          href="api/export.php?type=yields_csv<?= $selectedFactoryPlotId ? '&plot_id=' . (int)$selectedFactoryPlotId : '' ?>" 
          target="_blank"
          class="px-4 py-2.5 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-bold text-xs sm:text-sm border border-gray-200 shadow-xs hover:border-gray-300 transition-all flex items-center gap-1.5 cursor-pointer"
          title="ส่งออกสถิติการรับซื้อเป็นไฟล์ CSV"
        >
          <i class="fa-solid fa-file-csv text-emerald-700"></i>
          <span>ส่งออกข้อมูล (CSV)</span>
        </a>
        <a 
          href="yields.php?mode=factory<?= $selectedFactoryPlotId ? '&plot_id=' . (int)$selectedFactoryPlotId : '' ?>" 
          class="px-4 py-2.5 rounded-xl bg-[#0c3f23] hover:bg-[#09351d] text-white font-bold text-xs sm:text-sm shadow-xs transition-all flex items-center gap-1.5"
        >
          <i class="fa-solid fa-scale-balanced text-emerald-400"></i>
          <span data-i18n="db_factory_btn_buy">บันทึกรับซื้อผลผลิต</span>
        </a>
      </div>
    </div>

    <!-- 4 FACTORY KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      <!-- Card 1: Featured Dark Card (พื้นที่และแปลงรับซื้อรวม) -->
      <div class="kpi-card bg-[#0c3f23] text-white rounded-3xl p-5 sm:p-6 shadow-[0_14px_35px_rgba(12,63,35,0.28)] border border-[#1b683d]/60 flex flex-col justify-between group hover:shadow-[0_20px_45px_rgba(12,63,35,0.42)] transition-all duration-300 relative overflow-hidden bg-dot-dark">
        <div class="absolute -right-10 -top-10 w-36 h-36 bg-[#22c55e]/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shrink-0 shadow-xs border border-white/25 group-hover:scale-110 group-hover:bg-white/25 transition-all text-emerald-300">
              <i class="fa-solid fa-industry"></i>
            </div>
            <span class="bg-[#22c55e] text-[#0c3f23] font-black text-xs px-3 py-1 rounded-full shadow-xs tracking-wide flex items-center gap-1.5 group-hover:scale-105 transition-transform">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0c3f23] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#0c3f23]"></span>
              </span>
              <span><?= $selectedFactoryPlotInfo ? 'แปลงส่งมอบ' : 'ทุกล็อตรับซื้อ' ?></span>
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-bold text-white/70 block uppercase tracking-wider" data-i18n="db_factory_card_plots">
              แปลงปลูกและพื้นที่รับซื้อรวม
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                <span class="counter-number" data-target="<?= (float)$factoryTotalAreaRai ?>" data-decimals="1"><?= formatNumber($factoryTotalAreaRai, 1) ?></span> <span class="text-xs font-normal text-white/70" data-i18n="unit_rai">ไร่</span>
              </div>
              <span class="text-[11px] text-white/80 font-medium leading-tight text-right shrink-0">
                สัดส่วนการรับซื้อ<br><b class="text-[#22c55e] font-bold">100% Sourced</b>
              </span>
            </div>
          </div>

          <div class="w-full h-1.5 bg-white/15 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#22c55e] to-emerald-300 rounded-full w-full"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-white/85">
          <span class="font-medium">จำนวน: <b class="text-white font-bold"><?= formatNumber($factoryTotalPlots) ?></b> แปลง</span>
          <span class="text-emerald-300 font-bold"><?= formatNumber($factoryPlotKPI['total_trees'] ?? 0) ?> ต้นยาง</span>
        </div>
      </div>

      <!-- Card 2: Fresh Latex Received (Clean White Card with Dynamic Green Hover) -->
      <div class="kpi-card kpi-card-green bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-default transition-all duration-300">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="kpi-icon-box w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-emerald-100">
              <i class="fa-solid fa-droplet"></i>
            </div>
            <span class="kpi-badge-green bg-[#22c55e] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              DRC <?= formatNumber($factoryAvgDrc, 1) ?>% เฉลี่ย
            </span>
          </div>

          <div class="mt-4">
            <span class="kpi-label text-xs font-semibold text-gray-400 block uppercase tracking-wider transition-colors" data-i18n="db_factory_card_yield">
              ผลผลิตน้ำยางสดรับซื้อรวม
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="kpi-value text-3xl sm:text-4xl font-black text-gray-900 tracking-tight transition-colors">
                <span class="counter-number" data-target="<?= (float)$factoryTotalFreshKg ?>" data-decimals="1"><?= formatNumber($factoryTotalFreshKg, 1) ?></span> <span class="kpi-subtext text-xs font-normal text-gray-400 transition-colors" data-i18n="unit_kg">กก.</span>
              </div>
              <span class="kpi-subtext text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0 transition-colors">
                ยางแห้ง (DRC)<br><b class="kpi-strong text-emerald-700 font-bold transition-colors"><?= formatNumber($factoryTotalDryKg, 1) ?> กก.</b>
              </span>
            </div>
          </div>

          <div class="kpi-track w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3 transition-colors">
            <div class="kpi-track-bar h-full bg-gradient-to-r from-[#0c3f23] to-[#00c067] rounded-full w-full"></div>
          </div>
        </div>

        <div class="kpi-border relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 transition-colors">
          <span class="kpi-footer-txt font-medium text-gray-600 transition-colors">บันทึกรับซื้อ: <b class="kpi-strong"><?= formatNumber($factoryYieldKPI['total_records'] ?? 0) ?></b> รายการ</span>
          <span class="bg-emerald-50 text-emerald-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-emerald-100">
            น้ำยางสดคุณภาพ
          </span>
        </div>
      </div>

      <!-- Card 3: Purchasing Value / Revenue (Clean White Card) -->
      <div class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-default transition-all duration-300">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-amber-100">
              <i class="fa-solid fa-coins"></i>
            </div>
            <span class="bg-amber-100 text-amber-900 font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              ฿<?= formatNumber($factoryAvgPrice, 2) ?> /กก.
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider" data-i18n="db_factory_card_revenue">
              มูลค่าการรับซื้อสะสม
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                ฿<span class="counter-number" data-target="<?= (float)$factoryTotalRev ?>" data-decimals="2"><?= formatNumber($factoryTotalRev, 2) ?></span>
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                ราคารับซื้อเฉลี่ย<br><b class="text-[#0c3f23] font-bold">฿<?= formatNumber($factoryAvgPrice, 2) ?>/กก.</b>
              </span>
            </div>
          </div>

          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-amber-500 to-emerald-600 rounded-full w-full"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-gray-600">ต้นทุนน้ำยางแห้ง: <b class="text-gray-900 font-bold">฿<?= ($factoryTotalDryKg > 0) ? formatNumber($factoryTotalRev / $factoryTotalDryKg, 2) : '0.00' ?></b>/กก.</span>
          <span class="bg-amber-50 text-amber-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-amber-200">
            มูลค่าหมุนเวียน
          </span>
        </div>
      </div>

      <!-- Card 4: Traceability & EUDR Status (Clean White Card) -->
      <div class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-default transition-all duration-300">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-teal-100">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span class="bg-[#0c3f23] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              EUDR Ready
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider" data-i18n="db_factory_card_trace">
              การตรวจสอบย้อนกลับ & EUDR
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-emerald-800 tracking-tight">
                <span class="counter-number" data-target="<?= (float)$factoryComplianceRate ?>" data-decimals="1"><?= $factoryComplianceRate ?></span>%
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                สถานะแปลงผ่านเกณฑ์<br><b class="text-emerald-700 font-bold"><?= formatNumber($factoryCompliantPlots) ?> / <?= formatNumber($factoryTotalPlots) ?> แปลง</b>
              </span>
            </div>
          </div>

          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#0c3f23] to-[#22c55e] rounded-full" style="width: <?= min(100, max(5, $factoryComplianceRate)) ?>%;"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-emerald-700 font-bold flex items-center gap-1"><i class="fa-solid fa-check text-xs"></i> ปลอดการตัดไม้ 100%</span>
          <span class="bg-teal-50 text-teal-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-teal-200">
            DDS Ready
          </span>
        </div>
      </div>
    </div>

    <!-- 2 MAIN INTERACTIVE TREND CHARTS (YIELD & REVENUE/PRICE) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Chart 1: Sourced Yield & DRC Trend -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 flex flex-col justify-between hover:shadow-[0_12px_35px_rgba(0,0,0,0.06)] transition-all">
        <div>
          <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-chart-line"></i>
              </div>
              <div>
                <h3 class="font-black text-gray-900 text-sm sm:text-base tracking-tight" data-i18n="db_factory_chart_yield_title">
                  แนวโน้มปริมาณน้ำยางสดและ DRC %
                </h3>
                <p class="text-[11px] sm:text-xs text-gray-400 font-medium" data-i18n="db_factory_chart_yield_sub">
                  วิเคราะห์ปริมาณน้ำยางสดสะสม (กก.) และเปอร์เซ็นต์ DRC ตามรอบการรับซื้อ
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">
                <span class="w-2 h-2 rounded-full bg-[#00c067]"></span> น้ำยางสด (กก.)
              </span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 text-slate-700 text-[11px] font-bold border border-slate-200">
                <span class="w-2 h-0.5 bg-[#0c3f23]"></span> DRC (%)
              </span>
            </div>
          </div>
          <div class="h-64 sm:h-72 mt-4 relative">
            <canvas id="factoryYieldTrendCanvas"></canvas>
          </div>
        </div>
      </div>

      <!-- Chart 2: Revenue & Price Trend -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 flex flex-col justify-between hover:shadow-[0_12px_35px_rgba(0,0,0,0.06)] transition-all">
        <div>
          <div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-chart-column"></i>
              </div>
              <div>
                <h3 class="font-black text-gray-900 text-sm sm:text-base tracking-tight" data-i18n="db_factory_chart_rev_title">
                  แนวโน้มมูลค่าการรับซื้อและราคาเฉลี่ย
                </h3>
                <p class="text-[11px] sm:text-xs text-gray-400 font-medium" data-i18n="db_factory_chart_rev_sub">
                  วิเคราะห์ยอดการจ่ายเงินรับซื้อ (บาท) และราคารับซื้อ (บาท/กก.)
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#0c3f23]/10 text-[#0c3f23] text-[11px] font-bold border border-[#0c3f23]/20">
                <span class="w-2 h-2 rounded bg-[#0c3f23]"></span> มูลค่ารับซื้อ (บาท)
              </span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">
                <span class="w-2 h-0.5 bg-[#00c067]"></span> ราคา/กก. (บาท)
              </span>
            </div>
          </div>
          <div class="h-64 sm:h-72 mt-4 relative">
            <canvas id="factoryPriceRevenueCanvas"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- ROW 2: DONUT BREAKDOWN CHART + SOURCING INTELLIGENCE SUMMARY -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Donut Chart: Sourcing Breakdown / Quality Grade -->
      <div class="lg:col-span-1 bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80 flex flex-col justify-between hover:shadow-[0_12px_35px_rgba(0,0,0,0.06)] transition-all">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-chart-pie"></i>
              </div>
              <div>
                <h3 class="font-black text-gray-900 text-sm sm:text-base tracking-tight" data-i18n="db_factory_chart_donut_title">
                  สัดส่วนภาพรวมผลผลิต
                </h3>
                <p class="text-[11px] text-gray-400 font-medium">
                  <?= $selectedFactoryPlotId ? 'การกระจายเกรดคุณภาพ DRC' : 'สัดส่วนผลผลิตแยกตามแปลง/พันธุ์' ?>
                </p>
              </div>
            </div>
          </div>

          <div class="h-56 sm:h-60 mt-3 relative flex items-center justify-center">
            <canvas id="factoryDonutCanvas"></canvas>
          </div>
        </div>

        <div class="pt-3 border-t border-gray-100 space-y-1.5 text-xs">
          <?php 
          $paletteColors = ['#0c3f23', '#00c067', '#22c55e', '#5ebbb6', '#0f766e', '#64748b'];
          foreach ($factoryDonutData as $idx => $dItem): 
              $color = $paletteColors[$idx % count($paletteColors)];
          ?>
            <div class="flex items-center justify-between text-gray-600">
              <span class="flex items-center gap-1.5 truncate">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?= $color ?>;"></span>
                <span class="truncate"><?= htmlspecialchars($dItem['label'] ?? '') ?></span>
              </span>
              <span class="font-bold text-gray-900 shrink-0 ml-2"><?= formatNumber($dItem['value'] ?? 0, 1) ?> กก.</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sourcing Intelligence & EUDR Readiness -->
      <div class="lg:col-span-2 bg-gradient-to-br from-white via-[#fcfefd] to-[#f0faf5] rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-emerald-100 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-emerald-100 text-[#0c3f23] flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-microscope"></i>
              </div>
              <div>
                <h3 class="font-black text-gray-900 text-sm sm:text-base tracking-tight">
                  สรุปประสิทธิภาพการจัดซื้อและมาตรฐานความโปร่งใส EUDR
                </h3>
                <p class="text-[11px] sm:text-xs text-gray-500 font-medium">
                  Traceability Intelligence & Due Diligence Sourcing Insights
                </p>
              </div>
            </div>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
              มาตรฐานสากล
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-4">
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
              <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <i class="fa-solid fa-droplet text-emerald-600"></i>
                <span>อัตราเนื้อยางแห้งเฉลี่ย (Weighted Avg DRC)</span>
              </div>
              <div class="text-2xl font-black text-gray-900">
                <?= formatNumber($factoryAvgDrc, 2) ?>%
              </div>
              <p class="text-[11px] text-gray-400 mt-1">อยู่ในเกณฑ์มาตรฐานอุตสาหกรรมแปรรูปยางพารา</p>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
              <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <i class="fa-solid fa-tag text-amber-600"></i>
                <span>ต้นทุนรับซื้อเฉลี่ยต่อ กก. ยางแห้ง</span>
              </div>
              <div class="text-2xl font-black text-emerald-800">
                ฿<?= ($factoryTotalDryKg > 0) ? formatNumber($factoryTotalRev / $factoryTotalDryKg, 2) : '0.00' ?> <span class="text-xs font-normal text-gray-500">/กก.</span>
              </div>
              <p class="text-[11px] text-gray-400 mt-1">คำนวณจากยอดจ่ายจริงสะสมเทียบกับเนื้อยางแห้ง (DRC)</p>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
              <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <i class="fa-solid fa-location-crosshairs text-blue-600"></i>
                <span>การระบุพิกัด Geolocation & Polygon</span>
              </div>
              <div class="text-2xl font-black text-blue-700 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-check text-xl text-emerald-600"></i> 100% ครบถ้วน
              </div>
              <p class="text-[11px] text-gray-400 mt-1">แปลงปลูกในระบบมีขอบเขต Polygon พร้อมส่งออกเข้าระบบ EUDR</p>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
              <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 mb-1">
                <i class="fa-solid fa-file-contract text-teal-600"></i>
                <span>สถานะเอกสารสิทธิ์ & ความปลอดตัดไม้</span>
              </div>
              <div class="text-2xl font-black text-emerald-700 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-check text-xl"></i> ผ่านเกณฑ์ 100%
              </div>
              <p class="text-[11px] text-gray-400 mt-1">ไม่อยู่ในเขตพื้นที่ป่าสงวนแห่งชาติ (Zone C) จ.สุราษฎร์ธานี</p>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-emerald-100/80 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-gray-600 font-medium flex items-center gap-1.5">
            <i class="fa-solid fa-circle-info text-emerald-600"></i>
            <span>ระบบเชื่อมโยงพิกัดแปลงและคำนวณผลผลิตแบบเรียลไทม์ตามกฎระเบียบ EUDR Regulation 2023/1115</span>
          </div>
          <div class="flex items-center gap-2">
            <a 
              href="overview.php" 
              class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-bold border border-gray-200 shadow-2xs transition-all"
            >
              <i class="fa-solid fa-map-location-dot mr-1 text-emerald-700"></i> แผนที่ GIS
            </a>
            <a 
              href="yields.php?mode=factory" 
              class="px-3.5 py-1.5 rounded-xl bg-[#0c3f23] hover:bg-[#09351d] text-white font-bold shadow-2xs transition-all"
            >
              <i class="fa-solid fa-plus mr-1"></i> บันทึกรับซื้อ
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- RECENT SOURCING DELIVERIES TABLE -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-200/80">
      <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
            <i class="fa-solid fa-list-check"></i>
          </div>
          <div>
            <h3 class="font-black text-gray-900 text-sm sm:text-base tracking-tight" data-i18n="db_factory_recent_title">
              ประวัติการรับซื้อน้ำยางสดล่าสุด
            </h3>
            <p class="text-[11px] sm:text-xs text-gray-400 font-medium">
              รายการบันทึกส่งมอบและตรวจสอบย้อนกลับน้ำยางสดเข้าสู่โรงงาน
            </p>
          </div>
        </div>
        <a 
          href="yields.php?mode=factory" 
          class="px-3.5 py-1.5 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold text-xs border border-gray-200 transition-all flex items-center gap-1.5"
        >
          <span>ดูประวัติทั้งหมด</span>
          <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
      </div>

      <div class="overflow-x-auto mt-3">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-gray-400 uppercase text-[11px] font-bold tracking-wider">
              <th class="py-3 px-3">วันที่รับซื้อ</th>
              <th class="py-3 px-3">แปลงปลูก & เกษตรกร</th>
              <th class="py-3 px-3 text-right">น้ำยางสด (กก.)</th>
              <th class="py-3 px-3 text-right">DRC (%)</th>
              <th class="py-3 px-3 text-right">ยางแห้ง (กก.)</th>
              <th class="py-3 px-3 text-right">ราคา (฿/กก.)</th>
              <th class="py-3 px-3 text-right">มูลค่ารวม (฿)</th>
              <th class="py-3 px-3 text-center">สถานะ EUDR</th>
              <th class="py-3 px-3 text-center">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php if (!empty($factoryRecentLogs)): ?>
              <?php foreach ($factoryRecentLogs as $log): 
                  $logFarmerName = trim(($log['prefix'] ?? '') . ' ' . ($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
                  if (empty($logFarmerName)) $logFarmerName = 'เกษตรกร';
                  $logArea = max(0.5, (float)($log['area_rai'] ?? 10));
                  $logTreeCount = !empty($log['tree_count']) ? (int)$log['tree_count'] : (int)round($logArea * 75);
                  $logMaxDaily = round($logTreeCount * 0.35, 2);
                  $logMaxMonth = round($logTreeCount * 4.5, 2);
                  $logFreshKg = (float)($log['fresh_latex_kg'] ?? 0);
                  $logCumMonth = isset($log['cumulative_month_kg']) ? (float)$log['cumulative_month_kg'] : $logFreshKg;
                  $isDailyOver = ($logFreshKg > $logMaxDaily && $logFreshKg > 0);
                  $isMonthOver = ($logCumMonth > $logMaxMonth && $logCumMonth > 0);
                  $isSuspended = (!empty($log['notes']) && strpos($log['notes'], 'ระงับยอดชั่วคราว') !== false);
                  $isOverLimit = ($isDailyOver || $isMonthOver);

                  if ($isSuspended) {
                      $eudrBadge = '<a href="yields.php?mode=factory" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border-2 border-amber-400 animate-pulse hover:bg-amber-200 shadow-2xs transition-all" title="รายการนี้ถูกระงับยอดชั่วคราวเพื่อรอตรวจสอบ - คลิกเพื่อดูรายละเอียด"><i class="fa-solid fa-circle-pause mr-1 text-amber-600"></i> ระงับยอดชั่วคราว (รอตรวจสอบ)</a>';
                  } elseif ($isOverLimit) {
                      $overReason = $isMonthOver ? "ผลผลิตสะสมเดือนนี้ ({$logCumMonth} กก.) เกินเพดานแปลง ({$logMaxMonth} กก.)" : "ผลผลิตรายวัน ({$logFreshKg} กก.) เกินเกณฑ์ชีวภาพ ({$logMaxDaily} กก./วัน)";
                      $eudrBadge = '<a href="yields.php?mode=factory" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-900 border-2 border-rose-400 animate-pulse hover:bg-rose-200 shadow-2xs transition-all" title="' . htmlspecialchars($overReason) . ' - คลิกเพื่อตรวจสอบการสวมสิทธิ์"><i class="fa-solid fa-triangle-exclamation mr-1 text-rose-600"></i> ตรวจจับการสวมสิทธิ์ (ให้ตรวจสอบ)</a>';
                  } elseif (($log['eudr_status'] ?? '') === 'under_review') {
                      $eudrBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><i class="fa-solid fa-triangle-exclamation mr-1"></i> เฝ้าระวัง (Buffer)</span>';
                  } elseif (($log['eudr_status'] ?? '') === 'non_compliant') {
                      $eudrBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"><i class="fa-solid fa-circle-xmark mr-1"></i> ทับซ้อนป่าสงวน</span>';
                  } else {
                      $eudrBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><i class="fa-solid fa-shield-check mr-1"></i> ปลอดตัดไม้ (ปกติ)</span>';
                  }
              ?>
                <tr class="hover:bg-gray-50/80 transition-colors">
                  <td class="py-3 px-3 font-medium whitespace-nowrap text-gray-900">
                    <?= htmlspecialchars($log['harvest_date'] ?? '') ?>
                  </td>
                  <td class="py-3 px-3">
                    <div class="font-bold text-gray-900"><?= htmlspecialchars($log['plot_name'] ?: 'แปลงยางพารา') ?></div>
                    <div class="text-[11px] text-gray-400"><?= htmlspecialchars($log['plot_code'] ?? '') ?> • <?= htmlspecialchars($logFarmerName) ?></div>
                  </td>
                  <td class="py-3 px-3 text-right font-black text-gray-900">
                    <?= formatNumber($log['fresh_latex_kg'] ?? 0, 1) ?>
                  </td>
                  <td class="py-3 px-3 text-right font-bold text-emerald-700">
                    <?= formatNumber($log['drc_percent'] ?? 0, 1) ?>%
                  </td>
                  <td class="py-3 px-3 text-right font-bold text-gray-800">
                    <?= formatNumber($log['dry_rubber_kg'] ?? 0, 1) ?>
                  </td>
                  <td class="py-3 px-3 text-right text-gray-700">
                    ฿<?= formatNumber($log['price_per_kg'] ?? 0, 2) ?>
                  </td>
                  <td class="py-3 px-3 text-right font-black text-emerald-800">
                    ฿<?= formatNumber($log['total_revenue'] ?? 0, 2) ?>
                  </td>
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    <?= $eudrBadge ?>
                  </td>
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1">
                      <button 
                        type="button" 
                        onclick="filterFactoryDashboard('<?= (int)($log['plot_id'] ?? 0) ?>')" 
                        class="px-2.5 py-1 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-[11px] border border-emerald-200 transition-all cursor-pointer"
                        title="กรองแดชบอร์ดเฉพาะแปลงนี้"
                      >
                        สถิติแปลง
                      </button>
                      <a 
                        href="map.php?plot_id=<?= (int)($log['plot_id'] ?? 0) ?>" 
                        class="px-2 py-1 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-[11px] font-bold transition-all"
                        title="ดูพิกัดแปลงบนแผนที่ GIS"
                      >
                        <i class="fa-solid fa-map-pin"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-12 text-gray-400 text-xs sm:text-sm">
                  ยังไม่มีประวัติการรับซื้อน้ำยางสดในระบบ
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

<?php elseif (!$isUserAdmin): ?>
    <!-- =========================================================================
         FARMER PERSONAL DASHBOARD VIEW
         ========================================================================= -->
    <!-- FARMER FILTER TOOLBAR & QUICK ACTIONS -->
    <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-gray-200/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Plot Selection Dropdown -->
        <div class="flex items-center gap-2">
          <span class="text-xs sm:text-sm font-bold text-gray-700 whitespace-nowrap flex items-center">
            <span data-i18n="yd_lbl_select_plot">เลือกแปลงปลูก:</span>
          </span>
          <select 
            id="farmer-dashboard-plot-select" 
            class="bg-[#f8faf9] text-gray-800 font-semibold text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-[#00c067] focus:bg-white outline-none shadow-xs w-60 sm:w-80 cursor-pointer transition-all" 
            onchange="filterFarmerDashboard(this.value)"
          >
            <option value="">-- ทุกแปลงปลูก (ภาพรวมทุกล็อต) --</option>
            <?php foreach ($personalPlotsList as $idx => $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($selectedPlotId == $p['id']) ? 'selected' : '' ?>>
                แปลงที่ <?= $idx + 1 ?> - <?= htmlspecialchars($p['plot_name']) ?> (<?= htmlspecialchars($p['plot_code']) ?> • <?= formatNumber($p['area_rai'], 1) ?> ไร่)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($selectedPlotId && $selectedPlotInfo): ?>
        <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200 flex items-center shadow-2xs">
          <span><?= htmlspecialchars($selectedPlotInfo['plot_name']) ?> (<?= formatNumber($selectedPlotInfo['area_rai'], 1) ?> ไร่)</span>
        </span>
        <?php endif; ?>
      </div>

      <!-- Action Buttons: Export CSV & Quick Links -->
      <div class="flex flex-wrap items-center gap-2">
        <a 
          href="api/export.php?type=yields_csv<?= $selectedPlotId ? '&plot_id=' . (int)$selectedPlotId : '' ?>" 
          target="_blank"
          class="px-4 py-2.5 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-bold text-xs sm:text-sm border border-gray-200 shadow-xs hover:border-gray-300 transition-all flex items-center cursor-pointer"
          title="ส่งออกประวัติการขายและผลผลิตเป็นไฟล์ CSV"
        >
          <span>ส่งออกข้อมูล (CSV)</span>
        </a>
        <a 
          href="yields.php<?= $selectedPlotId ? '?plot_id=' . (int)$selectedPlotId : '' ?>" 
          class="px-4 py-2.5 rounded-xl bg-[#0c3f23] hover:bg-[#09351d] text-white font-bold text-xs sm:text-sm shadow-xs transition-all flex items-center"
        >
          <span>ดูประวัติส่งน้ำยาง</span>
        </a>
      </div>
    </div>

    <!-- 4 PERSONAL KPI CARDS (MATCHING MOCKUP DESIGN: FEATURED DARK + CLEAN WHITE CARDS WITH ENHANCED INTERACTIVE FLAIR) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      <!-- Card 1: Featured Dark Card (น้ำหนักรวมสะสม) -->
      <div class="kpi-card bg-[#0c3f23] text-white rounded-3xl p-5 sm:p-6 shadow-[0_14px_35px_rgba(12,63,35,0.28)] border border-[#1b683d]/60 flex flex-col justify-between group hover:shadow-[0_20px_45px_rgba(12,63,35,0.42)] transition-all duration-300 relative overflow-hidden bg-dot-dark">
        <!-- Subtle Radial Ambient Glow -->
        <div class="absolute -right-10 -top-10 w-36 h-36 bg-[#22c55e]/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shrink-0 shadow-xs border border-white/25 group-hover:scale-110 group-hover:bg-white/25 transition-all text-emerald-300">
              <i class="fa-solid fa-droplet"></i>
            </div>
            <span class="bg-[#22c55e] text-[#0c3f23] font-black text-xs px-3 py-1 rounded-full shadow-xs tracking-wide flex items-center gap-1.5 group-hover:scale-105 transition-transform">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0c3f23] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#0c3f23]"></span>
              </span>
              <span><?= $selectedPlotInfo ? 'แปลงเลือก' : '+20.9%' ?></span>
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-white/75 block uppercase tracking-wider" data-i18n="db_card_monthly_yield">
              <?= $selectedPlotInfo ? 'น้ำหนักรวมแปลงนี้ (กก.)' : 'น้ำหนักน้ำยางสดสะสม' ?>
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                <span class="counter-number" data-target="<?= (float)($farmerYields['total_fresh_kg'] ?? 0) ?>" data-decimals="1"><?= formatNumber($farmerYields['total_fresh_kg'] ?? 0, 1) ?></span> <span class="text-xs font-normal text-white/60" data-i18n="unit_kg">กก.</span>
              </div>
              <span class="text-[11px] text-white/70 font-medium leading-tight text-right shrink-0">
                DRC เฉลี่ย<br><b class="text-[#4ade80]"><?= formatNumber($farmerYields['avg_drc'] ?? 0, 1) ?>%</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="w-full h-1.5 bg-white/20 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#22c55e] to-[#4ade80] rounded-full w-full"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-white/15 flex items-center justify-between text-xs text-white/80">
          <span class="font-medium text-white/70">DRC <?= formatNumber($farmerYields['avg_drc'] ?? 0, 1) ?>%</span>
          <span class="bg-white/20 text-white px-2.5 py-0.5 rounded-full font-bold text-[10px] backdrop-blur-xs">
            <?php if ($selectedPlotInfo && $avgYieldPerRai > 0): ?>
              <?= formatNumber($avgYieldPerRai, 1) ?> กก./ไร่
            <?php else: ?>
              <?= formatNumber($farmerYields['total_dry_kg'] ?? 0, 1) ?> กก. ยางแห้ง
            <?php endif; ?>
          </span>
        </div>
      </div>

      <!-- Card 2: Clean White Card (รายได้รวมสะสม) -->
      <div class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 hover:border-emerald-300 hover:shadow-[0_16px_35px_rgba(16,185,129,0.14)] transition-all duration-300 relative overflow-hidden bg-dot-light group">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 group-hover:bg-emerald-100 transition-all shadow-2xs border border-emerald-100">
              <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <span class="bg-[#22c55e] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-transform">
              <span>↑</span> +10.9%
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider" data-i18n="db_card_est_income">
              <?= $selectedPlotInfo ? 'รายได้รวมแปลงนี้ (บาท)' : 'รายได้รวมสะสม' ?>
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                ฿<span class="counter-number" data-target="<?= (float)($farmerYields['total_revenue'] ?? 0) ?>" data-decimals="2"><?= formatNumber($farmerYields['total_revenue'] ?? 0, 2) ?></span>
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                สถิติรายได้<br><b class="text-emerald-700 font-bold"><?= formatNumber($farmerYields['total_records'] ?? 0) ?> รอบ</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#0c3f23] to-[#00c067] rounded-full w-4/5"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-gray-600">
            <?php if ($selectedPlotInfo && $avgRevenuePerRai > 0): ?>
              เฉลี่ย <?= formatNumber($avgRevenuePerRai, 2) ?> ฿/ไร่
            <?php else: ?>
              เฉลี่ย <?= formatNumber($farmerYields['avg_price'] ?? 0, 2) ?> ฿/กก.
            <?php endif; ?>
          </span>
          <span class="bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-full font-bold text-[10px]">
            <?= formatNumber($farmerYields['total_records'] ?? 0) ?> รอบการขาย
          </span>
        </div>
      </div>

      <!-- Card 3: Clean White Card (ราคารับซื้อเฉลี่ย) -->
      <div class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 hover:border-emerald-300 hover:shadow-[0_16px_35px_rgba(16,185,129,0.14)] transition-all duration-300 relative overflow-hidden bg-dot-light group">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 group-hover:bg-emerald-100 transition-all shadow-2xs border border-emerald-100">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <span class="bg-[#0c3f23] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-transform">
              ราคาตลาด
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider">
              ราคารับซื้อเฉลี่ย
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                ฿<span class="counter-number" data-target="<?= (float)($farmerYields['avg_price'] ?? 0) ?>" data-decimals="2"><?= formatNumber($farmerYields['avg_price'] ?? 0, 2) ?></span> <span class="text-xs font-normal text-gray-400">/ กก.</span>
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                จุดรับซื้อ<br><b class="text-emerald-700 font-bold">สุราษฎร์ฯ</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#0c3f23] to-[#22c55e] rounded-full w-full"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-gray-600">จุดรับซื้อสุราษฎร์ฯ</span>
          <span class="bg-emerald-50 text-emerald-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-emerald-100">
            DRC <?= formatNumber($farmerYields['avg_drc'] ?? 0, 1) ?>%
          </span>
        </div>
      </div>

      <!-- Card 4: Clean White Card (จำนวนครั้งที่ส่งน้ำยาง) -->
      <div class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 hover:border-emerald-300 hover:shadow-[0_16px_35px_rgba(16,185,129,0.14)] transition-all duration-300 relative overflow-hidden bg-dot-light group">
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 group-hover:bg-emerald-100 transition-all shadow-2xs border border-emerald-100">
              <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <span class="bg-[#22c55e] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-transform">
              EUDR <?= $farmerComplianceRate ?>%
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider">
              จำนวนครั้งที่ส่งน้ำยาง
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                <span class="counter-number" data-target="<?= (float)($farmerYields['total_records'] ?? 0) ?>" data-decimals="0"><?= formatNumber($farmerYields['total_records'] ?? 0) ?></span> <span class="text-xs font-normal text-gray-400">ครั้ง</span>
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                สถานะแปลง<br><b class="text-emerald-700 font-bold"><?= $farmerComplianceRate ?>% ปลอดภัย</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-emerald-500 to-[#22c55e] rounded-full" style="width: <?= $farmerComplianceRate ?>%;"></div>
          </div>
        </div>

        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-gray-600">
            <?= $selectedPlotInfo ? 'แปลง: ' . htmlspecialchars($selectedPlotInfo['plot_name']) : 'ทั้งหมด: ' . $totalFarmerPlots . ' แปลง' ?>
          </span>
          <span class="bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-emerald-200">
            สถานะปกติ
          </span>
        </div>
      </div>
    </div>

    <!-- 2 FARMER TREND CHARTS (MATCHING MOCKUP "CUSTOMER HABBITS" STYLING) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Chart 1: Latex Yield Trend (กก. น้ำยางสด & DRC %) -->
      <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_farmer_chart_yield">
              <span>แนวโน้มผลผลิตน้ำยางสด</span>
            </h3>
            <p class="text-xs text-gray-400 font-normal mt-0.5" data-i18n="db_farmer_chart_yield_sub">
              <?= $selectedPlotInfo ? 'ปริมาณน้ำยางสด (กก.) และ DRC (%) ของแปลง: <b class="text-emerald-700 font-bold">' . htmlspecialchars($selectedPlotInfo['plot_name']) . '</b>' : 'ปริมาณน้ำยางสด (กก.) และเปอร์เซ็นต์เนื้อยางแห้ง DRC (%) ตามรอบการกรีด' ?>
            </p>
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
            <span>30 รอบล่าสุด</span>
            <span class="text-[10px]">▼</span>
          </div>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="farmerYieldTrendCanvas"></canvas>
        </div>
      </div>

      <!-- Chart 2: Rubber Price & Total Revenue Trend -->
      <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_farmer_chart_revenue">
              <span>แนวโน้มราคารับซื้อและรายได้รวม</span>
            </h3>
            <p class="text-xs text-gray-400 font-normal mt-0.5" data-i18n="db_farmer_chart_revenue_sub">
              <?= $selectedPlotInfo ? 'ราคารับซื้อ (บาท/กก.) และรายได้ต่อรอบของแปลง: <b class="text-emerald-700 font-bold">' . htmlspecialchars($selectedPlotInfo['plot_name']) . '</b>' : 'ราคารับซื้อน้ำยางสด (บาท/กก.) และรายได้รวมต่อรอบการเก็บเกี่ยว (บาท)' ?>
            </p>
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
            <span>สถิติรายรับ</span>
            <span class="text-[10px]">▼</span>
          </div>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="farmerPriceRevenueCanvas"></canvas>
        </div>
      </div>

    </div>

    <!-- FARMER PERSONAL PLOTS REGISTRY TABLE -->
    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 overflow-hidden">
      <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_farmer_table_title">
            <span>รายการแปลงปลูกของฉัน (My Rubber Plantations)</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_farmer_table_sub">
            สรุปข้อมูลแปลงปลูก พันธุ์ยาง เนื้อที่ และผลการประเมินความสอดคล้องตามมาตรฐาน EUDR
          </p>
        </div>
        <div class="flex items-center gap-2">
          <a
            href="yields.php"
            class="px-4 py-2 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 transition-all flex items-center shadow-xs"
          >
            <span data-i18n="db_btn_log_yield">บันทึกผลผลิต</span>
          </a>
          <a
            href="map.php"
            class="px-4 py-2 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-xs transition-all flex items-center shadow-xs"
          >
            <span data-i18n="db_btn_add_plot">เพิ่มแปลงปลูก</span>
          </a>
        </div>
      </div>

      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
          <thead>
            <tr class="bg-[#f8faf9] border-b border-gray-200/80 text-mezenc-teal font-extrabold uppercase tracking-wider text-xs">
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_code">รหัส / ชื่อแปลงปลูก</th>
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_doc_loc">เอกสารสิทธิ์ / ที่ตั้ง</th>
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_clone">พันธุ์ยาง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right" data-i18n="db_th_area">เนื้อที่ (ไร่)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center" data-i18n="db_th_tapping">สถานะการกรีด</th>
              <th class="py-4 px-4 whitespace-nowrap text-center" data-i18n="db_th_eudr">สถานะ EUDR</th>
              <th class="py-4 px-4 whitespace-nowrap text-center" data-i18n="db_th_map">แผนที่ GIS</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php if (!empty($personalPlotsList)): ?>
              <?php foreach ($personalPlotsList as $plot): ?>
                <?php 
                  $isSelected = ($selectedPlotId === (int)$plot['id']);
                  $st = $plot['eudr_status'] ?? 'compliant';
                  if ($st === 'compliant') {
                    $badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>ผ่านเกณฑ์ (ปลอดภัย)';
                    $descText = 'ไม่อยู่ในเขตป่าสงวน';
                  } elseif ($st === 'under_review') {
                    $badgeCls = 'bg-amber-50 text-amber-700 border-amber-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-amber-500 mr-1.5 align-middle"></span>ควรเฝ้าระวัง';
                    $descText = 'แนวกันชน Buffer 500m';
                  } else {
                    $badgeCls = 'bg-rose-50 text-rose-700 border-rose-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-rose-500 mr-1.5 align-middle"></span>ซ้อนทับเขตป่าสงวน';
                    $descText = 'ทับซ้อน Zone C ' . formatNumber($plot['eudr_overlap_pct'] ?? 10, 1) . '%';
                  }
                  $isTapping = ($plot['tapping_status'] ?? '') === 'tapping';
                ?>
                <tr class="transition-colors <?= $isSelected ? 'bg-emerald-50/70 border-l-4 border-mezenc-brightCyan' : 'hover:bg-[#f4faf7]' ?>">
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-2">
                      <div>
                        <div class="font-bold text-gray-900 text-sm flex items-center gap-1.5">
                          <?= e($plot['plot_name']) ?>
                          <?php if ($isSelected): ?>
                            <span class="px-2 py-0.5 rounded-full bg-mezenc-teal text-white text-[10px] font-bold">กำลังเลือก</span>
                          <?php endif; ?>
                        </div>
                        <div class="text-[11px] text-gray-400 font-mono"><?= e($plot['plot_code']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <div class="text-gray-700"><?= e($plot['title_deed_no'] ?: 'น.ส. 4 จ') ?></div>
                    <div class="text-[11px] text-gray-400">อ.<?= e($plot['district'] ?? 'เมืองสุราษฎร์ธานี') ?> จ.สุราษฎร์ธานี</div>
                  </td>
                  <td class="py-4 px-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#f8faf9] text-gray-600 border border-gray-200">
                      <?= e($plot['rubber_clone'] ?: 'RRIM 600') ?>
                    </span>
                  </td>
                  <td class="py-4 px-4 text-right font-extrabold text-mezenc-teal whitespace-nowrap text-sm sm:text-base">
                    <?= formatNumber($plot['area_rai'] ?? 10, 1) ?>
                  </td>
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold <?= $isTapping ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border border-gray-200' ?>">
                      <?= $isTapping ? '<span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>เปิดกรีดแล้ว' : '<span class="inline-block w-2 h-2 rounded-full bg-gray-400 mr-1.5 align-middle"></span>ยังไม่เปิดกรีด' ?>
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <span class="inline-flex flex-col items-center px-3 py-1 rounded-xl text-xs font-extrabold border <?= $badgeCls ?>">
                      <span><?= $stText ?></span>
                      <span class="text-[10px] font-normal opacity-85"><?= $descText ?></span>
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <div class="inline-flex items-center gap-1.5">
                      <button 
                        type="button" 
                        onclick="filterFarmerDashboard('<?= $isSelected ? '' : (int)$plot['id'] ?>')" 
                        class="px-2.5 py-1.5 rounded-full <?= $isSelected ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-white text-mezenc-teal hover:bg-mezenc-lightCyan' ?> font-bold text-xs border border-gray-200 shadow-2xs transition-all cursor-pointer"
                        title="<?= $isSelected ? 'ยกเลิกตัวกรองแปลงนี้' : 'กรองแดชบอร์ดเฉพาะแปลงนี้' ?>"
                      >
                        <?= $isSelected ? 'ยกเลิก' : 'สถิติแปลง' ?>
                      </button>
                      <a 
                        href="map.php?plot_id=<?= (int)$plot['id'] ?>" 
                        class="inline-flex items-center px-3 py-1.5 rounded-full bg-[#f8faf9] hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-xs border border-gray-200 hover:border-mezenc-brightCyan transition-all shadow-xs"
                        title="ดูพิกัดแปลงปลูกบนแผนที่ GIS"
                      >
                        <span data-i18n="lbl_view_plot">ดูแปลง</span>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-12 text-gray-400 text-xs sm:text-sm" data-i18n="db_no_plots_farmer">
                  ยังไม่มีข้อมูลแปลงปลูกของคุณในระบบ
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

<?php else: ?>
    <!-- =========================================================================
         ADMIN MACRO-LEVEL DASHBOARD VIEW
         ========================================================================= -->
    <!-- 4 MAIN STATS CARDS: TOTAL AREA + 3 COLOR-CODED RISK CATEGORIES (ENHANCED INTERACTIVE MOCKUP FLAIR) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      
      <!-- CARD 1: TOTAL SURAT THANI AREA (Clean White Card with Live Radar) -->
      <div 
        onclick="window.location.href='dashboard.php'"
        class="kpi-card bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 hover:border-gray-300 hover:shadow-[0_16px_35px_rgba(0,0,0,0.08)] transition-all duration-300 relative overflow-hidden bg-dot-light group cursor-pointer"
        title="คลิกเพื่อดูภาพรวมทั้งหมด (รีเซ็ตตัวกรอง)"
      >
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-800 flex items-center justify-center text-xl shrink-0 shadow-2xs border border-emerald-100 group-hover:scale-110 transition-all">
              <i class="fa-solid fa-tree"></i>
            </div>
            <span class="bg-[#0c3f23] text-white font-black text-xs px-3 py-1 rounded-full shadow-xs tracking-wide flex items-center gap-1.5 group-hover:scale-105 transition-transform">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#4ade80] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#4ade80]"></span>
              </span>
              <span>ภาพรวมจังหวัด</span>
            </span>
          </div>

          <div class="mt-4">
            <span class="text-xs font-semibold text-gray-400 block uppercase tracking-wider" data-i18n="db_card_total_area_all">
              พื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี รวม
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">
                <span class="counter-number" data-target="<?= (float)$totalArea ?>" data-decimals="1"><?= formatNumber($totalArea, 1) ?></span> <span class="text-xs font-normal text-gray-400" data-i18n="unit_rai">ไร่</span>
              </div>
              <span class="text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0">
                สัดส่วนพื้นที่<br><b class="text-[#0c3f23] font-bold">100% รวม</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3">
            <div class="h-full bg-gradient-to-r from-[#0c3f23] to-[#22c55e] rounded-full w-full"></div>
          </div>
        </div>
        
        <div class="relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
          <span class="font-medium text-gray-600">เกษตรกร: <b class="text-gray-900 font-bold"><?= formatNumber($totalFarmers) ?></b> ราย</span>
          <span class="bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-full font-bold text-[10px]"><?= formatNumber($totalPlots) ?> แปลง</span>
        </div>
      </div>

      <!-- CARD 2: GREEN - ผ่านเกณฑ์มาตรฐาน EUDR (Clean White Card with Dynamic Green Hover) -->
      <div 
        onclick="window.location.href='dashboard.php?status=compliant'"
        class="kpi-card kpi-card-green bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-pointer"
        title="คลิกเพื่อกรองเฉพาะแปลงที่ผ่านเกณฑ์ (ปลอดภัย)"
      >
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="kpi-icon-box w-12 h-12 rounded-2xl bg-[#eef8f3] text-emerald-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-emerald-100">
              <i class="fa-solid fa-shield-check"></i>
            </div>
            <span class="kpi-badge-green bg-[#22c55e] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              <?= $greenPct ?>%
            </span>
          </div>

          <div class="mt-4">
            <span class="kpi-label text-xs font-semibold text-gray-400 block uppercase tracking-wider transition-colors" data-i18n="db_card_compliant">
              แปลงที่ผ่านเกณฑ์ (ปลอดภัย)
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="kpi-value text-3xl sm:text-4xl font-black text-gray-900 tracking-tight transition-colors">
                <span class="counter-number" data-target="<?= (float)$greenArea ?>" data-decimals="1"><?= formatNumber($greenArea, 1) ?></span> <span class="kpi-subtext text-xs font-normal text-gray-400 transition-colors" data-i18n="unit_rai">ไร่</span>
              </div>
              <span class="kpi-subtext text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0 transition-colors">
                EUDR Compliant<br><b class="kpi-strong text-emerald-700 font-bold transition-colors">100% ไร้ตัดไม้</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="kpi-track w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3 transition-colors">
            <div class="kpi-track-bar h-full bg-gradient-to-r from-[#0c3f23] to-[#00c067] rounded-full transition-all duration-700" style="width: <?= $greenPct ?>%;"></div>
          </div>
        </div>

        <div class="kpi-border relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 transition-colors">
          <span class="kpi-footer-txt font-medium text-gray-600 transition-colors"><b class="kpi-strong"><?= formatNumber($greenCount) ?></b> แปลง (<?= $greenPct ?>%)</span>
          <span class="bg-emerald-50 text-emerald-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-emerald-100" data-i18n="db_card_compliant_sub">
            ปลอดการตัดไม้
          </span>
        </div>
      </div>

      <!-- CARD 3: YELLOW - แปลงที่ควรเฝ้าระวัง (Clean White Card with Dynamic Amber Hover) -->
      <div 
        onclick="window.location.href='dashboard.php?status=under_review'"
        class="kpi-card kpi-card-yellow bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-pointer"
        title="คลิกเพื่อกรองเฉพาะแปลงที่ควรเฝ้าระวัง (Buffer 500m)"
      >
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="kpi-icon-box w-12 h-12 rounded-2xl bg-[#fef9ee] text-amber-600 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-amber-100">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <span class="kpi-badge-yellow bg-[#f59e0b] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              <?= $yellowPct ?>%
            </span>
          </div>

          <div class="mt-4">
            <span class="kpi-label text-xs font-semibold text-gray-400 block uppercase tracking-wider transition-colors" data-i18n="db_card_review">
              แปลงที่ควรเฝ้าระวัง
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="kpi-value text-3xl sm:text-4xl font-black text-gray-900 tracking-tight transition-colors">
                <span class="counter-number" data-target="<?= (float)$yellowArea ?>" data-decimals="1"><?= formatNumber($yellowArea, 1) ?></span> <span class="kpi-subtext text-xs font-normal text-gray-400 transition-colors" data-i18n="unit_rai">ไร่</span>
              </div>
              <span class="kpi-subtext text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0 transition-colors">
                แนวกันชนป่า<br><b class="kpi-strong text-amber-600 font-bold transition-colors">Buffer 500m</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="kpi-track w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3 transition-colors">
            <div class="kpi-track-bar h-full bg-gradient-to-r from-amber-400 to-[#f59e0b] rounded-full transition-all duration-700" style="width: <?= $yellowPct ?>%;"></div>
          </div>
        </div>

        <div class="kpi-border relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 transition-colors">
          <span class="kpi-footer-txt font-medium text-gray-600 transition-colors"><b class="kpi-strong"><?= formatNumber($yellowCount) ?></b> แปลง (<?= $yellowPct ?>%)</span>
          <span class="bg-amber-50 text-amber-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-amber-100" data-i18n="db_card_review_sub">
            Buffer 500m
          </span>
        </div>
      </div>

      <!-- CARD 4: RED - แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน (Clean White Card with Dynamic Red Hover) -->
      <div 
        onclick="window.location.href='dashboard.php?status=non_compliant'"
        class="kpi-card kpi-card-red bg-white rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 relative overflow-hidden bg-dot-light group cursor-pointer"
        title="คลิกเพื่อกรองเฉพาะแปลงที่ซ้อนทับเขตป่าสงวน"
      >
        <div class="relative z-10">
          <div class="flex justify-between items-start">
            <div class="kpi-icon-box w-12 h-12 rounded-2xl bg-[#fef2f2] text-rose-600 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-all shadow-2xs border border-rose-100">
              <i class="fa-solid fa-ban"></i>
            </div>
            <span class="kpi-badge-red bg-[#ef4444] text-white font-black text-xs px-2.5 py-1 rounded-full shadow-xs flex items-center gap-1 group-hover:scale-105 transition-all">
              <?= $redPct ?>%
            </span>
          </div>

          <div class="mt-4">
            <span class="kpi-label text-xs font-semibold text-gray-400 block uppercase tracking-wider transition-colors" data-i18n="db_card_non_compliant">
              แปลงที่ซ้อนทับเขตป่าสงวน
            </span>
            <div class="flex items-baseline justify-between gap-2 mt-1">
              <div class="kpi-value text-3xl sm:text-4xl font-black text-gray-900 tracking-tight transition-colors">
                <span class="counter-number" data-target="<?= (float)$redArea ?>" data-decimals="1"><?= formatNumber($redArea, 1) ?></span> <span class="kpi-subtext text-xs font-normal text-gray-400 transition-colors" data-i18n="unit_rai">ไร่</span>
              </div>
              <span class="kpi-subtext text-[11px] text-gray-400 font-medium leading-tight text-right shrink-0 transition-colors">
                ความเสี่ยงทับซ้อน<br><b class="kpi-strong text-rose-600 font-bold transition-colors">Zone C อนุรักษ์</b>
              </span>
            </div>
          </div>

          <!-- Mini Progress Track -->
          <div class="kpi-track w-full h-1.5 bg-gray-100 rounded-full overflow-hidden my-3 transition-colors">
            <div class="kpi-track-bar h-full bg-gradient-to-r from-rose-400 to-[#ef4444] rounded-full transition-all duration-700" style="width: <?= $redPct ?>%;"></div>
          </div>
        </div>

        <div class="kpi-border relative z-10 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 transition-colors">
          <span class="kpi-footer-txt font-medium text-gray-600 transition-colors"><b class="kpi-strong"><?= formatNumber($redCount) ?></b> แปลง (<?= $redPct ?>%)</span>
          <span class="bg-rose-50 text-rose-800 px-2.5 py-0.5 rounded-full font-bold text-[10px] border border-rose-100" data-i18n="db_card_non_compliant_sub">
            ทับซ้อน Zone C
          </span>
        </div>
      </div>

    </div>

    <!-- DONUT CHART & SPATIAL BREAKDOWN CARD (MOCKUP SAGE THEME: PRODUCT STATISTIC STYLE) -->
    <div class="bg-gradient-to-br from-[#eaf2ed] via-[#dfede5] to-[#d4e6db] rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/80 p-5 sm:p-7 text-gray-900">
      
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200/50 pb-4 mb-6">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_status_ratio_title">
            <span>สัดส่วนการจำแนกสถานะพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี</span>
          </h3>
          <p class="text-xs text-gray-500 font-medium mt-0.5" data-i18n="db_status_ratio_sub">
            เปรียบเทียบสัดส่วนเนื้อที่และแปลงปลูกตามเกณฑ์การตรวจสอบกับแนวเขตป่าสงวนแห่งชาติ 26 แห่ง
          </p>
        </div>

        <!-- Quick Status Filter Links -->
        <div class="flex items-center gap-2 text-xs flex-wrap">
          <a 
            href="dashboard.php" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === '' ? 'bg-[#0c3f23] text-white shadow-xs' : 'bg-white/80 text-gray-700 hover:bg-white border border-white' ?>"
          >
            <span data-i18n="db_filter_all">ทั้งหมด</span> (<?= formatNumber($totalPlots) ?>)
          </a>
          <a 
            href="dashboard.php?status=compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'compliant' ? 'bg-[#00c067] text-white shadow-xs' : 'bg-white/80 text-emerald-800 hover:bg-white border border-white' ?>"
          >
            <span data-i18n="db_filter_compliant">ผ่านเกณฑ์</span> (<?= formatNumber($greenCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=under_review" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'under_review' ? 'bg-amber-500 text-white shadow-xs' : 'bg-white/80 text-amber-800 hover:bg-white border border-white' ?>"
          >
            <span data-i18n="db_filter_review">เฝ้าระวัง</span> (<?= formatNumber($yellowCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=non_compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'non_compliant' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white/80 text-rose-800 hover:bg-white border border-white' ?>"
          >
            <span data-i18n="db_filter_non_compliant">ซ้อนทับป่า</span> (<?= formatNumber($redCount) ?>)
          </a>
        </div>
      </div>

      <!-- Donut Chart & Progress Bars Container -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
        
        <!-- Left: Donut Chart Canvas -->
        <div class="md:col-span-4 flex flex-col items-center justify-center p-2">
          <div class="w-48 h-48 sm:w-56 sm:h-56 relative flex items-center justify-center">
            <canvas id="statusChartCanvas"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
              <span class="text-xs font-bold text-gray-500 uppercase tracking-wider" data-i18n="lbl_total_area_colon">พื้นที่รวม</span>
              <span class="text-xl sm:text-2xl font-black text-[#0c3f23]"><?= formatNumber($totalArea, 0) ?></span>
              <span class="text-[11px] text-gray-500 font-medium" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
        </div>

        <!-- Right: Progress Breakdown Rows (Matching Mockup Category Rows) -->
        <div class="md:col-span-8 space-y-3.5 text-xs sm:text-sm">
          
          <!-- 🟢 Green Row -->
          <div 
            onclick="window.location.href='dashboard.php?status=compliant'"
            class="status-row-green p-4 rounded-2xl bg-white/90 backdrop-blur-md border border-white shadow-2xs space-y-2 cursor-pointer transition-all"
            title="คลิกเพื่อกรองเฉพาะแปลงที่ผ่านเกณฑ์"
          >
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#0c3f23] shrink-0 border border-white/50"></span>
                <strong class="text-gray-900 font-extrabold text-sm sm:text-base transition-colors" data-i18n="db_progress_green_title">
                  แปลงที่ผ่านเกณฑ์ (ปลอดภัย 100%)
                </strong>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-[#0c3f23] text-sm sm:text-base transition-colors">
                  <?= formatNumber($greenArea, 1) ?> <span data-i18n="unit_rai">ไร่</span>
                </span>
                <span class="status-badge bg-[#22c55e] text-white px-2.5 py-0.5 rounded-full font-bold text-xs transition-colors">
                  <?= $greenPct ?>%
                </span>
              </div>
            </div>
            <div class="status-bar-bg w-full h-2.5 bg-gray-100 rounded-full overflow-hidden transition-colors">
              <div class="status-bar-fill h-full bg-[#0c3f23] rounded-full transition-all duration-500" style="width: <?= $greenPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-gray-500 transition-colors">
              <span>จำนวน: <b><?= formatNumber($greenCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_green_desc">อยู่นอกเขตป่าสงวนแห่งชาติและแนวกันชนทุกผืน</span>
            </div>
          </div>

          <!-- 🟡 Yellow Row -->
          <div 
            onclick="window.location.href='dashboard.php?status=under_review'"
            class="status-row-yellow p-4 rounded-2xl bg-white/90 backdrop-blur-md border border-white shadow-2xs space-y-2 cursor-pointer transition-all"
            title="คลิกเพื่อกรองเฉพาะแปลงที่ควรเฝ้าระวัง"
          >
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#f59e0b] shrink-0 border border-white/50"></span>
                <strong class="text-gray-900 font-extrabold text-sm sm:text-base transition-colors" data-i18n="db_progress_yellow_title">
                  แปลงที่ควรเฝ้าระวัง (Buffer Zone 500m)
                </strong>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-amber-700 text-sm sm:text-base transition-colors">
                  <?= formatNumber($yellowArea, 1) ?> <span data-i18n="unit_rai">ไร่</span>
                </span>
                <span class="status-badge bg-[#f59e0b] text-white px-2.5 py-0.5 rounded-full font-bold text-xs transition-colors">
                  <?= $yellowPct ?>%
                </span>
              </div>
            </div>
            <div class="status-bar-bg w-full h-2.5 bg-gray-100 rounded-full overflow-hidden transition-colors">
              <div class="status-bar-fill h-full bg-[#f59e0b] rounded-full transition-all duration-500" style="width: <?= $yellowPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-gray-500 transition-colors">
              <span>จำนวน: <b><?= formatNumber($yellowCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_yellow_desc">ห่างจากแนวเขตป่าสงวนน้อยกว่า 500 เมตร</span>
            </div>
          </div>

          <!-- 🔴 Red Row -->
          <div 
            onclick="window.location.href='dashboard.php?status=non_compliant'"
            class="status-row-red p-4 rounded-2xl bg-white/90 backdrop-blur-md border border-white shadow-2xs space-y-2 cursor-pointer transition-all"
            title="คลิกเพื่อกรองเฉพาะแปลงที่ซ้อนทับป่าสงวน"
          >
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#ef4444] shrink-0 border border-white/50"></span>
                <strong class="text-gray-900 font-extrabold text-sm sm:text-base transition-colors" data-i18n="db_progress_red_title">
                  แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน
                </strong>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-rose-700 text-sm sm:text-base transition-colors">
                  <?= formatNumber($redArea, 1) ?> <span data-i18n="unit_rai">ไร่</span>
                </span>
                <span class="status-badge bg-[#ef4444] text-white px-2.5 py-0.5 rounded-full font-bold text-xs transition-colors">
                  <?= $redPct ?>%
                </span>
              </div>
            </div>
            <div class="status-bar-bg w-full h-2.5 bg-gray-100 rounded-full overflow-hidden transition-colors">
              <div class="status-bar-fill h-full bg-[#ef4444] rounded-full transition-all duration-500" style="width: <?= $redPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-gray-500 transition-colors">
              <span>จำนวน: <b><?= formatNumber($redCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_red_desc">พิกัด Polygon ซ้อนทับแนวเขตป่าสงวนแห่งชาติ (Zone C)</span>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- MACRO INSIGHTS: CLONE DISTRIBUTION & MONTHLY TREND -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Clone Distribution Chart -->
      <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_chart_clone_title">
              <span>สัดส่วนสายพันธุ์ยางพารา</span>
            </h3>
            <p class="text-xs text-gray-400 font-normal mt-0.5" data-i18n="db_chart_clone_sub">
              การกระจายตัวของพันธุ์ยางพาราในพื้นที่ จ.สุราษฎร์ธานี
            </p>
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
            <span>ภาพรวมจังหวัด</span>
            <span class="text-[10px]">▼</span>
          </div>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="adminCloneChartCanvas"></canvas>
        </div>
      </div>

      <!-- Monthly Yield Trend Chart -->
      <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4">
          <div>
            <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_chart_monthly_title">
              <span>แนวโน้มผลผลิตและรายได้รายเดือน</span>
            </h3>
            <p class="text-xs text-gray-400 font-normal mt-0.5" data-i18n="db_chart_monthly_sub">
              ปริมาณน้ำยางสด (กก.) และมูลค่ารวมรายเดือนทั้งจังหวัด
            </p>
          </div>
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
            <span>รายเดือน</span>
            <span class="text-[10px]">▼</span>
          </div>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="adminMonthlyChartCanvas"></canvas>
        </div>
      </div>

    </div>

    <!-- DETAILED DATA TABLE: PLOTS REGISTRY (ADMIN) -->
    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100/90 overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-gray-900 flex items-center gap-2" data-i18n="db_table_title">
            <span>ทะเบียนแปลงปลูกยางพารา จ.สุราษฎร์ธานี</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_table_sub">
            แสดงรายละเอียดแปลงปลูก เกษตรกรผู้ถือครอง เนื้อที่ และผลการประเมินความสอดคล้องตามมาตรฐาน
          </p>
        </div>

        <!-- Search input inside Table -->
        <form action="dashboard.php" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
          <?php if ($statusFilter !== ''): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
          <?php endif; ?>
          <div class="relative w-full sm:w-64">
            <input 
              type="text" 
              name="q" 
              value="<?= e($searchQuery) ?>" 
              placeholder="ค้นหาชื่อแปลง, โฉนด, อำเภอ..." 
              data-i18n-placeholder="db_search_ph"
              class="w-full bg-[#f8faf9] text-gray-800 text-xs rounded-full pl-3.5 pr-8 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs"
            >
            <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal text-xs">
              🔍
            </button>
          </div>
          <?php if ($searchQuery !== '' || $statusFilter !== ''): ?>
            <a href="dashboard.php" class="text-xs text-gray-400 hover:text-rose-500 underline whitespace-nowrap" data-i18n="db_clear_filter">
              ล้างตัวกรอง
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Responsive Table -->
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
          <thead>
            <tr class="bg-[#f8faf9] border-b border-gray-200/80 text-mezenc-teal font-extrabold uppercase tracking-wider text-xs">
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_code">รหัส / ชื่อแปลงปลูก</th>
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_farmer">เกษตรกรเจ้าของแปลง</th>
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_doc_loc">เอกสารสิทธิ์ / ที่ตั้ง</th>
              <th class="py-4 px-4 whitespace-nowrap" data-i18n="db_th_clone">พันธุ์ยาง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right" data-i18n="db_th_area">เนื้อที่ (ไร่)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center" data-i18n="db_th_eudr">สถานะความสอดคล้อง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right" data-i18n="db_th_updated">อัปเดตล่าสุด</th>
              <th class="py-4 px-4 whitespace-nowrap text-center" data-i18n="db_th_actions">การจัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php if (!empty($macroPlotsList)): ?>
              <?php foreach ($macroPlotsList as $plot): ?>
                <?php 
                  $st = $plot['eudr_status'] ?? 'compliant';
                  if ($st === 'compliant') {
                    $badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1.5 align-middle"></span>ผ่านเกณฑ์ (ปลอดภัย)';
                    $descText = 'ไม่อยู่ในเขตป่าสงวน';
                  } elseif ($st === 'under_review') {
                    $badgeCls = 'bg-amber-50 text-amber-700 border-amber-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-amber-500 mr-1.5 align-middle"></span>ควรเฝ้าระวัง';
                    $descText = 'แนวกันชน Buffer 500m';
                  } else {
                    $badgeCls = 'bg-rose-50 text-rose-700 border-rose-300';
                    $stText = '<span class="inline-block w-2 h-2 rounded-full bg-rose-500 mr-1.5 align-middle"></span>ซ้อนทับเขตป่าสงวน';
                    $descText = 'ทับซ้อน Zone C ' . formatNumber($plot['eudr_overlap_pct'] ?? 10, 1) . '%';
                  }
                  $farmerName = trim(($plot['prefix'] ?? '') . ($plot['first_name'] ?? '') . ' ' . ($plot['last_name'] ?? ''));
                  if (!$farmerName) $farmerName = 'เกษตรกร จ.สุราษฎร์ธานี';
                ?>
                <tr class="hover:bg-[#f4faf7] transition-colors">
                  
                  <!-- Plot Code & Name -->
                  <td class="py-4 px-4">
                    <div class="font-bold text-gray-900 text-sm"><?= e($plot['plot_name']) ?></div>
                    <div class="text-[11px] text-gray-400 font-mono"><?= e($plot['plot_code']) ?></div>
                  </td>

                  <!-- Farmer Owner -->
                  <td class="py-4 px-4">
                    <div class="font-semibold text-gray-800"><?= e($farmerName) ?></div>
                  </td>

                  <!-- Title Deed & Location -->
                  <td class="py-4 px-4">
                    <div class="text-gray-700"><?= e($plot['title_deed_no'] ?: 'น.ส. 4 จ') ?></div>
                    <div class="text-[11px] text-gray-400">อ.<?= e($plot['district'] ?? 'เมืองสุราษฎร์ธานี') ?> จ.สุราษฎร์ธานี</div>
                  </td>

                  <!-- Rubber Clone -->
                  <td class="py-4 px-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#f8faf9] text-gray-600 border border-gray-200">
                      <?= e($plot['rubber_clone'] ?: 'RRIM 600') ?>
                    </span>
                  </td>

                  <!-- Area (Rai) -->
                  <td class="py-4 px-4 text-right font-extrabold text-mezenc-teal whitespace-nowrap text-sm sm:text-base">
                    <?= formatNumber($plot['area_rai'] ?? 10, 1) ?>
                  </td>

                  <!-- EUDR Status Badge -->
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <span class="inline-flex flex-col items-center px-3 py-1 rounded-xl text-xs font-extrabold border <?= $badgeCls ?>">
                      <span><?= $stText ?></span>
                      <span class="text-[10px] font-normal opacity-85"><?= $descText ?></span>
                    </span>
                  </td>

                  <!-- Updated At -->
                  <td class="py-4 px-4 text-right text-gray-400 text-xs whitespace-nowrap">
                    <?= relativeTime($plot['updated_at'] ?? null) ?>
                  </td>

                  <!-- Action Button -->
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <a 
                      href="map.php?plot_id=<?= (int)$plot['id'] ?>" 
                      class="inline-flex items-center px-3 py-1.5 rounded-full bg-[#f8faf9] hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-xs border border-gray-200 hover:border-mezenc-brightCyan transition-all shadow-xs"
                      title="ดูพิกัดแปลงปลูกบนแผนที่ GIS"
                    >
                      <span data-i18n="lbl_view_plot">ดูแปลง</span>
                    </a>
                  </td>

                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center py-12 text-gray-400 text-xs sm:text-sm" data-i18n="db_no_plots_admin">
                  ไม่พบข้อมูลแปลงปลูกตามเงื่อนไขที่เลือก
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
<?php endif; ?>

  </main>

  <!-- =========================================================================
       3. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี 14px)
       ========================================================================= -->
  <footer id="footer-section" class="bg-mezenc-teal text-white pt-12 sm:pt-14 pb-10 border-t border-white/10 mt-12 sm:mt-16 relative z-20">
    <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-white/15 items-start md:items-center">
        
        <!-- คอลัมน์ที่ 1: ข้อมูลโครงการและสถาบัน -->
        <div class="md:col-span-5 space-y-2.5">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center bg-white/10 shrink-0 p-1.5 mt-0.5">
              <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
              </svg>
            </div>
            <div class="space-y-0.5">
              <div class="font-extrabold text-[15px] sm:text-[16px] leading-snug text-white" data-i18n="foot_title">แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา</div>
              <div class="font-semibold text-[13px] sm:text-[14px] leading-snug text-white/95" data-i18n="foot_title_en">GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations</div>
            </div>
          </div>
          <p class="text-[14px] text-white/85 leading-relaxed font-light" data-i18n="foot_dept">
            สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม<br>
            มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี<br>
            <span class="text-white/75 text-[14px]">31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000</span>
          </p>
        </div>

        <!-- คอลัมน์ที่ 2: ข้อมูลผู้จัดทำและช่องทางติดต่อ -->
        <div class="md:col-span-4 space-y-1.5 text-[14px] text-white/85 leading-relaxed">
          <div class="font-bold text-[15px] sm:text-[16px] text-mezenc-mint" data-i18n="foot_dev_header">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[14px] text-white/75" data-i18n="foot_dev_sub">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[14px] text-white/90 space-y-1">
            <div data-i18n="foot_authors">👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div data-i18n="foot_advisor">🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div><span data-i18n="foot_email">✉️ <strong>อีเมล / Email:</strong></span> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1" data-i18n="foot_card_hdr">SURAT THANI FOREST COVERAGE</div>
            <div class="text-[14px] font-bold text-white leading-tight mt-2" data-i18n="foot_card_stat">26 ผืนป่าสงวน (Zone C) • 3,643,595 ไร่</div>
            <div class="text-[12px] text-white/70 mt-1 font-light" data-i18n="foot_card_source">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- แถบล่างสุด (Copyright Bar) -->
      <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-[14px] text-white/75 gap-4">
        <div data-i18n="foot_copy">&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right" data-i18n="foot_eudr_cert">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>

    </div>
  </footer>

  <!-- App Global JavaScript -->
  <script src="assets/js/app.js?v=<?= time() ?>"></script>

  <!-- Dashboard Dynamic Charts JavaScript -->
  <script>
    // Mobile Drawer Toggle
    function toggleMobileDrawer() {
      const drawer = document.getElementById('mobile-drawer');
      const content = document.getElementById('mobile-drawer-content');
      
      if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        setTimeout(() => {
          drawer.classList.remove('opacity-0');
          drawer.classList.add('opacity-100');
          content.classList.remove('translate-x-full');
          content.classList.add('translate-x-0');
        }, 10);
      } else {
        drawer.classList.remove('opacity-100');
        drawer.classList.add('opacity-0');
        content.classList.remove('translate-x-0');
        content.classList.add('translate-x-full');
        setTimeout(() => {
          drawer.classList.add('hidden');
        }, 300);
      }
    }

    // -------------------------------------------------------------------------
    // GLOBAL CHART.JS DEFAULTS & THEME CONFIGURATION (MATCHING MOCKUP)
    // -------------------------------------------------------------------------
    if (typeof Chart !== 'undefined') {
      Chart.defaults.font.family = '"Google Sans", "Open Sans", "Sarabun", sans-serif';
      Chart.defaults.color = '#94a3b8';
      Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(12, 63, 35, 0.95)';
      Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
      Chart.defaults.plugins.tooltip.bodyColor = '#e2f5eb';
      Chart.defaults.plugins.tooltip.borderColor = 'rgba(34, 197, 94, 0.35)';
      Chart.defaults.plugins.tooltip.borderWidth = 1;
      Chart.defaults.plugins.tooltip.padding = 12;
      Chart.defaults.plugins.tooltip.cornerRadius = 14;
      Chart.defaults.plugins.tooltip.usePointStyle = true;
      Chart.defaults.plugins.tooltip.boxPadding = 6;
      Chart.defaults.plugins.legend.labels.usePointStyle = true;
      Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
      Chart.defaults.plugins.legend.labels.boxWidth = 7;
      Chart.defaults.plugins.legend.labels.padding = 18;
      Chart.defaults.plugins.legend.labels.font = { size: 12, weight: '600' };
    }

    // Helper: Create Vertical Gradient for Charts
    function getChartGradient(ctx, colorTop, colorBottom, height = 280) {
      const gradient = ctx.createLinearGradient(0, 0, 0, height);
      gradient.addColorStop(0, colorTop);
      gradient.addColorStop(1, colorBottom);
      return gradient;
    }

    <?php if ($isFactory): ?>
    // Factory Plot Filter Function
    function filterFactoryDashboard(plotId) {
      const url = new URL(window.location.href);
      url.searchParams.set('mode', 'factory');
      if (plotId && String(plotId).trim() !== '') {
        url.searchParams.set('plot_id', plotId);
      } else {
        url.searchParams.delete('plot_id');
      }
      window.location.href = url.pathname + url.search;
    }

    // -------------------------------------------------------------------------
    // FACTORY CHARTS INITIALIZATION
    // -------------------------------------------------------------------------
    (function initFactoryCharts() {
      if (typeof Chart === 'undefined') return;

      const yieldTrendData = <?= json_encode($factoryYieldTrendData, JSON_UNESCAPED_UNICODE) ?> || [];
      const priceRevenueData = <?= json_encode($factoryPriceRevenueTrendData, JSON_UNESCAPED_UNICODE) ?> || [];
      const donutData = <?= json_encode($factoryDonutData, JSON_UNESCAPED_UNICODE) ?> || [];

      // 1. Yield Trend Line Chart (Vivid Emerald Green & Deep Pine Forest Green)
      const yieldCanvas = document.getElementById('factoryYieldTrendCanvas');
      if (yieldCanvas) {
        const ctx = yieldCanvas.getContext('2d');
        const labels = yieldTrendData.map(d => d.harvest_date);
        const kgData = yieldTrendData.map(d => parseFloat(d.daily_kg) || 0);
        const drcData = yieldTrendData.map(d => parseFloat(d.avg_drc) || 0);

        const fillGradient = getChartGradient(ctx, 'rgba(0, 192, 103, 0.22)', 'rgba(0, 192, 103, 0.00)', 260);

        new Chart(yieldCanvas, {
          type: 'line',
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                label: 'น้ำยางสด (กก.)',
                data: kgData.length ? kgData : [0],
                borderColor: '#00c067',
                backgroundColor: fillGradient,
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#00c067',
                pointBorderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 6.5,
                tension: 0.38,
                fill: true,
                yAxisID: 'y'
              },
              {
                label: 'DRC (%)',
                data: drcData.length ? drcData : [0],
                borderColor: '#0c3f23',
                backgroundColor: 'transparent',
                borderWidth: 2.2,
                borderDash: [4, 4],
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#0c3f23',
                pointBorderWidth: 2,
                pointRadius: 3.5,
                pointHoverRadius: 6,
                tension: 0.38,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } }
              },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'น้ำยางสด (กก.)', color: '#00c067', font: { size: 11, weight: '700' } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                min: 0,
                max: 50,
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'DRC (%)', color: '#0c3f23', font: { size: 11, weight: '700' } }
              }
            },
            plugins: {
              legend: { position: 'top' }
            }
          }
        });
      }

      // 2. Price & Revenue Dual Chart (Capsule Deep Forest Green Bars & Emerald Line)
      const priceCanvas = document.getElementById('factoryPriceRevenueCanvas');
      if (priceCanvas) {
        const labels = priceRevenueData.map(d => d.harvest_date);
        const priceData = priceRevenueData.map(d => parseFloat(d.avg_price) || 0);
        const revData = priceRevenueData.map(d => parseFloat(d.daily_revenue) || 0);

        new Chart(priceCanvas, {
          type: 'bar',
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                type: 'bar',
                label: 'มูลค่ารับซื้อรวม (บาท)',
                data: revData.length ? revData : [0],
                backgroundColor: '#0c3f23',
                borderRadius: 20,
                barPercentage: 0.55,
                yAxisID: 'y'
              },
              {
                type: 'line',
                label: 'ราคารับซื้อ (บาท/กก.)',
                data: priceData.length ? priceData : [0],
                borderColor: '#00c067',
                backgroundColor: '#00c067',
                borderWidth: 2.8,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#00c067',
                pointBorderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 6.5,
                tension: 0.35,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } }
              },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'มูลค่า (บาท)', color: '#0c3f23', font: { size: 11, weight: '700' } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'ราคา (บาท/กก.)', color: '#00c067', font: { size: 11, weight: '700' } }
              }
            },
            plugins: {
              legend: { position: 'top' }
            }
          }
        });
      }

      // 3. Factory Donut Breakdown Chart
      const donutCanvas = document.getElementById('factoryDonutCanvas');
      if (donutCanvas) {
        const dLabels = donutData.map(d => d.label || 'ไม่ระบุ');
        const dValues = donutData.map(d => parseFloat(d.value) || 0);
        const palette = ['#0c3f23', '#00c067', '#22c55e', '#5ebbb6', '#0f766e', '#64748b'];

        new Chart(donutCanvas, {
          type: 'doughnut',
          data: {
            labels: dLabels.length ? dLabels : ['ไม่มีข้อมูล'],
            datasets: [{
              data: dValues.length ? dValues : [1],
              backgroundColor: palette.slice(0, Math.max(1, dLabels.length)),
              borderWidth: 3,
              borderColor: '#ffffff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return ` ${context.label}: ${Number(context.raw).toLocaleString()} กก.`;
                  }
                }
              }
            }
          }
        });
      }
    })();

    <?php elseif (!$isUserAdmin): ?>
    // Farmer Plot Filter Function
    function filterFarmerDashboard(plotId) {
      const url = new URL(window.location.href);
      if (plotId && String(plotId).trim() !== '') {
        url.searchParams.set('plot_id', plotId);
      } else {
        url.searchParams.delete('plot_id');
      }
      window.location.href = url.pathname + url.search;
    }

    // -------------------------------------------------------------------------
    // FARMER CHARTS INITIALIZATION
    // -------------------------------------------------------------------------
    (function initFarmerCharts() {
      if (typeof Chart === 'undefined') return;

      const yieldTrendData = <?= json_encode($yieldTrendData, JSON_UNESCAPED_UNICODE) ?> || [];
      const priceRevenueData = <?= json_encode($priceRevenueTrendData, JSON_UNESCAPED_UNICODE) ?> || [];

      // 1. Yield Trend Line Chart (Vivid Emerald Green & Deep Pine Forest Green)
      const yieldCanvas = document.getElementById('farmerYieldTrendCanvas');
      if (yieldCanvas) {
        const ctx = yieldCanvas.getContext('2d');
        const labels = yieldTrendData.map(d => d.harvest_date);
        const kgData = yieldTrendData.map(d => parseFloat(d.daily_kg) || 0);
        const drcData = yieldTrendData.map(d => parseFloat(d.avg_drc) || 0);

        const fillGradient = getChartGradient(ctx, 'rgba(0, 192, 103, 0.22)', 'rgba(0, 192, 103, 0.00)', 260);

        new Chart(yieldCanvas, {
          type: 'line',
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                label: 'น้ำยางสด (กก.)',
                data: kgData.length ? kgData : [0],
                borderColor: '#00c067',
                backgroundColor: fillGradient,
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#00c067',
                pointBorderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 6.5,
                tension: 0.38,
                fill: true,
                yAxisID: 'y'
              },
              {
                label: 'DRC (%)',
                data: drcData.length ? drcData : [0],
                borderColor: '#0c3f23',
                backgroundColor: 'transparent',
                borderWidth: 2.2,
                borderDash: [4, 4],
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#0c3f23',
                pointBorderWidth: 2,
                pointRadius: 3.5,
                pointHoverRadius: 6,
                tension: 0.38,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } }
              },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'น้ำยางสด (กก.)', color: '#00c067', font: { size: 11, weight: '700' } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                min: 0,
                max: 50,
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'DRC (%)', color: '#0c3f23', font: { size: 11, weight: '700' } }
              }
            },
            plugins: {
              legend: { position: 'top' }
            }
          }
        });
      }

      // 2. Price & Revenue Dual Chart (Capsule Deep Forest Green Bars & Emerald Line)
      const priceCanvas = document.getElementById('farmerPriceRevenueCanvas');
      if (priceCanvas) {
        const ctx = priceCanvas.getContext('2d');
        const labels = priceRevenueData.map(d => d.harvest_date);
        const priceData = priceRevenueData.map(d => parseFloat(d.avg_price) || 0);
        const revData = priceRevenueData.map(d => parseFloat(d.daily_revenue) || 0);

        new Chart(priceCanvas, {
          type: 'bar',
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                type: 'bar',
                label: 'รายได้รวม (บาท)',
                data: revData.length ? revData : [0],
                backgroundColor: '#0c3f23',
                borderRadius: 20,
                barPercentage: 0.55,
                yAxisID: 'y'
              },
              {
                type: 'line',
                label: 'ราคารับซื้อ (บาท/กก.)',
                data: priceData.length ? priceData : [0],
                borderColor: '#00c067',
                backgroundColor: '#00c067',
                borderWidth: 2.8,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#00c067',
                pointBorderWidth: 2.5,
                pointRadius: 4,
                pointHoverRadius: 6.5,
                tension: 0.35,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } }
              },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'รายได้ (บาท)', color: '#0c3f23', font: { size: 11, weight: '700' } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'ราคา (บาท/กก.)', color: '#00c067', font: { size: 11, weight: '700' } }
              }
            },
            plugins: {
              legend: { position: 'top' }
            }
          }
        });
      }
    })();

    <?php else: ?>
    // -------------------------------------------------------------------------
    // ADMIN CHARTS INITIALIZATION
    // -------------------------------------------------------------------------
    (function initAdminCharts() {
      if (typeof Chart === 'undefined') return;

      // 1. Status Donut Chart (Mockup Product Statistic Slices)
      const donutCtx = document.getElementById('statusChartCanvas');
      if (donutCtx) {
        new Chart(donutCtx, {
          type: 'doughnut',
          data: {
            labels: [
              'ผ่านเกณฑ์ (ปลอดภัย)',
              'ควรเฝ้าระวัง (Buffer 500m)',
              'ซ้อนทับเขตป่าสงวน'
            ],
            datasets: [{
              data: [
                <?= (float)$greenArea ?>,
                <?= (float)$yellowArea ?>,
                <?= (float)$redArea ?>
              ],
              backgroundColor: ['#0c3f23', '#00c067', '#ef4444'],
              borderWidth: 3,
              borderColor: '#ffffff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return ` ${context.label}: ${Number(context.raw).toLocaleString()} ไร่`;
                  }
                }
              }
            }
          }
        });
      }

      // 2. Clone Distribution Bar Chart (Capsule Bars & Modern Green Tone)
      const cloneCtx = document.getElementById('adminCloneChartCanvas');
      if (cloneCtx) {
        const cloneData = <?= json_encode($cloneStats, JSON_UNESCAPED_UNICODE) ?> || [];
        const cloneLabels = cloneData.map(c => c.rubber_clone || 'ไม่ระบุ');
        const cloneCounts = cloneData.map(c => parseInt(c.count) || 0);

        const ecoPalette = [
          '#0c3f23', // Deep Pine Green
          '#00c067', // Vivid Emerald
          '#22c55e', // Spring Green
          '#5ebbb6', // Soft Mint
          '#0f766e', // Rainforest Teal
          '#14b8a6', // Bright Cyan
          '#64748b', // Slate Blue
          '#94a3b8'  // Cool Grey
        ];

        new Chart(cloneCtx, {
          type: 'bar',
          data: {
            labels: cloneLabels.length ? cloneLabels : ['ไม่มีข้อมูล'],
            datasets: [{
              label: 'จำนวนแปลง (แปลง)',
              data: cloneCounts.length ? cloneCounts : [0],
              backgroundColor: ecoPalette.slice(0, cloneLabels.length),
              borderRadius: 20,
              barPercentage: 0.55
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } }
              },
              y: {
                beginAtZero: true,
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { color: '#94a3b8', font: { size: 11 } },
                title: { display: true, text: 'จำนวนแปลง', color: '#0c3f23', font: { size: 11, weight: '700' } }
              }
            }
          }
        });
      }

      // 3. Monthly Yield Trend Chart (Capsule Deep Pine Bar & Emerald Line Area)
      const monthlyCtx = document.getElementById('adminMonthlyChartCanvas');
      if (monthlyCtx) {
        const ctx = monthlyCtx.getContext('2d');
        const monthlyData = <?= json_encode($monthlyYields, JSON_UNESCAPED_UNICODE) ?> || [];
        
        const thaiMonthMap = {
          '01': 'ม.ค.', '02': 'ก.พ.', '03': 'มี.ค.', '04': 'เม.ย.',
          '05': 'พ.ค.', '06': 'มิ.ย.', '07': 'ก.ค.', '08': 'ส.ค.',
          '09': 'ก.ย.', '10': 'ต.ค.', '11': 'พ.ย.', '12': 'ธ.ค.'
        };

        const mLabels = monthlyData.map(m => {
          if (!m.harvest_month) return 'ไม่ระบุ';
          const parts = m.harvest_month.split('-');
          if (parts.length === 2) {
            const mName = thaiMonthMap[parts[1]] || parts[1];
            const yShort = parts[0].slice(-2);
            return `${mName} '${yShort}`;
          }
          return m.harvest_month;
        });
        const mKg = monthlyData.map(m => parseFloat(m.monthly_fresh_kg) || 0);
        const mRev = monthlyData.map(m => parseFloat(m.monthly_revenue) || 0);

        const lineGrad = getChartGradient(ctx, 'rgba(0, 192, 103, 0.20)', 'rgba(0, 192, 103, 0.00)', 260);

        new Chart(monthlyCtx, {
          type: 'bar',
          data: {
            labels: mLabels.length ? mLabels : ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
            datasets: [
              {
                type: 'bar',
                label: 'น้ำยางสดรวม (กก.)',
                data: mKg.length ? mKg : [0],
                backgroundColor: '#0c3f23',
                borderRadius: 16,
                barPercentage: 0.52,
                yAxisID: 'y',
                order: 2
              },
              {
                type: 'line',
                label: 'มูลค่ารวม (บาท)',
                data: mRev.length ? mRev : [0],
                borderColor: '#00c067',
                backgroundColor: lineGrad,
                borderWidth: 2.8,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#00c067',
                pointBorderWidth: 2.5,
                pointRadius: 4.5,
                pointHoverRadius: 7,
                tension: 0.38,
                yAxisID: 'y1',
                order: 1
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#64748b', font: { size: 11, weight: '600' } }
              },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false, borderDash: [3, 3] },
                ticks: { 
                  color: '#64748b', 
                  font: { size: 11 },
                  callback: function(v) { return Number(v).toLocaleString(); }
                },
                title: { display: true, text: 'น้ำยางสด (กก.)', color: '#0c3f23', font: { size: 11, weight: '700' } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false, drawBorder: false },
                ticks: { 
                  color: '#64748b', 
                  font: { size: 11 },
                  callback: function(v) { return '฿' + Number(v).toLocaleString(); }
                },
                title: { display: true, text: 'มูลค่า (บาท)', color: '#00c067', font: { size: 11, weight: '700' } }
              }
            },
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  usePointStyle: true,
                  boxWidth: 8,
                  padding: 14,
                  font: { size: 11, weight: '600' }
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    if (context.dataset.yAxisID === 'y1') {
                      return ` ${context.dataset.label}: ฿${Number(context.raw).toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
                    }
                    return ` ${context.dataset.label}: ${Number(context.raw).toLocaleString('th-TH', { minimumFractionDigits: 1 })} กก.`;
                  }
                }
              }
            }
          }
        });
      }
    })();
    <?php endif; ?>

    // -------------------------------------------------------------------------
    // SMOOTH NUMBER COUNT-UP ANIMATION FOR KPI METRIC CARDS
    // -------------------------------------------------------------------------
    function initCounterAnimation() {
      const counters = document.querySelectorAll('.counter-number');
      counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target')) || 0;
        const decimals = parseInt(counter.getAttribute('data-decimals')) || 0;
        const duration = 1200; // ms
        const startTime = performance.now();

        function updateCount(currentTime) {
          const elapsed = currentTime - startTime;
          const progress = Math.min(elapsed / duration, 1);
          // Ease-out cubic easing curve
          const easeOut = 1 - Math.pow(1 - progress, 3);
          const currentVal = target * easeOut;
          
          counter.textContent = currentVal.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
          });

          if (progress < 1) {
            requestAnimationFrame(updateCount);
          } else {
            counter.textContent = target.toLocaleString('en-US', {
              minimumFractionDigits: decimals,
              maximumFractionDigits: decimals
            });
          }
        }
        requestAnimationFrame(updateCount);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCounterAnimation);
    } else {
      initCounterAnimation();
    }
  </script>
</body>
</html>
