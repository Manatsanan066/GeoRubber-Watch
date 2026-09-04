<?php
/**
 * GeoRubber Watch - หน้าที่ 2: หน้าแรกระบบและแผนที่ภาพรวม Web-GIS (Overview & Spatial Explorer)
 * ดีไซน์มาตรฐาน Mezenc / Eco-GIS สอดคล้องกับหน้า index.php 100%
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$current_page = 'overview.php';
$current_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['full_name'] ?? ($current_role === 'admin' ? 'รศ.ดร.สุพัตรา พุฒิเนาวรัตน์' : 'นางสาวมาทินี โรยนรินทร์');
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>แผนที่ภูมิสารสนเทศภาพรวม (Web-GIS Explorer) • GeoRubber Watch</title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
  <!-- Leaflet & Leaflet.draw CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Google Sans', 'Open Sans', 'Sarabun', 'sans-serif'],
          },
          colors: {
            mezenc: {
              teal: '#0e4d4e',
              deepTeal: '#093637',
              darkNavy: '#062627',
              mint: '#5ebbb6',
              brightCyan: '#00a699',
              lightCyan: '#e6f7f6',
              sand: '#f8faf9',
              slateText: '#1a332c',
              muted: '#6b7280'
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

    /* Hide Leaflet.draw Toolbar (ซ่อนปุ่มเครื่องมือวาด 3 ปุ่มบนแผนที่) */
    .leaflet-draw {
      display: none !important;
    }

    /* Leaflet Zoom Controls on the Right side below GPS, Pin & Fullscreen buttons (ขยับปุ่ม + และ - ไปไว้ด้านขวา) */
    .leaflet-right .leaflet-control-zoom {
      margin-top: 168px !important;
      margin-right: 16px !important;
      border-radius: 14px !important;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(14,77,78,0.18) !important;
      border: 1.5px solid #bee6e1 !important;
    }
    .leaflet-control-zoom a {
      width: 38px !important;
      height: 38px !important;
      line-height: 38px !important;
      color: #0e4d4e !important;
      font-weight: 800 !important;
      font-size: 18px !important;
      background: rgba(255, 255, 255, 0.95) !important;
      transition: all 0.2s ease !important;
    }
    .leaflet-control-zoom a:hover {
      background-color: #e6f7f6 !important;
      color: #00a699 !important;
    }

    /* Fullscreen Mode Styling for GIS Map Workspace */
    .gis-fullscreen-active {
      position: fixed !important;
      inset: 0 !important;
      z-index: 99999 !important;
      width: 100vw !important;
      height: 100vh !important;
      max-width: 100vw !important;
      max-height: 100vh !important;
      border-radius: 0 !important;
      border: none !important;
      margin: 0 !important;
      padding: 12px !important;
      box-shadow: none !important;
    }

    /* Custom Draggable Pin Marker & Bounce Animation */
    .custom-pin-marker {
      background: transparent;
      border: none;
    }
    .pin-drop-animation {
      animation: pinDrop 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes pinDrop {
      0% { transform: translateY(-30px) scale(0.6); opacity: 0; }
      70% { transform: translateY(4px) scale(1.08); }
      100% { transform: translateY(0) scale(1); opacity: 1; }
    }

    /* Pulsing GPS Location Marker */
    .custom-gps-icon {
      background: transparent;
      border: none;
    }
    .gps-pulse-marker {
      position: relative;
      width: 24px;
      height: 24px;
    }
    .gps-pulse-marker .gps-dot {
      width: 14px;
      height: 14px;
      background: #00b4d8;
      border: 2.5px solid #ffffff;
      border-radius: 50%;
      position: absolute;
      top: 5px;
      left: 5px;
      box-shadow: 0 0 10px rgba(0, 180, 216, 0.9);
      z-index: 2;
    }
    .gps-pulse-marker .gps-ring {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: rgba(0, 180, 216, 0.45);
      position: absolute;
      top: 0;
      left: 0;
      animation: gps-pulse-anim 2s infinite ease-out;
      z-index: 1;
    }
    @keyframes gps-pulse-anim {
      0% {
        transform: scale(0.5);
        opacity: 1;
      }
      100% {
        transform: scale(2.5);
        opacity: 0;
      }
    }
  </style>
</head>
<body class="bg-[#f8faf9] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION & ATMOSPHERIC NATURE BANNER (Matching index.php & map.php Hero)
       ========================================================================= -->
  <div class="relative w-full text-white overflow-hidden bg-mezenc-darkNavy min-h-[540px] sm:min-h-[600px] lg:min-h-[660px] flex flex-col justify-between pb-24 sm:pb-32 lg:pb-36">
    
    <!-- Hero Image Background with Clean Dark Overlay & Smooth Soft Fade Lowered to Bottom Edge -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="img/forest_gis_hero.jpg" alt="Surat Thani Pristine National Forest Reserve & Landscape" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='ปก.png';">
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
            drop-shadow
            truncate
          "
        >
          GeoRubber Watch
        </span>
      </div>

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
            text-mezenc-mint
            font-bold
            border-b-2
            border-mezenc-mint
            pb-0.5
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
            text-white
            hover:text-mezenc-mint
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

      <!-- NAV ICONS & MOBILE MENU BUTTON (Exact Match) -->
      <div class="flex items-center gap-2.5 sm:gap-3.5">
        <!-- USER / LOGIN BUTTON (Desktop/iPad) -->
        <button
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
          onclick="location.href='login.php'"
          title="เข้าสู่ระบบ (Login)"
        >
          <svg
            class="w-4 h-4 sm:w-5 sm:h-5 text-white"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
            >
            </path>
          </svg>
        </button>

        <!-- MOBILE / IPAD HAMBURGER MENU BUTTON -->
        <button 
          class="lg:hidden text-white w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md flex items-center justify-center border border-white/20 shadow-md transition-all active:scale-95 cursor-pointer" 
          onclick="toggleMobileDrawer()"
          aria-label="เปิดเมนูนำทาง"
        >
          <svg
            class="w-5 h-5 sm:w-6 sm:h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              d="M4 6h16M4 12h16M4 18h16"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
            >
            </path>
          </svg>
        </button>
      </div>

    </header>

    <!-- HERO CONTENT (Centered typography over landscape background) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow">
          WEB-GIS SPATIAL EXPLORER & MONITORING
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md">
          แผนที่ภูมิสารสนเทศภาพรวมและการเฝ้าระวังเชิงพื้นที่
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1 drop-shadow">
          ศูนย์กลางข้อมูลเชิงพื้นที่ 26 แนวเขตป่าสงวนแห่งชาติและแปลงปลูกยางพารา จังหวัดสุราษฎร์ธานี แสดงผลในรูปแบบ Web-GIS พร้อมระบบตรวจสอบความสอดคล้องตามมาตรฐาน EUDR (Zero Deforestation)
        </p>
      </div>
    </div>

  </div>

  <!-- =========================================================================
       [MOBILE / IPAD RESPONSIVE DRAWER OVERLAY]
       ========================================================================= -->
  <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-md hidden transition-opacity duration-300 opacity-0 lg:hidden">
    <div id="mobile-drawer-content" class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-mezenc-deepTeal text-white p-6 shadow-2xl flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-out border-l border-white/10">
      
      <div>
        <!-- Drawer Header -->
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

        <!-- Drawer Navigation Links -->
        <nav class="flex flex-col gap-2 pt-6 text-sm font-medium">
          <a href="index.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🏠</span> <span>หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
            <span>🛰️</span> <span>แผนที่ GIS</span>
          </a>
          <a href="dashboard.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📊</span> <span>แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>📍</span> <span>แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🧪</span> <span>ผลผลิต</span>
          </a>
          <a href="contact.php" onclick="toggleMobileDrawer()" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
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
       2. MAIN INTERACTIVE WEB-GIS WORKSPACE (Full Width Map with Toggleable Control Panel)
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
            <span>แผนที่แนวเขตป่าสงวนแห่งชาติ จังหวัดสุราษฎร์ธานี</span>
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

          <a 
            href="map.php" 
            class="px-4 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-[14px] sm:text-[15px] shadow-md hover:shadow-lg transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>ตรวจสอบแปลงปลูก</span>
          </a>
        </div>

      </div>

      <!-- Map Viewport (Expanded Full Width & Height) -->
      <div class="relative w-full flex-1 min-h-[550px] rounded-xl sm:rounded-2xl overflow-hidden shadow-inner border border-gray-200">
        
        <!-- Real Leaflet Map Container -->
        <div id="map-view" class="w-full h-full z-10"></div>

        <!-- =====================================================================
             2. SLIDE-OUT / FLOATING GIS LAYER CONTROL PANEL (แผงควบคุมแผนที่ - เริ่มต้นซ่อนไว้)
             ===================================================================== -->
        <div 
          id="floatingLayerPanel" 
          class="absolute top-3 bottom-3 left-3 w-[360px] sm:w-[390px] max-w-[calc(100%-24px)] bg-white/95 backdrop-blur-md rounded-2xl sm:rounded-3xl shadow-[0_20px_50px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] p-4 sm:p-5 flex flex-col overflow-y-auto space-y-4 z-[450] transition-all duration-300 ease-in-out transform -translate-x-[120%] opacity-0 pointer-events-none"
        >
          
          <!-- Panel Header with Close Button ✕ -->
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
              <h3 class="font-extrabold text-[16px] text-mezenc-teal leading-tight">แผงควบคุมแผนที่</h3>
              <span class="text-[14px] text-gray-500 font-medium block mt-0.5">Layer Control &amp; Tools</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-[13px] font-bold text-mezenc-brightCyan px-2.5 py-0.5 rounded-full bg-mezenc-lightCyan border border-[#bee6e1] shrink-0">
                GIS Toggles
              </span>
              <button 
                type="button" 
                onclick="GeoOverview.toggleLayerPanel(false)" 
                class="w-7 h-7 rounded-full bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-500 flex items-center justify-center text-xs font-bold transition-all cursor-pointer shadow-xs"
                title="ปิดแผงควบคุม"
              >
                ✕
              </button>
            </div>
          </div>

          <!-- 1.1 SEARCH BOX & QUICK JUMP DROPDOWN -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label for="forest-search-input" class="text-[15px] font-bold text-gray-700 flex items-center gap-1.5 cursor-pointer">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block"><path d="M15.7955 15.8111L21 21M18 10.5C18 14.6421 14.6421 18 10.5 18C6.35786 18 3 14.6421 3 10.5C3 6.35786 6.35786 3 10.5 3C14.6421 3 18 6.35786 18 10.5Z" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>ค้นหาป่าสงวนแห่งชาติ</span>
              </label>
              <span id="search-result-count" class="text-[14px] font-bold text-mezenc-brightCyan bg-mezenc-lightCyan px-2.5 py-0.5 rounded-lg border border-[#bee6e1]/70 shrink-0">
                26 ผืนป่า
              </span>
            </div>

            <!-- Text Search Input with Clear button -->
            <div class="relative">
              <input 
                type="text" 
                id="forest-search-input" 
                placeholder="พิมพ์รหัส เช่น R1.001 หรือชื่อเขตป่าสงวน" 
                class="w-full bg-[#f8faf9] text-gray-800 font-medium text-[15px] rounded-xl pl-9 pr-8 py-2.5 outline-none border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white transition-all shadow-xs"
                oninput="GeoOverview.filterForestList(this.value)"
                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); GeoOverview.handleSearchEnter(); }"
                autocomplete="off"
              >
              <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              <button 
                type="button" 
                id="clear-search-btn" 
                onclick="GeoOverview.clearSearch()" 
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 hidden text-[15px] font-bold w-4 h-4 rounded-full flex items-center justify-center cursor-pointer"
                title="ล้างคำค้นหา"
              >✕</button>

              <!-- Search Autocomplete Suggestions Popup -->
              <div id="forest-search-suggestions" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white rounded-2xl shadow-xl border border-mezenc-brightCyan/40 max-h-64 overflow-y-auto z-50 p-1.5 space-y-1"></div>
            </div>

            <!-- Dropdown Select 26 Forest Reserves -->
            <div class="relative">
              <select 
                id="forest-select-dropdown" 
                class="w-full bg-[#f8faf9] text-gray-800 font-medium text-[15px] rounded-xl px-3 py-2.5 outline-none border border-gray-200 focus:border-mezenc-brightCyan focus:bg-white transition-all cursor-pointer shadow-xs leading-relaxed"
                onchange="GeoOverview.zoomToForest(this.value)"
              >
                <option value="">เลือกพื้นที่เขตป่าสงวนแห่งชาติ</option>
              </select>
            </div>
          </div>

          <!-- 1.2 SELECTED FOREST INFO CARD -->
          <div id="selected-forest-info-card" class="hidden p-3 rounded-2xl bg-[#f4faf7] border border-[#bee6e1] shadow-xs text-[15px] space-y-1.5 transition-all">
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-mezenc-teal text-[15px] truncate max-w-[200px]" id="info-forest-name">ชื่อป่าสงวน</span>
              <button type="button" onclick="GeoOverview.clearSearch()" class="text-gray-400 hover:text-rose-500 text-[15px] font-bold cursor-pointer" title="ปิดกล่องข้อมูล">✕</button>
            </div>
            <div class="text-[14px] text-gray-600 space-y-0.5">
              <div><strong>รหัสพื้นที่:</strong> <span id="info-forest-code" class="font-mono text-mezenc-brightCyan font-bold text-[14px]">FR-001</span></div>
              <div><strong>เนื้อที่ประมาณ:</strong> <span id="info-forest-area" class="font-bold text-gray-800 text-[14px]">0 ไร่</span></div>
              <div><strong>ประเภท:</strong> <span id="info-forest-type" class="text-gray-700 text-[14px]">ป่าสงวนแห่งชาติ</span></div>
            </div>
            <div class="pt-1 flex gap-1.5">
              <button 
                type="button" 
                onclick="GeoOverview.recenterSelectedForest()" 
                class="flex-1 py-1.5 px-2.5 rounded-xl bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-[14px] transition-colors text-center cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
              >
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block"><path d="M15.7955 15.8111L21 21M18 10.5C18 14.6421 14.6421 18 10.5 18C6.35786 18 3 14.6421 3 10.5C3 6.35786 6.35786 3 10.5 3C14.6421 3 18 6.35786 18 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>ซูมดูภาพรวมผืนป่า</span>
              </button>
            </div>
          </div>

          <!-- 1.3 PIN LOCATION & SPATIAL RISK CHECK -->
          <div class="space-y-2">
            <div class="grid grid-cols-2 gap-2">
              <!-- Pin Tool Button -->
              <button 
                type="button" 
                id="btn-panel-pin-mode"
                onclick="GeoOverview.togglePinMode()" 
                class="py-2.5 px-3 rounded-2xl bg-white hover:bg-mezenc-lightCyan text-mezenc-teal font-bold text-[14px] border-2 border-[#bee6e1] shadow-xs hover:shadow-md hover:border-mezenc-brightCyan transition-all flex items-center justify-center gap-1.5 cursor-pointer group"
                title="คลิกปุ่มนี้แล้วคลิกบนแผนที่เพื่อปักหมุด"
              >
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block group-hover:scale-110 transition-transform"><path d="M5.7 15C4.03377 15.6353 3 16.5205 3 17.4997C3 19.4329 7.02944 21 12 21C16.9706 21 21 19.4329 21 17.4997C21 16.5205 19.9662 15.6353 18.3 15M12 9H12.01M18 9C18 13.0637 13.5 15 12 18C10.5 15 6 13.0637 6 9C6 5.68629 8.68629 3 12 3C15.3137 3 18 5.68629 18 9ZM13 9C13 9.55228 12.5523 10 12 10C11.4477 10 11 9.55228 11 9C11 8.44772 11.4477 8 12 8C12.5523 8 13 8.44772 13 9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>ปักหมุดตรวจพิกัด</span>
              </button>

              <!-- GPS Locate Button -->
              <button 
                type="button" 
                id="btn-panel-locate-me"
                onclick="GeoOverview.locateUser()" 
                class="py-2.5 px-3 rounded-2xl bg-gradient-to-r from-[#d4f1ee] to-[#e8f7f5] hover:from-mezenc-brightCyan hover:to-mezenc-teal text-mezenc-teal hover:text-white font-bold text-[14px] border-2 border-[#bee6e1] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer group"
                title="ระบุตำแหน่ง GPS ปัจจุบัน"
              >
                <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block group-hover:scale-110 transition-transform"><path d="M12,2C6.5,2,2,6.5,2,12s4.5,10,10,10s10-4.5,10-10v-1c-0.6,0.9-1.2,1.7-1.7,2.4c-0.4,2.2-1.6,4.1-3.3,5.4 c-0.1-0.3-0.2-0.7-0.3-1c-0.1-0.4-0.2-0.8-0.4-1.2c-0.1-0.4-0.2-0.8-0.4-1.1c-0.1-0.1-0.3-0.2-0.5-0.3c-0.7-0.2-1.6,0.1-2.1-0.6 c-0.2-0.3-0.2-0.6-0.1-1c0.2-0.3,0.3-0.6,0.5-0.9c0.2-0.4,0.5-0.9,0.6-1.4c-0.8-1.2-1.6-2.6-2-3.9h-0.1c-0.1,0-0.1,0-0.2-0.1 c-0.2-0.2-0.3-0.7-0.2-1c0-0.5,0.3-0.8,0.2-1.3c0-0.1-0.1-0.9-0.1-0.9c-0.3,0-0.8,0-0.7-0.5V3.5H12h0.5c0.2-0.6,0.6-1.1,0.9-1.5H12z M18,2c-2.2,0-4,1.8-4,4s4,7,4,7s4-4.8,4-7S20.2,2,18,2z M18,4.5c0.8,0,1.5,0.7,1.5,1.5S18.8,7.5,18,7.5S16.5,6.8,16.5,6 S17.2,4.5,18,4.5z M8,5.1c0.4,0,0.7,0,1,0.1s0.6,0.3,0.8,0.5s0.5,0.5,0.5,0.8c0,0.1,0,0.2-0.1,0.2C10.1,6.8,10,6.8,9.9,6.8 c-0.3,0-0.6,0-0.8-0.1C9,6.6,8.8,6.4,8.6,6.3C8.1,6.1,7.2,7.4,7.1,7.8C7,8.1,7,8.8,7.5,8.9c0.3,0,1-0.6,1.2-0.8C8.9,8,9,7.9,9.2,7.8 c0.8-0.1,1.4,0.6,1.6,1.3C11,9.9,9.6,10.5,9,10.7c-0.2,0.1-0.3-0.1-0.5,0c-0.5,0.2-1.1,0.9-1.1,1.4s-0.1,1-0.2,1.5 c-0.1,0-0.2-0.1-0.2-0.1v-0.2c0-0.3-0.1-0.6-0.4-0.8c-0.1,0-0.1-0.1-0.2-0.1c-0.3-0.1-0.6-0.4-0.9-0.1c-0.2,0.2-0.4,0.5-0.4,0.8 c0,0.1,0,0.2,0.1,0.3c0.2,0.1,0.4,0,0.6,0c0.1,0,0.2,0.2,0.3,0.3c0.2,0.3,0.3,0.8,0.7,0.8h0.7h1.3c0.3,0.1,0.8,0.2,1,0.4 c0.1,0.2,0.1,0.4,0.2,0.6c0.4,0.5,1.1,0.5,1.7,0.7c0.2,0.1,0.3,0.2,0.3,0.4c0,0.3-0.1,0.7-0.2,1s-0.2,0.7-0.4,0.9s-0.4,0.3-0.6,0.4 c-0.4,0.2-0.6,0.6-0.8,0.9c0,0-0.1,0.2-0.2,0.3c-0.8-0.2-1.5-0.5-2.2-1v-0.2c-0.1-0.4-0.2-0.7-0.3-1c-0.2-0.5-0.5-1.1-0.6-1.6 c0-0.5,0.1-1-0.2-1.4c-0.3-0.5-1.1-0.5-1.6-0.8c-0.4-0.4-0.9-0.8-1.3-1.3V12c0-2.7,1.3-5.1,3.3-6.7C7.3,5.2,7.6,5.1,8,5.1z"/></svg>
                <span>ตำแหน่งฉัน (GPS)</span>
              </button>
            </div>

            <!-- Pin Result Card -->
            <div id="pin-status-card" class="hidden p-3.5 rounded-2xl border text-[14px] shadow-xs transition-all bg-[#f8faf9] border-gray-200 space-y-2">
              <div class="flex items-center justify-between">
                <span class="font-extrabold text-mezenc-teal text-[14px] flex items-center gap-1.5">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block"><path d="M5.7 15C4.03377 15.6353 3 16.5205 3 17.4997C3 19.4329 7.02944 21 12 21C16.9706 21 21 19.4329 21 17.4997C21 16.5205 19.9662 15.6353 18.3 15M12 9H12.01M18 9C18 13.0637 13.5 15 12 18C10.5 15 6 13.0637 6 9C6 5.68629 8.68629 3 12 3C15.3137 3 18 5.68629 18 9ZM13 9C13 9.55228 12.5523 10 12 10C11.4477 10 11 9.55228 11 9C11 8.44772 11.4477 8 12 8C12.5523 8 13 8.44772 13 9Z" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                  <span>ผลการปักหมุดตรวจสอบ</span>
                </span>
                <button type="button" onclick="GeoOverview.clearPin()" class="text-gray-400 hover:text-rose-500 text-xs font-bold px-1.5 py-0.5 rounded-md hover:bg-rose-50 cursor-pointer" title="ลบหมุด">ลบหมุด ✕</button>
              </div>
              <div id="pin-status-text" class="leading-relaxed text-[13px] space-y-1">
                <!-- Dynamic Content -->
              </div>
            </div>

            <!-- GPS Status Result Box -->
            <div id="gps-status-card" class="hidden p-3 rounded-2xl border text-[14px] shadow-xs transition-all bg-[#f8faf9] border-gray-200">
              <div id="gps-status-text" class="leading-relaxed text-[14px]">
                กำลังค้นหาพิกัด GPS...
              </div>
            </div>
          </div>

          <!-- 1.4 BASEMAP SELECT -->
          <div>
            <label for="basemap-select" class="block text-[15px] font-bold text-gray-700 mb-1.5 flex items-center gap-1.5">
              <svg fill="#00a896" viewBox="0 0 32 32" class="w-4 h-4 shrink-0 inline-block" xmlns="http://www.w3.org/2000/svg" stroke="#00a896"><title>satellite</title><path d="M22.81 23.318l-0.126-0.188 1.107-0.704-2.953-4.646-1.214 0.772-0.521-0.779c0.817-0.727 1.372-1.742 1.504-2.887l4.052 5.901 6.244-1.885-11.090-16.151-6.244 1.885 3.628 5.283c-0.363-0.093-0.743-0.142-1.134-0.142-1.915 0-3.555 1.177-4.237 2.847l-4.095-5.963-6.244 1.885 11.090 16.151 6.244-1.885-2.667-3.884c0.888-0.017 1.714-0.288 2.409-0.742l0.488 0.73-1.026 0.652 2.953 4.646 1.133-0.72 0.12 0.18c-1.012 0.736-1.72 1.902-1.94 3.227l6.030-3.773c-1.3-0.448-2.513-0.32-3.511 0.19zM25.79 26.619c0 0.638-0.517 1.155-1.155 1.155s-1.155-0.517-1.155-1.155c0-0.638 0.517-1.155 1.155-1.155s1.155 0.517 1.155 1.155z"></path></svg>
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

          <!-- 1.5 OVERLAYS SECTION -->
          <div class="space-y-2">
            <div class="text-[15px] font-bold text-gray-700 flex items-center gap-1.5">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 inline-block"><path d="M17.4 10L21 12L17.4 14M17.4 10L12 13L6.6 10M17.4 10L21 8L12 3L3 8L6.6 10M6.6 10L3 12L6.6 14M17.4 14L21 16L12 21L3 16L6.6 14M17.4 14L12 17L6.6 14" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
              <span>เปิด/ปิดชั้นข้อมูล</span>
            </div>

            <!-- Toggle Row 1: Forest Reserves -->
            <div 
              class="flex items-center justify-between p-3 rounded-2xl bg-[#f4faf7] border border-[#e2ece7] cursor-pointer hover:bg-[#eef8f4] hover:border-mezenc-brightCyan/40 transition-all shadow-xs" 
              onclick="GeoMap.toggleForestLayer()"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 flex items-center justify-center shrink-0">
                  <svg class="w-5 h-5" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#e32400" d="M346.483,226.653c-58.176-75.765-90.498-181.813-90.498-181.813s-32.318,106.048-90.505,181.813 c0,0,26.66,16.09,41.21,7.569c0,0-14.55,65.341-79.995,151.514c58.176,18.923,101.81-12.328,101.81-12.328v93.75h21.025h12.916 h21.021v-93.75c0,0,43.642,31.25,101.817,12.328c-65.457-86.174-79.995-151.514-79.995-151.514 C319.826,242.743,346.483,226.653,346.483,226.653z"></path>
                    <path fill="#e32400" d="M160.886,307.087c-19.185-35.761-24.363-59.015-24.363-59.015c8.768,5.141,23.33-1.454,29.058-4.376 c1.522-0.84,2.417-1.379,2.417-1.379c-5.313-6.985-10.353-14.276-15.186-21.718c-34.855-54.482-53.972-117.26-53.972-117.26 s-24.711,81.041-69.23,138.977c0,0,20.361,12.283,31.542,5.756c0,0-11.181,49.956-61.151,115.88 c44.451,14.426,77.788-9.443,77.788-9.443v71.674h42.034v-71.674c0,0,3.035,2.151,8.415,4.759 C141.633,340.391,152.332,322.817,160.886,307.087z"></path>
                    <path fill="#e32400" d="M450.849,248.071c11.121,6.527,31.474-5.756,31.474-5.756c-44.454-57.936-69.155-138.977-69.155-138.977 s-19.125,62.778-54.05,117.26c-4.766,7.441-9.803,14.733-15.123,21.718c0,0,0.906,0.54,2.428,1.379 c5.725,2.922,20.29,9.517,29.058,4.376c0,0-5.178,23.328-24.442,59.09c8.566,15.655,19.331,33.303,32.723,52.106 c5.381-2.608,8.423-4.759,8.423-4.759v71.674h41.967v-71.674c0,0,33.394,23.869,77.848,9.443 C461.97,298.027,450.849,248.071,450.849,248.071z"></path>
                  </svg>
                </div>
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
          </div>

          <!-- 1.6 STATUS LEGEND GUIDE -->
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

        <!-- Floating Map Overlay: ☰ Hamburger Toggle Button (Top-Left of Map - Opposite to GPS) -->
        <div class="absolute top-4 left-4 z-[400] flex flex-col gap-2">
          <button 
            type="button" 
            id="btn-toggle-panel"
            onclick="GeoOverview.toggleLayerPanel()" 
            title="เปิด/ปิด แผงควบคุมแผนที่ (Layer Control & Tools)" 
            class="w-11 h-11 rounded-2xl bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>

        <!-- Floating Map Overlay: Circular Bottom-Right Action Buttons (GPS, Pin & Full Map) -->
        <div class="absolute bottom-5 right-4 z-[400] flex flex-col gap-2.5">
          <!-- GPS Locate Button -->
          <button 
            type="button" 
            id="btn-floating-locate-me"
            onclick="GeoOverview.locateUser()" 
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

          <!-- Pin Location Tool Button (ปุ่มปักหมุดตรวจสอบพิกัด) -->
          <button 
            type="button" 
            id="btn-floating-pin-mode"
            onclick="GeoOverview.togglePinMode()" 
            title="ปักหมุดบนแผนที่เพื่อตรวจสอบการทับซ้อนเขตป่าสงวน (Pin Location Tool)" 
            class="w-11 h-11 rounded-full bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 transition-transform group-hover:scale-110">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="currentColor"/>
            </svg>
          </button>

          <!-- Fullscreen / Full Map Toggle Button -->
          <button 
            type="button" 
            id="btn-floating-fullscreen"
            onclick="GeoOverview.toggleFullscreen()" 
            title="ขยายแผนที่เต็มหน้าจอ (Full Map / Fullscreen)" 
            class="w-11 h-11 rounded-full bg-white/95 backdrop-blur-md shadow-[0_10px_25px_-5px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:bg-mezenc-teal text-mezenc-teal hover:text-white flex items-center justify-center transition-all duration-300 cursor-pointer active:scale-90 group"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 transition-transform group-hover:scale-110">
              <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
            </svg>
          </button>
        </div>

      </div>

    </div>

  </main>

  <!-- =========================================================================
       5. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี)
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
          <p class="text-xs text-white/75 leading-relaxed font-light">
            ระบบบริการสารสนเทศภูมิศาสตร์เพื่อการตรวจสอบย้อนกลับและประเมินความสอดคล้องตามกฎหมายว่าด้วยสินค้าที่ปลอดจากการตัดไม้ทำลายป่าของสหภาพยุโรป (EUDR)
          </p>
          <div class="text-[11px] text-white/60 pt-1">
            สาขาวิทยาศาสตร์และเทคโนโลยี คณะศิลปศาสตร์และวิทยาการจัดการ<br>
            มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี
          </div>
        </div>

        <!-- คอลัมน์ที่ 2: ข้อมูลผู้พัฒนาและอาจารย์ที่ปรึกษา -->
        <div class="md:col-span-5 space-y-1.5 text-xs text-white/85 leading-relaxed">
          <div class="font-bold text-mezenc-mint uppercase">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[11px] text-white/75">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[11px] text-white/90 space-y-0.5">
            <div>👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div>🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div>✉️ <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a>, <a href="mailto:6640011066@psu.ac.th" class="hover:text-mezenc-mint underline">6640011066@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[10px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1">🗺️</div>
            <div class="text-xs font-bold text-white">26 ผืนป่าสงวน (Zone C) • 3,643,595 ไร่</div>
            <div class="text-[10px] text-white/70 mt-1 font-light">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- แถบล่างสุด (Copyright Bar) -->
      <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-[11px] text-white/60 gap-4">
        <div>&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>

    </div>
  </footer>

  <!-- =========================================================================
       SCRIPTS: Leaflet, Leaflet.draw, Turf & Map JS Engine
       ========================================================================= -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
  <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
  <script src="assets/js/map.js?v=<?= time() ?>"></script>

  <script>
    /**
     * GeoOverview Controller
     * Handles Search Box, Forest Dropdown Jump, and GPS Real-time EUDR Check
     */
    const GeoOverview = {
      forestFeatures: [],
      selectedForestFeature: null,
      selectedHighlightLayer: null,
      userGpsMarker: null,
      userAccuracyCircle: null,
      userPinMarker: null,
      userPinBufferCircle: null,
      isPinMode: false,
      isFullscreen: false,

      async init() {
        await this.loadForestList();
        this.bindFullscreenEvents();
        this.bindMapEvents();
        this.bindOutsideClickEvents();
      },

      bindOutsideClickEvents() {
        document.addEventListener('click', (e) => {
          if (!e.target.closest('#forest-search-input') && !e.target.closest('#forest-search-suggestions')) {
            const el = document.getElementById('forest-search-suggestions');
            if (el) el.classList.add('hidden');
          }
        });
      },

      bindMapEvents() {
        const attachClick = () => {
          if (GeoMap && GeoMap.map) {
            GeoMap.map.on('click', (e) => {
              if (this.isPinMode) {
                this.onMapClick(e);
              }
            });
            if (GeoMap.forestLayerGroup) {
              GeoMap.forestLayerGroup.on('click', (e) => {
                if (this.isPinMode) {
                  if (e.originalEvent) e.originalEvent.stopPropagation();
                  this.onMapClick(e);
                }
              });
            }
          }
        };
        if (GeoMap && GeoMap.map) {
          attachClick();
        } else {
          setTimeout(attachClick, 500);
          setTimeout(attachClick, 1500);
        }
      },

      bindFullscreenEvents() {
        // Handle ESC key or system fullscreen exit
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && this.isFullscreen) {
            this.toggleFullscreen(false);
          }
        });

        document.addEventListener('fullscreenchange', () => {
          if (!document.fullscreenElement && this.isFullscreen) {
            this.toggleFullscreen(false, true);
          }
        });
      },

      // Toggle Fullscreen / Full Map View (ขยายแผนที่เต็มหน้าจอ)
      toggleFullscreen(forceState = null, skipApi = false) {
        const container = document.getElementById('gis-map-card-container');
        const btnFloat = document.getElementById('btn-floating-fullscreen');
        const btnToolbar = document.getElementById('btn-toolbar-fullscreen');
        if (!container) return;

        const shouldBeFullscreen = forceState !== null ? forceState : !this.isFullscreen;
        this.isFullscreen = shouldBeFullscreen;

        if (this.isFullscreen) {
          container.classList.add('gis-fullscreen-active');
          document.body.classList.add('overflow-hidden');

          if (!skipApi) {
            try {
              if (container.requestFullscreen) {
                container.requestFullscreen().catch(() => {});
              } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
              }
            } catch(e) {}
          }

          if (btnFloat) {
            btnFloat.title = 'ย่อแผนที่กลับขนาดปกติ (Exit Fullscreen - กด ESC)';
            btnFloat.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnFloat.classList.remove('bg-white/95', 'text-mezenc-teal');
            btnFloat.innerHTML = `
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 transition-transform group-hover:scale-110">
                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path>
              </svg>
            `;
          }

          if (btnToolbar) {
            btnToolbar.innerHTML = '<span class="text-sm">🗗</span> <span>ย่อขนาดปกติ</span>';
            btnToolbar.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnToolbar.classList.remove('bg-white', 'text-mezenc-teal');
          }
        } else {
          container.classList.remove('gis-fullscreen-active');
          document.body.classList.remove('overflow-hidden');

          if (!skipApi) {
            try {
              if (document.fullscreenElement || document.webkitFullscreenElement) {
                if (document.exitFullscreen) {
                  document.exitFullscreen().catch(() => {});
                } else if (document.webkitExitFullscreen) {
                  document.webkitExitFullscreen();
                }
              }
            } catch(e) {}
          }

          if (btnFloat) {
            btnFloat.title = 'ขยายแผนที่เต็มหน้าจอ (Full Map / Fullscreen)';
            btnFloat.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnFloat.classList.add('bg-white/95', 'text-mezenc-teal');
            btnFloat.innerHTML = `
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 transition-transform group-hover:scale-110">
                <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"></path>
              </svg>
            `;
          }

          if (btnToolbar) {
            btnToolbar.innerHTML = '<span class="text-sm">⛶</span> <span>ขยายเต็มจอ</span>';
            btnToolbar.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnToolbar.classList.add('bg-white', 'text-mezenc-teal');
          }
        }

        // Invalidate Leaflet Map Size so all tiles re-render crisp
        setTimeout(() => {
          if (GeoMap && GeoMap.map) {
            GeoMap.map.invalidateSize();
          }
        }, 150);
        setTimeout(() => {
          if (GeoMap && GeoMap.map) {
            GeoMap.map.invalidateSize();
          }
        }, 350);
      },

      // Toggle Layer Control Panel (Slide-out / Floating Panel)
      toggleLayerPanel(forceState = null) {
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
            btn.classList.remove('bg-white', 'text-mezenc-teal');
          }
        } else {
          panel.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
          panel.classList.add('-translate-x-[120%]', 'opacity-0', 'pointer-events-none');
          if (btn) {
            btn.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btn.classList.add('bg-white', 'text-mezenc-teal');
          }
        }

        setTimeout(() => {
          if (GeoMap && GeoMap.map) {
            GeoMap.map.invalidateSize();
          }
        }, 320);
      },

      // 1. Fetch and populate 26 forest reserves
      async loadForestList() {
        try {
          const res = await fetch('api/forests.php');
          const data = await res.json();
          this.forestFeatures = data.features || [];
          this.populateDropdown(this.forestFeatures);
        } catch (e) {
          console.error('Error fetching forest list:', e);
        }
      },

      populateDropdown(features) {
        const select = document.getElementById('forest-select-dropdown');
        if (!select) return;

        select.innerHTML = '<option value="">เลือกพื้นที่เขตป่าสงวนแห่งชาติ</option>';

        // Sort by forest code (R1.001 to R1.026)
        const sorted = [...features].sort((a, b) => (a.properties.forest_code || '').localeCompare(b.properties.forest_code || ''));

        sorted.forEach((f) => {
          const p = f.properties;
          const areaStr = p.area_rai ? ` (${parseFloat(p.area_rai).toLocaleString()} ไร่)` : '';
          const opt = document.createElement('option');
          opt.value = p.forest_code || p.id;
          opt.textContent = `${p.forest_code} - ${p.name_th}${areaStr}`;
          select.appendChild(opt);
        });

        const countBadge = document.getElementById('search-result-count');
        if (countBadge) {
          countBadge.textContent = `${features.length} ผืนป่า`;
        }
      },

      // 2. Filter forest list by Forest Name (TH/EN) or Forest Code (e.g. R1.001)
      filterForestList(query) {
        const q = (query || '').trim().toLowerCase();
        const clearBtn = document.getElementById('clear-search-btn');
        if (clearBtn) {
          clearBtn.style.display = q ? 'flex' : 'none';
        }

        if (!q) {
          this.populateDropdown(this.forestFeatures);
          this.renderSuggestions([]);
          return;
        }

        const filtered = this.forestFeatures.filter(f => {
          const p = f.properties;
          const nameTh = (p.name_th || '').toLowerCase();
          const nameEn = (p.name_en || '').toLowerCase();
          const code = (p.forest_code || '').toLowerCase();
          const cat = (p.category || '').toLowerCase();
          // Support searching by name, code (R1.001), or number (001)
          const cleanQ = q.replace(/[^0-9a-zก-๙]/g, '');
          const cleanCode = code.replace(/[^0-9a-z]/g, '');
          return nameTh.includes(q) || 
                 nameEn.includes(q) || 
                 code.includes(q) || 
                 cat.includes(q) ||
                 (cleanQ && cleanCode.includes(cleanQ));
        });

        this.populateDropdown(filtered);
        this.renderSuggestions(filtered, q);

        const countBadge = document.getElementById('search-result-count');
        if (countBadge) {
          countBadge.textContent = `${filtered.length} ผืนป่า`;
        }

        // Auto zoom if exactly 1 match found
        if (filtered.length === 1) {
          const targetCode = filtered[0].properties.forest_code || filtered[0].properties.id;
          const select = document.getElementById('forest-select-dropdown');
          if (select) select.value = targetCode;
        }
      },

      renderSuggestions(features, query = '') {
        const container = document.getElementById('forest-search-suggestions');
        if (!container) return;

        if (!features || features.length === 0 || !query) {
          container.classList.add('hidden');
          container.innerHTML = '';
          return;
        }

        let html = '';
        features.slice(0, 8).forEach(f => {
          const p = f.properties;
          const targetCode = p.forest_code || p.id;
          html += `
            <div 
              class="flex items-center justify-between p-2 hover:bg-[#e6f7f6] rounded-xl cursor-pointer transition-all text-left group"
              onclick="GeoOverview.selectForestFromSearch('${targetCode}')"
            >
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-base shrink-0">🌲</span>
                <div class="truncate">
                  <div class="font-extrabold text-gray-800 text-[13px] group-hover:text-mezenc-brightCyan truncate">
                    ${p.name_th}
                  </div>
                  <div class="text-[11px] text-gray-400 font-mono">
                    ${p.name_en || p.category || 'Zone-C ป่าสงวนแห่งชาติ'}
                  </div>
                </div>
              </div>
              <div class="text-right shrink-0 pl-2">
                <span class="px-2 py-0.5 rounded-md bg-mezenc-lightCyan text-mezenc-teal font-mono font-bold text-[11px] border border-[#bee6e1]">
                  ${p.forest_code}
                </span>
                <div class="text-[10px] text-gray-500 mt-0.5">
                  ${parseFloat(p.area_rai || 0).toLocaleString()} ไร่
                </div>
              </div>
            </div>
          `;
        });

        container.innerHTML = html;
        container.classList.remove('hidden');
      },

      selectForestFromSearch(code) {
        const input = document.getElementById('forest-search-input');
        const select = document.getElementById('forest-select-dropdown');
        const feat = this.forestFeatures.find(f => (f.properties.forest_code == code || f.properties.id == code));
        
        if (feat && input) {
          input.value = feat.properties.name_th;
        }
        if (select) select.value = code;
        this.renderSuggestions([]);
        this.zoomToForest(code);
      },

      handleSearchEnter() {
        const input = document.getElementById('forest-search-input');
        const q = (input?.value || '').trim().toLowerCase();
        if (!q) return;

        const cleanQ = q.replace(/[^0-9a-zก-๙]/g, '');
        const matched = this.forestFeatures.find(f => {
          const p = f.properties;
          const nameTh = (p.name_th || '').toLowerCase();
          const nameEn = (p.name_en || '').toLowerCase();
          const code = (p.forest_code || '').toLowerCase();
          const cleanCode = code.replace(/[^0-9a-z]/g, '');
          return code === q || nameTh === q || code.includes(q) || nameTh.includes(q) || nameEn.includes(q) || (cleanQ && cleanCode.includes(cleanQ));
        });

        if (matched) {
          const targetCode = matched.properties.forest_code || matched.properties.id;
          this.selectForestFromSearch(targetCode);
        }
      },

      clearSearch() {
        const input = document.getElementById('forest-search-input');
        if (input) input.value = '';
        const select = document.getElementById('forest-select-dropdown');
        if (select) select.value = '';
        const clearBtn = document.getElementById('clear-search-btn');
        if (clearBtn) clearBtn.style.display = 'none';

        this.renderSuggestions([]);
        this.populateDropdown(this.forestFeatures);
        this.hideForestInfoCard();

        if (this.selectedHighlightLayer && GeoMap.map) {
          GeoMap.map.removeLayer(this.selectedHighlightLayer);
          this.selectedHighlightLayer = null;
        }

        if (GeoMap.map) {
          GeoMap.map.flyTo([9.0805, 99.3515], 10, { duration: 1.2 });
        }
      },

      // 3. Zoom directly to selected Forest Reserve
      zoomToForest(forestCodeOrId) {
        if (!forestCodeOrId || !GeoMap.map) {
          this.hideForestInfoCard();
          return;
        }

        const feat = this.forestFeatures.find(f => (f.properties.forest_code == forestCodeOrId || f.properties.id == forestCodeOrId));
        if (!feat) return;

        this.selectedForestFeature = feat;

        // Ensure forest layer group is active
        if (!GeoMap.isForestVisible) {
          GeoMap.toggleForestLayer(true);
        }

        // Calculate polygon bounds
        const tempLayer = L.geoJSON(feat);
        const bounds = tempLayer.getBounds();

        // Highlight selected forest boundary
        if (this.selectedHighlightLayer) {
          GeoMap.map.removeLayer(this.selectedHighlightLayer);
        }

        this.selectedHighlightLayer = L.geoJSON(feat, {
          style: {
            color: '#00b4d8',
            fillColor: '#00b4d8',
            fillOpacity: 0.35,
            weight: 3.5,
            dashArray: '0'
          }
        }).addTo(GeoMap.map);

        // Smooth fly to forest boundary
        GeoMap.map.flyToBounds(bounds, {
          padding: [30, 30],
          maxZoom: 13,
          duration: 1.3
        });

        // Show Info Card in Left Panel
        this.showForestInfoCard(feat.properties);

        // Open Popup with complete metadata
        setTimeout(() => {
          const center = bounds.getCenter();
          const p = feat.properties;
          L.popup({ autoClose: true, closeOnClick: true })
            .setLatLng(center)
            .setContent(`
              <div style="min-width: 260px; padding: 4px; font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;">
                <div style="font-weight: 800; color: #0e4d4e; font-size: 18px; margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
                  <svg style="width: 20px; height: 20px; flex-shrink: 0;" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="#e32400" d="M346.483,226.653c-58.176-75.765-90.498-181.813-90.498-181.813s-32.318,106.048-90.505,181.813 c0,0,26.66,16.09,41.21,7.569c0,0-14.55,65.341-79.995,151.514c58.176,18.923,101.81-12.328,101.81-12.328v93.75h21.025h12.916 h21.021v-93.75c0,0,43.642,31.25,101.817,12.328c-65.457-86.174-79.995-151.514-79.995-151.514 C319.826,242.743,346.483,226.653,346.483,226.653z"></path><path fill="#e32400" d="M160.886,307.087c-19.185-35.761-24.363-59.015-24.363-59.015c8.768,5.141,23.33-1.454,29.058-4.376 c1.522-0.84,2.417-1.379,2.417-1.379c-5.313-6.985-10.353-14.276-15.186-21.718c-34.855-54.482-53.972-117.26-53.972-117.26 s-24.711,81.041-69.23,138.977c0,0,20.361,12.283,31.542,5.756c0,0-11.181,49.956-61.151,115.88 c44.451,14.426,77.788-9.443,77.788-9.443v71.674h42.034v-71.674c0,0,3.035,2.151,8.415,4.759 C141.633,340.391,152.332,322.817,160.886,307.087z"></path><path fill="#e32400" d="M450.849,248.071c11.121,6.527,31.474-5.756,31.474-5.756c-44.454-57.936-69.155-138.977-69.155-138.977 s-19.125,62.778-54.05,117.26c-4.766,7.441-9.803,14.733-15.123,21.718c0,0,0.906,0.54,2.428,1.379 c5.725,2.922,20.29,9.517,29.058,4.376c0,0-5.178,23.328-24.442,59.09c8.566,15.655,19.331,33.303,32.723,52.106 c5.381-2.608,8.423-4.759,8.423-4.759v71.674h41.967v-71.674c0,0,33.394,23.869,77.848,9.443 C461.97,298.027,450.849,248.071,450.849,248.071z"></path></svg>
                  <span>${p.name_th}</span>
                </div>
                <div style="font-size: 16px; color: #64748b; margin-bottom: 8px;">
                  รหัส: <span style="font-family: monospace; font-weight:700; color:#00b4d8; font-size:16px;">${p.forest_code}</span> | ${p.category || 'ป่าสงวนแห่งชาติ'}
                </div>
                <div style="background: #f4faf7; border: 1.5px solid #bee6e1; padding: 8px 10px; border-radius: 10px; font-size: 16px; line-height: 1.6; margin-bottom: 8px; display: flex; flex-direction: column; gap: 4px;">
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" viewBox="0 0 32 32" fill="none"><polygon points="3,3 3,27 29,27" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polygon><polygon points="8,15 8,22 16,22" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polygon><polygon points="29,13.7 29,16.5 26.2,16.5 15.6,5.9 18.4,3.1" stroke="#00a896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></polygon></svg>
                    <span><strong>เนื้อที่คุ้มครอง:</strong> <span style="font-weight:700; color:#0e4d4e; font-size:16px;">${parseFloat(p.area_rai || 0).toLocaleString()} ไร่</span></span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <svg style="width: 16px; height: 16px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 16.0156C19.2447 16.5445 20 17.2392 20 18C20 19.6568 16.4183 21 12 21C7.58172 21 4 19.6568 4 18C4 17.2392 4.75527 16.5445 6 16.0156" stroke="#00a896" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M17 8.44444C17 11.5372 12 17 12 17C12 17 7 11.5372 7 8.44444C7 5.35165 9.23858 3 12 3C14.7614 3 17 5.35165 17 8.44444Z" stroke="#00a896" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><circle cx="12" cy="8" r="1" stroke="#00a896" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></circle></svg>
                    <span><strong>พิกัดศูนย์กลาง:</strong> ${center.lat.toFixed(4)}, ${center.lng.toFixed(4)}</span>
                  </div>
                </div>
                <div style="font-size: 16px; background: #fee2e2; color: #b91c1c; border: 1.5px solid #fca5a5; padding: 6px 10px; border-radius: 8px; font-weight: 700; line-height: 1.4;">
                  ⚠️ เขตป่าเพื่อการอนุรักษ์ (Zone C) - ห้ามบุกรุกตามเกณฑ์ EUDR
                </div>
              </div>
            `)
            .openOn(GeoMap.map);
        }, 1350);
      },

      recenterSelectedForest() {
        if (this.selectedForestFeature) {
          const tempLayer = L.geoJSON(this.selectedForestFeature);
          GeoMap.map.flyToBounds(tempLayer.getBounds(), { padding: [30, 30], maxZoom: 13, duration: 1.2 });
        }
      },

      showForestInfoCard(props) {
        const card = document.getElementById('selected-forest-info-card');
        if (!card) return;

        document.getElementById('info-forest-name').textContent = props.name_th;
        document.getElementById('info-forest-code').textContent = props.forest_code;
        document.getElementById('info-forest-area').textContent = `${parseFloat(props.area_rai || 0).toLocaleString()} ไร่`;
        document.getElementById('info-forest-type').textContent = props.category || 'ป่าสงวนแห่งชาติ';

        card.classList.remove('hidden');
      },

      hideForestInfoCard() {
        const card = document.getElementById('selected-forest-info-card');
        if (card) card.classList.add('hidden');
      },

      // 4. GPS Real-time Locate Me & Spatial EUDR Risk Analysis
      locateUser() {
        if (!navigator.geolocation) {
          alert('เบราว์เซอร์หรืออุปกรณ์ของคุณไม่รองรับการระบุตำแหน่งพิกัด GPS');
          return;
        }

        const floatBtn = document.getElementById('btn-floating-locate-me');
        const panelBtn = document.getElementById('btn-panel-locate-me');
        if (floatBtn) floatBtn.classList.add('animate-pulse', 'text-mezenc-brightCyan');
        if (panelBtn) panelBtn.classList.add('animate-pulse');

        const gpsStatusBox = document.getElementById('gps-status-card');
        const gpsStatusText = document.getElementById('gps-status-text');
        if (gpsStatusBox) {
          gpsStatusBox.className = 'p-3.5 rounded-2xl border text-[14px] shadow-xs transition-all bg-[#f0fdfa] border-mezenc-brightCyan text-mezenc-teal';
          gpsStatusBox.classList.remove('hidden');
          if (gpsStatusText) {
            gpsStatusText.innerHTML = '<div class="flex items-center gap-2 text-[14px]"><span class="animate-spin inline-block text-base">⏳</span> <span>กำลังเชื่อมต่อดาวเทียมและค้นหาพิกัด GPS...</span></div>';
          }
        }

        navigator.geolocation.getCurrentPosition(
          (position) => {
            if (floatBtn) floatBtn.classList.remove('animate-pulse', 'text-mezenc-brightCyan');
            if (panelBtn) panelBtn.classList.remove('animate-pulse');

            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy || 25;

            // Remove previous GPS markers
            if (this.userGpsMarker && GeoMap.map) GeoMap.map.removeLayer(this.userGpsMarker);
            if (this.userAccuracyCircle && GeoMap.map) GeoMap.map.removeLayer(this.userAccuracyCircle);

            // Custom Pulsing GPS DivIcon
            const gpsIcon = L.divIcon({
              className: 'custom-gps-icon',
              html: '<div class="gps-pulse-marker"><div class="gps-ring"></div><div class="gps-dot"></div></div>',
              iconSize: [24, 24],
              iconAnchor: [12, 12]
            });

            this.userGpsMarker = L.marker([lat, lng], { icon: gpsIcon, zIndexOffset: 1000 }).addTo(GeoMap.map);
            this.userAccuracyCircle = L.circle([lat, lng], {
              radius: Math.max(accuracy, 20),
              color: '#00b4d8',
              fillColor: '#00b4d8',
              fillOpacity: 0.15,
              weight: 1.5,
              dashArray: '4, 4'
            }).addTo(GeoMap.map);

            GeoMap.map.flyTo([lat, lng], 16, { duration: 1.4 });

            // Perform Spatial Proximity & Overlap Check with Turf.js
            this.analyzeUserLocation(lat, lng, accuracy);
          },
          (error) => {
            if (floatBtn) floatBtn.classList.remove('animate-pulse', 'text-mezenc-brightCyan');
            if (panelBtn) panelBtn.classList.remove('animate-pulse');

            let msg = 'ไม่สามารถดึงข้อมูลตำแหน่ง GPS ได้';
            if (error.code === error.PERMISSION_DENIED) {
              msg = 'กรุณาอนุญาตการเข้าถึงตำแหน่งที่ตั้ง (Location Permission) ในเบราว์เซอร์ของคุณ';
            } else if (error.code === error.POSITION_UNAVAILABLE) {
              msg = 'สัญญาณ GPS ไม่พร้อมใช้งานในขณะนี้';
            } else if (error.code === error.TIMEOUT) {
              msg = 'หมดเวลาการค้นหาสัญญาณพิกัด GPS';
            }

            if (gpsStatusBox && gpsStatusText) {
              gpsStatusBox.className = 'p-3.5 rounded-2xl border text-[13px] shadow-xs transition-all bg-rose-50 border-rose-200 text-rose-800';
              gpsStatusText.innerHTML = `<div><strong>⚠️ เกิดข้อผิดพลาด:</strong> ${msg}</div>`;
            } else {
              alert(msg);
            }
          },
          {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
          }
        );
      },

      // Exact Spatial Proximity & Overlap Calculation Engine (Turf.js)
      calculateForestSpatialProximity(lat, lng) {
        let insideForest = null;
        let nearestForest = null;
        let minDistanceKm = Infinity;

        // Ensure forest features are loaded
        const features = (this.forestFeatures && this.forestFeatures.length > 0) 
          ? this.forestFeatures 
          : (GeoMap && GeoMap.forestData && GeoMap.forestData.features ? GeoMap.forestData.features : []);

        if (typeof turf !== 'undefined' && features.length > 0) {
          const pt = turf.point([lng, lat]);

          // Helper to calculate exact shortest distance from pt to any polygon or multipolygon boundary
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
                // Point-to-point fallback along boundary
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
            } else if (geom.type === 'LineString') {
              checkRing(geom.coordinates);
            } else if (geom.type === 'MultiLineString') {
              for (let l = 0; l < geom.coordinates.length; l++) {
                checkRing(geom.coordinates[l]);
              }
            }

            return minD;
          };

          for (let i = 0; i < features.length; i++) {
            const f = features[i];
            try {
              if (f.geometry) {
                // 1. Check if point is inside forest reserve polygon
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
                  break; // Found overlap
                }

                // 2. Calculate accurate boundary line distance
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
      },

      analyzeUserLocation(lat, lng, accuracy) {
        const result = this.calculateForestSpatialProximity(lat, lng);
        const { insideForest, nearestForest, distMeters, status } = result;

        const gpsStatusBox = document.getElementById('gps-status-card');
        const gpsStatusText = document.getElementById('gps-status-text');

        let statusTitle = '';
        let statusClass = '';
        let statusBadge = '';
        let adviceText = '';

        if (status === 'non_compliant') {
          statusTitle = `🔴 อยู่ในเขต ${insideForest.name_th}`;
          statusClass = 'bg-rose-50 border-rose-300 text-rose-800';
          statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-rose-600 text-white font-bold text-[15px]">⛔ ทับซ้อนป่าสงวน</span>';
          adviceText = `จุดพิกัดปัจจุบันของคุณตกอยู่ในแนวเขตป่าสงวนแห่งชาติ <strong>${insideForest.name_th}</strong> (รหัส: ${insideForest.forest_code}) ซึ่งเป็นพื้นที่คุ้มครอง (ห้ามบุกรุก/ตัดไม้)`;
        } else if (status === 'buffer_zone') {
          statusTitle = `🟡 โซนเฝ้าระวัง Buffer (${distMeters} ม.)`;
          statusClass = 'bg-amber-50 border-amber-300 text-amber-900';
          statusBadge = `<span class="px-2.5 py-0.5 rounded-lg bg-amber-500 text-white font-bold text-[15px]">⚠️ โซนเฝ้าระวัง Buffer (${distMeters} ม.)</span>`;
          adviceText = `อยู่นอกแนวเขตป่า แต่อยู่ในระยะกันชนใกล้กับ <strong>${nearestForest ? nearestForest.name_th : 'ป่าสงวน'}</strong> เพียง ${distMeters} เมตร (ไม่เกินระยะเฝ้าระวัง 500 ม.)`;
        } else {
          statusTitle = `🟢 ปลอดภัย ผ่านเกณฑ์ EUDR`;
          statusClass = 'bg-emerald-50 border-emerald-300 text-emerald-900';
          statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-[15px]">✅ ผ่านเกณฑ์ EUDR</span>';
          adviceText = `จุดพิกัดปัจจุบันอยู่นอกแนวเขตป่าสงวนแห่งชาติ โดยห่างจาก <strong>${nearestForest ? nearestForest.name_th : 'แนวเขตป่า'}</strong> ประมาณ ${distMeters >= 1000 ? (distMeters/1000).toFixed(2) + ' กม.' : distMeters + ' ม.'}`;
        }

        const pinSvgIcon = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; display: inline-block; vertical-align: -3px; flex-shrink: 0;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#00A896"/></svg>`;

        if (gpsStatusBox && gpsStatusText) {
          gpsStatusBox.className = `p-3.5 rounded-2xl border text-[15px] shadow-xs transition-all ${statusClass}`;
          gpsStatusText.innerHTML = `
            <div class="flex items-center justify-between mb-1 gap-2">
              <strong class="font-extrabold text-[15px] flex items-center gap-1.5">${statusTitle}</strong>
              ${statusBadge}
            </div>
            <div class="text-[14px] leading-relaxed mb-1">${adviceText}</div>
            <div class="text-[13px] opacity-75 font-mono flex items-center gap-1.5">${pinSvgIcon} <span>พิกัด: ${lat.toFixed(5)}, ${lng.toFixed(5)} (±${Math.round(accuracy)} ม.)</span></div>
          `;
        }

        // Marker Popup with custom SVG Pin Icon
        if (this.userGpsMarker) {
          this.userGpsMarker.bindPopup(`
            <div style="min-width: 250px; font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif; padding: 4px;">
              <div style="font-weight: 800; font-size: 16px; margin-bottom: 4px; color: #0e4d4e; display: flex; align-items: center; gap: 6px;">
                ${pinSvgIcon}
                <span>ตำแหน่งพิกัดปัจจุบันของคุณ (GPS)</span>
              </div>
              <div style="margin-bottom: 6px;">${statusBadge}</div>
              <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 8px 10px; border-radius: 10px; font-size: 13px; line-height: 1.5; margin-bottom: 6px;">
                ${adviceText}
              </div>
              <div style="font-size: 12px; color: #64748b; font-family: monospace; display: flex; align-items: center; gap: 4px;">
                ${pinSvgIcon}
                <span>พิกัด: ${lat.toFixed(5)}, ${lng.toFixed(5)} (±${Math.round(accuracy)} ม.)</span>
              </div>
            </div>
          `).openPopup();
        }
      },

      // 5. Pin Marker Tool & Real-Time Spatial Risk Check (ปักหมุดตรวจสอบพิกัด)
      togglePinMode(forceState = null) {
        this.isPinMode = forceState !== null ? forceState : !this.isPinMode;
        const btnFloat = document.getElementById('btn-floating-pin-mode');
        const btnPanel = document.getElementById('btn-panel-pin-mode');

        if (this.isPinMode) {
          if (btnFloat) {
            btnFloat.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnFloat.classList.remove('bg-white/95', 'text-mezenc-teal');
          }
          if (btnPanel) {
            btnPanel.classList.add('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnPanel.classList.remove('bg-white', 'text-mezenc-teal');
          }

          if (!this.userPinMarker && GeoMap && GeoMap.map) {
            const center = GeoMap.map.getCenter();
            this.placePin(center.lat, center.lng);
          }
        } else {
          if (btnFloat) {
            btnFloat.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnFloat.classList.add('bg-white/95', 'text-mezenc-teal');
          }
          if (btnPanel) {
            btnPanel.classList.remove('bg-mezenc-brightCyan', 'text-white', 'border-mezenc-brightCyan');
            btnPanel.classList.add('bg-white', 'text-mezenc-teal');
          }
        }
      },

      onMapClick(e) {
        // Drop or move pin marker on map click
        this.placePin(e.latlng.lat, e.latlng.lng);
      },

      placePin(lat, lng) {
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

        if (this.userPinMarker) {
          this.userPinMarker.setLatLng([lat, lng]);
        } else {
          this.userPinMarker = L.marker([lat, lng], {
            icon: pinIcon,
            draggable: true,
            zIndexOffset: 1200
          }).addTo(GeoMap.map);

          this.userPinMarker.on('dragend', (evt) => {
            const pos = evt.target.getLatLng();
            this.analyzePinLocation(pos.lat, pos.lng);
          });
        }

        // Draw 500m buffer circle around pin
        if (this.userPinBufferCircle) {
          this.userPinBufferCircle.setLatLng([lat, lng]);
        } else {
          this.userPinBufferCircle = L.circle([lat, lng], {
            radius: 500,
            color: '#f59e0b',
            fillColor: '#f59e0b',
            fillOpacity: 0.14,
            weight: 2,
            dashArray: '5, 5'
          }).addTo(GeoMap.map);
        }

        this.togglePinMode(true);
        this.analyzePinLocation(lat, lng);
      },

      clearPin() {
        if (this.userPinMarker && GeoMap && GeoMap.map) {
          GeoMap.map.removeLayer(this.userPinMarker);
          this.userPinMarker = null;
        }
        if (this.userPinBufferCircle && GeoMap && GeoMap.map) {
          GeoMap.map.removeLayer(this.userPinBufferCircle);
          this.userPinBufferCircle = null;
        }
        this.togglePinMode(false);
        const card = document.getElementById('pin-status-card');
        if (card) card.classList.add('hidden');
      },

      analyzePinLocation(lat, lng) {
        const result = this.calculateForestSpatialProximity(lat, lng);
        const { insideForest, nearestForest, distMeters, status } = result;

        let statusTitle = '';
        let statusClass = '';
        let statusBadge = '';
        let adviceText = '';
        let circleColor = '#059669'; // 🟢 ปลอดภัย (Green)
        let circleFill = '#10b981';

        if (status === 'non_compliant') {
          statusTitle = `🔴 ซ้อนทับเขต ${insideForest.name_th}`;
          statusClass = 'bg-red-50 border-red-300 text-red-900';
          statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-red-600 text-white font-bold text-xs inline-block">🔴 ซ้อนทับเขตป่าสงวน (ไม่ผ่านเกณฑ์)</span>';
          adviceText = `จุดพิกัดนี้ตั้งอยู่ในแนวเขตป่าสงวนแห่งชาติ <strong>${insideForest.name_th}</strong> (รหัส: ${insideForest.forest_code}) ซึ่งเป็นเขตป่าเพื่อการอนุรักษ์ (Zone C)`;
          circleColor = '#dc2626'; // 🔴 ซ้อนทับ (Red)
          circleFill = '#ef4444';
        } else if (status === 'buffer_zone') {
          statusTitle = `🟠 มีความเสี่ยง (โซนเฝ้าระวัง Buffer ${distMeters} ม.)`;
          statusClass = 'bg-orange-50 border-orange-300 text-orange-900';
          statusBadge = `<span class="px-2.5 py-0.5 rounded-lg bg-orange-500 text-white font-bold text-xs inline-block">🟠 มีความเสี่ยง (โซนเฝ้าระวัง ${distMeters} ม.)</span>`;
          adviceText = `อยู่นอกแนวเขตป่า แต่อยู่ในระยะกันชนใกล้กับ <strong>${nearestForest ? nearestForest.name_th : 'ป่าสงวน'}</strong> เพียง ${distMeters} เมตร (อยู่ในระยะเฝ้าระวังไม่เกิน 500 ม.)`;
          circleColor = '#ea580c'; // 🟠 มีความเสี่ยง (Orange)
          circleFill = '#f97316';
        } else {
          statusTitle = `🟢 ปลอดภัย ผ่านเกณฑ์ EUDR`;
          statusClass = 'bg-emerald-50 border-emerald-300 text-emerald-900';
          statusBadge = '<span class="px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white font-bold text-xs inline-block">🟢 ปลอดภัย (ผ่านเกณฑ์ EUDR)</span>';
          adviceText = `อยู่นอกแนวเขตป่าสงวนแห่งชาติ โดยห่างจาก <strong>${nearestForest ? nearestForest.name_th : 'แนวเขตป่า'}</strong> ประมาณ ${distMeters >= 1000 ? (distMeters/1000).toFixed(2) + ' กม.' : distMeters + ' ม.'}`;
          circleColor = '#059669'; // 🟢 ปลอดภัย (Green)
          circleFill = '#10b981';
        }

        // Dynamically update buffer circle color
        if (this.userPinBufferCircle) {
          this.userPinBufferCircle.setStyle({
            color: circleColor,
            fillColor: circleFill,
            fillOpacity: 0.18
          });
        }

        // Update Panel Card
        const pinCard = document.getElementById('pin-status-card');
        const pinText = document.getElementById('pin-status-text');
        if (pinCard && pinText) {
          pinCard.className = `p-3.5 rounded-2xl border text-[13px] shadow-xs transition-all ${statusClass}`;
          pinCard.classList.remove('hidden');
          pinText.innerHTML = `
            <div class="font-extrabold text-[14px] mb-1">${statusTitle}</div>
            <div class="mb-1.5 leading-relaxed">${adviceText}</div>
            <div class="font-mono text-[12px] opacity-80 pt-1.5 border-t border-current/20 flex items-center justify-between">
              <span>พิกัด: ${lat.toFixed(5)}, ${lng.toFixed(5)}</span>
              <a href="map.php?lat=${lat.toFixed(5)}&lng=${lng.toFixed(5)}" class="underline font-bold text-mezenc-teal hover:text-mezenc-brightCyan">วาดแปลงที่นี่ ➔</a>
            </div>
          `;
        }

        // Open Popup on the Pin Marker
        if (this.userPinMarker) {
          this.userPinMarker.bindPopup(`
            <div style="min-width: 270px; padding: 4px; font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <div style="font-weight: 800; color: #024959; font-size: 16px;">📍 ผลการตรวจพิกัดที่ปักหมุด</div>
              </div>
              <div style="margin-bottom: 6px;">${statusBadge}</div>
              <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 8px 10px; border-radius: 10px; font-size: 13px; line-height: 1.5; margin-bottom: 8px;">
                ${adviceText}
              </div>
              <div style="font-size: 12px; color: #64748b; font-family: monospace; display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; background: #fff; padding: 4px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <span>🌐 ${lat.toFixed(5)}, ${lng.toFixed(5)}</span>
                <span style="color: ${circleColor}; font-weight: bold;">วง Buffer 500 ม.</span>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                <a href="map.php?lat=${lat.toFixed(5)}&lng=${lng.toFixed(5)}" style="padding: 6px 10px; border-radius: 8px; background: #00A896; color: #fff; font-weight: bold; font-size: 12px; text-align: center; text-decoration: none; display: block;">
                  ✏️ วาดแปลงต่อ
                </a>
                <button type="button" onclick="GeoOverview.clearPin()" style="padding: 6px 10px; border-radius: 8px; background: #fee2e2; color: #b91c1c; font-weight: bold; font-size: 12px; text-align: center; border: 1px solid #fca5a5; cursor: pointer;">
                  🗑️ ลบหมุดนี้
                </button>
              </div>
            </div>
          `).openPopup();
        }
      }
    };

    // Initialize Web-GIS Map and Overview Controller on document ready
    document.addEventListener('DOMContentLoaded', () => {
      GeoMap.init({ isOverview: true, loadPlots: false, zoomPosition: 'topright' });
      GeoOverview.init();
    });

    // Mobile Drawer Toggle
    function toggleMobileDrawer() {
      const drawer = document.getElementById('mobile-drawer');
      const content = document.getElementById('mobile-drawer-content');
      if (!drawer || !content) return;

      if (drawer.classList.contains('hidden')) {
        drawer.classList.remove('hidden');
        drawer.offsetHeight;
        drawer.classList.remove('opacity-0');
        drawer.classList.add('opacity-100');
        content.classList.remove('translate-x-full');
        content.classList.add('translate-x-0');
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

    // Quick role switcher helper
    async function switchUserRole(role) {
      try {
        await fetch('api/auth.php?action=switch_demo_user', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ role: role })
        });
        window.location.reload();
      } catch (e) {
        window.location.reload();
      }
    }

    // Logout handler
    async function handleLogout() {
      try {
        await fetch('api/auth.php?action=logout');
        window.location.href = 'login.php';
      } catch (e) {
        window.location.href = 'login.php';
      }
    }
  </script>

</body>
</html>
