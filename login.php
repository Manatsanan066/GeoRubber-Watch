<?php
/**
 * GeoRubber Watch • EUDR GIS Portal Login & Register (login.php)
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

          <!-- Alert Notification Box -->
          <div id="auth-alert" class="hidden mb-4 p-3 rounded-2xl text-xs font-semibold transition-all"></div>

          <!-- =================================================================
               1. LOGIN FORM
               ================================================================= -->
          <form id="login-form" onsubmit="event.preventDefault(); handleLoginSubmit();" class="space-y-3 sm:space-y-3.5 transition-all duration-300">
            
            <!-- Email / Username / Phone Input -->
            <div class="transition-all duration-300">
              <input 
                id="login-username"
                type="text" 
                placeholder="ชื่อผู้ใช้, อีเมล หรือ เบอร์โทรศัพท์" 
                value="admin"
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full px-5 sm:px-6 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
            </div>

            <!-- Password Input with Eye Toggle -->
            <div class="relative transition-all duration-300">
              <input 
                id="login-password"
                type="password" 
                placeholder="รหัสผ่าน" 
                value="admin123"
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-5 sm:pl-6 pr-12 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
              <button 
                type="button" 
                onclick="togglePasswordVisibility('login-password', 'eye-login-icon')"
                title="Toggle password visibility"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal p-1 transition-colors"
              >
                <!-- Eye Icon -->
                <svg id="eye-login-icon" class="w-4 h-4 sm:w-5 sm:h-5 stroke-current" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>

            <!-- Main Submit Action Button (Gradient Mezenc Teal) -->
            <div class="pt-2">
              <button 
                id="login-submit-button"
                type="submit" 
                class="w-full bg-gradient-to-r from-mezenc-teal to-[#166061] hover:from-mezenc-brightCyan hover:to-mezenc-teal active:scale-[0.98] text-white font-bold text-xs sm:text-sm py-3.5 sm:py-4 px-6 rounded-full shadow-[0_10px_25px_-5px_rgba(14,77,78,0.35)] hover:shadow-[0_15px_30px_-5px_rgba(0,166,153,0.45)] transition-all duration-300 uppercase tracking-wider flex items-center justify-center gap-2"
              >
                <span>เข้าสู่ระบบ (LOG IN)</span>
                <span id="login-spinner" class="hidden animate-spin">⏳</span>
              </button>
            </div>

          </form>


          <!-- =================================================================
               2. FARMER REGISTRATION FORM (ห้ามซ้ำทั้งชื่อและรหัสผ่าน)
               ================================================================= -->
          <form id="register-form" onsubmit="event.preventDefault(); handleRegisterSubmit();" class="hidden space-y-3 sm:space-y-3.5 transition-all duration-300">
            
            <!-- 1. Full Name -->
            <div class="transition-all duration-300">
              <input 
                id="reg-fullname"
                type="text" 
                placeholder="ชื่อ - นามสกุล (เช่น นางสาวมาทินี โรยนรินทร์)" 
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full px-5 sm:px-6 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
            </div>

            <!-- 2. Phone Number -->
            <div class="transition-all duration-300">
              <input 
                id="reg-phone"
                type="tel" 
                placeholder="เบอร์โทรศัพท์ (เช่น 081-234-5678)" 
                required
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full px-5 sm:px-6 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
            </div>

            <!-- 3. Password Input with Eye Toggle -->
            <div class="relative transition-all duration-300">
              <input 
                id="reg-password"
                type="password" 
                placeholder="รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" 
                required
                minlength="6"
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-5 sm:pl-6 pr-12 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
              <button 
                type="button" 
                onclick="togglePasswordVisibility('reg-password', 'eye-reg-icon-1')"
                title="Toggle password visibility"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal p-1 transition-colors"
              >
                <svg id="eye-reg-icon-1" class="w-4 h-4 sm:w-5 sm:h-5 stroke-current" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>

            <!-- 4. Confirm Password Input with Eye Toggle -->
            <div class="relative transition-all duration-300">
              <input 
                id="reg-confirm-password"
                type="password" 
                placeholder="ยืนยันรหัสผ่านอีกครั้ง" 
                required
                minlength="6"
                class="w-full bg-[#f4faf9] hover:bg-[#ebf6f4] focus:bg-white text-gray-800 placeholder-gray-400 text-xs sm:text-sm font-medium rounded-full pl-5 sm:pl-6 pr-12 py-3.5 sm:py-4 outline-none border-2 border-[#bee6e1] focus:border-mezenc-brightCyan focus:ring-4 focus:ring-mezenc-lightCyan/60 transition-all shadow-xs"
              />
              <button 
                type="button" 
                onclick="togglePasswordVisibility('reg-confirm-password', 'eye-reg-icon-2')"
                title="Toggle password visibility"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-mezenc-teal p-1 transition-colors"
              >
                <svg id="eye-reg-icon-2" class="w-4 h-4 sm:w-5 sm:h-5 stroke-current" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>

            <!-- Register Submit Button -->
            <div class="pt-2">
              <button 
                id="reg-submit-button"
                type="submit" 
                class="w-full bg-gradient-to-r from-mezenc-teal to-[#166061] hover:from-mezenc-brightCyan hover:to-mezenc-teal active:scale-[0.98] text-white font-bold text-xs sm:text-sm py-3.5 sm:py-4 px-6 rounded-full shadow-[0_10px_25px_-5px_rgba(14,77,78,0.35)] hover:shadow-[0_15px_30px_-5px_rgba(0,166,153,0.45)] transition-all duration-300 uppercase tracking-wider flex items-center justify-center gap-2"
              >
                <span>ลงทะเบียน (Sign Up)</span>
                <span id="reg-spinner" class="hidden animate-spin">⏳</span>
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
          
          <!-- Rubber Purchase Station Image Background -->
          <img 
            src="img/rubber_purchase_station.jpg" 
            alt="เกษตรกรชาวสวนยางบันทึกผลผลิตน้ำยางสด ณ จุดรับซื้อ จ.สุราษฎร์ธานี" 
            class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700 ease-out"
          />

          <!-- Atmospheric Vignette Gradient -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20 pointer-events-none"></div>

          <!-- INTERACTIVE GEO-PIN: EUDR Traceable -->
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
       JAVASCRIPT: INTERACTIVE CONTROLS & AUTH API INTEGRATION
       ========================================================================= -->
  <script>
    let currentMode = 'login';
    const redirectUrl = '<?= $safe_redirect ?>';

    // Toggle Password Visibility
    function togglePasswordVisibility(inputId, iconId) {
      const passwordInput = document.getElementById(inputId);
      const eyeIcon = document.getElementById(iconId);
      if (!passwordInput || !eyeIcon) return;

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
      const loginForm = document.getElementById('login-form');
      const registerForm = document.getElementById('register-form');
      const switchText = document.getElementById('auth-switch-text');
      hideAlert();

      if (mode === 'login') {
        heading.innerHTML = 'Welcome';
        subHeading.innerText = 'เข้าสู่ระบบเพื่อตรวจสอบการทับซ้อนของแปลงปลูกกับแนวเขตป่าสงวน';
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        switchText.innerHTML = `
          ยังไม่มีบัญชีผู้ใช้งาน? 
          <button type="button" onclick="switchAuthMode('register')" class="font-bold text-mezenc-teal hover:text-mezenc-brightCyan underline ml-1 cursor-pointer transition-colors">
            ลงทะเบียนใหม่
          </button>
        `;
      } else {
        heading.innerHTML = 'Start your<br class="hidden sm:block"> perfect trip';
        subHeading.innerText = 'สร้างบัญชีเพื่อเริ่มต้นจัดการและรับรองมาตรฐานแปลงปลูก';
        loginForm.classList.add('hidden');
        registerForm.classList.remove('hidden');
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

    // Handle Login Submit
    async function handleLoginSubmit() {
      const usernameInput = document.getElementById('login-username').value.trim();
      const passwordInput = document.getElementById('login-password').value.trim();
      const spinner = document.getElementById('login-spinner');

      if (!usernameInput || !passwordInput) {
        showAlert('กรุณากรอกชื่อผู้ใช้ / อีเมล / เบอร์โทร และรหัสผ่าน');
        return;
      }

      spinner.classList.remove('hidden');
      hideAlert();

      try {
        const response = await fetch('api/auth.php?action=login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username: usernameInput, password: passwordInput })
        });
        const data = await response.json();
        spinner.classList.add('hidden');

        if (data.success) {
          showAlert(`🎉 เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ ${data.user.full_name} (${data.user.role}) กำลังนำท่านไปยังหน้า ${redirectUrl}...`, true);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 600);
        } else {
          showAlert(data.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        }
      } catch (e) {
        spinner.classList.add('hidden');
        showAlert('⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง');
      }
    }

    // Handle Farmer Register Submit (With strict uniqueness validation)
    async function handleRegisterSubmit() {
      const fullName = document.getElementById('reg-fullname').value.trim();
      const phone = document.getElementById('reg-phone').value.trim();
      const password = document.getElementById('reg-password').value.trim();
      const confirmPassword = document.getElementById('reg-confirm-password').value.trim();
      const spinner = document.getElementById('reg-spinner');

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
        const response = await fetch('api/auth.php?action=register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            full_name: fullName,
            phone: phone,
            password: password,
            confirm_password: confirmPassword
          })
        });
        const data = await response.json();
        spinner.classList.add('hidden');

        if (data.success) {
          showAlert(`🎉 ${data.message}! รหัสเกษตรกรของคุณคือ <strong>${data.user.farmer_code}</strong> ข้อมูลได้รับการบันทึกลงฐานข้อมูลแล้ว กำลังนำท่านเข้าสู่ระบบ...`, true);
          
          // Pre-fill login input in case user switches back
          document.getElementById('login-username').value = phone;
          document.getElementById('login-password').value = password;

          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 1500);
        } else {
          showAlert(`❌ ${data.message || 'เกิดข้อผิดพลาดในการลงทะเบียน'}`);
        }
      } catch (err) {
        spinner.classList.add('hidden');
        showAlert('⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง');
      }
    }
  </script>
</body>
</html>
