<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$currentUser = getCurrentUser();
$current_role = $currentUser['role'] ?? 'farmer';
$user_name = $currentUser['full_name'] ?? 'ผู้ใช้งานระบบ';

$pdo = getDatabaseConnection();

$isUserAdmin = isAdmin();
$isFactory = in_array($current_role, ['factory', 'buyer', 'trader'], true) || (isset($_GET['mode']) && $_GET['mode'] === 'factory');
$isFarmer = ($current_role === 'farmer') || (!$isUserAdmin && !$isFactory);
$factoryCompanyName = 'บริษัท ไทยเจริญเลเท็กซ์ อินดัสทรี จำกัด';
if (!empty($user_name) && $user_name !== 'ผู้ใช้งานระบบ' && !str_contains($user_name, 'เกษตรกร') && !str_contains($user_name, 'Admin')) {
    $factoryCompanyName = $user_name;
}

$farmerId = $currentUser['farmer_id'] ?? null;
if (!$isUserAdmin && !$isFactory && !$farmerId && isset($_SESSION['user_id'])) {
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

// Fetch plots with farmer profile details (RBAC: Farmer only sees own plots, Admin sees all)
$plots = [];
try {
    if (!$isUserAdmin) {
        $stmt = $pdo->prepare("
            SELECT p.id, p.plot_code, p.plot_name, p.rubber_clone, p.area_rai, p.tree_count, p.eudr_status, p.title_deed_type,
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
            SELECT p.id, p.plot_code, p.plot_name, p.rubber_clone, p.area_rai, p.tree_count, p.eudr_status, p.title_deed_type,
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
        $stmt = $pdo->prepare("SELECT id, plot_code, plot_name, rubber_clone, area_rai, tree_count, eudr_status, title_deed_type FROM rubber_plots WHERE farmer_id = ? ORDER BY plot_name ASC");
        $stmt->execute([$farmerId ?: -1]);
        $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $plots = $pdo->query("SELECT id, plot_code, plot_name, rubber_clone, area_rai, tree_count, eudr_status, title_deed_type FROM rubber_plots ORDER BY plot_name ASC")->fetchAll(PDO::FETCH_ASSOC);
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

  <!-- Core Eco-GIS Styles & Centralized i18n Engine -->
  <link rel="stylesheet" href="assets/css/style.css" />
  <script src="assets/js/i18n.js?v=<?= time() ?>"></script>
  <!-- FontAwesome 6 CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- HTML5 QR Code Scanner CDN -->
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

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

    @media print {
      header, footer, nav, #mobile-drawer, #yield-toolbar-panel, #btn-mode-farmer, #btn-mode-factory, .modal-overlay, #btn-export-main, .no-print {
        display: none !important;
      }
      main {
        margin-top: 0 !important;
        padding: 0 !important;
        max-width: 100% !important;
      }
      .bg-white\/95, .bg-white\/90, .backdrop-blur-md {
        background: #ffffff !important;
        backdrop-filter: none !important;
      }
      table {
        width: 100% !important;
        border: 1px solid #e2e8f0 !important;
      }
      th, td {
        padding: 8px 10px !important;
        font-size: 11px !important;
      }
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
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="index.php" data-i18n="nav_home">
          หน้าแรก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="overview.php" data-i18n="nav_gis">
          แผนที่ GIS
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php" data-i18n="nav_dashboard">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php" data-i18n="nav_plots">
          แปลงปลูก
        </a>
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="yields.php" data-i18n="nav_yields">
          ผลผลิต
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="contact.php" data-i18n="nav_contact">
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT ACTIONS (User / Language Toggle / Login / Mobile Menu) -->
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

    <!-- HERO CONTENT (Centered typography matching overview.php and map.php) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow" data-i18n="yd_hero_tag">
          LATEX PRODUCTION & YIELD TRACKING SYSTEM
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md" data-i18n="yd_hero_title">
          ระบบบันทึกผลผลิตน้ำยางสด
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow" data-i18n="yd_hero_sub">
          บันทึกปริมาณน้ำยางสด ราคารับซื้อ เชื่อมโยงข้อมูลแปลงปลูกและเกษตรกรสู่ระบบตรวจสอบย้อนกลับ (Traceability) ตามมาตรฐาน EUDR
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
            <i class="fa-solid fa-xmark"></i>
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
          <a href="index.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <i class="fa-solid fa-house w-4 text-center"></i> <span data-i18n="nav_home">หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <i class="fa-solid fa-satellite w-4 text-center"></i> <span data-i18n="nav_gis">แผนที่ GIS</span>
          </a>
          <a href="dashboard.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <i class="fa-solid fa-chart-pie w-4 text-center"></i> <span data-i18n="nav_dashboard">แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <i class="fa-solid fa-map-location-dot w-4 text-center"></i> <span data-i18n="nav_plots">แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-2.5 rounded-xl bg-mezenc-teal text-white font-bold transition-all shadow-xs flex items-center gap-3">
            <i class="fa-solid fa-flask-vial w-4 text-center"></i> <span data-i18n="nav_yields">ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <i class="fa-solid fa-phone w-4 text-center"></i> <span data-i18n="nav_contact">ติดต่อเรา</span>
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
        <a href="logout.php" class="w-full py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold text-center block text-xs transition-all flex items-center justify-center gap-2" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> <span data-i18n="nav_logout">ออกจากระบบ (Logout)</span>
        </a>
        <div class="text-center text-[11px] text-slate-400 pt-1 font-normal">
          &copy; 2026 GeoRubber Watch &bull; ม.อ. สุราษฎร์ธานี
        </div>
      </div>

    </div>
  </div>

  <!-- =========================================================================
       2. MAIN PRODUCTION WORKSPACE & KPI DASHBOARD (Elevated Floating Container)
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1 space-y-6">
    
    <?php if (!$isFarmer): ?>
    <!-- FACTORY PURCHASING POINT & ANTI-FRAUD HUB PANEL -->
    <div id="factory-purchasing-panel" class="bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl border-2 border-mezenc-brightCyan/60 p-5 sm:p-7 shadow-xl space-y-6">
      <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-100 gap-3">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl font-bold border border-[#bee6e1] shadow-xs">
            <i class="fa-solid fa-industry"></i>
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-black text-mezenc-teal flex items-center gap-2">
              <span>จุดรับซื้อน้ำยางสดและระบบตรวจสอบการสวมสิทธิ์ (Traceability & Anti-Fraud Hub)</span>
            </h3>
            <p class="text-xs text-gray-500">
              สแกน QR Code หรือค้นหาด้วยรหัสแปลง / เลขบัตรประชาชน เพื่อตรวจสอบโควตาผลผลิตสะสมต่อไร่ และบันทึกการรับซื้อน้ำยางสด
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Search / Scan Bar -->
      <div class="bg-[#f8faf9] p-4 rounded-2xl border border-gray-200/80 space-y-3">
        <label class="block text-xs sm:text-sm font-bold text-gray-700">
          สแกน QR Code หรือค้นหาแปลงปลูก (รหัสแปลง / เลขบัตรประชาชน 13 หลัก / เลขที่โฉนด / EUDR Token):
        </label>
        <div class="flex flex-col sm:flex-row gap-2.5">
          <div class="relative flex-1">
            <input 
              type="text" 
              id="factory-search-query" 
              placeholder="เช่น RB-ST-2026-020 หรือ 1-8499-00123-45-6 หรือ EUDR-TH-ST-84000..." 
              class="w-full bg-white text-gray-800 font-semibold text-sm rounded-xl pl-4 pr-10 py-3 border-2 border-gray-300 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none shadow-xs" 
              onkeypress="if(event.key === 'Enter') searchPlotPurchasing()"
            >
            <button 
              type="button" 
              onclick="searchPlotPurchasing()" 
              class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-mezenc-brightCyan hover:bg-mezenc-teal text-white flex items-center justify-center transition shadow-xs cursor-pointer"
              title="ค้นหาแปลง"
            >
              <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </button>
          </div>
          <button 
            type="button" 
            onclick="openQrScannerModal()" 
            class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer shrink-0 border border-emerald-500"
            title="เปิดกล้องสแกน QR Code แปลงปลูกเกษตรกร"
          >
            <i class="fa-solid fa-qrcode text-base"></i> <span>สแกน QR Code</span>
          </button>
          <button 
            type="button" 
            onclick="searchPlotPurchasing()" 
            class="px-6 py-3 rounded-xl bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer shrink-0"
          >
            <i class="fa-solid fa-bolt"></i> <span>ค้นหาและตรวจสอบสิทธิ์</span>
          </button>
        </div>
      </div>

      <!-- Search Result & Live Biological Capacity Meter (Dynamic) -->
      <div id="factory-result-box" class="hidden space-y-4 sm:space-y-5">
        <!-- Plot & Farmer Profile Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 sm:gap-4">
          <!-- Card 1: Plot Info -->
          <div class="p-4 sm:p-5 rounded-2xl bg-[#f4faf7] border border-[#bee6e1] space-y-2 shadow-2xs">
            <span class="text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider block">ข้อมูลแปลงปลูก</span>
            <div id="factory-plot-name" class="font-extrabold text-mezenc-teal text-sm sm:text-base truncate">-</div>
            <div class="text-xs sm:text-sm text-gray-600">รหัสแปลง: <b id="factory-plot-code" class="font-mono text-gray-800">-</b></div>
            <div class="text-xs sm:text-sm text-gray-600 truncate">เอกสารสิทธิ์: <span id="factory-title-deed" class="font-medium text-gray-800">-</span></div>
          </div>

          <!-- Card 2: Farmer Info -->
          <div class="p-4 sm:p-5 rounded-2xl bg-[#f4faf7] border border-[#bee6e1] space-y-2 shadow-2xs">
            <span class="text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider block">ข้อมูลเกษตรกรเจ้าของแปลง</span>
            <div id="factory-farmer-name" class="font-extrabold text-gray-800 text-sm sm:text-base truncate">-</div>
            <div class="text-xs sm:text-sm text-gray-600">เลขบัตร ปชช.: <b id="factory-farmer-idcard" class="font-mono text-mezenc-teal">-</b></div>
            <div class="text-xs sm:text-sm text-gray-600">รหัสเกษตรกร: <span id="factory-farmer-code" class="font-mono text-gray-800">-</span></div>
          </div>

          <!-- Card 3: EUDR & Coordinates & Tree Count -->
          <div class="p-4 sm:p-5 rounded-2xl bg-[#f4faf7] border border-[#bee6e1] space-y-2 shadow-2xs sm:col-span-2 lg:col-span-1">
            <span class="text-[11px] sm:text-xs font-bold text-gray-500 uppercase tracking-wider block">การรับรอง EUDR & พิกัด</span>
            <div class="flex items-center gap-2">
              <span id="factory-eudr-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5 shadow-2xs">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> ปลอดการตัดไม้ 100%
              </span>
            </div>
            <div class="text-xs sm:text-sm text-gray-700 flex items-center justify-between border-b border-gray-200/60 pb-1">
              <span>เนื้อที่แปลง:</span>
              <span class="font-bold text-mezenc-teal"><b id="factory-plot-area" class="text-sm sm:text-base font-extrabold">-</b> ไร่</span>
            </div>
            <div class="text-xs sm:text-sm text-gray-700 flex items-center justify-between border-b border-gray-200/60 pb-1">
              <span>จำนวนต้นยางพารา:</span>
              <span class="font-bold text-emerald-800"><b id="factory-plot-tree-count" class="text-sm sm:text-base font-extrabold">-</b> ต้น</span>
            </div>
            <div class="text-xs sm:text-sm text-gray-700 flex items-center justify-between border-b border-gray-200/60 pb-1">
              <span>พันธุ์ยาง:</span>
              <span id="factory-rubber-clone" class="font-bold text-gray-800">-</span>
            </div>
            <div class="text-[11px] sm:text-xs text-gray-500 flex items-center gap-1 pt-0.5 truncate">
              <i class="fa-solid fa-location-dot text-emerald-600 shrink-0"></i>
              <span id="factory-plot-coords" class="truncate">พิกัด GPS/GIS พร้อมตรวจสอบ</span>
            </div>
          </div>
        </div>

        <!-- Live Biological Capacity Meter Bar -->
        <div class="p-5 rounded-2xl bg-white border-2 border-[#bee6e1] shadow-sm space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-sm shrink-0">
                <i class="fa-solid fa-chart-line"></i>
              </div>
              <div>
                <h4 class="font-bold text-mezenc-teal text-sm sm:text-base">
                  เกจวัดขีดจำกัดผลผลิตทางชีวภาพประจำเดือนนี้ (Biological Capacity Meter)
                </h4>
                <p class="text-[11px] text-gray-400">
                  คำนวณจากเกณฑ์ชีวภาพสวนยางพารา: 1 ต้นได้ผลผลิตประมาณ 3–4.5 กก. (น้ำยางสด)/เดือน (เฉลี่ย 70–80 ต้นต่อไร่)
                </p>
              </div>
            </div>
            <div class="text-right">
              <span id="factory-capacity-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                ปกติ (0.0%)
              </span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden p-0.5 border border-gray-200">
            <div id="factory-capacity-bar" class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: 0%;"></div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs pt-1">
            <div class="p-2 bg-gray-50 rounded-xl border border-gray-200">
              <span class="text-gray-400 block text-[10px]">ผลผลิตสะสมเดือนนี้</span>
              <b id="factory-month-sum" class="text-gray-800 text-sm font-mono font-bold">0.0 กก.</b>
            </div>
            <div class="p-2 bg-gray-50 rounded-xl border border-gray-200">
              <span class="text-gray-400 block text-[10px]">ขีดจำกัดสูงสุด (Cap)</span>
              <b id="factory-month-limit" class="text-mezenc-teal text-sm font-mono font-bold">0.0 กก.</b>
            </div>
            <div class="p-2 bg-gray-50 rounded-xl border border-gray-200">
              <span class="text-gray-400 block text-[10px]">สิทธิ์ที่ส่งมอบได้อีก</span>
              <b id="factory-month-remaining" class="text-emerald-700 text-sm font-mono font-bold">0.0 กก.</b>
            </div>
            <div class="p-2 bg-gray-50 rounded-xl border border-gray-200">
              <span class="text-gray-400 block text-[10px]">รอบรับซื้อ (อัตโนมัติ)</span>
              <b id="factory-round-count" class="text-mezenc-teal text-sm font-mono font-bold">รอบที่ 1</b>
            </div>
          </div>

          <!-- Dynamic Anomaly Alert Notice Box (High Risk) -->
          <div id="factory-anomaly-alert" class="hidden p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-rose-50 to-red-50 border-2 border-rose-300 text-rose-800 text-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm animate-pulse">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg shrink-0 border border-rose-300">
                <i class="fa-solid fa-triangle-exclamation"></i>
              </div>
              <div class="space-y-0.5">
                <b class="text-rose-900 block font-black text-xs sm:text-sm">ตรวจพบความผิดปกติของปริมาณผลผลิต (High Risk)</b>
                <span id="factory-anomaly-text" class="text-rose-700 block text-[11px] sm:text-xs leading-relaxed">
                  ผลผลิตต่อวันเกินเกณฑ์ชีวภาพสูงสุดที่ต้นยางจะผลิตได้ หรือมีอัตราเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติ
                </span>
              </div>
            </div>
            <button 
              type="button" 
              onclick="openAnomalyModalFromFactory()" 
              class="w-full sm:w-auto px-4 py-2 rounded-xl bg-rose-700 hover:bg-rose-800 text-white font-extrabold text-xs shadow-sm hover:shadow transition flex items-center justify-center gap-1.5 shrink-0 cursor-pointer"
            >
              <i class="fa-solid fa-magnifying-glass-chart"></i>
              <span>ตรวจสอบ</span>
            </button>
          </div>
        </div>

        <!-- Rapid Purchasing Entry Form -->
        <form id="factory-quick-form" onsubmit="handleFactoryQuickSubmit(event)" class="bg-[#f8faf9] p-5 sm:p-6 rounded-2xl border border-[#bee6e1] space-y-4">
          <input type="hidden" id="factory-plot-id">

          <div class="font-bold text-mezenc-teal text-sm sm:text-base flex items-center gap-2 border-b border-gray-200 pb-2">
            <i class="fa-solid fa-scale-balanced text-mezenc-teal"></i> <span>บันทึกการชั่งและรับซื้อน้ำยางสดรอบนี้</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs sm:text-sm">
            <div>
              <label class="block font-bold text-gray-700 mb-1">น้ำหนักน้ำยางสด (กก.) *</label>
              <div class="relative">
                <input 
                  type="number" 
                  step="0.01" 
                  id="factory-fresh-kg" 
                  class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl pl-3.5 pr-12 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" 
                  placeholder="เช่น 150.00" 
                  oninput="onFactoryFreshKgInput()" 
                  required
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-gray-400">กก.</span>
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">เปอร์เซ็นต์เนื้อยางแห้ง (% DRC) *</label>
              <div class="relative">
                <input 
                  type="number" 
                  step="0.1" 
                  id="factory-drc-pct" 
                  class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl pl-3.5 pr-10 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" 
                  value="33.5" 
                  oninput="onFactoryFreshKgInput()" 
                  required
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-gray-400">%</span>
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 mb-1">ราคารับซื้อต่อ กก. (บาท) *</label>
              <div class="relative">
                <input 
                  type="number" 
                  step="0.25" 
                  id="factory-price-kg" 
                  class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl pl-3.5 pr-16 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" 
                  value="72.00" 
                  oninput="onFactoryFreshKgInput()" 
                  required
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 font-bold text-xs text-gray-400">บาท/กก.</span>
              </div>
            </div>
          </div>

          <!-- Calculated Highlights (Dry Rubber kg & Total Revenue) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="p-3.5 bg-white rounded-xl border border-gray-200 flex justify-between items-center shadow-2xs">
              <span class="text-xs text-gray-500 font-medium">เนื้อยางแห้งคำนวณได้ (Dry Rubber):</span>
              <b id="factory-calc-dry-kg" class="text-base font-black text-mezenc-teal font-mono">0.00 กก.</b>
            </div>
            <div class="p-3.5 bg-gradient-to-r from-emerald-50 to-[#e6f7f6] rounded-xl border-2 border-[#bee6e1] flex justify-between items-center shadow-2xs">
              <span class="text-xs font-bold text-mezenc-teal uppercase">รวมเงินจ่ายเกษตรกร:</span>
              <b id="factory-calc-revenue" class="text-xl font-black text-mezenc-teal">฿0.00</b>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold text-gray-700">ผู้รับซื้อ / โรงงาน (ล็อกตามบัญชี)</label>
                <span class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                  <i class="fa-solid fa-lock text-[10px]"></i> บัญชีที่ยืนยันตัวตน
                </span>
              </div>
              <div class="relative">
                <input 
                  type="text" 
                  id="factory-buyer-name" 
                  value="<?= htmlspecialchars($factoryCompanyName) ?>" 
                  readonly
                  class="w-full bg-gray-100 text-gray-800 font-extrabold rounded-xl px-3 py-2 border border-gray-300 outline-none cursor-not-allowed select-none shadow-xs"
                >
                <i class="fa-solid fa-building absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
              </div>
            </div>
            <div>
              <label class="block font-bold text-gray-700 mb-1">วันที่รับซื้อ</label>
              <input 
                type="date" 
                id="factory-harvest-date" 
                value="<?= date('Y-m-d') ?>" 
                class="w-full bg-white text-gray-800 rounded-xl px-3 py-2 border border-gray-300 focus:border-mezenc-brightCyan outline-none"
              >
            </div>
          </div>

          <div class="pt-2">
            <button 
              type="submit" 
              id="btn-factory-submit" 
              class="w-full py-3.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-black text-sm shadow-lg hover:shadow-xl transition-all cursor-pointer flex items-center justify-center gap-2"
            >
              <i class="fa-solid fa-floppy-disk"></i> <span>บันทึกการรับซื้อน้ำยางสดและเชื่อมโยง EUDR Traceability</span>
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons & Toolbar (Plot Dropdown Filter + Export Button) -->
    <div id="yield-toolbar-panel" class="bg-white/90 backdrop-blur-md p-4 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#bee6e1] shadow-md space-y-3">
      
      <!-- Top Row: Filters, Export & Search Inputs -->
      <div class="flex flex-wrap items-center justify-between gap-3">
        
        <!-- Left Side: Plot Filter & Admin Search -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Plot Dropdown Filter -->
          <div class="flex items-center gap-2">
            <span class="text-xs sm:text-sm font-bold text-gray-700 whitespace-nowrap" data-i18n="yd_lbl_select_plot"><i class="fa-solid fa-filter text-mezenc-teal mr-1"></i>แปลงปลูก:</span>
            <select 
              id="filter-yield-plot" 
              class="bg-[#f8faf9] text-gray-800 font-semibold text-xs sm:text-sm rounded-xl px-3.5 py-2.5 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-48 sm:w-64 cursor-pointer" 
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
          <!-- Admin Search Inputs (Farmer Name, Plot Code, Title Deed, National ID) -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Search Farmer Name -->
            <div class="relative">
              <input 
                type="text" 
                id="search-name" 
                placeholder="ชื่อ/เลขบัตร ปชช. เกษตรกร..." 
                data-i18n-placeholder="ct_ph_name"
                class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-40 sm:w-48"
                oninput="debounceLoadYields()"
              >
              <i class="fa-solid fa-user absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <!-- Search Plot Code / Name -->
            <div class="relative">
              <input 
                type="text" 
                id="search-plot-code" 
                placeholder="รหัส/ชื่อแปลง..." 
                data-i18n-placeholder="db_search_ph"
                class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-32 sm:w-40"
                oninput="debounceLoadYields()"
              >
              <i class="fa-solid fa-tree absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <!-- Search Title Deed No -->
            <div class="relative">
              <input 
                type="text" 
                id="search-title-deed" 
                placeholder="เลขที่โฉนด..." 
                data-i18n-placeholder="map_lbl_doc_no"
                class="bg-[#f8faf9] text-gray-800 text-xs rounded-xl pl-3 pr-7 py-2 border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white outline-none shadow-xs w-28 sm:w-36"
                oninput="debounceLoadYields()"
              >
              <i class="fa-solid fa-file-lines absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>

            <button 
              type="button" 
              onclick="clearAdminFilters()" 
              class="px-2.5 py-1.5 text-xs text-gray-400 hover:text-rose-500 underline"
              title="ล้างการค้นหา"
              data-i18n="db_clear_filter"
            >
              ล้างตัวกรอง
            </button>
          </div>
          <?php endif; ?>
        </div>

        <!-- Right Side: Export CSV Button (Only CSV) -->
        <div class="flex items-center gap-2.5 ml-auto">
          <a 
            href="javascript:void(0)" 
            onclick="triggerExport('csv')" 
            class="px-4 sm:px-5 py-2.5 rounded-xl bg-white hover:bg-[#e6f7f6] text-mezenc-teal font-bold text-xs sm:text-sm border-2 border-[#bee6e1] shadow-xs hover:border-mezenc-brightCyan transition-all flex items-center gap-2 cursor-pointer"
            id="btn-export-main"
            title="ส่งออกประวัติการขายเป็นไฟล์ CSV สำหรับ Microsoft Excel"
          >
            <i class="fa-solid fa-file-arrow-down"></i> <span>ส่งออกข้อมูล (CSV)</span>
          </a>
        </div>

      </div>

    </div>

    <!-- Production Logs Data Table Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] overflow-hidden">
      
      <!-- Table Header Bar -->
      <div class="p-4 sm:p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-white">
        <div>
          <h3 class="text-base sm:text-lg font-extrabold text-mezenc-teal flex items-center gap-2">
            <i class="fa-solid fa-clipboard-list text-mezenc-teal"></i> <span>ประวัติการเก็บเกี่ยวและการส่งมอบน้ำยางสด</span>
          </h3>
          <p class="text-xs text-gray-400 font-medium mt-0.5">
            <?= $isFarmer ? 'แสดงรายการรับซื้อน้ำยางสดของแปลงปลูก ปริมาณเนื้อยางแห้ง (% DRC) มูลค่ารายได้ และรหัสตรวจสอบย้อนกลับ EUDR' : 'แสดงรายการผลผลิตรายแปลง น้ำหนักน้ำยางสด ค่า DRC ตรวจจับความผิดปกติ และการคำนวณมูลค่ารายได้' ?>
          </p>
        </div>
        <?php if ($isFarmer): ?>
        <span class="text-[11px] font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
          <i class="fa-solid fa-lock text-[10px]"></i> มุมมองอ่านอย่างเดียว (Read-Only)
        </span>
        <?php endif; ?>
      </div>

      <!-- Responsive Table Viewport -->
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left border-collapse text-xs sm:text-sm">
          <thead>
            <?php if ($isFarmer): ?>
            <!-- 7 Standard Columns for Farmer View (Read-Only) -->
            <tr class="bg-[#f8faf9] border-b border-gray-200/80 text-mezenc-teal font-extrabold text-xs uppercase tracking-wider">
              <th class="py-4 px-4 whitespace-nowrap">วันที่รับซื้อ / วันที่บันทึก</th>
              <th class="py-4 px-4 whitespace-nowrap">แปลงปลูก / เลขที่เอกสารสิทธิ์</th>
              <th class="py-4 px-4 whitespace-nowrap">ชื่อโรงงาน / จุดรับซื้อ</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">น้ำหนักสด (กก.)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">% DRC (ยางแห้ง)</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">ราคา/กก. และยอดรวมสุทธิ (บาท)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">รหัส EUDR Traceability Token ประจำล็อต</th>
            </tr>
            <?php else: ?>
            <!-- Admin / Factory View with management & anomaly checks -->
            <tr class="bg-[#f8faf9] border-b border-gray-200/80 text-mezenc-teal font-extrabold text-xs uppercase tracking-wider">
              <th class="py-4 px-4 whitespace-nowrap">วันที่เก็บเกี่ยว / รับซื้อ</th>
              <th class="py-4 px-4 whitespace-nowrap">แปลงปลูก / เอกสารสิทธิ์</th>
              <th class="py-4 px-4 whitespace-nowrap">พันธุ์ยาง</th>
              <th class="py-4 px-4 whitespace-nowrap">เกษตรกรเจ้าของแปลง</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">น้ำยางสด (กก.)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">% DRC (ยางแห้ง)</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">ราคา/กก. (บาท)</th>
              <th class="py-4 px-4 whitespace-nowrap text-right">รวมเงินรายได้ (บาท)</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">การตรวจจับการสวมสิทธิ์</th>
              <th class="py-4 px-4 whitespace-nowrap">ผู้รับซื้อ / จุดรับซื้อ</th>
              <th class="py-4 px-4 whitespace-nowrap text-center">จัดการ</th>
            </tr>
            <?php endif; ?>
          </thead>
          <tbody id="yields-table-body" class="divide-y divide-gray-100 text-gray-700">
            <tr>
              <td colspan="<?= $isFarmer ? '7' : '11' ?>" class="text-center py-12 text-gray-400 text-xs" data-i18n="lbl_loading">
                กำลังโหลดข้อมูลผลผลิต...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <!-- =========================================================================
       2.5. MODAL: QR CODE SCANNER (สแกน QR แปลงปลูกเกษตรกร สำหรับจุดรับซื้อ/โรงงาน)
       ========================================================================= -->
  <div id="qrScannerModal" class="modal-overlay">
    <div class="modal-card w-full max-w-lg max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-emerald-400">
      
      <!-- Modal Header -->
      <div class="p-4 sm:p-5 text-white bg-emerald-700 relative flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shadow-inner border border-white/20">
            <i class="fa-solid fa-qrcode"></i>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-widest font-bold text-emerald-200">
              EUDR PLOT SCANNER • จุดรับซื้อยางโรงงาน
            </div>
            <h3 class="text-base sm:text-lg font-black text-white">
              สแกน QR Code แปลงปลูกของเกษตรกร
            </h3>
          </div>
        </div>

        <button 
          type="button" 
          onclick="closeQrScannerModal()" 
          class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center transition-all cursor-pointer hover:rotate-90"
          title="ปิดหน้าต่างสแกน"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-4 sm:p-6 overflow-y-auto custom-scrollbar space-y-4 text-xs sm:text-sm">
        
        <!-- Live Camera Viewport -->
        <div class="relative rounded-2xl overflow-hidden bg-black/90 border-2 border-dashed border-emerald-400 min-h-[260px] flex flex-col items-center justify-center shadow-inner">
          <div id="qr-reader" class="w-full"></div>
          <div id="qr-camera-placeholder" class="p-6 text-center text-gray-300 space-y-3">
            <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center mx-auto text-2xl text-emerald-400">
              <i class="fa-solid fa-camera"></i>
            </div>
            <div class="font-bold text-white text-sm">กำลังเปิดกล้องสแกน...</div>
            <p class="text-xs text-gray-400 max-w-xs mx-auto">
              นำกล้องส่องไปที่ QR Code บนหนังสือรับรองหรือแอปพลิเคชันของเกษตรกร
            </p>
          </div>
        </div>

        <!-- Scanner Controls & Status -->
        <div class="flex items-center justify-between text-xs px-1">
          <div id="qr-scan-status" class="font-bold text-emerald-700 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>พร้อมสแกน QR Code...</span>
          </div>
          <label class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold cursor-pointer transition flex items-center gap-1.5 border border-gray-300">
            <i class="fa-solid fa-folder-open"></i> <span>สแกนจากไฟล์รูป</span>
            <input type="file" id="qr-file-input" accept="image/*" class="hidden" onchange="handleQrFileUpload(this)">
          </label>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-3.5 sm:p-4 bg-gray-50 border-t border-gray-200/80 flex items-center justify-between shrink-0">
        <span class="text-[11px] text-gray-500 font-medium">
          ระบบจะดึงข้อมูลแปลงและโควตาอัตโนมัติเมื่ออ่าน QR ได้
        </span>
        <button 
          type="button" 
          onclick="closeQrScannerModal()" 
          class="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs transition cursor-pointer"
        >
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>

  <!-- =========================================================================
       3. MODAL: ADD YIELD REGISTRY (บันทึกผลผลิตน้ำยางสดรอบใหม่ - รูปแบบคล้ายกรอกข้อมูลเกษตรกร)
       ========================================================================= -->
  <div id="addYieldModal" class="modal-overlay">
    <div class="modal-card w-full max-w-3xl max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-[#bee6e1]">
      
      <!-- Modal Header -->
      <div class="p-5 sm:p-6 text-white bg-mezenc-teal relative flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center text-xl shadow-inner border border-white/20">
            <i class="fa-solid fa-seedling"></i>
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
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Body (Scrollable Form Content) -->
      <form id="new-yield-form" onsubmit="handleSaveYield(event)" class="overflow-y-auto custom-scrollbar p-5 sm:p-7 space-y-6 flex-1 text-xs sm:text-sm">
        
        <!-- SECTION 1: ข้อมูลแปลงปลูกและเกษตรกรเจ้าของแปลง -->
        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-200 pb-2.5">
            <h4 class="font-bold text-mezenc-teal flex items-center gap-2 text-sm sm:text-base">
              <i class="fa-solid fa-map-pin text-mezenc-teal"></i> <span>ส่วนที่ 1: ข้อมูลแปลงปลูกและเกษตรกรเจ้าของแปลง</span>
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
                <div class="text-gray-400 font-medium"><i class="fa-solid fa-user mr-1"></i> เกษตรกรเจ้าของแปลง:</div>
                <div id="disp-farmer-name" class="font-bold text-gray-800 text-sm">-</div>
                <div class="text-gray-500 text-[11px]">รหัสเกษตรกร: <span id="disp-farmer-code" class="font-mono font-bold text-mezenc-teal">-</span></div>
              </div>
              <div class="space-y-1">
                <div class="text-gray-400 font-medium"><i class="fa-solid fa-tree mr-1"></i> พันธุ์ยาง / เนื้อที่ / จำนวนต้น:</div>
                <div class="font-bold text-gray-800 text-sm">
                  พันธุ์: <span id="disp-rubber-clone" class="text-mezenc-teal">-</span> | <span id="disp-plot-area">-</span> ไร่ (<span id="disp-plot-trees" class="text-emerald-800 font-extrabold">-</span> ต้น)
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
              <i class="fa-solid fa-calendar-days text-mezenc-teal"></i> <span>ส่วนที่ 2: วันที่และรอบการกรีดยาง</span>
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
                onchange="updateAutoTappingRound()"
                required
              >
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block font-bold text-gray-700 text-xs sm:text-sm">
                  รอบการกรีดที่
                </label>
                <span class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                  <i class="fa-solid fa-wand-magic-sparkles text-[9px] mr-0.5"></i> อัตโนมัติ (เริ่มจาก 1)
                </span>
              </div>
              <div class="relative">
                <input 
                  type="number" 
                  id="yield-round" 
                  class="w-full bg-gray-100 text-gray-800 text-xs sm:text-sm font-extrabold rounded-xl pl-3.5 pr-12 py-2.5 border border-gray-300 outline-none cursor-not-allowed select-none shadow-xs" 
                  value="1" 
                  min="1"
                  readonly
                >
                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-semibold">รอบ</span>
              </div>
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
              <i class="fa-solid fa-scale-balanced text-mezenc-teal"></i> <span>ส่วนที่ 3: ปริมาณน้ำยางสดและราคารับซื้อ</span>
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
                <i class="fa-solid fa-money-bill-wave text-mezenc-teal"></i> <span>ยอดเงินรวมรายได้จากการจำหน่าย</span>
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
              <i class="fa-solid fa-building text-mezenc-teal"></i> <span>ส่วนที่ 4: ข้อมูลการส่งมอบและผู้รับซื้อ</span>
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
            data-i18n="btn_cancel"
          >
            ยกเลิก
          </button>
          
          <button 
            type="submit" 
            id="btn-save-yield"
            class="w-full sm:flex-1 py-3 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all cursor-pointer text-center flex items-center justify-center gap-2"
          >
            <i class="fa-solid fa-floppy-disk"></i> <span data-i18n="yd_btn_save_yield">บันทึกผลผลิตรอบใหม่</span>
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
            <i class="fa-solid fa-pen-to-square"></i>
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
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <form id="edit-yield-form" onsubmit="handleSaveEditYield(event)" class="overflow-y-auto custom-scrollbar p-5 sm:p-7 space-y-6 flex-1 text-xs sm:text-sm">
        <input type="hidden" id="edit-yield-id">
        <input type="hidden" id="edit-plot-id">

        <div class="bg-[#f8faf9] p-4 sm:p-5 rounded-2xl border border-gray-200/80 space-y-2">
          <div class="font-bold text-mezenc-teal flex items-center gap-2">
            <i class="fa-solid fa-map-pin text-mezenc-teal"></i> <span data-i18n="yd_th_plot">แปลงปลูก:</span> <span id="edit-disp-plot-info" class="text-gray-800 font-semibold">-</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <div>
            <label class="block font-bold text-gray-700 mb-1.5" data-i18n="yd_th_date">วันที่เก็บเกี่ยว *</label>
            <input type="date" id="edit-yield-date" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" required>
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5">รอบการกรีดที่</label>
            <input type="number" id="edit-yield-round" min="1" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs">
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5" data-i18n="yd_lbl_collector">สถานที่ส่งมอบ / ผู้รับซื้อ</label>
            <input type="text" id="edit-yield-buyer" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-bold text-gray-700 mb-1.5" data-i18n="yd_th_weight">ปริมาณน้ำยางสด (กก.) *</label>
            <input type="number" step="0.01" id="edit-yield-fresh-kg" class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" oninput="calculateEditRevenue()" required>
          </div>
          <div>
            <label class="block font-bold text-gray-700 mb-1.5" data-i18n="yd_th_price">ราคารับซื้อต่อ กก. (บาท) *</label>
            <input type="number" step="0.25" id="edit-yield-price" class="w-full bg-white text-gray-800 font-extrabold text-sm sm:text-base rounded-xl px-3.5 py-2.5 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs" oninput="calculateEditRevenue()" required>
          </div>
        </div>

        <!-- Revenue Highlight Box -->
        <div class="bg-gradient-to-r from-emerald-50 to-[#e6f7f6] p-4 rounded-2xl border-2 border-[#bee6e1] flex items-center justify-between">
          <span class="text-xs font-bold uppercase text-mezenc-teal flex items-center gap-1.5" data-i18n="yd_th_total"><i class="fa-solid fa-money-bill-wave"></i> ยอดเงินรวมคำนวณอัตโนมัติ</span>
          <span id="edit-calc-revenue-display" class="text-2xl font-black text-mezenc-teal">฿0.00</span>
        </div>

        <div>
          <label class="block font-bold text-gray-700 mb-1.5">หมายเหตุเพิ่มเติม</label>
          <textarea id="edit-yield-notes" rows="2" class="w-full bg-white text-gray-800 text-xs sm:text-sm rounded-xl px-3.5 py-2 border border-gray-300 focus:border-mezenc-brightCyan outline-none shadow-xs"></textarea>
        </div>

        <!-- Modal Actions -->
        <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
          <button type="button" onclick="closeEditYieldModal()" class="w-full sm:flex-1 py-3 rounded-full border-2 border-gray-300 hover:bg-gray-100 text-gray-700 font-bold text-xs sm:text-sm transition-all cursor-pointer text-center" data-i18n="btn_cancel">
            ยกเลิก
          </button>
          <button type="submit" id="btn-save-edit-yield" class="w-full sm:flex-1 py-3 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm shadow-lg hover:shadow-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2" data-i18n="btn_save">
            <i class="fa-solid fa-floppy-disk"></i> <span>บันทึกการแก้ไข</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- =========================================================================
       MODAL: ANOMALY AUDIT (หน้าต่างตรวจสอบและแจ้งเตือนความผิดปกติของปริมาณผลผลิต - High Risk)
       ========================================================================= -->
  <div id="anomalyAuditModal" class="modal-overlay">
    <div class="modal-card w-full max-w-2xl max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-rose-400">
      
      <!-- Modal Header (Rose / Red Gradient with Alert Pulse) -->
      <div class="p-5 sm:p-6 text-white bg-gradient-to-r from-rose-700 via-rose-800 to-red-900 relative flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl text-white shadow-inner border border-white/30 shrink-0">
            <i class="fa-solid fa-triangle-exclamation animate-bounce"></i>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-widest font-black text-rose-200 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-rose-300 animate-ping"></span>
              <span>ANTI-FRAUD & BIOLOGICAL AUDIT SYSTEM</span>
            </div>
            <h3 class="text-base sm:text-xl font-black text-white leading-tight">
              ตรวจพบความผิดปกติของปริมาณผลผลิต (High Risk)
            </h3>
          </div>
        </div>

        <button 
          type="button" 
          onclick="closeAnomalyModal()" 
          class="w-9 h-9 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer hover:rotate-90"
          title="ปิดหน้าต่าง"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Modal Body (Scrollable Details) -->
      <div class="overflow-y-auto custom-scrollbar p-5 sm:p-6 space-y-4 text-xs sm:text-sm text-gray-700">
        
        <!-- 1. ข้อมูลแปลง (Plot Summary Banner) -->
        <div class="bg-rose-50/70 p-4 rounded-2xl border border-rose-200 space-y-1.5">
          <div class="text-xs font-bold text-rose-900 uppercase tracking-wider flex items-center gap-1.5">
            <i class="fa-solid fa-map-location-dot text-rose-600"></i> ข้อมูลแปลงปลูกและเพดานชีวภาพ
          </div>
          <div class="font-extrabold text-base text-gray-900" id="anomaly-plot-title">-</div>
          <div class="text-xs text-gray-700 font-medium">
            เนื้อที่ <b id="anomaly-area-rai" class="text-rose-900">-</b> ไร่ | 
            ยางพารา <b id="anomaly-tree-count" class="text-rose-900">-</b> ต้น 
            <span class="text-gray-500 font-mono">(เพดานสูงสุดรายเดือน: <span id="anomaly-month-cap" class="font-bold text-rose-800 font-mono">-</span>)</span>
          </div>
          <div class="text-[11px] text-gray-500 flex flex-wrap gap-2 pt-0.5">
            <span>รหัสแปลง: <b id="anomaly-plot-code" class="font-mono text-gray-700">-</b></span>
            <span>•</span>
            <span>เจ้าของ: <b id="anomaly-farmer-name" class="text-gray-800">-</b></span>
            <span>•</span>
            <span>เอกสารสิทธิ์: <b id="anomaly-title-deed" class="text-gray-800">-</b></span>
          </div>
        </div>

        <!-- 2. เปรียบเทียบผลผลิต (Yield Comparison Stats Grid) -->
        <div class="space-y-2">
          <div class="font-bold text-gray-800 text-xs sm:text-sm flex items-center justify-between">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-simple text-rose-600"></i> เปรียบเทียบผลผลิตรายวันและการเปลี่ยนแปลง</span>
            <span class="text-[11px] font-bold text-rose-700 bg-rose-100 border border-rose-300 px-2.5 py-0.5 rounded-full" id="anomaly-surge-badge">
              เพิ่มขึ้นผิดปกติ +0%
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- ยอดบันทึกล่าสุด -->
            <div class="p-3.5 rounded-2xl bg-white border-2 border-rose-300 shadow-2xs space-y-1">
              <span class="text-[11px] font-bold text-rose-800 block">ยอดบันทึกล่าสุด:</span>
              <div class="flex items-baseline justify-between">
                <b id="anomaly-latest-kg" class="text-xl font-black text-rose-900 font-mono">0.0 กก./วัน</b>
                <span class="text-xs font-bold text-rose-700 font-mono">(เฉลี่ย <span id="anomaly-latest-per-tree">0.00</span> กก./ต้น)</span>
              </div>
              <span class="text-[10px] text-gray-400 block" id="anomaly-latest-date">วันที่บันทึก: -</span>
            </div>

            <!-- ยอดบันทึกรอบก่อนหน้า -->
            <div class="p-3.5 rounded-2xl bg-gray-50 border border-gray-200 shadow-2xs space-y-1">
              <span class="text-[11px] font-bold text-gray-600 block" id="anomaly-prev-label">ยอดบันทึก 2 วันก่อน:</span>
              <div class="flex items-baseline justify-between">
                <b id="anomaly-prev-kg" class="text-xl font-bold text-gray-800 font-mono">0.0 กก./วัน</b>
                <span class="text-xs font-medium text-gray-500 font-mono">(เฉลี่ย <span id="anomaly-prev-per-tree">0.00</span> กก./ต้น)</span>
              </div>
              <span class="text-[10px] text-gray-400 block" id="anomaly-prev-date">รอบก่อนหน้า</span>
            </div>
          </div>
        </div>

        <!-- 3. เหตุผลที่ต้องตรวจสอบ (Inspection Reasons & Anti-Fraud Indicators) -->
        <div class="p-4 rounded-2xl bg-amber-50/80 border-2 border-amber-300/80 space-y-2 text-xs">
          <div class="font-extrabold text-amber-900 flex items-center gap-1.5 text-sm">
            <i class="fa-solid fa-shield-halved text-amber-600"></i> เหตุผลที่ต้องตรวจสอบ:
          </div>
          <ul id="anomaly-reasons-list" class="space-y-1.5 list-disc list-inside text-amber-950 leading-relaxed font-medium pl-1">
            <li>ผลผลิตต่อวันเกินเกณฑ์ชีวภาพสูงสุดที่ต้นยางจะผลิตได้ (ปกติไม่เกิน 0.35 กก./ต้น/วัน หรือ 70 กก./วัน สำหรับ 200 ต้น)</li>
            <li>ปริมาณเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติของรอบกรีด เสี่ยงต่อการสวมสิทธิ์ นำยางนอกแปลงมาขาย หรือเกิดข้อผิดพลาดในการบันทึกตัวเลข</li>
          </ul>
        </div>

        <!-- Warning Disclaimer Note -->
        <div class="text-[11px] text-gray-500 bg-gray-50 p-3 rounded-xl border border-gray-200 leading-relaxed">
          <i class="fa-solid fa-circle-info text-gray-400 mr-1"></i>
          หากปล่อยให้ผลผลิตที่เกินเกณฑ์ผ่านการรับรอง อาจส่งผลให้ใบรับรอง DDS ประจำล็อตถูกระงับการออกใบอนุญาตส่งออกตามเกณฑ์ Regulation (EU) 2023/1115
        </div>

      </div>

      <!-- Modal Footer Action Buttons -->
      <div class="p-4 sm:p-5 bg-gray-50 border-t border-gray-200/80 flex flex-col sm:flex-row items-center gap-2.5 shrink-0">
        <button 
          type="button" 
          id="btn-anomaly-edit"
          onclick="handleAnomalyEditAction()" 
          class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-white hover:bg-gray-100 border border-gray-300 text-gray-800 font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 shadow-xs"
        >
          <i class="fa-solid fa-pen-to-square text-mezenc-teal"></i> <span>แก้ไขตัวเลข</span>
        </button>

        <button 
          type="button" 
          id="btn-anomaly-suspend"
          onclick="handleAnomalySuspendAction()" 
          class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 shadow-sm"
          title="พักยอดผลผลิตนี้ไว้ก่อนชั่วคราวเพื่อรอตรวจสอบเอกสารสิทธิ์และข้อเท็จจริง"
        >
          <i class="fa-solid fa-circle-pause text-rose-200"></i> <span>ระงับยอดชั่วคราว</span>
        </button>

        <button 
          type="button" 
          id="btn-anomaly-confirm"
          onclick="handleAnomalyConfirmAction()" 
          class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-mezenc-teal hover:bg-mezenc-deepTeal text-white font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 shadow-sm"
        >
          <i class="fa-solid fa-check"></i> <span>ยืนยันบันทึกข้อมูล</span>
        </button>
      </div>

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
            <div data-i18n="foot_authors"><i class="fa-solid fa-user-pen mr-1 text-mezenc-mint"></i> <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div data-i18n="foot_advisor"><i class="fa-solid fa-graduation-cap mr-1 text-mezenc-mint"></i> <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div data-i18n="foot_email"><i class="fa-solid fa-envelope mr-1 text-mezenc-mint"></i> <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1" data-i18n="foot_card_hdr">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1"><i class="fa-solid fa-map-location-dot text-mezenc-mint"></i></div>
            <div class="text-[14px] font-bold text-white leading-tight" data-i18n="foot_card_stat">26 ผืนป่าสงวน (Zone C) • 3,643,595 ไร่</div>
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

  <!-- Pass Plot & Farmer Data to JavaScript -->
  <script>
    const PLOTS_DATA = <?= json_encode($plots, JSON_UNESCAPED_UNICODE) ?>;
    window.CURRENT_USER = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE) ?>;
    window.CURRENT_ROLE = <?= json_encode($current_role, JSON_UNESCAPED_UNICODE) ?>;
    window.IS_ADMIN = <?= $isUserAdmin ? 'true' : 'false' ?>;
    window.IS_FACTORY = <?= $isFactory ? 'true' : 'false' ?>;
    window.IS_FARMER = <?= $isFarmer ? 'true' : 'false' ?>;
    window.FACTORY_NAME = <?= json_encode($factoryCompanyName, JSON_UNESCAPED_UNICODE) ?>;
    let currentFactoryPlot = null;
    let quotaCheckTimer = null;
    let html5QrScannerInstance = null;

    // Toggle Export Dropdown Menu
    function toggleExportMenu(e) {
      if (e) e.stopPropagation();
      const menu = document.getElementById('export-dropdown-menu');
      if (menu) {
        menu.classList.toggle('hidden');
      }
    }

    // Close Export Menu when clicking outside
    document.addEventListener('click', (e) => {
      const container = document.getElementById('export-dropdown-container');
      const menu = document.getElementById('export-dropdown-menu');
      if (menu && container && !container.contains(e.target)) {
        menu.classList.add('hidden');
      }
    });

    // Trigger Export (Excel / CSV / Print PDF)
    function triggerExport(type) {
      const menu = document.getElementById('export-dropdown-menu');
      if (menu) menu.classList.add('hidden');

      const plotSelect = document.getElementById('filter-yield-plot');
      const plotId = plotSelect ? plotSelect.value : '';

      if (type === 'print') {
        window.print();
        return;
      }

      let exportUrl = 'api/export.php?type=yields_csv';
      if (plotId) {
        exportUrl += `&plot_id=${encodeURIComponent(plotId)}`;
      }
      window.location.href = exportUrl;
    }

    // Format 13-Digit National ID: x-xxxx-xxxxx-xx-x
    function formatNationalId(idStr) {
      if (!idStr) return '-';
      const d = idStr.replace(/[^0-9]/g, '');
      if (d.length === 13) {
        return `${d.substr(0, 1)}-${d.substr(1, 4)}-${d.substr(5, 5)}-${d.substr(10, 2)}-${d.substr(12, 1)}`;
      }
      return idStr;
    }

    // =========================================================================
    // QR CODE SCANNER & PARSER (สำหรับจุดรับซื้อ / โรงงาน)
    // =========================================================================
    function parseScannedQRCode(qrText) {
      if (!qrText) return '';
      qrText = qrText.trim();

      // 1. Try parsing JSON format: {"plot_id": "...", "chanote_no": "...", "plot_code": "..."}
      try {
        const obj = JSON.parse(qrText);
        if (obj.plot_code) return obj.plot_code;
        if (obj.token) return obj.token;
        if (obj.traceability_token) return obj.traceability_token;
        if (obj.title_deed_no || obj.chanote_no) return obj.title_deed_no || obj.chanote_no;
        if (obj.plot_id) return String(obj.plot_id);
        if (obj.id) return String(obj.id);
      } catch (e) {}

      // 2. Try parsing URL format: https://.../trace.php?token=... หรือ ?plot_code=...
      if (qrText.includes('?') || qrText.includes('http://') || qrText.includes('https://')) {
        try {
          const url = new URL(qrText.startsWith('http') ? qrText : ('https://georubberwatch.com/' + qrText));
          const token = url.searchParams.get('token');
          if (token) return token;
          const plotCode = url.searchParams.get('plot_code') || url.searchParams.get('code');
          if (plotCode) return plotCode;
          const plotId = url.searchParams.get('plot_id') || url.searchParams.get('id');
          if (plotId) return plotId;
          const deed = url.searchParams.get('title_deed_no') || url.searchParams.get('chanote_no');
          if (deed) return deed;
        } catch (e) {}
      }

      // 3. Return clean raw text
      return qrText;
    }

    function openQrScannerModal() {
      const modal = document.getElementById('qrScannerModal');
      if (!modal) return;
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';

      const placeholder = document.getElementById('qr-camera-placeholder');
      const statusEl = document.getElementById('qr-scan-status');
      if (placeholder) placeholder.classList.remove('hidden');
      if (statusEl) statusEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> <span>กำลังเปิดกล้องสแกน...</span>';

      // Initialize camera via Html5Qrcode
      if (typeof Html5Qrcode !== 'undefined') {
        try {
          if (html5QrScannerInstance) {
            html5QrScannerInstance.stop().catch(() => {}).then(() => {
              startHtml5Camera();
            });
          } else {
            html5QrScannerInstance = new Html5Qrcode("qr-reader");
            startHtml5Camera();
          }
        } catch (err) {
          console.warn('QR camera init error:', err);
          if (placeholder) placeholder.innerHTML = '<div class="text-amber-400"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ไม่สามารถเปิดกล้องได้ กรุณาอนุญาตการเข้าถึงกล้อง หรือใช้ปุ่มอัปโหลดรูป QR</div>';
        }
      } else {
        if (placeholder) placeholder.innerHTML = '<div class="text-amber-400"><i class="fa-solid fa-triangle-exclamation mr-1"></i> กรุณาอนุญาตการเข้าถึงกล้อง หรือใช้ปุ่มอัปโหลดรูป QR</div>';
      }
    }

    function startHtml5Camera() {
      if (!html5QrScannerInstance) return;
      const config = { fps: 10, qrbox: { width: 220, height: 220 } };
      html5QrScannerInstance.start(
        { facingMode: "environment" },
        config,
        (decodedText) => {
          onQrCodeSuccess(decodedText);
        },
        (errorMessage) => {
          // in progress
        }
      ).then(() => {
        const placeholder = document.getElementById('qr-camera-placeholder');
        if (placeholder) placeholder.classList.add('hidden');
        const statusEl = document.getElementById('qr-scan-status');
        if (statusEl) statusEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> <span>กล้องพร้อมทำงาน กำลังสแกน...</span>';
      }).catch(err => {
        console.warn('Camera start error:', err);
        const placeholder = document.getElementById('qr-camera-placeholder');
        if (placeholder) {
          placeholder.classList.remove('hidden');
          placeholder.innerHTML = '<div class="text-amber-300 font-bold mb-1"><i class="fa-solid fa-camera mr-1"></i> ไม่พบกล้อง หรือยังไม่ได้อนุญาตการใช้กล้อง</div><div class="text-xs text-gray-300">กรุณาอนุญาตการเข้าถึงกล้อง หรือใช้ปุ่ม "สแกนจากไฟล์รูป" ด้านบน</div>';
        }
      });
    }

    function closeQrScannerModal() {
      const modal = document.getElementById('qrScannerModal');
      if (modal) modal.classList.remove('active');
      document.body.style.overflow = '';

      if (html5QrScannerInstance) {
        try {
          html5QrScannerInstance.stop().catch(() => {});
        } catch (e) {}
      }
    }

    function handleQrFileUpload(input) {
      if (!input.files || input.files.length === 0) return;
      const file = input.files[0];
      if (typeof Html5Qrcode !== 'undefined') {
        const html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.scanFile(file, true)
          .then(decodedText => {
            onQrCodeSuccess(decodedText);
          })
          .catch(err => {
            alert('ไม่สามารถอ่าน QR Code จากรูปภาพนี้ได้ กรุณาลองใหม่อีกครั้ง');
          });
      }
    }

    function onQrCodeSuccess(rawText) {
      const parsed = parseScannedQRCode(rawText);
      if (!parsed) return;

      closeQrScannerModal();

      // Populate search input
      const queryInput = document.getElementById('factory-search-query');
      if (queryInput) {
        queryInput.value = parsed;
      }

      // Trigger live query and auto-fill
      searchPlotPurchasing(parsed);
    }

    // Search Plot for Factory Purchasing / QR Scan
    async function searchPlotPurchasing(overrideQuery = null, silent = false) {
      const q = (overrideQuery || document.getElementById('factory-search-query').value).trim();
      if (!q) {
        if (!silent) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('กรุณากรอกรหัสแปลง, เลขบัตร ปชช. 13 หลัก หรือเลขที่โฉนดเพื่อค้นหา', 'warning');
          } else {
            alert('กรุณากรอกรหัสแปลง, เลขบัตร ปชช. 13 หลัก หรือเลขที่โฉนดเพื่อค้นหา');
          }
        }
        return;
      }

      try {
        const res = await fetch(`api/yields.php?action=search_plot_for_purchasing&q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.success || !data.plot) {
          if (!silent) {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast(data.message || 'ไม่พบแปลงปลูกที่ตรงกับเงื่อนไข', 'error');
            } else {
              alert(data.message || 'ไม่พบแปลงปลูกที่ตรงกับเงื่อนไข');
            }
          }
          return;
        }

        const plot = data.plot;
        const stats = data.stats;
        currentFactoryPlot = { plot, stats };

        // Auto-fill Plot & Farmer Data
        document.getElementById('factory-plot-id').value = plot.id;
        document.getElementById('factory-plot-name').textContent = plot.plot_name;
        document.getElementById('factory-plot-code').textContent = plot.plot_code || '-';
        document.getElementById('factory-title-deed').textContent = `${plot.title_deed_type || 'โฉนดที่ดิน'} เลขที่ ${plot.title_deed_no || '-'}`;
        
        const fName = `${plot.prefix || ''}${plot.first_name || ''} ${plot.last_name || ''}`.trim() || 'ไม่ระบุ';
        document.getElementById('factory-farmer-name').textContent = fName;
        document.getElementById('factory-farmer-idcard').textContent = formatNationalId(plot.id_card_num);
        document.getElementById('factory-farmer-code').textContent = plot.farmer_code || '-';
        document.getElementById('factory-plot-area').textContent = parseFloat(plot.area_rai || 0).toLocaleString();
        
        // Populate tree count
        const treeCountVal = parseInt(plot.tree_count || (stats ? stats.tree_count : 0)) || Math.round((parseFloat(plot.area_rai) || 0) * 75);
        const treeEl = document.getElementById('factory-plot-tree-count');
        if (treeEl) treeEl.textContent = treeCountVal.toLocaleString();

        // Populate coordinates
        const coordsEl = document.getElementById('factory-plot-coords');
        if (coordsEl) {
          if (plot.centroid_lat && plot.centroid_lng) {
            coordsEl.textContent = `Lat: ${parseFloat(plot.centroid_lat).toFixed(5)}, Lng: ${parseFloat(plot.centroid_lng).toFixed(5)} (${plot.polygon_geojson ? 'มีรูปแปลง GIS' : 'พิกัด GPS'})`;
          } else {
            coordsEl.textContent = 'พิกัด GPS/GIS พร้อมตรวจสอบ';
          }
        }

        // Populate EUDR badge
        const eudrBadge = document.getElementById('factory-eudr-badge');
        if (eudrBadge) {
          const isPassed = (plot.eudr_status === 'passed' || plot.eudr_status === 'verified' || !plot.eudr_status || plot.eudr_status === 'compliant');
          if (isPassed) {
            eudrBadge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5 shadow-2xs';
            eudrBadge.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600"></i> ปลอดการตัดไม้ 100% (EUDR ผ่าน)';
          } else {
            eudrBadge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1.5 shadow-2xs';
            eudrBadge.innerHTML = `<i class="fa-solid fa-clock text-amber-600"></i> รอตรวจสอบ (${plot.eudr_status})`;
          }
        }

        document.getElementById('factory-rubber-clone').textContent = plot.rubber_clone || 'RRIM 600';

        // Update Biological Capacity Meter
        updateFactoryCapacityUI(stats.current_month_sum, stats.max_monthly_capacity, stats.remaining_quota, stats.capacity_pct, stats.round_count);

        document.getElementById('factory-result-box').classList.remove('hidden');

        if (!silent) {
          document.getElementById('factory-fresh-kg').value = '';
          onFactoryFreshKgInput();

          // Toast notification
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast(`สแกนพบ: ${plot.plot_name} (${fName})`, 'success');
          }

          // Smooth scroll to quick form and focus on fresh weight input
          const quickForm = document.getElementById('factory-quick-form');
          if (quickForm) {
            quickForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          setTimeout(() => {
            const freshInput = document.getElementById('factory-fresh-kg');
            if (freshInput) freshInput.focus();
          }, 350);
        }

      } catch (err) {
        console.error('Error searching plot for purchasing:', err);
        if (!silent) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('เกิดข้อผิดพลาดในการค้นหาแปลงปลูก', 'error');
          } else {
            alert('เกิดข้อผิดพลาดในการค้นหาแปลงปลูก');
          }
        }
      }
    }

    function updateFactoryCapacityUI(monthSum, limit, remaining, pct, roundCount) {
      document.getElementById('factory-month-sum').textContent = `${parseFloat(monthSum).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก.`;
      document.getElementById('factory-month-limit').textContent = `${parseFloat(limit).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก.`;
      document.getElementById('factory-month-remaining').textContent = `${parseFloat(remaining).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก.`;
      document.getElementById('factory-round-count').textContent = `รอบที่ ${parseInt(roundCount) || 1}`;

      const bar = document.getElementById('factory-capacity-bar');
      const badge = document.getElementById('factory-capacity-badge');
      const displayPct = Math.min(100, Math.max(0, pct));
      bar.style.width = `${displayPct}%`;

      if (pct > 100) {
        bar.className = 'h-full rounded-full bg-rose-600 transition-all duration-500';
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300 animate-pulse flex items-center gap-1';
        badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> เกินเกณฑ์ (${pct}%)`;
      } else if (pct > 75) {
        bar.className = 'h-full rounded-full bg-amber-500 transition-all duration-500';
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300';
        badge.textContent = `เฝ้าระวัง (${pct}%)`;
      } else {
        bar.className = 'h-full rounded-full bg-emerald-500 transition-all duration-500';
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
        badge.textContent = `ปกติ (${pct}%)`;
      }
    }

    // Factory Real-time Calculations and Quota Check
    let currentFactoryAnomalyData = null;
    let windowYieldsList = [];
    let currentInspectingAnomaly = null;

    function onFactoryFreshKgInput() {
      const freshKg = parseFloat(document.getElementById('factory-fresh-kg').value) || 0;
      const drcPct = parseFloat(document.getElementById('factory-drc-pct').value) || 33.5;
      const priceKg = parseFloat(document.getElementById('factory-price-kg').value) || 72.0;

      const dryKg = (freshKg * (drcPct / 100.0)).toFixed(2);
      const totalRev = (freshKg * priceKg).toFixed(2);

      document.getElementById('factory-calc-dry-kg').textContent = `${parseFloat(dryKg).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} กก.`;
      document.getElementById('factory-calc-revenue').textContent = `฿${parseFloat(totalRev).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

      if (!currentFactoryPlot || !currentFactoryPlot.plot) return;

      clearTimeout(quotaCheckTimer);
      quotaCheckTimer = setTimeout(async () => {
        const plotId = currentFactoryPlot.plot.id;
        const harvestDate = document.getElementById('factory-harvest-date').value || '';
        try {
          const res = await fetch(`api/yields.php?action=check_quota&plot_id=${plotId}&fresh_kg=${freshKg}&harvest_date=${harvestDate}`);
          const qData = await res.json();
          if (qData.success) {
            currentFactoryAnomalyData = qData;
            updateFactoryCapacityUI(
              qData.projected_total, 
              qData.max_monthly_capacity, 
              Math.max(0, qData.max_monthly_capacity - qData.projected_total), 
              qData.capacity_pct, 
              (currentFactoryPlot.stats.round_count || 0) + 1
            );

            const alertBox = document.getElementById('factory-anomaly-alert');
            const alertText = document.getElementById('factory-anomaly-text');
            if (qData.is_anomaly) {
              alertBox.classList.remove('hidden');
              alertText.textContent = qData.warning_message || 'ผลผลิตต่อวันเกินเกณฑ์ชีวภาพสูงสุดที่ควรเป็นไปได้ หรือมีอัตราเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติ';
            } else {
              alertBox.classList.add('hidden');
            }
          }
        } catch (e) {
          console.warn('Quota live check error:', e);
        }
      }, 200);
    }

    // Factory Quick Purchasing Submission (Ultra-Fast Optimistic Flow)
    async function handleFactoryQuickSubmit(e) {
      e.preventDefault();
      const plotId = parseInt(document.getElementById('factory-plot-id').value);
      if (!plotId) {
        if (window.App && typeof window.App.showToast === 'function') {
          App.showToast('กรุณาค้นหาแปลงปลูกก่อนบันทึกการรับซื้อ', 'warning');
        } else {
          alert('กรุณาค้นหาแปลงปลูกก่อนบันทึกการรับซื้อ');
        }
        return;
      }

      const btn = document.getElementById('btn-factory-submit');
      btn.disabled = true;
      btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> <span>กำลังบันทึกและเชื่อมโยง EUDR...</span>`;

      const freshKgVal = parseFloat(document.getElementById('factory-fresh-kg').value) || 0;
      const drcPctVal = parseFloat(document.getElementById('factory-drc-pct').value) || 33.5;
      const priceVal = parseFloat(document.getElementById('factory-price-kg').value) || 72.0;

      const payload = {
        plot_id: plotId,
        harvest_date: document.getElementById('factory-harvest-date').value,
        fresh_latex_kg: freshKgVal,
        drc_percent: drcPctVal,
        price_per_kg: priceVal,
        buyer_name: document.getElementById('factory-buyer-name').value,
        notes: 'บันทึกผ่านโหมดจุดรับซื้อโรงงานและตรวจสอบย้อนกลับ EUDR'
      };

      try {
        const res = await fetch('api/yields.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          const tokenMsg = data.traceability_token ? ` (Token: ${data.traceability_token})` : '';

          // Instant Optimistic Table Update (Zero Delay)
          if (data.yield) {
            windowYieldsList.unshift(data.yield);
            renderYieldsTable(windowYieldsList, window.IS_ADMIN);
            updateSummaryQuickAdd(freshKgVal, freshKgVal * priceVal);
          }

          if (data.is_anomaly) {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast(`บันทึกแล้ว (ติดสถานะตรวจสอบความผิดปกติ): ${tokenMsg}`, 'warning');
            }
            if (currentFactoryAnomalyData) {
              currentFactoryAnomalyData.id = data.id;
              openAnomalyModal(currentFactoryAnomalyData);
            }
          } else {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast(`บันทึกการรับซื้อน้ำยางสดสำเร็จ!${tokenMsg}`, 'success');
            }
          }

          // Clear fresh kg input for the next incoming rubber batch
          const freshInput = document.getElementById('factory-fresh-kg');
          if (freshInput) {
            freshInput.value = '';
            freshInput.focus();
          }

          // Non-blocking background sync
          setTimeout(() => {
            searchPlotPurchasing(null, true);
            loadYields(true);
          }, 150);
        } else {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast(data.message || 'ไม่สามารถบันทึกได้', 'error');
          } else {
            alert(data.message || 'ไม่สามารถบันทึกได้');
          }
        }
      } catch (err) {
        if (window.App && typeof window.App.showToast === 'function') {
          App.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย', 'error');
        } else {
          alert('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
        }
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> <span>บันทึกการรับซื้อน้ำยางสดและเชื่อมโยง EUDR Traceability</span>`;
      }
    }

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

    // Modal Opening & Closing Handlers (Add Modal)
    function openAddYieldModal() {
      const modal = document.getElementById('addYieldModal');
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
      const dInput = document.getElementById('yield-date');
      if (dInput && !dInput.value) {
        dInput.value = new Date().toISOString().split('T')[0];
      }
      updateAutoTappingRound();
    }

    function closeAddYieldModal() {
      const modal = document.getElementById('addYieldModal');
      if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    }

    // Auto-calculate Tapping Round starting from 1 for selected plot in the current month
    function updateAutoTappingRound() {
      const plotSelect = document.getElementById('yield-plot-id');
      const plotId = parseInt(plotSelect ? plotSelect.value : 0);
      const dateVal = document.getElementById('yield-date')?.value || new Date().toISOString().split('T')[0];
      const monthPrefix = dateVal.substring(0, 7);
      const roundInput = document.getElementById('yield-round');
      if (!roundInput) return;

      if (!plotId) {
        roundInput.value = 1;
        return;
      }

      let maxRound = 0;
      let countMonth = 0;
      if (Array.isArray(windowYieldsList)) {
        windowYieldsList.forEach(y => {
          if (parseInt(y.plot_id) === plotId && y.harvest_date && y.harvest_date.startsWith(monthPrefix)) {
            countMonth++;
            const r = parseInt(y.tapping_round) || 0;
            if (r > maxRound) maxRound = r;
          }
        });
      }

      const nextRound = Math.max(maxRound, countMonth) + 1;
      roundInput.value = nextRound;
    }

    function onPlotSelectChange() {
      const plotId = parseInt(document.getElementById('yield-plot-id')?.value);
      const dynamicCard = document.getElementById('plot-dynamic-card');
      updateAutoTappingRound();
      if (!plotId || !PLOTS_DATA) {
        if (dynamicCard) dynamicCard.classList.add('hidden');
        return;
      }
      const plot = PLOTS_DATA.find(p => parseInt(p.id) === plotId);
      if (plot) {
        const farmerNameEl = document.getElementById('disp-farmer-name');
        const farmerCodeEl = document.getElementById('disp-farmer-code');
        const cloneEl = document.getElementById('disp-rubber-clone');
        const areaEl = document.getElementById('disp-plot-area');
        const treesEl = document.getElementById('disp-plot-trees');
        const locEl = document.getElementById('disp-plot-location');

        if (farmerNameEl) farmerNameEl.textContent = `${plot.prefix || ''}${plot.first_name || ''} ${plot.last_name || ''}`.trim() || '-';
        if (farmerCodeEl) farmerCodeEl.textContent = plot.farmer_code || '-';
        if (cloneEl) cloneEl.textContent = plot.rubber_clone || 'RRIM 600';
        if (areaEl) areaEl.textContent = parseFloat(plot.area_rai || 0).toLocaleString();
        const treeCountVal = parseInt(plot.tree_count) || Math.round((parseFloat(plot.area_rai) || 0) * 75);
        if (treesEl) treesEl.textContent = treeCountVal.toLocaleString();
        if (locEl) locEl.textContent = [plot.subdistrict, plot.district, plot.province].filter(Boolean).join(', ') || '-';
        if (dynamicCard) dynamicCard.classList.remove('hidden');
      } else {
        if (dynamicCard) dynamicCard.classList.add('hidden');
      }
    }

    function calculateRevenue() {
      const kg = parseFloat(document.getElementById('yield-fresh-kg')?.value) || 0;
      const price = parseFloat(document.getElementById('yield-price')?.value) || 0;
      const rev = kg * price;
      const disp = document.getElementById('calc-revenue-display');
      if (disp) {
        disp.textContent = '฿' + rev.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    }

    // Modal Opening & Closing Handlers (Edit Modal)
    function openEditYieldModal(id) {
      if (!windowYieldsList || windowYieldsList.length === 0) return;
      const item = windowYieldsList.find(y => parseInt(y.id) === parseInt(id));
      if (!item) {
        alert('ไม่พบข้อมูลผลผลิตที่ต้องการแก้ไข');
        return;
      }
      const eId = document.getElementById('edit-yield-id');
      const ePId = document.getElementById('edit-plot-id');
      const ePlotInfo = document.getElementById('edit-disp-plot-info');
      const eDate = document.getElementById('edit-yield-date');
      const eRound = document.getElementById('edit-yield-round');
      const eBuyer = document.getElementById('edit-yield-buyer');
      const eFreshKg = document.getElementById('edit-yield-fresh-kg');
      const ePrice = document.getElementById('edit-yield-price');
      const eNotes = document.getElementById('edit-yield-notes');

      if (eId) eId.value = item.id;
      if (ePId) ePId.value = item.plot_id || 0;
      if (ePlotInfo) {
        const deedText = item.title_deed_no ? `${item.title_deed_type || 'โฉนด'}: ${item.title_deed_no}` : (item.rubber_clone || 'ยางพารา');
        ePlotInfo.textContent = `${item.plot_name || 'แปลงปลูก'} (${item.plot_code || '-'}) • ${deedText}`;
      }
      if (eDate) eDate.value = item.harvest_date || '';
      if (eRound) eRound.value = item.tapping_round || 1;
      if (eBuyer) eBuyer.value = item.buyer_name || '';
      if (eFreshKg) eFreshKg.value = item.fresh_latex_kg || '';
      if (ePrice) ePrice.value = item.price_per_kg || 65.0;
      if (eNotes) eNotes.value = item.notes || '';

      calculateEditRevenue();

      const modal = document.getElementById('editYieldModal');
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeEditYieldModal() {
      const modal = document.getElementById('editYieldModal');
      if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
      }
    }

    function calculateEditRevenue() {
      const kg = parseFloat(document.getElementById('edit-yield-fresh-kg')?.value) || 0;
      const price = parseFloat(document.getElementById('edit-yield-price')?.value) || 0;
      const rev = kg * price;
      const disp = document.getElementById('edit-calc-revenue-display');
      if (disp) {
        disp.textContent = '฿' + rev.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    }

    async function handleSaveEditYield(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-save-edit-yield');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> <span>กำลังบันทึกการแก้ไข...</span>`;
      }

      const id = parseInt(document.getElementById('edit-yield-id')?.value);
      const payload = {
        id: id,
        plot_id: parseInt(document.getElementById('edit-plot-id')?.value) || 0,
        harvest_date: document.getElementById('edit-yield-date')?.value,
        tapping_round: parseInt(document.getElementById('edit-yield-round')?.value) || 1,
        buyer_name: document.getElementById('edit-yield-buyer')?.value || '',
        fresh_latex_kg: parseFloat(document.getElementById('edit-yield-fresh-kg')?.value) || 0,
        price_per_kg: parseFloat(document.getElementById('edit-yield-price')?.value) || 0,
        notes: document.getElementById('edit-yield-notes')?.value || ''
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
            App.showToast('แก้ไขข้อมูลผลผลิตสำเร็จแล้ว', 'success');
          } else {
            alert('แก้ไขข้อมูลผลผลิตสำเร็จแล้ว');
          }
          closeEditYieldModal();
          loadYields();
        } else {
          alert(data.message || 'เกิดข้อผิดพลาดในการแก้ไข');
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> <span>บันทึกการแก้ไข</span>`;
        }
      }
    }

    // =========================================================================
    // ANOMALY AUDIT MODAL HANDLERS (POPUP ตรวจสอบความผิดปกติผลผลิต)
    // =========================================================================
    function openAnomalyModalFromFactory() {
      if (currentFactoryAnomalyData) {
        openAnomalyModal(currentFactoryAnomalyData);
      } else if (currentFactoryPlot) {
        const freshKg = parseFloat(document.getElementById('factory-fresh-kg').value) || 0;
        const p = currentFactoryPlot.plot;
        const areaRai = parseFloat(p.area_rai || 10);
        const treeCount = parseInt(p.tree_count || Math.round(areaRai * 75));
        const monthCap = Math.round(treeCount * 4.5);
        const dayCap = Math.round(treeCount * 0.35);
        const latestPerTree = treeCount > 0 ? (freshKg / treeCount) : 0;
        
        openAnomalyModal({
          plot_name: p.plot_name,
          plot_code: p.plot_code,
          area_rai: areaRai,
          tree_count: treeCount,
          max_monthly_capacity: monthCap,
          max_daily_capacity: dayCap,
          latest_kg: freshKg,
          latest_per_tree: latestPerTree,
          prev_kg: 30.0,
          prev_per_tree: 0.15,
          diff_days: 2,
          surge_pct: 233.3,
          reasons: [
            `ผลผลิตต่อวัน (${freshKg} กก.) เกินเกณฑ์ชีวภาพสูงสุดที่ต้นยาง ${treeCount} ต้นจะผลิตได้ (ปกติไม่เกิน ${dayCap} กก./วัน หรือ 0.35 กก./ต้น/วัน)`,
            `ปริมาณเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติของรอบกรีด (+233.3%) เทียบกับ 2 วันก่อน (30.0 กก.) เสี่ยงต่อการสวมสิทธิ์ นำยางนอกแปลงมาขาย หรือเกิดข้อผิดพลาดในการบันทึกตัวเลข`
          ],
          farmer: {
            name: `${p.prefix || ''}${p.first_name || ''} ${p.last_name || ''}`.trim()
          },
          title_deed_no: p.title_deed_no,
          title_deed_type: p.title_deed_type
        });
      }
    }

    function openAnomalyModalFromRow(idx) {
      if (windowYieldsList && windowYieldsList[idx]) {
        const y = windowYieldsList[idx];
        const aData = y.anomaly_data || {};
        aData.id = y.id;
        aData.harvest_date = y.harvest_date;
        aData.plot_name = y.plot_name || aData.plot_name;
        aData.plot_code = y.plot_code || aData.plot_code;
        aData.latest_kg = y.fresh_latex_kg;
        aData.title_deed_no = y.title_deed_no;
        aData.title_deed_type = y.title_deed_type;
        aData.notes = y.notes || '';
        aData.is_suspended = Boolean(y.notes && y.notes.includes('ระงับยอดชั่วคราว'));
        aData.farmer_name = `${y.prefix || ''}${y.first_name || ''} ${y.last_name || ''}`.trim();
        openAnomalyModal(aData);
      }
    }

    function openAnomalyModal(data) {
      currentInspectingAnomaly = data;
      const modal = document.getElementById('anomalyAuditModal');
      if (!modal) return;

      // 1. Data Grounding: Plot Info
      const plotName = data.plot_name || 'แปลงปลูกยางพารา';
      const plotCode = data.plot_code || '-';
      document.getElementById('anomaly-plot-title').textContent = `${plotName} (${plotCode})`;
      document.getElementById('anomaly-area-rai').textContent = parseFloat(data.area_rai || 0).toLocaleString();
      document.getElementById('anomaly-tree-count').textContent = parseInt(data.tree_count || 0).toLocaleString();
      document.getElementById('anomaly-month-cap').textContent = `${parseFloat(data.max_monthly_capacity || 0).toLocaleString()} กก.`;
      document.getElementById('anomaly-plot-code').textContent = plotCode;
      
      const farmerName = data.farmer_name || (data.farmer && data.farmer.name) || '-';
      document.getElementById('anomaly-farmer-name').textContent = farmerName;
      
      const deedType = data.title_deed_type || 'โฉนดที่ดิน';
      const deedNo = data.title_deed_no || '-';
      document.getElementById('anomaly-title-deed').textContent = `${deedType} เลขที่ ${deedNo}`;

      // 2. Yield Comparison
      const latestKg = parseFloat(data.latest_kg || 0);
      const latestPerTree = parseFloat(data.latest_per_tree || (data.tree_count ? (latestKg / data.tree_count) : 0)).toFixed(2);
      document.getElementById('anomaly-latest-kg').textContent = `${latestKg.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก./วัน`;
      document.getElementById('anomaly-latest-per-tree').textContent = latestPerTree;
      document.getElementById('anomaly-latest-date').textContent = `วันที่บันทึก: ${data.harvest_date || 'วันนี้'}`;

      const diffDays = parseInt(data.diff_days || 2);
      const prevKg = parseFloat(data.prev_kg || 30.0);
      const prevPerTree = parseFloat(data.prev_per_tree || (data.tree_count ? (prevKg / data.tree_count) : 0.15)).toFixed(2);
      document.getElementById('anomaly-prev-label').textContent = `ยอดบันทึก ${diffDays} วันก่อน:`;
      document.getElementById('anomaly-prev-kg').textContent = `${prevKg.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก./วัน`;
      document.getElementById('anomaly-prev-per-tree').textContent = prevPerTree;
      document.getElementById('anomaly-prev-date').textContent = `รอบก่อนหน้า (${diffDays} วันก่อน)`;

      const surgePct = parseFloat(data.surge_pct || ((prevKg > 0) ? (((latestKg - prevKg) / prevKg) * 100) : 0)).toFixed(0);
      const badge = document.getElementById('anomaly-surge-badge');
      if (data.is_monthly_exceeded) {
        badge.textContent = `ผลผลิตสะสมเกินลิมิตประจำเดือน (+${parseFloat(data.overflow_month_kg || 0).toLocaleString()} กก.)`;
      } else if (surgePct > 0) {
        badge.textContent = `ปริมาณเพิ่มขึ้นผิดปกติ +${surgePct}%`;
      } else {
        badge.textContent = `เกินเกณฑ์ชีวภาพรายวัน`;
      }

      // 3. Reasons List
      const listContainer = document.getElementById('anomaly-reasons-list');
      if (listContainer) {
        let reasonsHtml = '';
        if (data.reasons && Array.isArray(data.reasons) && data.reasons.length > 0) {
          data.reasons.forEach(r => {
            reasonsHtml += `<li class="flex items-start gap-2"><i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i><span>${r}</span></li>`;
          });
        } else {
          const tCount = data.tree_count || 200;
          const dCap = data.max_daily_capacity || Math.round(tCount * 0.35);
          reasonsHtml += `
            <li class="flex items-start gap-2"><i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i><span>ผลผลิตต่อวัน (${latestKg} กก.) เกินเกณฑ์ชีวภาพสูงสุดที่ต้นยาง ${tCount} ต้นจะผลิตได้ (ปกติไม่เกิน ${dCap} กก./วัน หรือ 0.35 กก./ต้น/วัน)</span></li>
            <li class="flex items-start gap-2"><i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 shrink-0"></i><span>ปริมาณเพิ่มขึ้นก้าวกระโดดผิดธรรมชาติของรอบกรีด (+${surgePct}%) เทียบกับ ${diffDays} วันก่อน (${prevKg} กก.) เสี่ยงต่อการสวมสิทธิ์ นำยางนอกแปลงมาขาย หรือเกิดข้อผิดพลาดในการบันทึกตัวเลข</span></li>
          `;
        }
        listContainer.innerHTML = reasonsHtml;
      }

      // 4. Update Suspend / Approve Action Button
      const isSuspended = Boolean(data.notes && data.notes.includes('ระงับยอดชั่วคราว')) || Boolean(data.is_suspended);
      const suspendBtn = document.getElementById('btn-anomaly-suspend');
      if (suspendBtn) {
        if (isSuspended) {
          suspendBtn.className = 'w-full sm:flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 shadow-sm';
          suspendBtn.innerHTML = '<i class="fa-solid fa-unlock text-emerald-200"></i> <span>ปลดล็อก/อนุมัติยอด</span>';
          suspendBtn.onclick = handleAnomalyApproveAction;
        } else {
          suspendBtn.className = 'w-full sm:flex-1 py-3 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs sm:text-sm transition-all cursor-pointer flex items-center justify-center gap-2 shadow-sm';
          suspendBtn.innerHTML = '<i class="fa-solid fa-circle-pause text-rose-200"></i> <span>ระงับยอดชั่วคราว</span>';
          suspendBtn.onclick = handleAnomalySuspendAction;
        }
      }

      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeAnomalyModal() {
      const modal = document.getElementById('anomalyAuditModal');
      if (modal) modal.classList.remove('active');
      document.body.style.overflow = '';
    }

    function handleAnomalyEditAction() {
      closeAnomalyModal();
      if (currentInspectingAnomaly && currentInspectingAnomaly.id) {
        openEditYieldModal(currentInspectingAnomaly.id);
      } else {
        const freshInput = document.getElementById('factory-fresh-kg');
        if (freshInput) {
          freshInput.focus();
          freshInput.select();
        }
      }
    }

    async function handleAnomalySuspendAction() {
      if (!currentInspectingAnomaly) return;
      closeAnomalyModal();

      if (currentInspectingAnomaly.id) {
        try {
          const res = await fetch('api/yields.php?action=suspend_audit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: currentInspectingAnomaly.id,
              note: `ระงับยอดชั่วคราวเนื่องจากปริมาณ ${currentInspectingAnomaly.latest_kg || ''} กก. เกินเกณฑ์ชีวภาพ`
            })
          });
          const data = await res.json();
          if (data.success) {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast('ระงับยอดผลผลิตชั่วคราวเรียบร้อยแล้ว (สถานะ: รอตรวจสอบ)', 'warning');
            } else {
              alert('ระงับยอดผลผลิตชั่วคราวเรียบร้อยแล้ว (สถานะ: รอตรวจสอบ)');
            }
            loadYields();
          } else {
            alert(data.message || 'ไม่สามารถดำเนินการได้');
          }
        } catch (e) {
          alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
      } else {
        if (window.App && typeof window.App.showToast === 'function') {
          App.showToast('ระงับการบันทึกยอดผลผลิตนี้ชั่วคราว เพื่อรอตรวจสอบข้อเท็จจริง', 'warning');
        } else {
          alert('ระงับการบันทึกยอดผลผลิตนี้ชั่วคราว เพื่อรอตรวจสอบข้อเท็จจริง');
        }
      }
    }

    async function handleAnomalyApproveAction() {
      if (!currentInspectingAnomaly || !currentInspectingAnomaly.id) return;
      closeAnomalyModal();
      try {
        const res = await fetch('api/yields.php?action=approve_audit', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            id: currentInspectingAnomaly.id,
            note: `ตรวจสอบข้อเท็จจริงเรียบร้อย ปลดล็อกยอด ${currentInspectingAnomaly.latest_kg || ''} กก.`
          })
        });
        const data = await res.json();
        if (data.success) {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast('ปลดล็อกและอนุมัติยอดผลผลิตเรียบร้อยแล้ว', 'success');
          } else {
            alert('ปลดล็อกและอนุมัติยอดผลผลิตเรียบร้อยแล้ว');
          }
          loadYields();
        } else {
          alert(data.message || 'ไม่สามารถดำเนินการได้');
        }
      } catch (e) {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      }
    }

    function handleAnomalyConfirmAction() {
      closeAnomalyModal();
      if (window.App && typeof window.App.showToast === 'function') {
        App.showToast('บันทึกผลผลิตพร้อมระบุหมายเหตุตรวจสอบความผิดปกติทางชีวภาพแล้ว', 'success');
      }
    }

    function updateSummaryQuickAdd(addKg, addRevenue) {
      try {
        const kgEl = document.getElementById('sum-fresh-latex');
        if (kgEl) {
          const curr = parseFloat(kgEl.textContent.replace(/[^0-9.]/g, '')) || 0;
          kgEl.textContent = `${(curr + addKg).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} กก.`;
        }
        const revEl = document.getElementById('sum-revenue');
        if (revEl) {
          const currRev = parseFloat(revEl.textContent.replace(/[^0-9.]/g, '')) || 0;
          revEl.textContent = `฿${(currRev + addRevenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
        const recEl = document.getElementById('sum-total-records');
        if (recEl) {
          const currRec = parseInt(recEl.textContent.replace(/[^0-9]/g, '')) || 0;
          recEl.textContent = `${(currRec + 1).toLocaleString()} ครั้ง`;
        }
      } catch (e) {}
    }

    function renderYieldsTable(yieldsList, canDelete = false) {
      const tbody = document.getElementById('yields-table-body');
      if (!tbody) return;
      if (!yieldsList || yieldsList.length === 0) {
        const colSpan = window.IS_FARMER ? 7 : 11;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-12 text-gray-400 text-xs">ยังไม่มีข้อมูลผลผลิตในเงื่อนไขนี้</td></tr>`;
        return;
      }

      let html = '';
      yieldsList.forEach((y, idx) => {
        const idCardFormatted = formatNationalId(y.id_card_num);
        const isAnomaly = y.is_anomaly === true;
        const isSuspended = Boolean(y.notes && y.notes.includes('ระงับยอดชั่วคราว'));
        const dryKg = parseFloat(y.dry_rubber_kg || (y.fresh_latex_kg * ((y.drc_percent || 33.5) / 100.0))).toFixed(2);
        const deedText = y.title_deed_no ? `${y.title_deed_type || 'โฉนดที่ดิน'} เลขที่ ${y.title_deed_no}` : '-';
        const tokenStr = y.traceability_token || ('EUDR-TX-' + y.id);
        const batchStr = y.batch_code || '-';

        if (window.IS_FARMER) {
          // 7 DEDICATED COLUMNS FOR FARMER (READ-ONLY VIEW)
          html += `
            <tr class="hover:bg-[#f4faf7] transition-colors">
              <td class="py-4 px-4 font-bold text-gray-800 whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <i class="fa-solid fa-calendar-day text-mezenc-teal"></i>
                  <span>${y.harvest_date}</span>
                </div>
                <span class="text-[10px] text-gray-400 block pl-5 font-medium">รอบกรีดที่ ${y.tapping_round || 1}</span>
              </td>
              <td class="py-4 px-4">
                <div class="font-extrabold text-mezenc-teal text-sm">${y.plot_name}</div>
                <div class="text-xs text-gray-600 flex flex-wrap items-center gap-1.5 mt-0.5">
                  <span class="font-mono text-gray-500 font-bold">${y.plot_code || '-'}</span>
                  <span class="text-gray-300">•</span>
                  <span class="text-emerald-700 font-medium"><i class="fa-solid fa-file-lines text-xs mr-1"></i>${deedText}</span>
                </div>
              </td>
              <td class="py-4 px-4 text-gray-800 text-xs sm:text-sm font-semibold">
                <div class="flex items-center gap-1.5 text-mezenc-teal font-bold">
                  <i class="fa-solid fa-building text-xs"></i> <span>${y.buyer_name || 'จุดรับซื้อน้ำยางสดประจำตำบล'}</span>
                </div>
                ${isSuspended ? `
                  <span class="text-[10px] text-amber-800 bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-full inline-block mt-1 font-bold">
                    <i class="fa-solid fa-circle-pause text-[10px] mr-0.5 text-amber-600"></i> ระงับยอดชั่วคราว (รอตรวจสอบ)
                  </span>
                ` : `
                  <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full inline-block mt-1 font-bold">
                    <i class="fa-solid fa-check text-[10px] mr-0.5"></i> รับซื้อเรียบร้อย
                  </span>
                `}
              </td>
              <td class="py-4 px-4 text-right font-black text-mezenc-teal whitespace-nowrap text-sm sm:text-base">
                ${parseFloat(y.fresh_latex_kg).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })} <span class="text-xs font-normal text-gray-500">กก.</span>
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap text-xs">
                <span class="font-extrabold text-emerald-700 font-mono text-sm">${parseFloat(y.drc_percent || 33.5).toFixed(1)}%</span>
                <span class="text-[11px] text-gray-500 block font-mono mt-0.5">(${dryKg} กก. ยางแห้ง)</span>
              </td>
              <td class="py-4 px-4 text-right whitespace-nowrap">
                <div class="text-xs text-gray-500 font-medium">฿${parseFloat(y.price_per_kg).toFixed(2)} / กก.</div>
                <div class="font-black text-emerald-600 text-sm sm:text-base mt-0.5">
                  ฿${parseFloat(y.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </div>
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5 font-mono text-xs font-bold px-3 py-1 rounded-xl ${isSuspended ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-emerald-50 text-emerald-900 border-emerald-300'} border shadow-2xs">
                  <i class="fa-solid ${isSuspended ? 'fa-circle-pause text-amber-600' : 'fa-shield-halved text-emerald-700'} text-xs"></i> <span>${tokenStr}</span>
                </div>
                <span class="text-[10px] text-gray-400 font-mono block mt-1">ล็อต: ${batchStr}</span>
              </td>
            </tr>
          `;
        } else {
          // ADMIN / FACTORY EXTENDED COLUMNS WITH MANAGEMENT & AUDIT
          html += `
            <tr class="hover:bg-[#f4faf7] transition-colors ${isSuspended ? 'bg-amber-50/40' : (isAnomaly ? 'bg-rose-50/50' : '')}">
              <td class="py-4 px-4 font-bold text-gray-800 whitespace-nowrap">${y.harvest_date}</td>
              <td class="py-4 px-4">
                <span class="font-bold text-mezenc-teal">${y.plot_name}</span> 
                <span class="text-[11px] text-gray-400 font-mono block">(${y.plot_code || '-'}) • ${deedText}</span>
              </td>
              <td class="py-4 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#f8faf9] text-gray-700 border border-gray-200">
                  ${y.rubber_clone || 'RRIM 600'}
                </span>
              </td>
              <td class="py-4 px-4 text-gray-800 font-medium">
                <div class="font-bold">${y.prefix || ''}${y.first_name} ${y.last_name}</div>
                <div class="text-[10.5px] text-mezenc-teal font-mono">ปชช: ${idCardFormatted}</div>
                <span class="text-[10px] text-gray-400 font-mono">${y.farmer_code || ''}</span>
              </td>
              <td class="py-4 px-4 text-right font-extrabold text-mezenc-teal whitespace-nowrap text-sm sm:text-base">
                ${parseFloat(y.fresh_latex_kg).toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap font-mono text-xs">
                <span class="font-bold text-emerald-700">${parseFloat(y.drc_percent || 33.5).toFixed(1)}%</span>
                <span class="text-[10px] text-gray-400 block font-normal">(${dryKg} กก.ยางแห้ง)</span>
              </td>
              <td class="py-4 px-4 text-right font-medium text-gray-700 whitespace-nowrap">
                ฿${parseFloat(y.price_per_kg).toFixed(2)}
              </td>
              <td class="py-4 px-4 text-right font-black text-emerald-600 whitespace-nowrap text-sm sm:text-base">
                ฿${parseFloat(y.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap">
                ${isSuspended ? `
                  <button 
                    type="button" 
                    onclick="openAnomalyModalFromRow(${idx})" 
                    class="inline-flex items-center gap-1.5 text-[11px] font-black text-amber-950 bg-amber-100 hover:bg-amber-200 border-2 border-amber-400 px-3 py-1 rounded-full shadow-xs cursor-pointer transition transform hover:scale-105 active:scale-95" 
                    title="รายการนี้ถูกระงับยอดชั่วคราวเพื่อรอตรวจสอบ - คลิกเพื่อดูรายละเอียดและปลดล็อก"
                  >
                    <i class="fa-solid fa-circle-pause text-amber-600 animate-pulse"></i> 
                    <span>ระงับยอดชั่วคราว (รอตรวจสอบ)</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9.5px] text-amber-600"></i>
                  </button>
                ` : isAnomaly ? `
                  <button 
                    type="button" 
                    onclick="openAnomalyModalFromRow(${idx})" 
                    class="inline-flex items-center gap-1.5 text-[11px] font-black text-rose-900 bg-rose-100 hover:bg-rose-200 border-2 border-rose-400 px-3 py-1 rounded-full shadow-xs cursor-pointer transition transform hover:scale-105 active:scale-95" 
                    title="ตรวจพบผลผลิตเกินขีดจำกัดชีวภาพ/เสี่ยงสวมสิทธิ์ - คลิกเพื่อเปิดหน้าต่างตรวจสอบ"
                  >
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 animate-pulse"></i> 
                    <span>ตรวจจับการสวมสิทธิ์ (ให้ตรวจสอบ)</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[9.5px] text-rose-600"></i>
                  </button>
                ` : `
                  <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 px-2.5 py-1 rounded-full">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i> ปกติ (EUDR)
                  </span>
                `}
              </td>
              <td class="py-4 px-4 text-gray-600 text-xs max-w-[180px] truncate" title="${y.buyer_name || '-'}">
                ${y.buyer_name || '-'}
              </td>
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button 
                    onclick="openEditYieldModal(${y.id})" 
                    class="w-8 h-8 rounded-full bg-[#dcf5f5] hover:bg-[#00a699] text-[#00a699] hover:text-white flex items-center justify-center transition-all shadow-xs cursor-pointer"
                    title="แก้ไขผลผลิตรอบนี้"
                  >
                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                  </button>
                  ${canDelete ? `
                  <button 
                    onclick="deleteYield(${y.id})" 
                    class="w-8 h-8 rounded-full bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition-all shadow-xs cursor-pointer"
                    title="ลบรายการนี้"
                  >
                    <i class="fa-solid fa-trash-can text-xs"></i>
                  </button>` : ''}
                </div>
              </td>
            </tr>
          `;
        }
      });

      tbody.innerHTML = html;
    }

    // Load Yields Table via AJAX (Optimized Single Network Request)
    async function loadYields(silent = false) {
      const plotId = document.getElementById('filter-yield-plot') ? document.getElementById('filter-yield-plot').value : '';
      const sName = document.getElementById('search-name') ? document.getElementById('search-name').value.trim() : '';
      const sCode = document.getElementById('search-plot-code') ? document.getElementById('search-plot-code').value.trim() : '';
      const sDeed = document.getElementById('search-title-deed') ? document.getElementById('search-title-deed').value.trim() : '';

      let params = new URLSearchParams();
      if (plotId) params.append('plot_id', plotId);
      if (sName) params.append('search_name', sName);
      if (sCode) params.append('search_plot_code', sCode);
      if (sDeed) params.append('search_title_deed', sDeed);
      params.append('limit', '1000');

      let url = 'api/yields.php' + (params.toString() ? '?' + params.toString() : '');

      try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) {
          console.error('API Error:', data.message);
          renderYieldsTable([]);
          return;
        }

        windowYieldsList = data.yields || [];

        const s = data.summary;
        if (s) {
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
        }

        const canDelete = data.can_delete === true || window.IS_ADMIN === true;
        renderYieldsTable(windowYieldsList, canDelete);

      } catch (e) {
        console.error('Error loading yields:', e);
        renderYieldsTable([]);
      }
    }

    // Save Yield Submission (Ultra-Fast Optimistic Flow)
    async function handleSaveYield(e) {
      e.preventDefault();

      const btnSave = document.getElementById('btn-save-yield');
      btnSave.disabled = true;
      btnSave.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> <span>กำลังบันทึกข้อมูล...</span>`;

      const plotIdVal = parseInt(document.getElementById('yield-plot-id').value);
      const freshKgVal = parseFloat(document.getElementById('yield-fresh-kg').value) || 0;
      const priceVal = parseFloat(document.getElementById('yield-price').value) || 0;

      const payload = {
        plot_id: plotIdVal,
        harvest_date: document.getElementById('yield-date').value,
        tapping_round: parseInt(document.getElementById('yield-round').value) || 1,
        fresh_latex_kg: freshKgVal,
        price_per_kg: priceVal,
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
          // Instant Optimistic Table Update (Zero Delay)
          if (data.yield) {
            windowYieldsList.unshift(data.yield);
            renderYieldsTable(windowYieldsList, window.IS_ADMIN);
            updateSummaryQuickAdd(freshKgVal, freshKgVal * priceVal);
          }

          if (data.is_anomaly) {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast('บันทึกสำเร็จ แต่ตรวจพบความผิดปกติของผลผลิต', 'warning');
            }
          } else {
            if (window.App && typeof window.App.showToast === 'function') {
              App.showToast('บันทึกข้อมูลผลผลิตน้ำยางสดสำเร็จ!', 'success');
            }
          }
          closeAddYieldModal();
          document.getElementById('new-yield-form').reset();
          document.getElementById('plot-dynamic-card').classList.add('hidden');
          document.getElementById('calc-revenue-display').textContent = '฿0.00';

          // Non-blocking background sync
          setTimeout(() => {
            loadYields(true);
          }, 150);
        } else {
          if (window.App && typeof window.App.showToast === 'function') {
            App.showToast(data.message || 'บันทึกไม่สำเร็จ', 'error');
          } else {
            alert(data.message || 'บันทึกไม่สำเร็จ');
          }
        }
      } catch (e) {
        if (window.App && typeof window.App.showToast === 'function') {
          App.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย', 'error');
        } else {
          alert('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
        }
      } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> <span>บันทึกผลผลิตรอบใหม่</span>`;
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
