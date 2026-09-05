<?php
/**
 * GeoRubber Watch • EUDR GIS Portal Authentication (login2.php)
 * Dedicated Multi-Agency & Farmer Authentication & Registration Hub
 */
session_start();
$redirect = $_GET['redirect'] ?? '';
$safe_redirect = 'index.php';

// Page Title Mapping
$page_titles = [
    'index.php' => 'หน้าแรก GeoRubber Watch',
    'overview.php' => 'สำรวจ 26 แนวเขตป่าสงวนแห่งชาติ',
    'dashboard.php' => 'ระบบสนับสนุนการตัดสินใจและวิเคราะห์ข้อมูลสวนยาง (DSS)',
    'map.php' => 'ระบบวาดแปลงพิกัดและตรวจสอบแนวเขตป่าไม้ (GIS Map)',
    'yields.php' => 'ระบบบันทึกผลผลิตน้ำยางสดและ DRC',
    'trace.php' => 'ออกเอกสาร EUDR Passport ดิจิทัล (QR Code)',
    'farmers.php' => 'ทะเบียนเกษตรกรและแปลงปลูกยางพารา',
    'eudr_certs.php' => 'ระบบหนังสือรับรอง EUDR'
];

if (!empty($redirect)) {
    $clean_redirect = basename($redirect);
    if (preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $clean_redirect)) {
        $safe_redirect = $clean_redirect;
    }
}

