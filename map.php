<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$currentUser = getCurrentUser();
$current_role = $currentUser['role'] ?? 'farmer';
$user_name = $currentUser['full_name'] ?? 'ผู้ใช้งานระบบ';

$pdo = getDatabaseConnection();
$farmers = $pdo->query("SELECT id, farmer_code, prefix, first_name, last_name FROM farmers ORDER BY first_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GeoRubber Watch - ทะเบียนแปลงปลูกยางพาราและพิกัดภูมิสารสนเทศ (EUDR Map)</title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Leaflet & Leaflet.draw CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

  <!-- QRCode.js Library -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <!-- Platform Core JS Helper -->
  <script src="assets/js/app.js"></script>

  <!-- Tailwind Theme Configuration (Exact Mezenc Natural Teal System) -->
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
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;
    }

    /* Minimalist Toggle Switch Button */
    .toggle-switch-btn {
      width: 46px;
      height: 26px;
      background-color: #e2e8f0;
      border-radius: 9999px;
      border: none;
      cursor: pointer;
      position: relative;
      transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 2px;
      display: inline-flex;
      align-items: center;
      flex-shrink: 0;
      outline: none;
    }
    .toggle-switch-btn:hover {
      background-color: #cbd5e1;
    }
    .toggle-switch-btn.active {
      background-color: #00a699 !important;
    }
    .toggle-switch-btn .toggle-knob {
      width: 22px;
      height: 22px;
      background-color: #ffffff;
      border-radius: 50%;
      display: block;
      transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
      transform: translateX(0px);
      pointer-events: none;
    }
    .toggle-switch-btn.active .toggle-knob {
      transform: translateX(20px) !important;
    }

    /* Leaflet popup styling to match Mezenc */
    .leaflet-popup-content-wrapper {
      border-radius: 1.25rem !important;
      padding: 4px !important;
      box-shadow: 0 20px 35px -10px rgba(14,77,78,0.25), 0 4px 12px rgba(0,0,0,0.08) !important;
      border: 1.5px solid #bee6e1 !important;
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif !important;
    }
    .leaflet-popup-tip {
      background: white !important;
    }

    /* True Alpha Mask Fade (กลืนภาพลงสู่พื้นหลังอย่างเนียนกริบ 100% สไตล์ MNTN / Editorial) */
    .hero-mask-fade {
      -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 25%, rgba(0, 0, 0, 0.8) 55%, rgba(0, 0, 0, 0) 100%);
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 25%, rgba(0, 0, 0, 0.8) 55%, rgba(0, 0, 0, 0) 100%);
    }

    /* Plot Card Styling in Sidebar */
    .plot-card {
      background: #ffffff;
      border: 1.5px solid #e2ece7;
      border-radius: 1.25rem;
      padding: 1.25rem;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
    }
    .plot-card:hover {
      border-color: #00a699;
      box-shadow: 0 10px 25px -5px rgba(14, 77, 78, 0.12);
      transform: translateY(-2px);
    }
    .plot-card.active {
      border-color: #00a699 !important;
      background-color: #f0fdf9 !important;
      box-shadow: 0 0 0 2px rgba(0, 166, 153, 0.25), 0 10px 25px -5px rgba(14, 77, 78, 0.15) !important;
    }
    .plot-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.65rem;
      gap: 0.5rem;
    }
    .plot-title {
      font-size: 16px;
      font-weight: 700;
      color: #0e4d4e;
      line-height: 1.35;
    }
    .plot-code {
      font-size: 16px;
      color: #00a699;
      font-family: monospace;
      font-weight: 700;
      margin-top: 2px;
    }
    .plot-meta {
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
      font-size: 16px;
      color: #4b5563;
      margin-top: 0.65rem;
      background: #f8faf9;
      padding: 0.75rem 0.85rem;
      border-radius: 0.85rem;
      line-height: 1.5;
    }
    .plot-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.75rem;
      padding-top: 0.65rem;
      border-top: 1px dashed #e2ece7;
    }

    /* Status Badges */
    .badge-compliant {
      background-color: #dcfce7;
      color: #15803d;
      border: 1px solid #bbf7d0;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 16px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .badge-non_compliant {
      background-color: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 16px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      white-space: nowrap;
      flex-shrink: 0;
    }
    .badge-under_review {
      background-color: #ffedd5;
      color: #c2410c;
      border: 1px solid #fed7aa;
      padding: 0.25rem 0.65rem;
      border-radius: 9999px;
      font-size: 16px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* Plot Action Buttons in Sidebar Card */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.5rem 0.85rem;
      font-size: 16px;
      font-weight: 700;
      border-radius: 9999px;
      border: 1.5px solid transparent;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn-outline {
      background-color: #ffffff;
      border-color: #bee6e1;
      color: #0e4d4e;
    }
    .btn-outline:hover {
      background-color: #e6f7f6;
      border-color: #00a699;
      color: #00a699;
    }
    .btn-sm {
      padding: 0.45rem 0.85rem;
      font-size: 16px;
    }

    /* Modal Overlay & Transitions */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(11, 29, 38, 0.75);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      opacity: 0;
      pointer-events: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 1rem;
    }
    .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    .modal-card {
      background: #ffffff;
      border-radius: 1.75rem;
      box-shadow: 0 25px 60px -15px rgba(14, 77, 78, 0.3);
      width: 100%;
      max-width: 880px;
      max-height: 92vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: scale(0.95);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid #bee6e1;
    }
    .modal-overlay.active .modal-card {
      transform: scale(1);
    }
  </style>
