<?php
/**
 * GeoRubber Watch - หน้าติดต่อเรา (Contact Us • Get In Touch)
 * ดีไซน์เอกลักษณ์ของระบบ (Eco-Minimalist / Deep Teal & Mint Theme) กลมกลืนกับทุกหน้า 100%
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$current_page = 'contact.php';
$current_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['full_name'] ?? ($current_role === 'admin' ? 'รศ.ดร.สุพัตรา พุฒิเนาวรัตน์' : 'นางสาวมาทินี โรยนรินทร์');

// Process contact form submission
$submitted = false;
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_contact') {
    $sender_name = trim($_POST['name'] ?? '');
    $sender_email = trim($_POST['email'] ?? '');
    $sender_subject = trim($_POST['subject'] ?? '');
    $sender_message = trim($_POST['message'] ?? '');

    if (!empty($sender_name) && !empty($sender_email) && !empty($sender_message)) {
        $submitted = true;
    } else {
        $error_msg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>ติดต่อเรา (Contact Us) • GeoRubber Watch</title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
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
            primary: '#00A896',
            secondary: '#024959',
            darkNavy: '#0B2545',
            accent: '#F0A500',
            brandBg: '#F4F9F8',
            surface: '#FFFFFF',
            textPrimary: '#1E293B',
            textMuted: '#64748B',
            mezenc: {
              teal: '#024959',
              deepTeal: '#0B2545',
              darkNavy: '#062627',
              mint: '#00A896',
              brightCyan: '#00A896',
              lightCyan: '#e6f7f6',
              sand: '#F4F9F8',
              slateText: '#1E293B',
              muted: '#64748B'
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
    
    /* Smooth Gradient Mask on Hero */
    .hero-mask-bottom {
      -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 25%, rgba(0, 0, 0, 0.8) 55%, rgba(0, 0, 0, 0) 100%);
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 25%, rgba(0, 0, 0, 0.8) 55%, rgba(0, 0, 0, 0) 100%);
    }

    /* Minimalist Underline Input Focus Transition */
    .clean-input {
      border-bottom-width: 2px;
      transition: all 0.25s ease;
    }
    .clean-input:focus {
      border-color: #00A896 !important;
      padding-bottom: 0.5rem;
    }
  </style>
