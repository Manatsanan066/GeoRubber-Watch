<?php
require_once __DIR__ . '/includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>GeoRubber Watch - ระบบภูมิสารสนเทศอัจฉริยะติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี</title>
  
  <!-- Google Fonts: Plus Jakarta Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'Sarabun', 'sans-serif'],
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
            },
            brand: {
              dark: '#111827',
              cardDark: '#171717',
              grayBg: '#f3f4f6',
              grayLight: '#f9fafb',
              border: '#e5e7eb',
              teal: '#0e4d4e',
              mint: '#5ebbb6',
              cyan: '#00a699',
              accent: '#00A896',
              emerald: '#10b981',
            }
          },
          borderRadius: {
            '3xl': '1.75rem',
            '4xl': '2.25rem',
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Plus Jakarta Sans', 'Sarabun', sans-serif;
      background-color: #f4f5f7;
      color: #1a332c;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    .tab-pill.active {
      background-color: #0e4d4e;
      color: #ffffff;
    }

    .tab-pill {
      background-color: #ffffff;
      color: #4b5563;
      border: 1px solid #e5e7eb;
      transition: all 0.2s ease;
    }
    .tab-pill:hover {
      background-color: #e6f7f6;
      color: #0e4d4e;
      border-color: #bee6e1;
    }

    /* Hover lift animation */
    .hover-lift {
      transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .hover-lift:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 32px -8px rgba(14, 77, 78, 0.12);
    }
  </style>
