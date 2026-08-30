<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LOREM IPSUM ONE PAGE SITE</title>
  
  <!-- Google Fonts: Inter & Plus Jakarta Sans for clean Swiss/Bauhaus Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Inter"', '"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            theme: {
              slateDark: '#333c4d',
              slateNavy: '#28303f',
              slateLight: '#e7ecf2',
              slateLighter: '#f2f5f8',
              slateMuted: '#94a3b8',
              accentBlue: '#2563eb',
              accentBlueHover: '#1d4ed8',
            }
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
      background-color: #f8fafc;
      color: #334155;
    }

    /* Custom image placeholder icon styling */
    .img-placeholder-box {
      background-color: #e5eaef;
      border: 1px solid #d9e1e8;
    }
    
    /* Giant watermark numbers */
    .watermark-number {
      font-size: 8rem;
      line-height: 1;
      font-weight: 900;
      color: #eef2f6;
      user-select: none;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-[#f2f5f8] text-slate-800 antialiased selection:bg-blue-600 selection:text-white">

  <!-- =========================================================================
       1. HERO SECTION & TOP NAVBAR
       ========================================================================= -->
  <section id="home" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-0 sm:my-6 rounded-none sm:rounded-2xl overflow-hidden border border-slate-200/60">
    
    <!-- Top Navigation Menu -->
    <header class="w-full px-6 sm:px-12 py-5 flex items-center justify-between border-b border-slate-100">
      <div class="font-extrabold text-sm tracking-widest text-slate-800 uppercase">
        LOREM IPSUM
      </div>
      
      <!-- Nav Links -->
      <nav class="hidden lg:flex items-center gap-6 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
        <a href="#home" class="text-blue-600 font-bold hover:text-blue-700 transition-colors">Home</a>
        <a href="#features" class="hover:text-slate-900 transition-colors">Features</a>
        <a href="#explore" class="hover:text-slate-900 transition-colors">Explore</a>
        <a href="#impressions" class="hover:text-slate-900 transition-colors">Impressions</a>
        <a href="#potential" class="hover:text-slate-900 transition-colors">Potential</a>
        <a href="#subscribe" class="hover:text-slate-900 transition-colors">Blog</a>
        <a href="#company" class="hover:text-slate-900 transition-colors">Company</a>
        <a href="#contacts" class="hover:text-slate-900 transition-colors">Contacts</a>
      </nav>
    </header>

    <!-- Hero Asymmetric Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[460px] lg:min-h-[520px]">
      
      <!-- Left Hero Block (White) -->
      <div class="lg:col-span-6 p-8 sm:p-14 lg:p-16 flex flex-col justify-center space-y-6">
        <h1 class="text-3xl sm:text-4xl lg:text-[44px] font-extrabold text-slate-900 uppercase tracking-tight leading-[1.1]">
          LOREM IPSUM<br>
          ONE PAGE<br>
          SITE
        </h1>
        
        <p class="text-xs sm:text-sm text-slate-400 max-w-sm leading-relaxed">
          Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt.
        </p>

        <div class="pt-2">
          <a href="#features" class="inline-flex items-center justify-center px-7 py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md shadow-blue-600/20">
            Start now
          </a>
        </div>
      </div>

      <!-- Right Hero Block (Light Slate Grey with Play Button) -->
      <div class="lg:col-span-6 bg-[#e7ecf2] p-10 flex items-center justify-center relative group min-h-[280px] lg:min-h-auto">
        <!-- Center Video Play Icon Circle -->
        <button type="button" class="w-16 h-16 rounded-full bg-white/80 backdrop-blur-xs border border-white text-slate-700 hover:text-blue-600 hover:scale-110 flex items-center justify-center shadow-md transition-all cursor-pointer">
          <svg class="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </button>
      </div>

    </div>

  </section>

  <!-- =========================================================================
       2. SECTION: FEATURES (3D Zooming Focus Carousel - Matching Uploaded Design)
       ========================================================================= -->
  <section id="features" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-6 sm:p-10 lg:p-14 border border-slate-200/80 relative overflow-hidden">
    
    <!-- Outer Left Square Arrow -->
    <button type="button" onclick="moveFeaturesCarousel(-1)" class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-8 h-8 sm:w-10 sm:h-10 bg-[#28303f] hover:bg-slate-900 text-white flex items-center justify-center text-base transition-all shadow-md z-30 cursor-pointer select-none" aria-label="Previous">
      ‹
    </button>

    <!-- Outer Right Square Arrow -->
    <button type="button" onclick="moveFeaturesCarousel(1)" class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-8 h-8 sm:w-10 sm:h-10 bg-[#28303f] hover:bg-slate-900 text-white flex items-center justify-center text-base transition-all shadow-md z-30 cursor-pointer select-none" aria-label="Next">
      ›
    </button>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-8 items-center px-4 sm:px-8">
      
      <!-- Left Column: Title & Thai Description -->
      <div class="lg:col-span-4 space-y-4 pr-0 lg:pr-4">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 uppercase tracking-tight">
          FEATURES
        </h2>
        
        <p class="text-xs sm:text-[13px] text-slate-500 leading-relaxed font-light">
          ยกระดับการจัดการสวนยางพาราด้วยเทคโนโลยี GIS และดาวเทียม เพื่อความยั่งยืนและการปฏิบัติตามมาตรฐาน EUDR อย่างครบวงจร เชื่อมต่อข้อมูลเชิงพื้นที่และแปลงปลูกอย่างแม่นยำ
        </p>
      </div>

      <!-- Right Column: 3D Focus Zooming Carousel Stage -->
      <div class="lg:col-span-8 relative h-[380px] sm:h-[420px] flex items-center justify-center overflow-hidden">
        
        <!-- Card 1: ORDER -->
        <div id="feat-card-1" onclick="jumpToFeatCard(1)" class="feat-carousel-card absolute w-[260px] sm:w-[320px] md:w-[360px] min-h-[300px] sm:min-h-[340px] p-6 sm:p-8 rounded-xl text-center cursor-pointer select-none transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] flex flex-col justify-center items-center shadow-lg">
          <div class="feat-card-num text-5xl sm:text-6xl md:text-7xl font-black leading-none mb-2 transition-colors duration-500">1</div>
          <h3 class="feat-card-title text-base sm:text-lg font-extrabold uppercase tracking-widest mb-2 transition-colors duration-500">ORDER</h3>
          <p class="feat-card-desc text-[11px] sm:text-xs font-light max-w-xs mx-auto leading-relaxed mb-5 transition-all duration-500">
            ตรวจสอบพิกัดแปลงปลูก ปลอดจากการตัดไม้ทำลายป่า และประเมินสถานะความเสี่ยงเชิงพื้นที่ตามมาตรฐาน EUDR
          </p>
          <div class="feat-card-btn-wrap transition-opacity duration-500">
            <button type="button" class="px-6 py-2 rounded-full border border-white/50 hover:border-white hover:bg-white hover:text-slate-900 text-white font-bold text-[11px] uppercase tracking-wider transition-all">
              READ INFO
            </button>
          </div>
        </div>

        <!-- Card 2: REGISTER -->
        <div id="feat-card-2" onclick="jumpToFeatCard(2)" class="feat-carousel-card absolute w-[260px] sm:w-[320px] md:w-[360px] min-h-[300px] sm:min-h-[340px] p-6 sm:p-8 rounded-xl text-center cursor-pointer select-none transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] flex flex-col justify-center items-center shadow-lg">
          <div class="feat-card-num text-5xl sm:text-6xl md:text-7xl font-black leading-none mb-2 transition-colors duration-500">2</div>
          <h3 class="feat-card-title text-base sm:text-lg font-extrabold uppercase tracking-widest mb-2 transition-colors duration-500">REGISTER</h3>
          <p class="feat-card-desc text-[11px] sm:text-xs font-light max-w-xs mx-auto leading-relaxed mb-5 transition-all duration-500">
            ลงทะเบียนเกษตรกรและบันทึกขอบเขตแปลงปลูกดิจิทัล พร้อมออกรหัสประจำแปลงในระบบคลาวด์
          </p>
          <div class="feat-card-btn-wrap transition-opacity duration-500">
            <button type="button" class="px-6 py-2 rounded-full border border-white/50 hover:border-white hover:bg-white hover:text-slate-900 text-white font-bold text-[11px] uppercase tracking-wider transition-all">
              READ INFO
            </button>
          </div>
        </div>

        <!-- Card 3: TRACEABILITY -->
        <div id="feat-card-3" onclick="jumpToFeatCard(3)" class="feat-carousel-card absolute w-[260px] sm:w-[320px] md:w-[360px] min-h-[300px] sm:min-h-[340px] p-6 sm:p-8 rounded-xl text-center cursor-pointer select-none transition-all duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] flex flex-col justify-center items-center shadow-lg">
          <div class="feat-card-num text-5xl sm:text-6xl md:text-7xl font-black leading-none mb-2 transition-colors duration-500">3</div>
          <h3 class="feat-card-title text-base sm:text-lg font-extrabold uppercase tracking-widest mb-2 transition-colors duration-500">TRACEABILITY</h3>
          <p class="feat-card-desc text-[11px] sm:text-xs font-light max-w-xs mx-auto leading-relaxed mb-5 transition-all duration-500">
            สร้าง QR Code ประจำแปลงเพื่อยืนยันแหล่งที่มาของผลผลิตและรองรับข้อกำหนดความยั่งยืนสากล
          </p>
          <div class="feat-card-btn-wrap transition-opacity duration-500">
            <button type="button" class="px-6 py-2 rounded-full border border-white/50 hover:border-white hover:bg-white hover:text-slate-900 text-white font-bold text-[11px] uppercase tracking-wider transition-all">
              READ INFO
            </button>
          </div>
        </div>

      </div>

    </div>

  </section>

  <!-- CSS & JS for Features 3D Zoom Carousel -->
  <style>
    /* Active Zoomed Center Card (Dark Slate Blue) */
    .feat-card-active {
      transform: translateX(0) scale(1) !important;
      z-index: 20 !important;
      background-color: #4f5d73 !important;
      color: #ffffff !important;
      opacity: 1 !important;
      box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.3) !important;
      pointer-events: auto !important;
    }
    .feat-card-active .feat-card-num {
      color: #ffffff !important;
    }
    .feat-card-active .feat-card-title {
      color: #ffffff !important;
    }
    .feat-card-active .feat-card-desc {
      color: rgba(241, 245, 249, 0.9) !important;
      opacity: 1 !important;
      max-height: 100px !important;
    }
    .feat-card-active .feat-card-btn-wrap {
      opacity: 1 !important;
      pointer-events: auto !important;
    }

    /* Left Card (Scaled down, Light Grey & Peeking) */
    .feat-card-left {
      transform: translateX(-58%) scale(0.85) !important;
      z-index: 10 !important;
      background-color: #f8fafc !important;
      border: 1px solid #e2e8f0 !important;
      color: #94a3b8 !important;
      opacity: 0.5 !important;
      pointer-events: auto !important;
    }
    .feat-card-left:hover {
      opacity: 0.8 !important;
    }
    .feat-card-left .feat-card-num {
      color: #cbd5e1 !important;
    }
    .feat-card-left .feat-card-title {
      color: #94a3b8 !important;
    }
    .feat-card-left .feat-card-desc {
      opacity: 0 !important;
      max-height: 0 !important;
      overflow: hidden;
      margin: 0 !important;
    }
    .feat-card-left .feat-card-btn-wrap {
      opacity: 0 !important;
      pointer-events: none !important;
      display: none;
    }

    /* Right Card (Scaled down, Light Grey & Peeking) */
    .feat-card-right {
      transform: translateX(58%) scale(0.85) !important;
      z-index: 10 !important;
      background-color: #f8fafc !important;
      border: 1px solid #e2e8f0 !important;
      color: #94a3b8 !important;
      opacity: 0.5 !important;
      pointer-events: auto !important;
    }
    .feat-card-right:hover {
      opacity: 0.8 !important;
    }
    .feat-card-right .feat-card-num {
      color: #cbd5e1 !important;
    }
    .feat-card-right .feat-card-title {
      color: #94a3b8 !important;
    }
    .feat-card-right .feat-card-desc {
      opacity: 0 !important;
      max-height: 0 !important;
      overflow: hidden;
      margin: 0 !important;
    }
    .feat-card-right .feat-card-btn-wrap {
      opacity: 0 !important;
      pointer-events: none !important;
      display: none;
    }
  </style>

  <script>
    let activeFeatIndex = 1; // Default to 1 (ORDER)
    const totalFeatCards = 3;

    function renderFeatCarousel() {
      for (let i = 1; i <= totalFeatCards; i++) {
        const card = document.getElementById(`feat-card-${i}`);
        if (!card) continue;

        card.classList.remove('feat-card-active', 'feat-card-left', 'feat-card-right');

        if (i === activeFeatIndex) {
          card.classList.add('feat-card-active');
        } else if (
          (activeFeatIndex === 1 && i === 3) ||
          (activeFeatIndex === 2 && i === 1) ||
          (activeFeatIndex === 3 && i === 2)
        ) {
          card.classList.add('feat-card-left');
        } else {
          card.classList.add('feat-card-right');
        }
      }
    }

    function moveFeaturesCarousel(dir) {
      activeFeatIndex += dir;
      if (activeFeatIndex > totalFeatCards) activeFeatIndex = 1;
      if (activeFeatIndex < 1) activeFeatIndex = totalFeatCards;
      renderFeatCarousel();
    }

    function jumpToFeatCard(index) {
      if (activeFeatIndex === index) return;
      activeFeatIndex = index;
      renderFeatCarousel();
    }

    // Initialize immediately
    document.addEventListener('DOMContentLoaded', renderFeatCarousel);
    renderFeatCarousel();
  </script>

  <!-- =========================================================================
       3. SECTION: EXPLORE (Asymmetric 4-Card Bento Grid)
       ========================================================================= -->
  <section id="explore" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-8 sm:p-12 lg:p-14 border border-slate-200/60 space-y-8">
    
    <!-- Header + Filter Tabs -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 uppercase tracking-tight">
        EXPLORE
      </h2>

      <!-- Tabs -->
      <div class="flex items-center gap-2 text-xs font-semibold">
        <button type="button" class="px-5 py-1.5 rounded-full border border-slate-300 text-slate-700 bg-slate-50 font-bold">First</button>
        <button type="button" class="px-5 py-1.5 rounded-full text-slate-400 hover:text-slate-700">Second</button>
        <button type="button" class="px-5 py-1.5 rounded-full text-slate-400 hover:text-slate-700">Third</button>
      </div>
    </div>

    <!-- Asymmetric 4-Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
      
      <!-- Box 1 (Top Left, Dark Slate Card, Col-Span 4) -->
      <div class="md:col-span-4 bg-[#333c4d] text-white p-7 rounded-xl flex flex-col justify-between min-h-[260px] shadow-sm group hover:-translate-y-1 transition-all">
        <!-- Top Row with Arrow -->
        <div class="flex items-center justify-start">
          <div class="w-8 h-8 rounded-full border border-white/20 flex items-center justify-center text-xs text-white/70">
            ↗
          </div>
        </div>
        
        <!-- Image Placeholder Icon -->
        <div class="w-12 h-12 rounded-lg bg-white/10 flex items-center justify-center text-white/60 mx-auto my-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>

        <!-- Headline & Category -->
        <div class="space-y-1">
          <h3 class="font-extrabold text-xs uppercase tracking-wider text-white">FIRST TEASER BOX<br>WITH HEADLINE</h3>
          <div class="text-[10px] text-white/50 uppercase">Category</div>
        </div>
      </div>

      <!-- Box 2 (Top Right, Wide Light Slate Card, Col-Span 8) -->
      <div class="md:col-span-8 bg-[#e7ecf2] p-7 rounded-xl flex flex-col justify-between min-h-[260px] shadow-xs group hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-start">
          <div class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-xs text-slate-400">
            ↗
          </div>
        </div>
        
        <div class="w-12 h-12 rounded-lg bg-slate-300/60 flex items-center justify-center text-slate-500 mx-auto my-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>

        <div class="space-y-1">
          <h3 class="font-extrabold text-xs uppercase tracking-wider text-slate-900">SECOND TEASER BOX<br>WITH HEADLINE</h3>
          <div class="text-[10px] text-slate-400 uppercase">Category</div>
        </div>
      </div>

      <!-- Box 3 (Bottom Left, Wide Light Slate Card, Col-Span 7) -->
      <div class="md:col-span-7 bg-[#e7ecf2] p-7 rounded-xl flex flex-col justify-between min-h-[260px] shadow-xs group hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-start">
          <div class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-xs text-slate-400">
            ↗
          </div>
        </div>
        
        <div class="w-12 h-12 rounded-lg bg-slate-300/60 flex items-center justify-center text-slate-500 mx-auto my-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>

        <div class="space-y-1">
          <h3 class="font-extrabold text-xs uppercase tracking-wider text-slate-900">THIRD TEASER BOX<br>WITH HEADLINE</h3>
          <div class="text-[10px] text-slate-400 uppercase">Category</div>
        </div>
      </div>

      <!-- Box 4 (Bottom Right, Light Slate Card, Col-Span 5) -->
      <div class="md:col-span-5 bg-[#e7ecf2] p-7 rounded-xl flex flex-col justify-between min-h-[260px] shadow-xs group hover:-translate-y-1 transition-all">
        <div class="flex items-center justify-start">
          <div class="w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center text-xs text-slate-400">
            ↗
          </div>
        </div>
        
        <div class="w-12 h-12 rounded-lg bg-slate-300/60 flex items-center justify-center text-slate-500 mx-auto my-4">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>

        <div class="space-y-1">
          <h3 class="font-extrabold text-xs uppercase tracking-wider text-slate-900">FOURTH TEASER BOX<br>WITH HEADLINE</h3>
          <div class="text-[10px] text-slate-400 uppercase">Category</div>
        </div>
      </div>

    </div>

  </section>

  <!-- =========================================================================
       4. SECTION: IMPRESSIONS (Editorial Gallery)
       ========================================================================= -->
  <section id="impressions" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-8 sm:p-12 lg:p-14 border border-slate-200/60 space-y-8">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 uppercase tracking-tight">
      IMPRESSIONS
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
      <!-- Image 1 Large Left -->
      <div class="md:col-span-5 bg-[#e7ecf2] rounded-xl h-64 sm:h-80 flex items-center justify-center text-slate-400">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>

      <!-- Image 2 & 3 Right Staggered -->
      <div class="md:col-span-7 space-y-6">
        <div class="grid grid-cols-2 gap-6">
          <div class="bg-[#cbd5e1] rounded-xl h-44 flex items-center justify-center text-slate-500">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="bg-[#e7ecf2] rounded-xl h-44 flex items-center justify-center text-slate-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =========================================================================
       5. SECTION: SUBSCRIBE (Dark Slate Call-to-Action Banner)
       ========================================================================= -->
  <section id="subscribe" class="w-full max-w-[1380px] mx-auto bg-[#333c4d] text-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-10 sm:p-14 text-center space-y-6">
    <h2 class="text-2xl sm:text-3xl font-extrabold uppercase tracking-tight">
      SUBSCRIBE
    </h2>

    <div class="max-w-md mx-auto flex flex-col sm:flex-row items-center gap-3">
      <input type="email" placeholder="Your mail" class="w-full px-6 py-3 rounded-full bg-white/10 border border-white/20 text-white placeholder-white/50 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
      <button type="button" class="w-full sm:w-auto px-7 py-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider shrink-0 transition-colors">
        Send File
      </button>
    </div>
  </section>

  <!-- =========================================================================
       6. SECTION: FULL POTENTIAL (Analytics Gauge & Triple Feature Cards)
       ========================================================================= -->
  <section id="potential" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-8 sm:p-12 lg:p-14 border border-slate-200/60 space-y-10">
    
    <!-- Top Row: Narrative + Donut Progress -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
      <div class="md:col-span-6 space-y-4">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 uppercase tracking-tight">
          FULL POTENTIAL
        </h2>
        <p class="text-xs text-slate-400 leading-relaxed">
          Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum.
        </p>
      </div>

      <!-- Donut Gauge Chart Graphic -->
      <div class="md:col-span-6 flex items-center justify-center md:justify-end gap-6">
        <div class="relative w-28 h-28 flex items-center justify-center">
          <svg viewBox="0 0 36 36" class="w-full h-full transform -rotate-90">
            <path stroke="#e2e8f0" stroke-width="3.5" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
            <path stroke="#2563eb" stroke-width="3.5" stroke-dasharray="75, 100" stroke-linecap="round" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
          </svg>
          <span class="absolute font-extrabold text-slate-800 text-sm">75%</span>
        </div>

        <div class="text-xs space-y-1.5 text-slate-500">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
            <span>Feature 1</span>
            <span class="font-bold text-slate-800 ml-2">75%</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
            <span>Feature 2</span>
            <span class="font-bold text-slate-800 ml-2">25%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Triple Image Showcase Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-slate-100">
      <!-- Column 1 -->
      <div class="bg-[#e7ecf2] p-6 rounded-xl space-y-4">
        <div class="h-32 bg-slate-300/60 rounded-lg flex items-center justify-center text-slate-500">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</p>
        <a href="#" class="text-xs font-semibold text-blue-600 block">Read more</a>
      </div>

      <!-- Column 2 -->
      <div class="bg-[#333c4d] text-white p-6 rounded-xl space-y-4">
        <div class="h-32 bg-white/10 rounded-lg flex items-center justify-center text-white/60">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-xs text-white/80 leading-relaxed">Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</p>
        <a href="#" class="text-xs font-semibold text-blue-400 block">Read more</a>
      </div>

      <!-- Column 3 -->
      <div class="bg-[#e7ecf2] p-6 rounded-xl space-y-4">
        <div class="h-32 bg-slate-300/60 rounded-lg flex items-center justify-center text-slate-500">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-xs text-slate-500 leading-relaxed">Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</p>
        <a href="#" class="text-xs font-semibold text-blue-600 block">Read more</a>
      </div>
    </div>

  </section>

  <!-- =========================================================================
       8. SECTION: OUR COMPANY & STATS
       ========================================================================= -->
  <section id="company" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-8 sm:p-12 lg:p-14 border border-slate-200/60 space-y-8">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 uppercase tracking-tight">
      OUR COMPANY
    </h2>

    <!-- 4 Stats Counters -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center border-t border-slate-100 pt-8">
      <div class="space-y-1">
        <div class="text-3xl sm:text-4xl font-extrabold text-slate-900">1.3k</div>
        <div class="w-8 h-0.5 bg-slate-300 mx-auto"></div>
        <div class="text-xs text-slate-400 pt-1">Projects</div>
      </div>
      <div class="space-y-1">
        <div class="text-3xl sm:text-4xl font-extrabold text-slate-900">531</div>
        <div class="w-8 h-0.5 bg-slate-300 mx-auto"></div>
        <div class="text-xs text-slate-400 pt-1">Clients</div>
      </div>
      <div class="space-y-1">
        <div class="text-3xl sm:text-4xl font-extrabold text-slate-900">35</div>
        <div class="w-8 h-0.5 bg-slate-300 mx-auto"></div>
        <div class="text-xs text-slate-400 pt-1">Awards</div>
      </div>
      <div class="space-y-1">
        <div class="text-3xl sm:text-4xl font-extrabold text-slate-900">1</div>
        <div class="w-8 h-0.5 bg-slate-300 mx-auto"></div>
        <div class="text-xs text-slate-400 pt-1">Goal</div>
      </div>
    </div>
  </section>

  <!-- =========================================================================
       9. SECTION: CONTACTS & MAP
       ========================================================================= -->
  <section id="contacts" class="w-full max-w-[1380px] mx-auto bg-white shadow-sm my-6 sm:my-8 rounded-none sm:rounded-2xl p-8 sm:p-12 lg:p-14 border border-slate-200/60 space-y-8">
    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 uppercase tracking-tight">
      CONTACTS
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
      <!-- Contact Details -->
      <div class="md:col-span-5 space-y-4 text-xs text-slate-600">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">📞</span>
          <span>+1 (234) 567-89-00</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">✉️</span>
          <span>info@yourmail.com</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">📍</span>
          <span>City, Street 123</span>
        </div>
      </div>

      <!-- Minimal Map View Placeholder -->
      <div class="md:col-span-7 bg-[#cbd5e1] rounded-xl h-56 flex items-center justify-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-40 bg-[radial-gradient(#94a3b8_1px,transparent_1px)] [background-size:16px_16px]"></div>
        <div class="relative z-10 px-5 py-2.5 rounded-full bg-white shadow-md text-xs font-bold text-slate-800 flex items-center gap-2">
          <span>📍</span> <span>City Center Office</span>
        </div>
      </div>
    </div>
  </section>

  <!-- =========================================================================
       10. FOOTER
       ========================================================================= -->
  <footer class="w-full max-w-[1380px] mx-auto bg-[#333c4d] text-white/60 text-xs py-6 px-8 rounded-none sm:rounded-t-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
    <div>&copy; 2026 LOREM IPSUM ONE PAGE SITE. All rights reserved.</div>
    <div class="flex items-center gap-4">
      <a href="#home" class="hover:text-white transition-colors">Home</a>
      <a href="#features" class="hover:text-white transition-colors">Features</a>
      <a href="#contacts" class="hover:text-white transition-colors">Contacts</a>
    </div>
  </footer>

</body>
</html>
