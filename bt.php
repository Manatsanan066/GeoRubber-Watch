<?php
/**
 * GeoRubber Watch • Language Switcher Component & Demo (bt.php)
 * Segmented Pill Toggle Switch (TH / EN) with Smooth Sliding Animation
 */
?>
<!DOCTYPE html>
<html lang="th" id="html-root" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ปุ่มสลับภาษา TH / EN • Language Toggle Switch</title>
  
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
            sans: ['"Google Sans"', '"Open Sans"', 'Sarabun', 'sans-serif']
          }
        }
      }
    }
  </script>

  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;
    }

    /* Soft Neumorphism Inset Track matching user's reference image */
    .toggle-track {
      background: #E8EBF0;
      box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.08), inset -2px -2px 5px rgba(255, 255, 255, 0.9);
      border-radius: 9999px;
      position: relative;
      cursor: pointer;
      user-select: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Floating White Pill Indicator with Soft Drop Shadow */
    .toggle-thumb {
      background: #FFFFFF;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.14), 0 1px 3px rgba(0, 0, 0, 0.08);
      border-radius: 9999px;
      position: absolute;
      top: 3px;
      bottom: 3px;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Dark Header Theme Variant */
    .toggle-track-dark {
      background: rgba(255, 255, 255, 0.15);
      box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.3), inset -1px -1px 3px rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .toggle-thumb-dark {
      background: #FFFFFF;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }
  </style>
</head>
<body class="bg-[#F0F2F6] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. TOP NAVBAR WITH INTEGRATED LANGUAGE SWITCHER (Dark Nature Theme)
       ========================================================================= -->
  <header class="bg-mezenc-deepTeal text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 py-3.5 flex justify-between items-center">
      
      <!-- LOGO -->
      <div class="flex items-center gap-2.5 sm:gap-3 cursor-pointer group" onclick="location.href='index.php'">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/30 group-hover:scale-105 transition-transform shadow-md shrink-0 p-1.5">
          <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
          </svg>
        </div>
        <span class="text-lg sm:text-xl font-black tracking-wide text-white drop-shadow truncate">
          GeoRubber Watch
        </span>
      </div>

      <!-- NAVIGATION LINKS (Bilingual) -->
      <nav class="hidden md:flex items-center gap-5 lg:gap-7 text-sm lg:text-base font-medium tracking-wide">
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5" href="index.php" data-i18n="nav_home">
          หน้าแรก
        </a>
        <a class="text-white/80 hover:text-mezenc-mint transition-colors" href="overview.php" data-i18n="nav_gis">
          แผนที่ GIS
        </a>
        <a class="text-white/80 hover:text-mezenc-mint transition-colors" href="dashboard.php" data-i18n="nav_dashboard">
          แดชบอร์ด
        </a>
        <a class="text-white/80 hover:text-mezenc-mint transition-colors" href="map.php" data-i18n="nav_plots">
          แปลงปลูก
        </a>
        <a class="text-white/80 hover:text-mezenc-mint transition-colors" href="yields.php" data-i18n="nav_yields">
          ผลผลิต
        </a>
        <a class="text-white/80 hover:text-mezenc-mint transition-colors" href="contact.php" data-i18n="nav_contact">
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT ACTIONS: NAVBAR LANGUAGE TOGGLE SWITCH -->
      <div class="flex items-center gap-3">
        
        <!-- [NAVBAR PILL TOGGLE] Matching Reference Design -->
        <div 
          onclick="toggleLanguage()"
          class="toggle-track toggle-track-dark w-24 h-9 p-[3px] flex items-center relative cursor-pointer"
          title="คลิกเพื่อสลับภาษา TH / EN"
        >
          <!-- Sliding White Thumb -->
          <div 
            id="nav-thumb" 
            class="toggle-thumb toggle-thumb-dark w-[44px] h-[30px] transition-all duration-300 left-[3px]"
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

        <button onclick="location.href='index.php'" class="px-3.5 py-1.5 text-xs font-bold bg-white/15 hover:bg-white/25 rounded-full border border-white/20 transition-all text-white">
          ← <span data-i18n="btn_back">กลับหน้าหลัก</span>
        </button>

      </div>
    </div>
  </header>

  <!-- =========================================================================
       2. SHOWCASE HEADER: TITLE & SUBTITLE
       ========================================================================= -->
  <main class="w-full max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10 flex-1">
    
    <div class="text-center space-y-3">
      <div class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-mezenc-lightCyan text-mezenc-deepTeal border border-[#bee6e1] text-xs sm:text-sm font-bold tracking-wide shadow-xs">
        <span>🌐</span> <span data-i18n="badge_i18n">ระบบสลับ 2 ภาษา (Bilingual Language Switcher)</span>
      </div>
      <h1 class="text-2xl sm:text-4xl font-extrabold text-gray-900 tracking-tight" data-i18n="page_title">
        ปุ่มสลับภาษา TH / EN ดีไซน์ Segmented Pill Toggle
      </h1>
      <p class="text-sm sm:text-base text-gray-600 max-w-2xl mx-auto" data-i18n="page_desc">
        ออกแบบตามรูปแบบ Soft Pill Slider พร้อมภาพเคลื่อนไหวที่ลื่นไหล เมื่อคลิกสลับภาษา หน้าเว็บจะปรับเปลี่ยนเนื้อหาทั้งหมดระหว่างภาษาไทยและภาษาอังกฤษทันที
      </p>
    </div>

    <!-- =========================================================================
         3. TOGGLE SIZE COMPARISON SHOWCASE (Matching User Image Exact Styles)
         ========================================================================= -->
    <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border border-gray-200/80 space-y-8">
      
      <div class="text-center pb-2 border-b border-gray-100">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" data-i18n="section_styles">
          LANGUAGE SYSTEM TOGGLE • 3 ขนาดตามตัวอย่าง
        </span>
      </div>

      <!-- 3 Sizes Row (DEFAULT, SMALL, LARGE) as in reference image -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-10 items-center justify-items-center pt-2 pb-4">
        
        <!-- 1. DEFAULT SIZE -->
        <div class="flex flex-col items-center gap-3 w-full max-w-[240px]">
          <div 
            onclick="toggleLanguage()"
            class="toggle-track w-48 h-14 p-1 flex items-center relative"
          >
            <!-- Sliding Thumb -->
            <div id="thumb-default" class="toggle-thumb w-[90px] h-[48px] left-1"></div>
            
            <!-- Labels -->
            <div id="lbl-default-th" class="relative z-10 w-1/2 text-center text-base font-bold text-gray-900 transition-colors duration-300 pointer-events-none">
              TH
            </div>
            <div id="lbl-default-en" class="relative z-10 w-1/2 text-center text-base font-medium text-gray-400 transition-colors duration-300 pointer-events-none">
              EN
            </div>
          </div>
          
          <div class="text-center">
            <div class="text-xs text-gray-500 font-medium">
              <span data-i18n="lbl_selected">Selected:</span> <b id="stat-default" class="text-mezenc-deepTeal">TH (ภาษาไทย)</b>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">DEFAULT</span>
          </div>
        </div>

        <!-- 2. SMALL SIZE (With Outline border accent) -->
        <div class="flex flex-col items-center gap-3 w-full max-w-[240px]">
          <div 
            onclick="toggleLanguage()"
            class="toggle-track w-40 h-11 p-1 flex items-center relative border-2 border-blue-400/50 bg-[#E8EEF8]"
          >
            <!-- Sliding Thumb -->
            <div id="thumb-small" class="toggle-thumb w-[72px] h-[36px] left-1 shadow-md"></div>
            
            <!-- Labels -->
            <div id="lbl-small-th" class="relative z-10 w-1/2 text-center text-sm font-bold text-gray-900 transition-colors duration-300 pointer-events-none">
              TH
            </div>
            <div id="lbl-small-en" class="relative z-10 w-1/2 text-center text-sm font-medium text-gray-400 transition-colors duration-300 pointer-events-none">
              EN
            </div>
          </div>
          
          <div class="text-center">
            <div class="text-xs text-gray-500 font-medium">
              <span data-i18n="lbl_selected">Selected:</span> <b id="stat-small" class="text-mezenc-deepTeal">TH</b>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">SMALL</span>
          </div>
        </div>

        <!-- 3. LARGE SIZE -->
        <div class="flex flex-col items-center gap-3 w-full max-w-[280px]">
          <div 
            onclick="toggleLanguage()"
            class="toggle-track w-60 h-16 p-1.5 flex items-center relative"
          >
            <!-- Sliding Thumb -->
            <div id="thumb-large" class="toggle-thumb w-[112px] h-[52px] left-1.5 shadow-lg"></div>
            
            <!-- Labels -->
            <div id="lbl-large-th" class="relative z-10 w-1/2 text-center text-lg font-bold text-gray-900 transition-colors duration-300 pointer-events-none">
              TH
            </div>
            <div id="lbl-large-en" class="relative z-10 w-1/2 text-center text-lg font-medium text-gray-400 transition-colors duration-300 pointer-events-none">
              EN
            </div>
          </div>
          
          <div class="text-center">
            <div class="text-xs text-gray-500 font-medium">
              <span data-i18n="lbl_selected">Selected:</span> <b id="stat-large" class="text-mezenc-deepTeal">TH</b>
            </div>
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">LARGE</span>
          </div>
        </div>

      </div>

    </div>

    <!-- =========================================================================
         4. LIVE INTERACTIVE DEMO: REAL CONTENT CHANGING WITH LANGUAGE
         ========================================================================= -->
    <div class="space-y-6">
      
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-xl sm:text-2xl font-bold text-gray-900" data-i18n="demo_heading">
            ทดสอบการเปลี่ยนเนื้อหาทั้งหน้าเว็บ (Live Content Preview)
          </h2>
          <p class="text-xs sm:text-sm text-gray-500" data-i18n="demo_sub">
            ลองคลิกปุ่มสลับภาษาด้านบน หรือคลิกปุ่มด้านล่าง เพื่อดูการเปลี่ยนภาษาแบบเรียลไทม์
          </p>
        </div>

        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-full border border-gray-200 shadow-xs">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-xs font-semibold text-gray-700">
            <span data-i18n="active_lang_lbl">ภาษาปัจจุบัน:</span> 
            <span id="current-lang-name" class="text-mezenc-deepTeal font-bold">ภาษาไทย (TH)</span>
          </span>
        </div>
      </div>

      <!-- 4 FEATURE CARDS (Bilingual Data) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- CARD 1 -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200/80 shadow-sm hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl mb-4">
            🛰️
          </div>
          <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1" data-i18n="card1_title">
            แผนที่ภูมิสารสนเทศ Web-GIS
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed" data-i18n="card1_desc">
            แสดงชั้นข้อมูลแนวเขตป่าสงวนแห่งชาติ 26 แห่งในสุราษฎร์ธานี รองรับภาพถ่ายดาวเทียมความละเอียดสูง
          </p>
        </div>

        <!-- CARD 2 -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200/80 shadow-sm hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-4">
            🛡️
          </div>
          <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1" data-i18n="card2_title">
            ตรวจเกณฑ์ EUDR อัตโนมัติ
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed" data-i18n="card2_desc">
            ประมวลผลการทับซ้อนและระยะกันชน Buffer Zone 500 เมตร เพื่อยืนยันว่าแปลงยางปลอดการตัดไม้ทำลายป่า
          </p>
        </div>

        <!-- CARD 3 -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200/80 shadow-sm hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mb-4">
            📱
          </div>
          <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1" data-i18n="card3_title">
            พาสปอร์ตดิจิทัล & QR Code
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed" data-i18n="card3_desc">
            ออกใบรับรองแปลงและ QR Code สำหรับตรวจสอบย้อนกลับแหล่งกำเนิดเนื้อยางสดถึงโคนต้น
          </p>
        </div>

        <!-- CARD 4 -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200/80 shadow-sm hover:shadow-md transition-all">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-4">
            📊
          </div>
          <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1" data-i18n="card4_title">
            แดชบอร์ดตัดสินใจ (DSS)
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed" data-i18n="card4_desc">
            สรุปสถิติปริมาณผลผลิต รายได้ และสัดส่วนความเสี่ยงทั้ง 26 ป่าสงวนแบบเรียลไทม์
          </p>
        </div>

      </div>

      <!-- SAMPLE DATA TABLE (Bilingual) -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-200/80 shadow-sm space-y-4">
        
        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
          <h3 class="text-base sm:text-lg font-bold text-gray-900 flex items-center gap-2">
            <span>📋</span> <span data-i18n="table_title">ตัวอย่างข้อมูลแปลงปลูกยางพารา</span>
          </h3>
          <span class="text-xs font-medium text-gray-500" data-i18n="table_note">
            อัปเดตสถานะอัตโนมัติตามภาษาที่เลือก
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm">
            <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
              <tr>
                <th class="py-3 px-4" data-i18n="th_code">รหัสแปลง</th>
                <th class="py-3 px-4" data-i18n="th_name">ชื่อแปลง / เจ้าของ</th>
                <th class="py-3 px-4" data-i18n="th_deed">เอกสารสิทธิ์</th>
                <th class="py-3 px-4" data-i18n="th_area">เนื้อที่</th>
                <th class="py-3 px-4" data-i18n="th_clone">พันธุ์ยาง</th>
                <th class="py-3 px-4" data-i18n="th_status">สถานะ EUDR</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-800">
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-mezenc-deepTeal">PLT-ST-001</td>
                <td class="py-3.5 px-4 font-medium" data-i18n="row1_name">สวนยางแปลงเขาพลอง • อ.เมือง</td>
                <td class="py-3.5 px-4" data-i18n="row1_deed">โฉนดที่ดิน (น.ส. 4 จ) #45892</td>
                <td class="py-3.5 px-4" data-i18n="row1_area">8 ไร่ 2 งาน (1.36 เฮกตาร์)</td>
                <td class="py-3.5 px-4">RRIM 600</td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span data-i18n="status_compliant">ผ่านเกณฑ์ (Compliant)</span>
                  </span>
                </td>
              </tr>
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-mezenc-deepTeal">PLT-ST-002</td>
                <td class="py-3.5 px-4 font-medium" data-i18n="row2_name">สวนยางบ้านขุนทะเล • อ.เมือง</td>
                <td class="py-3.5 px-4" data-i18n="row2_deed">โฉนดที่ดิน (น.ส. 4 จ) #10425</td>
                <td class="py-3.5 px-4" data-i18n="row2_area">12 ไร่ 0 งาน (1.92 เฮกตาร์)</td>
                <td class="py-3.5 px-4">RRIT 251</td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                    <span data-i18n="status_buffer">โซนเฝ้าระวัง 500ม.</span>
                  </span>
                </td>
              </tr>
              <tr class="hover:bg-gray-50/80 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-mezenc-deepTeal">PLT-ST-003</td>
                <td class="py-3.5 px-4 font-medium" data-i18n="row3_name">สวนยางพนม-คลองชะอุ่น • อ.พนม</td>
                <td class="py-3.5 px-4" data-i18n="row3_deed">ส.ป.ก. 4-01 #8409</td>
                <td class="py-3.5 px-4" data-i18n="row3_area">15 ไร่ 1 งาน (2.44 เฮกตาร์)</td>
                <td class="py-3.5 px-4">PB 235</td>
                <td class="py-3.5 px-4">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 text-xs font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                    <span data-i18n="status_non_compliant">ทับซ้อนเขตป่า (Non-Compliant)</span>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

    </div>

  </main>

  <!-- =========================================================================
       5. FOOTER
       ========================================================================= -->
  <footer class="bg-white border-t border-gray-200 mt-12 py-6 text-center text-xs text-gray-500">
    <div class="max-w-[1440px] mx-auto px-4 space-y-1">
      <p data-i18n="footer_rights">
        GeoRubber Watch • แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา
      </p>
      <p class="text-gray-400" data-i18n="footer_uni">
        มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี (PSU Surat Thani)
      </p>
    </div>
  </footer>

  <!-- =========================================================================
       6. JAVASCRIPT: BILINGUAL I18N ENGINE & PILL TOGGLE ANIMATION
       ========================================================================= -->
  <script>
    // Bilingual Dictionary (พจนานุกรมแปล 2 ภาษา)
    const i18n = {
      th: {
        // Navbar
        nav_home: "หน้าแรก",
        nav_gis: "แผนที่ GIS",
        nav_dashboard: "แดชบอร์ด",
        nav_plots: "แปลงปลูก",
        nav_yields: "ผลผลิต",
        nav_contact: "ติดต่อเรา",
        btn_back: "กลับหน้าหลัก",

        // Header
        badge_i18n: "ระบบสลับ 2 ภาษา (Bilingual Language Switcher)",
        page_title: "ปุ่มสลับภาษา TH / EN ดีไซน์ Segmented Pill Toggle",
        page_desc: "ออกแบบตามรูปแบบ Soft Pill Slider พร้อมภาพเคลื่อนไหวที่ลื่นไหล เมื่อคลิกสลับภาษา หน้าเว็บจะปรับเปลี่ยนเนื้อหาทั้งหมดระหว่างภาษาไทยและภาษาอังกฤษทันที",
        section_styles: "LANGUAGE SYSTEM TOGGLE • 3 ขนาดตามตัวอย่าง",
        lbl_selected: "เลือกอยู่:",

        // Demo section
        demo_heading: "ทดสอบการเปลี่ยนเนื้อหาทั้งหน้าเว็บ (Live Content Preview)",
        demo_sub: "ลองคลิกปุ่มสลับภาษาด้านบน หรือคลิกปุ่มด้านล่าง เพื่อดูการเปลี่ยนภาษาแบบเรียลไทม์",
        active_lang_lbl: "ภาษาปัจจุบัน:",
        current_lang_display: "ภาษาไทย (TH)",

        // Cards
        card1_title: "แผนที่ภูมิสารสนเทศ Web-GIS",
        card1_desc: "แสดงชั้นข้อมูลแนวเขตป่าสงวนแห่งชาติ 26 แห่งในสุราษฎร์ธานี รองรับภาพถ่ายดาวเทียมความละเอียดสูง",
        card2_title: "ตรวจเกณฑ์ EUDR อัตโนมัติ",
        card2_desc: "ประมวลผลการทับซ้อนและระยะกันชน Buffer Zone 500 เมตร เพื่อยืนยันว่าแปลงยางปลอดการตัดไม้ทำลายป่า",
        card3_title: "พาสปอร์ตดิจิทัล & QR Code",
        card3_desc: "ออกใบรับรองแปลงและ QR Code สำหรับตรวจสอบย้อนกลับแหล่งกำเนิดเนื้อยางสดถึงโคนต้น",
        card4_title: "แดชบอร์ดตัดสินใจ (DSS)",
        card4_desc: "สรุปสถิติปริมาณผลผลิต รายได้ และสัดส่วนความเสี่ยงทั้ง 26 ป่าสงวนแบบเรียลไทม์",

        // Table
        table_title: "ตัวอย่างข้อมูลแปลงปลูกยางพารา",
        table_note: "อัปเดตสถานะอัตโนมัติตามภาษาที่เลือก",
        th_code: "รหัสแปลง",
        th_name: "ชื่อแปลง / เจ้าของ",
        th_deed: "เอกสารสิทธิ์",
        th_area: "เนื้อที่",
        th_clone: "พันธุ์ยาง",
        th_status: "สถานะ EUDR",

        row1_name: "สวนยางแปลงเขาพลอง • อ.เมือง",
        row1_deed: "โฉนดที่ดิน (น.ส. 4 จ) #45892",
        row1_area: "8 ไร่ 2 งาน (1.36 เฮกตาร์)",

        row2_name: "สวนยางบ้านขุนทะเล • อ.เมือง",
        row2_deed: "โฉนดที่ดิน (น.ส. 4 จ) #10425",
        row2_area: "12 ไร่ 0 งาน (1.92 เฮกตาร์)",

        row3_name: "สวนยางพนม-คลองชะอุ่น • อ.พนม",
        row3_deed: "ส.ป.ก. 4-01 #8409",
        row3_area: "15 ไร่ 1 งาน (2.44 เฮกตาร์)",

        status_compliant: "ผ่านเกณฑ์ (Compliant)",
        status_buffer: "โซนเฝ้าระวัง 500ม.",
        status_non_compliant: "ทับซ้อนเขตป่า (Non-Compliant)",

        // Footer
        footer_rights: "GeoRubber Watch • แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา",
        footer_uni: "มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี (PSU Surat Thani)"
      },

      en: {
        // Navbar
        nav_home: "Home",
        nav_gis: "GIS Map",
        nav_dashboard: "Dashboard",
        nav_plots: "Plantation Plots",
        nav_yields: "Yields & Latex",
        nav_contact: "Contact Us",
        btn_back: "Back to Home",

        // Header
        badge_i18n: "Bilingual Language Switcher (TH / EN)",
        page_title: "Language Switcher (TH / EN) Segmented Pill Toggle",
        page_desc: "Engineered with smooth animated pill slider styling. Clicking to switch instantly translates all website content seamlessly between Thai and English.",
        section_styles: "LANGUAGE SYSTEM TOGGLE • 3 Reference Sizing Options",
        lbl_selected: "Selected:",

        // Demo section
        demo_heading: "Live Bilingual Webpage Preview",
        demo_sub: "Click the toggle button in the navbar or below to experience instant real-time translation.",
        active_lang_lbl: "Active Language:",
        current_lang_display: "English (EN)",

        // Cards
        card1_title: "Web-GIS Spatial Map",
        card1_desc: "Visualizes 26 National Forest Reserve boundaries in Surat Thani overlaid on ultra-high-resolution satellite imagery.",
        card2_title: "Automated EUDR Compliance",
        card2_desc: "Real-time spatial polygon intersection & 500m buffer zone analysis ensuring zero-deforestation certification.",
        card3_title: "Digital Passport & QR Code",
        card3_desc: "Issues unique EUDR traceability QR tokens verifying farm origin directly back to individual rubber tree coordinates.",
        card4_title: "Decision Support (DSS)",
        card4_desc: "Aggregates farm yields, latex revenue, and risk compliance statistics across all 26 forest reserves in real-time.",

        // Table
        table_title: "Rubber Plantation Registry Sample",
        table_note: "Content dynamically updates with selected language",
        th_code: "Plot Code",
        th_name: "Plot Name / Owner",
        th_deed: "Title Deed",
        th_area: "Total Area",
        th_clone: "Rubber Clone",
        th_status: "EUDR Status",

        row1_name: "Khao Phlong Farm • Mueang District",
        row1_deed: "Title Deed (Nor.Sor. 4 Jor) #45892",
        row1_area: "8.5 Rai (1.36 Hectares)",

        row2_name: "Khun Thale Farm • Mueang District",
        row2_deed: "Title Deed (Nor.Sor. 4 Jor) #10425",
        row2_area: "12.0 Rai (1.92 Hectares)",

        row3_name: "Phanom-Khlong Cha-un • Phanom District",
        row3_deed: "ALRO Land (Sor.Por.Kor. 4-01) #8409",
        row3_area: "15.25 Rai (2.44 Hectares)",

        status_compliant: "EUDR Compliant",
        status_buffer: "Buffer Zone 500m",
        status_non_compliant: "Forest Overlap (Non-Compliant)",

        // Footer
        footer_rights: "GeoRubber Watch • Intelligent Geospatial Platform for Rubber Monitoring & Deforestation Due Diligence",
        footer_uni: "Prince of Songkla University, Surat Thani Campus"
      }
    };

    // State
    let currentLang = localStorage.getItem('georubber_lang') || 'th';

    // Apply Language Function
    function setLanguage(lang) {
      currentLang = lang;
      localStorage.setItem('georubber_lang', lang);
      document.getElementById('html-root').setAttribute('lang', lang);

      // Update all data-i18n DOM elements
      document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (i18n[lang] && i18n[lang][key]) {
          el.textContent = i18n[lang][key];
        }
      });

      // Update current lang text
      const currentLangDisplay = document.getElementById('current-lang-name');
      if (currentLangDisplay) {
        currentLangDisplay.textContent = lang === 'th' ? 'ภาษาไทย (TH)' : 'English (EN)';
      }

      // Update Slider Visual Positions across all 4 Toggles
      updateToggleVisuals(lang);
    }

    // Toggle between TH and EN
    function toggleLanguage() {
      const nextLang = currentLang === 'th' ? 'en' : 'th';
      setLanguage(nextLang);
    }

    // Update Slider Thumbs and Text Active Classes
    function updateToggleVisuals(lang) {
      const isTH = lang === 'th';

      // 1. NAVBAR TOGGLE
      const navThumb = document.getElementById('nav-thumb');
      const navTh = document.getElementById('nav-label-th');
      const navEn = document.getElementById('nav-label-en');
      if (navThumb && navTh && navEn) {
        if (isTH) {
          navThumb.style.left = '3px';
          navTh.className = "relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none";
          navEn.className = "relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none";
        } else {
          navThumb.style.left = '47px';
          navTh.className = "relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none";
          navEn.className = "relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none";
        }
      }

      // 2. DEFAULT SIZE TOGGLE
      const thumbDefault = document.getElementById('thumb-default');
      const lblDefTh = document.getElementById('lbl-default-th');
      const lblDefEn = document.getElementById('lbl-default-en');
      const statDef = document.getElementById('stat-default');
      if (thumbDefault && lblDefTh && lblDefEn && statDef) {
        if (isTH) {
          thumbDefault.style.left = '4px';
          lblDefTh.className = "relative z-10 w-1/2 text-center text-base font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          lblDefEn.className = "relative z-10 w-1/2 text-center text-base font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          statDef.textContent = "TH (ภาษาไทย)";
        } else {
          thumbDefault.style.left = '96px';
          lblDefTh.className = "relative z-10 w-1/2 text-center text-base font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          lblDefEn.className = "relative z-10 w-1/2 text-center text-base font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          statDef.textContent = "EN (English)";
        }
      }

      // 3. SMALL SIZE TOGGLE
      const thumbSmall = document.getElementById('thumb-small');
      const lblSmallTh = document.getElementById('lbl-small-th');
      const lblSmallEn = document.getElementById('lbl-small-en');
      const statSmall = document.getElementById('stat-small');
      if (thumbSmall && lblSmallTh && lblSmallEn && statSmall) {
        if (isTH) {
          thumbSmall.style.left = '4px';
          lblSmallTh.className = "relative z-10 w-1/2 text-center text-sm font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          lblSmallEn.className = "relative z-10 w-1/2 text-center text-sm font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          statSmall.textContent = "TH";
        } else {
          thumbSmall.style.left = '80px';
          lblSmallTh.className = "relative z-10 w-1/2 text-center text-sm font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          lblSmallEn.className = "relative z-10 w-1/2 text-center text-sm font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          statSmall.textContent = "EN";
        }
      }

      // 4. LARGE SIZE TOGGLE
      const thumbLarge = document.getElementById('thumb-large');
      const lblLargeTh = document.getElementById('lbl-large-th');
      const lblLargeEn = document.getElementById('lbl-large-en');
      const statLarge = document.getElementById('stat-large');
      if (thumbLarge && lblLargeTh && lblLargeEn && statLarge) {
        if (isTH) {
          thumbLarge.style.left = '6px';
          lblLargeTh.className = "relative z-10 w-1/2 text-center text-lg font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          lblLargeEn.className = "relative z-10 w-1/2 text-center text-lg font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          statLarge.textContent = "TH";
        } else {
          thumbLarge.style.left = '120px';
          lblLargeTh.className = "relative z-10 w-1/2 text-center text-lg font-medium text-gray-400 transition-colors duration-300 pointer-events-none";
          lblLargeEn.className = "relative z-10 w-1/2 text-center text-lg font-bold text-gray-900 transition-colors duration-300 pointer-events-none";
          statLarge.textContent = "EN";
        }
      }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
      setLanguage(currentLang);
    });
  </script>

</body>
</html>