</head>
<body class="bg-[#f4f5f7] text-[#1a332c] antialiased">

  <!-- =========================================================================
       PAGE CONTAINER (Match Figma / Template Max Width)
       ========================================================================= -->
  <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 sm:space-y-8">

    <!-- =========================================================================
         0. NAVBAR (Minimalist Clean Header matching index.php branding)
         ========================================================================= -->
    <header class="bg-white/95 backdrop-blur-md rounded-3xl px-6 py-4 border-2 border-[#bee6e1] shadow-[0_10px_25px_-5px_rgba(14,77,78,0.08)] flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-mezenc-teal text-white flex items-center justify-center font-bold text-xl shadow-xs">
          🌲
        </div>
        <div>
          <span class="font-extrabold text-lg sm:text-xl text-mezenc-teal tracking-tight block leading-tight">GeoRubber Watch</span>
          <span class="text-xs text-gray-500 font-medium">สุราษฎร์ธานี • EUDR GIS Platform</span>
        </div>
      </div>

      <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-600">
        <a href="#hero" class="text-mezenc-teal font-bold hover:text-mezenc-brightCyan transition-colors">หน้าแรก</a>
        <a href="#plots" class="hover:text-mezenc-brightCyan transition-colors">แปลงปลูก</a>
        <a href="#guide" class="hover:text-mezenc-brightCyan transition-colors">คู่มือการใช้งาน</a>
        <a href="#reviews" class="hover:text-mezenc-brightCyan transition-colors">เสียงตอบรับ</a>
        <a href="#stats" class="hover:text-mezenc-brightCyan transition-colors">สถิติ EUDR</a>
        <a href="#blogs" class="hover:text-mezenc-brightCyan transition-colors">บทความ</a>
        <a href="#services" class="hover:text-mezenc-brightCyan transition-colors">บริการ</a>
      </nav>

      <div class="flex items-center gap-3">
        <a href="index.php" class="px-4 py-2 rounded-full bg-mezenc-lightCyan hover:bg-mezenc-teal hover:text-white text-mezenc-teal text-xs sm:text-sm font-bold border border-[#bee6e1] transition-all">
          ไปหน้าหลัก ➔
        </a>
      </div>
    </header>

    <!-- =========================================================================
         1. HERO SECTION (Transformed with index.php Luxury Landscape & Grand Typography)
         ========================================================================= -->
    <section id="hero" class="bg-mezenc-darkNavy text-white rounded-4xl p-6 sm:p-10 lg:p-14 relative overflow-hidden shadow-[0_25px_50px_-12px_rgba(14,77,78,0.35)] border-2 border-[#bee6e1]/30">
      
      <!-- Background Image with Dark Overlay from index.php -->
      <div class="absolute inset-0 z-0 overflow-hidden">
        <img 
          src="ปก.png" 
          alt="Surat Thani Landscape" 
          class="w-full h-full object-cover object-center filter brightness-90 contrast-105 scale-105" 
          onerror="this.onerror=null; this.src='img/map_rubber_hero.jpg';"
        >
        <!-- Deep Gradient Overlay from index.php -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/60 to-black/35"></div>
        <div class="absolute inset-0 bg-mezenc-darkNavy/40 mix-blend-multiply"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center relative z-10">
        
        <!-- Left Content with index.php Grand Typography & Frosted Search -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Frosted Badge -->
          <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/15 text-mezenc-mint text-xs sm:text-sm font-bold backdrop-blur-md border border-white/20 shadow-sm">
            <span>🌲</span>
            <span>โครงการวิจัยระบบภูมิสารสนเทศ • ม.อ. สุราษฎร์ธานี</span>
          </div>

          <!-- Grand Headline from index.php -->
          <h1 class="font-sans font-black tracking-tight text-white leading-none space-y-1">
            <span class="block text-3xl sm:text-4xl md:text-5xl lg:text-6xl uppercase tracking-tight text-white drop-shadow-md">
              THE FUTURE OF
            </span>
            <span class="block text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold tracking-[0.14em] sm:tracking-[0.20em] text-mezenc-mint mt-2 drop-shadow">
              SUSTAINABLE RUBBER
            </span>
          </h1>

          <!-- Subtitle from index.php -->
          <p class="text-xs sm:text-sm md:text-base text-slate-200/95 font-light leading-relaxed max-w-xl drop-shadow">
            ยกระดับการจัดการสวนยางพาราด้วยเทคโนโลยี GIS และดาวเทียม เพื่อความยั่งยืนและการปฏิบัติตามมาตรฐาน EUDR อย่างครบวงจร ตรวจสอบ 26 แนวเขตป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี
          </p>

          <!-- Frosted Glass Search Bar from index.php -->
          <div class="max-w-xl relative shadow-lg rounded-full">
            <form onsubmit="event.preventDefault(); window.location.href='map.php';" class="relative flex items-center">
              <input 
                type="text" 
                placeholder="ระบุเลขที่โฉนด, น.ส.3ก หรือรหัสแปลงปลูก..." 
                class="w-full pl-5 sm:pl-6 pr-28 sm:pr-32 py-3 rounded-full bg-white/20 hover:bg-white/25 backdrop-blur-md border border-white/30 text-white placeholder-white/75 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-mezenc-mint/80 focus:bg-white/30 focus:border-white/50 shadow-inner font-light transition-all"
              >
              <button 
                type="submit" 
                class="absolute right-1.5 top-1.5 bottom-1.5 px-4 sm:px-5 bg-mezenc-brightCyan hover:bg-mezenc-teal text-white rounded-full flex items-center gap-1.5 font-bold text-xs uppercase tracking-wider transition-all shadow-md border border-white/20 cursor-pointer"
              >
                <span>ตรวจสอบ</span>
                <svg class="w-3.5 h-3.5 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path></svg>
              </button>
            </form>
          </div>

          <!-- Buttons -->
          <div class="flex flex-wrap items-center gap-3 pt-1">
            <a href="map.php" class="px-6 py-3 rounded-full bg-white text-mezenc-teal hover:bg-mezenc-lightCyan font-bold text-xs sm:text-sm transition-all shadow-md hover:scale-105">
              🗺️ สำรวจแปลงปลูกบนแผนที่ GIS
            </a>
            <a href="#guide" class="px-6 py-3 rounded-full bg-white/15 hover:bg-white/25 text-white font-bold text-xs sm:text-sm border border-white/25 transition-all backdrop-blur-md">
              📖 คู่มือการใช้งาน EUDR
            </a>
          </div>

        </div>

        <!-- Right Hero Interactive Badge Card -->
        <div class="lg:col-span-5 relative">
          <div class="w-full h-72 sm:h-80 lg:h-96 rounded-3xl overflow-hidden bg-black/40 border-2 border-white/20 shadow-2xl relative group backdrop-blur-xs">
            <img 
              src="img/forest_reserve_hero.jpg" 
              alt="Surat Thani Forest Reserve" 
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 filter brightness-95" 
              onerror="this.onerror=null; this.src='ปก.png';"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            
            <!-- Frosted Bottom Info Card -->
            <div class="absolute bottom-4 left-4 right-4 p-4 bg-white/15 backdrop-blur-md rounded-2xl border border-white/20 text-white space-y-1.5 shadow-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs text-mezenc-mint font-bold uppercase tracking-wider flex items-center gap-1.5">
                  <span>🌲</span> <span>SURAT THANI PILOT AREA</span>
                </span>
                <span class="text-[11px] px-2.5 py-0.5 rounded-full bg-emerald-500/30 text-emerald-300 border border-emerald-400/40 font-bold">
                  ✅ 100% EUDR
                </span>
              </div>
              <strong class="text-sm sm:text-base font-extrabold block text-white leading-snug">
                26 ผืนป่าสงวนแห่งชาติ • 784,618 ไร่
              </strong>
              <p class="text-[11px] text-white/80 font-light leading-tight">
                ฐานข้อมูลแนวเขตป่าสงวนและการตรวจสอบย้อนกลับ (Traceability) กรมป่าไม้
              </p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- =========================================================================
         2. HORIZONTAL FEATURE PILLS BAR (3 Pillars)
         ========================================================================= -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      
      <!-- Pill 1 -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border-2 border-[#bee6e1] shadow-[0_10px_25px_-5px_rgba(14,77,78,0.06)] flex items-center gap-4 hover-lift">
        <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1]">
          🌲
        </div>
        <div>
          <h4 class="font-extrabold text-sm text-mezenc-teal leading-tight">Zero Deforestation</h4>
          <p class="text-xs text-gray-500 mt-0.5">ปลอดตัดไม้ทำลายป่า ตรวจสอบ 26 ป่าสงวน</p>
        </div>
      </div>

      <!-- Pill 2 -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border-2 border-[#bee6e1] shadow-[0_10px_25px_-5px_rgba(14,77,78,0.06)] flex items-center gap-4 hover-lift">
        <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1]">
          🛰️
        </div>
        <div>
          <h4 class="font-extrabold text-sm text-mezenc-teal leading-tight">GIS & Satellite Analysis</h4>
          <p class="text-xs text-gray-500 mt-0.5">พิกัดดาวเทียม & Polygon เชิงพื้นที่แม่นยำ</p>
        </div>
      </div>

      <!-- Pill 3 -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border-2 border-[#bee6e1] shadow-[0_10px_25px_-5px_rgba(14,77,78,0.06)] flex items-center gap-4 hover-lift">
        <div class="w-12 h-12 rounded-2xl bg-mezenc-lightCyan text-mezenc-teal flex items-center justify-center text-2xl shrink-0 border border-[#bee6e1]">
          📱
        </div>
        <div>
          <h4 class="font-extrabold text-sm text-mezenc-teal leading-tight">Digital Traceability</h4>
          <p class="text-xs text-gray-500 mt-0.5">QR Code หนังสือรับรอง EUDR ประจำแปลง</p>
        </div>
      </div>

    </section>

    <!-- =========================================================================
         3. FEATURED RUBBER PLOTS (Property / Plot Listing Section with Tabs)
         ========================================================================= -->
    <section id="plots" class="bg-white rounded-4xl p-6 sm:p-8 lg:p-10 border-2 border-[#bee6e1] shadow-[0_20px_45px_-10px_rgba(14,77,78,0.12)] space-y-6">
      
      <!-- Section Header with Tabs & Navigation Buttons -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100">
        
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
          <button class="tab-pill active px-4 py-2 rounded-full text-xs font-bold shadow-xs">
            ทั้งหมด (All)
          </button>
          <button class="tab-pill px-4 py-2 rounded-full text-xs font-bold">
            🟢 ผ่าน EUDR
          </button>
          <button class="tab-pill px-4 py-2 rounded-full text-xs font-bold">
            🟠 โซนเฝ้าระวัง
          </button>
          <button class="tab-pill px-4 py-2 rounded-full text-xs font-bold">
            🔴 ทับซ้อนป่า
          </button>
        </div>

        <!-- Carousel Arrows -->
        <div class="flex items-center gap-2 self-end md:self-auto">
          <button class="w-9 h-9 rounded-full bg-mezenc-lightCyan hover:bg-mezenc-teal hover:text-white text-mezenc-teal flex items-center justify-center font-bold text-sm border border-[#bee6e1] transition-all">
            ‹
          </button>
          <button class="w-9 h-9 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white flex items-center justify-center font-bold text-sm transition-all shadow-xs">
            ›
          </button>
        </div>

      </div>

      <!-- 3 Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Plot Card 1 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift flex flex-col justify-between p-4 space-y-4 hover:border-mezenc-brightCyan">
          <!-- Thumbnail -->
          <div class="w-full h-48 rounded-2xl overflow-hidden bg-gray-100 relative">
            <img src="img/map_rubber_hero.jpg" alt="Plot E" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold shadow-xs">
              🟢 EUDR ผ่านเกณฑ์
            </span>
          </div>

          <!-- Info -->
          <div class="space-y-1.5">
            <h4 class="font-extrabold text-base text-mezenc-teal leading-snug">สวนยางขุนทะเลอินทรีย์ (Plot E)</h4>
            <p class="text-xs text-mezenc-brightCyan font-mono font-bold">RB-ST-2026-005</p>
            <p class="text-xs text-gray-600 leading-relaxed pt-1">
              📐 13 ไร่ 2 งาน 10 ตร.ว. • 👨‍🌾 สมหมาย ขายของ<br>
              🌱 RRIM 600 (ปลูก 2016) • 🌳 1,020 ต้น
            </p>
          </div>

          <!-- Footer Action -->
          <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] text-gray-400 uppercase font-bold block">ผลผลิตเฉลี่ย</span>
              <strong class="text-sm font-extrabold text-mezenc-teal">420 กก./เดือน</strong>
            </div>
            <a href="trace.php?token=EUDR-TH-ST-84000-005-E5B77" target="_blank" class="px-4 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white text-xs font-bold transition-all shadow-xs">
              View EUDR
            </a>
          </div>
        </div>

        <!-- Plot Card 2 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift flex flex-col justify-between p-4 space-y-4 hover:border-mezenc-brightCyan">
          <!-- Thumbnail -->
          <div class="w-full h-48 rounded-2xl overflow-hidden bg-gray-100 relative">
            <img src="img/forest_reserve_hero.jpg" alt="Plot A" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-emerald-600 text-white text-xs font-bold shadow-xs">
              🟢 EUDR ผ่านเกณฑ์
            </span>
          </div>

          <!-- Info -->
          <div class="space-y-1.5">
            <h4 class="font-extrabold text-base text-mezenc-teal leading-snug">สวนยางกาญจนดิษฐ์ร่มเย็น (Plot A)</h4>
            <p class="text-xs text-mezenc-brightCyan font-mono font-bold">RB-ST-2026-001</p>
            <p class="text-xs text-gray-600 leading-relaxed pt-1">
              📐 18 ไร่ 3 งาน 40 ตร.ว. • 👨‍🌾 นายประสิทธิ์ ยางทอง<br>
              🌱 RRIM 600 (ปลูก 2017) • 🌳 1,400 ต้น
            </p>
          </div>

          <!-- Footer Action -->
          <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] text-gray-400 uppercase font-bold block">ผลผลิตเฉลี่ย</span>
              <strong class="text-sm font-extrabold text-mezenc-teal">580 กก./เดือน</strong>
            </div>
            <a href="map.php" class="px-4 py-2 rounded-full bg-mezenc-brightCyan hover:bg-mezenc-teal text-white text-xs font-bold transition-all shadow-xs">
              View EUDR
            </a>
          </div>
        </div>

        <!-- Plot Card 3 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift flex flex-col justify-between p-4 space-y-4 hover:border-mezenc-brightCyan">
          <!-- Thumbnail -->
          <div class="w-full h-48 rounded-2xl overflow-hidden bg-gray-100 relative">
            <img src="img/dss_analytics_hero.jpg" alt="Plot D" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-rose-600 text-white text-xs font-bold shadow-xs">
              🔴 ทับซ้อนป่า
            </span>
          </div>

          <!-- Info -->
          <div class="space-y-1.5">
            <h4 class="font-extrabold text-base text-mezenc-teal leading-snug">สวนยางชายขอบเขาท่าเพชร (Plot D)</h4>
            <p class="text-xs text-rose-600 font-mono font-bold">RB-ST-2026-004</p>
            <p class="text-xs text-gray-600 leading-relaxed pt-1">
              📐 14 ไร่ 0 งาน 80 ตร.ว. • 👨‍🌾 น.ส.มนัสนันท์ อนันตณรงค์<br>
              🌱 BPM 24 (ปลูก 2021) • 🌳 1,050 ต้น
            </p>
          </div>

          <!-- Footer Action -->
          <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            <div>
              <span class="text-[10px] text-gray-400 uppercase font-bold block">สถานะตรวจสอบ</span>
              <strong class="text-sm font-extrabold text-rose-600">Buffer Alert 120m</strong>
            </div>
            <a href="map.php" class="px-4 py-2 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-xs">
              View EUDR
            </a>
          </div>
        </div>

      </div>

    </section>

    <!-- =========================================================================
         4. USER GUIDE FOR FIRST TIMER (2-Column Guide Section)
         ========================================================================= -->
    <section id="guide" class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-center">
      
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
              src="img/forest_reserve_hero.jpg" 
              alt="Surat Thani Forest Reserve" 
              class="w-full h-full object-cover object-center" 
              onerror="this.onerror=null; this.src='ปก.png';"
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
          <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-sm sm:text-base flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
            01
          </div>
          <div class="space-y-1">
            <h4 class="font-extrabold text-sm sm:text-base text-mezenc-teal flex items-center gap-2 group-hover:text-mezenc-brightCyan transition-colors">
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
          <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-sm sm:text-base flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
            02
          </div>
          <div class="space-y-1">
            <h4 class="font-extrabold text-sm sm:text-base text-mezenc-teal flex items-center gap-2 group-hover:text-mezenc-brightCyan transition-colors">
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
          <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-sm sm:text-base flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
            03
          </div>
          <div class="space-y-1">
            <h4 class="font-extrabold text-sm sm:text-base text-mezenc-teal flex items-center gap-2 group-hover:text-mezenc-brightCyan transition-colors">
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
          <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-white text-mezenc-teal font-black text-sm sm:text-base flex items-center justify-center shrink-0 border border-[#bee6e1] shadow-xs group-hover:bg-mezenc-brightCyan group-hover:text-white group-hover:border-mezenc-brightCyan group-hover:scale-105 group-hover:shadow-md transition-all duration-300">
            04
          </div>
          <div class="space-y-1">
            <h4 class="font-extrabold text-sm sm:text-base text-mezenc-teal flex items-center gap-2 group-hover:text-mezenc-brightCyan transition-colors">
              <svg fill="#000000" viewBox="0 0 24 24" id="qr-code-scan" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0"><g id="SVGRepo_iconCarrier"><path id="secondary" d="M12,13v4a1,1,0,0,1-1,1H7a1,1,0,0,1-1-1V13a1,1,0,0,1,1-1h4A1,1,0,0,1,12,13ZM7,10H9a1,1,0,0,0,1-1V7A1,1,0,0,0,9,6H7A1,1,0,0,0,6,7V9A1,1,0,0,0,7,10Zm10,4H15a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V15A1,1,0,0,0,17,14Zm0-8H13a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1V7A1,1,0,0,0,17,6Z" style="fill: #2ca9bc;"></path><path id="primary" d="M21,9a1,1,0,0,1-1-1V4H16a1,1,0,0,1,0-2h4a2,2,0,0,1,2,2V8A1,1,0,0,1,21,9Zm1,11V16a1,1,0,0,0-2,0v4H16a1,1,0,0,0,0,2h4A2,2,0,0,0,22,20ZM4,8V4H8A1,1,0,0,0,8,2H4A2,2,0,0,0,2,4V8A1,1,0,0,0,4,8ZM9,21a1,1,0,0,0-1-1H4V16a1,1,0,0,0-2,0v4a2,2,0,0,0,2,2H8A1,1,0,0,0,9,21Z" style="fill: #00A896;"></path></g></svg>
              <span>Step 4: ตรวจสอบย้อนกลับตามมาตรฐาน EUDR</span>
            </h4>
            <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
              สร้างเอกสารรับรองดิจิทัล พร้อม QR Code สำหรับผู้ซื้อและเจ้าหน้าที่สแกนตรวจสอบย้อนกลับ (Traceability) 100%
            </p>
          </div>
        </div>

      </div>

    </section>

    <!-- =========================================================================
         5. SATISFIED CLIENTS SPEAKS (Testimonials Section)
         ========================================================================= -->
    <section id="reviews" class="bg-white rounded-4xl p-6 sm:p-10 border-2 border-[#bee6e1] shadow-[0_20px_45px_-10px_rgba(14,77,78,0.12)] space-y-8 text-center">
      
      <!-- Section Title -->
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-mezenc-brightCyan bg-mezenc-lightCyan px-3 py-1 rounded-full border border-[#bee6e1] inline-block">
          TESTIMONIALS
        </span>
        <h2 class="text-2xl sm:text-3xl font-black text-mezenc-teal mt-2">
          Satisfied Clients Speaks
        </h2>
        <p class="text-xs sm:text-sm text-gray-500 max-w-lg mx-auto mt-1 font-light">
          เสียงตอบรับและความประทับใจจากเกษตรกรชาวสวนยางและตัวแทนสหกรณ์ในจังหวัดสุราษฎร์ธานี
        </p>
      </div>

      <!-- Testimonial Content Box -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center text-left">
        
        <!-- Left: Overlapping Photos Placeholder -->
        <div class="lg:col-span-6 relative h-64 sm:h-72 flex items-center justify-center">
          <div class="w-48 sm:w-56 h-56 sm:h-64 rounded-3xl overflow-hidden bg-gray-100 border-4 border-white shadow-xl transform -rotate-6 absolute left-4 sm:left-12">
            <img src="img/map_rubber_hero.jpg" alt="Farmer review" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
          <div class="w-48 sm:w-56 h-56 sm:h-64 rounded-3xl overflow-hidden bg-gray-200 border-4 border-white shadow-2xl transform rotate-6 absolute right-4 sm:right-12">
            <img src="img/forest_reserve_hero.jpg" alt="Rubber Farm" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
        </div>

        <!-- Right: Review Card -->
        <div class="lg:col-span-6 bg-[#fcfdfd] p-6 sm:p-8 rounded-3xl border-2 border-[#bee6e1] space-y-4">
          
          <!-- User info & Rating -->
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-mezenc-teal text-white font-bold flex items-center justify-center text-lg shadow-sm">
              👨‍🌾
            </div>
            <div>
              <h4 class="font-extrabold text-sm sm:text-base text-mezenc-teal leading-tight">สมหมาย ขายของ</h4>
              <p class="text-xs text-gray-500 font-medium">ประธานกลุ่มเกษตรกรสวนยางขุนทะเล จ.สุราษฎร์ธานี</p>
              <div class="flex items-center gap-1 text-amber-400 text-xs mt-0.5">
                ★★★★★
              </div>
            </div>
          </div>

          <!-- Review Quote -->
          <blockquote class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed italic">
            “ระบบ GeoRubber Watch ช่วยให้พวกเราเกษตรกรสามารถตรวจสอบพิกัดแปลงยางพาราได้อย่างสะดวกรวดเร็ว ยืนยันได้ชัดเจนว่าแปลงของเราไม่ได้บุกรุกพื้นที่ป่าสงวน และสามารถออก QR Code หนังสือรับรอง EUDR เพื่อส่งออกยางพาราได้ทันที ทำให้ขายผลผลิตได้ราคาดีและมั่นคงครับ”
          </blockquote>

          <!-- Action Button -->
          <div class="pt-2">
            <a href="map.php" class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white font-bold text-xs transition-all shadow-xs inline-flex items-center justify-center gap-2">
              <span>ดูความคิดเห็นเพิ่มเติม (See More)</span>
              <span>➔</span>
            </a>
          </div>

        </div>

      </div>

    </section>

    <!-- =========================================================================
         6. STATISTICS / IMPACT SECTION (3 Stats + Center Image + Right Story)
         ========================================================================= -->
    <section id="stats" class="bg-white rounded-4xl p-6 sm:p-10 border-2 border-[#bee6e1] shadow-[0_20px_45px_-10px_rgba(14,77,78,0.12)]">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        
        <!-- Left Stats (Col 4) -->
        <div class="lg:col-span-4 space-y-6">
          
          <div class="space-y-1 pb-4 border-b border-gray-100">
            <h3 class="text-3xl sm:text-4xl font-black text-mezenc-teal tracking-tight">784,618 ไร่</h3>
            <p class="text-xs text-gray-600 font-light leading-relaxed">
              พื้นที่แนวเขตป่าสงวนแห่งชาติ 26 แห่ง จ.สุราษฎร์ธานี ในระบบฐานข้อมูลเฝ้าระวัง
            </p>
          </div>

          <div class="space-y-1 pb-4 border-b border-gray-100">
            <h3 class="text-3xl sm:text-4xl font-black text-mezenc-brightCyan tracking-tight">100% EUDR</h3>
            <p class="text-xs text-gray-600 font-light leading-relaxed">
              มาตรฐาน Zero Deforestation สอดคล้องตามกฎระเบียบว่าด้วยสินค้าปลอดการตัดไม้ทำลายป่า
            </p>
          </div>

          <div class="space-y-1">
            <h3 class="text-3xl sm:text-4xl font-black text-mezenc-teal tracking-tight">1,000+ แปลง</h3>
            <p class="text-xs text-gray-600 font-light leading-relaxed">
              เป้าหมายแปลงปลูกที่ผ่านการรับรองและมี Digital Traceability Passport
            </p>
          </div>

        </div>

        <!-- Center Image (Col 4) -->
        <div class="lg:col-span-4 h-64 sm:h-80 rounded-3xl overflow-hidden bg-gray-100 relative border-2 border-[#bee6e1] shadow-sm">
          <img src="img/forest_reserve_hero.jpg" alt="Surat Thani Landscape" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
          <div class="absolute bottom-3 left-3 right-3 text-white text-xs font-bold bg-black/50 backdrop-blur-md p-2.5 rounded-xl border border-white/20">
            🌲 สุราษฎร์ธานี โมเดลการจัดการสวนยางยั่งยืน
          </div>
        </div>

        <!-- Right Story (Col 4) -->
        <div class="lg:col-span-4 space-y-4">
          <span class="text-xs font-bold uppercase tracking-wider text-mezenc-brightCyan bg-mezenc-lightCyan px-3 py-1 rounded-full border border-[#bee6e1] inline-block">
            TRANSFORMATION
          </span>
          <h3 class="text-xl sm:text-2xl font-black text-mezenc-teal leading-snug">
            Surat Thani: The Transformation Of Sustainable Rubber.
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
            จังหวัดสุราษฎร์ธานีเป็นหนึ่งในแหล่งปลูกยางพาราที่สำคัญที่สุดของภาคใต้ การขับเคลื่อนระบบสารสนเทศภูมิศาสตร์และการตรวจสอบย้อนกลับมาตรฐาน EUDR จึงเป็นก้าวสำคัญที่ช่วยยกระดับเกษตรกรไทยสู่เวทีการค้ายางพาราระดับสากลอย่างยั่งยืน
          </p>
        </div>

      </div>
    </section>

    <!-- =========================================================================
         7. BLOG / KNOWLEDGE SECTION (4 Cards Grid)
         ========================================================================= -->
    <section id="blogs" class="bg-white rounded-4xl p-6 sm:p-8 lg:p-10 border-2 border-[#bee6e1] shadow-[0_20px_45px_-10px_rgba(14,77,78,0.12)] space-y-6">
      
      <!-- Section Header -->
      <div class="flex items-center justify-between pb-4 border-b border-gray-100">
        <div>
          <h2 class="text-xl sm:text-2xl font-black text-mezenc-teal">Blog Section</h2>
          <p class="text-xs text-gray-500 font-light mt-0.5">เกร็ดความรู้และบทความวิชาการ EUDR และการจัดการสวนยาง</p>
        </div>

        <!-- Arrows -->
        <div class="flex items-center gap-2">
          <button class="w-8 h-8 rounded-full bg-mezenc-lightCyan hover:bg-mezenc-teal hover:text-white text-mezenc-teal flex items-center justify-center font-bold text-xs border border-[#bee6e1] transition-all">
            ‹
          </button>
          <button class="w-8 h-8 rounded-full bg-mezenc-teal hover:bg-mezenc-brightCyan text-white flex items-center justify-center font-bold text-xs transition-all">
            ›
          </button>
        </div>
      </div>

      <!-- 4 Columns Blog Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Blog 1 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift p-3.5 space-y-3 flex flex-col justify-between hover:border-mezenc-brightCyan">
          <div class="w-full h-36 rounded-2xl overflow-hidden bg-gray-100">
            <img src="img/map_rubber_hero.jpg" alt="EUDR Guide" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
          <div class="space-y-1">
            <span class="text-[10px] font-bold text-mezenc-brightCyan uppercase">EU Regulation</span>
            <h4 class="font-extrabold text-xs sm:text-sm text-mezenc-teal leading-snug line-clamp-2">
              ทำความเข้าใจมาตรฐาน EUDR (EU 2023/1115) กฎระเบียบใหม่ส่งออกยางพารา
            </h4>
            <p class="text-[11px] text-gray-500 font-light line-clamp-2">
              ข้อกำหนดที่ผู้ส่งออกและเกษตรกรต้องทราบเกี่ยวกับการปลอดการตัดไม้ทำลายป่า
            </p>
          </div>
          <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
            <span>20 ส.ค. 2026</span>
            <span class="text-mezenc-brightCyan font-bold hover:underline cursor-pointer">อ่านต่อ ➔</span>
          </div>
        </div>

        <!-- Blog 2 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift p-3.5 space-y-3 flex flex-col justify-between hover:border-mezenc-brightCyan">
          <div class="w-full h-36 rounded-2xl overflow-hidden bg-gray-100">
            <img src="img/forest_reserve_hero.jpg" alt="Forest 26" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
          <div class="space-y-1">
            <span class="text-[10px] font-bold text-emerald-600 uppercase">GIS & Forestry</span>
            <h4 class="font-extrabold text-xs sm:text-sm text-mezenc-teal leading-snug line-clamp-2">
              แนวเขตป่าสงวนแห่งชาติ 26 แห่ง ในสุราษฎร์ธานีและการตรวจสอบ Buffer Zone
            </h4>
            <p class="text-[11px] text-gray-500 font-light line-clamp-2">
              เจาะลึก 26 ผืนป่าสงวนและระยะเฝ้าระวัง 500 เมตรเพื่อความปลอดภัยของแปลงปลูก
            </p>
          </div>
          <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
            <span>18 ส.ค. 2026</span>
            <span class="text-mezenc-brightCyan font-bold hover:underline cursor-pointer">อ่านต่อ ➔</span>
          </div>
        </div>

        <!-- Blog 3 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift p-3.5 space-y-3 flex flex-col justify-between hover:border-mezenc-brightCyan">
          <div class="w-full h-36 rounded-2xl overflow-hidden bg-gray-100">
            <img src="img/dashboard_dss_hero.jpg" alt="RRIM 600" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
          <div class="space-y-1">
            <span class="text-[10px] font-bold text-amber-600 uppercase">Agronomy</span>
            <h4 class="font-extrabold text-xs sm:text-sm text-mezenc-teal leading-snug line-clamp-2">
              เทคนิคการเพิ่มผลผลิตน้ำยางพาราและการจัดการแปลงยางพันธุ์ RRIM 600
            </h4>
            <p class="text-[11px] text-gray-500 font-light line-clamp-2">
              แนวทางการบำรุงรักษาต้นยางและการกรีดยางอย่างถูกวิธีเพื่อผลผลิตที่ยั่งยืน
            </p>
          </div>
          <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
            <span>15 ส.ค. 2026</span>
            <span class="text-mezenc-brightCyan font-bold hover:underline cursor-pointer">อ่านต่อ ➔</span>
          </div>
        </div>

        <!-- Blog 4 -->
        <div class="bg-[#fcfdfd] rounded-3xl border-2 border-[#bee6e1] overflow-hidden hover-lift p-3.5 space-y-3 flex flex-col justify-between hover:border-mezenc-brightCyan">
          <div class="w-full h-36 rounded-2xl overflow-hidden bg-gray-100">
            <img src="img/dss_analytics_hero.jpg" alt="QR Code Passport" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='ปก.png';">
          </div>
          <div class="space-y-1">
            <span class="text-[10px] font-bold text-purple-600 uppercase">Traceability</span>
            <h4 class="font-extrabold text-xs sm:text-sm text-mezenc-teal leading-snug line-clamp-2">
              ระบบ QR Code Digital Passport และการตรวจสอบย้อนกลับระดับสากล
            </h4>
            <p class="text-[11px] text-gray-500 font-light line-clamp-2">
              กระบวนการสร้างโทเค็นและการตรวจสอบย้อนกลับของน้ำยางสดจากต้นน้ำสู่ปลายน้ำ
            </p>
          </div>
          <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
            <span>12 ส.ค. 2026</span>
            <span class="text-mezenc-brightCyan font-bold hover:underline cursor-pointer">อ่านต่อ ➔</span>
          </div>
        </div>

      </div>

    </section>

    <!-- =========================================================================
         8. SERVICES SECTION (Numbered 01 Dark Card, 02 & 03 Light Cards)
         ========================================================================= -->
    <section id="services" class="space-y-6">
      
      <div class="text-center space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-mezenc-brightCyan bg-mezenc-lightCyan px-3 py-1 rounded-full border border-[#bee6e1] inline-block">
          OUR CAPABILITIES
        </span>
        <h2 class="text-2xl sm:text-3xl font-black text-mezenc-teal mt-2">Services</h2>
        <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto font-light">
          บริการหลักและฟังก์ชันการทำงานของระบบภูมิสารสนเทศ GeoRubber Watch
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Service 01: Dark Card -->
        <div class="bg-mezenc-darkNavy text-white rounded-4xl p-8 sm:p-10 flex flex-col justify-between space-y-6 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.3)] border-2 border-[#bee6e1]/30 hover-lift">
          <div class="flex justify-end">
            <span class="text-3xl font-black text-white/30">01</span>
          </div>
          <div class="space-y-3">
            <h3 class="text-2xl font-black text-white leading-tight">
              Plot Registry & Data Management
            </h3>
            <p class="text-xs sm:text-sm text-gray-300 font-light leading-relaxed">
              ระบบจัดการและจัดเก็บข้อมูลเกษตรกร ข้อมูลแปลงปลูก เอกสารสิทธิ์ที่ดิน และบันทึกผลผลิตน้ำยางสดในระบบคลาวด์ความปลอดภัยสูง
            </p>
          </div>
          <div class="pt-2">
            <a href="index.php" class="text-xs text-mezenc-mint font-bold hover:underline inline-flex items-center gap-1">
              <span>เรียนรู้เพิ่มเติม</span>
              <span>➔</span>
            </a>
          </div>
        </div>

        <!-- Service 02: White Card -->
        <div class="bg-white rounded-4xl p-8 sm:p-10 border-2 border-[#bee6e1] flex flex-col justify-between space-y-6 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.12)] hover-lift">
          <div class="flex justify-end">
            <span class="text-3xl font-black text-mezenc-lightCyan">02</span>
          </div>
          <div class="space-y-3">
            <h3 class="text-2xl font-black text-mezenc-teal leading-tight">
              Spatial Analysis & Buffer Monitoring
            </h3>
            <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
              การวิเคราะห์พิกัดเชิงพื้นที่เทียบกับ 26 แนวเขตป่าสงวนแห่งชาติ คำนวณระยะห่าง Buffer Zone และแจ้งเตือนความเสี่ยงการทับซ้อนแบบ Real-time
            </p>
          </div>
          <div class="pt-2">
            <a href="overview.php" class="text-xs text-mezenc-brightCyan font-bold hover:underline inline-flex items-center gap-1">
              <span>ดูแผนที่ GIS</span>
              <span>➔</span>
            </a>
          </div>
        </div>

        <!-- Service 03: White Card -->
        <div class="bg-white rounded-4xl p-8 sm:p-10 border-2 border-[#bee6e1] flex flex-col justify-between space-y-6 shadow-[0_16px_36px_-8px_rgba(14,77,78,0.12)] hover-lift">
          <div class="flex justify-end">
            <span class="text-3xl font-black text-mezenc-lightCyan">03</span>
          </div>
          <div class="space-y-3">
            <h3 class="text-2xl font-black text-mezenc-teal leading-tight">
              EUDR Digital Passport & Traceability
            </h3>
            <p class="text-xs sm:text-sm text-gray-600 font-light leading-relaxed">
              ออกหนังสือรับรองดิจิทัลและ QR Code ประจำแปลงเพื่อยืนยันความสอดคล้องตามมาตรฐาน EUDR รองรับการตรวจสอบย้อนกลับในการส่งออก
            </p>
          </div>
          <div class="pt-2">
            <a href="map.php" class="text-xs text-mezenc-brightCyan font-bold hover:underline inline-flex items-center gap-1">
              <span>ออกหนังสือรับรอง</span>
              <span>➔</span>
            </a>
          </div>
        </div>

      </div>

    </section>

    <!-- =========================================================================
         9. FOOTER SECTION
         ========================================================================= -->
    <footer class="bg-mezenc-teal text-white rounded-4xl p-8 sm:p-12 border-2 border-[#bee6e1]/30 shadow-2xl space-y-8">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
        
        <!-- Project & Institution Info -->
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

        <!-- Researchers & Contact -->
        <div class="md:col-span-4 space-y-1.5 text-[14px] text-white/85 leading-relaxed">
          <div class="font-bold text-[15px] sm:text-[16px] text-mezenc-mint">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[14px] text-white/75">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[14px] text-white/90 space-y-1">
            <div>👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div>🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div>✉️ <strong>อีเมล:</strong> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a>, <a href="mailto:6640011066@psu.ac.th" class="hover:text-mezenc-mint underline">6640011066@psu.ac.th</a></div>
          </div>
        </div>

        <!-- Forest Stats Card -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1">SURAT THANI FOREST COVERAGE</div>
            <div class="text-2xl my-1">🗺️</div>
            <div class="text-[14px] font-bold text-white leading-tight">26 ผืนป่าสงวน (Zone C) • 784,618 ไร่</div>
            <div class="text-[12px] text-white/70 mt-1 font-light">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- Copyright Bar -->
      <div class="pt-6 border-t border-white/15 flex flex-col sm:flex-row justify-between items-center text-[14px] text-white/75 gap-4">
        <div>&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>
    </footer>

  </div>

</body>
</html>