$destination_title = $page_titles[$safe_redirect] ?? 'ระบบสารสนเทศ GeoRubber Watch';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบและลงทะเบียน • GeoRubber Watch EUDR Portal</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
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
              lightCyan: '#e8f7f5',
              brightCyan: '#00a699',
              mint: '#5ebbb6',
              darkTeal: '#092a2a',
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
    
    @keyframes subtlePulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.08); opacity: 0.9; }
    }
    .animate-geo-pulse {
      animation: subtlePulse 3s infinite ease-in-out;
    }

    .glass-badge-teal {
      background: rgba(14, 77, 78, 0.65);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .glass-card-light {
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: rgba(14, 77, 78, 0.05);
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(14, 77, 78, 0.25);
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(14, 77, 78, 0.45);
    }
  </style>
</head>
<body class="min-h-screen w-full flex items-center justify-center p-2 sm:p-4 md:p-8 relative overflow-x-hidden bg-[#072121]">

  <!-- Atmospheric Forest Nature Background -->
  <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
    <img 
      src="img/login_nature_hero.jpg" 
      alt="Nature Backdrop" 
      class="w-full h-full object-cover scale-105 filter blur-[5px] brightness-[0.5] contrast-[1.15]"
    />
    <div class="absolute inset-0 bg-gradient-to-tr from-[#041616]/95 via-[#0e4d4e]/70 to-[#072424]/90 mix-blend-multiply"></div>
  </div>

  <!-- Main Floating Login/Register Card Container -->
  <main class="relative z-10 w-full max-w-[1140px] glass-card-light rounded-[2rem] sm:rounded-[2.75rem] p-3 sm:p-5 md:p-6 shadow-[0_35px_100px_rgba(4,22,22,0.65),0_15px_40px_rgba(0,0,0,0.3)] border-2 border-white/80 my-auto transition-all duration-300">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 items-stretch">
      
      <!-- =========================================================================
           LEFT COLUMN: AUTHENTICATION FORM & DEMO AGENCY ACCOUNTS
           ========================================================================= -->
      <div class="lg:col-span-6 w-full flex flex-col justify-between px-2 sm:px-4 md:px-6 py-3 sm:py-5">
        
        <!-- Header & Branding -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <a href="index.php" class="inline-flex items-center gap-2.5 group">
              <div class="w-8 h-8 rounded-xl bg-mezenc-teal text-white flex items-center justify-center font-black text-sm shadow-md group-hover:bg-mezenc-brightCyan transition-colors">
                🌿
              </div>
              <div>
                <span class="text-xl sm:text-2xl font-black text-mezenc-teal tracking-tight group-hover:text-mezenc-brightCyan transition-colors block leading-tight">
                  GeoRubber Watch
                </span>
                <span class="text-[9px] uppercase tracking-widest font-extrabold text-mezenc-mint block">
                  EUDR GIS • Multi-Agency Portal
                </span>
              </div>
            </a>
            <a href="index.php" class="text-xs font-semibold text-gray-500 hover:text-mezenc-teal bg-gray-100 hover:bg-mezenc-lightCyan px-3 py-1.5 rounded-full transition-all flex items-center gap-1.5 border border-gray-200 hover:border-mezenc-mint">
              <span>← กลับหน้าหลัก</span>
            </a>
          </div>

          <!-- Destination Redirect Pill Notice (If redirected from a protected feature) -->
          <?php if (!empty($redirect)): ?>
          <div class="mb-4 p-2.5 sm:p-3 rounded-2xl bg-[#f4faf9] border-2 border-[#bee6e1] text-xs text-mezenc-teal flex items-center gap-2.5 shadow-xs animate-fade-in">
            <span class="w-2.5 h-2.5 rounded-full bg-mezenc-brightCyan shrink-0 animate-ping"></span>
            <div class="leading-snug">
              <span class="font-bold text-mezenc-brightCyan">กรุณาเข้าสู่ระบบ</span> เพื่อเข้าถึง: <span class="font-bold underline"><?= htmlspecialchars($destination_title) ?></span>
            </div>
          </div>
          <?php endif; ?>

          <!-- Segmented Tab Navigation: Sign In vs Farmer Register -->
          <div class="bg-gray-100/90 p-1.5 rounded-2xl flex items-center gap-1.5 border border-gray-200/80 mb-5">
            <button 
              type="button" 
              id="tab-login"
              onclick="switchAuthMode('login')"
              class="flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-mezenc-teal bg-white shadow-sm border border-gray-200/50 transition-all flex items-center justify-center gap-2"
            >
              <span>🔑 เข้าสู่ระบบ</span>
              <span class="text-[10px] bg-mezenc-lightCyan text-mezenc-teal px-1.5 py-0.5 rounded-md font-semibold">Sign In</span>
            </button>
            <button 
              type="button" 
              id="tab-register"
              onclick="switchAuthMode('register')"
              class="flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-gray-500 hover:text-mezenc-teal hover:bg-white/60 transition-all flex items-center justify-center gap-2"
            >
              <span>🌱 ลงทะเบียนเกษตรกร</span>
              <span class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-md font-bold">Farmer Only</span>
            </button>
          </div>
        </div>

        <!-- Middle Content Area: Dynamic Form -->
        <div class="my-auto py-1">
          
          <!-- Alert Notification Box -->
          <div id="auth-alert" class="hidden mb-4 p-3 rounded-2xl text-xs font-semibold transition-all"></div>

          <!-- =================================================================
               VIEW 1: SIGN IN FORM (เข้าสู่ระบบ)
               ================================================================= -->
          <div id="view-login" class="transition-all duration-300">
            
            <div class="mb-4">
              <h1 class="text-xl sm:text-2xl font-black text-mezenc-teal tracking-tight">
                ยินดีต้อนรับสู่ระบบ EUDR Portal
              </h1>
              <p class="text-xs text-gray-500 font-light mt-1">
                เข้าสู่ระบบสำหรับหน่วยงานรัฐ สหกรณ์ และเกษตรกรชาวสวนยาง
              </p>
            </div>

            <!-- Login Form -->
            <form id="form-login" onsubmit="event.preventDefault(); handleLoginSubmit();" class="space-y-3">
              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                  ชื่อผู้ใช้ / อีเมลหน่วยงาน / เบอร์โทรศัพท์
                </label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">👤</span>
                  <input 
                    id="login-username"
                    type="text" 
                    placeholder="เช่น admin@georubberwatch.com หรือ 093-578-2399" 
                    value="admin@georubberwatch.com"
                    required
                    class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-11 pr-5 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                  รหัสผ่าน (Password)
                </label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔒</span>
                  <input 
                    id="login-password"
                    type="password" 
                    placeholder="กรอกรหัสผ่าน" 
                    value="admin123"
                    required
                    class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-11 pr-12 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                  />
                  <button 
                    type="button" 
                    onclick="togglePasswordVisibility('login-password', 'eye-login')"
                    title="แสดง/ซ่อนรหัสผ่าน"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal p-1 transition-colors"
                  >
                    <svg id="eye-login" class="w-4 h-4 stroke-current" fill="none" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between text-xs pt-1 px-1">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600 select-none">
                  <input type="checkbox" checked class="w-4 h-4 rounded text-mezenc-brightCyan focus:ring-mezenc-mint border-gray-300">
                  <span>จดจำการเข้าสู่ระบบ</span>
                </label>
                <button type="button" onclick="showHelpModal()" class="text-mezenc-teal hover:text-mezenc-brightCyan font-bold underline transition-colors">
                  ต้องการความช่วยเหลือ?
                </button>
              </div>

              <!-- Submit Button -->
              <div class="pt-2">
                <button 
                  id="btn-login-submit"
                  type="submit" 
                  class="w-full bg-gradient-to-r from-mezenc-teal to-[#135f60] hover:from-mezenc-brightCyan hover:to-mezenc-teal active:scale-[0.99] text-white font-bold text-xs sm:text-sm py-3.5 sm:py-4 px-6 rounded-full shadow-[0_10px_25px_-5px_rgba(14,77,78,0.35)] hover:shadow-[0_15px_30px_-5px_rgba(0,166,153,0.45)] transition-all duration-300 uppercase tracking-wider flex items-center justify-center gap-2"
                >
                  <span>เข้าสู่ระบบ (Sign In)</span>
                  <span id="spinner-login" class="hidden animate-spin">⏳</span>
                </button>
              </div>
            </form>

            <!-- Agency Mock Accounts Quick Selector Drawer -->
            <div class="mt-5 pt-4 border-t border-gray-200/80">
              <div class="flex items-center justify-between mb-2.5">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-black text-mezenc-teal flex items-center gap-1">
                    🏛️ บัญชีทดสอบ 5 หน่วยงาน & เกษตรกร
                  </span>
                  <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full border border-amber-200">
                    Mock Data
                  </span>
                </div>
                <button 
                  type="button" 
                  onclick="toggleAgencyList()" 
                  id="btn-toggle-agencies"
                  class="text-[11px] font-bold text-mezenc-brightCyan hover:underline flex items-center gap-1"
                >
                  <span id="agency-toggle-text">แสดงทั้งหมด (6 บัญชี)</span>
                  <span id="agency-toggle-icon">▼</span>
                </button>
              </div>

              <!-- Interactive Grid of Mock Agency & Farmer Credentials -->
              <div id="agency-list" class="space-y-1.5 max-h-[220px] overflow-y-auto pr-1">
                
                <!-- 1. Super Admin -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-mezenc-teal text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      👑
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-mezenc-darkTeal truncate">ผู้ดูแลระบบกลาง (Super Admin)</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">admin@georubberwatch.com</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('admin@georubberwatch.com', 'admin123', 'SUPER_ADMIN')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-mezenc-teal text-mezenc-teal hover:text-white border border-mezenc-mint/50 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

                <!-- 2. Royal Forest Department -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-emerald-700 text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      🌲
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-emerald-950 truncate">กรมป่าไม้ (Royal Forest Dept.)</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">suratthani.forest@forest.go.th</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('suratthani.forest@forest.go.th', 'admin123', 'FORESTRY_ADMIN')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-emerald-700 text-emerald-700 hover:text-white border border-emerald-300 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

                <!-- 3. Department of Lands -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-blue-700 text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      📜
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-blue-950 truncate">กรมที่ดิน (Dept. of Lands)</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">land.surat@dol.go.th</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('land.surat@dol.go.th', 'admin123', 'LAND_ADMIN')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-blue-700 text-blue-700 hover:text-white border border-blue-300 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

                <!-- 4. Rubber Authority of Thailand (RAOT) -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-amber-600 text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      🌳
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-amber-950 truncate">การยางแห่งประเทศไทย (RAOT)</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">surat.eudr@raot.co.th</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('surat.eudr@raot.co.th', 'admin123', 'RAOT_ADMIN')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-300 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

                <!-- 5. Rubber Cooperative -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-teal-700 text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      🤝
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-teal-950 truncate">สหกรณ์กองทุนสวนยางสุราษฎร์ฯ</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">manager@suratrubber-coop.com</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('manager@suratrubber-coop.com', 'admin123', 'COOP_ADMIN')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-teal-700 text-teal-700 hover:text-white border border-teal-300 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

                <!-- 6. Sample Farmer Account -->
                <div class="p-2 rounded-xl bg-[#f0f8f7] border border-[#cbebe7] hover:border-mezenc-brightCyan flex items-center justify-between gap-2 transition-all">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shrink-0 font-bold">
                      🧑‍🌾
                    </div>
                    <div class="truncate">
                      <div class="text-[11px] font-bold text-emerald-950 truncate">เกษตรกรชาวสวนยาง (นางสาวมาทินี)</div>
                      <div class="text-[10px] text-gray-500 font-mono truncate">093-578-2399 (FM-PSU-001)</div>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    onclick="autoFillAndLogin('093-578-2399', 'farmer123', 'farmer')" 
                    class="shrink-0 px-2.5 py-1 bg-white hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-300 rounded-lg text-[10px] font-bold transition-all shadow-2xs"
                  >
                    ⚡ ทดสอบ
                  </button>
                </div>

              </div>
            </div>

          </div>
          <!-- End View 1: Sign In -->


          <!-- =================================================================
               VIEW 2: FARMER REGISTRATION FORM (ลงทะเบียนเฉพาะเกษตรกร)
               ================================================================= -->
          <div id="view-register" class="hidden transition-all duration-300">
            
            <!-- Registration Header Banner -->
            <div class="mb-4">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100/80 text-emerald-800 rounded-full text-[11px] font-bold border border-emerald-300 mb-2">
                <span>🌿 ลงทะเบียนเกษตรกรใหม่ (Farmer Registration)</span>
              </div>
              <h1 class="text-xl sm:text-2xl font-black text-mezenc-teal tracking-tight">
                สร้างบัญชีเกษตรกรชาวสวนยาง
              </h1>
              <p class="text-xs text-gray-500 font-light mt-1">
                กรอกข้อมูล 4 ขั้นตอนเพื่อเริ่มต้นวาดแปลงพิกัดและยื่นรับรองมาตรฐาน EUDR
              </p>
            </div>

            <!-- Notice for Admins / Agencies -->
            <div class="mb-3.5 p-2.5 bg-amber-50/90 border border-amber-200 rounded-xl text-[11px] text-amber-900 flex items-start gap-2 leading-relaxed">
              <span class="text-amber-600 font-bold shrink-0 mt-0.5">ℹ️</span>
              <div>
                <span class="font-bold">เงื่อนไขการลงทะเบียน:</span> หน้านี้เปิดรับลงทะเบียนเฉพาะ <span class="underline font-bold">เกษตรกรชาวสวนยาง</span> เท่านั้น สำหรับเจ้าหน้าที่หน่วยงานภาครัฐและสหกรณ์ โปรดใช้บัญชีองค์กรเข้าสู่ระบบในแท็บ "เข้าสู่ระบบ"
              </div>
            </div>

            <!-- Farmer Registration Form -->
            <form id="form-register" onsubmit="event.preventDefault(); handleRegisterSubmit();" class="space-y-3">
              
              <!-- 1. Full Name -->
              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                  1. ชื่อ - นามสกุล (Full Name) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🧑‍🌾</span>
                  <input 
                    id="reg-fullname"
                    type="text" 
                    placeholder="เช่น นางสาวมาทินี โรยนรินทร์ หรือ นายสมชาย ยางเจริญสุข" 
                    required
                    class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-11 pr-5 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                  />
                </div>
              </div>

              <!-- 2. Phone Number -->
              <div>
                <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                  2. เบอร์โทรศัพท์ (Phone Number) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">📱</span>
                  <input 
                    id="reg-phone"
                    type="tel" 
                    placeholder="เช่น 081-234-5678 (ใช้เป็นชื่อผู้ใช้ในการเข้าสู่ระบบ)" 
                    required
                    class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-11 pr-5 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                  />
                </div>
              </div>

              <!-- 3. Password & 4. Confirm Password Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                    3. รหัสผ่าน <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      id="reg-password"
                      type="password" 
                      placeholder="อย่างน้อย 6 ตัวอักษร" 
                      required
                      minlength="6"
                      class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs font-medium rounded-full pl-5 pr-10 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                    />
                    <button 
                      type="button" 
                      onclick="togglePasswordVisibility('reg-password', 'eye-reg-1')"
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal"
                    >
                      <svg id="eye-reg-1" class="w-4 h-4 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-bold text-gray-700 mb-1 ml-2">
                    4. ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      id="reg-confirm-password"
                      type="password" 
                      placeholder="กรอกรหัสผ่านซ้ำอีกครั้ง" 
                      required
                      minlength="6"
                      class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs font-medium rounded-full pl-5 pr-10 py-3 sm:py-3.5 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
                    />
                    <button 
                      type="button" 
                      onclick="togglePasswordVisibility('reg-confirm-password', 'eye-reg-2')"
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal"
                    >
                      <svg id="eye-reg-2" class="w-4 h-4 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Terms Checkbox -->
              <div class="pt-1 px-1">
                <label class="flex items-start gap-2 cursor-pointer text-xs text-gray-600 select-none">
                  <input type="checkbox" required checked class="w-4 h-4 rounded text-mezenc-brightCyan focus:ring-mezenc-mint border-gray-300 mt-0.5">
                  <span>ยินยอมให้บันทึกข้อมูลพิกัดแปลงปลูกยางพาราและตรวจสอบย้อนกลับตามข้อกำหนดมาตรฐาน EUDR</span>
                </label>
              </div>

              <!-- Register Submit Button -->
              <div class="pt-2">
                <button 
                  id="btn-reg-submit"
                  type="submit" 
                  class="w-full bg-gradient-to-r from-emerald-700 to-mezenc-teal hover:from-emerald-600 hover:to-mezenc-brightCyan active:scale-[0.99] text-white font-bold text-xs sm:text-sm py-3.5 sm:py-4 px-6 rounded-full shadow-[0_10px_25px_-5px_rgba(5,150,105,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(16,185,129,0.5)] transition-all duration-300 uppercase tracking-wider flex items-center justify-center gap-2"
                >
                  <span>สมัครสมาชิกเกษตรกร (Register as Farmer)</span>
                  <span id="spinner-reg" class="hidden animate-spin">⏳</span>
                </button>
              </div>

            </form>

          </div>
          <!-- End View 2: Farmer Registration -->

        </div>

        <!-- Footer / Switch Prompt -->
        <div class="text-center pt-3 border-t border-gray-200/50 mt-2">
          <p id="auth-footer-text" class="text-xs text-gray-500 font-medium">
            ยังไม่มีบัญชีเกษตรกร? 
            <button type="button" onclick="switchAuthMode('register')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
              ลงทะเบียนใหม่ที่นี่
            </button>
          </p>
        </div>

      </div>
      <!-- End Left Column -->

      <!-- =========================================================================
           RIGHT COLUMN: NATURE HERO SCENE & MULTI-AGENCY INFO PANEL
           ========================================================================= -->
      <div class="lg:col-span-6 w-full flex flex-col justify-between">
        <div class="relative w-full h-[420px] sm:h-[480px] lg:h-full min-h-[460px] rounded-[1.75rem] sm:rounded-[2.25rem] overflow-hidden group shadow-inner border border-white/50 bg-[#062020]">
          
          <!-- Image Backdrop -->
          <img 
            src="img/rubber_purchase_station.jpg" 
            alt="เกษตรกรชาวสวนยางบันทึกผลผลิตน้ำยางสด ณ จุดรับซื้อ จ.สุราษฎร์ธานี" 
            class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out"
          />

          <!-- Atmospheric Vignette Gradient -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-black/40 pointer-events-none"></div>

          <!-- Top Badge: EUDR System Live -->
          <div class="absolute top-4 left-4 right-4 z-20 flex items-center justify-between pointer-events-auto">
            <div class="glass-badge-teal text-white px-3.5 py-1.5 rounded-full shadow-lg flex items-center gap-2 text-xs">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
              <span class="font-bold tracking-wide">EUDR GIS Traceability 100%</span>
            </div>
            <div class="glass-badge-teal text-white/90 px-3 py-1.5 rounded-full shadow-lg text-[10px] font-mono">
              Surat Thani • Zone C
            </div>
          </div>

          <!-- Floating Interactive Geo-Pins -->
          <div class="absolute top-[34%] right-[6%] z-20 flex flex-col items-end">
            <div class="glass-badge-teal text-white px-4 py-2.5 rounded-2xl shadow-xl hover:scale-105 transition-transform cursor-pointer text-left">
              <div class="text-xs font-black text-mezenc-lightCyan tracking-wide flex items-center gap-1.5">
                <span>EUDR Deforestation-Free</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              </div>
              <div class="text-[10px] text-gray-200 font-light mt-0.5">
                ตรวจสอบความปลอดการตัดไม้ทำลายป่า 100%
              </div>
            </div>
            <div class="flex items-center gap-1.5 mt-2 mr-5">
              <div class="w-2.5 h-2.5 bg-mezenc-brightCyan rounded-full ring-4 ring-white/60 shadow-md animate-ping"></div>
            </div>
          </div>

          <!-- Bottom Card: 5-Agency Integrated Architecture Overview -->
          <div class="absolute bottom-4 left-4 right-4 z-20">
            <div class="bg-black/60 backdrop-blur-md rounded-2xl p-3.5 sm:p-4 border border-white/20 text-white shadow-2xl">
              <div class="text-xs font-bold text-mezenc-mint mb-1 flex items-center gap-1.5">
                <span>🏛️ ระบบบูรณาการ 5 หน่วยงานภาครัฐ & เกษตรกร</span>
              </div>
              <p class="text-[11px] text-gray-300 leading-snug font-light mb-2.5">
                โครงสร้างข้อมูลจำลองเพื่อการทดสอบระบบ (Mock Data) อิงตามโดเมนและภารกิจจริงของหน่วยงานภาครัฐในจังหวัดสุราษฎร์ธานี
              </p>
              <div class="grid grid-cols-3 gap-1.5 text-center text-[10px]">
                <div class="p-1.5 rounded-lg bg-white/10 border border-white/10">
                  <div class="font-bold text-emerald-300">🌲 กรมป่าไม้</div>
                  <div class="text-gray-400 text-[9px]">26 ป่าสงวน</div>
                </div>
                <div class="p-1.5 rounded-lg bg-white/10 border border-white/10">
                  <div class="font-bold text-blue-300">📜 กรมที่ดิน</div>
                  <div class="text-gray-400 text-[9px]">เอกสารสิทธิ์ น.ส.4จ</div>
                </div>
                <div class="p-1.5 rounded-lg bg-white/10 border border-white/10">
                  <div class="font-bold text-amber-300">🌳 กยท. & สหกรณ์</div>
                  <div class="text-gray-400 text-[9px]">รับรอง EUDR</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- End Right Column -->

    </div>

  </main>

  <!-- =========================================================================
       HELP MODAL: MOCK CREDENTIALS FOR EXAMINERS / REPORT
       ========================================================================= -->
  <div id="help-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border-2 border-mezenc-mint animate-scale-in">
      <div class="flex items-center justify-between mb-4 border-b pb-3">
        <h3 class="font-black text-mezenc-teal text-base sm:text-lg flex items-center gap-2">
          <span>📋 ข้อมูลบัญชีผู้ใช้จำลองสำหรับเล่มรายงาน</span>
        </h3>
        <button type="button" onclick="closeHelpModal()" class="text-gray-400 hover:text-gray-700 text-lg font-bold">✕</button>
      </div>
      <div class="space-y-2 text-xs text-gray-700 max-h-[360px] overflow-y-auto pr-1">
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-mezenc-teal">1. ผู้ดูแลระบบสูงสุด (Super Admin)</div>
          <div>อีเมล: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin@georubberwatch.com</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin123</code></div>
        </div>
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-emerald-800">2. กรมป่าไม้ (Royal Forest Department)</div>
          <div>อีเมล: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">suratthani.forest@forest.go.th</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin123</code></div>
        </div>
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-blue-800">3. กรมที่ดิน (Department of Lands)</div>
          <div>อีเมล: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">land.surat@dol.go.th</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin123</code></div>
        </div>
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-amber-800">4. การยางแห่งประเทศไทย (RAOT)</div>
          <div>อีเมล: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">surat.eudr@raot.co.th</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin123</code></div>
        </div>
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-teal-800">5. สหกรณ์กองทุนสวนยาง</div>
          <div>อีเมล: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">manager@suratrubber-coop.com</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">admin123</code></div>
        </div>
        <div class="p-2.5 rounded-xl bg-gray-50 border">
          <div class="font-bold text-emerald-800">6. เกษตรกรชาวสวนยาง (Farmer)</div>
          <div>เบอร์โทร / ผู้ใช้: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">093-578-2399</code> หรือ <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">matinee</code></div>
          <div>รหัสผ่าน: <code class="bg-gray-200 px-1.5 py-0.5 rounded font-mono">farmer123</code></div>
        </div>
      </div>
      <div class="mt-4 pt-3 border-t text-right">
        <button type="button" onclick="closeHelpModal()" class="px-5 py-2 bg-mezenc-teal text-white rounded-xl text-xs font-bold hover:bg-mezenc-brightCyan transition-all">
          ปิดหน้าต่าง
        </button>
      </div>
    </div>
  </div>

  <!-- =========================================================================
       JAVASCRIPT: CONTROLLER & API INTEGRATION
       ========================================================================= -->
  <script>
    let currentMode = 'login';
    const redirectUrl = '<?= $safe_redirect ?>';

    // Switch between 'login' and 'register' mode
    function switchAuthMode(mode) {
      currentMode = mode;
      hideAlert();

      const tabLogin = document.getElementById('tab-login');
      const tabRegister = document.getElementById('tab-register');
      const viewLogin = document.getElementById('view-login');
      const viewRegister = document.getElementById('view-register');
      const footerText = document.getElementById('auth-footer-text');

      if (mode === 'login') {
        tabLogin.className = 'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-mezenc-teal bg-white shadow-sm border border-gray-200/50 transition-all flex items-center justify-center gap-2';
        tabRegister.className = 'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-gray-500 hover:text-mezenc-teal hover:bg-white/60 transition-all flex items-center justify-center gap-2';
        viewLogin.classList.remove('hidden');
        viewRegister.classList.add('hidden');
        footerText.innerHTML = `
          ยังไม่มีบัญชีเกษตรกร? 
          <button type="button" onclick="switchAuthMode('register')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
            ลงทะเบียนใหม่ที่นี่
          </button>
        `;
      } else {
        tabRegister.className = 'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-emerald-800 bg-white shadow-sm border border-emerald-200/60 transition-all flex items-center justify-center gap-2';
        tabLogin.className = 'flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-gray-500 hover:text-mezenc-teal hover:bg-white/60 transition-all flex items-center justify-center gap-2';
        viewRegister.classList.remove('hidden');
        viewLogin.classList.add('hidden');
        footerText.innerHTML = `
          มีบัญชีผู้ใช้งานแล้ว? 
          <button type="button" onclick="switchAuthMode('login')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
            เข้าสู่ระบบที่นี่
          </button>
        `;
      }
    }

    // Toggle Password Visibility
    function togglePasswordVisibility(inputId, eyeId) {
      const input = document.getElementById(inputId);
      const eye = document.getElementById(eyeId);
      if (!input || !eye) return;

      if (input.type === 'password') {
        input.type = 'text';
        eye.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
        `;
      } else {
        input.type = 'password';
        eye.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    }

    // Show / Hide Alert
    function showAlert(msg, isSuccess = false) {
      const alertBox = document.getElementById('auth-alert');
      alertBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'border-red-200', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200');
      if (isSuccess) {
        alertBox.className = 'mb-4 p-3 rounded-2xl text-xs font-semibold border bg-emerald-50 text-emerald-800 border-emerald-200 shadow-xs';
      } else {
        alertBox.className = 'mb-4 p-3 rounded-2xl text-xs font-semibold border bg-red-50 text-red-700 border-red-200 shadow-xs';
      }
      alertBox.innerHTML = msg;
    }

    function hideAlert() {
      const alertBox = document.getElementById('auth-alert');
      alertBox.classList.add('hidden');
    }

    // Toggle Agency Quick Selector Accordion
    function toggleAgencyList() {
      const list = document.getElementById('agency-list');
      const icon = document.getElementById('agency-toggle-icon');
      const text = document.getElementById('agency-toggle-text');
      if (list.classList.contains('hidden')) {
        list.classList.remove('hidden');
        icon.innerText = '▼';
        text.innerText = 'ซ่อนรายการ';
      } else {
        list.classList.add('hidden');
        icon.innerText = '▲';
        text.innerText = 'แสดงทั้งหมด (6 บัญชี)';
      }
    }

    // Auto Fill credentials and Quick Login
    async function autoFillAndLogin(emailOrUser, password, role) {
      switchAuthMode('login');
      document.getElementById('login-username').value = emailOrUser;
      document.getElementById('login-password').value = password;
      
      showAlert(`⏳ กำลังเข้าสู่ระบบสำหรับ [${emailOrUser}]...`, true);

      try {
        const response = await fetch('api/auth.php?action=switch_demo_user', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: emailOrUser, role: role })
        });
        const data = await response.json();
        if (data.success) {
          showAlert(`🎉 ${data.message} กำลังนำท่านเข้าสู่ระบบ...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 600);
        } else {
          // Fallback to standard login API
          handleLoginSubmit();
        }
      } catch (err) {
        handleLoginSubmit();
      }
    }

    // Handle Login Submit
    async function handleLoginSubmit() {
      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value.trim();
      const spinner = document.getElementById('spinner-login');

      if (!username || !password) {
        showAlert('⚠️ กรุณากรอกชื่อผู้ใช้ / อีเมล และรหัสผ่าน');
        return;
      }

      spinner.classList.remove('hidden');
      hideAlert();

      try {
        const res = await fetch('api/auth.php?action=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username: username, password: password })
        });
        const data = await res.json();
        spinner.classList.add('hidden');

        if (data.success) {
          showAlert(`🎉 เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ ${data.user.full_name} (${data.user.role}) กำลังนำท่านไปยังหน้าเป้าหมาย...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 600);
        } else {
          showAlert(`❌ ${data.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'}`);
        }
      } catch (err) {
        spinner.classList.add('hidden');
        showAlert('⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง');
      }
    }

    // Handle Farmer Register Submit
    async function handleRegisterSubmit() {
      const fullName = document.getElementById('reg-fullname').value.trim();
      const phone = document.getElementById('reg-phone').value.trim();
      const password = document.getElementById('reg-password').value.trim();
      const confirmPassword = document.getElementById('reg-confirm-password').value.trim();
      const spinner = document.getElementById('spinner-reg');

      if (!fullName || !phone || !password || !confirmPassword) {
        showAlert('⚠️ กรุณากรอกข้อมูลให้ครบถ้วนทุกช่อง');
        return;
      }

      if (password.length < 6) {
        showAlert('⚠️ รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        return;
      }

      if (password !== confirmPassword) {
        showAlert('⚠️ รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
        return;
      }

      spinner.classList.remove('hidden');
      hideAlert();

      try {
        const res = await fetch('api/auth.php?action=register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            full_name: fullName,
            phone: phone,
            password: password,
            confirm_password: confirmPassword
          })
        });
        const data = await res.json();
        spinner.classList.add('hidden');

        if (data.success) {
          showAlert(`🎉 ${data.message}! รหัสเกษตรกรของคุณคือ <strong>${data.user.farmer_code}</strong> กำลังนำท่านเข้าสู่ระบบ...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 1200);
        } else {
          showAlert(`❌ ${data.message || 'เกิดข้อผิดพลาดในการลงทะเบียน'}`);
        }
      } catch (err) {
        spinner.classList.add('hidden');
        showAlert('⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง');
      }
    }

    // Help Modal Controls
    function showHelpModal() {
      document.getElementById('help-modal').classList.remove('hidden');
    }
    function closeHelpModal() {
      document.getElementById('help-modal').classList.add('hidden');
    }
  </script>
</body>
</html>
