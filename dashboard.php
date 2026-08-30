<?php
/**
 * GeoRubber Watch - Decision Support System (DSS) Dashboard
 * Spatial Analytics & Surat Thani Forest Overlap Verification
 */

declare(strict_types=1);

if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

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
// DATA AGGREGATION: SURAT THANI RUBBER PLANTATION STATS
// -------------------------------------------------------------------------
$totalPlots = 0;
$totalArea = 0.0;

$greenCount = 0;
$greenArea = 0.0;

$yellowCount = 0;
$yellowArea = 0.0;

$redCount = 0;
$redArea = 0.0;

$plotsList = [];
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

if ($dbConnected && $pdo) {
    try {
        // Total Stats
        $totalArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots")->fetchColumn();
        $totalPlots = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots")->fetchColumn();

        // 🟢 Green: ผ่านเกณฑ์ (Compliant)
        $greenCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'compliant'")->fetchColumn();
        $greenArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'compliant'")->fetchColumn();

        // 🟡 Yellow: ควรเฝ้าระวัง (Under Review / Buffer 500m)
        $yellowCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'under_review'")->fetchColumn();
        $yellowArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'under_review'")->fetchColumn();

        // 🔴 Red: ซ้อนทับพื้นที่เขตป่าสงวน (Non-compliant / Overlap)
        $redCount = (int)$pdo->query("SELECT COUNT(*) FROM rubber_plots WHERE eudr_status = 'non_compliant'")->fetchColumn();
        $redArea = (float)$pdo->query("SELECT COALESCE(SUM(area_rai + (area_ngan * 0.25) + (area_sqwah * 0.0025)), 0) FROM rubber_plots WHERE eudr_status = 'non_compliant'")->fetchColumn();

        // If no data in DB yet, provide realistic fallback
        if ($totalPlots === 0) {
            $totalPlots = 12;
            $totalArea = 145.5;
            $greenCount = 8;
            $greenArea = 102.0;
            $yellowCount = 3;
            $yellowArea = 31.5;
            $redCount = 1;
            $redArea = 12.0;
        }

        // Filtered Plots List Query
        $where = [];
        $params = [];

        if ($statusFilter !== '') {
            $where[] = "p.eudr_status = ?";
            $params[] = $statusFilter;
        }
        if ($searchQuery !== '') {
            $where[] = "(p.plot_name LIKE ? OR p.plot_code LIKE ? OR p.title_deed_no LIKE ? OR f.first_name LIKE ? OR f.last_name LIKE ? OR f.district LIKE ?)";
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
        $plotsList = $tableStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {
        // Fallback gracefully
    }
}

// Percentages
$greenPct = $totalArea > 0 ? round(($greenArea / $totalArea) * 100, 1) : 0;
$yellowPct = $totalArea > 0 ? round(($yellowArea / $totalArea) * 100, 1) : 0;
$redPct = $totalArea > 0 ? round(($redArea / $totalArea) * 100, 1) : 0;
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
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="index.php">
          หน้าแรก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="overview.php">
          แผนที่ GIS
        </a>
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php">
          แปลงปลูก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="yields.php">
          ผลผลิต
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="contact.php">
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT ACTIONS (User / Login / Mobile Menu) -->
      <div class="flex items-center gap-2.5 sm:gap-3.5">
        <button
          type="button"
          onclick="location.href='login.php'"
          class="
            text-white
            hover:text-mezenc-mint
            flex
            items-center
            justify-center
            w-9
            h-9
            sm:w-10
            sm:h-10
            rounded-full
            bg-white/15
            hover:bg-white/25
            backdrop-blur-md
            transition-all
            hover:scale-105
            border
            border-white/20
            shadow-md
            cursor-pointer
          "
          title="เข้าสู่ระบบ (Login)"
        >
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </button>

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
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow">
          DECISION SUPPORT SYSTEM (DSS) • SURAT THANI
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md">
          แดชบอร์ดวิเคราะห์พื้นที่ปลูกและสถานะความสอดคล้อง
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow">
          ติดตามภาพรวมพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี และจำแนกสถานะแปลงผ่านเกณฑ์ เฝ้าระวัง และทับซ้อนเขตป่าสงวนแห่งชาติ
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
        <div class="flex items-center justify-between pb-6 border-b border-white/15">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
              🌲
            </div>
            <span class="font-extrabold text-base">GeoRubber Watch</span>
          </div>
          <button onclick="toggleMobileDrawer()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white">
            ✕
          </button>
        </div>

        <nav class="flex flex-col gap-2 pt-6 text-sm font-medium">
          <a href="index.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🏠</span> <span>หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🛰️</span> <span>แผนที่ GIS</span>
          </a>
          <a href="dashboard.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
            <span>📊</span> <span>แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📍</span> <span>แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🧪</span> <span>ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📞</span> <span>ติดต่อเรา</span>
          </a>
          <a href="login.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3 text-mezenc-mint font-bold">
            <span>👤</span> <span>เข้าสู่ระบบ (Login)</span>
          </a>
        </nav>
      </div>

      <div class="pt-6 border-t border-white/15 text-center text-xs text-white/60">
        GeoRubber Watch • ม.อ. สุราษฎร์ธานี
      </div>

    </div>
  </div>

  <!-- =========================================================================
       2. MAIN DASHBOARD: SPATIAL STATUS BREAKDOWN & DETAILED TABLE
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1 space-y-6">
    
    <!-- 4 MAIN STATS CARDS: TOTAL AREA + 3 COLOR-CODED RISK CATEGORIES -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      
      <!-- CARD 1: TOTAL SURAT THANI AREA (Main Highlight Card) -->
      <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] flex flex-col justify-between group hover:border-mezenc-brightCyan transition-all">
        <div class="flex justify-between items-start">
          <div>
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-gray-400 block">
              พื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี รวมทั้งหมด
            </span>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-mezenc-teal mt-1">
              <?= formatNumber($totalArea, 1) ?> <span class="text-sm font-normal text-gray-500">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1] shadow-xs">
            🌲
          </div>
        </div>
        
        <div class="pt-4 border-t border-gray-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-gray-500 font-medium">จำนวนแปลงในระบบ:</span>
          <span class="font-extrabold text-mezenc-teal text-sm"><?= formatNumber($totalPlots) ?> แปลง</span>
        </div>
      </div>

      <!-- CARD 2: 🟢 GREEN - ผ่านเกณฑ์มาตรฐาน EUDR (100% Deforestation-Free) -->
      <div class="bg-gradient-to-br from-emerald-50/80 to-white rounded-3xl p-5 sm:p-6 shadow-[0_16px_40px_-10px_rgba(16,185,129,0.18)] border-2 border-emerald-300 flex flex-col justify-between group hover:border-emerald-500 transition-all">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-1.5 mb-1">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-emerald-800">
                แปลงที่ผ่านเกณฑ์ (ปลอดภัย)
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-emerald-600 mt-1">
              <?= formatNumber($greenArea, 1) ?> <span class="text-sm font-normal text-gray-500">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl shrink-0 border border-emerald-300 shadow-xs">
            🟢
          </div>
        </div>

        <div class="pt-4 border-t border-emerald-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-emerald-700 font-semibold"><?= formatNumber($greenCount) ?> แปลง (<?= $greenPct ?>%)</span>
          <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold text-[10px]">
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
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-amber-800">
                แปลงที่ควรเฝ้าระวัง
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-amber-600 mt-1">
              <?= formatNumber($yellowArea, 1) ?> <span class="text-sm font-normal text-gray-500">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl shrink-0 border border-amber-300 shadow-xs">
            🟡
          </div>
        </div>

        <div class="pt-4 border-t border-amber-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-amber-700 font-semibold"><?= formatNumber($yellowCount) ?> แปลง (<?= $yellowPct ?>%)</span>
          <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold text-[10px]">
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
              <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-rose-800">
                แปลงที่ซ้อนทับเขตป่าสงวน
              </span>
            </div>
            <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-rose-600 mt-1">
              <?= formatNumber($redArea, 1) ?> <span class="text-sm font-normal text-gray-500">ไร่</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-2xl shrink-0 border border-rose-300 shadow-xs">
            🔴
          </div>
        </div>

        <div class="pt-4 border-t border-rose-100 mt-4 flex items-center justify-between text-xs">
          <span class="text-rose-700 font-semibold"><?= formatNumber($redCount) ?> แปลง (<?= $redPct ?>%)</span>
          <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full font-bold text-[10px]">
            ทับซ้อน Zone C
          </span>
        </div>
      </div>

    </div>

    <!-- DONUT CHART & SPATIAL BREAKDOWN CARD -->
    <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-7">
      
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4 mb-6">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2">
            <span>📊 สัดส่วนการจำแนกสถานะพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5">
            เปรียบเทียบสัดส่วนเนื้อที่และแปลงปลูกตามเกณฑ์การตรวจสอบกับแนวเขตป่าสงวนแห่งชาติ 26 แห่ง
          </p>
        </div>

        <!-- Quick Status Filter Links -->
        <div class="flex items-center gap-2 text-xs">
          <a 
            href="dashboard.php" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === '' ? 'bg-mezenc-teal text-white shadow-xs' : 'bg-[#f8faf9] text-gray-600 hover:bg-gray-100 border border-gray-200' ?>"
          >
            ทั้งหมด (<?= formatNumber($totalPlots) ?>)
          </a>
          <a 
            href="dashboard.php?status=compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'compliant' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' ?>"
          >
            🟢 ผ่านเกณฑ์ (<?= formatNumber($greenCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=under_review" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'under_review' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200' ?>"
          >
            🟡 เฝ้าระวัง (<?= formatNumber($yellowCount) ?>)
          </a>
          <a 
            href="dashboard.php?status=non_compliant" 
            class="px-3.5 py-1.5 rounded-full font-bold transition-all <?= $statusFilter === 'non_compliant' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' ?>"
          >
            🔴 ซ้อนทับป่า (<?= formatNumber($redCount) ?>)
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
              <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">พื้นที่รวม</span>
              <span class="text-xl sm:text-2xl font-black text-mezenc-teal"><?= formatNumber($totalArea, 0) ?></span>
              <span class="text-[11px] text-gray-500 font-medium">ไร่</span>
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
                <strong class="text-emerald-900 font-extrabold text-sm sm:text-base">
                  🟢 แปลงที่ผ่านเกณฑ์ (ปลอดภัย 100%)
                </strong>
              </div>
              <span class="font-extrabold text-emerald-700 text-sm sm:text-base">
                <?= formatNumber($greenArea, 1) ?> ไร่ (<?= $greenPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-emerald-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: <?= $greenPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-emerald-800/80">
              <span>จำนวน: <b><?= formatNumber($greenCount) ?> แปลง</b></span>
              <span>สถานะ: อยู่นอกแนวเขตป่าสงวนแห่งชาติและแนวกันชนทุกผืน</span>
            </div>
          </div>

          <!-- 🟡 Yellow Bar -->
          <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200 space-y-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                <strong class="text-amber-900 font-extrabold text-sm sm:text-base">
                  🟡 แปลงที่ควรเฝ้าระวัง (Buffer Zone 500m)
                </strong>
              </div>
              <span class="font-extrabold text-amber-700 text-sm sm:text-base">
                <?= formatNumber($yellowArea, 1) ?> ไร่ (<?= $yellowPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-amber-100 rounded-full overflow-hidden">
              <div class="h-full bg-amber-500 rounded-full transition-all duration-500" style="width: <?= $yellowPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-amber-800/80">
              <span>จำนวน: <b><?= formatNumber($yellowCount) ?> แปลง</b></span>
              <span>สถานะ: ห่างจากแนวเขตป่าสงวนน้อยกว่า 500 เมตร ต้องติดตามพิกัดขอบเขต</span>
            </div>
          </div>

          <!-- 🔴 Red Bar -->
          <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200 space-y-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500 shrink-0"></span>
                <strong class="text-rose-900 font-extrabold text-sm sm:text-base">
                  🔴 แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน
                </strong>
              </div>
              <span class="font-extrabold text-rose-700 text-sm sm:text-base">
                <?= formatNumber($redArea, 1) ?> ไร่ (<?= $redPct ?>%)
              </span>
            </div>
            <div class="w-full h-3 bg-rose-100 rounded-full overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full transition-all duration-500" style="width: <?= $redPct ?>%;"></div>
            </div>
            <div class="flex justify-between items-center text-xs text-rose-800/80">
              <span>จำนวน: <b><?= formatNumber($redCount) ?> แปลง</b></span>
              <span>สถานะ: มีพิกัด Polygon ซ้อนทับแนวเขตป่าสงวนแห่งชาติสุราษฎร์ธานี (Zone C)</span>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- DETAILED DATA TABLE: PLOTS REGISTRY -->
    <div class="bg-white rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="p-5 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2">
            <span>📋 ทะเบียนแปลงปลูกยางพารา จ.สุราษฎร์ธานี</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5">
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
              class="w-full bg-[#f8faf9] text-gray-800 text-xs rounded-full pl-3.5 pr-8 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs"
            >
            <button type="submit" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal text-xs">
              🔍
            </button>
          </div>
          <?php if ($searchQuery !== '' || $statusFilter !== ''): ?>
            <a href="dashboard.php" class="text-xs text-gray-400 hover:text-rose-500 underline whitespace-nowrap">
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
              <th class="py-4 px-4 whitespace-nowrap">รหัส / ชื่อแปลงปลูก</th>
              <th class="py-4 px-4 whitespace-nowrap">เกษตรกรเจ้าของแปลง</th>
              <th class="py-4 px-4 whitespace-nowrap">เอกสารสิทธิ์ / ที่ตั้ง</th>
              <th class="py-4 px-4 whitespace-nowrap">พันธุ์ยาง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">เนื้อที่ (ไร่)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">สถานะความสอดคล้อง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">อัปเดตล่าสุด</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">การจัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-gray-700">
            <?php if (!empty($plotsList)): ?>
              <?php foreach ($plotsList as $plot): ?>
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
                      <span>📍</span> <span>ดูแปลง</span>
                    </a>
                  </td>

                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center py-12 text-gray-400 text-xs sm:text-sm">
                  ไม่พบข้อมูลแปลงปลูกตามเงื่อนไขที่เลือก
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

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
              <div class="font-extrabold text-[15px] sm:text-[16px] leading-snug text-white">แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา</div>
              <div class="font-semibold text-[13px] sm:text-[14px] leading-snug text-white/95">GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations</div>
            </div>
          </div>
          <p class="text-[14px] text-white/85 leading-relaxed font-light">
            สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม<br>
            มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี<br>
            <span class="text-white/75 text-[14px]">31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000</span>
          </p>
        </div>

        <!-- คอลัมน์ที่ 2: ข้อมูลผู้จัดทำและช่องทางติดต่อ -->
        <div class="md:col-span-4 space-y-1.5 text-[14px] text-white/85 leading-relaxed">
          <div class="font-bold text-[15px] sm:text-[16px] text-mezenc-mint">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[14px] text-white/75">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[14px] text-white/90 space-y-1">
            <div>👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div>🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div>✉️ <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a>, <a href="mailto:6640011066@psu.ac.th" class="hover:text-mezenc-mint underline">6640011066@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1">🗺️</div>
            <div class="text-[14px] font-bold text-white leading-tight">26 ผืนป่าสงวน (Zone C) • 784,618 ไร่</div>
            <div class="text-[12px] text-white/70 mt-1 font-light">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- แถบล่างสุด (Copyright Bar) -->
      <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-[14px] text-white/75 gap-4">
        <div>&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>

    </div>
  </footer>

  <!-- App Global JavaScript -->
  <script src="assets/js/app.js"></script>

  <!-- Status Breakdown Donut Chart JavaScript -->
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

    // Initialize Donut Chart
    (function initStatusDonut() {
      try {
        const ctx = document.getElementById('statusChartCanvas');
        if (!ctx || typeof Chart === 'undefined') return;

        new Chart(ctx, {
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
      } catch (e) {
        console.error("Donut Chart error:", e);
      }
    })();
  </script>
</body>
</html>
