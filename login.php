<?php
/**
 * GeoRubber Watch • EUDR GIS Portal Login & Register
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
  <title>เข้าสู่ระบบ • GeoRubber Watch EUDR Portal</title>
  
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
    
    /* Subtle pulse animation for map geo-pins */
    @keyframes subtlePulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.12); opacity: 0.88; }
    }
    .animate-geo-pulse {
      animation: subtlePulse 3s infinite ease-in-out;
    }

    /* Floating glassmorphic badge themed in project teal */
    .glass-badge-teal {
      background: rgba(14, 77, 78, 0.55);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(255, 255, 255, 0.35);
    }
  </style>
</head>
<body class="min-h-screen w-full flex items-center justify-center p-3 sm:p-6 md:p-10 relative overflow-x-hidden bg-[#0a2a2a]">

  <!-- Fullscreen Atmospheric Nature Background matching Project Theme -->
  <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
    <img 
      src="img/login_nature_hero.jpg" 
      alt="Nature Backdrop" 
      class="w-full h-full object-cover scale-110 filter blur-[6px] brightness-[0.55] contrast-[1.1]"
    />
    <!-- Deep Forest Teal Vignette Overlay -->
    <div class="absolute inset-0 bg-gradient-to-tr from-[#051c1c]/90 via-[#0e4d4e]/65 to-[#072424]/85 mix-blend-multiply"></div>
  </div>

  <!-- Main Floating Login/Register Card Container -->
  <main class="relative z-10 w-full max-w-[1080px] bg-white rounded-[2.25rem] sm:rounded-[3rem] p-3 sm:p-4 md:p-5 shadow-[0_30px_90px_rgba(14,77,78,0.38),0_12px_36px_rgba(0,0,0,0.25)] border-2 border-white/90 my-auto transition-all duration-300">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-stretch">
      
      <!-- =========================================================================
           LEFT COLUMN: INTERACTIVE FORM (Sign In / Register)
           ========================================================================= -->
      <div class="lg:col-span-5 w-full flex flex-col justify-between px-4 sm:px-8 py-6 sm:py-8 lg:py-10">
        
        <!-- Top Logo / Brand Title & Back to Home -->
        <div class="flex items-center justify-between">
          <a href="index.php" class="inline-flex items-center gap-2 group">
            <span class="text-2xl font-black text-mezenc-teal tracking-tight group-hover:text-mezenc-brightCyan transition-colors">
              GeoRubber Watch
            </span>
            <span class="text-[10px] uppercase tracking-widest font-extrabold px-2 py-0.5 bg-mezenc-lightCyan text-mezenc-teal border border-mezenc-mint/40 rounded-full">
              EUDR GIS
            </span>
          </a>
          <a href="index.php" class="text-xs font-semibold text-gray-400 hover:text-mezenc-teal transition-colors flex items-center gap-1">
            <span>← หน้าแรก</span>
          </a>
        </div>

        <!-- Middle Content Area -->
        <div class="my-auto py-4 sm:py-6">
          
          <!-- Destination Redirect Pill Notice (If redirected from a feature) -->
          <?php if (!empty($redirect)): ?>
          <div class="mb-4 p-2.5 sm:p-3 rounded-2xl bg-[#f4faf9] border-2 border-[#bee6e1] text-xs text-mezenc-teal flex items-center gap-2.5 shadow-xs">
            <span class="w-2.5 h-2.5 rounded-full bg-mezenc-brightCyan shrink-0 animate-pulse"></span>
            <div class="leading-snug">
              <span class="font-bold text-mezenc-brightCyan">กรุณาเข้าสู่ระบบ</span> เพื่อไปยัง: <span class="font-bold underline"><?= htmlspecialchars($destination_title) ?></span>
            </div>
          </div>
          <?php endif; ?>

          <!-- Dynamic Headline -->
          <div class="text-center mb-6">
            <h1 id="form-heading" class="text-2xl sm:text-3xl lg:text-[2rem] font-black text-mezenc-teal tracking-tight leading-tight">
              Welcome
            </h1>
            <p id="form-subheading" class="text-xs sm:text-sm text-gray-500 font-light mt-1.5">
              เข้าสู่ระบบเพื่อตรวจสอบการทับซ้อนของแปลงปลูกกับแนวเขตป่าสงวน
            </p>
          </div>

          <!-- Quick 1-Click Demo Login Options for Testing -->
          <div class="mb-5 p-2.5 bg-mezenc-lightCyan/60 rounded-2xl border border-[#bee6e1]">
            <div class="text-[11px] font-bold text-mezenc-teal text-center mb-2 flex items-center justify-center gap-1.5">
              <span>เลือกประเภทผู้ใช้งาน:</span>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <button 
                type="button" 
                onclick="loginDemo('admin')" 
                class="py-2 px-3 rounded-xl bg-white hover:bg-mezenc-teal hover:text-white text-mezenc-teal font-bold text-xs shadow-xs border border-mezenc-mint/50 transition-all flex items-center justify-center gap-1.5"
              >
                <span>ผู้ดูแล (Admin)</span>
              </button>
              <button 
                type="button" 
                onclick="loginDemo('farmer')" 
                class="py-2 px-3 rounded-xl bg-white hover:bg-mezenc-brightCyan hover:text-white text-mezenc-brightCyan font-bold text-xs shadow-xs border border-mezenc-mint/50 transition-all flex items-center justify-center gap-1.5"
              >
                <span>เกษตรกร (Farmer)</span>
              </button>
            </div>
          </div>

          <!-- Organic 3-Pill Social Login Buttons -->
          <div class="flex justify-center mb-4">
            <div class="inline-flex items-center gap-5 bg-mezenc-lightCyan/70 hover:bg-mezenc-lightCyan px-6 py-2 rounded-full shadow-inner border border-[#bee6e1] transition-all">
              
              <!-- Apple Icon -->
              <button type="button" onclick="loginDemo('admin')" title="Sign in with Apple (Demo)" class="text-mezenc-teal hover:text-black hover:scale-115 active:scale-95 transition-all text-base sm:text-lg">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 fill-current" viewBox="0 0 170 170">
                  <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.08-7.7-7.86-12.01-14.35-5.99-9.14-10.74-19.64-14.24-31.5-3.5-11.86-5.26-23.01-5.26-33.45 0-14.48 3.7-26.47 11.1-35.97 7.4-9.5 16.63-14.37 27.71-14.62 5.03 0 10.45 1.25 16.27 3.75 5.82 2.5 9.77 3.8 11.86 3.9 1.86 0 5.86-1.35 12-4.04 6.13-2.69 11.53-3.9 16.21-3.63 12.3.63 22.28 5.17 29.93 13.62-10.73 6.54-15.99 15.65-15.77 27.34.22 9.17 3.75 16.89 10.59 23.16 6.84 6.27 15.04 9.87 24.6 10.8-2.39 7.42-5.49 14.7-9.31 21.84zM119.22 31.84c0-7.39 2.68-14.19 8.04-20.4 5.36-6.21 11.96-9.97 19.8-11.28.22 1.09.33 2.18.33 3.27 0 7.39-2.79 14.32-8.37 20.78-5.58 6.46-12.3 10.29-20.16 11.49-.22-1.09-.33-2.18-.33-3.27z"/>
                </svg>
              </button>

              <!-- Google Icon -->
              <button type="button" onclick="loginDemo('farmer')" title="Sign in with Google (Demo)" class="hover:scale-115 active:scale-95 transition-all text-base sm:text-lg">
                <svg class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 24 24">
                  <path fill="#EA4335" d="M12 5c1.56 0 2.98.57 4.09 1.51l3.06-3.06C17.29 1.7 14.81 1 12 1 7.48 1 3.59 3.57 1.62 7.3l3.66 2.84C6.17 7.15 8.84 5 12 5z"/>
                  <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58l3.71 2.88c2.16-1.99 3.41-4.92 3.41-8.7z"/>
                  <path fill="#FBBC05" d="M5.28 14.86c-.25-.74-.38-1.53-.38-2.36s.13-1.62.38-2.36L1.62 7.3C.59 9.36 0 11.61 0 12s.59 2.64 1.62 4.7l3.66-2.84z"/>
                  <path fill="#34A853" d="M12 23c3.24 0 5.95-1.08 7.93-2.91l-3.71-2.88c-1.08.72-2.45 1.16-4.22 1.16-3.16 0-5.83-2.15-6.79-5.14L1.62 16.7C3.59 20.43 7.48 23 12 23z"/>
                </svg>
              </button>

              <!-- Facebook Icon -->
              <button type="button" onclick="loginDemo('farmer')" title="Sign in with Facebook (Demo)" class="text-[#1877F2] hover:scale-115 active:scale-95 transition-all text-base sm:text-lg">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </button>

            </div>
          </div>

          <!-- Divider 'or' -->
          <div class="text-center mb-4">
            <span class="text-xs text-gray-400 font-medium tracking-wide">หรือกรอกข้อมูล</span>
          </div>

          <!-- Alert Notification Box -->
          <div id="auth-alert" class="hidden mb-4 p-3 rounded-2xl text-xs font-semibold transition-all"></div>

          <!-- Input Fields Form (Styled with soft light cyan background and mint border) -->
          <form id="auth-form" onsubmit="event.preventDefault(); handleAuthSubmit();" class="space-y-3 sm:space-y-3.5">
            
            <!-- Full Name Input (Visible in Register Mode) -->
            <div id="field-fullname" class="hidden transition-all duration-300">
              <input 
                id="name-input"
                type="text" 
                placeholder="ชื่อ - นามสกุล" 
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full px-5 sm:px-6 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
            </div>

            <!-- Email / Username Input -->
            <div class="transition-all duration-300">
              <input 
                id="username-input"
                type="text" 
                placeholder="ชื่อผู้ใช้ หรือ อีเมล (เช่น admin หรือ somchai)" 
                value="admin"
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full px-5 sm:px-6 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
            </div>

            <!-- Password Input with Eye Toggle -->
            <div class="relative transition-all duration-300">
              <input 
                id="password-input"
                type="password" 
                placeholder="รหัสผ่าน (เช่น admin123 หรือ farmer123)" 
                value="admin123"
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-5 sm:pl-6 pr-12 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
              <button 
                type="button" 
                onclick="togglePasswordVisibility()"
                title="Toggle password visibility"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal p-1 transition-colors"
              >
                <!-- Eye Icon -->
                <svg id="eye-icon" class="w-4 h-4 sm:w-5 sm:h-5 stroke-current" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>

            <!-- Main Submit Action Button (Gradient Mezenc Teal) -->
            <div class="pt-2">
              <button 
                id="submit-button"
                type="submit" 
                class="w-full bg-gradient-to-r from-mezenc-teal to-[#166061] hover:from-mezenc-brightCyan hover:to-mezenc-teal active:scale-[0.98] text-white font-bold text-xs sm:text-sm py-3.5 sm:py-4 px-6 rounded-full shadow-[0_10px_25px_-5px_rgba(14,77,78,0.35)] hover:shadow-[0_15px_30px_-5px_rgba(0,166,153,0.45)] transition-all duration-300 uppercase tracking-wider flex items-center justify-center gap-2"
              >
                <span id="btn-text">เข้าสู่ระบบ (Log In)</span>
                <span id="btn-spinner" class="hidden animate-spin">⏳</span>
              </button>
            </div>

          </form>

        </div>

        <!-- Bottom Footer Switcher -->
        <div class="text-center pt-2">
          <p id="auth-switch-text" class="text-xs text-gray-500 font-medium">
            ยังไม่มีบัญชีผู้ใช้งาน? 
            <button type="button" onclick="switchAuthMode('register')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
              ลงทะเบียนใหม่
            </button>
          </p>
        </div>

      </div>
      <!-- End Left Column -->

      <!-- =========================================================================
           RIGHT COLUMN: NATURE HERO SCENE WITH THEMED INTERACTIVE GEO-PINS
           ========================================================================= -->
      <div class="lg:col-span-7 w-full">
        <div class="relative w-full h-[400px] sm:h-[480px] lg:h-full min-h-[460px] rounded-[1.75rem] sm:rounded-[2.5rem] overflow-hidden group shadow-inner">
          
          <!-- Rubber Purchase Station & Farmer App Image Background -->
          <img 
            src="img/rubber_purchase_station.jpg" 
            alt="เกษตรกรชาวสวนยางบันทึกผลผลิตน้ำยางสด ณ จุดรับซื้อ จ.สุราษฎร์ธานี" 
            class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out"
          />

          <!-- Atmospheric Vignette Gradient -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20 pointer-events-none"></div>



          <!-- =================================================================
               INTERACTIVE GEO-PIN 2: EUDR Traceable (Middle Right)
               ================================================================= -->
          <div class="absolute top-[38%] right-[6%] sm:right-[10%] z-20 flex flex-col items-end">
            <!-- Glassmorphism Badge Card (Themed in project Teal) -->
            <div class="glass-badge-teal text-white px-4 sm:px-5 py-2.5 sm:py-3 rounded-2xl shadow-xl hover:scale-105 transition-transform cursor-pointer text-left">
              <div class="text-xs sm:text-sm font-black text-mezenc-lightCyan tracking-wide flex items-center gap-1.5">
                <span>EUDR Traceable</span>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              </div>
              <div class="text-[10px] sm:text-xs text-gray-200 font-light mt-0.5">
                ตรวจสอบย้อนกลับผลผลิต
              </div>
            </div>
            <!-- Pin Pointer Dot -->
            <div class="flex items-center gap-1.5 mt-2 mr-6">
              <div class="w-2.5 h-2.5 bg-mezenc-brightCyan rounded-full ring-4 ring-white/60 shadow-md animate-ping"></div>
            </div>
          </div>

        </div>
      </div>
      <!-- End Right Column -->

    </div>

  </main>

  <!-- =========================================================================
       JAVASCRIPT: INTERACTIVE CONTROLS & AUTH
       ========================================================================= -->
  <script>
    let currentMode = 'login'; // default mode
    const redirectUrl = '<?= $safe_redirect ?>';

    // Toggle Password Visibility (Eye Icon)
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password-input');
      const eyeIcon = document.getElementById('eye-icon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
        `;
      } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
      }
    }

    // Switch between 'register' and 'login' view
    function switchAuthMode(mode) {
      currentMode = mode;
      const heading = document.getElementById('form-heading');
      const subHeading = document.getElementById('form-subheading');
      const fullNameField = document.getElementById('field-fullname');
      const submitBtn = document.getElementById('btn-text');
      const switchText = document.getElementById('auth-switch-text');
      hideAlert();

      if (mode === 'login') {
        heading.innerHTML = 'Welcome';
        subHeading.innerText = 'เข้าสู่ระบบเพื่อตรวจสอบการทับซ้อนของแปลงปลูกกับแนวเขตป่าสงวน';
        fullNameField.classList.add('hidden');
        submitBtn.innerText = 'เข้าสู่ระบบ (Log In)';
        switchText.innerHTML = `
          ยังไม่มีบัญชีผู้ใช้งาน? 
          <button type="button" onclick="switchAuthMode('register')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
            ลงทะเบียนใหม่
          </button>
        `;
      } else {
        heading.innerHTML = 'Start your<br class="hidden sm:block"> perfect trip';
        subHeading.innerText = 'สร้างบัญชีเพื่อเริ่มต้นจัดการและรับรองมาตรฐานแปลงปลูก';
        fullNameField.classList.remove('hidden');
        submitBtn.innerText = 'ลงทะเบียน (Sign Up)';
        switchText.innerHTML = `
          มีบัญชีผู้ใช้งานแล้ว? 
          <button type="button" onclick="switchAuthMode('login')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
            เข้าสู่ระบบ
          </button>
        `;
      }
    }

    function showAlert(msg, isSuccess = false) {
      const alertBox = document.getElementById('auth-alert');
      alertBox.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'border-red-200', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200');
      if (isSuccess) {
        alertBox.className = 'mb-4 p-3 rounded-2xl text-xs font-semibold border bg-emerald-50 text-emerald-800 border-emerald-200';
      } else {
        alertBox.className = 'mb-4 p-3 rounded-2xl text-xs font-semibold border bg-red-50 text-red-700 border-red-200';
      }
      alertBox.innerHTML = msg;
    }

    function hideAlert() {
      const alertBox = document.getElementById('auth-alert');
      alertBox.classList.add('hidden');
    }

    // 1-Click Demo Login
    async function loginDemo(role) {
      showAlert(`⏳ กำลังเข้าสู่ระบบแบบ ${role === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'เกษตรกร (Farmer)'}...`, true);
      try {
        const response = await fetch('api/auth.php?action=switch_demo_user', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ role: role })
        });
        const data = await response.json();
        if (data.success) {
          showAlert(`🎉 เข้าสู่ระบบสำเร็จ! กำลังนำท่านไปยังหน้าเป้าหมาย...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 600);
        } else {
          showAlert(`⚠️ ${data.message || 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'}`);
        }
      } catch (err) {
        // Fallback simulate login & redirect
        showAlert(`🎉 เข้าสู่ระบบสำเร็จ! กำลังนำท่านไปยังหน้าเป้าหมาย...`, true);
        setTimeout(() => {
          window.location.href = redirectUrl;
        }, 500);
      }
    }

    // Handle Form Submit
    async function handleAuthSubmit() {
      const usernameInput = document.getElementById('username-input').value.trim();
      const passwordInput = document.getElementById('password-input').value.trim();
      const nameInput = document.getElementById('name-input')?.value.trim();

      if (!usernameInput || !passwordInput) {
        showAlert('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน');
        return;
      }

      const btnSpinner = document.getElementById('btn-spinner');
      btnSpinner.classList.remove('hidden');

      if (currentMode === 'login') {
        try {
          const response = await fetch('api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: usernameInput, password: passwordInput })
          });
          const data = await response.json();
          btnSpinner.classList.add('hidden');

          if (data.success) {
            showAlert(`🎉 เข้าสู่ระบบสำเร็จ! กำลังนำท่านไปยังหน้า ${redirectUrl}...`, true);
            setTimeout(() => {
              window.location.href = redirectUrl;
            }, 600);
          } else {
            showAlert(data.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (ลอง admin / admin123)');
          }
        } catch (e) {
          btnSpinner.classList.add('hidden');
          // Fallback demo switch if database is fresh
          showAlert(`🎉 เข้าสู่ระบบสำเร็จ! กำลังนำท่านไปยังหน้า ${redirectUrl}...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 600);
        }
      } else {
        // Register mode simulation
        btnSpinner.classList.add('hidden');
        showAlert(`🎉 ลงทะเบียนสำเร็จ! กำลังเข้าสู่ระบบและนำท่านไปยังหน้า ${redirectUrl}...`, true);
        setTimeout(() => {
          loginDemo('farmer');
        }, 800);
      }
    }
  </script>
</body>
</html>
