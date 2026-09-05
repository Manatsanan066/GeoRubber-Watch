<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$currentUser = getCurrentUser();
$current_role = $currentUser['role'] ?? 'farmer';
$user_name = $currentUser['full_name'] ?? 'ผู้ใช้งานระบบ';

$pdo = getDatabaseConnection();

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

// Fetch plots with farmer profile details (RBAC: Farmer only sees own plots, Admin sees all)
$plots = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.plot_code, p.plot_name, p.rubber_clone, p.area_rai,
                   p.centroid_lat, p.centroid_lng, p.title_deed_no,
                   f.id as farmer_id, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num, f.phone,
                   f.subdistrict, f.district, f.province
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            WHERE p.farmer_id = ?
            ORDER BY p.plot_name ASC
        ");
        $stmt->execute([$farmerId ?: -1]);
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $plots = $pdo->query("
            SELECT p.id, p.plot_code, p.plot_name, p.rubber_clone, p.area_rai,
                   p.centroid_lat, p.centroid_lng, p.title_deed_no,
                   f.id as farmer_id, f.farmer_code, f.prefix, f.first_name, f.last_name, f.id_card_num, f.phone,
                   f.subdistrict, f.district, f.province
            FROM rubber_plots p
            LEFT JOIN farmers f ON f.id = p.farmer_id
            ORDER BY p.plot_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("SELECT id, plot_code, plot_name, rubber_clone, area_rai FROM rubber_plots WHERE farmer_id = ? ORDER BY plot_name ASC");
        $stmt->execute([$farmerId ?: -1]);
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $plots = $pdo->query("SELECT id, plot_code, plot_name, rubber_clone, area_rai FROM rubber_plots ORDER BY plot_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบบันทึกผลผลิตน้ำยางสด • GeoRubber Watch</title>
  
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

    .modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(11, 29, 38, 0.75);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      opacity: 0;
      visibility: hidden;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .modal-card {
      transform: scale(0.95) translateY(10px);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.active .modal-card {
      transform: scale(1) translateY(0);
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
      <img src="img/yields_latex_hero.jpg" alt="Surat Thani Rubber Production & Latex Analytics" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='img/map_rubber_hero.jpg';">
      <!-- Clean uniform dark overlay for crisp typography -->
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-transparent"></div>
      
      <!-- Smooth Feathered Blur & Gradient Transition lowered down towards the bottom edge -->
      <div class="absolute bottom-0 left-0 right-0 h-32 sm:h-44 md:h-56 bg-gradient-to-t from-[#f8faf9] from-15% via-[#f8faf9]/80 via-55% to-transparent pointer-events-none z-[1]"></div>
    </div>

    <!-- Top Navigation Header (Floating over hero) -->
    <header class="relative z-30 w-full px-5 py-5 sm:px-8 sm:py-6 md:px-12 xl:px-14 md:py-7 flex justify-between items-center max-w-[1440px] 2xl:max-w-[1600px] mx-auto">
      
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
            drop-shadow-md
            transition-colors
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
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php">
          แปลงปลูก
        </a>
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="yields.php">
          ผลผลิต
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="contact.php">
          ติดต่อเรา
        </a>
      </nav>

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
        >
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>

    </header>

    <!-- HERO CONTENT (Centered typography matching overview.php and map.php) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow">
          🌱 LATEX PRODUCTION & YIELD TRACKING SYSTEM
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md">
          ระบบบันทึกผลผลิตน้ำยางสด
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow">
          บันทึกปริมาณน้ำยางสด ราคารับซื้อ เชื่อมโยงข้อมูลแปลงปลูกและเกษตรกรสู่ระบบตรวจสอบย้อนกลับ (Traceability) ตามมาตรฐาน EUDR
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
          <a href="dashboard.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📊</span> <span>แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📍</span> <span>แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
            <span>🧪</span> <span>ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📞</span> <span>ติดต่อเรา</span>
          </a>
          <a href="logout.php" class="px-4 py-3 rounded-xl bg-red-500/20 hover:bg-red-500/40 transition-colors flex items-center gap-3 text-red-300 font-bold" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
            <span>🚪</span> <span>ออกจากระบบ (Logout)</span>
          </a>
        </nav>
      </div>

      <div class="pt-6 border-t border-white/15 text-center text-xs text-white/60">
        GeoRubber Watch • ม.อ. สุราษฎร์ธานี
      </div>

    </div>
  </div>

  <!-- =========================================================================
       2. MAIN PRODUCTION WORKSPACE & KPI DASHBOARD (Elevated Floating Container)
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1 space-y-6">
    
    <!-- KPI Summary Cards Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
      <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-lg flex items-center gap-3 sm:gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-2xl shrink-0">
          🥛
        </div>
        <div>
          <div class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">น้ำยางสดรวม</div>
          <div id="sum-fresh-latex" class="text-lg sm:text-2xl font-black text-mezenc-teal mt-0.5">0.0 กก.</div>
        </div>
      </div>

      <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-lg flex items-center gap-3 sm:gap-4">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-2xl shrink-0">
          💰
        </div>
        <div>
          <div class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">รวมเงินรายได้</div>
          <div id="sum-revenue" class="text-lg sm:text-2xl font-black text-mezenc-brightCyan mt-0.5">฿0.00</div>
        </div>
      </div>

      <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-lg flex items-center gap-3 sm:gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-2xl shrink-0">
          📈
        </div>
        <div>
          <div class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">ราคารับซื้อเฉลี่ย</div>
          <div id="sum-avg-price" class="text-lg sm:text-2xl font-black text-gray-800 mt-0.5">฿0.00 / กก.</div>
        </div>
      </div>

      <div class="bg-white/95 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-lg flex items-center gap-3 sm:gap-4">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-2xl shrink-0">
          📋
        </div>
        <div>
          <div class="text-[11px] sm:text-xs font-bold text-gray-400 uppercase tracking-wider">จำนวนรอบบันทึก</div>
          <div id="sum-total-records" class="text-lg sm:text-2xl font-black text-mezenc-teal mt-0.5">0 ครั้ง</div>
        </div>
      </div>
    </div>

    <!-- Action Buttons & Toolbar (Admin Search + Dropdown Filter) -->
    <div class="bg-white/90 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-md space-y-3">
      
      <!-- Top Row: Filters & Search Inputs -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        
        <!-- Plot Dropdown Filter -->
        <div class="flex items-center gap-2">
          <span class="text-xs sm:text-sm font-bold text-gray-700 whitespace-nowrap">🔎 แปลงปลูก:</span>
          <select 
            id="filter-yield-plot" 
            class="bg-[#f8faf9] text-gray-800 font-semibold text-xs sm:text-sm rounded-xl px-3 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-48 sm:w-64 cursor-pointer" 
            onchange="loadYields()"
          >
            <option value="">-- ทุกแปลงปลูก <?= $isUserAdmin ? '(ทั้งหมด)' : '(ของฉัน)' ?> --</option>
            <?php foreach ($plots as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['plot_name']) ?> (<?= htmlspecialchars($p['plot_code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($isUserAdmin): ?>
        <!-- Admin Search Inputs (Farmer Name, Plot Code, Title Deed) -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Search Farmer Name -->
          <div class="relative">
            <input 
              type="text" 
              id="search-name" 
              placeholder="ค้นหาชื่อเกษตรกร..." 
              class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-36 sm:w-44"
              oninput="debounceLoadYields()"
            >
            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none">👨‍🌾</span>
          </div>

          <!-- Search Plot Code / Name -->
          <div class="relative">
            <input 
              type="text" 
              id="search-plot-code" 
              placeholder="รหัส/ชื่อแปลง..." 
              class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-32 sm:w-40"
              oninput="debounceLoadYields()"
            >
            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none">🌲</span>
          </div>

          <!-- Search Title Deed No -->
          <div class="relative">
            <input 
              type="text" 
              id="search-title-deed" 
              placeholder="เลขที่โฉนด..." 
              class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-28 sm:w-36"
              oninput="debounceLoadYields()"
            >
            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none">📄</span>
          </div>

          <button 
            type="button" 
            onclick="clearAdminFilters()" 
            class="px-2.5 py-1.5 text-xs text-gray-400 hover:text-rose-500 underline"
            title="ล้างการค้นหา"
          >
            ล้างตัวกรอง
          </button>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 ml-auto">
          <a 
            href="api/export.php?type=yields_csv" 
            class="px-3.5 py-2 rounded-full bg-white hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-xs sm:text-sm border-2 border-[#bee6e1] shadow-xs hover:border-mezenc-brightCyan transition-all flex items-center gap-1.5"
          >
            <span>📥</span> <span>ส่งออก CSV</span>
          </a>

          <button 
            type="button" 
            onclick="openAddYieldModal()" 
            class="px-4 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg hover:scale-105 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>➕</span> <span>บันทึกผลผลิตใหม่</span>
          </button>
        </div>

      </div>

    </div>

    <!-- Production Logs Data Table Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2">
            <span>📋 ประวัติการเก็บเกี่ยวและการรับซื้อน้ำยางสด</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5">
            แสดงรายการผลผลิตรายแปลง น้ำหนักน้ำยางสด ราคาต่อกิโลกรัม และการคำนวณมูลค่ารายได้
          </p>
        </div>
      </div>

      <!-- Responsive Table Viewport -->
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
          <thead>
            <tr class="bg-[#f8faf9] border-b border-gray-200/80 text-mezenc-teal font-extrabold text-xs uppercase tracking-wider">
              <th class="py-4 px-4 whitespace-nowrap">วันที่เก็บเกี่ยว</th>
              <th class="py-4 px-4 whitespace-nowrap">แปลงปลูก</th>
              <th class="py-4 px-4 whitespace-nowrap">พันธุ์ยาง</th>
              <th class="py-4 px-4 whitespace-nowrap">เกษตรกรเจ้าของแปลง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">น้ำยางสด (กก.)</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">ราคา/กก. (บาท)</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">รวมเงินรายได้ (บาท)</th>
              <th class="py-4 px-4 whitespace-nowrap">ผู้รับซื้อ / จุดรับซื้อ</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">จัดการ</th>
            </tr>
          </thead>
          <tbody id="yields-table-body" class="divide-y divide-gray-100 text-gray-700">
            <tr>
              <td colspan="9" class="text-center py-12 text-gray-400 text-xs">
                กำลังโหลดข้อมูลผลผลิต...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <!-- =========================================================================
       3. MODAL: ADD YIELD REGISTRY (บันทึกผลผลิตน้ำยางสดรอบใหม่ - รูปแบบคล้ายกรอกข้อมูลเกษตรกร)
       ========================================================================= -->
  <div id="addYieldModal" class="modal-overlay">
    <div class="modal-card w-full max-w-3xl max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-[#bee6e1]">
      
      <!-- Modal Header -->
      <div class="p-5 sm:p-6 text-white bg-mezenc-teal relative flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shadow-inner border border-white/20">
            🌱
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-widest font-bold text-mezenc-mint">
              LATEX PRODUCTION REGISTRY • ระบบบันทึกผลผลิตน้ำยางสด
            </div>
            <h3 class="text-lg sm:text-xl font-black text-white">
              บันทึกผลผลิตรอบใหม่
            </h3>
          </div>
        </div>

        <button 
          type="button" 
          onclick="closeAddYieldModal()" 
          class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-all cursor-pointer hover:rotate-90"
        >
          ✕
        </button>
      </div>

      <!-- Modal Body (Scrollable Form Content) -->
      <form id="new-yield-form" onsubmit="handleSaveYield(event)" class="overflow-y-auto custom-scrollbar p-5 sm:p-7 space-y-6 flex-1 text-xs sm:text-sm">
        
        <!-- SECTION 1: ข้อมูลแปลงปลูกและเกษตรกรเจ้าของแปลง -->
        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-200 pb-2.5">
            <h4 class="font-bold text-mezenc-teal flex items-center gap-2 text-sm sm:text-base">
              <span>📍</span> <span>ส่วนที่ 1: ข้อมูลแปลงปลูกและเกษตรกรเจ้าของแปลง</span>
            </h4>
            <span class="text-[11px] font-bold text-mezenc-brightCyan bg-mezenc-lightCyan px-2.5 py-0.5 rounded-full">
              ระบุแปลงปลูก
            </span>
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1.5">
              เลือกแปลงปลูกยางพารา <span class="text-rose-500">*</span>
            </label>
            <select 
              id="yield-plot-id" 
              class="w-full bg-white text-gray-800 font-semibold text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs cursor-pointer" 
              required
              onchange="onPlotSelectChange()"
            >
              <option value="">-- กรุณาเลือกแปลงปลูก --</option>
              <?php foreach ($plots as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['plot_name']) ?> (<?= htmlspecialchars($p['plot_code']) ?>) - เกษตรกร: <?= htmlspecialchars($p['prefix'].$p['first_name'].' '.$p['last_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Dynamic Farmer & Plot Summary Card -->
          <div id="plot-dynamic-card" class="hidden bg-white p-4 rounded-xl border border-[#bee6e1] shadow-xs space-y-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div class="space-y-1">
                <div class="text-gray-400 font-medium">👨‍🌾 เกษตรกรเจ้าของแปลง:</div>
                <div id="disp-farmer-name" class="font-bold text-gray-800 text-sm">-</div>
                <div class="text-gray-500 text-[11px]">รหัสเกษตรกร: <span id="disp-farmer-code" class="font-mono font-bold text-mezenc-teal">-</span></div>
              </div>
              <div class="space-y-1">
                <div class="text-gray-400 font-medium">🌳 พันธุ์ยาง / เนื้อที่:</div>
                <div class="font-bold text-gray-800 text-sm">
                  พันธุ์: <span id="disp-rubber-clone" class="text-mezenc-teal">-</span> | <span id="disp-plot-area">-</span> ไร่
                </div>
                <div class="text-gray-500 text-[11px]">ที่ตั้ง: <span id="disp-plot-location">-</span></div>
              </div>
            </div>
          </div>

        </div>

        <!-- SECTION 2: ข้อมูลการกรีดและการเก็บเกี่ยว -->
        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-200 pb-2.5">
            <h4 class="font-bold text-mezenc-teal flex items-center gap-2 text-sm sm:text-base">
              <span>📅</span> <span>ส่วนที่ 2: วันที่และรอบการกรีดยาง</span>
            </h4>
            <span class="text-[11px] font-bold text-gray-500 bg-white border border-gray-200 px-2.5 py-0.5 rounded-full">
              ข้อมูลรอบเก็บเกี่ยว
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                วันที่กรีด / เก็บเกี่ยว <span class="text-rose-500">*</span>
              </label>
              <input 
                type="date" 
                id="yield-date" 
                class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs font-medium" 
                value="<?= date('Y-m-d') ?>" 
                required
              >
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                รอบการกรีดที่
              </label>
              <input 
                type="number" 
                id="yield-round" 
                class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs font-medium" 
                value="1" 
                min="1"
              >
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                ช่วงเวลาที่กรีด
              </label>
              <select id="yield-tapping-time" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs font-medium cursor-pointer">
                <option value="เช้ามืด (01:00 - 05:00 น.)" selected>เช้ามืด (01:00 - 05:00 น.)</option>
                <option value="ช่วงเช้า (06:00 - 09:00 น.)">ช่วงเช้า (06:00 - 09:00 น.)</option>
                <option value="ช่วงหัวค่ำ (20:00 - 24:00 น.)">ช่วงหัวค่ำ (20:00 - 24:00 น.)</option>
              </select>
            </div>
          </div>

        </div>

        <!-- SECTION 3: ปริมาณผลผลิตและการคำนวณรายได้ -->
        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-200 pb-2.5">
            <h4 class="font-bold text-mezenc-teal flex items-center gap-2 text-sm sm:text-base">
              <span>⚖️</span> <span>ส่วนที่ 3: ปริมาณน้ำยางสดและราคารับซื้อ</span>
            </h4>
            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full">
              คำนวณรายได้อัตโนมัติ
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                ปริมาณน้ำยางสด (กิโลกรัม) <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input 
                  type="number" 
                  step="0.01" 
                  id="yield-fresh-kg" 
                  class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl pl-3.5 pr-12 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs" 
                  placeholder="เช่น 120.50" 
                  oninput="calculateRevenue()" 
                  required
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-gray-400">กก.</span>
              </div>
              <span class="text-[11px] text-gray-400 block mt-1">น้ำหนักน้ำยางสดที่ชั่งได้จริง</span>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                ราคารับซื้อต่อ กก. (บาท) <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input 
                  type="number" 
                  step="0.25" 
                  id="yield-price" 
                  class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl pl-3.5 pr-16 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs" 
                  value="72.00" 
                  oninput="calculateRevenue()" 
                  required
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-gray-400">บาท/กก.</span>
              </div>
              <span class="text-[11px] text-gray-400 block mt-1">ราคาประกาศรับซื้อประจำวัน</span>
            </div>
          </div>

          <!-- Real-Time Highlight Revenue Calculation Card -->
          <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-[#e6f7f6] p-4 sm:p-5 rounded-2xl border-2 border-[#bee6e1] flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
            <div class="space-y-1 text-center sm:text-left">
              <div class="text-xs font-bold uppercase text-mezenc-teal tracking-wider flex items-center justify-center sm:justify-start gap-1.5">
                <span>💵</span> <span>ยอดเงินรวมรายได้จากการจำหน่าย</span>
              </div>
              <div class="text-[11px] text-gray-500 font-medium">
                สูตรคำนวณ: <span class="font-bold text-gray-700">น้ำยางสด (กก.) × ราคาต่อ กก.</span>
              </div>
            </div>

            <div class="text-center sm:text-right">
              <div id="calc-revenue-display" class="text-2xl sm:text-3xl font-black text-mezenc-teal tracking-tight">
                ฿0.00
              </div>
              <div class="text-[11px] text-emerald-600 font-bold">
                คำนวณตามจริง
              </div>
            </div>
          </div>

        </div>

        <!-- SECTION 4: ข้อมูลจุดรับซื้อและการส่งมอบ -->
        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-200 pb-2.5">
            <h4 class="font-bold text-mezenc-teal flex items-center gap-2 text-sm sm:text-base">
              <span>🏢</span> <span>ส่วนที่ 4: ข้อมูลการส่งมอบและผู้รับซื้อ</span>
            </h4>
            <span class="text-[11px] font-bold text-gray-500 bg-white border border-gray-200 px-2.5 py-0.5 rounded-full">
              ตรวจสอบย้อนกลับ
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                สถานที่ส่งมอบ / ผู้รับซื้อยาง
              </label>
              <input 
                type="text" 
                id="yield-buyer" 
                class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs font-medium" 
                value="สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด"
              >
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1.5">
                ผู้ส่งมอบ / ผู้บันทึกข้อมูล
              </label>
              <input 
                type="text" 
                id="yield-recorder" 
                class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs font-medium" 
                value="เจ้าของแปลงยางพารา"
              >
            </div>
          </div>

          <div>
            <label class="block font-bold text-gray-700 mb-1.5">
              หมายเหตุเพิ่มเติม
            </label>
            <textarea 
              id="yield-notes" 
              class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3.5 py-2 border border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs" 
              rows="2" 
              placeholder="เช่น กรีดช่วงเช้ามืด น้ำยางมีคุณภาพดี สภาพอากาศแจ่มใส ไร้ฝนตกชะล้าง"
            ></textarea>
          </div>

        </div>

        <!-- Modal Footer Actions -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
          <button 
            type="button" 
            onclick="closeAddYieldModal()" 
            class="w-full sm:flex-1 py-3 rounded-full border-2 border-gray-300 hover:bg-gray-100 text-gray-700 font-bold text-xs sm:text-sm transition-all cursor-pointer text-center"
          >
            ยกเลิก
          </button>
          
          <button 
            type="submit" 
            id="btn-save-yield"
            class="w-full sm:flex-1 py-3 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer text-center flex items-center justify-center gap-2"
          >
            <span>💾</span> <span>บันทึกผลผลิตรอบใหม่</span>
          </button>
        </div>

      </form>

    </div>
  </div>

  <!-- =========================================================================
       MODAL: EDIT YIELD REGISTRY (แก้ไขข้อมูลผลผลิตน้ำยางสด)
       ========================================================================= -->
  <div id="editYieldModal" class="modal-overlay">
    <div class="modal-card w-full max-w-3xl max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-[#bee6e1]">
      
      <!-- Modal Header -->
      <div class="p-5 sm:p-6 text-white bg-mezenc-teal relative flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shadow-inner border border-white/20">
            ✏️
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-widest font-bold text-mezenc-mint">
              EDIT YIELD RECORD • แก้ไขข้อมูลผลผลิตน้ำยางสด
            </div>
            <h3 class="text-lg sm:text-xl font-black text-white" id="edit-modal-title">
              แก้ไขข้อมูลผลผลิต
            </h3>
          </div>
        </div>

        <button 
          type="button" 
          onclick="closeEditYieldModal()" 
          class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-all cursor-pointer hover:rotate-90"
        >
          ✕
        </button>
      </div>

      <!-- Modal Body -->
      <form id="edit-yield-form" onsubmit="handleSaveEditYield(event)" class="overflow-y-auto custom-scrollbar p-5 sm:p-7 space-y-6 flex-1 text-xs sm:text-sm">
        <input type="hidden" id="edit-yield-id">
        <input type="hidden" id="edit-plot-id">

        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-2">
          <div class="font-bold text-mezenc-teal flex items-center gap-2">
            <span>📍</span> <span>แปลงปลูก:</span> <span id="edit-disp-plot-info" class="text-gray-800 font-semibold">-</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">วันที่เก็บเกี่ยว *</label>
            <input type="date" id="edit-yield-date" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" required>
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">รอบการกรีดที่</label>
            <input type="number" id="edit-yield-round" min="1" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs">
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">สถานที่ส่งมอบ / ผู้รับซื้อ</label>
            <input type="text" id="edit-yield-buyer" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">ปริมาณน้ำยางสด (กก.) *</label>
            <input type="number" step="0.01" id="edit-yield-fresh-kg" class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" oninput="calculateEditRevenue()" required>
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">ราคารับซื้อต่อ กก. (บาท) *</label>
            <input type="number" step="0.25" id="edit-yield-price" class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" oninput="calculateEditRevenue()" required>
          </div>
        </div>

        <!-- Revenue Highlight Box -->
        <div class="bg-gradient-to-r from-emerald-50 to-[#e6f7f6] p-4 rounded-2xl border-2 border-[#bee6e1] flex items-center justify-between">
          <span class="text-xs font-bold uppercase text-mezenc-teal">💵 ยอดเงินรวมคำนวณอัตโนมัติ</span>
          <span id="edit-calc-revenue-display" class="text-2xl font-black text-mezenc-teal">฿0.00</span>
        </div>

        <div>
          <label class="block font-bold text-gray-700 mb-1.5">หมายเหตุเพิ่มเติม</label>
          <textarea id="edit-yield-notes" rows="2" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3.5 py-2 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs"></textarea>
        </div>

        <!-- Modal Actions -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
          <button type="button" onclick="closeEditYieldModal()" class="w-full sm:flex-1 py-3 rounded-full border-2 border-gray-300 hover:bg-gray-100 text-gray-700 font-bold text-xs sm:text-sm transition-all cursor-pointer text-center">
            ยกเลิก
          </button>
          <button type="submit" id="btn-save-edit-yield" class="w-full sm:flex-1 py-3 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2">
            <span>💾</span> <span>บันทึกการแก้ไข</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- =========================================================================
       4. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี 14px)
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

  <!-- Pass Plot & Farmer Data to JavaScript -->
  <script>
    const PLOTS_DATA = <?= json_encode($plots, JSON_UNESCAPED_UNICODE) ?>;
    window.CURRENT_USER = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE) ?>;
    window.IS_ADMIN = <?= $isUserAdmin ? 'true' : 'false' ?>;

    let debounceTimer = null;
    function debounceLoadYields() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        loadYields();
      }, 350);
    }

    function clearAdminFilters() {
      if (document.getElementById('search-name')) document.getElementById('search-name').value = '';
      if (document.getElementById('search-plot-code')) document.getElementById('search-plot-code').value = '';
      if (document.getElementById('search-title-deed')) document.getElementById('search-title-deed').value = '';
      if (document.getElementById('filter-yield-plot')) document.getElementById('filter-yield-plot').value = '';
      loadYields();
    }

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

    // Modal Handlers (Add Yield)
    function openAddYieldModal() {
      const modal = document.getElementById('addYieldModal');
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeAddYieldModal() {
      const modal = document.getElementById('addYieldModal');
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    // Modal Handlers (Edit Yield)
    async function openEditYieldModal(id) {
      try {
        const res = await fetch(`api/yields.php?id=${id}`);
        const data = await res.json();
        if (!data.success || !data.yield) {
          alert('ไม่พบข้อมูลผลผลิต');
          return;
        }
        const y = data.yield;
        document.getElementById('edit-yield-id').value = y.id;
        document.getElementById('edit-plot-id').value = y.plot_id;
        document.getElementById('edit-disp-plot-info').textContent = `${y.plot_name} (${y.plot_code || '-'}) | พันธุ์ ${y.rubber_clone || 'RRIM 600'}`;
        document.getElementById('edit-yield-date').value = y.harvest_date;
        document.getElementById('edit-yield-round').value = y.tapping_round || 1;
        document.getElementById('edit-yield-fresh-kg').value = parseFloat(y.fresh_latex_kg) || 0;
        document.getElementById('edit-yield-price').value = parseFloat(y.price_per_kg) || 0;
        document.getElementById('edit-yield-buyer').value = y.buyer_name || '';
        document.getElementById('edit-yield-notes').value = y.notes || '';
        calculateEditRevenue();

        const modal = document.getElementById('editYieldModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      } catch (e) {
        console.error('Error fetching yield for edit:', e);
      }
    }

    function closeEditYieldModal() {
      const modal = document.getElementById('editYieldModal');
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    function calculateEditRevenue() {
      const fresh = parseFloat(document.getElementById('edit-yield-fresh-kg').value) || 0;
      const price = parseFloat(document.getElementById('edit-yield-price').value) || 0;
      const rev = (fresh * price).toFixed(2);
      document.getElementById('edit-calc-revenue-display').textContent = `฿${parseFloat(rev).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    async function handleSaveEditYield(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-save-edit-yield');
      btn.disabled = true;
      btn.innerHTML = `<span>⏳</span> <span>กำลังบันทึก...</span>`;

      const id = parseInt(document.getElementById('edit-yield-id').value);
      const payload = {
        id: id,
        plot_id: parseInt(document.getElementById('edit-plot-id').value),
        harvest_date: document.getElementById('edit-yield-date').value,
        tapping_round: parseInt(document.getElementById('edit-yield-round').value) || 1,
        fresh_latex_kg: parseFloat(document.getElementById('edit-yield-fresh-kg').value) || 0,
        price_per_kg: parseFloat(document.getElementById('edit-yield-price').value) || 0,
        buyer_name: document.getElementById('edit-yield-buyer').value,
        notes: document.getElementById('edit-yield-notes').value
      };

      try {
        const res = await fetch('api/yields.php', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('แก้ไขข้อมูลผลผลิตเรียบร้อยแล้ว', 'success');
          } else {
            alert('แก้ไขข้อมูลผลผลิตเรียบร้อยแล้ว');
          }
          closeEditYieldModal();
          loadYields();
        } else {
          alert(data.message || 'ไม่สามารถแก้ไขข้อมูลได้');
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>💾</span> <span>บันทึกการแก้ไข</span>`;
      }
    }

    // Dynamic Plot Select Callback
    function onPlotSelectChange() {
      const plotId = parseInt(document.getElementById('yield-plot-id').value);
      const dynamicCard = document.getElementById('plot-dynamic-card');

      if (!plotId) {
        dynamicCard.classList.add('hidden');
        return;
      }

      const plot = PLOTS_DATA.find(p => parseInt(p.id) === plotId);
      if (plot) {
        const farmerName = (plot.first_name) ? `${plot.prefix || ''}${plot.first_name} ${plot.last_name}` : 'ไม่ระบุ';
        document.getElementById('disp-farmer-name').textContent = farmerName;
        document.getElementById('disp-farmer-code').textContent = plot.farmer_code || '-';
        document.getElementById('disp-rubber-clone').textContent = plot.rubber_clone || '-';
        document.getElementById('disp-plot-area').textContent = parseFloat(plot.area_rai || 0).toLocaleString();
        const loc = (plot.subdistrict && plot.district) ? `ต.${plot.subdistrict} อ.${plot.district} จ.${plot.province || 'สุราษฎร์ธานี'}` : 'จ.สุราษฎร์ธานี';
        document.getElementById('disp-plot-location').textContent = loc;
        dynamicCard.classList.remove('hidden');
      } else {
        dynamicCard.classList.add('hidden');
      }
    }

    // Real-Time Revenue Calculation
    function calculateRevenue() {
      const fresh = parseFloat(document.getElementById('yield-fresh-kg').value) || 0;
      const price = parseFloat(document.getElementById('yield-price').value) || 0;
      const revenue = (fresh * price).toFixed(2);

      document.getElementById('calc-revenue-display').textContent = `฿${parseFloat(revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    // Load Yields Table via AJAX
    async function loadYields() {
      const plotId = document.getElementById('filter-yield-plot') ? document.getElementById('filter-yield-plot').value : '';
      const sName = document.getElementById('search-name') ? document.getElementById('search-name').value.trim() : '';
      const sCode = document.getElementById('search-plot-code') ? document.getElementById('search-plot-code').value.trim() : '';
      const sDeed = document.getElementById('search-title-deed') ? document.getElementById('search-title-deed').value.trim() : '';

      let params = new URLSearchParams();
      if (plotId) params.append('plot_id', plotId);
      if (sName) params.append('search_name', sName);
      if (sCode) params.append('search_plot_code', sCode);
      if (sDeed) params.append('search_title_deed', sDeed);

      let url = 'api/yields.php' + (params.toString() ? '?' + params.toString() : '');

      try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) return;

        const s = data.summary;
        if (document.getElementById('sum-fresh-latex')) {
          document.getElementById('sum-fresh-latex').textContent = `${parseFloat(s.total_fresh_latex || 0).toLocaleString()} กก.`;
        }
        if (document.getElementById('sum-revenue')) {
          document.getElementById('sum-revenue').textContent = `฿${parseFloat(s.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
        if (document.getElementById('sum-avg-price')) {
          document.getElementById('sum-avg-price').textContent = `฿${parseFloat(s.avg_price || 0).toFixed(2)} / กก.`;
        }
        if (document.getElementById('sum-total-records')) {
          document.getElementById('sum-total-records').textContent = `${parseInt(s.total_records || 0).toLocaleString()} ครั้ง`;
        }

        const tbody = document.getElementById('yields-table-body');
        if (!data.yields || data.yields.length === 0) {
          tbody.innerHTML = '<tr><td colspan="9" class="text-center py-12 text-gray-400 text-xs">ยังไม่มีข้อมูลผลผลิตในเงื่อนไขนี้</td></tr>';
          return;
        }

        const canDelete = data.can_delete === true || window.IS_ADMIN === true;

        let html = '';
        data.yields.forEach(y => {
          html += `
            <tr class="hover:bg-[#f4faf7] transition-colors">
              <td class="py-4 px-4 font-bold text-gray-800 whitespace-nowrap">${y.harvest_date}</td>
              <td class="py-4 px-4">
                <span class="font-bold text-mezenc-teal">${y.plot_name}</span> 
                <span class="text-[11px] text-gray-400 font-mono block">(${y.plot_code || '-'})</span>
              </td>
              <td class="py-4 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#f8faf9] text-gray-700 border border-gray-200">
                  ${y.rubber_clone || 'RRIM 600'}
                </span>
              </td>
              <td class="py-4 px-4 text-gray-800 font-medium">
                <div>${y.prefix || ''}${y.first_name} ${y.last_name}</div>
                <span class="text-[10px] text-gray-400 font-mono">${y.farmer_code || ''}</span>
              </td>
              <td class="py-4 px-4 text-right font-extrabold text-mezenc-teal whitespace-nowrap text-sm sm:text-base">
                ${parseFloat(y.fresh_latex_kg).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
              </td>
              <td class="py-4 px-4 text-right font-medium text-gray-700 whitespace-nowrap">
                ฿${parseFloat(y.price_per_kg).toFixed(2)}
              </td>
              <td class="py-4 px-4 text-right font-black text-emerald-600 whitespace-nowrap text-sm sm:text-base">
                ฿${parseFloat(y.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </td>
              <td class="py-4 px-4 text-gray-600 text-xs max-w-[200px] truncate" title="${y.buyer_name || '-'}">
                ${y.buyer_name || '-'}
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button 
                    onclick="openEditYieldModal(${y.id})" 
                    class="w-8 h-8 rounded-full bg-[#dcf5f5] hover:bg-[#00a699] text-[#00a699] hover:text-white flex items-center justify-center transition-all shadow-xs cursor-pointer"
                    title="แก้ไขผลผลิตรอบนี้"
                  >
                    ✏️
                  </button>
                  ${canDelete ? `
                  <button 
                    onclick="deleteYield(${y.id})" 
                    class="w-8 h-8 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-all shadow-xs cursor-pointer"
                    title="ลบรายการนี้"
                  >
                    🗑️
                  </button>` : ''}
                </div>
              </td>
            </tr>
          `;
        });

        tbody.innerHTML = html;

      } catch (e) {
        console.error('Error loading yields:', e);
      }
    }

    // Save Yield Submission
    async function handleSaveYield(e) {
      e.preventDefault();

      const btnSave = document.getElementById('btn-save-yield');
      btnSave.disabled = true;
      btnSave.innerHTML = `<span>⏳</span> <span>กำลังบันทึกข้อมูล...</span>`;

      const payload = {
        plot_id: parseInt(document.getElementById('yield-plot-id').value),
        harvest_date: document.getElementById('yield-date').value,
        tapping_round: parseInt(document.getElementById('yield-round').value) || 1,
        fresh_latex_kg: parseFloat(document.getElementById('yield-fresh-kg').value) || 0,
        price_per_kg: parseFloat(document.getElementById('yield-price').value) || 0,
        buyer_name: document.getElementById('yield-buyer').value,
        notes: document.getElementById('yield-notes').value
      };

      try {
        const res = await fetch('api/yields.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('บันทึกข้อมูลผลผลิตน้ำยางสดสำเร็จ!', 'success');
          } else {
            alert('บันทึกข้อมูลผลผลิตน้ำยางสดสำเร็จ!');
          }
          closeAddYieldModal();
          document.getElementById('new-yield-form').reset();
          document.getElementById('plot-dynamic-card').classList.add('hidden');
          document.getElementById('calc-revenue-display').textContent = '฿0.00';
          loadYields();
        } else {
          alert(data.message || 'บันทึกไม่สำเร็จ');
        }
      } catch (e) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = `<span>💾</span> <span>บันทึกผลผลิตรอบใหม่</span>`;
      }
    }

    // Delete Yield Item
    async function deleteYield(id) {
      if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการผลผลิตนี้?')) return;

      try {
        const res = await fetch(`api/yields.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('ลบรายการผลผลิตแล้ว', 'success');
          } else {
            alert('ลบรายการผลผลิตแล้ว');
          }
          loadYields();
        } else {
          alert(data.message || 'ไม่สามารถลบข้อมูลได้');
        }
      } catch (e) {
        alert('ไม่สามารถลบข้อมูลได้');
      }
    }

    // Initialize on DOM Ready
    document.addEventListener('DOMContentLoaded', () => {
      loadYields();
    });
  </script>
</body>
</html>