</head>
<body class="bg-[#f0faf9] text-gray-800 antialiased min-h-screen flex flex-col justify-between selection:bg-mezenc-mint selection:text-white">

  <!-- =========================================================================
       1. HERO & TOP ATMOSPHERIC SECTION (Matching index.php & overview.php 100%)
       ========================================================================= -->
  <div class="relative w-full bg-mezenc-teal overflow-hidden flex flex-col justify-between pb-28 sm:pb-36 lg:pb-44">
    
    <!-- Hero Background Graphic & Gradient Overlay -->
    <div class="absolute inset-0 z-0 bg-cover bg-center pointer-events-none hero-mask-bottom opacity-40 mix-blend-overlay"
         style="background-image: url('img/hero_bg.png'), url('assets/img/rubber_hero.jpg'); background-size: cover; background-position: center;">
    </div>
    
    <div class="absolute inset-0 z-0 bg-gradient-to-b from-[#024959]/95 via-[#024959]/85 to-[#0B2545]/90 pointer-events-none"></div>

    <!-- Atmospheric subtle glow -->
    <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-mezenc-mint/15 blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 -right-24 w-96 h-96 rounded-full bg-mezenc-brightCyan/15 blur-3xl pointer-events-none"></div>

    <!-- =====================================================================
         TOP NAVIGATION BAR (Matching index.php & overview.php)
         ===================================================================== -->
    <header class="relative z-30 w-full px-5 py-4 sm:px-8 sm:py-5 md:px-12 xl:px-16 max-w-[1440px] 2xl:max-w-[1600px] mx-auto flex justify-between items-center">
      
      <!-- BRAND LOGO -->
      <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 cursor-pointer group">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full border-2 border-white flex items-center justify-center bg-white/10 shrink-0 p-1.5 transition-transform group-hover:scale-105">
          <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
          </svg>
        </div>
        <span class="text-white font-sans font-black text-xl sm:text-2xl tracking-normal uppercase drop-shadow-md">
          GeoRubber Watch
        </span>
      </a>

      <!-- DESKTOP NAVIGATION LINKS -->
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
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="yields.php">
          ผลผลิต
        </a>
        <a class="text-mezenc-mint font-bold border-b-2 border-mezenc-mint pb-0.5 transition-colors cursor-pointer drop-shadow-sm" href="contact.php">
          ติดต่อเรา
        </a>
      </nav>

      <!-- USER / LOGIN & MOBILE MENU BUTTON -->
      <div class="flex items-center gap-2.5 sm:gap-3.5">
        <button
          type="button"
          class="text-white hover:text-mezenc-mint flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md transition-all hover:scale-105 border border-white/20 shadow-md cursor-pointer"
          onclick="location.href='login.php'"
          title="เข้าสู่ระบบ (Login)"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
          </svg>
        </button>

        <!-- MOBILE HAMBURGER BUTTON -->
        <button 
          class="lg:hidden text-white w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md flex items-center justify-center border border-white/20 shadow-md transition-all active:scale-95 cursor-pointer" 
          onclick="toggleMobileDrawer()"
          aria-label="เปิดเมนูนำทาง"
        >
          <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
          </svg>
        </button>
      </div>

    </header>

    <!-- HERO CONTENT (Centered typography) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 my-auto py-6 sm:py-10 text-center">
      <div class="max-w-4xl mx-auto space-y-3 sm:space-y-4">
        <div class="text-base sm:text-lg md:text-[20px] font-bold text-mezenc-mint uppercase tracking-widest leading-relaxed drop-shadow">
          COMMUNICATION &amp; SUPPORT CENTER
        </div>
        <h1 class="text-3xl sm:text-4xl md:text-[48px] font-extrabold text-white tracking-wide leading-[1.3] sm:leading-[1.35] drop-shadow-md">
          ศูนย์บริการข้อมูลและติดต่อสอบถาม
        </h1>
        <p class="text-[14px] sm:text-base text-white/90 font-light leading-relaxed tracking-normal max-w-3xl mx-auto pt-1 drop-shadow">
          ศูนย์กลางการประสานงานและบริการข้อมูลภูมิสารสนเทศอัจฉริยะ GeoRubber Watch มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี เพื่อยกระดับสวนยางพาราสู่มาตรฐานความยั่งยืน EUDR
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
        <div class="flex items-center justify-between pb-6 border-b border-white/15">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">🌲</div>
            <span class="font-extrabold text-base">GeoRubber Watch</span>
          </div>
          <button onclick="toggleMobileDrawer()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white">✕</button>
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
          <a href="yields.php" class="px-4 py-3 rounded-xl hover:bg-white/10 transition-colors flex items-center gap-3">
            <span>🧪</span> <span>ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-3 rounded-xl bg-white/15 text-mezenc-mint font-bold transition-colors flex items-center gap-3">
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
       2. MAIN WORKSPACE CARD (Elevated Card Overlapping Hero with -mt)
       ========================================================================= -->
  <main class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 -mt-20 sm:-mt-28 lg:-mt-32 relative z-20 py-2 sm:py-4 flex-1">
    
    <!-- Main Card Container Matching overview.php & index.php -->
    <div class="w-full bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-7 lg:p-10 shadow-[0_20px_45px_-10px_rgba(14,77,78,0.18)] border-2 border-[#bee6e1] relative">
      
      <!-- =====================================================================
           CENTRAL 2-COLUMN CONTACT LAYOUT (Matching Reference UI Mockup)
           ===================================================================== -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-stretch">
        
        <!-- ===================================================================
             LEFT COLUMN: Contact Information Card (Teal & Navy Gradient Card)
             =================================================================== -->
        <div class="lg:col-span-5 bg-gradient-to-br from-[#00A896] via-[#024959] to-[#0B2545] text-white rounded-2xl sm:rounded-3xl p-7 sm:p-9 flex flex-col justify-between relative overflow-hidden shadow-xl min-h-[480px]">
          
          <!-- Decorative Glow Shapes -->
          <div class="absolute -bottom-16 -right-16 w-56 h-56 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
          <div class="absolute bottom-6 right-6 w-36 h-36 rounded-full bg-[#00A896]/20 blur-md pointer-events-none"></div>
          <div class="absolute top-10 right-4 w-24 h-24 rounded-full bg-white/5 blur-sm pointer-events-none"></div>

          <!-- Top Heading -->
          <div class="relative z-10 space-y-3">
            <h3 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-tight">
              Contact Information
            </h3>
            <p class="text-xs sm:text-sm text-teal-50/90 font-light leading-relaxed">
              สอบถามข้อมูลการใช้งานระบบ การวิเคราะห์พิกัดแปลง หรือความสอดคล้องตามมาตรฐาน EUDR ได้ตลอดเวลา
            </p>
          </div>

          <!-- Contact Items with Icons -->
          <div class="relative z-10 space-y-6 my-8">
            
            <!-- Phone Item -->
            <div class="flex items-start gap-4 group">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0 border border-white/20 shadow-xs group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
              </div>
              <div class="text-xs sm:text-sm space-y-0.5 pt-0.5">
                <div class="text-white/70 text-[11px] font-medium uppercase tracking-wider">โทรศัพท์ติดต่อ</div>
                <div class="font-bold text-white tracking-wide">+66 77 278 888</div>
                <div class="font-medium text-white/90 tracking-wide">+66 77 278 889</div>
              </div>
            </div>

            <!-- Email Item -->
            <div class="flex items-start gap-4 group">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0 border border-white/20 shadow-xs group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div class="text-xs sm:text-sm space-y-0.5 pt-0.5">
                <div class="text-white/70 text-[11px] font-medium uppercase tracking-wider">อีเมลสำหรับติดต่อ</div>
                <div class="font-bold text-white break-all">6640011044@psu.ac.th</div>
                <div class="font-medium text-white/90 break-all">6640011066@psu.ac.th</div>
              </div>
            </div>

            <!-- Location Item -->
            <div class="flex items-start gap-4 group">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center shrink-0 border border-white/20 shadow-xs group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="text-xs sm:text-sm space-y-0.5 pt-0.5">
                <div class="text-white/70 text-[11px] font-medium uppercase tracking-wider">สถานที่ตั้งสถาบัน</div>
                <div class="font-bold text-white leading-relaxed">
                  มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี
                </div>
                <div class="text-xs text-white/80 font-light leading-relaxed">
                  31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000
                </div>
              </div>
            </div>

          </div>

          <!-- Bottom Status & Working Hours Badge -->
          <div class="relative z-10 pt-4 border-t border-white/15 flex justify-between items-center text-xs text-white/70">
            <span>🕘 จันทร์ - ศุกร์: 08:30 - 16:30 น.</span>
            <span class="text-mezenc-mint font-bold px-2 py-0.5 rounded-full bg-white/10">EUDR Helpdesk</span>
          </div>

        </div>

        <!-- ===================================================================
             RIGHT COLUMN: Interactive Contact Form
             =================================================================== -->
        <div class="lg:col-span-7 flex flex-col justify-between py-2 sm:py-4 px-2 sm:px-6">
          
          <!-- Form Header & Status Message -->
          <div class="space-y-2 mb-4">
            <?php if ($submitted): ?>
              <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <span class="text-2xl">✅</span>
                <div>
                  <strong class="font-bold text-base">ส่งข้อความเรียบร้อยแล้ว!</strong>
                  <div class="text-xs text-emerald-700 mt-0.5">ขอบคุณสำหรับข้อความ ทีมงานจะติดต่อกลับไปยังอีเมล <?= htmlspecialchars($sender_email) ?> โดยเร็วที่สุดครับ</div>
                </div>
              </div>
            <?php elseif (!empty($error_msg)): ?>
              <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <div><?= htmlspecialchars($error_msg) ?></div>
              </div>
            <?php endif; ?>
          </div>

          <form method="POST" action="contact.php" class="space-y-6 sm:space-y-8 flex-1 flex flex-col justify-between">
            <input type="hidden" name="action" value="send_contact">

            <div class="space-y-6 sm:space-y-8">
              
              <!-- Row 1: Name and Email -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8">
                
                <!-- Your Name -->
                <div class="space-y-1">
                  <label for="name" class="text-xs sm:text-sm font-semibold text-slate-500 block">Your Name (ชื่อ-นามสกุล)</label>
                  <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    placeholder="เช่น สมชาย ใจดี" 
                    required 
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    class="clean-input w-full py-2 bg-transparent text-base sm:text-lg font-bold text-slate-800 border-b-2 border-slate-200 focus:outline-none placeholder:text-slate-300 placeholder:font-normal"
                  >
                </div>

                <!-- Your Email -->
                <div class="space-y-1">
                  <label for="email" class="text-xs sm:text-sm font-semibold text-slate-500 block">Your Email (อีเมลติดต่อกลับ)</label>
                  <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="name@example.com" 
                    required 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="clean-input w-full py-2 bg-transparent text-base sm:text-lg font-bold text-slate-800 border-b-2 border-slate-200 focus:outline-none placeholder:text-slate-300 placeholder:font-normal"
                  >
                </div>

              </div>

              <!-- Row 2: Your Subject -->
              <div class="space-y-1">
                <label for="subject" class="text-xs sm:text-sm font-semibold text-slate-500 block">Your Subject (หัวข้อเรื่อง)</label>
                <input 
                  type="text" 
                  id="subject" 
                  name="subject" 
                  placeholder="ระบุหัวข้อเรื่อง เช่น สอบถามการวาดแปลง, การตรวจสอบความสอดคล้อง EUDR..." 
                  value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                  class="clean-input w-full py-2 bg-transparent text-base sm:text-lg font-bold text-slate-800 border-b-2 border-slate-200 focus:outline-none placeholder:text-slate-300 placeholder:font-normal"
                >
              </div>

              <!-- Row 3: Message -->
              <div class="space-y-1">
                <label for="message" class="text-xs sm:text-sm font-bold text-mezenc-brightCyan block">Message (ข้อความของคุณ)</label>
                <textarea 
                  id="message" 
                  name="message" 
                  rows="3" 
                  placeholder="Write here your message..." 
                  required
                  class="clean-input w-full py-2 bg-transparent text-sm sm:text-base font-medium text-slate-800 border-b-2 border-mezenc-brightCyan focus:outline-none placeholder:text-slate-300 resize-none leading-relaxed"
                ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
              </div>

            </div>

            <!-- Bottom Submit Button -->
            <div class="pt-4 sm:pt-6">
              <button 
                type="submit" 
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 sm:px-10 py-3 sm:py-3.5 rounded-xl bg-mezenc-brightCyan hover:bg-mezenc-teal text-white font-bold text-sm tracking-wide shadow-md hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer"
              >
                <span>Send Message (ส่งข้อความ)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
              </button>
            </div>

          </form>

        </div>

      </div>

    </div>

  </main>

  <!-- =========================================================================
       3. OFFICIAL PSU FOOTER (Matching index.php & overview.php 100%)
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
            <div class="text-xs font-bold text-white">26 ผืนป่าสงวน (Zone C) • 784,618 ไร่</div>
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

  <script>
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
  </script>

</body>
</html>
