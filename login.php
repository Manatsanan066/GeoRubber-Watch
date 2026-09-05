<?php
/**
 * GeoRubber Watch • EUDR GIS Portal Login & Register (login.php)
 * Split-Screen Frosted Glass Design with Database Integration
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
    'contact.php' => 'ติดต่อเรา',
    'trace.php' => 'ออกเอกสาร EUDR Passport ดิจิทัล (QR Code)'
];

if (!empty($redirect)) {
    $clean_redirect = basename($redirect);
    if (preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $clean_redirect)) {
        $safe_redirect = $clean_redirect;
    }
}

$destination_title = $page_titles[$safe_redirect] ?? 'ระบบสารสนเทศ GeoRubber Watch';
$msg = $_GET['msg'] ?? '';
$initial_mode = $_GET['mode'] ?? 'signup'; // 'signup' or 'login'
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$logged_in_name = $_SESSION['full_name'] ?? '';
$logged_in_role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบและลงทะเบียน • GeoRubber Watch</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Google+Sans:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Google Sans', 'Sarabun', 'sans-serif'],
                    },
                    colors: {
                        mezenc: {
                            teal: '#0e4d4e',
                            deepTeal: '#093637',
                            brightCyan: '#00a699',
                            mint: '#5ebbb6',
                            lightCyan: '#e6f7f6',
                            sand: '#f8faf9',
                            darkNavy: '#0b1118'
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Google Sans', 'Sarabun', sans-serif;
            -webkit-font-smoothing: antialiased;
            background-color: #0b1118;
        }

        /* Fullscreen Bridge Background Layer (โหลดจาก Local Asset พร้อม Fallback) */
        .bg-bridge {
            background-image: 
                linear-gradient(to right, rgba(10, 15, 22, 0.45) 0%, rgba(10, 15, 22, 0.2) 50%, rgba(10, 15, 22, 0.55) 100%),
                url('img/bridge_wallpaper.jpg'),
                url('img/login_nature_hero.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Frosted Glass Pane on the right side - Soft blur */
        .glass-pane {
            background: rgba(12, 18, 25, 0.42);
            backdrop-filter: blur(9px);
            -webkit-backdrop-filter: blur(9px);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: -15px 0 35px rgba(0, 0, 0, 0.35);
        }

        /* Minimal Underline Input */
        .line-input {
            border-bottom: 1px solid rgba(255, 255, 255, 0.35);
            transition: all 0.25s ease;
        }
        .line-input:focus-within {
            border-bottom-color: rgba(255, 255, 255, 0.95);
        }

        /* Outline Button with elegant proportions */
        .btn-outline-pill {
            border: 1px solid rgba(255, 255, 255, 0.45);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-outline-pill:hover {
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
        }
        .btn-outline-pill:active {
            transform: translateY(0);
        }

        .demo-chip {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.2s ease;
        }
        .demo-chip:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="min-h-screen w-full bg-bridge text-white flex flex-col justify-between selection:bg-white selection:text-black">

    <!-- Container wrapper with Split-Screen layout -->
    <div class="min-h-screen w-full flex flex-col md:flex-row">
        
        <!-- ================= LEFT SECTION: UNOBSTRUCTED VIEW ================= -->
        <div class="hidden md:flex md:w-[48%] lg:w-[50%] z-10 flex-col justify-between p-8 lg:p-12 pointer-events-none">
            <!-- Brand Badge on Left Side -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/30 p-2 shadow-lg">
                    <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-lg font-black tracking-wide text-white drop-shadow">GeoRubber Watch</div>
                    <div class="text-xs text-white/75 font-medium">EUDR Web-GIS Platform • Surat Thani</div>
                </div>
            </div>

            <!-- Territory Information -->
            <div class="space-y-2 max-w-md">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-white/90">
                    <span>🌲</span> <span>26 ผืนป่าสงวนแห่งชาติ • 784,618 ไร่</span>
                </div>
                <p class="text-sm text-white/80 font-light leading-relaxed drop-shadow">
                    ระบบบูรณาการฐานข้อมูลเชิงพื้นที่สำหรับตรวจสอบย้อนกลับแปลงปลูกยางพาราตามกฎระเบียบ EUDR (EU) 2023/1115
                </p>
            </div>
        </div>

        <!-- ================= RIGHT SECTION: FROSTED GLASS FORM PANE ================= -->
        <div class="w-full md:w-[52%] lg:w-[50%] min-h-screen glass-pane flex flex-col justify-between items-center px-6 sm:px-12 lg:px-16 py-8 sm:py-12 z-20 overflow-y-auto custom-scrollbar">
            
            <!-- Top Bar: Back to Home Link -->
            <div class="w-full max-w-[540px] flex justify-between items-center mb-6">
                <a href="index.php" class="inline-flex items-center gap-2 text-xs sm:text-sm text-white/80 hover:text-white transition group">
                    <span class="group-hover:-translate-x-1 transition-transform">←</span>
                    <span>กลับสู่หน้าแรก (Home)</span>
                </a>
                <span class="text-[11px] font-mono uppercase tracking-widest px-2.5 py-0.5 rounded-full bg-white/10 border border-white/20 text-white/90">
                    EUDR PORTAL
                </span>
            </div>

            <div class="w-full max-w-[540px] mx-auto flex flex-col items-center my-auto">
                
                <!-- 1. Header: Welcome to GeoRubber Watch -->
                <div class="text-center mb-7 w-full flex flex-col items-center">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-[36px] xl:text-[38px] font-extrabold text-white tracking-tight drop-shadow-md whitespace-nowrap text-center w-full">
                        Welcome to GeoRubber Watch
                    </h1>
                    <p class="text-sm sm:text-base lg:text-lg text-gray-200 font-medium leading-relaxed mt-2.5 text-center w-full drop-shadow">
                        เข้าสู่ระบบเพื่อบริหารจัดการแปลงปลูก บันทึกผลผลิต<br>
                        และตรวจสอบความสอดคล้องตามมาตรฐาน EUDR
                    </p>
                    <div class="w-16 h-0.5 bg-white/30 mx-auto mt-4 rounded-full"></div>
                </div>

                <!-- Logged Out Notification Notice -->
                <?php if ($msg === 'logged_out'): ?>
                <div class="w-full mb-5 p-3 rounded-2xl bg-amber-500/20 border border-amber-400/40 text-xs sm:text-sm text-amber-200 font-medium text-center shadow-lg">
                    🚪 ออกจากระบบเรียบร้อยแล้ว ท่านสามารถเข้าสู่ระบบใหม่ได้ตลอดเวลา
                </div>
                <?php endif; ?>

                <!-- Already Logged In Notice -->
                <?php if ($is_logged_in): ?>
                <div class="w-full mb-6 p-4 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-xs sm:text-sm text-emerald-200 font-medium flex flex-wrap items-center justify-between gap-3 shadow-lg">
                    <div>
                        <div class="font-bold text-white text-sm">เข้าสู่ระบบอยู่ในขณะนี้: <?= htmlspecialchars($logged_in_name) ?></div>
                        <div class="text-xs text-emerald-300/90 font-mono">บทบาท: <?= htmlspecialchars($logged_in_role) ?></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= htmlspecialchars($safe_redirect) ?>" class="px-3.5 py-1.5 rounded-full bg-white text-black font-bold text-xs hover:bg-gray-200 transition">
                            ต่อไปยังระบบ →
                        </a>
                        <a href="logout.php" class="px-3 py-1.5 rounded-full bg-red-500/30 text-red-200 hover:bg-red-500/50 font-medium text-xs transition" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
                            ออกจากระบบ
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- =============================================================
                     FORM VIEW 1: SIGN UP (ลงทะเบียนเกษตรกรใหม่)
                     ============================================================= -->
                <div id="signupSection" class="w-full <?= $initial_mode === 'login' ? 'hidden' : '' ?>">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white text-left w-full mb-6 tracking-tight flex items-center justify-between">
                        <span>Sign Up</span>
                        <span class="text-xs font-normal text-gray-300">ลงทะเบียนเกษตรกร</span>
                    </h2>

                    <form id="signupForm" onsubmit="handleSignup(event)" class="w-full space-y-4">
                        
                        <!-- Email Field (Solid Envelope Icon) -->
                        <div class="space-y-1.5">
                            <label for="signup_email" class="block text-sm sm:text-base font-semibold text-gray-200">
                                Email
                            </label>
                            <div class="line-input flex items-center justify-between pb-2">
                                <input type="email" 
                                       id="signup_email" 
                                       name="email" 
                                       required 
                                       placeholder="กรอกอีเมลของคุณ เช่น farmer@example.com"
                                       class="w-full bg-transparent border-none outline-none text-white text-sm sm:text-base font-normal pr-3 placeholder-white/40">
                                <!-- Solid Mail Envelope Icon -->
                                <svg class="w-5 h-5 text-gray-300 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                                    <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Username Field (User Circle Icon) -->
                        <div class="space-y-1.5 pt-1">
                            <label for="signup_username" class="block text-sm sm:text-base font-semibold text-gray-200">
                                Username / ชื่อ-นามสกุล
                            </label>
                            <div class="line-input flex items-center justify-between pb-2">
                                <input type="text" 
                                       id="signup_username" 
                                       name="username" 
                                       required 
                                       placeholder="กรอกชื่อผู้ใช้ หรือชื่อ-นามสกุลจริง"
                                       class="w-full bg-transparent border-none outline-none text-white text-sm sm:text-base font-normal pr-3 placeholder-white/40">
                                <!-- User Circle Icon -->
                                <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Password Field (Lock Icon) -->
                        <div class="space-y-1.5 pt-1">
                            <label for="signup_password" class="block text-sm sm:text-base font-semibold text-gray-200">
                                Password
                            </label>
                            <div class="line-input flex items-center justify-between pb-2">
                                <input type="password" 
                                       id="signup_password" 
                                       name="password" 
                                       required 
                                       minlength="6"
                                       placeholder="กำหนดรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)"
                                       class="w-full bg-transparent border-none outline-none text-white text-sm sm:text-base font-normal pr-3 placeholder-white/40">
                                <!-- Lock Icon -->
                                <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Terms Checkbox -->
                        <div class="pt-2 flex justify-center">
                            <label class="flex items-center gap-2.5 cursor-pointer text-xs sm:text-sm text-gray-200 select-none">
                                <input type="checkbox" 
                                       id="agreeTerms" 
                                       required 
                                       class="w-4 h-4 rounded bg-transparent border border-white/50 accent-white cursor-pointer">
                                <span>
                                    I Agree To <b class="text-white font-bold">Terms</b> And <b class="text-white font-bold">Conditions</b> Of Service
                                </span>
                            </label>
                        </div>

                        <!-- Feedback Banner -->
                        <div id="signupFeedback" class="hidden text-xs sm:text-sm rounded-xl p-3 text-center font-medium transition-all"></div>

                        <!-- Action Row: Sign Up > & Have An Account? -->
                        <div class="flex items-center justify-between pt-5 px-1">
                            <button type="submit" 
                                    id="btnSignup"
                                    class="btn-outline-pill rounded-full px-8 sm:px-10 py-2.5 sm:py-3 text-sm sm:text-base font-semibold tracking-wide text-white shadow-md flex items-center gap-2.5 cursor-pointer">
                                <span>Sign Up</span>
                                <span class="font-extrabold text-sm sm:text-base">&gt;</span>
                            </button>

                            <button type="button" 
                                    onclick="toggleAuthMode('login')" 
                                    class="text-sm sm:text-base text-gray-200 hover:text-white underline font-semibold transition cursor-pointer">
                                Have An Account?
                            </button>
                        </div>

                    </form>
                </div>

                <!-- =============================================================
                     FORM VIEW 2: SIGN IN / LOG IN (เข้าสู่ระบบ)
                     ============================================================= -->
                <div id="loginSection" class="w-full <?= $initial_mode === 'login' ? '' : 'hidden' ?>">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white text-left w-full mb-6 tracking-tight flex items-center justify-between">
                        <span>Sign In</span>
                        <span class="text-xs font-normal text-gray-300">เข้าสู่ระบบ</span>
                    </h2>

                    <form id="loginForm" onsubmit="handleLogin(event)" class="w-full space-y-4">
                        
                        <!-- Username / Email Field -->
                        <div class="space-y-1.5">
                            <label for="login_identifier" class="block text-sm sm:text-base font-semibold text-gray-200">
                                Email / Username / Phone
                            </label>
                            <div class="line-input flex items-center justify-between pb-2">
                                <input type="text" 
                                       id="login_identifier" 
                                       name="username" 
                                       required 
                                       placeholder="กรอกอีเมล, ชื่อผู้ใช้ หรือเบอร์โทรศัพท์"
                                       class="w-full bg-transparent border-none outline-none text-white text-sm sm:text-base font-normal pr-3 placeholder-white/40">
                                <!-- Solid Envelope / User Icon -->
                                <svg class="w-5 h-5 text-gray-300 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                                    <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="space-y-1.5 pt-1">
                            <label for="login_password" class="block text-sm sm:text-base font-semibold text-gray-200">
                                Password
                            </label>
                            <div class="line-input flex items-center justify-between pb-2">
                                <input type="password" 
                                       id="login_password" 
                                       name="password" 
                                       required 
                                       placeholder="กรอกรหัสผ่านของคุณ"
                                       class="w-full bg-transparent border-none outline-none text-white text-sm sm:text-base font-normal pr-3 placeholder-white/40">
                                <!-- Lock Icon -->
                                <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Feedback Banner -->
                        <div id="loginFeedback" class="hidden text-xs sm:text-sm rounded-xl p-3 text-center font-medium transition-all"></div>

                        <!-- Action Row: Sign In > & Don't Have An Account? -->
                        <div class="flex items-center justify-between pt-5 px-1">
                            <button type="submit" 
                                    id="btnLogin"
                                    class="btn-outline-pill rounded-full px-8 sm:px-10 py-2.5 sm:py-3 text-sm sm:text-base font-semibold tracking-wide text-white shadow-md flex items-center gap-2.5 cursor-pointer">
                                <span>Sign In</span>
                                <span class="font-extrabold text-sm sm:text-base">&gt;</span>
                            </button>

                            <button type="button" 
                                    onclick="toggleAuthMode('signup')" 
                                    class="text-sm sm:text-base text-gray-200 hover:text-white underline font-semibold transition cursor-pointer">
                                Don't Have An Account?
                            </button>
                        </div>

                    </form>
                </div>

                <!-- =============================================================
                     QUICK TEST ACCOUNTS / DEMO ROLES (Minimal Accordion / Chips)
                     ============================================================= -->
                <div class="w-full mt-8 pt-6 border-t border-white/15">
                    <div class="flex items-center justify-between mb-3 text-xs text-white/70">
                        <span class="font-semibold">🚀 บัญชีทดสอบระบบตามหน่วยงาน (1-Click Test)</span>
                        <span class="text-[10px] text-white/50">Pass: admin123</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <button type="button" onclick="quickLogin('SUPER_ADMIN', 'admin@georubberwatch.com')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>👑</span> <span class="truncate font-medium">Super Admin</span>
                        </button>
                        <button type="button" onclick="quickLogin('FORESTRY_ADMIN', 'suratthani.forest@forest.go.th')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>🌲</span> <span class="truncate font-medium">กรมป่าไม้</span>
                        </button>
                        <button type="button" onclick="quickLogin('LAND_ADMIN', 'land.surat@dol.go.th')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>📜</span> <span class="truncate font-medium">กรมที่ดิน</span>
                        </button>
                        <button type="button" onclick="quickLogin('RAOT_ADMIN', 'surat.eudr@raot.co.th')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>🌱</span> <span class="truncate font-medium">การยาง (RAOT)</span>
                        </button>
                        <button type="button" onclick="quickLogin('COOP_ADMIN', 'manager@suratrubber-coop.com')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>🏢</span> <span class="truncate font-medium">สหกรณ์สวนยาง</span>
                        </button>
                        <button type="button" onclick="quickLogin('farmer', 'somchai.farmer@georubberwatch.com')" class="demo-chip px-2.5 py-2 rounded-xl text-left text-xs text-white flex items-center gap-1.5 cursor-pointer">
                            <span>👨‍🌾</span> <span class="truncate font-medium">เกษตรกรตัวอย่าง</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer Section inside Frosted Glass Pane -->
            <div class="w-full max-w-[540px] text-center text-xs text-white/60 pt-6 mt-4 border-t border-white/10">
                &copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี
            </div>

        </div>

    </div>

    <!-- ================= AUTH SCRIPT & DATABASE INTEGRATION ================= -->
    <script>
        const API_BASE = window.location.pathname.includes('/pages/') ? '../api' : 'api';
        const redirectUrl = <?= json_encode($safe_redirect) ?>;

        // Toggle between Sign Up and Sign In views
        function toggleAuthMode(mode) {
            const signupSec = document.getElementById('signupSection');
            const loginSec = document.getElementById('loginSection');
            const signupFeedback = document.getElementById('signupFeedback');
            const loginFeedback = document.getElementById('loginFeedback');

            signupFeedback.classList.add('hidden');
            loginFeedback.classList.add('hidden');

            if (mode === 'login') {
                signupSec.classList.add('hidden');
                loginSec.classList.remove('hidden');
            } else {
                signupSec.classList.remove('hidden');
                loginSec.classList.add('hidden');
            }
        }

        // Handle Signup (ลงทะเบียนใหม่)
        async function handleSignup(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSignup');
            const feedback = document.getElementById('signupFeedback');
            const email = document.getElementById('signup_email').value.trim();
            const username = document.getElementById('signup_username').value.trim();
            const password = document.getElementById('signup_password').value.trim();
            const agree = document.getElementById('agreeTerms').checked;

            if (!agree) {
                alert("กรุณายอมรับเงื่อนไขและข้อตกลงการให้บริการ");
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `<span>Signing Up...</span>`;

            try {
                const res = await fetch(`${API_BASE}/auth.php?action=register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: email,
                        username: username,
                        full_name: username,
                        password: password
                    })
                });

                const data = await res.json();

                if (data.status === 'success' || data.success === true) {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-emerald-950/80 text-emerald-300 border border-emerald-600/60 shadow-lg';
                    feedback.innerHTML = `✅ ลงทะเบียนสำเร็จ! ยินดีต้อนรับคุณ <strong>${data.user?.full_name || username}</strong> กำลังนำท่านเข้าสู่ระบบ...`;
                    feedback.classList.remove('hidden');

                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1200);
                } else {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                    feedback.textContent = `❌ ${data.message || 'ไม่สามารถลงทะเบียนได้'}`;
                    feedback.classList.remove('hidden');
                    resetBtn(btn, 'Sign Up');
                }

            } catch (err) {
                feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                feedback.textContent = '⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง';
                feedback.classList.remove('hidden');
                resetBtn(btn, 'Sign Up');
            }
        }

        // Handle Login (เข้าสู่ระบบ)
        async function handleLogin(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            const feedback = document.getElementById('loginFeedback');
            const identifier = document.getElementById('login_identifier').value.trim();
            const password = document.getElementById('login_password').value.trim();

            btn.disabled = true;
            btn.innerHTML = `<span>Signing In...</span>`;

            try {
                const res = await fetch(`${API_BASE}/auth.php?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: identifier,
                        password: password
                    })
                });

                const data = await res.json();

                if (data.status === 'success' || data.success === true) {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-emerald-950/80 text-emerald-300 border border-emerald-600/60 shadow-lg';
                    feedback.innerHTML = `🎉 เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับคุณ <strong>${data.user?.full_name}</strong> (${data.user?.role}) กำลังนำท่านเข้าสู่ระบบ...`;
                    feedback.classList.remove('hidden');

                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 800);
                } else {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                    feedback.textContent = `❌ ${data.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'}`;
                    feedback.classList.remove('hidden');
                    resetBtn(btn, 'Sign In');
                }

            } catch (err) {
                feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                feedback.textContent = '⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง';
                feedback.classList.remove('hidden');
                resetBtn(btn, 'Sign In');
            }
        }

        // Quick 1-Click Demo Login for Testing
        async function quickLogin(role, email) {
            const feedback = document.getElementById('loginSection').classList.contains('hidden') 
                ? document.getElementById('signupFeedback') 
                : document.getElementById('loginFeedback');

            feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-blue-950/80 text-blue-200 border border-blue-600/60 shadow-lg';
            feedback.innerHTML = `⏳ กำลังเข้าสู่ระบบด้วยสิทธิ์ <strong>${role}</strong>...`;
            feedback.classList.remove('hidden');

            try {
                const res = await fetch(`${API_BASE}/auth.php?action=switch_demo_user`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role: role, email: email })
                });
                const data = await res.json();

                if (data.success) {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-emerald-950/80 text-emerald-300 border border-emerald-600/60 shadow-lg';
                    feedback.innerHTML = `🎉 ${data.message} กำลังนำท่านเข้าสู่ระบบ...`;
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 600);
                } else {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                    feedback.textContent = `❌ ${data.message || 'ไม่พบบัญชีผู้ใช้'}`;
                }
            } catch (err) {
                feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-600/60 shadow-lg';
                feedback.textContent = '⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
            }
        }

        function resetBtn(btn, label) {
            btn.disabled = false;
            btn.innerHTML = `<span>${label}</span> <span class="font-extrabold text-sm sm:text-base">&gt;</span>`;
        }
    </script>
</body>
</html>
