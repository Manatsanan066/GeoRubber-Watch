<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>GeoRubber Watch - ระบบภูมิสารสนเทศอัจฉริยะติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี</title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <!-- Tailwind CSS -->
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
      background-color: #ffffff;
      color: #1a332c;
      overflow-x: hidden;
      -webkit-tap-highlight-color: transparent;
    }

    /* Workflow Cards */
    .workflow-mezenc-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 20px;
      padding: 1.25rem;
      transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
      position: relative;
    }
    @media (min-width: 640px) {
      .workflow-mezenc-card {
        padding: 1.5rem;
      }
    }
    .workflow-mezenc-card:hover {
      transform: translateY(-4px);
      border-color: #00a699;
      box-shadow: 0 12px 30px rgba(14, 77, 78, 0.1);
    }

    /* Knowledge Status Cards */
    .knowledge-card {
      border-radius: 22px;
      overflow: hidden;
      background: #ffffff;
      border: 1.5px solid #e5e7eb;
      transition: all 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .knowledge-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    }
    .knowledge-card.card-red:hover {
      border-color: #ef4444;
      box-shadow: 0 20px 40px rgba(239, 68, 68, 0.15);
    }
    .knowledge-card.card-orange:hover {
      border-color: #f97316;
      box-shadow: 0 20px 40px rgba(249, 115, 22, 0.15);
    }
    .knowledge-card.card-green:hover {
      border-color: #10b981;
      box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
    }

    /* Real Forest Leaflet Map Container */
    #surat-real-territory-map {
      width: 100%;
      height: 280px;
      border-radius: 16px;
      z-index: 10;
    }
    @media (min-width: 640px) {
      #surat-real-territory-map {
        height: 320px;
        border-radius: 18px;
      }
    }
    @media (min-width: 1024px) {
      #surat-real-territory-map {
        height: 340px;
      }
    }
    @media (min-width: 1280px) {
      #surat-real-territory-map {
        height: 360px;
      }
    }
    @media (min-width: 1440px) {
      #surat-real-territory-map {
        height: 380px;
      }
    }
    .leaflet-popup-content-wrapper {
      border-radius: 14px !important;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    }
  </style>