</head>
<body class="bg-[#f8faf9] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION & ATMOSPHERIC NATURE BANNER (Matching index.php Hero)
       ========================================================================= -->
  <div class="relative w-full text-white overflow-hidden bg-mezenc-darkNavy min-h-[540px] sm:min-h-[600px] lg:min-h-[660px] flex flex-col justify-between pb-24 sm:pb-32 lg:pb-36">
    
    <!-- Hero Image Background with Clean Dark Overlay & Smooth Soft Fade Lowered to Bottom Edge -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="img/map_rubber_hero.jpg" alt="Surat Thani Rubber Plantation Aerial Landscape" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='ปก.png';">
      <!-- Clean uniform dark overlay for crisp typography in upper hero -->
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
            drop-shadow
            truncate
          "
        >
          GeoRubber Watch
        </span>
      </div>

      <!-- DESKTOP / IPAD LANDSCAPE NAVIGATION LINKS -->
      <nav
        class="
          hidden
          lg:flex
          items-center
          gap-5
          xl:gap-7
          text-sm
          xl:text-base
          font-medium
          tracking-wide
        "
      >
        <a
          class="
            text-white
            hover:text-mezenc-mint
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="index.php"
        >
          หน้าแรก
        </a>

        <a
          class="
            text-white
            hover:text-mezenc-mint
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="overview.php"
        >
          แผนที่ GIS
        </a>

        <a
          class="
            text-white
            hover:text-mezenc-mint
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="dashboard.php"
        >
          แดชบอร์ด
        </a>

        <a
          class="
            text-mezenc-mint
            font-bold
            border-b-2
            border-mezenc-mint
            pb-0.5
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="map.php"
        >
          แปลงปลูก
        </a>

        <a
          class="
            text-white
            hover:text-mezenc-mint
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="yields.php"
        >
          ผลผลิต
        </a>

        <a
          class="
            text-white
            hover:text-mezenc-mint
            transition-colors
            cursor-pointer
            drop-shadow-sm
          "
          href="contact.php"
        >
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT NAV ACTIONS & MOBILE MENU BUTTON -->
      <div class="flex items-center gap-2.5 sm:gap-3.5">
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

        <!-- Mobile Drawer Toggle Button -->
        <button
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

    <!-- HERO CONTENT (Matching exact font sizes from overview.php) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow">
          🌱 WEB-GIS RUBBER PLOT REGISTRY & EUDR VERIFICATION
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md">
          ระบบทะเบียนแปลงปลูกและพิกัดภูมิสารสนเทศ (GIS)
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow">
          วาดขอบเขตแปลงปลูก (Polygon), ตรวจสอบพิกัด GPS, วิเคราะห์การทับซ้อนแนวเขตป่าสงวน 26 แห่ง จ.สุราษฎร์ธานี แบบ Real-Time พร้อมออกหนังสือรับรอง EUDR Passport
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
          <a href="map.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
            <span>📍</span> <span>แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
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
       2. MAIN INTERACTIVE GIS WORKSPACE (Full-Width Map with Floating Slide-Out Layer Control Panel)
       ========================================================================= -->
  <main class="w-full max-w-[1520px] 2xl:max-w-[1680px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1">
    
    <!-- Main Full-Width GIS Map Card (Supports Fullscreen Mode) -->
    <div id="gis-map-card-container" class="w-full bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] relative flex flex-col h-auto min-h-[660px] lg:h-[800px] xl:h-[840px] transition-all duration-300">
      
      <!-- Top Map Toolbar (Title, Badges, and Action Buttons) -->
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3.5 mb-3.5 border-b border-gray-100">
        
        <!-- Left: Title & Badges -->
        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3.5">
          <!-- Title & Map Icon -->
          <h2 class="text-[16px] sm:text-lg font-extrabold text-mezenc-teal tracking-tight flex items-center gap-2">
            <img src="img/map_icon.png" alt="Map Icon" class="w-5 h-5 object-contain inline-block drop-shadow-xs" onerror="this.style.display='none'">
            <span>แผนที่พิกัดแปลงปลูก &amp; แนวเขตป่าสงวน</span>
          </h2>

          <!-- Badges -->
          <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[14px] sm:text-[15px] font-bold bg-mezenc-lightCyan text-mezenc-teal border border-[#bee6e1]">
            ม.อ. สุราษฎร์ธานี
          </span>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2">
          <button 
            type="button" 
            onclick="GeoMap.map.flyTo([9.0805, 99.3515], 14, { duration: 1.2 })" 
            class="px-4 py-2 rounded-full bg-white hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-[14px] sm:text-[15px] border-2 border-[#bee6e1] shadow-xs hover:border-mezenc-brightCyan transition-all cursor-pointer"
          >
            กลับจุดเริ่มต้น
          </button>

          <button 
            type="button" 
            onclick="activateMapDrawDirect()" 
            class="px-4 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[14px] sm:text-[15px] shadow-md hover:shadow-lg transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>✏️ วาดแปลงใหม่</span>
          </button>
        </div>

      </div>

      <!-- Map Viewport (Expanded Full Width & Height) -->
      <div class="relative w-full flex-1 min-h-[550px] rounded-xl sm:rounded-2xl overflow-hidden shadow-inner border border-gray-200">
        
        <!-- Real Leaflet Map Container -->
        <div id="map-view" class="w-full h-full z-10"></div>

        <!-- =====================================================================
             2. SLIDE-OUT / FLOATING GIS LAYER CONTROL PANEL (แผงควบคุมแผนที่)
             ===================================================================== -->
        <div 
          id="floatingLayerPanel" 
          class="absolute top-3 bottom-3 left-3 w-[360px] sm:w-[390px] max-w-[calc(100%-24px)] bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl shadow-[0_20px_50px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] p-4 sm:p-5 flex flex-col overflow-y-auto space-y-4 z-[450] transition-all duration-300 ease-in-out transform -translate-x-[120%] opacity-0 pointer-events-none"
        >
          
          <!-- Panel Header with Close Button ✕ -->
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
              <span class="text-xl">🎛️</span>
              <div>
                <h3 class="font-extrabold text-[16px] text-mezenc-teal leading-tight">แผงควบคุมแผนที่</h3>
                <span class="text-[14px] text-gray-500 font-medium block mt-0.5">Layer Control &amp; Tools</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-[13px] font-bold text-mezenc-brightCyan px-2.5 py-0.5 rounded-full bg-mezenc-lightCyan border border-[#bee6e1] shrink-0">
                GIS Toggles
              </span>
              <button 
                type="button" 
                onclick="toggleLayerPanel(false)" 
                class="w-7 h-7 rounded-full bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-500 flex items-center justify-center text-xs font-bold transition-all cursor-pointer shadow-xs"
                title="ปิดแผงควบคุม"
              >
                ✕
              </button>
            </div>
          </div>

          <!-- Basemap Select -->
          <div>
            <label for="basemap-select" class="block text-[15px] font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
              <svg fill="#00a896" viewBox="0 0 32 32" class="w-4 h-4 shrink-0 inline-block" xmlns="http://www.w3.org/2000/svg" stroke="#00a896"><path d="M22.81 23.318l-0.126-0.188 1.107-0.704-2.953-4.646-1.214 0.772-0.521-0.779c0.817-0.727 1.372-1.742 1.504-2.887l4.052 5.901 6.244-1.885-11.090-16.151-6.244 1.885 3.628 5.283c-0.363-0.093-0.743-0.142-1.134-0.142-1.915 0-3.555 1.177-4.237 2.847l-4.095-5.963-6.244 1.885 11.090 16.151 6.244-1.885-2.667-3.884c0.888-0.017 1.714-0.288 2.409-0.742l0.488 0.73-1.026 0.652 2.953 4.646 1.133-0.72 0.12 0.18c-1.012 0.736-1.72 1.902-1.94 3.227l6.030-3.773c-1.3-0.448-2.513-0.32-3.511 0.19zM25.79 26.619c0 0.638-0.517 1.155-1.155 1.155s-1.155-0.517-1.155-1.155c0-0.638 0.517-1.155 1.155-1.155s1.155 0.517 1.155 1.155z"></path></svg>
              <span>แผนที่ฐาน (Basemap)</span>
            </label>
            <div class="relative">
              <select 
                id="basemap-select" 
                class="w-full bg-[#f8faf9] text-gray-800 font-medium text-[15px] rounded-xl px-3 py-2.5 outline-none border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white transition-all cursor-pointer shadow-xs leading-relaxed"
                onchange="GeoMap.setBaseMap(this.value)"
              >
                <option value="satellite">🛰️ ภาพถ่ายดาวเทียม (Satellite)</option>
                <option value="osm">🗺️ แผนที่ถนน (OpenStreetMap)</option>
                <option value="topo">⛰️ ภูมิประเทศ (Topographic)</option>
              </select>
            </div>
          </div>

          <!-- Overlays Section -->
          <div class="space-y-2">
            <div class="text-[15px] font-bold text-gray-700 flex items-center gap-1.5">
              <span>📂</span> <span>เปิด/ปิดชั้นข้อมูล (Layers):</span>
            </div>

            <!-- Toggle Row 1: Forest Reserves -->
            <div 
              class="flex items-center justify-between p-3 rounded-2xl bg-[#f4faf7] border border-[#e2ece7] cursor-pointer hover:bg-[#eef8f4] hover:border-mezenc-brightCyan/40 transition-all shadow-xs" 
              onclick="GeoMap.toggleForestLayer()"
            >
              <div class="flex items-center gap-2.5">
                <span style="width: 22px; height: 22px; background: #fee2e2; border: 2px dashed #dc2626; border-radius: 6px;" class="shrink-0 inline-block shadow-xs"></span>
                <div>
                  <span class="font-bold text-gray-800 text-[15px] leading-tight block">แนวเขตป่าสงวนแห่งชาติ</span>
                  <span class="text-[13px] text-gray-500 font-medium block mt-0.5">(26 ผืนป่า จ.สุราษฎร์ฯ)</span>
                </div>
              </div>
              <button 
                type="button" 
                class="toggle-switch-btn active" 
                id="switch-forest" 
                aria-label="เปิดปิดป่าสงวน" 
                onclick="event.stopPropagation(); GeoMap.toggleForestLayer();"
              >
                <span class="toggle-knob"></span>
              </button>
            </div>

            <!-- Toggle Row 2: Rubber Plots -->
            <div 
              class="flex items-center justify-between p-3 rounded-2xl bg-[#f4faf7] border border-[#e2ece7] cursor-pointer hover:bg-[#eef8f4] hover:border-mezenc-brightCyan/40 transition-all shadow-xs" 
              onclick="GeoMap.togglePlotsLayer()"
            >
              <div class="flex items-center gap-2.5">
                <span style="width: 22px; height: 22px; background: #22c55e; border-radius: 6px;" class="shrink-0 inline-block shadow-xs"></span>
                <div>
                  <span class="font-bold text-gray-800 text-[15px] leading-tight block">แปลงปลูกยางพารา</span>
                  <span class="text-[13px] text-gray-500 font-medium block mt-0.5">(แปลงทะเบียน EUDR)</span>
                </div>
              </div>
              <button 
                type="button" 
                class="toggle-switch-btn active" 
                id="switch-plots" 
                aria-label="เปิดปิดแปลงยางพารา" 
                onclick="event.stopPropagation(); GeoMap.togglePlotsLayer();"
              >
                <span class="toggle-knob"></span>
              </button>
            </div>

          </div>

          <!-- Quick Drawing Tools & Actions -->
          <div class="space-y-2">
            <div class="text-[15px] font-bold text-gray-700 flex items-center gap-1.5">
              <span>🛠️</span> <span>เครื่องมือจัดการแปลง:</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <button 
                type="button" 
                onclick="activateMapDrawDirect()" 
                class="py-2.5 px-3 rounded-2xl bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-[14px] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>✏️ วาดแปลงใหม่</span>
              </button>
              <button 
                type="button" 
                onclick="locateUserDirect()" 
                class="py-2.5 px-3 rounded-2xl bg-gradient-to-r from-[#d4f1ee] to-[#e8f7f5] hover:from-mezenc-brightCyan hover:to-mezenc-teal text-mezenc-teal hover:text-white font-bold text-[14px] border-2 border-[#bee6e1] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>📍 GPS ของฉัน</span>
              </button>
            </div>
          </div>

          <!-- Status Legend Guide -->
          <div class="mt-auto pt-3 border-t border-gray-100 text-[15px] text-gray-700 space-y-2 bg-[#f8faf9] p-3 rounded-2xl border border-gray-100">
            <div class="font-bold text-mezenc-teal flex items-center gap-1.5 text-[15px]">
              <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mezenc-teal shrink-0">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M8 16L4.35009 13.3929C2.24773 11.8912 1 9.46667 1 6.88306V3L8 0L15 3V6.88306C15 9.46667 13.7523 11.8912 11.6499 13.3929L8 16ZM12.2071 5.70711L10.7929 4.29289L7 8.08579L5.20711 6.29289L3.79289 7.70711L7 10.9142L12.2071 5.70711Z" fill="#00a896"/>
              </svg>
              <span>สถานะความสอดคล้อง EUDR</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 shrink-0"></span>
              <span class="font-medium text-gray-800 text-[15px] leading-tight">ผ่านเกณฑ์ EUDR (ปลอดตัดไม้)</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3.5 h-3.5 rounded-full bg-rose-600 shrink-0"></span>
              <span class="font-medium text-gray-800 text-[15px] leading-tight">ทับซ้อนป่าสงวน (ไม่ผ่านเกณฑ์)</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3.5 h-3.5 rounded-full bg-amber-500 shrink-0"></span>
              <span class="font-medium text-gray-800 text-[15px] leading-tight">โซนเฝ้าระวัง (Buffer &lt; 500 ม.)</span>
            </div>
            <div class="flex items-center gap-2 pt-2 border-t border-gray-200/60">
              <svg viewBox="0 0 1024 1024" class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg">
                <path d="M921.6 742.4c0 14.08-11.52 25.6-25.6 25.6l-256 64-256-76.8-230.4 64c-14.08 0-25.6-11.52-25.6-25.6V332.8c0-14.08 11.52-25.6 25.6-25.6l230.4-64 256 76.8 256-64c14.08 0 25.6 11.52 25.6 25.6v460.8z" fill="#66A8A5"/>
                <path d="M640 832l-256-76.8V243.2l256 76.8z" fill="#438478"/>
              </svg>
              <span class="font-bold text-red-700 text-[15px] leading-tight">แนวเขตป่าสงวน (Zone C เขตหวงห้าม)</span>
            </div>
          </div>

        </div>

        <!-- Floating Map Overlay: ☰ Hamburger Toggle Button (Top-Left of Map) -->
        <div class="absolute top-4 left-4 z-[400] flex flex-col gap-2">
          <button 
            type="button" 
            id="btn-toggle-panel"
            onclick="toggleLayerPanel()" 
            title="เปิด/ปิด แผงควบคุมแผนที่ (Layer Control & Tools)" 
            class="w-11 h-11 rounded-2xl bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>

        <!-- Floating Map Overlay: Circular Bottom-Right Action Buttons (GPS, Pin Location & Fullscreen) -->
        <div class="absolute bottom-5 right-4 z-[400] flex flex-col gap-2.5">
          <!-- GPS Locate Button -->
          <button 
            type="button" 
            onclick="locateUserDirect()" 
            title="ระบุตำแหน่ง GPS ปัจจุบันของฉัน" 
            class="w-11 h-11 rounded-full bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform group-hover:scale-110">
              <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
              <circle cx="12" cy="3" r="3" fill="currentColor"/>
              <line x1="12" y1="2" x2="12" y2="5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="12" y1="19" x2="12" y2="22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="2" y1="12" x2="5" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <line x1="19" y1="12" x2="22" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>

          <!-- Pin Location Tool Button (ปุ่มปักหมุดตรวจสอบพิกัด & ความเสี่ยง Buffer Zone) -->
          <button 
            type="button" 
            id="btn-floating-pin-mode"
            onclick="toggleMapPinMode()" 
            title="ปักหมุดบนแผนที่เพื่อตรวจสอบความเสี่ยงและระยะห่างเขตป่าสงวน (Buffer Zone Check)" 
            class="w-11 h-11 rounded-full bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform group-hover:scale-110">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
            </svg>
          </button>

          <!-- Fullscreen Toggle Button -->
          <button 
            type="button" 
            onclick="toggleFullscreen()" 
            title="ขยายแผนที่เต็มจอ / ย่อขนาด" 
            class="w-11 h-11 rounded-full bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
            </svg>
          </button>
        </div>

      </div>

    </div>

    <!-- =========================================================================
         3. BOTTOM SECTION: Rubber Plots Management & Registry (Placed below Control Panel & Map)
         ========================================================================= -->
    <div class="mt-6 sm:mt-8 bg-white rounded-2xl sm:rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] p-5 sm:p-7 space-y-6">
      
      <!-- Header & Action Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-gray-100">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan border border-[#bee6e1] flex items-center justify-center text-2xl shrink-0 shadow-xs">
            🌱
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h3 class="text-xl sm:text-2xl font-extrabold text-mezenc-teal leading-tight">
                แปลงปลูกยางพารา
              </h3>
              <span id="total-plots-count-badge" class="inline-flex items-center px-3.5 py-1 rounded-full text-[16px] font-bold bg-mezenc-lightCyan text-mezenc-teal border border-[#bee6e1]">
                กำลังโหลด...
              </span>
            </div>
            <p class="text-[16px] text-gray-500 font-medium mt-1">
              ทะเบียนแปลงปลูกยางพาราและระบบตรวจสอบย้อนกลับมาตรฐาน EUDR (Digital Passport Registry)
            </p>
          </div>
        </div>
        
        <!-- Add Plot Button -->
        <div class="flex items-center gap-3 shrink-0">
          <button 
            type="button" 
            onclick="openAddPlotWizard()" 
            class="px-5 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow-md hover:shadow-lg hover:scale-105 transition-all flex items-center gap-2 cursor-pointer group"
          >
            <span class="group-hover:rotate-90 transition-transform font-bold text-lg">➕</span>
            <span>เพิ่มแปลง</span>
          </button>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3.5 bg-[#f8faf9] p-3.5 sm:p-4 rounded-2xl border border-gray-200">
        
        <!-- Text Search Box -->
        <div class="relative flex-1">
          <input 
            type="text" 
            id="plot-search-input" 
            placeholder="🔍 ค้นหาชื่อแปลง, รหัส, เกษตรกร..." 
            class="w-full bg-white text-gray-800 text-[16px] rounded-xl pl-11 pr-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-1 focus:ring-mezenc-brightCyan outline-none transition-all shadow-xs" 
            oninput="filterPlotsList()"
          >
          <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>

        <!-- Status Filter Dropdown / Tabs -->
        <div class="flex items-center gap-2 shrink-0">
          <select 
            id="plot-status-filter" 
            class="bg-white text-gray-800 font-bold text-[16px] rounded-xl px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan outline-none shadow-xs cursor-pointer leading-relaxed" 
            onchange="filterPlotsList()"
          >
            <option value="">ทั้งหมด</option>
            <option value="compliant">🟢 ผ่าน EUDR</option>
            <option value="non_compliant">🔴 ทับซ้อนป่า</option>
            <option value="under_review">🟠 โซนเฝ้าระวัง</option>
          </select>
        </div>

      </div>

      <!-- Modern Plots Table (Matching Reference Design) -->
      <div class="overflow-x-auto rounded-2xl border border-gray-200/90 shadow-xs bg-white">
        <table class="w-full text-left border-collapse min-w-[880px]">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50/80 text-[13px] font-bold text-gray-500 uppercase tracking-wider select-none">
              <th class="py-3.5 pl-5 pr-2 w-12 text-center">
                <span class="inline-block w-4 h-4 rounded-full border-2 border-gray-300"></span>
              </th>
              <th class="py-3.5 px-4 font-bold text-gray-600">รหัส / เกษตรกร & แปลงปลูก</th>
              <th class="py-3.5 px-4 font-bold text-gray-600">เนื้อที่ยาง</th>
              <th class="py-3.5 px-4 font-bold text-gray-600">พันธุ์ยาง & ปีปลูก</th>
              <th class="py-3.5 px-4 font-bold text-gray-600">จำนวนต้น</th>
              <th class="py-3.5 px-4 font-bold text-gray-600">สถานะ EUDR</th>
              <th class="py-3.5 pr-6 pl-4 text-center font-bold text-gray-600 w-36">การจัดการ</th>
            </tr>
          </thead>
          <tbody id="plots-list-container" class="divide-y divide-gray-100 text-[14px]">
            <tr>
              <td colspan="7" class="text-center text-gray-400 py-12 text-[15px]">กำลังโหลดข้อมูลแปลงปลูก...</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

  </main>

  <!-- =========================================================================
       3. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี)
       ========================================================================= -->
  <footer id="footer-section" class="bg-mezenc-teal text-white pt-12 sm:pt-14 pb-10 border-t border-white/10 mt-12 sm:mt-16 relative z-20">
    <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-white/15 items-start md:items-center">
        
        <!-- คอลัมน์ที่ 1: ข้อมูลโครงการและสถาบัน -->
        <div class="md:col-span-4 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center bg-white/10 shrink-0 p-1.5">
              <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
              </svg>
            </div>
            <div>
              <div class="font-bold text-xs leading-tight text-mezenc-mint">โครงการวิจัยระบบภูมิสารสนเทศ</div>
              <div class="font-extrabold text-base sm:text-lg leading-tight">GeoRubber Watch • สุราษฎร์ธานี</div>
            </div>
          </div>
          <p class="text-xs text-white/80 leading-relaxed font-light">
            สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม<br>
            มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี<br>
            <span class="text-white/65 text-[11px]">31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000</span>
          </p>
        </div>

        <!-- คอลัมน์ที่ 2: ข้อมูลผู้จัดทำและช่องทางติดต่อ -->
        <div class="md:col-span-5 space-y-1.5 text-xs text-white/85 leading-relaxed">
          <div class="font-bold text-mezenc-mint uppercase">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[11px] text-white/75">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[11px] text-white/90 space-y-0.5">
            <div>👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div>🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div>✉️ <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a>, <a href="mailto:6640011066@psu.ac.th" class="hover:text-mezenc-mint underline">6640011066@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[10px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1">🗺️</div>
            <div class="text-xs font-bold text-white">26 ผืนป่าสงวน (Zone C) • 784,618 ไร่</div>
            <div class="text-[10px] text-white/70 mt-1 font-light">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- ลิขสิทธิ์ -->
      <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-[11px] text-white/60 gap-4">
        <div>&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>

    </div>
  </footer>

  <!-- =========================================================================
       4. MODAL: Add New Rubber Plot & Multi-Step EUDR Wizard
       ========================================================================= -->
  <div id="addPlotModal" class="modal-overlay">
    <div class="modal-card w-full max-w-4xl max-h-[92vh] flex flex-col justify-between overflow-hidden bg-white rounded-3xl shadow-2xl border-2 border-[#bee6e1]">
      
      <!-- Top Progress Step Indicators (Matching order.php & Screenshot) -->
      <div class="border-b border-gray-100 px-6 sm:px-10 pt-6 pb-5 relative bg-white shrink-0">
        <!-- Close Button at top right -->
        <button 
          type="button" 
          onclick="App.closeModal('addPlotModal')" 
          class="absolute right-5 top-5 w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-800 flex items-center justify-center font-bold text-sm transition-all cursor-pointer z-10"
        >
          ✕
        </button>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-center relative pr-8">
          
          <!-- Step 1: วาดแปลงปลูก -->
          <button type="button" onclick="goToModalStep(1)" class="flex items-center gap-2.5 text-left group cursor-pointer" title="ขั้นตอนที่ 1: วาดขอบเขตแปลงปลูก">
            <div id="modal-step-badge-1" class="w-8 h-8 rounded-full bg-mezenc-brightCyan text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-[16px]">
              1
            </div>
            <div class="leading-tight">
              <span id="modal-step-label-1" class="text-[16px] font-bold text-mezenc-brightCyan group-hover:underline block">วาดแปลงปลูก</span>
            </div>
          </button>

          <!-- Step 2: ข้อมูลเกษตรกร -->
          <button type="button" onclick="goToModalStep(2)" class="flex items-center gap-2.5 text-left group cursor-pointer" title="ขั้นตอนที่ 2: กรอกข้อมูลเกษตรกรและแปลง">
            <div id="modal-step-badge-2" class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-[16px] shadow-xs">
              2
            </div>
            <div class="leading-tight">
              <span id="modal-step-label-2" class="text-[16px] font-semibold text-gray-400 group-hover:text-gray-600 block">ข้อมูลเกษตรกร</span>
            </div>
          </button>

          <!-- Step 3: ตรวจสอบ -->
          <button type="button" onclick="goToModalStep(3)" class="flex items-center gap-2.5 text-left group cursor-pointer" title="ขั้นตอนที่ 3: ตรวจสอบความสอดคล้อง EUDR">
            <div id="modal-step-badge-3" class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-[16px] shadow-xs">
              3
            </div>
            <div class="leading-tight">
              <span id="modal-step-label-3" class="text-[16px] font-semibold text-gray-400 group-hover:text-gray-600 block">ตรวจสอบ</span>
            </div>
          </button>

          <!-- Step 4: QR Code -->
          <button type="button" onclick="goToModalStep(4)" class="flex items-center gap-2.5 text-left group cursor-pointer" title="ขั้นตอนที่ 4: ออก QR Code และใบรับรอง">
            <div id="modal-step-badge-4" class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-[16px] shadow-xs">
              4
            </div>
            <div class="leading-tight">
              <span id="modal-step-label-4" class="text-[16px] font-semibold text-gray-400 group-hover:text-gray-600 block">QR Code</span>
            </div>
          </button>

        </div>

        <!-- Active Step Underline Indicator Bar -->
        <div class="hidden md:block absolute bottom-0 left-0 right-0 h-[3px] bg-transparent">
          <div id="modal-step-indicator-bar" class="h-full bg-mezenc-brightCyan transition-all duration-300 w-1/4 translate-x-0"></div>
        </div>
      </div>

      <!-- Inner Modal Step Content Area (Scrollable) -->
      <div class="px-6 sm:px-10 py-6 overflow-y-auto flex-1 text-[16px]">
        
        <!-- STEP 1: วาดแปลงปลูก -->
        <div id="modal-step-content-1" class="space-y-5">
          <div class="text-center space-y-1">
            <h2 class="text-lg sm:text-xl text-mezenc-teal font-extrabold">
              ขั้นตอนที่ 1: กำหนดพิกัดและ <span class="text-mezenc-brightCyan underline decoration-mezenc-brightCyan underline-offset-4">วาดขอบเขตแปลงปลูกใหม่</span>
            </h2>
            <p class="text-[16px] text-gray-500">
              ระบบต้องการพิกัดขอบเขตแปลง (Polygon) ตามมาตรฐาน WGS84 เพื่อตรวจสอบการทับซ้อนและระยะห่างจาก 26 แนวเขตป่าสงวนแห่งชาติ
            </p>
          </div>

          <!-- Drawing Action Card with Dashed Border (Matches Screenshot) -->
          <div class="border-2 border-dashed border-[#bee6e1] bg-[#f8faf9] rounded-3xl p-6 sm:p-7 text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-white text-mezenc-brightCyan flex items-center justify-center mx-auto text-2xl shadow-sm border border-[#bee6e1]">
              📍
            </div>

            <div class="max-w-md mx-auto space-y-1.5">
              <h3 class="font-extrabold text-[16px] text-mezenc-teal">
                ต้องการเปิดแผนที่ GIS เพื่อวาดแปลง หรือใช้พิกัดที่ระบุไว้?
              </h3>
              <p class="text-[16px] text-gray-500 leading-relaxed">
                ท่านสามารถคลิกปุ่มด้านล่างเพื่อเปิดโหมดวาดแปลงพิกัดจริงบนแผนที่ หรือใช้พิกัดที่ระบุเพื่อเข้าสู่ขั้นตอนที่ 2 ทันที
              </p>
            </div>

            <!-- Current Loaded Coordinates Badge -->
            <div class="inline-flex flex-wrap items-center justify-center gap-2.5 bg-white px-4 py-2 rounded-full border border-gray-200 text-[16px] shadow-xs">
              <span class="text-gray-400">พิกัดปัจจุบัน:</span>
              <span class="font-mono font-bold text-mezenc-teal">Lat: <span id="modal-disp-lat">9.138240</span>, Lng: <span id="modal-disp-lng">99.321850</span></span>
              <span class="bg-mezenc-lightCyan text-mezenc-teal px-2.5 py-0.5 rounded-full font-bold text-[16px]"><span id="modal-disp-points">Polygon 6 จุด</span> (เนื้อที่ ~<span id="modal-disp-area">10.5</span> ไร่)</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-1">
              <button 
                type="button" 
                onclick="activateMapDrawFromModal()" 
                class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-white hover:bg-mezenc-teal hover:text-white text-mezenc-teal font-bold text-[16px] border-2 border-mezenc-teal transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>เปิดแผนที่เพื่อวาดแปลงใหม่</span>
              </button>

              <button 
                type="button" 
                onclick="confirmModalCoordsAndNext()" 
                class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>บันทึก</span>
              </button>
            </div>
          </div>
        </div>

        <!-- STEP 2: ข้อมูลเกษตรกรและแปลงปลูก -->
        <div id="modal-step-content-2" class="hidden space-y-4 text-[16px]">
          <div class="text-center space-y-1">
            <h2 class="text-lg sm:text-xl text-mezenc-teal font-extrabold">
              ขั้นตอนที่ 2: <span class="text-mezenc-brightCyan underline decoration-mezenc-brightCyan underline-offset-4">บันทึกข้อมูลเกษตรกรและแปลงปลูก</span>
            </h2>
            <p class="text-[16px] text-gray-500">กรุณากรอกข้อมูลทะเบียนเกษตรกร เอกสารสิทธิ์ และข้อมูลการเพาะปลูกให้ครบถ้วนเพื่อดำเนินการประเมินความสอดคล้อง</p>
          </div>

          <div class="border-2 border-dashed border-[#bee6e1] bg-[#f8faf9] rounded-3xl p-6 sm:p-7 space-y-4">
            
            <!-- Real Calculated Area & Points Banner from Drawn Polygon -->
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 p-4 rounded-2xl border-2 border-emerald-300 shadow-xs flex flex-wrap items-center justify-between gap-3 text-[16px]">
              <div class="flex items-center gap-3">
                <span class="text-2xl">📐</span>
                <div>
                  <div class="text-[13px] text-emerald-800 font-bold uppercase">พิกัดและเนื้อที่จริงที่คำนวณได้จากแผนที่ GIS:</div>
                  <div class="text-mezenc-teal font-extrabold text-[17px]">
                    <span id="step2-area-text">10 ไร่ 2 งาน 0 ตร.ว.</span> 
                    <span class="text-gray-500 font-normal text-[14px]">(<span id="step2-area-ha">1.6800</span> ha / <span id="step2-area-sqm">16,800</span> ตร.ม.)</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="bg-mezenc-brightCyan text-white font-bold text-[14px] px-3.5 py-1 rounded-full shadow-xs" id="step2-points-badge">
                  Polygon 6 จุด
                </span>
                <span class="bg-emerald-100 text-emerald-800 font-bold text-[14px] px-3 py-1 rounded-full border border-emerald-300" id="step2-eudr-badge">
                  🟢 ปลอดการตัดไม้
                </span>
              </div>
            </div>

            <!-- Form Content Box -->
            <div class="bg-white p-5 sm:p-6 rounded-3xl border border-[#bee6e1] shadow-xs space-y-4 text-[16px]">
              <!-- Row 1 -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">ชื่อเกษตรกรเจ้าของแปลง <span class="text-red-500">*</span></label>
                  <input 
                    type="text" 
                    id="form-farmer-name" 
                    list="farmer-suggestions" 
                    value="นางสาวมนัสนันท์ อนันตณรงค์" 
                    class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs" 
                    placeholder="พิมพ์ชื่อ-นามสกุล เกษตรกรเจ้าของแปลง"
                  >
                  <datalist id="farmer-suggestions">
                    <?php foreach ($farmers as $f): ?>
                      <option value="<?= htmlspecialchars($f['prefix'] . $f['first_name'] . ' ' . $f['last_name']) ?>">
                    <?php endforeach; ?>
                    <option value="นางสาวมนัสนันท์ อนันตณรงค์">
                    <option value="นางสาวมาทินี โรยนรินทร์">
                    <option value="นายสมชาย ยางเจริญสุข">
                  </datalist>
                </div>
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">ชื่อแปลงปลูก <span class="text-red-500">*</span></label>
                  <input type="text" id="form-plot-name" value="แปลงยางพาราเขาท่าเพชร 1" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs" placeholder="เช่น แปลงยางพาราเขาท่าเพชร 1">
                </div>
              </div>

              <!-- Row 2 -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">ประเภทเอกสารสิทธิ์</label>
                  <select id="form-deed-type" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs cursor-pointer">
                    <option value="โฉนดที่ดิน (น.ส. 4 จ)" selected>โฉนดที่ดิน (น.ส. 4 จ)</option>
                    <option value="หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)">หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)</option>
                    <option value="หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01">หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01</option>
                    <option value="ภ.บ.ท. 5 / แบบแจ้งการครอบครอง">ภ.บ.ท. 5 / แบบแจ้งการครอบครอง</option>
                    <option value="เอกสารสิทธิ์อื่นๆ">เอกสารสิทธิ์อื่นๆ</option>
                  </select>
                </div>
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">เลขที่เอกสารสิทธิ์</label>
                  <input type="text" id="form-deed-no" value="8401-4458" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs" placeholder="เช่น 8401-4458">
                </div>
              </div>

              <!-- Row 3 -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">พันธุ์ยางพารา <span class="text-red-500">*</span></label>
                  <select id="form-rubber-clone" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs cursor-pointer">
                    <option value="RRIM 600" selected>RRIM 600 (ยอดนิยม)</option>
                    <option value="RRIT 251">RRIT 251</option>
                    <option value="RRIT 408">RRIT 408</option>
                    <option value="BPM 24">BPM 24</option>
                    <option value="PB 235">PB 235</option>
                  </select>
                </div>
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">ปีที่เริ่มปลูก <span class="text-red-500">*</span></label>
                  <input type="number" id="form-planting-year" value="2018" min="1990" max="2026" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs">
                </div>
                <div>
                  <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">จำนวนต้นยาง (ต้น)</label>
                  <input type="number" id="form-tree-count" value="500" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs">
                </div>
              </div>

              <!-- Row 4 -->
              <div>
                <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">สถานะการกรีด</label>
                <select id="form-tapping-status" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs cursor-pointer">
                  <option value="tapping" selected>เปิดกรีดแล้ว (Tapping Active)</option>
                  <option value="not_tapping">ยังไม่เปิดกรีด (Immature)</option>
                </select>
              </div>

              <!-- Row 5 -->
              <div>
                <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">จุดกึ่งกลางแปลง (Centroid พิกัด GPS)</label>
                <input type="text" id="form-centroid-display" value="9.138240, 99.321850" class="w-full bg-[#f8faf9] font-mono text-gray-700 text-[16px] rounded-full px-4 py-2.5 sm:py-3 border border-gray-200 outline-none shadow-xs" readonly>
                <input type="hidden" id="form-centroid-lat" value="9.138240">
                <input type="hidden" id="form-centroid-lng" value="99.321850">
                <input type="hidden" id="form-geojson-geometry">
              </div>

              <!-- Row 6 -->
              <div>
                <label class="block font-bold text-mezenc-teal mb-1.5 pl-2 text-[16px]">หมายเหตุเพิ่มเติม</label>
                <textarea id="form-notes" rows="2" class="w-full bg-[#f8faf9] hover:bg-white focus:bg-white text-gray-800 text-[16px] rounded-3xl px-4 py-3 border border-gray-200 focus:border-mezenc-brightCyan focus:ring-2 focus:ring-mezenc-brightCyan/20 outline-none transition-all shadow-xs" placeholder="ระบุรายละเอียดเพิ่มเติม เช่น สภาพแปลง ประวัติการใช้ที่ดิน หรือข้อมูลเอกสารแนบ">แปลงยางพาราได้รับการดูแลรักษาอย่างถูกต้อง สอดคล้องตามเกณฑ์พื้นที่เพาะปลูก</textarea>
              </div>
            </div>

            <!-- Direct Action Buttons at Bottom of Step 2 -->
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
              <button 
                type="button" 
                onclick="activateMapDrawFromModal()" 
                class="w-full sm:w-auto px-5 py-2.5 rounded-full border-2 border-gray-300 hover:bg-gray-100 text-gray-600 font-bold text-[16px] transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>‹ วาดแปลงใหม่อีกครั้ง</span>
              </button>

              <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full sm:w-auto justify-end">
                <button 
                  type="button" 
                  onclick="submitPlotFromModal('compliant')" 
                  class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[16px] shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>💾 บันทึกแปลงปลูกลงฐานข้อมูลทันที</span>
                </button>
                <button 
                  type="button" 
                  onclick="goToModalStep(3)" 
                  class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow transition-all flex items-center justify-center gap-1 cursor-pointer"
                >
                  <span>ถัดไป: ตรวจสอบ EUDR ›</span>
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- STEP 3: ตรวจสอบความสอดคล้อง EUDR -->
        <div id="modal-step-content-3" class="hidden space-y-4 text-[16px]">
          <div class="text-center space-y-1">
            <h2 class="text-lg sm:text-xl text-mezenc-teal font-extrabold">
              ขั้นตอนที่ 3: <span class="text-mezenc-brightCyan underline decoration-mezenc-brightCyan underline-offset-4">ระบบตรวจสอบความสอดคล้องตามมาตรฐาน EUDR</span>
            </h2>
            <p class="text-[16px] text-gray-500">ผลการวิเคราะห์พิกัดแปลงเทียบกับ 26 แนวเขตป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี</p>
          </div>

          <div class="border-2 border-dashed border-[#bee6e1] bg-[#f8faf9] rounded-3xl p-6 sm:p-7 space-y-4">
            <!-- Header bar of the card -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-[#bee6e1] shadow-xs flex flex-wrap justify-between items-center gap-2">
              <div class="flex items-center gap-2 text-[16px] text-mezenc-teal font-bold">
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mezenc-teal shrink-0">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M8 16L4.35009 13.3929C2.24773 11.8912 1 9.46667 1 6.88306V3L8 0L15 3V6.88306C15 9.46667 13.7523 11.8912 11.6499 13.3929L8 16ZM12.2071 5.70711L10.7929 4.29289L7 8.08579L5.20711 6.29289L3.79289 7.70711L7 10.9142L12.2071 5.70711Z" fill="#00a896"/>
                </svg>
                <span>ผลการวิเคราะห์เชิงพื้นที่ (Spatial Deforestation Analysis)</span>
              </div>

              <!-- Real Analysis Status Badge -->
              <div id="step3-auto-status-badge" class="flex items-center gap-2">
                <span class="text-[13px] font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full border border-gray-200">
                  ⚡ วิเคราะห์อัตโนมัติ 26 ผืนป่า
                </span>
                <span id="step3-status-pill" class="px-3.5 py-1 rounded-full text-[14px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                  🟢 ปลอดภัย / ผ่านเกณฑ์
                </span>
              </div>
            </div>

            <!-- Card Body Container -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-[#bee6e1] shadow-xs space-y-4 text-[16px]">
              
              <!-- 1. DYNAMIC RESULT CONTAINER: COMPLIANT CASE (GREEN) -->
              <div id="modal-case-compliant" class="space-y-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-[#f4faf7] border-2 border-emerald-300 flex items-start gap-3.5">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                    ✅
                  </div>
                  <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="text-[16px] sm:text-lg font-extrabold text-emerald-800">
                        ผ่านเกณฑ์ EUDR 100% (Zero Deforestation Compliant)
                      </h3>
                      <span class="bg-emerald-100 text-emerald-800 font-bold text-[14px] px-2.5 py-0.5 rounded-full border border-emerald-300">
                        🟢 ปลอดภัย
                      </span>
                    </div>
                    <p class="text-[16px] text-emerald-700 font-medium mt-1 leading-relaxed">
                      แปลงปลูกนี้ <strong class="underline">ไม่พบการทับซ้อน</strong> กับแนวเขตป่าสงวนแห่งชาติทั้ง 26 แห่งในจังหวัดสุราษฎร์ธานี และอยู่นอกระยะ Buffer Zone ปลอดภัย (> 500 ม.)
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[16px]">
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Zero Deforestation</span>
                    <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-1 text-[15px]">🌲 ไม่อยู่ในเขตป่าสงวน</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Buffer Distance</span>
                    <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-1 text-[15px]" id="modal-compliant-buffer-text">📏 ห่างป่า > 500 ม. (ปลอดภัย)</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Cut-off Date</span>
                    <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-1 text-[15px]" id="modal-compliant-cutoff-text">📅 ปลูกก่อนปี 2020 (สอดคล้อง)</strong>
                  </div>
                </div>

                <div class="p-3.5 bg-emerald-50 rounded-2xl text-[16px] text-emerald-900 border border-emerald-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                  <span class="leading-relaxed">🎉 แปลงนี้พร้อมสำหรับ <strong>บันทึกลงฐานข้อมูลและออกเอกสารรับรอง EUDR Passport (QR Code)</strong></span>
                  <button 
                    type="button" 
                    onclick="goToModalStep(4)" 
                    class="px-5 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow transition-all whitespace-nowrap cursor-pointer"
                  >
                    ออก QR Code ทันที ➔
                  </button>
                </div>
              </div>

              <!-- 2. DYNAMIC RESULT CONTAINER: UNDER REVIEW / BUFFER ZONE CASE (ORANGE) -->
              <div id="modal-case-review" class="hidden space-y-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-orange-50/80 border-2 border-orange-300 flex items-start gap-3.5">
                  <div class="w-10 h-10 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                    ⚠️
                  </div>
                  <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="text-[16px] sm:text-lg font-extrabold text-orange-950">
                        มีความเสี่ยง / อยู่ในโซนเฝ้าระวังแนวเขตป่าสงวน (Buffer Zone)
                      </h3>
                      <span class="bg-orange-100 text-orange-900 font-bold text-[14px] px-2.5 py-0.5 rounded-full border border-orange-300" id="modal-review-badge">
                        🟠 มีความเสี่ยง (&lt; 500 ม.)
                      </span>
                    </div>
                    <p class="text-[16px] text-orange-900 font-medium mt-1 leading-relaxed" id="modal-review-desc">
                      แปลงปลูกนี้ <strong class="underline">อยู่นอกเขตป่าสงวน</strong> แต่มีระยะประชิดแนวเขตป่าสงวนแห่งชาติ &lt; 500 เมตร ซึ่งอยู่ในระยะกันชนที่ต้องเฝ้าระวังเป็นพิเศษ
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[16px]">
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Forest Proximity</span>
                    <strong class="text-orange-700 font-bold flex items-center gap-1 mt-1 text-[15px]" id="modal-review-proximity">⚠️ ประชิดแนวเขตป่าสงวน</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Buffer Distance</span>
                    <strong class="text-orange-700 font-bold flex items-center gap-1 mt-1 text-[15px]" id="modal-review-distance-text">📏 ระยะห่าง &lt; 500 ม. (เฝ้าระวัง)</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">EUDR Status</span>
                    <strong class="text-orange-700 font-bold flex items-center gap-1 mt-1 text-[15px]">🟠 บันทึกสถานะเฝ้าระวัง</strong>
                  </div>
                </div>

                <div class="p-3.5 bg-orange-50 rounded-2xl text-[16px] text-orange-950 border border-orange-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                  <span class="leading-relaxed">📌 แปลงนี้อยู่ในโซนเฝ้าระวัง สามารถ <strong>ออกเอกสารรับรองเฝ้าระวัง (QR Code)</strong> หรือบันทึกข้อมูลได้</span>
                  <div class="flex items-center gap-2">
                    <button 
                      type="button" 
                      onclick="goToModalStep(4)" 
                      class="px-5 py-2.5 rounded-full bg-orange-500 hover:bg-orange-600 text-white font-bold text-[16px] shadow transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>ออก QR Code เฝ้าระวัง ➔</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- 3. DYNAMIC RESULT CONTAINER: OVERLAP CASE (RED) -->
              <div id="modal-case-overlap" class="hidden space-y-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-red-50/80 border-2 border-red-300 flex items-start gap-3.5">
                  <div class="w-10 h-10 rounded-2xl bg-red-600 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                    ⛔
                  </div>
                  <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <h3 class="text-[16px] sm:text-lg font-extrabold text-red-900">
                        ซ้อนทับแนวเขตป่าสงวนแห่งชาติ (Non-Compliant)
                      </h3>
                      <span class="bg-red-100 text-red-900 font-bold text-[14px] px-2.5 py-0.5 rounded-full border border-red-300">
                        🔴 ซ้อนทับเขตป่า
                      </span>
                    </div>
                    <p class="text-[16px] text-red-800 font-medium mt-1 leading-relaxed" id="modal-overlap-desc">
                      แปลงปลูกนี้ <strong class="underline">ตรวจพบการทับซ้อนกับแนวเขตป่าสงวนแห่งชาติ</strong> ซึ่งเป็นพื้นที่คุ้มครองและไม่ผ่านเกณฑ์ EUDR
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[16px]">
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Forest Proximity</span>
                    <strong class="text-red-700 font-bold flex items-center gap-1 mt-1 text-[15px]" id="modal-overlap-forest-name">⛔ อยู่ในเขตป่าสงวน</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Compliance Status</span>
                    <strong class="text-red-700 font-bold flex items-center gap-1 mt-1 text-[15px]">🔴 ไม่ผ่านเกณฑ์ EUDR</strong>
                  </div>
                  <div class="p-3 rounded-2xl bg-[#f8faf9] border border-gray-200">
                    <span class="text-gray-400 block text-[13px] uppercase font-bold">Action</span>
                    <strong class="text-red-700 font-bold flex items-center gap-1 mt-1 text-[15px]">⚠️ ระงับการออก Passport</strong>
                  </div>
                </div>

                <div class="p-3.5 bg-red-50 rounded-2xl border border-red-200 text-[16px] text-red-900 flex flex-col sm:flex-row items-center justify-between gap-3">
                  <span class="leading-relaxed">📌 ระบบจะทำการบันทึกข้อมูลแปลงนี้เป็น <strong>"ไม่ผ่านเกณฑ์ (Non-Compliant)"</strong> เพื่อเก็บประวัติในระบบ</span>
                  <button 
                    type="button" 
                    onclick="submitPlotFromModal('non_compliant')" 
                    class="px-5 py-2.5 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold text-[16px] shadow transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer"
                  >
                    <span>💾 บันทึกข้อมูล</span>
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- STEP 4: ออก QR Code และบันทึกเสร็จสิ้น -->
        <div id="modal-step-content-4" class="hidden space-y-4 text-[16px]">
          <div class="text-center space-y-1">
            <h2 class="text-lg sm:text-xl text-mezenc-teal font-extrabold">
              ขั้นตอนที่ 4: <span class="text-mezenc-brightCyan underline decoration-mezenc-brightCyan underline-offset-4">ออกหนังสือรับรอง EUDR Digital Passport (QR Code)</span>
            </h2>
            <p class="text-[16px] text-gray-500">เอกสารดิจิทัลสำหรับตรวจสอบย้อนกลับ (Traceability) สำหรับการส่งออกยางพาราสากล</p>
          </div>

          <div class="border-2 border-dashed border-[#bee6e1] bg-[#f8faf9] rounded-3xl p-6 sm:p-7 space-y-4">
            <!-- Header bar of the card -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-[#bee6e1] shadow-xs flex flex-wrap justify-between items-center gap-2">
              <div class="flex items-center gap-2 text-[16px] text-mezenc-teal font-bold">
                <span class="text-lg">🛡️</span>
                <span>EUDR DIGITAL PASSPORT CERTIFICATE</span>
                <span class="text-[16px] text-emerald-700 font-bold bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-300">
                  ✅ VERIFIED
                </span>
              </div>
              <div class="text-right">
                <span class="text-[16px] text-gray-400">เลขที่ใบรับรอง: </span>
                <span class="font-mono font-bold text-[16px] text-mezenc-teal">EUDR-TH-84-2026-0889</span>
              </div>
            </div>

            <!-- Body -->
            <div class="bg-white p-5 sm:p-6 rounded-2xl border border-[#bee6e1] shadow-xs space-y-5 text-[16px]">
              <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
                <!-- QR Box -->
                <div class="sm:col-span-5 flex flex-col items-center justify-center p-4 bg-[#f8faf9] rounded-2xl border border-[#bee6e1] shadow-inner space-y-2">
                  <div class="w-32 h-32 bg-white p-2 border-2 border-mezenc-brightCyan rounded-2xl flex items-center justify-center relative shadow-sm">
                    <svg class="w-full h-full text-mezenc-teal" viewBox="0 0 100 100" fill="currentColor">
                      <path d="M10,10 h30 v30 h-30 z M15,15 v20 h20 v-20 z M20,20 h10 v10 h-10 z"/>
                      <path d="M60,10 h30 v30 h-30 z M65,15 v20 h20 v-20 z M70,20 h10 v10 h-10 z"/>
                      <path d="M10,60 h30 v30 h-30 z M15,65 v20 h20 v-20 z M20,70 h10 v10 h-10 z"/>
                      <path d="M45,15 h5 v5 h-5 z M50,20 h5 v5 h-5 z M45,25 h10 v5 h-10 z M50,35 h5 v10 h-5 z"/>
                      <path d="M65,45 h10 v5 h-10 z M80,45 h10 v10 h-10 z M60,55 h5 v10 h-5 z M75,55 h10 v5 h-10 z M70,65 h15 v5 h-15 z M85,75 h5 v15 h-5 z M65,80 h15 v5 h-15 z"/>
                      <path d="M45,60 h5 v10 h-5 z M50,75 h5 v5 h-5 z M45,85 h10 v5 h-10 z M55,65 h5 v5 h-5 z M55,75 h5 v5 h-5 z"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                      <span class="w-6 h-6 bg-white rounded-full flex items-center justify-center text-[16px] shadow">🌲</span>
                    </div>
                  </div>
                  <span class="text-[16px] font-bold text-mezenc-teal">สแกนเพื่อตรวจสอบย้อนกลับ</span>
                  <span class="text-[16px] text-gray-500 font-mono">Traceability ID: RB-2026-009</span>
                </div>

                <!-- Info Grid -->
                <div class="sm:col-span-7 space-y-2.5 text-[16px]">
                  <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">เจ้าของแปลง:</span>
                    <strong class="text-mezenc-teal font-bold text-[16px]" id="modal-sum-farmer">นางสาวมนัสนันท์ อนันตณรงค์</strong>
                  </div>
                  <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">ชื่อแปลงปลูก:</span>
                    <strong class="text-gray-800 font-bold text-[16px]" id="modal-sum-plot">แปลงยางพาราเขาท่าเพชร 1</strong>
                  </div>
                  <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">เอกสารสิทธิ์:</span>
                    <strong class="text-gray-800 text-[16px]" id="modal-sum-deed">โฉนดที่ดิน (น.ส. 4 จ) เลขที่ 8401-4458</strong>
                  </div>
                  <div class="flex justify-between py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">พิกัด GPS:</span>
                    <strong class="font-mono text-gray-800 text-[16px]" id="modal-sum-coords">9.138240, 99.321850</strong>
                  </div>
                  <div class="flex justify-between py-1.5">
                    <span class="text-gray-500">สถานะ EUDR:</span>
                    <span id="modal-sum-eudr-badge" class="font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 text-[16px]">🟢 ผ่านเกณฑ์ 100% (Compliant)</span>
                  </div>
                </div>
              </div>

              <!-- Footer of Card -->
              <div class="pt-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <span class="text-[16px] text-gray-500">บันทึกข้อมูลแปลงเข้าสู่ระบบฐานข้อมูล GeoRubber Watch</span>
                <button 
                  type="button" 
                  onclick="submitPlotFromModal()" 
                  class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer uppercase"
                >
                  <span>💾 บันทึกแปลงปลูกและเสร็จสิ้นกระบวนการ</span>
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer (Matching order.php / Screenshot) -->
      <div class="bg-[#fcfdfd] border-t border-gray-100 px-6 sm:px-10 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0 text-[16px]">
        <div class="text-[16px] font-semibold text-mezenc-teal flex items-center gap-2">
          <span>🌲 GeoRubber Watch</span>
          <span class="text-gray-300">|</span>
          <span class="text-gray-500 font-normal">ระบบมาตรฐานการตรวจสอบย้อนกลับ EUDR สุราษฎร์ธานี</span>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
          <button 
            type="button" 
            id="modal-btn-prev" 
            onclick="prevModalStep()" 
            class="hidden px-4 py-2 rounded-full bg-white hover:bg-gray-100 text-gray-600 font-bold text-[16px] border border-gray-300 transition-all cursor-pointer shadow-xs"
          >
            ‹ ย้อนกลับ
          </button>
          <button 
            type="button" 
            id="modal-btn-save" 
            onclick="submitPlotFromModal()" 
            class="px-5 py-2 rounded-full bg-white hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-[16px] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <span id="modal-btn-save-label">💾 บันทึกข้อมูล</span>
          </button>
          <button 
            type="button" 
            id="modal-btn-next" 
            onclick="nextModalStep()" 
            class="px-5 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow-sm hover:shadow transition-all flex items-center gap-1 cursor-pointer"
          >
            <span id="modal-btn-next-label">หน้าถัดไป</span>
            <span class="text-[16px] font-bold">›</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- =========================================================================
       5. MODAL: QR Code & Traceability Verification
       ========================================================================= -->
  <div id="qrModal" class="modal-overlay">
    <div class="modal-card max-w-md text-center p-6 space-y-4 text-[16px]">
      <div class="flex justify-between items-center pb-2 border-b border-gray-100">
        <h3 class="font-extrabold text-mezenc-teal text-lg" id="qr-plot-title">QR Code หนังสือรับรอง</h3>
        <button onclick="App.closeModal('qrModal')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-[16px] flex items-center justify-center">✕</button>
      </div>

      <div class="text-[16px] text-mezenc-brightCyan font-mono font-bold" id="qr-plot-code"></div>

      <div id="qrcode-canvas" class="flex justify-center p-4 bg-white rounded-2xl border border-gray-100 shadow-inner my-2"></div>

      <div class="text-[16px] text-gray-600 break-all font-mono p-2.5 bg-[#f8faf9] rounded-xl border border-gray-100 leading-relaxed" id="qr-token-display"></div>

      <div class="pt-2 flex flex-col gap-2">
        <a id="qr-url-link" href="#" target="_blank" class="w-full py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[16px] shadow-md transition-all">
          🌐 เปิดตรวจสอบหนังสือรับรอง (EUDR Passport)
        </a>
        <button onclick="App.copyToClipboard(document.getElementById('qr-url-link').href)" class="w-full py-2 rounded-full bg-white hover:bg-gray-50 text-mezenc-teal font-bold text-[16px] border border-gray-200 transition-all cursor-pointer">
          📋 คัดลอกลิงก์ตรวจสอบ
        </button>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>
  <script src="assets/js/map.js?v=<?= time() ?>"></script>

  <script>
    // Toggle Slide-out Floating Layer Panel on map.php
    function toggleLayerPanel(forceState = null) {
      const panel = document.getElementById('floatingLayerPanel');
      const btn = document.getElementById('btn-toggle-panel');
      if (!panel) return;

      const isCurrentlyOpen = panel.classList.contains('translate-x-0');
      const shouldOpen = forceState !== null ? forceState : !isCurrentlyOpen;

      if (shouldOpen) {
        panel.classList.remove('-translate-x-[120%]', 'opacity-0', 'pointer-events-none');
        panel.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');
        if (btn) {
          btn.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
          btn.classList.remove('bg-white/95', 'text-mezenc-teal');
        }
      } else {
        panel.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
        panel.classList.add('-translate-x-[120%]', 'opacity-0', 'pointer-events-none');
        if (btn) {
          btn.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
          btn.classList.add('bg-white/95', 'text-mezenc-teal');
        }
      }

      setTimeout(() => {
        if (GeoMap && GeoMap.map) {
          GeoMap.map.invalidateSize();
        }
      }, 320);
    }

    // Direct draw trigger
    function activateMapDrawDirect() {
      if (GeoMap && GeoMap.drawControl && GeoMap.drawControl._toolbars && GeoMap.drawControl._toolbars.draw) {
        GeoMap.drawControl._toolbars.draw._modes.polygon.handler.enable();
        App.showToast('เริ่มคลิกบนแผนที่ GIS เพื่อวาดแปลงยางพารา (Polygon)', 'info');
      } else {
        App.showToast('กรุณารอโหลดเครื่องมือวาดสักครู่', 'info');
      }
    }

    // GPS Live Pin Marker & Accuracy Circle Tracker
    let userGpsMarker = null;
    let userAccuracyCircle = null;

    function clearUserGpsPin() {
      if (userGpsMarker && GeoMap.map) {
        GeoMap.map.removeLayer(userGpsMarker);
        userGpsMarker = null;
      }
      if (userAccuracyCircle && GeoMap.map) {
        GeoMap.map.removeLayer(userAccuracyCircle);
        userAccuracyCircle = null;
      }
    }

    // Direct GPS Locate & Pin trigger
    function locateUserDirect() {
      if (!navigator.geolocation) {
        App.showToast('เบราว์เซอร์ของคุณไม่รองรับการระบุพิกัด GPS', 'error');
        return;
      }
      App.showToast('📡 กำลังค้นหาและปักหมุดพิกัด GPS ของคุณ...', 'info');
      
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          const accuracy = Math.round(pos.coords.accuracy || 10);

          if (!GeoMap || !GeoMap.map) return;

          clearUserGpsPin();

          // Pulse animation custom pin icon
          const pulseIcon = L.divIcon({
            className: 'gps-pulse-pin-container',
            html: `
              <div style="position: relative; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <div style="position: absolute; width: 36px; height: 36px; background: rgba(0, 166, 153, 0.35); border-radius: 50%; animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                <div style="position: absolute; width: 28px; height: 28px; background: rgba(0, 166, 153, 0.5); border-radius: 50%;"></div>
                <div style="width: 22px; height: 22px; background: #00a699; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 10;"></div>
              </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
          });

          // Draw Accuracy Circle
          userAccuracyCircle = L.circle([lat, lng], {
            radius: Math.max(accuracy, 25),
            color: '#00a699',
            fillColor: '#00a699',
            fillOpacity: 0.15,
            weight: 1.5,
            dashArray: '3, 3'
          }).addTo(GeoMap.map);

          // Add Pulse Marker
          userGpsMarker = L.marker([lat, lng], { icon: pulseIcon }).addTo(GeoMap.map);

          GeoMap.map.flyTo([lat, lng], 17, { duration: 1.4 });

          const popupContent = `
            <div style="font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif; min-width: 240px; padding: 4px;">
              <div style="font-size: 15px; font-weight: 800; color: #0e4d4e; margin-bottom: 2px;">📍 ตำแหน่งพิกัดปัจจุบันของคุณ</div>
              <div style="font-size: 11px; color: #64748b; margin-bottom: 6px;">Live GPS Geolocation (ความแม่นยำ ±${accuracy} ม.)</div>
              <div style="background: #f8faf9; border: 1.5px solid #bee6e1; border-radius: 8px; padding: 6px 8px; font-size: 12px; margin-bottom: 8px;">
                <div>🌐 <strong>ละติจูด:</strong> <span style="font-family: monospace; font-weight: bold; color: #0e4d4e;">${lat.toFixed(6)}</span></div>
                <div>🌐 <strong>ลองจิจูด:</strong> <span style="font-family: monospace; font-weight: bold; color: #0e4d4e;">${lng.toFixed(6)}</span></div>
              </div>
              <div style="display: flex; gap: 6px;">
                <button type="button" onclick="activateMapDrawDirect()" style="flex: 1; padding: 6px 8px; border-radius: 6px; background: #00a699; color: #fff; font-size: 11px; font-weight: bold; border: none; cursor: pointer;">
                  ✏️ เริ่มวาดแปลงที่นี่
                </button>
                <button type="button" onclick="clearUserGpsPin()" style="padding: 6px 8px; border-radius: 6px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: bold; border: 1px solid #fca5a5; cursor: pointer;">
                  🗑️ ลบหมุด
                </button>
              </div>
            </div>
          `;

          userGpsMarker.bindPopup(popupContent).openPopup();
          App.showToast(`📍 ระบุตำแหน่ง GPS สำเร็จ: ${lat.toFixed(5)}, ${lng.toFixed(5)}`, 'success');
        },
        (err) => {
          App.showToast('ไม่สามารถดึงพิกัด GPS ได้: ' + err.message, 'error');
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    }

    // Fullscreen Toggle
    function toggleFullscreen() {
      const container = document.getElementById('gis-map-card-container');
      if (!container) return;

      if (!document.fullscreenElement) {
        container.requestFullscreen().catch(err => {
          console.warn('Fullscreen error:', err);
        });
      } else {
        document.exitFullscreen();
      }
    }

    // Pin Marker Tool & Real-Time Spatial Risk Check (ปักหมุดตรวจสอบพิกัด & Buffer Zone)
    let isMapPinMode = false;
    let userPinMarker = null;
    let userPinBufferCircle = null;

    function clearMapPin() {
      if (userPinMarker && GeoMap && GeoMap.map) {
        GeoMap.map.removeLayer(userPinMarker);
        userPinMarker = null;
      }
      if (userPinBufferCircle && GeoMap && GeoMap.map) {
        GeoMap.map.removeLayer(userPinBufferCircle);
        userPinBufferCircle = null;
      }
      toggleMapPinMode(false);
    }

    function toggleMapPinMode(forceState = null) {
      isMapPinMode = forceState !== null ? forceState : !isMapPinMode;
      const btn = document.getElementById('btn-floating-pin-mode');

      if (isMapPinMode) {
        if (btn) {
          btn.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
          btn.classList.remove('bg-white/95', 'text-mezenc-teal');
        }
        if (!userPinMarker && GeoMap && GeoMap.map) {
          const center = GeoMap.map.getCenter();
          placeMapPin(center.lat, center.lng);
        }
        if (typeof App !== 'undefined' && typeof App.showToast === 'function') {
          App.showToast('📍 โหมดปักหมุดเปิดใช้งาน: คลิกหรือลากหมุดบนแผนที่เพื่อดูพิกัดและความเสี่ยง', 'info');
        }
      } else {
        if (btn) {
          btn.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
          btn.classList.add('bg-white/95', 'text-mezenc-teal');
        }
      }
    }

    function handleMapPinClick(e) {
      if (!isMapPinMode) return;
      placeMapPin(e.latlng.lat, e.latlng.lng);
    }

    function placeMapPin(lat, lng) {
      if (!GeoMap || !GeoMap.map) return;

      const pinIcon = L.divIcon({
        className: 'custom-pin-marker',
        html: `
          <div class="pin-drop-animation relative cursor-pointer" style="filter: drop-shadow(0 6px 14px rgba(14,77,78,0.45)); transform: translate(-17px, -42px);">
            <svg width="34" height="42" viewBox="0 0 384 512" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z" fill="#00A896"/>
              <circle cx="192" cy="192" r="80" fill="#FFFFFF"/>
              <circle cx="192" cy="192" r="44" fill="#024959"/>
            </svg>
          </div>
        `,
        iconSize: [34, 42],
        iconAnchor: [0, 0]
      });

      if (userPinMarker) {
        userPinMarker.setLatLng([lat, lng]);
      } else {
        userPinMarker = L.marker([lat, lng], {
          icon: pinIcon,
          draggable: true,
          zIndexOffset: 1200
        }).addTo(GeoMap.map);

        userPinMarker.on('dragend', (evt) => {
          const pos = evt.target.getLatLng();
          analyzePinLocation(pos.lat, pos.lng);
        });
      }

      // Draw 500m buffer circle around pin
      if (userPinBufferCircle) {
        userPinBufferCircle.setLatLng([lat, lng]);
      } else {
        userPinBufferCircle = L.circle([lat, lng], {
          radius: 500,
          color: '#f59e0b',
          fillColor: '#f59e0b',
          fillOpacity: 0.14,
          weight: 2,
          dashArray: '5, 5'
        }).addTo(GeoMap.map);
      }

      toggleMapPinMode(true);
      analyzePinLocation(lat, lng);
    }

    // Exact Spatial Proximity & Overlap Calculation Engine (Turf.js)
    function calculateForestSpatialProximity(lat, lng) {
      let insideForest = null;
      let nearestForest = null;
      let minDistanceKm = Infinity;

      const features = (GeoMap && GeoMap.forestData && GeoMap.forestData.features)
        ? GeoMap.forestData.features
        : (GeoMap && GeoMap.forestData ? GeoMap.forestData : []);

      if (typeof turf !== 'undefined' && features.length > 0) {
        const pt = turf.point([lng, lat]);

        const getDistanceToFeature = (feature) => {
          if (!feature || !feature.geometry) return Infinity;
          const geom = feature.geometry;
          let minD = Infinity;

          const checkRing = (coords) => {
            if (!coords || coords.length < 2) return;
            try {
              const line = turf.lineString(coords);
              const d = turf.pointToLineDistance(pt, line, { units: 'kilometers' });
              if (!isNaN(d) && isFinite(d) && d < minD) {
                minD = d;
              }
            } catch (e) {
              for (let i = 0; i < coords.length; i++) {
                const p = turf.point(coords[i]);
                const d = turf.distance(pt, p, { units: 'kilometers' });
                if (!isNaN(d) && isFinite(d) && d < minD) {
                  minD = d;
                }
              }
            }
          };

          if (geom.type === 'Polygon') {
            for (let r = 0; r < geom.coordinates.length; r++) {
              checkRing(geom.coordinates[r]);
            }
          } else if (geom.type === 'MultiPolygon') {
            for (let p = 0; p < geom.coordinates.length; p++) {
              const poly = geom.coordinates[p];
              for (let r = 0; r < poly.length; r++) {
                checkRing(poly[r]);
              }
            }
          }
          return minD;
        };

        for (let i = 0; i < features.length; i++) {
          const f = features[i];
          try {
            if (f.geometry) {
              let isInside = false;
              try {
                isInside = turf.booleanPointInPolygon(pt, f);
              } catch (ePoly) {
                try {
                  isInside = turf.booleanPointInPolygon(pt, f.geometry);
                } catch (eGeom) {}
              }

              if (isInside) {
                insideForest = f.properties;
                minDistanceKm = 0;
                nearestForest = f.properties;
                break;
              }

              const distToBoundary = getDistanceToFeature(f);
              if (distToBoundary < minDistanceKm) {
                minDistanceKm = distToBoundary;
                nearestForest = f.properties;
              }
            }
          } catch (err) {
            console.error('Spatial check error for forest:', f, err);
          }
        }
      }

      const distMeters = insideForest ? 0 : (isFinite(minDistanceKm) ? Math.round(minDistanceKm * 1000) : 9999);
      
      let status = 'compliant';
      if (insideForest) {
        status = 'non_compliant';
      } else if (distMeters <= 500) {
        status = 'buffer_zone';
      }

      return {
        insideForest,
        nearestForest,
        minDistanceKm,
        distMeters,
        status
      };
    }

    function analyzePinLocation(lat, lng) {
      const result = calculateForestSpatialProximity(lat, lng);
      const { insideForest, nearestForest, distMeters, status } = result;

      let statusTitle = '';
      let statusBadge = '';
      let adviceText = '';
      let circleColor = '#059669'; // 🟢 ปลอดภัย (Green)
      let circleFill = '#10b981';

      if (status === 'non_compliant') {
        statusTitle = `🔴 ซ้อนทับเขต ${insideForest.name_th || 'ป่าสงวนแห่งชาติ'}`;
        statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-red-600 text-white font-bold text-xs inline-block">🔴 ซ้อนทับเขตป่าสงวน (ไม่ผ่านเกณฑ์)</span>';
        adviceText = `จุดพิกัดนี้ตั้งอยู่ในแนวเขตป่าสงวนแห่งชาติ <strong>${insideForest.name_th || 'ป่าสงวน'}</strong> (รหัส: ${insideForest.forest_code || '-'}) ซึ่งเป็นเขตป่าเพื่อการอนุรักษ์ (Zone C)`;
        circleColor = '#dc2626'; // 🔴 ซ้อนทับ (Red)
        circleFill = '#ef4444';
      } else if (status === 'buffer_zone') {
        statusTitle = `🟠 มีความเสี่ยง (โซนเฝ้าระวัง Buffer ${distMeters} ม.)`;
        statusBadge = `<span class="px-2.5 py-0.5 rounded-lg bg-orange-500 text-white font-bold text-xs inline-block">🟠 มีความเสี่ยง (โซนเฝ้าระวัง ${distMeters} ม.)</span>`;
        adviceText = `อยู่นอกแนวเขตป่า แต่อยู่ในระยะกันชนใกล้กับ <strong>${nearestForest ? nearestForest.name_th : 'ป่าสงวน'}</strong> เพียง ${distMeters} เมตร (อยู่ในระยะเฝ้าระวังไม่เกิน 500 ม.)`;
        circleColor = '#ea580c'; // 🟠 มีความเสี่ยง (Orange)
        circleFill = '#f97316';
      } else {
        statusTitle = `🟢 ปลอดภัย ผ่านเกณฑ์ EUDR`;
        statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-xs inline-block">🟢 ปลอดภัย (ผ่านเกณฑ์ EUDR)</span>';
        adviceText = `อยู่นอกแนวเขตป่าสงวนแห่งชาติ โดยห่างจาก <strong>${nearestForest ? nearestForest.name_th : 'แนวเขตป่า'}</strong> ประมาณ ${distMeters >= 1000 ? (distMeters/1000).toFixed(2) + ' กม.' : distMeters + ' ม.'}`;
        circleColor = '#059669'; // 🟢 ปลอดภัย (Green)
        circleFill = '#10b981';
      }

      if (userPinBufferCircle) {
        userPinBufferCircle.setStyle({
          color: circleColor,
          fillColor: circleFill,
          fillOpacity: 0.18
        });
      }

      const pinSvgIcon = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; display: inline-block; vertical-align: -3px; flex-shrink: 0;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#00A896"/></svg>`;

      if (userPinMarker) {
        userPinMarker.bindPopup(`
          <div style="min-width: 270px; font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif; padding: 4px;">
            <div style="font-weight: 800; font-size: 15px; margin-bottom: 4px; color: #0e4d4e; display: flex; align-items: center; gap: 6px;">
              ${pinSvgIcon}
              <span>${statusTitle}</span>
            </div>
            <div style="margin-bottom: 6px;">${statusBadge}</div>
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 8px 10px; border-radius: 10px; font-size: 12px; line-height: 1.5; margin-bottom: 8px;">
              ${adviceText}
            </div>
            <div style="font-size: 12px; color: #64748b; font-family: monospace; margin-bottom: 8px;">
              🌐 พิกัด: <strong>${lat.toFixed(5)}, ${lng.toFixed(5)}</strong>
            </div>
            <div style="display: flex; gap: 6px;">
              <button type="button" onclick="if(GeoMap.drawControl && GeoMap.drawControl._toolbars && GeoMap.drawControl._toolbars.draw) { GeoMap.drawControl._toolbars.draw._modes.polygon.handler.enable(); } else if (GeoMap.startDrawPolygon) { GeoMap.startDrawPolygon(); }" style="flex: 1; padding: 6px 8px; border-radius: 8px; background: #00a699; color: #fff; font-size: 11px; font-weight: bold; border: none; cursor: pointer;">
                ✏️ เริ่มวาดแปลงที่นี่
              </button>
              <button type="button" onclick="clearMapPin()" style="padding: 6px 8px; border-radius: 8px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: bold; border: 1px solid #fca5a5; cursor: pointer;">
                🗑️ ลบหมุด
              </button>
            </div>
          </div>
        `).openPopup();
      }
    }

    // Toggle Mobile Drawer
    function toggleMobileDrawer() {
      const drawer = document.getElementById('mobile-drawer');
      const content = document.getElementById('mobile-drawer-content');
      if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        setTimeout(() => {
          drawer.classList.remove('opacity-0');
          content.classList.remove('translate-x-full');
        }, 10);
      } else {
        drawer.classList.add('opacity-0');
        content.classList.add('translate-x-full');
        setTimeout(() => {
          drawer.classList.add('hidden');
        }, 300);
      }
    }

    // Filter Rubber Plots in Sidebar List
    function filterPlotsList() {
      const query = document.getElementById('plot-search-input').value.toLowerCase();
      const status = document.getElementById('plot-status-filter').value;

      const filtered = GeoMap.plotsData.filter(f => {
        const p = f.properties;
        const matchesText = p.plot_name.toLowerCase().includes(query) ||
                            p.plot_code.toLowerCase().includes(query) ||
                            p.farmer_name.toLowerCase().includes(query);
        const matchesStatus = !status || p.eudr_status === status;
        return matchesText && matchesStatus;
      });

      GeoMap.renderSidebarPlotsList(filtered);
    }

    // Wizard Step Engine for Add Plot Modal
    let modalCurrentStep = 1;
    let modalPresetMode = "compliant";

    function openAddPlotWizard() {
      goToModalStep(1);
      App.openModal('addPlotModal');
    }

    function renderStep3SpatialResult() {
      const check = window.currentDrawnSpatialCheck;
      const caseCompliant = document.getElementById("modal-case-compliant");
      const caseReview = document.getElementById("modal-case-review");
      const caseOverlap = document.getElementById("modal-case-overlap");
      const statusPill = document.getElementById("step3-status-pill");

      // Hide all cases by default
      if (caseCompliant) caseCompliant.classList.add("hidden");
      if (caseReview) caseReview.classList.add("hidden");
      if (caseOverlap) caseOverlap.classList.add("hidden");

      let currentStatus = 'compliant';
      if (check) {
        if (check.has_overlap || check.eudr_status === 'non_compliant') {
          currentStatus = 'non_compliant';
        } else if (check.eudr_status === 'under_review' || (check.nearest_forest_distance_m !== undefined && check.nearest_forest_distance_m < 500 && !check.has_overlap)) {
          currentStatus = 'under_review';
        } else {
          currentStatus = check.eudr_status || 'compliant';
        }
      }

      modalPresetMode = currentStatus;

      const btnNext = document.getElementById("modal-btn-next");
      const btnNextLabel = document.getElementById("modal-btn-next-label");

      if (currentStatus === 'compliant') {
        if (caseCompliant) caseCompliant.classList.remove("hidden");
        if (statusPill) {
          statusPill.className = "px-3.5 py-1 rounded-full text-[14px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs";
          statusPill.innerHTML = "🟢 ปลอดภัย / ผ่านเกณฑ์ EUDR";
        }
        const bufferTextElem = document.getElementById("modal-compliant-buffer-text");
        if (bufferTextElem && check && check.nearest_forest_distance_m) {
          bufferTextElem.innerText = `📏 ห่างป่า ${Math.round(check.nearest_forest_distance_m).toLocaleString()} ม. (ปลอดภัย)`;
        }
        const plantYear = parseInt(document.getElementById("form-planting-year")?.value) || 2018;
        const cutoffElem = document.getElementById("modal-compliant-cutoff-text");
        if (cutoffElem) {
          cutoffElem.innerText = `📅 ปลูกปี ${plantYear} (สอดคล้อง)`;
        }
        // ปลอดภัย: แสดงปุ่มหน้าถัดไปเพื่อไปขั้นตอนที่ 4
        if (btnNext) btnNext.classList.remove("hidden");
        if (btnNextLabel) btnNextLabel.innerText = "หน้าถัดไป: ออก QR Code";
      } else if (currentStatus === 'under_review') {
        if (caseReview) caseReview.classList.remove("hidden");
        const dist = check && check.nearest_forest_distance_m ? Math.round(check.nearest_forest_distance_m) : null;
        if (statusPill) {
          statusPill.className = "px-3.5 py-1 rounded-full text-[14px] font-bold bg-orange-100 text-orange-900 border border-orange-300 shadow-xs";
          statusPill.innerHTML = `🟠 โซนเฝ้าระวัง ${dist ? `(${dist} ม.)` : '(< 500 ม.)'}`;
        }
        const reviewDistElem = document.getElementById("modal-review-distance-text");
        if (reviewDistElem && dist) {
          reviewDistElem.innerText = `📏 ระยะห่าง ${dist} ม. (เฝ้าระวัง)`;
        }
        const reviewProxElem = document.getElementById("modal-review-proximity");
        if (reviewProxElem && check && check.nearest_forest_name) {
          reviewProxElem.innerText = `⚠️ ใกล้ ${check.nearest_forest_name}`;
        }
        const reviewDesc = document.getElementById("modal-review-desc");
        if (reviewDesc && check && check.nearest_forest_name) {
          reviewDesc.innerHTML = `แปลงปลูกนี้ <strong class="underline">อยู่นอกเขตป่าสงวน</strong> แต่มีระยะประชิดแนวเขต <strong>${check.nearest_forest_name}</strong> เพียง ${dist ? `${dist} เมตร` : '< 500 เมตร'} ซึ่งอยู่ในระยะกันชนที่ต้องเฝ้าระวัง`;
        }
        // มีความเสี่ยง: แสดงปุ่มหน้าถัดไปเพื่อไปขั้นตอนที่ 4 ได้
        if (btnNext) btnNext.classList.remove("hidden");
        if (btnNextLabel) btnNextLabel.innerText = "หน้าถัดไป: ออก QR Code (เฝ้าระวัง)";
      } else {
        if (caseOverlap) caseOverlap.classList.remove("hidden");
        if (statusPill) {
          statusPill.className = "px-3.5 py-1 rounded-full text-[14px] font-bold bg-rose-100 text-rose-800 border border-rose-300 shadow-xs";
          statusPill.innerHTML = "🔴 ทับซ้อนป่าสงวน (ไม่ผ่านเกณฑ์)";
        }
        const forestName = check && check.overlapping_forests && check.overlapping_forests.length > 0 ? check.overlapping_forests.join(', ') : (check?.nearest_forest_name || 'เขตป่าสงวนแห่งชาติ');
        const overlapForestElem = document.getElementById("modal-overlap-forest-name");
        if (overlapForestElem) {
          overlapForestElem.innerText = `⛔ ซ้อนทับ ${forestName}`;
        }
        const overlapDesc = document.getElementById("modal-overlap-desc");
        if (overlapDesc) {
          overlapDesc.innerHTML = `แปลงปลูกนี้ <strong class="underline">ตรวจพบการทับซ้อนกับแนวเขต ${forestName}</strong> ซึ่งเป็นพื้นที่คุ้มครองและไม่ผ่านเกณฑ์ EUDR`;
        }
        // ทับซ้อนป่าสงวน: ซ่อนปุ่มหน้าถัดไป (ไม่สามารถไปยังขั้นตอนที่ 4 ได้)
        if (btnNext) btnNext.classList.add("hidden");
      }
    }

    function setModalPresetMode(mode) {
      if (window.currentDrawnSpatialCheck) {
        window.currentDrawnSpatialCheck.eudr_status = mode;
        if (mode === 'compliant') {
          window.currentDrawnSpatialCheck.has_overlap = false;
          window.currentDrawnSpatialCheck.nearest_forest_distance_m = 1200;
        } else if (mode === 'under_review') {
          window.currentDrawnSpatialCheck.has_overlap = false;
          window.currentDrawnSpatialCheck.nearest_forest_distance_m = 250;
        } else {
          window.currentDrawnSpatialCheck.has_overlap = true;
        }
      }
      renderStep3SpatialResult();
    }

    function activateMapDrawFromModal() {
      App.closeModal('addPlotModal');
      const mapContainer = document.getElementById('map-view');
      if (mapContainer) {
        mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      if (GeoMap && GeoMap.drawnItems) {
        GeoMap.drawnItems.clearLayers();
      }
      setTimeout(() => {
        if (GeoMap && GeoMap.drawControl && GeoMap.drawControl._toolbars && GeoMap.drawControl._toolbars.draw) {
          GeoMap.drawControl._toolbars.draw._modes.polygon.handler.enable();
          App.showToast('เริ่มคลิกบนแผนที่ GIS เพื่อวาดขอบเขตแปลง (Polygon)', 'info');
        } else {
          App.showToast('กรุณารอโหลดเครื่องมือวาดสักครู่', 'info');
        }
      }, 300);
    }

    function confirmModalCoordsAndNext() {
      goToModalStep(2);
    }

    function goToModalStep(step) {
      // ตรวจสอบเงื่อนไข: หากแปลงทับซ้อนป่าสงวน จะไม่สามารถไปขั้นตอนที่ 4 ได้
      if (step === 4 && modalPresetMode === 'non_compliant') {
        if (typeof App !== 'undefined' && typeof App.showToast === 'function') {
          App.showToast('⚠️ แปลงนี้ทับซ้อนเขตป่าสงวน ไม่สามารถออกหนังสือรับรอง QR Code ได้ กรุณาบันทึกข้อมูลในขั้นตอนที่ 3', 'warning');
        }
        step = 3;
      }

      modalCurrentStep = step;

      // Toggle modal step views
      for (let i = 1; i <= 4; i++) {
        const el = document.getElementById("modal-step-content-" + i);
        if (el) {
          if (i === step) {
            el.classList.remove("hidden");
          } else {
            el.classList.add("hidden");
          }
        }
      }

      // Update Step Top Badges & Labels
      for (let i = 1; i <= 4; i++) {
        const badge = document.getElementById("modal-step-badge-" + i);
        const label = document.getElementById("modal-step-label-" + i);

        if (i < step) {
          // Done
          badge.className = "w-7 h-7 rounded-full bg-mezenc-brightCyan text-white flex items-center justify-center shrink-0 shadow-xs";
          badge.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
          label.className = "text-xs font-semibold text-mezenc-brightCyan group-hover:underline block";
        } else if (i === step) {
          // Active
          badge.className = "w-7 h-7 rounded-full bg-mezenc-brightCyan text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs";
          badge.innerHTML = i;
          label.className = "text-xs font-bold text-mezenc-brightCyan group-hover:underline block";
        } else {
          // Pending
          badge.className = "w-7 h-7 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-xs shadow-xs";
          badge.innerHTML = i;
          label.className = "text-xs font-semibold text-gray-400 group-hover:text-gray-600 block";
        }
      }

      // Animate indicator underline bar
      const bar = document.getElementById("modal-step-indicator-bar");
      if (bar) {
        bar.style.transform = "translateX(" + ((step - 1) * 100) + "%)";
      }

      // Update Bottom Action Buttons
      const btnPrev = document.getElementById("modal-btn-prev");
      const btnSave = document.getElementById("modal-btn-save");
      const btnSaveLabel = document.getElementById("modal-btn-save-label");
      const btnNext = document.getElementById("modal-btn-next");
      const btnNextLabel = document.getElementById("modal-btn-next-label");

      if (step === 1) {
        // ขั้นตอนที่ 1: ปุ่มบันทึกข้อมูล และ ปุ่มหน้าถัดไป (ปุ่มย้อนกลับซ่อน)
        if (btnPrev) btnPrev.classList.add("hidden");
        if (btnSave) {
          btnSave.classList.remove("hidden");
          if (btnSaveLabel) btnSaveLabel.innerText = "💾 บันทึกข้อมูล";
          btnSave.onclick = function() { submitPlotFromModal(); };
        }
        if (btnNext) btnNext.classList.remove("hidden");
        if (btnNextLabel) btnNextLabel.innerText = "หน้าถัดไป";
      } else if (step === 2) {
        // ขั้นตอนที่ 2: ปุ่มย้อนกลับ, ปุ่มบันทึกข้อมูล, และ ปุ่มหน้าถัดไป
        if (btnPrev) btnPrev.classList.remove("hidden");
        if (btnSave) {
          btnSave.classList.remove("hidden");
          if (btnSaveLabel) btnSaveLabel.innerText = "💾 บันทึกข้อมูล";
          btnSave.onclick = function() { submitPlotFromModal(); };
        }
        if (btnNext) btnNext.classList.remove("hidden");
        if (btnNextLabel) btnNextLabel.innerText = "หน้าถัดไป: ตรวจสอบ EUDR";
      } else if (step === 3) {
        // ขั้นตอนที่ 3: ปุ่มย้อนกลับ, ปุ่มบันทึกข้อมูล, และ ปุ่มหน้าถัดไป (แสดงเฉพาะกรณีปลอดภัย/เฝ้าระวัง)
        if (btnPrev) btnPrev.classList.remove("hidden");
        if (btnSave) {
          btnSave.classList.remove("hidden");
          if (btnSaveLabel) btnSaveLabel.innerText = "💾 บันทึกข้อมูล";
          btnSave.onclick = function() { submitPlotFromModal(modalPresetMode); };
        }
        renderStep3SpatialResult();
      } else if (step === 4) {
        // ขั้นตอนที่ 4: ปุ่มย้อนกลับ และ ปุ่มบันทึก (ไม่มีปุ่มหน้าถัดไป)
        if (btnPrev) btnPrev.classList.remove("hidden");
        if (btnSave) {
          btnSave.classList.remove("hidden");
          if (btnSaveLabel) btnSaveLabel.innerText = "💾 บันทึกแปลงปลูก";
          btnSave.onclick = function() { submitPlotFromModal(modalPresetMode || 'compliant'); };
        }
        if (btnNext) btnNext.classList.add("hidden");
        updateModalSummaryCard();
      }
    }

    function nextModalStep() {
      if (modalCurrentStep === 3) {
        if (modalPresetMode === 'compliant' || modalPresetMode === 'under_review') {
          goToModalStep(4);
        } else {
          // ถ้าทับซ้อนป่าสงวน ให้บันทึกข้อมูลในขั้นตอนที่ 3
          submitPlotFromModal('non_compliant');
        }
        return;
      }
      if (modalCurrentStep < 4) {
        goToModalStep(modalCurrentStep + 1);
      }
    }

    function prevModalStep() {
      if (modalCurrentStep > 1) {
        goToModalStep(modalCurrentStep - 1);
      }
    }

    function updateModalSummaryCard() {
      const farmerName = document.getElementById("form-farmer-name")?.value || "นางสาวมนัสนันท์ อนันตณรงค์";
      const plotName = document.getElementById("form-plot-name").value || "แปลงยางพารา";
      const deedType = document.getElementById("form-deed-type").value;
      const deedNo = document.getElementById("form-deed-no").value || "8401-4458";
      const coords = document.getElementById("form-centroid-display").value || "9.138240, 99.321850";

      document.getElementById("modal-sum-farmer").innerText = farmerName;
      document.getElementById("modal-sum-plot").innerText = plotName;
      document.getElementById("modal-sum-deed").innerText = `${deedType} เลขที่ ${deedNo}`;
      document.getElementById("modal-sum-coords").innerText = coords;

      // Update EUDR Summary Badge in Step 4 dynamically
      const eudrBadgeElem = document.getElementById("modal-sum-eudr-badge");
      if (eudrBadgeElem) {
        const check = window.currentDrawnSpatialCheck;
        if (check && (check.has_overlap || check.eudr_status === 'non_compliant' || modalPresetMode === 'non_compliant')) {
          eudrBadgeElem.className = "font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200 text-[16px]";
          eudrBadgeElem.innerHTML = "🔴 ไม่ผ่านเกณฑ์ (ทับซ้อนป่าสงวน)";
        } else if (check && (check.eudr_status === 'under_review' || check.nearest_forest_distance_m < 500 || modalPresetMode === 'under_review')) {
          const dist = check && check.nearest_forest_distance_m ? Math.round(check.nearest_forest_distance_m) : null;
          eudrBadgeElem.className = "font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200 text-[16px]";
          eudrBadgeElem.innerHTML = `🟠 โซนเฝ้าระวัง Buffer ${dist ? `(${dist} ม.)` : '(< 500 ม.)'}`;
        } else {
          eudrBadgeElem.className = "font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 text-[16px]";
          eudrBadgeElem.innerHTML = "🟢 ผ่านเกณฑ์ 100% (Compliant)";
        }
      }
    }

    async function submitPlotFromModal(status) {
      const geojsonStr = document.getElementById('form-geojson-geometry').value;
      const lat = parseFloat(document.getElementById('form-centroid-lat').value) || 9.138240;
      const lng = parseFloat(document.getElementById('form-centroid-lng').value) || 99.321850;

      let geom;
      if (geojsonStr) {
        geom = JSON.parse(geojsonStr);
      } else {
        geom = {
          type: "Polygon",
          coordinates: [[
            [lng, lat],
            [lng + 0.002, lat + 0.001],
            [lng + 0.003, lat - 0.001],
            [lng + 0.001, lat - 0.002],
            [lng, lat]
          ]]
        };
      }

      const check = window.currentDrawnSpatialCheck;
      let finalStatus = status;
      if (!finalStatus) {
        if (check && (check.has_overlap || check.eudr_status === 'non_compliant')) {
          finalStatus = 'non_compliant';
        } else if (check && (check.eudr_status === 'under_review' || check.nearest_forest_distance_m < 500)) {
          finalStatus = 'under_review';
        } else {
          finalStatus = check?.eudr_status || modalPresetMode || 'compliant';
        }
      }

      const farmerNameVal = document.getElementById('form-farmer-name')?.value?.trim() || 'นางสาวมนัสนันท์ อนันตณรงค์';
      const plotNameVal = document.getElementById('form-plot-name')?.value?.trim() || 'แปลงยางพาราใหม่';

      const payload = {
        farmer_name: farmerNameVal,
        plot_name: plotNameVal,
        title_deed_type: document.getElementById('form-deed-type').value,
        title_deed_no: document.getElementById('form-deed-no').value,
        rubber_clone: document.getElementById('form-rubber-clone').value,
        planting_year: parseInt(document.getElementById('form-planting-year').value) || 2018,
        tree_count: parseInt(document.getElementById('form-tree-count').value) || 500,
        tapping_status: document.getElementById('form-tapping-status').value,
        notes: document.getElementById('form-notes').value,
        eudr_status: finalStatus,
        geojson_geometry: geom
      };

      try {
        App.showToast('กำลังบันทึกข้อมูลแปลงปลูกลงฐานข้อมูล...', 'info');
        const res = await fetch('api/plots.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch (parseErr) {
          console.error('API Non-JSON Response:', rawText);
          throw new Error('การตอบกลับจากเซิร์ฟเวอร์ผิดพลาด: ' + rawText.replace(/<[^>]*>?/gm, '').trim().substring(0, 120));
        }

        if (data && (data.success || res.ok)) {
          App.showToast(`🎉 บันทึกแปลงปลูก "${payload.plot_name}" สำเร็จ!`, 'success');
          App.closeModal('addPlotModal');
          
          if (GeoMap && GeoMap.drawnItems) {
            GeoMap.drawnItems.clearLayers();
          }
          if (GeoMap && typeof GeoMap.loadRubberPlots === 'function') {
            await GeoMap.loadRubberPlots();
          }
          if (GeoMap && GeoMap.map && lat && lng) {
            try {
              GeoMap.map.flyTo([lat, lng], 16, { duration: 1.2 });
            } catch (e) {
              GeoMap.map.setView([lat, lng], 16);
            }
          }
        } else {
          alert('ไม่สามารถบันทึกข้อมูลได้: ' + (data.message || data.error || 'กรุณาลองใหม่อีกครั้ง'));
        }
      } catch (err) {
        console.error('Error saving plot:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + err.message);
      }
    }
  </script>

</body>
</html>
