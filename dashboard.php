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
$isUserAdmin = isAdmin();

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
if (!$isUserAdmin && !$farmerId && isset($_SESSION['user_id'])) {
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

if (!$isUserAdmin) {
    // =========================================================================
    // 1. FARMER PERSONAL DASHBOARD DATA
    // =========================================================================
    $fId = $farmerId ?: -1;

    // Own Plot KPIs
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

    // Own Yield KPIs
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

    // Trend 1: Latex Yield Trend (กก. น้ำยางสด ตามรอบการกรีด/วันที่)
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

    // Trend 2: Price & Revenue Trend (ราคารับซื้อ และ รายได้รวม)
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

    // Own Plots List
    $listStmt = $pdo->prepare("
        SELECT p.*, f.prefix, f.first_name, f.last_name, f.district, f.subdistrict
        FROM rubber_plots p
        LEFT JOIN farmers f ON f.id = p.farmer_id
        WHERE p.farmer_id = ?
        ORDER BY p.id DESC
    ");
    $listStmt->execute([$fId]);
    $personalPlotsList = $listStmt->fetchAll(PDO::FETCH_ASSOC);

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

  <!-- Core App Styles & Toast Notifications -->
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/i18n.js"></script>

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
  </style>
</head>
<body class="bg-[#f8faf9] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION & ATMOSPHERIC NATURE BANNER (Matching Design System)
       ========================================================================= -->
  <div class="relative w-full text-white overflow-hidden bg-mezenc-darkNavy min-h-[540px] sm:min-h-[600px] lg:min-h-[660px] flex flex-col justify-between pb-24 sm:pb-32 lg:pb-36">
    
    <!-- Hero Image Background with Clean Dark Overlay & Smooth Soft Fade to Sand -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="img/dashboard_dss_hero.jpg" alt="Surat Thani Decision Support System & GIS Analytics" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='img/map_rubber_hero.jpg';">
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
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php" data-i18n="nav_dashboard">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php" data-i18n="nav_plots">
          แปลงปลูก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="yields.php" data-i18n="nav_yields">
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
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md" data-i18n="<?= !$isUserAdmin ? 'db_hero_title_farmer' : 'db_hero_title' ?>">
          <?= !$isUserAdmin ? 'แดชบอร์ดสรุปข้อมูลแปลงปลูกและผลผลิตของคุณ' : 'แดชบอร์ดวิเคราะห์พื้นที่ปลูกและสถานะความสอดคล้อง' ?>
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow" data-i18n="<?= !$isUserAdmin ? 'db_hero_sub_farmer' : 'db_hero_sub' ?>">
          <?= !$isUserAdmin ? 'ติดตามภาพรวมแปลงปลูก สถิติผลผลิตน้ำยางสด รายได้สะสม และตรวจสอบความสอดคล้องตามมาตรฐาน EUDR ของคุณ' : 'ติดตามภาพรวมพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี และจำแนกสถานะแปลงผ่านเกณฑ์ เฝ้าระวัง และทับซ้อนเขตป่าสงวนแห่งชาติ' ?>
        </p>
      </div>
    </div>

  </div>

  <!-- =========================================================================
       [MOBILE RESPONSIVE DRAWER OVERLAY]
       ========================================================================= -->
  <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-md hidden transition-opacity duration-300 opacity-0 lg:hidden">
    <div id="mobile-drawer-content" class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-mezenc-deepTeal text-white p-6 shadow-2xl flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-out border-l border-white/10">
      
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-white/15">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
              🌲
            </div>
            <span class="font-extrabold text-base" data-i18n="nav_brand">GeoRubber Watch</span>
          </div>
          <button onclick="toggleMobileDrawer()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white">
            ✕
          </button>
        </div>

        <!-- Mobile Language Toggle Switch -->
        <div class="py-3 flex items-center justify-between border-b border-white/10">
          <span class="text-xs text-white/70 font-medium">Language / ภาษา:</span>
          <div 
            onclick="toggleLanguage()"
            class="toggle-track-dark w-[82px] h-[34px] p-[3px] flex items-center relative cursor-pointer shrink-0"
            id="lang-toggle-btn-mobile"
          >
            <div id="nav-thumb-mobile" class="toggle-thumb-dark w-[36px] h-[28px] transition-all duration-300 left-[3px]"></div>
            <div id="nav-label-th-mobile" class="relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none">TH</div>
            <div id="nav-label-en-mobile" class="relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none">EN</div>
          </div>
        </div>

        <nav class="flex flex-col gap-2 pt-4 text-sm font-medium">
          <a href="index.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🏠</span> <span data-i18n="nav_home">หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🛰️</span> <span data-i18n="nav_gis">แผนที่ GIS</span>
          </a>
          <a href="dashboard.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
            <span>📊</span> <span data-i18n="nav_dashboard">แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📍</span> <span data-i18n="nav_plots">แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🧪</span> <span data-i18n="nav_yields">ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📞</span> <span data-i18n="nav_contact">ติดต่อเรา</span>
          </a>
          <a href="logout.php" class="px-4 py-3 rounded-xl bg-red-500/20 hover:bg-red-500/40 transition-colors flex items-center gap-3 text-red-300 font-bold" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
            <span>🚪</span> <span data-i18n="nav_logout">ออกจากระบบ (Logout)</span>
          </a>
        </nav>
      </div>

      <div class="pt-4 border-t border-white/15 text-center text-xs text-white/60">
        GeoRubber Watch • ม.อ. สุราษฎร์ธานี
      </div>

    </div>
  </div>

  <!-- =========================================================================
       2. MAIN DASHBOARD: SPATIAL STATUS BREAKDOWN & DETAILED TABLE
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1 space-y-6">
<?php if (!$isUserAdmin): ?>
    <!-- =========================================================================
         FARMER PERSONAL DASHBOARD VIEW
         ========================================================================= -->
    <!-- 4 PERSONAL KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      <!-- Card 1: My Plots & Area -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] flex flex-col justify-between group hover:border-mezenc-brightCyan transition-all">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-400 block" data-i18n="db_card_my_plots">
              แปลงปลูกของฉันทั้งหมด
            </span>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-mezenc-teal mt-1">
              <?= formatNumber($farmerPlots['total_plots'] ?? 0) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_plots">แปลง</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1] shadow-xs">
            🌳
          </div>
        </div>
        <div class="pt-4 border-t border-gray-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-gray-500 font-medium"><span data-i18n="lbl_total_area_colon">เนื้อที่รวม:</span> <b><?= formatNumber($farmerPlots['total_rai'] ?? 0, 1) ?> <span data-i18n="unit_rai">ไร่</span></b> (<?= formatNumber($farmerPlots['total_ha'] ?? 0, 2) ?> ha)</span>
          <span class="bg-mezenc-lightCyan text-mezenc-teal px-2 py-0.5 rounded-full font-bold text-[10px]">
            <?= formatNumber($farmerPlots['total_trees'] ?? 0) ?> <span data-i18n="unit_trees">ต้น</span>
          </span>
        </div>
      </div>

      <!-- Card 2: Fresh Latex Production -->
      <div class="bg-gradient-to-br from-emerald-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(16,185,129,0.18)] border-2 border-emerald-300 flex flex-col justify-between group hover:border-emerald-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-emerald-800" data-i18n="db_card_monthly_yield">
                ผลผลิตน้ำยางสดสะสม
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-emerald-600 mt-1">
              <?= formatNumber($farmerYields['total_fresh_kg'] ?? 0, 1) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_kg">กก.</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl shrink-0 border border-emerald-300 shadow-xs">
            🧪
          </div>
        </div>
        <div class="pt-4 border-t border-emerald-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-emerald-700 font-semibold">DRC <?= formatNumber($farmerYields['avg_drc'] ?? 0, 1) ?>%</span>
          <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold text-[10px]">
            <?= formatNumber($farmerYields['total_dry_kg'] ?? 0, 1) ?> <span data-i18n="unit_kg">กก.</span>
          </span>
        </div>
      </div>

      <!-- Card 3: Total Revenue -->
      <div class="bg-gradient-to-br from-amber-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(245,158,11,0.18)] border-2 border-amber-300 flex flex-col justify-between group hover:border-amber-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-amber-800" data-i18n="db_card_est_income">
                รายได้สะสมรวม
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-amber-600 mt-1">
              <?= formatNumber($farmerYields['total_revenue'] ?? 0, 2) ?> <span class="text-sm font-normal text-gray-500">฿</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0 border border-amber-300 shadow-xs">
            💰
          </div>
        </div>
        <div class="pt-4 border-t border-amber-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-amber-700 font-semibold"><span data-i18n="lbl_average">เฉลี่ย</span> <?= formatNumber($farmerYields['avg_price'] ?? 0, 2) ?> ฿/กก.</span>
          <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold text-[10px]">
            <?= formatNumber($farmerYields['total_records'] ?? 0) ?> <span data-i18n="unit_plots">รอบ</span>
          </span>
        </div>
      </div>

      <!-- Card 4: Personal EUDR Compliance -->
      <div class="bg-gradient-to-br from-teal-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(14,77,78,0.18)] border-2 border-mezenc-mint flex flex-col justify-between group hover:border-mezenc-brightCyan transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-mezenc-brightCyan inline-block"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-mezenc-teal" data-i18n="db_card_eudr_status">
                สถานะความสอดคล้อง EUDR
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-mezenc-teal mt-1">
              <?= $farmerComplianceRate ?>% <span class="text-sm font-normal text-gray-500" data-i18n="status_safe">ปลอดภัย</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-mezenc-mint shadow-xs">
            🛡️
          </div>
        </div>
        <div class="pt-4 border-t border-teal-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-emerald-700 font-bold">🟢 <?= (int)($farmerPlots['compliant_plots'] ?? 0) ?></span>
          <span class="text-amber-700 font-bold">🟡 <?= (int)($farmerPlots['review_plots'] ?? 0) ?></span>
          <span class="text-rose-700 font-bold">🔴 <?= (int)($farmerPlots['non_compliant_plots'] ?? 0) ?></span>
        </div>
      </div>
    </div>

    <!-- 2 FARMER TREND CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Chart 1: Latex Yield Trend (กก. น้ำยางสด & DRC %) -->
      <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
          <div>
            <h3 class="text-sm sm:text-base font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_farmer_chart_yield">
              <span>📈 แนวโน้มผลผลิตน้ำยางสด (Latex Yield Trend)</span>
            </h3>
            <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_farmer_chart_yield_sub">
              ปริมาณน้ำยางสด (กก.) และเปอร์เซ็นต์เนื้อยางแห้ง DRC (%) ตามรอบการกรีด
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200" data-i18n="db_badge_30_rounds">
            30 รอบล่าสุด
          </span>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="farmerYieldTrendCanvas"></canvas>
        </div>
      </div>

      <!-- Chart 2: Rubber Price & Total Revenue Trend -->
      <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
          <div>
            <h3 class="text-sm sm:text-base font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_farmer_chart_revenue">
              <span>💵 แนวโน้มราคารับซื้อและรายได้รวม (Price & Revenue Trend)</span>
            </h3>
            <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_farmer_chart_revenue_sub">
              ราคารับซื้อน้ำยางสด (บาท/กก.) และรายได้รวมต่อรอบการเก็บเกี่ยว (บาท)
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-[11px] font-bold border border-amber-200" data-i18n="db_badge_revenue_stats">
            สถิติรายรับ
          </span>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="farmerPriceRevenueCanvas"></canvas>
        </div>
      </div>

    </div>

    <!-- FARMER PERSONAL PLOTS REGISTRY TABLE -->
    <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] overflow-hidden">
      <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_farmer_table_title">
            <span>📋 รายการแปลงปลูกของฉัน (My Rubber Plantations)</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_farmer_table_sub">
            สรุปข้อมูลแปลงปลูก พันธุ์ยาง เนื้อที่ และผลการประเมินความสอดคล้องตามมาตรฐาน EUDR
          </p>
        </div>
        <div class="flex items-center gap-2">
          <a
            href="yields.php"
            class="px-4 py-2 rounded-full bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs border border-emerald-200 transition-all flex items-center gap-1.5 shadow-xs"
          >
            <span>🧪</span> <span data-i18n="db_btn_log_yield">บันทึกผลผลิต</span>
          </a>
          <a
            href="map.php"
            class="px-4 py-2 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-xs transition-all flex items-center gap-1.5 shadow-xs"
          >
            <span>➕</span> <span data-i18n="db_btn_add_plot">เพิ่มแปลงปลูก</span>
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
                  $st = $plot['eudr_status'] ?? 'compliant';
                  if ($st === 'compliant') {
                    $badgeCls = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                    $stText = '🟢 ผ่านเกณฑ์ (ปลอดภัย)';
                    $descText = 'ไม่อยู่ในเขตป่าสงวน';
                  } elseif ($st === 'under_review') {
                    $badgeCls = 'bg-amber-50 text-amber-700 border-amber-300';
                    $stText = '🟡 ควรเฝ้าระวัง';
                    $descText = 'แนวกันชน Buffer 500m';
                  } else {
                    $badgeCls = 'bg-rose-50 text-rose-700 border-rose-300';
                    $stText = '🔴 ซ้อนทับเขตป่าสงวน';
                    $descText = 'ทับซ้อน Zone C ' . formatNumber($plot['eudr_overlap_pct'] ?? 10, 1) . '%';
                  }
                  $isTapping = ($plot['tapping_status'] ?? '') === 'tapping';
                ?>
                <tr class="hover:bg-[#f4faf7] transition-colors">
                  <td class="py-4 px-4">
                    <div class="font-bold text-gray-900 text-sm"><?= e($plot['plot_name']) ?></div>
                    <div class="text-[11px] text-gray-400 font-mono"><?= e($plot['plot_code']) ?></div>
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
                      <?= $isTapping ? '🟢 เปิดกรีดแล้ว' : '⚪ ยังไม่เปิดกรีด' ?>
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <span class="inline-flex flex-col items-center px-3 py-1 rounded-xl text-xs font-extrabold border <?= $badgeCls ?>">
                      <span><?= $stText ?></span>
                      <span class="text-[10px] font-normal opacity-85"><?= $descText ?></span>
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center whitespace-nowrap">
                    <a 
                      href="map.php?plot_id=<?= (int)$plot['id'] ?>" 
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-[#f8faf9] hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-xs border border-gray-200 hover:border-mezenc-brightCyan transition-all shadow-xs"
                      title="ดูพิกัดแปลงปลูกบนแผนที่ GIS"
                    >
                      <span>📍</span> <span data-i18n="lbl_view_plot">ดูแปลง</span>
                    </a>
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
    <!-- 4 MAIN STATS CARDS: TOTAL AREA + 3 COLOR-CODED RISK CATEGORIES -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      
      <!-- CARD 1: TOTAL SURAT THANI AREA (Main Highlight Card) -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] flex flex-col justify-between group hover:border-mezenc-brightCyan transition-all">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-400 block" data-i18n="db_card_total_area_all">
              พื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี รวมทั้งหมด
            </span>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-mezenc-teal mt-1">
              <?= formatNumber($totalArea, 1) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1] shadow-xs">
            🌲
          </div>
        </div>
        
        <div class="pt-4 border-t border-gray-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-gray-500 font-medium">จำนวนเกษตรกร: <b><?= formatNumber($totalFarmers) ?></b> ราย</span>
          <span class="font-extrabold text-mezenc-teal text-sm"><?= formatNumber($totalPlots) ?> <span data-i18n="unit_plots">แปลง</span></span>
        </div>
      </div>

      <!-- CARD 2: 🟢 GREEN - ผ่านเกณฑ์มาตรฐาน EUDR (100% Deforestation-Free) -->
      <div class="bg-gradient-to-br from-emerald-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(16,185,129,0.18)] border-2 border-emerald-300 flex flex-col justify-between group hover:border-emerald-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-emerald-800" data-i18n="db_card_compliant">
                แปลงที่ผ่านเกณฑ์ (ปลอดภัย)
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-emerald-600 mt-1">
              <?= formatNumber($greenArea, 1) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl shrink-0 border border-emerald-300 shadow-xs">
            🟢
          </div>
        </div>

        <div class="pt-4 border-t border-emerald-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-emerald-700 font-semibold"><?= formatNumber($greenCount) ?> แปลง (<?= $greenPct ?>%)</span>
          <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold text-[10px]" data-i18n="db_card_compliant_sub">
            ปลอดการตัดไม้ทำลายป่า
          </span>
        </div>
      </div>

      <!-- CARD 3: 🟡 YELLOW - แปลงที่ควรเฝ้าระวัง (Buffer Zone 500m) -->
      <div class="bg-gradient-to-br from-amber-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(245,158,11,0.18)] border-2 border-amber-300 flex flex-col justify-between group hover:border-amber-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-amber-800" data-i18n="db_card_review">
                แปลงที่ควรเฝ้าระวัง
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-amber-600 mt-1">
              <?= formatNumber($yellowArea, 1) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0 border border-amber-300 shadow-xs">
            🟡
          </div>
        </div>

        <div class="pt-4 border-t border-amber-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-amber-700 font-semibold"><?= formatNumber($yellowCount) ?> แปลง (<?= $yellowPct ?>%)</span>
          <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold text-[10px]" data-i18n="db_card_review_sub">
            แนวกันชน Buffer 500m
          </span>
        </div>
      </div>

      <!-- CARD 4: 🔴 RED - แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน (Non-compliant / Overlap) -->
      <div class="bg-gradient-to-br from-rose-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(239,68,68,0.18)] border-2 border-rose-300 flex flex-col justify-between group hover:border-rose-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-rose-800" data-i18n="db_card_non_compliant">
                แปลงที่ซ้อนทับเขตป่าสงวน
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-rose-600 mt-1">
              <?= formatNumber($redArea, 1) ?> <span class="text-sm font-normal text-gray-500" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-2xl shrink-0 border border-rose-300 shadow-xs">
            🔴
          </div>
        </div>

        <div class="pt-4 border-t border-rose-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-rose-700 font-semibold"><?= formatNumber($redCount) ?> แปลง (<?= $redPct ?>%)</span>
          <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full font-bold text-[10px]" data-i18n="db_card_non_compliant_sub">
            ทับซ้อน Zone C
          </span>
        </div>
      </div>

    </div>

    <!-- DONUT CHART & SPATIAL BREAKDOWN CARD -->
    <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-7">
      
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4 mb-6">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_status_ratio_title">
            <span>📊 สัดส่วนการจำแนกสถานะพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_status_ratio_sub">
            เปรียบเทียบสัดส่วนเนื้อที่และแปลงปลูกตามเกณฑ์การตรวจสอบกับแนวเขตป่าสงวนแห่งชาติ 26 แห่ง
          </p>
        </div>

        <!-- Quick Status Filter Links -->
        <div class="flex items-center gap-2 text-xs">
          <a 
            href="dashboard.php" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === '' ? 'bg-mezenc-teal text-white shadow-xs' : 'bg-[#f8faf9] text-gray-600 hover:bg-gray-100 border border-gray-200' ?>"
          >
            <span data-i18n="db_filter_all">ทั้งหมด</span> (<?= formatNumber($totalPlots) ?>)
          </a>
          <a 
            href="dashboard.php?status=compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'compliant' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' ?>"
          >
            <span data-i18n="db_filter_compliant">🟢 ผ่านเกณฑ์</span> (<?= formatNumber($greenCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=under_review" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'under_review' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
          >
            <span data-i18n="db_filter_review">🟡 เฝ้าระวัง</span> (<?= formatNumber($yellowCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=non_compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'non_compliant' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' ?>"
          >
            <span data-i18n="db_filter_non_compliant">🔴 ซ้อนทับป่า</span> (<?= formatNumber($redCount) ?>)
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
              <span class="text-xs font-bold text-gray-400 uppercase tracking-wider" data-i18n="lbl_total_area_colon">พื้นที่รวม</span>
              <span class="text-xl sm:text-2xl font-black text-mezenc-teal"><?= formatNumber($totalArea, 0) ?></span>
              <span class="text-[11px] text-gray-500 font-medium" data-i18n="unit_rai">ไร่</span>
            </div>
          </div>
        </div>

        <!-- Right: Progress Breakdown Rows -->
        <div class="md:col-span-8 space-y-4 text-xs sm:text-sm">
          
          <!-- 🟢 Green Bar -->
          <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                <strong class="text-emerald-900 font-extrabold text-sm sm:text-base" data-i18n="db_progress_green_title">
                  🟢 แปลงที่ผ่านเกณฑ์ (ปลอดภัย 100%)
                </strong>
              </div>
              <span class="font-extrabold text-emerald-700 text-sm sm:text-base">
                <?= formatNumber($greenArea, 1) ?> <span data-i18n="unit_rai">ไร่</span> (<?= $greenPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-emerald-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: <?= $greenPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-emerald-800/80">
              <span>จำนวน: <b><?= formatNumber($greenCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_green_desc">สถานะ: อยู่นอกแนวเขตป่าสงวนแห่งชาติและแนวกันชนทุกผืน</span>
            </div>
          </div>

          <!-- 🟡 Yellow Bar -->
          <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200 space-y-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                <strong class="text-amber-900 font-extrabold text-sm sm:text-base" data-i18n="db_progress_yellow_title">
                  🟡 แปลงที่ควรเฝ้าระวัง (Buffer Zone 500m)
                </strong>
              </div>
              <span class="font-extrabold text-amber-700 text-sm sm:text-base">
                <?= formatNumber($yellowArea, 1) ?> <span data-i18n="unit_rai">ไร่</span> (<?= $yellowPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-amber-100 rounded-full overflow-hidden">
              <div class="h-full bg-amber-500 rounded-full transition-all duration-500" style="width: <?= $yellowPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-amber-800/80">
              <span>จำนวน: <b><?= formatNumber($yellowCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_yellow_desc">สถานะ: ห่างจากแนวเขตป่าสงวนน้อยกว่า 500 เมตร ต้องติดตามพิกัดขอบเขต</span>
            </div>
          </div>

          <!-- 🔴 Red Bar -->
          <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200 space-y-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500 shrink-0"></span>
                <strong class="text-rose-900 font-extrabold text-sm sm:text-base" data-i18n="db_progress_red_title">
                  🔴 แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน
                </strong>
              </div>
              <span class="font-extrabold text-rose-700 text-sm sm:text-base">
                <?= formatNumber($redArea, 1) ?> <span data-i18n="unit_rai">ไร่</span> (<?= $redPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-rose-100 rounded-full overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full transition-all duration-500" style="width: <?= $redPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-rose-800/80">
              <span>จำนวน: <b><?= formatNumber($redCount) ?> <span data-i18n="unit_plots">แปลง</span></b></span>
              <span data-i18n="db_progress_red_desc">สถานะ: มีพิกัด Polygon ซ้อนทับแนวเขตป่าสงวนแห่งชาติสุราษฎร์ธานี (Zone C)</span>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- MACRO INSIGHTS: CLONE DISTRIBUTION & MONTHLY TREND -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Clone Distribution Chart -->
      <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
          <div>
            <h3 class="text-sm sm:text-base font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_chart_clone_title">
              <span>🧬 สัดส่วนสายพันธุ์ยางพารา (Clone Distribution)</span>
            </h3>
            <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_chart_clone_sub">
              การกระจายตัวของพันธุ์ยางพาราในพื้นที่ จ.สุราษฎร์ธานี
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-mezenc-lightCyan text-mezenc-teal text-[11px] font-bold border border-mezenc-mint" data-i18n="db_badge_provincial">
            ภาพรวมจังหวัด
          </span>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="adminCloneChartCanvas"></canvas>
        </div>
      </div>

      <!-- Monthly Yield Trend Chart -->
      <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
          <div>
            <h3 class="text-sm sm:text-base font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_chart_monthly_title">
              <span>📅 แนวโน้มผลผลิตและรายได้รายเดือน (Monthly Provincial Trends)</span>
            </h3>
            <p class="text-xs text-gray-400 font-medium mt-0.5" data-i18n="db_chart_monthly_sub">
              ปริมาณน้ำยางสด (กก.) และมูลค่ารวมรายเดือนทั้งจังหวัด
            </p>
          </div>
          <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200" data-i18n="db_badge_monthly">
            รายเดือน
          </span>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <canvas id="adminMonthlyChartCanvas"></canvas>
        </div>
      </div>

    </div>

    <!-- DETAILED DATA TABLE: PLOTS REGISTRY (ADMIN) -->
    <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2" data-i18n="db_table_title">
            <span>📋 ทะเบียนแปลงปลูกยางพารา จ.สุราษฎร์ธานี</span>
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
                    $stText = '🟢 ผ่านเกณฑ์ (ปลอดภัย)';
                    $descText = 'ไม่อยู่ในเขตป่าสงวน';
                  } elseif ($st === 'under_review') {
                    $badgeCls = 'bg-amber-50 text-amber-700 border-amber-300';
                    $stText = '🟡 ควรเฝ้าระวัง';
                    $descText = 'แนวกันชน Buffer 500m';
                  } else {
                    $badgeCls = 'bg-rose-50 text-rose-700 border-rose-300';
                    $stText = '🔴 ซ้อนทับเขตป่าสงวน';
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
                      class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-[#f8faf9] hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-xs border border-gray-200 hover:border-mezenc-brightCyan transition-all shadow-xs"
                      title="ดูพิกัดแปลงปลูกบนแผนที่ GIS"
                    >
                      <span>📍</span> <span data-i18n="lbl_view_plot">ดูแปลง</span>
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
            <div data-i18n="foot_email">✉️ <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a>, <a href="mailto:6640011066@psu.ac.th" class="hover:text-mezenc-mint underline">6640011066@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1" data-i18n="foot_card_hdr">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1">🗺️</div>
            <div class="text-[14px] font-bold text-white leading-tight" data-i18n="foot_card_stat">26 ผืนป่าสงวน (Zone C) • 784,618 ไร่</div>
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

    <?php if (!$isUserAdmin): ?>
    // -------------------------------------------------------------------------
    // FARMER CHARTS INITIALIZATION
    // -------------------------------------------------------------------------
    (function initFarmerCharts() {
      if (typeof Chart === 'undefined') return;

      const yieldTrendData = <?= json_encode($yieldTrendData, JSON_UNESCAPED_UNICODE) ?> || [];
      const priceRevenueData = <?= json_encode($priceRevenueTrendData, JSON_UNESCAPED_UNICODE) ?> || [];

      // 1. Yield Trend Line Chart
      const yieldCanvas = document.getElementById('farmerYieldTrendCanvas');
      if (yieldCanvas) {
        const labels = yieldTrendData.map(d => d.harvest_date);
        const kgData = yieldTrendData.map(d => parseFloat(d.daily_kg) || 0);
        const drcData = yieldTrendData.map(d => parseFloat(d.avg_drc) || 0);

        new Chart(yieldCanvas, {
          type: 'line',
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                label: 'น้ำยางสด (กก.)',
                data: kgData.length ? kgData : [0],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2.5,
                tension: 0.35,
                fill: true,
                yAxisID: 'y'
              },
              {
                label: 'DRC (%)',
                data: drcData.length ? drcData : [0],
                borderColor: '#0e4d4e',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [4, 4],
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
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: { display: true, text: 'น้ำยางสด (กก.)', font: { size: 11 } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                min: 0,
                max: 50,
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'DRC (%)', font: { size: 11 } }
              }
            },
            plugins: {
              legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
            }
          }
        });
      }

      // 2. Price & Revenue Dual Chart
      const priceCanvas = document.getElementById('farmerPriceRevenueCanvas');
      if (priceCanvas) {
        const labels = priceRevenueData.map(d => d.harvest_date);
        const priceData = priceRevenueData.map(d => parseFloat(d.avg_price) || 0);
        const revData = priceRevenueData.map(d => parseFloat(d.daily_revenue) || 0);

        new Chart(priceCanvas, {
          data: {
            labels: labels.length ? labels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                type: 'bar',
                label: 'รายได้รวม (บาท)',
                data: revData.length ? revData : [0],
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: '#f59e0b',
                borderRadius: 6,
                yAxisID: 'y'
              },
              {
                type: 'line',
                label: 'ราคารับซื้อ (บาท/กก.)',
                data: priceData.length ? priceData : [0],
                borderColor: '#0e4d4e',
                backgroundColor: '#0e4d4e',
                borderWidth: 2.5,
                tension: 0.3,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: { display: true, text: 'รายได้ (บาท)', font: { size: 11 } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'ราคา (บาท/กก.)', font: { size: 11 } }
              }
            },
            plugins: {
              legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
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

      // 1. Status Donut Chart
      const donutCtx = document.getElementById('statusChartCanvas');
      if (donutCtx) {
        new Chart(donutCtx, {
          type: 'doughnut',
          data: {
            labels: [
              '🟢 ผ่านเกณฑ์ (ปลอดภัย)',
              '🟡 ควรเฝ้าระวัง (Buffer 500m)',
              '🔴 ซ้อนทับเขตป่าสงวน'
            ],
            datasets: [{
              data: [
                <?= (float)$greenArea ?>,
                <?= (float)$yellowArea ?>,
                <?= (float)$redArea ?>
              ],
              backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
              borderWidth: 3,
              borderColor: '#ffffff',
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#0e4d4e',
                titleFont: { family: 'Google Sans', size: 12 },
                bodyFont: { family: 'Google Sans', size: 12 },
                padding: 10,
                cornerRadius: 10,
                callbacks: {
                  label: function(context) {
                    return ` ${context.label}: ${context.raw} ไร่`;
                  }
                }
              }
            }
          }
        });
      }

      // 2. Clone Distribution Doughnut Chart
      const cloneCtx = document.getElementById('adminCloneChartCanvas');
      if (cloneCtx) {
        const cloneData = <?= json_encode($cloneStats, JSON_UNESCAPED_UNICODE) ?> || [];
        const cloneLabels = cloneData.map(c => c.rubber_clone || 'ไม่ระบุ');
        const cloneCounts = cloneData.map(c => parseInt(c.count) || 0);

        new Chart(cloneCtx, {
          type: 'bar',
          data: {
            labels: cloneLabels.length ? cloneLabels : ['ไม่มีข้อมูล'],
            datasets: [{
              label: 'จำนวนแปลง (แปลง)',
              data: cloneCounts.length ? cloneCounts : [0],
              backgroundColor: ['#0e4d4e', '#00a699', '#5ebbb6', '#93c5fd', '#f59e0b', '#10b981'],
              borderRadius: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'จำนวนแปลง' } }
            }
          }
        });
      }

      // 3. Monthly Yield Trend Chart
      const monthlyCtx = document.getElementById('adminMonthlyChartCanvas');
      if (monthlyCtx) {
        const monthlyData = <?= json_encode($monthlyYields, JSON_UNESCAPED_UNICODE) ?> || [];
        const mLabels = monthlyData.map(m => m.harvest_month);
        const mKg = monthlyData.map(m => parseFloat(m.monthly_fresh_kg) || 0);
        const mRev = monthlyData.map(m => parseFloat(m.monthly_revenue) || 0);

        new Chart(monthlyCtx, {
          data: {
            labels: mLabels.length ? mLabels : ['ไม่มีข้อมูล'],
            datasets: [
              {
                type: 'bar',
                label: 'น้ำยางสดรวม (กก.)',
                data: mKg.length ? mKg : [0],
                backgroundColor: 'rgba(0, 166, 153, 0.65)',
                borderColor: '#00a699',
                borderRadius: 6,
                yAxisID: 'y'
              },
              {
                type: 'line',
                label: 'มูลค่ารวม (บาท)',
                data: mRev.length ? mRev : [0],
                borderColor: '#f59e0b',
                backgroundColor: '#f59e0b',
                borderWidth: 2.5,
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
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: { display: true, text: 'น้ำยางสด (กก.)', font: { size: 11 } }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'มูลค่า (บาท)', font: { size: 11 } }
              }
            },
            plugins: {
              legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
            }
          }
        });
      }
    })();
    <?php endif; ?>
  </script>
</body>
</html>