</head>
<body class="selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION (FULL-WIDTH RECTANGULAR BANNER - RESPONSIVE IPHONE, IPAD, MAC)
       ========================================================================= -->
  <div class="relative w-full text-white overflow-hidden bg-mezenc-darkNavy min-h-[680px] sm:min-h-[760px] lg:min-h-[860px] flex flex-col justify-between pb-28 sm:pb-36 lg:pb-44">
    
    <!-- Hero Image Background with Clean Dark Overlay & Smooth Soft Fade to White at Bottom -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="ปก.png" alt="Surat Thani Landscape" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='img/pp.png';">
      <!-- Clean uniform dark overlay for crisp typography in upper hero -->
      <div class="absolute inset-0 bg-black/40"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-transparent"></div>
      
      <!-- Smooth Feathered Blur & Gradient Transition to White at the bottom edge (ขอบล่างค่อยๆ เฟดกลืนลงสู่สีขาวอย่างนุ่มนวล) -->
      <div class="absolute bottom-0 left-0 right-0 h-48 sm:h-64 md:h-80 bg-gradient-to-t from-white via-white/75 via-white/25 to-transparent pointer-events-none z-[1]"></div>
    </div>

    <!-- =========================================================================
         [HEADER NAVIGATION] - เต็มจอ เรียบหรู รองรับ iPhone, iPad, Mac
         ========================================================================= -->
    <header
      class="
        relative
        z-30
        w-full
        px-5
        py-5
        sm:px-8
        sm:py-6
        md:px-12
        xl:px-14
        md:py-7
        flex
        justify-between
        items-center
        max-w-[1440px]
        2xl:max-w-[1600px]
        mx-auto
      "
    >
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
            text-mezenc-mint
            font-bold
            border-b-2
            border-mezenc-mint
            pb-0.5
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

      <!-- NAV ICONS & MOBILE MENU BUTTON -->
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
            <a href="index.php" class="px-4 py-3 rounded-xl bg-white/15 text-white font-bold transition-colors flex items-center gap-3">
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
            <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
              <span>🧪</span> <span>ผลผลิต</span>
            </a>
            <a href="contact.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
              <span>📞</span> <span>ติดต่อเรา</span>
            </a>
          </nav>
        </div>

        <!-- Drawer Footer Action -->
        <div class="pt-6 border-t border-white/15">
          <a href="overview.php" class="w-full py-3 rounded-xl bg-mezenc-brightCyan hover:bg-mezenc-mint text-white font-bold text-center block shadow transition-all">
            เปิดแผนที่ระบบภูมิสารสนเทศ ➔
          </a>
        </div>

      </div>
    </div>

    <!-- =========================================================================
         [HERO CONTENT] - ขยายขนาดใหญ่เต็มตา สมดุลพอดี ไม่เหลือที่ว่างด้านข้างมากเกินไป
         ========================================================================= -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 my-auto py-8 sm:py-14 md:py-20 text-center w-full">

      <!-- Headline: THE FUTURE OF / SUSTAINABLE RUBBER (Grand Scale & Balanced Tracking) -->
      <h1 class="inline-block mx-auto mb-5 sm:mb-7 text-white uppercase font-sans font-black tracking-tight drop-shadow-xl text-center w-full">
        <span class="block text-4xl sm:text-6xl md:text-7xl lg:text-8xl xl:text-9xl 2xl:text-[9rem] leading-none">
          THE FUTURE OF
        </span>
        <span class="block text-lg sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-6xl font-bold tracking-[0.16em] sm:tracking-[0.24em] md:tracking-[0.28em] lg:tracking-[0.32em] mt-3 sm:mt-5 text-white">
          SUSTAINABLE RUBBER
        </span>
      </h1>

      <!-- Subtitle ภาษาไทย (ขนาดเล็กลงและจัดวางให้อยู่ใน 1 บรรทัดพอดี) -->
      <p class="text-xs sm:text-sm md:text-base lg:text-[1.1rem] text-slate-100/95 font-normal max-w-6xl mx-auto mb-6 sm:mb-8 md:mb-10 leading-normal drop-shadow whitespace-normal md:whitespace-nowrap tracking-wide">
        ยกระดับการจัดการสวนยางพาราด้วยเทคโนโลยี GIS และดาวเทียม เพื่อความยั่งยืนและการปฏิบัติตามมาตรฐาน EUDR อย่างครบวงจร
      </p>

      <!-- =========================================================================
           [FROSTED GLASS SEARCH BAR] - ขนาดพอดี มินิมอล ไม่เด่นแย่งสายตา
           ========================================================================= -->
      <div class="max-w-md sm:max-w-lg md:max-w-xl mx-auto relative shadow-xl rounded-full px-1">
        <form onsubmit="handleDeedSearch(event); return false;" class="relative flex items-center">
          <input type="text" id="hero-deed-search" 
                 placeholder="ระบุเลขที่โฉนด, น.ส.3ก หรือรหัสแปลงปลูก..." 
                 class="w-full pl-5 sm:pl-6 pr-24 sm:pr-28 py-2.5 sm:py-3 rounded-full bg-white/15 hover:bg-white/20 backdrop-blur-md border border-white/25 text-white placeholder-white/70 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-mezenc-mint/60 focus:bg-white/25 focus:border-white/40 shadow-lg font-light transition-all">
          <button type="submit" class="absolute right-1 sm:right-1.5 top-1 sm:top-1.5 bottom-1 sm:bottom-1.5 px-3.5 sm:px-4.5 bg-mezenc-teal/90 hover:bg-mezenc-brightCyan text-white rounded-full flex items-center gap-1 sm:gap-1.5 font-bold text-[11px] sm:text-xs uppercase tracking-wider transition-all shadow border border-white/20">
            <span>ตรวจสอบ</span>
            <svg class="w-3.5 h-3.5 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path></svg>
          </button>
        </form>
      </div>

    </div>

  </div>

    <!-- =========================================================================
       2. CAPABILITY HIGHLIGHT CARDS (การ์ดความสามารถหลัก 5 ด้าน ปรับปรุงสีและขนาดตามมาตรฐาน UI)
       ========================================================================= -->
  <section id="features" class="relative z-20 -mt-20 sm:-mt-24 md:-mt-28 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 mb-14 sm:mb-20">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-5 xl:gap-6">
      
      <!-- Card 1: จัดการข้อมูลเกษตรกรและแปลงปลูก -->
      <div class="group bg-white rounded-2xl md:rounded-3xl p-6 sm:p-7 xl:p-8 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.18),0_4px_12px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-10px_rgba(14,77,78,0.28),0_10px_20px_rgba(0,0,0,0.06)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:-translate-y-3 transition-all duration-300 flex flex-col justify-between items-center text-center relative">
        <div>
          <!-- Icon Badge -->
          <div class="w-14 h-14 xl:w-16 xl:h-16 rounded-2xl bg-mezenc-lightCyan/80 group-hover:bg-mezenc-teal flex items-center justify-center mx-auto mb-4 p-2.5 xl:p-3 transition-all duration-300 group-hover:scale-110 shadow-sm">
            <svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full"><path clip-rule="evenodd" d="M26.4097 9.61208C27.196 8.8358 27.1969 7.57578 26.4117 6.79842L21.1441 1.58305C20.3597 0.806412 19.0875 0.805538 18.302 1.5811L3.55214 16.1442C3.15754 16.5338 2.87982 17.024 2.74985 17.5603L1.05726 24.5451C0.697341 26.0304 2.09375 27.3461 3.57566 26.918L10.3372 24.9646C10.8224 24.8244 11.2642 24.5658 11.622 24.2125L26.4097 9.61208ZM20.4642 12.6725L10.2019 22.8047C10.0827 22.9225 9.9354 23.0087 9.77366 23.0554L4.17079 24.6741C3.65448 24.8232 3.16963 24.359 3.2962 23.8367L4.70476 18.024C4.74809 17.8453 4.84066 17.6819 4.97219 17.552L15.195 7.45865L20.4642 12.6725ZM21.8871 11.2676L16.618 6.05372L19.0185 3.68356C19.4084 3.29865 20.0354 3.29908 20.4247 3.68454L24.271 7.49266C24.6666 7.88436 24.6661 8.52374 24.27 8.91488L21.8871 11.2676Z" fill="#00A896" fill-rule="evenodd" class="group-hover:fill-white transition-colors"></path></svg>
          </div>
          <h3 class="font-extrabold text-sm sm:text-base xl:text-lg text-gray-900 mb-2 group-hover:text-mezenc-brightCyan transition-colors">
            จัดการข้อมูลเกษตรกรและแปลงปลูก
          </h3>
          <p class="text-xs xl:text-sm text-gray-500 font-light leading-relaxed mb-6">
            จัดเก็บและบริหารจัดการข้อมูลเกษตรกรพร้อมแปลงปลูกยางพาราให้อยู่ในรูปแบบดิจิทัลบนระบบคลาวด์
          </p>
        </div>
        <div class="w-full pt-1 flex items-center justify-center gap-2">
          <button onclick="openCardModal(1)" class="px-5 xl:px-6 py-1.5 xl:py-2 rounded-full border border-gray-200 hover:border-mezenc-teal hover:bg-mezenc-teal hover:text-white text-gray-500 text-[11px] xl:text-xs font-bold uppercase tracking-wider transition-all shadow-sm cursor-pointer">
            READ MORE
          </button>
        </div>
      </div>

      <!-- Card 2: วาดขอบเขตแปลงปลูก -->
      <div class="group bg-white rounded-2xl md:rounded-3xl p-6 sm:p-7 xl:p-8 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.18),0_4px_12px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-10px_rgba(14,77,78,0.28),0_10px_20px_rgba(0,0,0,0.06)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:-translate-y-3 transition-all duration-300 flex flex-col justify-between items-center text-center relative">
        <div>
          <!-- Icon Badge -->
          <div class="w-14 h-14 xl:w-16 xl:h-16 rounded-2xl bg-mezenc-lightCyan/80 group-hover:bg-mezenc-teal flex items-center justify-center mx-auto mb-4 p-2.5 xl:p-3 transition-all duration-300 group-hover:scale-110 shadow-sm">
            <svg viewBox="-3 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000" class="w-full h-full"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-183.000000, -5439.000000)" fill="#00A896"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M134,5287.635 C133.449,5287.635 133,5287.186 133,5286.635 C133,5286.084 133.449,5285.635 134,5285.635 C134.551,5285.635 135,5286.084 135,5286.635 C135,5287.186 134.551,5287.635 134,5287.635 M134,5283.635 C132.343,5283.635 131,5284.978 131,5286.635 C131,5288.292 132.343,5289.635 134,5289.635 C135.657,5289.635 137,5288.292 137,5286.635 C137,5284.978 135.657,5283.635 134,5283.635 M134,5296 C134,5296 129,5289 129,5286 C129,5283.243 131.243,5281 134,5281 C136.757,5281 139,5283.243 139,5286 C139,5289 134,5296 134,5296 M134,5279 C130.134,5279 127,5282.134 127,5286 C127,5289.866 134,5299 134,5299 C134,5299 141,5289.866 141,5286 C141,5282.134 137.866,5279 134,5279" id="pin_outline-[#1328]" class="group-hover:fill-white transition-colors"> </path> </g> </g> </g> </g></svg>
          </div>
          <h3 class="font-extrabold text-sm sm:text-base xl:text-lg text-gray-900 mb-2 group-hover:text-mezenc-brightCyan transition-colors">
            วาดขอบเขตแปลงปลูก
          </h3>
          <p class="text-xs xl:text-sm text-gray-500 font-light leading-relaxed mb-6">
            กำหนดและคำนวณขอบเขตแปลงปลูกจริงในรูปแบบ Polygon บนแผนที่ดิจิทัลแบบโต้ตอบ
          </p>
        </div>
        <div class="w-full pt-1 flex items-center justify-center gap-2">
          <button onclick="openCardModal(2)" class="px-5 xl:px-6 py-1.5 xl:py-2 rounded-full border border-gray-200 hover:border-mezenc-teal hover:bg-mezenc-teal hover:text-white text-gray-500 text-[11px] xl:text-xs font-bold uppercase tracking-wider transition-all shadow-sm cursor-pointer">
            READ MORE
          </button>
        </div>
      </div>

      <!-- Card 3: ตรวจสอบการทับซ้อนพื้นที่แปลงปลูก -->
      <div class="group bg-white rounded-2xl md:rounded-3xl p-6 sm:p-7 xl:p-8 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.18),0_4px_12px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-10px_rgba(14,77,78,0.28),0_10px_20px_rgba(0,0,0,0.06)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:-translate-y-3 transition-all duration-300 flex flex-col justify-between items-center text-center relative">
        <div>
          <!-- Icon Badge -->
          <div class="w-14 h-14 xl:w-16 xl:h-16 rounded-2xl bg-mezenc-lightCyan/80 group-hover:bg-mezenc-teal flex items-center justify-center mx-auto mb-4 p-2.5 xl:p-3 transition-all duration-300 group-hover:scale-110 shadow-sm">
            <svg viewBox="-2.96 0 19.945 19.945" xmlns="http://www.w3.org/2000/svg" fill="#000000" class="w-full h-full"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="tree-2" transform="translate(-4.992 -2.055)"> <path id="secondary" fill="#00A896" d="M16.74,12.17A3.66,3.66,0,0,1,17,13.5,3.5,3.5,0,0,1,13.5,17a3.45,3.45,0,0,1-1.5-.35,3.45,3.45,0,0,1-1.5.35A3.5,3.5,0,0,1,7,13.5a3.66,3.66,0,0,1,.26-1.33,3.48,3.48,0,0,1,.81-5.86,4,4,0,0,1,7.86,0,3.48,3.48,0,0,1,.81,5.86Z"></path> <path id="primary" d="M16.74,12.17A3.66,3.66,0,0,1,17,13.5,3.5,3.5,0,0,1,13.5,17a3.45,3.45,0,0,1-1.5-.35,3.45,3.45,0,0,1-1.5.35A3.5,3.5,0,0,1,7,13.5a3.66,3.66,0,0,1,.26-1.33,3.48,3.48,0,0,1,.81-5.86,4,4,0,0,1,7.86,0,3.48,3.48,0,0,1,.81,5.86ZM12,21V11M10,21h4" fill="none" stroke="#00A896" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path> </g> </g></svg>
          </div>
          <h3 class="font-extrabold text-sm sm:text-base xl:text-lg text-gray-900 mb-2 group-hover:text-mezenc-brightCyan transition-colors">
            ตรวจสอบการทับซ้อนพื้นที่แปลงปลูก
          </h3>
          <p class="text-xs xl:text-sm text-gray-500 font-light leading-relaxed mb-6">
            วิเคราะห์ความถูกต้องเชิงพื้นที่เพื่อตรวจสอบการทับซ้อนของแปลงปลูกกับแนวเขตป่าสงวน
          </p>
        </div>
        <div class="w-full pt-1 flex items-center justify-center gap-2">
          <button onclick="openCardModal(3)" class="px-5 xl:px-6 py-1.5 xl:py-2 rounded-full border border-gray-200 hover:border-mezenc-teal hover:bg-mezenc-teal hover:text-white text-gray-500 text-[11px] xl:text-xs font-bold uppercase tracking-wider transition-all shadow-sm cursor-pointer">
            READ MORE
          </button>
        </div>
      </div>

      <!-- Card 4: บันทึกผลผลิตและสนับสนุนการตัดสินใจ -->
      <div class="group bg-white rounded-2xl md:rounded-3xl p-6 sm:p-7 xl:p-8 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.18),0_4px_12px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-10px_rgba(14,77,78,0.28),0_10px_20px_rgba(0,0,0,0.06)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:-translate-y-3 transition-all duration-300 flex flex-col justify-between items-center text-center relative">
        <div>
          <!-- Icon Badge -->
          <div class="w-14 h-14 xl:w-16 xl:h-16 rounded-2xl bg-mezenc-lightCyan/80 group-hover:bg-mezenc-teal flex items-center justify-center mx-auto mb-4 p-2.5 xl:p-3 transition-all duration-300 group-hover:scale-110 shadow-sm">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4 5V19C4 19.5523 4.44772 20 5 20H19" stroke="#00A896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M18 9L13 13.9999L10.5 11.4998L7 14.9998" stroke="#00A896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
          </div>
          <h3 class="font-extrabold text-sm sm:text-base xl:text-lg text-gray-900 mb-2 group-hover:text-mezenc-brightCyan transition-colors">
            บันทึกผลผลิตและสนับสนุนการตัดสินใจ
          </h3>
          <p class="text-xs xl:text-sm text-gray-500 font-light leading-relaxed mb-6">
            ระบบบันทึกผลผลิตน้ำยางสดพร้อมแดชบอร์ดวิเคราะห์ข้อมูลเพื่อการบริหารจัดการสวนยาง
          </p>
        </div>
        <div class="w-full pt-1 flex items-center justify-center gap-2">
          <button onclick="openCardModal(4)" class="px-5 xl:px-6 py-1.5 xl:py-2 rounded-full border border-gray-200 hover:border-mezenc-teal hover:bg-mezenc-teal hover:text-white text-gray-500 text-[11px] xl:text-xs font-bold uppercase tracking-wider transition-all shadow-sm cursor-pointer">
            READ MORE
          </button>
        </div>
      </div>

      <!-- Card 5: ตรวจสอบย้อนกลับตามมาตรฐาน EUDR -->
      <div class="group bg-white rounded-2xl md:rounded-3xl p-6 sm:p-7 xl:p-8 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.18),0_4px_12px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-10px_rgba(14,77,78,0.28),0_10px_20px_rgba(0,0,0,0.06)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan hover:-translate-y-3 transition-all duration-300 flex flex-col justify-between items-center text-center col-span-1 sm:col-span-2 md:col-span-1 relative">
        <div>
          <!-- Icon Badge -->
          <div class="w-14 h-14 xl:w-16 xl:h-16 rounded-2xl bg-mezenc-lightCyan/80 group-hover:bg-mezenc-teal flex items-center justify-center mx-auto mb-4 p-2.5 xl:p-3 transition-all duration-300 group-hover:scale-110 shadow-sm">
            <svg fill="#000000" viewBox="0 0 24 24" id="qr-code-scan" data-name="Flat Color" xmlns="http://www.w3.org/2000/svg" class="icon flat-color w-full h-full"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path id="secondary" d="M12,13v4a1,1,0,0,1-1,1H7a1,1,0,0,1-1-1V13a1,1,0,0,1,1-1h4A1,1,0,0,1,12,13ZM7,10H9a1,1,0,0,0,1-1V7A1,1,0,0,0,9,6H7A1,1,0,0,0,6,7V9A1,1,0,0,0,7,10Zm10,4H15a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V15A1,1,0,0,0,17,14Zm0-8H13a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1V7A1,1,0,0,0,17,6Z" style="fill: #2ca9bc;"></path><path id="primary" d="M21,9a1,1,0,0,1-1-1V4H16a1,1,0,0,1,0-2h4a2,2,0,0,1,2,2V8A1,1,0,0,1,21,9Zm1,11V16a1,1,0,0,0-2,0v4H16a1,1,0,0,0,0,2h4A2,2,0,0,0,22,20ZM4,8V4H8A1,1,0,0,0,8,2H4A2,2,0,0,0,2,4V8A1,1,0,0,0,4,8ZM9,21a1,1,0,0,0-1-1H4V16a1,1,0,0,0-2,0v4a2,2,0,0,0,2,2H8A1,1,0,0,0,9,21Z" style="fill: #00A896;"></path></g></svg>
          </div>
          <h3 class="font-extrabold text-sm sm:text-base xl:text-lg text-gray-900 mb-2 group-hover:text-mezenc-brightCyan transition-colors">
            ตรวจสอบย้อนกลับตามมาตรฐาน EUDR
          </h3>
          <p class="text-xs xl:text-sm text-gray-500 font-light leading-relaxed mb-6">
            สร้าง QR Code ประจำแปลงเพื่อยืนยันแหล่งที่มาของผลผลิตและรองรับข้อกำหนดความยั่งยืน
          </p>
        </div>
        <div class="w-full pt-1 flex items-center justify-center gap-2">
          <button onclick="openCardModal(5)" class="px-5 xl:px-6 py-1.5 xl:py-2 rounded-full border border-gray-200 hover:border-mezenc-teal hover:bg-mezenc-teal hover:text-white text-gray-500 text-[11px] xl:text-xs font-bold uppercase tracking-wider transition-all shadow-sm cursor-pointer">
            READ MORE
          </button>
        </div>
      </div>

    </div>
  </section>

  <!-- =========================================================================
       READ MORE DETAIL MODAL (กล่องป๊อปอัปแสดงรายละเอียดเต็มเมื่อกดปุ่ม)
       ========================================================================= -->
  <div id="readmore-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300" onclick="if(event.target === this) closeReadMoreModal()">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300 space-y-4" id="readmore-modal-content">
      <div class="flex justify-between items-start pb-3 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div id="modal-badge" class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 shadow-inner"></div>
          <div>
            <div class="text-[11px] font-bold text-mezenc-brightCyan uppercase tracking-wider">รายละเอียดระบบงาน</div>
            <h3 id="modal-title" class="font-extrabold text-base sm:text-lg text-mezenc-teal leading-snug"></h3>
          </div>
        </div>
        <button onclick="closeReadMoreModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition-colors shrink-0">✕</button>
      </div>
      <p id="modal-text" class="text-sm sm:text-base text-gray-600 leading-relaxed font-light"></p>
      <div class="pt-4 flex justify-end items-center border-t border-gray-100">
        <button type="button" onclick="closeReadMoreModal()" class="px-7 py-2.5 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-xs sm:text-sm transition-all shadow-md hover:shadow-lg cursor-pointer">
          ปิด
        </button>
      </div>
    </div>
  </div>

    <!-- =========================================================================
       3. TERRITORIAL GIS MAP & REAL-TIME CONTROLS
       ========================================================================= -->
  <!-- 3.1 Section Header (อยู่บนพื้นหลังสีขาว ด้านบนก่อนเข้าสู่แถบสีเขียว เว้นระยะห่างด้านบนและล่างเท่าๆ กัน) -->
  <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 mb-10 sm:mb-14 xl:mb-16 pt-2 sm:pt-4 text-center">
    <div class="max-w-4xl mx-auto space-y-3 sm:space-y-3.5">
      <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-brightCyan uppercase tracking-widest leading-relaxed">
        การจำแนกแนวเขตป่าสงวนและประเมินพื้นที่เสี่ยงเชิงภูมิสารสนเทศ
      </div>
      <h2 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-mezenc-teal tracking-wide leading-[1.3] sm:leading-[1.35]">
        พื้นที่คุ้มครองและการใช้ประโยชน์ที่ดิน<br>จังหวัดสุราษฎร์ธานี
      </h2>
      <p class="text-[14px] text-gray-500 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1">
        ศูนย์กลางข้อมูลเชิงพื้นที่เพื่อสร้างความเข้าใจและเฝ้าระวังแนวเขตป่าสงวนแห่งชาติ 26 แห่งในจังหวัดสุราษฎร์ธานี
      </p>
    </div>
  </div>

  <!-- 3.2 Atmospheric Gradient Band (พื้นหลังสีเขียว/มิ้นต์ คลุมเฉพาะเนื้อหาซ้าย-ขวา ข้อความ + แผนที่) -->
  <section class="relative w-full bg-gradient-to-b from-[#f4faf9] via-[#dff2f0] to-[#f4faf9] py-8 sm:py-12 xl:py-14 mb-20 overflow-hidden">
    
    <!-- Top Feathered Dissolve from White (ขอบบนค่อยๆ ไล่สีและละลายจากสีขาวลงมาอย่างนุ่มนวล) -->
    <div class="absolute top-0 left-0 right-0 h-10 sm:h-14 bg-gradient-to-b from-white via-white/70 to-transparent pointer-events-none z-10"></div>
    
    <!-- Bottom Feathered Dissolve to White (ขอบล่างค่อยๆ ละลายกลับสู่สีขาวอย่างกลมกลืน) -->
    <div class="absolute bottom-0 left-0 right-0 h-12 sm:h-16 bg-gradient-to-t from-white via-white/70 to-transparent pointer-events-none z-10"></div>

    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 xl:gap-12 items-center">
        
        <!-- Left Column: Story, Key Stats & Main CTA (Vertically Centered with Map) -->
        <div class="lg:col-span-6 flex flex-col justify-center space-y-5 sm:space-y-6">
          
          <!-- Description Paragraphs -->
          <div class="space-y-3 text-sm sm:text-base lg:text-[15px] xl:text-[16px] text-gray-600 leading-relaxed font-light">
            <p>
              ศูนย์กลางข้อมูลเชิงพื้นที่เพื่อสร้างความเข้าใจและเฝ้าระวังแนวเขตป่าสงวนแห่งชาติ 26 แห่งในจังหวัดสุราษฎร์ธานี โดยเชื่อมโยงฐานข้อมูลสารสนเทศจริงร่วมกับแบบจำลองพื้นที่โดยรอบมหาวิทยาลัย เพื่อยกระดับการบริหารจัดการทรัพยากรธรรมชาติอย่างยั่งยืน
            </p>
            <p>
              ระบบรองรับทั้งการศึกษาเรียนรู้มิติด้านการอนุรักษ์ และการตรวจสอบพิกัดแปลงปลูกพืชเศรษฐกิจเทียบกับแนวเขตคุ้มครอง ช่วยประเมินและจำแนกโซนความเสี่ยงเพื่อป้องกันปัญหาการทับซ้อนพื้นที่หวงห้ามได้อย่างถูกต้อง
            </p>
          </div>

          <!-- Key Stats Cards -->
          <div class="grid grid-cols-3 gap-2.5 sm:gap-3 xl:gap-4">
            <div class="p-3 sm:p-3.5 xl:p-4 bg-white/95 rounded-2xl border border-gray-200/90 text-center shadow-xs">
              <div class="text-[10px] sm:text-xs text-mezenc-teal font-medium uppercase">พื้นที่คุ้มครองรวม</div>
              <div class="text-xs sm:text-sm lg:text-base xl:text-lg font-black text-mezenc-teal mt-0.5">784,618 ไร่</div>
            </div>
            <div class="p-3 sm:p-3.5 xl:p-4 bg-white/95 rounded-2xl border border-gray-200/90 text-center shadow-xs">
              <div class="text-[10px] sm:text-xs text-mezenc-teal font-medium uppercase">ป่าสงวนแห่งชาติ</div>
              <div class="text-xs sm:text-sm lg:text-base xl:text-lg font-black text-mezenc-teal mt-0.5">26 ผืนป่า</div>
            </div>
            <div class="p-3 sm:p-3.5 xl:p-4 bg-white/95 rounded-2xl border border-gray-200/90 text-center shadow-xs">
              <div class="text-[10px] sm:text-xs text-mezenc-teal font-medium uppercase">ระยะกันชน Buffer</div>
              <div class="text-xs sm:text-sm lg:text-base xl:text-lg font-black text-emerald-700 mt-0.5">500 เมตร</div>
            </div>
          </div>

          <!-- Main Full Map Action Button -->
          <div class="pt-0.5">
            <a href="overview.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-mezenc-brightCyan hover:bg-mezenc-teal text-white px-7 sm:px-8 py-3 sm:py-3.5 rounded-full font-bold text-xs sm:text-sm uppercase tracking-wider shadow-md hover:shadow-lg hover:scale-105 transition-all">
              <span>เปิดแผนที่ระบบภูมิสารสนเทศ (Full GIS Map) ➔</span>
            </a>
          </div>

        </div>
        <!-- End Left Column -->

        <!-- Right Column: REAL INTERACTIVE LEAFLET MAP (Balanced Proportions & 3D Floating Look) -->
        <div class="lg:col-span-6 w-full">
          <div class="bg-white p-4 sm:p-5 rounded-2xl md:rounded-3xl shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18),0_6px_18px_rgba(0,0,0,0.04)] hover:shadow-[0_28px_56px_-12px_rgba(14,77,78,0.25)] border-2 border-[#bee6e1] hover:border-mezenc-brightCyan/60 transition-all duration-300 space-y-2.5 sm:space-y-3 relative">
            
            <div class="flex justify-between items-center px-1">
              <span class="text-sm sm:text-base font-bold text-mezenc-teal flex items-center gap-2">
                <img src="img/map_icon.png" alt="Map Icon" class="w-7 h-7 sm:w-8 sm:h-8 object-contain inline-block drop-shadow-sm">
                <span>แผนที่แนวเขตป่าสงวนแห่งชาติ จังหวัดสุราษฎร์ธานี</span>
              </span>
              <span class="text-xs sm:text-sm font-bold text-rose-600 flex items-center gap-1">
                🔴 เขตคุ้มครองเข้มงวด
              </span>
            </div>

            <!-- Real Leaflet Map Container -->
            <div id="surat-real-territory-map" class="shadow-inner border border-gray-200"></div>

            <!-- Dynamic Info Box Below Map -->
            <div class="bg-mezenc-lightCyan/60 p-2.5 sm:p-3 rounded-xl border border-mezenc-mint/30 text-center">
              <div id="district-title" class="text-xs sm:text-sm font-bold text-mezenc-teal">
                ป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี (แตะหรือเลื่อนเมาส์บนแผนที่เพื่อดูข้อมูล)
              </div>
              <div id="district-desc" class="text-[10px] sm:text-xs text-gray-600 mt-0.5 font-light">
                ฐานข้อมูลแนวเขตป่าสงวนแห่งชาติ 26 แห่ง (Zone-c) • ปลอดการตัดไม้ทำลายป่า 100%
              </div>
              <div id="district-forest" class="text-[10px] sm:text-[11px] text-emerald-700 font-semibold mt-0.5">
                ครอบคลุมพื้นที่คุ้มครองรวมกว่า 784,618 ไร่
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- =========================================================================
       4. WORKFLOW PIPELINE / USER GUIDE (ขั้นตอนการทำงานของระบบ 4 ขั้นตอน แบบ 2 คอลัมน์)
       ========================================================================= -->
  <!-- 4.1 Section Header (อยู่บนพื้นหลังสีขาว ด้านบนก่อนเข้าสู่แถบสีเขียว เว้นระยะห่างด้านบนและล่างเท่าๆ กัน) -->
  <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 mb-10 sm:mb-14 xl:mb-16 pt-2 sm:pt-4 text-center">
    <div class="max-w-4xl mx-auto space-y-3 sm:space-y-3.5">
      <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-brightCyan uppercase tracking-widest leading-relaxed">
        ขั้นตอนการทำงานของระบบ
      </div>
      <h2 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-mezenc-teal tracking-wide leading-[1.3] sm:leading-[1.35]">
        4 ขั้นตอนสู่การรับรองมาตรฐาน EUDR
      </h2>
      <p class="text-[14px] text-gray-500 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1">
        คู่มือและขั้นตอนการใช้งานระบบภูมิสารสนเทศสำหรับเกษตรกรและผู้ประกอบการสวนยาง เพื่อการขึ้นทะเบียนและขอรับรองมาตรฐาน EUDR อย่างถูกต้องครบวงจร
      </p>
    </div>
  </div>

  <!-- 4.2 Atmospheric Gradient Band (พื้นหลังสีเขียว/มิ้นต์ คลุมการ์ดขั้นตอนแบบ 2 คอลัมน์) -->
  <section id="guide" class="relative w-full bg-gradient-to-b from-[#f4faf9] via-[#dff2f0] to-[#f4faf9] py-14 sm:py-20 xl:py-24 mb-20 overflow-hidden">
    
    <!-- Top Feathered Dissolve from White (ขอบบนค่อยๆ ไล่สีและละลายจากสีขาวลงมาอย่างนุ่มนวล) -->
    <div class="absolute top-0 left-0 right-0 h-14 sm:h-20 bg-gradient-to-b from-white via-white/70 to-transparent pointer-events-none z-10"></div>
    
    <!-- Bottom Feathered Dissolve to White (ขอบล่างค่อยๆ ละลายกลับสู่สีขาวอย่างกลมกลืน) -->
    <div class="absolute bottom-0 left-0 right-0 h-16 sm:h-24 bg-gradient-to-t from-white via-white/70 to-transparent pointer-events-none z-10"></div>

    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      
      <!-- 2-Column User Guide Layout (Overlapping Photos + 4 Steps) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-center">
        
        <!-- Left: Large Overlapping Nature & GIS Cards + Action Button -->
        <div class="lg:col-span-6 flex flex-col items-center justify-center space-y-4 sm:space-y-6 py-2 sm:py-4">
          
          <!-- Overlapping Cards Container -->
          <div class="relative flex items-center justify-center w-full max-w-[620px]">
            
            <!-- Left Card (เอียงซ้ายเล็กน้อย) -->
            <div class="w-[230px] sm:w-[280px] md:w-[310px] xl:w-[330px] h-[300px] sm:h-[360px] md:h-[400px] rounded-[28px] sm:rounded-[36px] overflow-hidden border-4 sm:border-[6px] border-white shadow-[0_20px_45px_rgba(0,0,0,0.2)] transform -rotate-6 hover:-rotate-2 hover:scale-105 hover:z-30 transition-all duration-500 ease-out shrink-0 relative z-10 -mr-12 sm:-mr-16 bg-gray-100">
              <img 
                src="img/map_rubber_hero.jpg" 
                alt="Rubber Plantation GIS View" 
                class="w-full h-full object-cover object-center" 
                onerror="this.onerror=null; this.src='ปก.png';"
              >
            </div>

            <!-- Right Card (เอียงขวาเล็กน้อย ทับซ้อนด้านบน) -->
            <div class="w-[230px] sm:w-[280px] md:w-[310px] xl:w-[330px] h-[300px] sm:h-[360px] md:h-[400px] rounded-[28px] sm:rounded-[36px] overflow-hidden border-4 sm:border-[6px] border-white shadow-[0_25px_55px_rgba(0,0,0,0.25)] transform rotate-6 hover:rotate-2 hover:scale-105 hover:z-30 transition-all duration-500 ease-out shrink-0 relative z-20 -ml-12 sm:-ml-16 bg-gray-200">
              <img 
                src="img/rubber_farmer_app.jpg" 
                alt="เกษตรกรชาวสวนยางพารา จ.สุราษฎร์ธานี ใช้งานแอปพลิเคชัน GeoRubber Watch" 
                class="w-full h-full object-cover object-center" 
                onerror="this.onerror=null; this.src='img/forest_reserve_hero.jpg';"
              >
            </div>

          </div>

          <!-- Action Button Below the Photos -->
          <div class="text-center pt-1 sm:pt-2">
            <a href="map.php" class="inline-flex items-center gap-2.5 px-6 sm:px-8 py-2.5 sm:py-3 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">
              <span>เริ่มต้นใช้งานทันที</span>
              <span class="text-xs">➔</span>
            </a>
          </div>

        </div>

        <!-- Right Steps (Clean Layout Without Card Background/Borders) -->
        <div class="lg:col-span-6 flex flex-col justify-center space-y-4 sm:space-y-5 px-2 sm:px-4">
          
          <!-- Step 1: วาดขอบเขตแปลงปลูก -->
          <div class="flex items-start gap-4 pb-5 border-b-2 border-white group cursor-default">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-base sm:text-lg flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
              01
            </div>
            <div class="space-y-1">
              <h4 class="font-extrabold text-base sm:text-lg text-mezenc-teal flex items-center gap-2.5 group-hover:text-mezenc-brightCyan transition-colors">
                <svg viewBox="-3 0 20 20" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="#00A896"><g transform="translate(-183 -5439)" fill="#00A896"><g transform="translate(56 160)"><path d="M134,5287.635 C133.449,5287.635 133,5287.186 133,5286.635 C133,5286.084 133.449,5285.635 134,5285.635 C134.551,5285.635 135,5286.084 135,5286.635 C135,5287.186 134.551,5287.635 134,5287.635 M134,5283.635 C132.343,5283.635 131,5284.978 131,5286.635 C131,5288.292 132.343,5289.635 134,5289.635 C135.657,5289.635 137,5288.292 137,5286.635 C137,5284.978 135.657,5283.635 134,5283.635 M134,5296 C134,5296 129,5289 129,5286 C129,5283.243 131.243,5281 134,5281 C136.757,5281 139,5283.243 139,5286 C139,5289 134,5296 134,5296 M134,5279 C130.134,5279 127,5282.134 127,5286 C127,5289.866 134,5299 134,5299 C134,5299 141,5289.866 141,5286 C141,5282.134 137.866,5279 134,5279"></path></g></g></svg>
                <span>Step 1: วาดขอบเขตแปลงปลูก</span>
              </h4>
              <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
                ปักหมุดพิกัด WGS84 และวาดขอบเขตแปลงยางพาราด้วยเครื่องมือ GIS พร้อมคำนวณเนื้อที่ ไร่-งาน-วา อัตโนมัติ
              </p>
            </div>
          </div>

          <!-- Step 2: ตรวจสอบการซ้อนทับพื้นที่แปลงปลูก -->
          <div class="flex items-start gap-4 pb-5 border-b-2 border-white group cursor-default">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-base sm:text-lg flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
              02
            </div>
            <div class="space-y-1">
              <h4 class="font-extrabold text-base sm:text-lg text-mezenc-teal flex items-center gap-2.5 group-hover:text-mezenc-brightCyan transition-colors">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0"><circle cx="10" cy="10" r="7" style="fill: #ffffff; stroke: #00a894; stroke-width: 2;"></circle><line x1="20.5" y1="20.5" x2="17" y2="17" style="fill: none; stroke: #00a894; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2.5;"></line><path d="M20,20,15,15M10,3a7,7,0,1,0,7,7A7,7,0,0,0,10,3Z" style="fill: none; stroke: #00a894; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2;"></path></svg>
                <span>Step 2: ตรวจสอบการซ้อนทับพื้นที่แปลงปลูก</span>
              </h4>
              <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
                วิเคราะห์การทับซ้อนและวัดระยะห่าง Buffer Zone 500 เมตร เทียบกับแนวเขตป่าสงวนแห่งชาติจริงของสุราษฎร์ธานี (Zone-c)
              </p>
            </div>
          </div>

          <!-- Step 3: บันทึกผลผลิต -->
          <div class="flex items-start gap-4 pb-5 border-b-2 border-white group cursor-default">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-base sm:text-lg flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
              03
            </div>
            <div class="space-y-1">
              <h4 class="font-extrabold text-base sm:text-lg text-mezenc-teal flex items-center gap-2.5 group-hover:text-mezenc-brightCyan transition-colors">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.1716 1C18.702 1 19.2107 1.21071 19.5858 1.58579L22.4142 4.41421C22.7893 4.78929 23 5.29799 23 5.82843V20C23 21.6569 21.6569 23 20 23H4C2.34315 23 1 21.6569 1 20V4C1 2.34315 2.34315 1 4 1H18.1716ZM4 3C3.44772 3 3 3.44772 3 4V20C3 20.5523 3.44772 21 4 21L5 21L5 15C5 13.3431 6.34315 12 8 12L16 12C17.6569 12 19 13.3431 19 15V21H20C20.5523 21 21 20.5523 21 20V6.82843C21 6.29799 20.7893 5.78929 20.4142 5.41421L18.5858 3.58579C18.2107 3.21071 17.702 3 17.1716 3H17V5C17 6.65685 15.6569 8 14 8H10C8.34315 8 7 6.65685 7 5V3H4ZM17 21V15C17 14.4477 16.5523 14 16 14L8 14C7.44772 14 7 14.4477 7 15L7 21L17 21ZM9 3H15V5C15 5.55228 14.5523 6 14 6H10C9.44772 6 9 5.55228 9 5V3Z" fill="#00a894"></path></svg>
                <span>Step 3: บันทึกผลผลิต</span>
              </h4>
              <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
                บันทึกปริมาณน้ำยางสด ราคารับซื้อ และผลผลิตรายเดือน เชื่อมโยงกับรหัสแปลงปลูกเพื่อวิเคราะห์แนวโน้ม
              </p>
            </div>
          </div>

          <!-- Step 4: ตรวจสอบย้อนกลับตามมาตรฐาน EUDR -->
          <div class="flex items-start gap-4 group cursor-default">
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-base sm:text-lg flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
              04
            </div>
            <div class="space-y-1">
              <h4 class="font-extrabold text-base sm:text-lg text-mezenc-teal flex items-center gap-2.5 group-hover:text-mezenc-brightCyan transition-colors">
                <svg fill="#000000" viewBox="0 0 24 24" id="qr-code-scan" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0"><g id="SVGRepo_iconCarrier"><path id="secondary" d="M12,13v4a1,1,0,0,1-1,1H7a1,1,0,0,1-1-1V13a1,1,0,0,1,1-1h4A1,1,0,0,1,12,13ZM7,10H9a1,1,0,0,0,1-1V7A1,1,0,0,0,9,6H7A1,1,0,0,0,6,7V9A1,1,0,0,0,7,10Zm10,4H15a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V15A1,1,0,0,0,17,14Zm0-8H13a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1V7A1,1,0,0,0,17,6Z" style="fill: #2ca9bc;"></path><path id="primary" d="M21,9a1,1,0,0,1-1-1V4H16a1,1,0,0,1,0-2h4a2,2,0,0,1,2,2V8A1,1,0,0,1,21,9Zm1,11V16a1,1,0,0,0-2,0v4H16a1,1,0,0,0,0,2h4A2,2,0,0,0,22,20ZM4,8V4H8A1,1,0,0,0,8,2H4A2,2,0,0,0,2,4V8A1,1,0,0,0,4,8ZM9,21a1,1,0,0,0-1-1H4V16a1,1,0,0,0-2,0v4a2,2,0,0,0,2,2H8A1,1,0,0,0,9,21Z" style="fill: #00A896;"></path></g></svg>
                <span>Step 4: ตรวจสอบย้อนกลับตามมาตรฐาน EUDR</span>
              </h4>
              <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
                สร้างเอกสารรับรองดิจิทัล พร้อม QR Code สำหรับผู้ซื้อและเจ้าหน้าที่สแกนตรวจสอบย้อนกลับ (Traceability) 100%
              </p>
            </div>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- =========================================================================
       5. [EUDR KNOWLEDGE BASE] กล่องความรู้และเกณฑ์จำแนกสีสถานะพื้นที่ (3 RISK CARDS)
       ========================================================================= -->
  <!-- 5.1 Section Header (อยู่บนพื้นหลังสีขาว ด้านบนก่อนเข้าสู่แถบสีเขียว เว้นระยะห่างด้านบนและล่างเท่าๆ กัน) -->
  <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 mb-10 sm:mb-14 xl:mb-16 pt-2 sm:pt-4 text-center">
    <div class="max-w-4xl mx-auto space-y-3 sm:space-y-3.5">
      <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-brightCyan uppercase tracking-widest leading-relaxed">
        EUDR KNOWLEDGE BASE
      </div>
      <h2 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-mezenc-teal tracking-wide leading-[1.3] sm:leading-[1.35]">
        3 ระดับสถานะความเสี่ยงเชิงพื้นที่
      </h2>
      <p class="text-[14px] text-gray-500 font-light leading-relaxed tracking-normal max-w-4xl mx-auto pt-1">
        คู่มือจำแนกแปลงปลูกยางพาราตามเกณฑ์ปลอดการตัดไม้ทำลายป่า (Zero Deforestation) และ พ.ร.บ. ป่าสงวนแห่งชาติ
      </p>
    </div>
  </div>

  <!-- 5.2 Atmospheric Gradient Band (พื้นหลังสีเขียว/มิ้นต์ คลุมการ์ดความรู้ 3 ระดับความเสี่ยง) -->
  <section id="knowledge-section" class="relative w-full bg-gradient-to-b from-[#f4faf9] via-[#dff2f0] to-[#f4faf9] py-14 sm:py-20 xl:py-24 mb-24 overflow-hidden">
    
    <!-- Top Feathered Dissolve from White (ขอบบนค่อยๆ ไล่สีและละลายจากสีขาวลงมาอย่างนุ่มนวล) -->
    <div class="absolute top-0 left-0 right-0 h-14 sm:h-20 bg-gradient-to-b from-white via-white/70 to-transparent pointer-events-none z-10"></div>
    
    <!-- Bottom Feathered Dissolve to White (ขอบล่างค่อยๆ ละลายกลับสู่สีขาวอย่างกลมกลืน) -->
    <div class="absolute bottom-0 left-0 right-0 h-16 sm:h-24 bg-gradient-to-t from-white via-white/70 to-transparent pointer-events-none z-10"></div>

    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      
      <!-- 3 Modern Aesthetic Cards matching the Reference Image -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
        
        <!-- CARD 1: STRICT FOREST RESERVE (เขตป่าสงวน) -->
        <div class="bg-white rounded-[2rem] sm:rounded-[2.25rem] p-4 sm:p-5 lg:p-6 shadow-[0_16px_40px_-10px_rgba(14,77,78,0.12)] hover:shadow-[0_24px_50px_-10px_rgba(14,77,78,0.22)] border-2 border-[#bee6e1]/80 hover:border-mezenc-brightCyan transition-all duration-300 hover:-translate-y-2 flex flex-col justify-between group">
          <div>
            <!-- Top Nature Image with Smooth Rounded Corners -->
            <div class="h-56 sm:h-60 lg:h-64 rounded-[1.5rem] sm:rounded-[1.75rem] overflow-hidden relative mb-5 shadow-xs">
              <img src="img/forest_reserve_hero.jpg" alt="พื้นที่อนุรักษ์ 26 ป่าสงวนแห่งชาติ สุราษฎร์ธานี" class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out">
            </div>

            <!-- Content Area -->
            <div class="px-1 sm:px-2">
              <h3 class="text-lg sm:text-xl font-black text-[#1a3a3a] mb-2 leading-snug group-hover:text-mezenc-brightCyan transition-colors">
                พื้นที่อนุรักษ์ 26 ป่าสงวนแห่งชาติ
              </h3>
              <p class="text-xs sm:text-sm text-gray-500 font-light leading-relaxed mb-6">
                แปลงที่ตั้งอยู่ในแนวเขตป่าสงวนแห่งชาติ 26 แห่ง ของสุราษฎร์ธานี (เขตป่าเพื่อการอนุรักษ์: Zone C) หรือพื้นที่ที่มีการแผ้วถางหลัง 31 ธ.ค. 2020 (EU Cut-off Date)
              </p>
            </div>
          </div>

          <!-- Bottom Action Pill Button (Bright Cyan Pill matching Full GIS Map button) -->
          <div class="px-1 sm:px-2 pt-2">
            <button type="button" onclick="openReadMoreModal('🔴', 'สำรวจ 26 แนวเขตป่าสงวนแห่งชาติ', 'กรุณาเข้าสู่ระบบเพื่อสำรวจและวิเคราะห์แนวเขตป่าสงวนแห่งชาติ 26 แห่งในจังหวัดสุราษฎร์ธานี (Zone C) ตรวจสอบพิกัดความทับซ้อน และข้อมูลเชิงพื้นที่ระบบ GIS อย่างละเอียด', 'login.php?redirect=overview.php')" class="inline-flex items-center justify-center gap-2.5 bg-mezenc-brightCyan hover:bg-mezenc-teal text-white px-7 sm:px-8 py-3 sm:py-3.5 rounded-full font-bold text-xs sm:text-sm uppercase tracking-wider shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer">
              <span>สำรวจ 26 แนวเขตป่าสงวน ➔</span>
            </button>
          </div>
        </div>

        <!-- CARD 2: BUFFER ZONE 500M (เขตเฝ้าระวัง) -->
        <div class="bg-white rounded-[2rem] sm:rounded-[2.25rem] p-4 sm:p-5 lg:p-6 shadow-[0_16px_40px_-10px_rgba(14,77,78,0.12)] hover:shadow-[0_24px_50px_-10px_rgba(14,77,78,0.22)] border-2 border-[#bee6e1]/80 hover:border-mezenc-brightCyan transition-all duration-300 hover:-translate-y-2 flex flex-col justify-between group">
          <div>
            <!-- Top Nature Image with Smooth Rounded Corners -->
            <div class="h-56 sm:h-60 lg:h-64 rounded-[1.5rem] sm:rounded-[1.75rem] overflow-hidden relative mb-5 shadow-xs">
              <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=800&q=80" alt="Buffer Zone 500m Satellite Monitoring" class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out">
            </div>

            <!-- Content Area -->
            <div class="px-1 sm:px-2">
              <h3 class="text-lg sm:text-xl font-black text-[#1a3a3a] mb-2 leading-snug group-hover:text-mezenc-brightCyan transition-colors">
                แนวกันชนประชิดแนวป่าสงวน
              </h3>
              <p class="text-xs sm:text-sm text-gray-500 font-light leading-relaxed mb-6">
                แปลงยางพาราที่มีเอกสารสิทธิ์ถูกต้อง แต่ตั้งอยู่ห่างจากแนวเขตป่าสงวนไม่เกิน 500 เมตร ต้องเฝ้าระวังและวิเคราะห์พิกัดไม่ให้ขยายขอบเขตล่วงล้ำแนวป่า
              </p>
            </div>
          </div>

          <!-- Bottom Action Pill Button (Bright Cyan Pill matching Full GIS Map button) -->
          <div class="px-1 sm:px-2 pt-2">
            <button type="button" onclick="openReadMoreModal('🟠', 'ตรวจสอบแนวเขตและแปลงพิกัด (Map GIS)', 'กรุณาเข้าสู่ระบบเพื่อเข้าสู่ระบบแผนที่ GIS สำหรับวาดแปลงพิกัดและตรวจสอบแนวเขตป่าสงวนแห่งชาติ 26 แห่ง พร้อมระบบวิเคราะห์ความเสี่ยงเชิงพื้นที่', 'login.php?redirect=map.php')" class="inline-flex items-center justify-center gap-2.5 bg-mezenc-brightCyan hover:bg-mezenc-teal text-white px-7 sm:px-8 py-3 sm:py-3.5 rounded-full font-bold text-xs sm:text-sm uppercase tracking-wider shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer">
              <span>ตรวจสอบระยะห่าง Buffer ➔</span>
            </button>
          </div>
        </div>

        <!-- CARD 3: SAFE & ZERO DEFORESTATION (พื้นที่ปลอดภัย) -->
        <div class="bg-white rounded-[2rem] sm:rounded-[2.25rem] p-4 sm:p-5 lg:p-6 shadow-[0_16px_40px_-10px_rgba(14,77,78,0.12)] hover:shadow-[0_24px_50px_-10px_rgba(14,77,78,0.22)] border-2 border-[#bee6e1]/80 hover:border-mezenc-brightCyan transition-all duration-300 hover:-translate-y-2 flex flex-col justify-between group">
          <div>
            <!-- Top Nature Image with Smooth Rounded Corners -->
            <div class="h-56 sm:h-60 lg:h-64 rounded-[1.5rem] sm:rounded-[1.75rem] overflow-hidden relative mb-5 shadow-xs">
              <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80" alt="Safe Rubber Farm Surat Thani" class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out">
            </div>

            <!-- Content Area -->
            <div class="px-1 sm:px-2">
              <h3 class="text-lg sm:text-xl font-black text-[#1a3a3a] mb-2 leading-snug group-hover:text-mezenc-brightCyan transition-colors">
                แปลงผ่านเกณฑ์มาตรฐานสากล
              </h3>
              <p class="text-xs sm:text-sm text-gray-500 font-light leading-relaxed mb-6">
                แปลงยางพาราที่มีเอกสารสิทธิ์ถูกต้อง (โฉนด, น.ส.3ก, ส.ป.ก.4-01 ฯลฯ) อยู่นอกแนวป่าสงวน 100% และปลูกก่อนปี 2020 สามารถออกหนังสือรับรอง EUDR ได้ทันที
              </p>
            </div>
          </div>

          <!-- Bottom Action Pill Button (Bright Cyan Pill matching Full GIS Map button) -->
          <div class="px-1 sm:px-2 pt-2">
            <button type="button" onclick="openReadMoreModal('🟢', 'ออกเอกสาร EUDR Passport ดิจิทัล', 'กรุณาเข้าสู่ระบบเพื่อสร้างเอกสารรับรองความสอดคล้องตามมาตรฐาน EUDR พร้อม QR Code สำหรับการตรวจสอบย้อนกลับ (Traceability) 100% สำหรับการส่งออกยางพารา', 'login.php?redirect=trace.php')" class="inline-flex items-center justify-center gap-2.5 bg-mezenc-brightCyan hover:bg-mezenc-teal text-white px-7 sm:px-8 py-3 sm:py-3.5 rounded-full font-bold text-xs sm:text-sm uppercase tracking-wider shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer">
              <span>ออกเอกสาร EUDR Passport ➔</span>
            </button>
          </div>
        </div>

      </div>

    </div>

  </section>

  <!-- =========================================================================
       6. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี)
       ========================================================================= -->
  <footer id="footer-section" class="bg-mezenc-teal text-white pt-12 sm:pt-14 pb-10 border-t border-white/10">
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

  <!-- =========================================================================
       MODAL: ผลการตรวจสอบเอกสารสิทธิ์จำลอง (Simulated Title Deed Result Modal)
       ========================================================================= -->
  <div id="deed-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 transform transition-all scale-95 opacity-0 duration-300" id="deed-modal-box">
      
      <!-- Modal Header -->
      <div id="modal-header-bg" class="p-5 sm:p-6 text-white bg-mezenc-teal relative">
        <button onclick="closeDeedModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-all">
          ✕
        </button>
        <div class="text-xs uppercase tracking-widest font-bold opacity-80" id="modal-subtitle">
          ผลการตรวจสอบเอกสารสิทธิ์เชิงพื้นที่ (ระบบจำลอง)
        </div>
        <h3 class="text-lg sm:text-xl font-extrabold mt-1" id="modal-title">
          โฉนดที่ดินเลขที่ 10425
        </h3>
      </div>

      <!-- Modal Body -->
      <div class="p-5 sm:p-6 space-y-4 text-xs">
        
        <!-- Status Banner -->
        <div id="modal-status-badge" class="p-3.5 sm:p-4 rounded-2xl border flex items-center gap-3">
          <div class="text-2xl" id="modal-status-icon">🟢</div>
          <div>
            <div class="font-bold text-sm" id="modal-status-text">พื้นที่ปลอดภัย (ผ่านเกณฑ์รับรอง 100%)</div>
            <div class="text-gray-500 font-light mt-0.5" id="modal-status-desc">อยู่นอกแนวเขตป่าสงวนแห่งชาติ 26 แห่ง ปลอดการตัดไม้ทำลายป่า</div>
          </div>
        </div>

        <!-- Details Grid -->
        <div class="bg-mezenc-sand p-3.5 sm:p-4 rounded-2xl border border-gray-200 space-y-2">
          <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
            <span class="text-gray-500">ประเภทเอกสาร:</span>
            <span class="font-bold text-gray-800" id="modal-doc-type">โฉนดที่ดิน (น.ส.4 จ) - ข้อมูลจำลอง</span>
          </div>
          <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
            <span class="text-gray-500">ที่ตั้งแปลง:</span>
            <span class="font-bold text-gray-800" id="modal-location">ต.ขุนทะเล อ.เมือง จ.สุราษฎร์ธานี</span>
          </div>
          <div class="flex justify-between border-b border-gray-200/60 pb-1.5">
            <span class="text-gray-500">เนื้อที่คำนวณ:</span>
            <span class="font-bold text-gray-800" id="modal-area">18 ไร่ 2 งาน 50 ตารางวา</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">ระยะห่างป่าสงวนที่ใกล้ที่สุด:</span>
            <span class="font-bold" id="modal-forest-dist">2,450 เมตร (ป่าเขาท่าเพชร)</span>
          </div>
        </div>

        <!-- EUDR Compliance Checklist -->
        <div class="space-y-1.5">
          <div class="font-bold text-gray-700">การประเมินความสอดคล้องตามมาตรฐาน EUDR:</div>
          <div class="flex items-center gap-2 text-gray-600" id="modal-check-1">
            <span>✅</span> <span>พิกัด Polygon WGS84 ครบถ้วน</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600" id="modal-check-2">
            <span>✅</span> <span>ปลอดการตัดไม้ทำลายป่าหลัง 31 ธ.ค. 2020</span>
          </div>
          <div class="flex items-center gap-2 text-gray-600" id="modal-check-3">
            <span>✅</span> <span>เอกสารสิทธิ์ที่ดินถูกต้องตามกฎหมายไทย</span>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-5 sm:p-6 pt-0 flex gap-3">
        <button onclick="closeDeedModal()" class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50 transition-all">
          ปิดหน้าต่าง
        </button>
        <a id="modal-action-btn" href="map.php" class="flex-1 py-2.5 rounded-xl bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-center transition-all shadow">
          เปิดดูบนแผนที่ GIS ➔
        </a>
      </div>

    </div>
  </div>

  <!-- Leaflet JavaScript -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Scripts: Real Map & Interactive Modals -->
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

    // Simulated Deed Database (ข้อมูลโฉนดจำลอง)
    const mockDeeds = {
      '10425': {
        title: 'โฉนดที่ดินเลขที่ 10425 (จำลอง)',
        docType: 'โฉนดที่ดิน (น.ส.4 จ)',
        location: 'แปลงทดลอง ม.อ. ต.ขุนทะเล อ.เมือง จ.สุราษฎร์ธานี',
        area: '18 ไร่ 2 งาน 50 ตารางวา',
        forestDist: '2,450 เมตร (ห่างจากป่าเขาท่าเพชร)',
        status: 'green',
        statusText: '🟢 พื้นที่ปลอดภัย (ผ่านเกณฑ์ 100%)',
        statusDesc: 'อยู่นอกแนวเขตป่าสงวนแห่งชาติ 26 แห่ง และอยู่นอกระยะ Buffer 500 ม.',
        check1: '✅ พิกัด Polygon WGS84 ครบถ้วน',
        check2: '✅ ปลูกก่อนวันที่ 31 ธ.ค. 2020',
        check3: '✅ มีเอกสารสิทธิ์ถูกต้อง สามารถออก EUDR Passport ได้ทันที',
        actionUrl: 'map.php',
        actionText: 'ดูแปลงบนแผนที่ GIS ➔'
      },
      '4589': {
        title: 'น.ส.3ก เลขที่ 4589 (จำลอง)',
        docType: 'หนังสือรับรองการทำประโยชน์ (น.ส.3ก)',
        location: 'หมู่ 4 ต.ทุ่งเตา อ.บ้านนาสาร จ.สุราษฎร์ธานี',
        area: '12 ไร่ 1 งาน 10 ตารางวา',
        forestDist: '320 เมตร (ประชิดแนวป่าเขาพุทธทอง)',
        status: 'orange',
        statusText: '🟠 เขตเฝ้าระวัง (Buffer Zone 500 ม.)',
        statusDesc: 'ตั้งอยู่ใกล้แนวเขตป่าสงวนในระยะ 320 เมตร ต้องเฝ้าระวังไม่ให้ขยายแปลงล่วงล้ำแนวป่า',
        check1: '✅ พิกัด Polygon WGS84 ครบถ้วน',
        check2: '⚠️ ระบบแจ้งเตือนระยะ Buffer Alert 320 ม.',
        check3: '📋 สามารถออก EUDR Passport ได้ แต่ต้องผ่านการตรวจรับรองจุดเลี้ยว',
        actionUrl: 'overview.php',
        actionText: 'วิเคราะห์ Buffer Zone ➔'
      },
      '8409': {
        title: 'โฉนดที่ดินเลขที่ 8409 (จำลอง)',
        docType: 'คำขอออกเอกสารสิทธิ์ (อยู่ระหว่างตรวจสอบ)',
        location: 'หมู่ 8 ต.คลองสก อ.พนม จ.สุราษฎร์ธานี',
        area: '25 ไร่ 0 งาน 00 ตารางวา',
        forestDist: '0 เมตร (ทับซ้อนแนวเขตป่าสงวนคลองสก)',
        status: 'red',
        statusText: '🔴 ห้ามบุกรุกเด็ดขาด (ทับซ้อนแนวป่าสงวน)',
        statusDesc: 'แปลงตั้งอยู่ในเขตป่าสงวนแห่งชาติป่าคลองสก ไม่อนุญาตให้ใช้ประโยชน์และห้ามบุกรุกเด็ดขาด',
        check1: '❌ ทับซ้อนแนวเขตป่าสงวนแห่งชาติ Zone-C',
        check2: '❌ ไม่สอดคล้องตามระเบียบ EUDR Zero Deforestation',
        check3: '⚖️ มีความผิดตาม พ.ร.บ. ป่าสงวนแห่งชาติ พ.ศ. 2507',
        actionUrl: 'overview.php',
        actionText: 'ตรวจสอบแนวเขต 26 ป่าสงวน ➔'
      }
    };

    function handleDeedSearch(e) {
      if (e) e.preventDefault();
      const query = document.getElementById('hero-deed-search').value.trim();
      if (!query) {
        showDeedModal('10425');
        return;
      }

      // Check if matches mock keys
      if (query.includes('10425') || query.includes('ม.อ') || query.includes('ปลอดภัย') || query.includes('เขียว')) {
        showDeedModal('10425');
      } else if (query.includes('4589') || query.includes('กันชน') || query.includes('เฝ้าระวัง') || query.includes('ส้ม')) {
        showDeedModal('4589');
      } else if (query.includes('8409') || query.includes('ป่า') || query.includes('บุกรุก') || query.includes('แดง')) {
        showDeedModal('8409');
      } else {
        // Default simulated result with query in title
        showDeedModal('10425', query);
      }
    }

    function showDeedModal(code, customQuery = null) {
      const data = mockDeeds[code] || mockDeeds['10425'];
      const modal = document.getElementById('deed-modal');
      const box = document.getElementById('deed-modal-box');

      document.getElementById('modal-title').textContent = customQuery ? `เอกสารสิทธิ์: ${customQuery} (จำลอง)` : data.title;
      document.getElementById('modal-doc-type').textContent = data.docType;
      document.getElementById('modal-location').textContent = data.location;
      document.getElementById('modal-area').textContent = data.area;
      document.getElementById('modal-forest-dist').textContent = data.forestDist;
      document.getElementById('modal-status-text').textContent = data.statusText;
      document.getElementById('modal-status-desc').textContent = data.statusDesc;

      document.getElementById('modal-check-1').innerHTML = `<span>${data.check1.slice(0, 2)}</span> <span>${data.check1.slice(2)}</span>`;
      document.getElementById('modal-check-2').innerHTML = `<span>${data.check2.slice(0, 2)}</span> <span>${data.check2.slice(2)}</span>`;
      document.getElementById('modal-check-3').innerHTML = `<span>${data.check3.slice(0, 2)}</span> <span>${data.check3.slice(2)}</span>`;

      const badge = document.getElementById('modal-status-badge');
      const header = document.getElementById('modal-header-bg');
      const actionBtn = document.getElementById('modal-action-btn');

      if (data.status === 'green') {
        badge.className = 'p-4 rounded-2xl border flex items-center gap-3 bg-emerald-50 border-emerald-200 text-emerald-900';
        document.getElementById('modal-status-icon').textContent = '🟢';
        header.className = 'p-5 sm:p-6 text-white bg-emerald-800 relative';
        actionBtn.className = 'flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-center transition-all shadow';
      } else if (data.status === 'orange') {
        badge.className = 'p-4 rounded-2xl border flex items-center gap-3 bg-amber-50 border-amber-200 text-amber-900';
        document.getElementById('modal-status-icon').textContent = '🟠';
        header.className = 'p-5 sm:p-6 text-white bg-amber-700 relative';
        actionBtn.className = 'flex-1 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-center transition-all shadow';
      } else {
        badge.className = 'p-4 rounded-2xl border flex items-center gap-3 bg-red-50 border-red-200 text-red-900';
        document.getElementById('modal-status-icon').textContent = '🔴';
        header.className = 'p-5 sm:p-6 text-white bg-red-800 relative';
        actionBtn.className = 'flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-center transition-all shadow';
      }

      actionBtn.href = data.actionUrl;
      actionBtn.textContent = data.actionText;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
      setTimeout(() => {
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
      }, 10);
    }

    function closeDeedModal() {
      const modal = document.getElementById('deed-modal');
      const box = document.getElementById('deed-modal-box');
      box.classList.remove('scale-100', 'opacity-100');
      box.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
      }, 200);
    }

    // Global variable for Map and Forest Layer
    let miniForestMap = null;
    let geoForestLayer = null;
    let isForestLayerVisible = true;

    function toggleForestLayer() {
      if (!miniForestMap || !geoForestLayer) return;
      const btn = document.getElementById('toggle-forest-layer-btn');
      
      if (isForestLayerVisible) {
        miniForestMap.removeLayer(geoForestLayer);
        isForestLayerVisible = false;
        if (btn) btn.innerHTML = `<span>🌲 แสดงเลเยอร์ป่าสงวน 26 แห่ง</span>`;
      } else {
        miniForestMap.addLayer(geoForestLayer);
        isForestLayerVisible = true;
        if (btn) btn.innerHTML = `<span>🌲 ซ่อนเลเยอร์ป่าสงวน 26 แห่ง</span>`;
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const mapContainer = document.getElementById('surat-real-territory-map');
      if (!mapContainer) return;

      miniForestMap = L.map('surat-real-territory-map', {
        center: [9.05, 99.1],
        zoom: 9,
        zoomControl: true,
        scrollWheelZoom: false
      });

      L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB &copy; OpenStreetMap',
        maxZoom: 18
      }).addTo(miniForestMap);

      const titleEl = document.getElementById('district-title');
      const descEl = document.getElementById('district-desc');
      const forestEl = document.getElementById('district-forest');

      fetch('api/forests.php')
        .then(res => res.json())
        .then(data => {
          if (!data || !data.features) return;

          geoForestLayer = L.geoJSON(data, {
            style: (feature) => ({
              color: '#0e4d4e',
              fillColor: '#00a699',
              fillOpacity: 0.55,
              weight: 1.8
            }),
            onEachFeature: (feature, layer) => {
              const props = feature.properties;
              
              layer.on('mouseover', function(e) {
                this.setStyle({
                  color: '#062627',
                  fillColor: '#5ebbb6',
                  fillOpacity: 0.85,
                  weight: 3
                });

                if (titleEl) titleEl.textContent = props.name_th;
                if (descEl) descEl.textContent = `${props.name_en || ''} • รหัสป่าสงวน: ${props.forest_code} • ${props.category}`;
                if (forestEl) forestEl.textContent = `เนื้อที่คุ้มครองจริง: ${Number(props.area_rai).toLocaleString()} ไร่ • ปลอดการตัดไม้ทำลายป่า 100%`;
              });

              layer.on('mouseout', function(e) {
                if (geoForestLayer) geoForestLayer.resetStyle(this);
              });

              layer.on('click', function(e) {
                miniForestMap.fitBounds(this.getBounds(), { padding: [25, 25] });
              });

              layer.bindPopup(`
                <div style="font-family:'Google Sans', 'Open Sans', 'Sarabun', sans-serif; min-width:180px; padding:3px;">
                  <div style="font-weight:700; color:#0e4d4e; font-size:13px;">🌲 ${props.name_th}</div>
                  <div style="font-size:11px; color:#6b7280; margin-bottom:4px;">${props.name_en || ''}</div>
                  <div style="font-size:11px; line-height:1.5;">
                    <div><strong>รหัส:</strong> ${props.forest_code}</div>
                    <div><strong>เนื้อที่:</strong> ${Number(props.area_rai).toLocaleString()} ไร่</div>
                  </div>
                </div>
              `);
            }
          }).addTo(miniForestMap);

          if (data.features.length > 0) {
            miniForestMap.fitBounds(geoForestLayer.getBounds(), { padding: [10, 10] });
          }
        })
        .catch(err => console.error('Map loading error:', err));
    });

    /* Read More Modal Control Functions */
    const cardData = {
      1: {
        icon: `<svg viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7"><g id="SVGRepo_iconCarrier"><path clip-rule="evenodd" d="M26.4097 9.61208C27.196 8.8358 27.1969 7.57578 26.4117 6.79842L21.1441 1.58305C20.3597 0.806412 19.0875 0.805538 18.302 1.5811L3.55214 16.1442C3.15754 16.5338 2.87982 17.024 2.74985 17.5603L1.05726 24.5451C0.697341 26.0304 2.09375 27.3461 3.57566 26.918L10.3372 24.9646C10.8224 24.8244 11.2642 24.5658 11.622 24.2125L26.4097 9.61208ZM20.4642 12.6725L10.2019 22.8047C10.0827 22.9225 9.9354 23.0087 9.77366 23.0554L4.17079 24.6741C3.65448 24.8232 3.16963 24.359 3.2962 23.8367L4.70476 18.024C4.74809 17.8453 4.84066 17.6819 4.97219 17.552L15.195 7.45865L20.4642 12.6725ZM21.8871 11.2676L16.618 6.05372L19.0185 3.68356C19.4084 3.29865 20.0354 3.29908 20.4247 3.68454L24.271 7.49266C24.6666 7.88436 24.6661 8.52374 24.27 8.91488L21.8871 11.2676Z" fill="#00A896" fill-rule="evenodd"></path></g></svg>`,
        title: 'จัดการข้อมูลเกษตรกรและแปลงปลูก',
        text: 'พัฒนาระบบฐานข้อมูลบนคลาวด์เพื่อรวบรวมข้อมูลทะเบียนเกษตรกร ข้อมูลพื้นที่เพาะปลูก และรายละเอียดแปลงยางพาราให้อยู่ในรูปแบบดิจิทัลอย่างเป็นระบบ ช่วยให้การจัดเก็บ ค้นหา และปรับปรุงข้อมูลทำได้อย่างสะดวกรวดเร็วและมีประสิทธิภาพ',
        link: 'farmers.php'
      },
      2: {
        icon: `<svg viewBox="-3 0 20 20" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="#00A896"><g transform="translate(-183 -5439)" fill="#00A896"><g transform="translate(56 160)"><path d="M134,5287.635 C133.449,5287.635 133,5287.186 133,5286.635 C133,5286.084 133.449,5285.635 134,5285.635 C134.551,5285.635 135,5286.084 135,5286.635 C135,5287.186 134.551,5287.635 134,5287.635 M134,5283.635 C132.343,5283.635 131,5284.978 131,5286.635 C131,5288.292 132.343,5289.635 134,5289.635 C135.657,5289.635 137,5288.292 137,5286.635 C137,5284.978 135.657,5283.635 134,5283.635 M134,5296 C134,5296 129,5289 129,5286 C129,5283.243 131.243,5281 134,5281 C136.757,5281 139,5283.243 139,5286 C139,5289 134,5296 134,5296 M134,5279 C130.134,5279 127,5282.134 127,5286 C127,5289.866 134,5299 134,5299 C134,5299 141,5289.866 141,5286 C141,5282.134 137.866,5279 134,5279"></path></g></g></svg>`,
        title: 'วาดขอบเขตแปลงปลูก',
        text: 'รองรับการบันทึกขอบเขตแปลงปลูกรายแปลงในรูปแบบรูปปิด (Polygon) ผ่าน Interactive Map เพื่อแก้ไขข้อจำกัดของการเก็บพิกัดจุด (Point) แบบเดิมที่ไม่สามารถแสดงพื้นที่จริง เพิ่มความแม่นยำในการตรวจสอบพื้นที่และคำนวณขนาดแปลง',
        link: 'map.php'
      },
      3: {
        icon: `<svg viewBox="-2.96 0 19.945 19.945" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7"><g transform="translate(-4.992 -2.055)"><path fill="#00A896" d="M16.74,12.17A3.66,3.66,0,0,1,17,13.5,3.5,3.5,0,0,1,13.5,17a3.45,3.45,0,0,1-1.5-.35,3.45,3.45,0,0,1-1.5.35A3.5,3.5,0,0,1,7,13.5a3.66,3.66,0,0,1,.26-1.33,3.48,3.48,0,0,1,.81-5.86,4,4,0,0,1,7.86,0,3.48,3.48,0,0,1,.81,5.86Z"></path><path d="M16.74,12.17A3.66,3.66,0,0,1,17,13.5,3.5,3.5,0,0,1,13.5,17a3.45,3.45,0,0,1-1.5-.35,3.45,3.45,0,0,1-1.5.35A3.5,3.5,0,0,1,7,13.5a3.66,3.66,0,0,1,.26-1.33,3.48,3.48,0,0,1,.81-5.86,4,4,0,0,1,7.86,0,3.48,3.48,0,0,1,.81,5.86ZM12,21V11M10,21h4" fill="none" stroke="#00A896" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></g></svg>`,
        title: 'ตรวจสอบการทับซ้อนพื้นที่แปลงปลูก',
        text: 'นำเครื่องมือวิเคราะห์ข้อมูลเชิงพื้นที่ (Spatial Overlay Analysis) มาประยุกต์ใช้เพื่อตรวจสอบการทับซ้อนระหว่างแปลงปลูกยางพารากับแนวเขตป่าสงวน ช่วยประเมินความเสี่ยงและยืนยันว่าแปลงปลูกไม่ได้บุกรุกพื้นที่ป่าอนุรักษ์',
        link: 'overview.php'
      },
      4: {
        icon: `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-7 h-7"><path d="M4 5V19C4 19.5523 4.44772 20 5 20H19" stroke="#00A896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18 9L13 13.9999L10.5 11.4998L7 14.9998" stroke="#00A896" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>`,
        title: 'บันทึกผลผลิตและสนับสนุนการตัดสินใจ',
        text: 'พัฒนาระบบสนับสนุนการตัดสินใจ (Decision Support System: DSS) แสดงผลผ่าน Dashboard สำหรับติดตามและวิเคราะห์ข้อมูลผลผลิตน้ำยางสดอย่างเป็นระบบ ช่วยให้เกษตรกรและผู้เกี่ยวข้องนำข้อมูลไปใช้ประกอบการตัดสินใจและวางแผนการจัดการสวนยางได้อย่างมีประสิทธิภาพ',
        link: 'yields.php'
      },
      5: {
        icon: `<svg fill="#000000" viewBox="0 0 24 24" class="w-7 h-7" xmlns="http://www.w3.org/2000/svg"><path d="M12,13v4a1,1,0,0,1-1,1H7a1,1,0,0,1-1-1V13a1,1,0,0,1,1-1h4A1,1,0,0,1,12,13ZM7,10H9a1,1,0,0,0,1-1V7A1,1,0,0,0,9,6H7A1,1,0,0,0,6,7V9A1,1,0,0,0,7,10Zm10,4H15a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V15A1,1,0,0,0,17,14Zm0-8H13a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1V7A1,1,0,0,0,17,6Z" style="fill: #2ca9bc;"></path><path d="M21,9a1,1,0,0,1-1-1V4H16a1,1,0,0,1,0-2h4a2,2,0,0,1,2,2V8A1,1,0,0,1,21,9Zm1,11V16a1,1,0,0,0-2,0v4H16a1,1,0,0,0,0,2h4A2,2,0,0,0,22,20ZM4,8V4H8A1,1,0,0,0,8,2H4A2,2,0,0,0,2,4V8A1,1,0,0,0,4,8ZM9,21a1,1,0,0,0-1-1H4V16a1,1,0,0,0-2,0v4a2,2,0,0,0,2,2H8A1,1,0,0,0,9,21Z" style="fill: #00A896;"></path></svg>`,
        title: 'ตรวจสอบย้อนกลับตามมาตรฐาน EUDR',
        text: 'สร้างกลไกสนับสนุนการตรวจสอบย้อนกลับ (Traceability) ของผลผลิตประจำแปลงผ่านเทคโนโลยี QR Code เพื่อยืนยันว่าผลผลิตไม่ได้มาจากการตัดไม้ทำลายป่าหรือพื้นที่ผิดกฎหมาย สอดคล้องกับข้อกำหนดของ European Union Deforestation Regulation (EUDR) และมาตรฐานความยั่งยืนสากล',
        link: 'trace.php'
      }
    };

    function openCardModal(cardId) {
      const item = cardData[cardId];
      if (item) {
        openReadMoreModal(item.icon, item.title, item.text, item.link);
      }
    }

    function openReadMoreModal(icon, title, text, link) {
      const modal = document.getElementById('readmore-modal');
      const modalContent = document.getElementById('readmore-modal-content');
      document.getElementById('modal-badge').innerHTML = icon;
      document.getElementById('modal-title').textContent = title;
      document.getElementById('modal-text').textContent = text;
      
      const modalLink = document.getElementById('modal-link');
      if (modalLink) {
        let targetLoginUrl = 'login.php';
        if (link) {
          if (link.startsWith('login.php')) {
            targetLoginUrl = link;
          } else {
            targetLoginUrl = 'login.php?redirect=' + encodeURIComponent(link);
          }
        }
        modalLink.href = targetLoginUrl;
      }
      
      modal.classList.remove('opacity-0', 'pointer-events-none');
      modalContent.classList.remove('scale-95');
      modalContent.classList.add('scale-100');
      document.body.style.overflow = 'hidden';
    }

    function closeReadMoreModal() {
      const modal = document.getElementById('readmore-modal');
      const modalContent = document.getElementById('readmore-modal-content');
      
      modal.classList.add('opacity-0', 'pointer-events-none');
      modalContent.classList.remove('scale-100');
      modalContent.classList.add('scale-95');
      document.body.style.overflow = '';
    }
  </script>

</body>
</html>