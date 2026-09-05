<?php
/**
 * GeoRubber Watch • Sign Up & Login (login.php)
 */
session_start();
$redirect = $_GET['redirect'] ?? 'index.php';
$clean_redirect = 'index.php';
if (!empty($redirect)) {
    $r = basename($redirect);
    if (preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $r)) {
        $clean_redirect = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GeoRubber Watch</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Google+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Google Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            background-color: #0b1118;
        }

        /* Fullscreen Bridge Background Layer (โหลดจาก Local Asset หมดปัญหาลิงก์หมดอายุ) */
        .bg-bridge {
            background-image: 
                linear-gradient(to right, rgba(10, 15, 22, 0.35) 0%, rgba(10, 15, 22, 0.15) 50%, rgba(10, 15, 22, 0.35) 100%),
                url('img/bridge_wallpaper.jpg'),
                url('img/login_nature_hero.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Frosted Glass Pane on the right side - 25% soft blur */
        .glass-pane {
            background: rgba(12, 18, 25, 0.32);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: -15px 0 35px rgba(0, 0, 0, 0.2);
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
    </style>
</head>
<body class="min-h-screen w-full bg-bridge text-white flex flex-col justify-between selection:bg-white selection:text-black">

    <!-- Container wrapper with Split-Screen layout -->
    <div class="min-h-screen w-full flex flex-col md:flex-row">
        
        <!-- ================= LEFT SECTION: UNOBSTRUCTED VIEW ================= -->
        <div class="hidden md:block md:w-[48%] lg:w-[50%] z-10"></div>

        <!-- ================= RIGHT SECTION: FROSTED GLASS SIGN UP FORM ================= -->
        <div class="w-full md:w-[52%] lg:w-[50%] min-h-screen glass-pane flex flex-col justify-center items-center px-6 sm:px-12 lg:px-16 py-12 z-20">
            
            <div class="w-full max-w-[540px] mx-auto flex flex-col items-center">
                
                <!-- 1. Header: Welcome to GeoRubber Watch (หัวข้อใหญ่ 1 บรรทัด พร้อมคำอธิบายจัดกึ่งกลางตรงเป๊ะ) -->
                <div class="text-center mb-8 w-full flex flex-col items-center">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-[38px] xl:text-[40px] font-extrabold text-white tracking-tight drop-shadow-md whitespace-nowrap text-center w-full">
                        Welcome to GeoRubber Watch
                    </h1>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-200 font-medium leading-relaxed mt-3 text-center w-full drop-shadow">
                        เข้าสู่ระบบเพื่อบริหารจัดการแปลงปลูก บันทึกผลผลิต<br>
                        และตรวจสอบความสอดคล้องตามมาตรฐาน EUDR
                    </p>
                    <div class="w-16 h-0.5 bg-white/30 mx-auto mt-5 rounded-full"></div>
                </div>

                <!-- 2. Sign Up Title (จัดวางตำแหน่งให้ตรงกับช่อง Email ด้านล่าง) -->
                <h2 id="formTitle" class="text-3xl sm:text-4xl font-extrabold text-white text-left w-full mb-7 tracking-tight">
                    Sign Up
                </h2>

                <form id="signupForm" onsubmit="handleSubmit(event)" class="w-full space-y-5">
                    
                    <!-- 3. Email Field (ไอคอนใหม่สไตล์ Solid Envelope แบบคมชัด) -->
                    <div id="emailFieldGroup" class="space-y-1.5">
                        <label for="email" class="block text-sm sm:text-base font-semibold text-gray-200">
                            Email
                        </label>
                        <div class="line-input flex items-center justify-between pb-2">
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required 
                                   class="w-full bg-transparent border-none outline-none text-white text-base sm:text-lg font-normal pr-3 placeholder-transparent">
                            <!-- Solid Mail Envelope Icon -->
                            <svg class="w-5 h-5 text-gray-300 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"/>
                                <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 4. Username Field (เพิ่มขนาด Label & Input & Icon) -->
                    <div class="space-y-1.5 pt-1">
                        <label id="usernameLabel" for="username" class="block text-sm sm:text-base font-semibold text-gray-200">
                            Username
                        </label>
                        <div class="line-input flex items-center justify-between pb-2">
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   required 
                                   class="w-full bg-transparent border-none outline-none text-white text-base sm:text-lg font-normal pr-3 placeholder-transparent">
                            <!-- User Circle Icon -->
                            <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- 5. Password Field (เพิ่มขนาด Label & Input & Icon) -->
                    <div class="space-y-1.5 pt-1">
                        <label for="password" class="block text-sm sm:text-base font-semibold text-gray-200">
                            Password
                        </label>
                        <div class="line-input flex items-center justify-between pb-2">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   class="w-full bg-transparent border-none outline-none text-white text-base sm:text-lg font-normal pr-3 placeholder-transparent">
                            <!-- Lock Icon -->
                            <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Terms Checkbox (ขนาดใหญ่ขึ้น จัดกึ่งกลาง) -->
                    <div id="termsGroup" class="pt-2 flex justify-center">
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
                    <div id="signupFeedback" class="hidden text-xs sm:text-sm rounded-xl p-3 text-center font-medium"></div>

                    <!-- 6. Action Row: Sign Up > & Have An Account? (ขนาดใหญ่ขึ้น สมดุล) -->
                    <div class="flex items-center justify-between pt-6 px-1">
                        <!-- Submit Button -->
                        <button type="submit" 
                                id="btnSignup"
                                class="btn-outline-pill rounded-full px-8 sm:px-10 py-2.5 sm:py-3 text-sm sm:text-base font-semibold tracking-wide text-white shadow-md flex items-center gap-2.5 cursor-pointer">
                            <span id="btnText">Sign Up</span>
                            <span class="font-extrabold text-sm sm:text-base">&gt;</span>
                        </button>

                        <!-- Have An Account? Link -->
                        <button type="button" 
                                id="toggleModeBtn"
                                onclick="toggleAuthMode()"
                                class="text-sm sm:text-base text-gray-200 hover:text-white underline font-semibold transition cursor-pointer">
                            Have An Account?
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- ================= AUTH SCRIPT ================= -->
    <script>
        const API_BASE = window.location.pathname.includes('/pages/') ? '../api' : 'api';
        const REDIRECT_URL = <?= json_encode($clean_redirect) ?>;
        let isSignupMode = true;

        function toggleAuthMode() {
            isSignupMode = !isSignupMode;
            const formTitle = document.getElementById('formTitle');
            const emailGroup = document.getElementById('emailFieldGroup');
            const emailInput = document.getElementById('email');
            const usernameLabel = document.getElementById('usernameLabel');
            const termsGroup = document.getElementById('termsGroup');
            const agreeTerms = document.getElementById('agreeTerms');
            const btnText = document.getElementById('btnText');
            const toggleBtn = document.getElementById('toggleModeBtn');
            const feedback = document.getElementById('signupFeedback');

            feedback.classList.add('hidden');

            if (isSignupMode) {
                formTitle.textContent = 'Sign Up';
                document.title = 'Sign Up - GeoRubber Watch';
                emailGroup.classList.remove('hidden');
                emailInput.required = true;
                usernameLabel.textContent = 'Username';
                termsGroup.classList.remove('hidden');
                agreeTerms.required = true;
                btnText.textContent = 'Sign Up';
                toggleBtn.textContent = 'Have An Account?';
            } else {
                formTitle.textContent = 'Sign In';
                document.title = 'Sign In - GeoRubber Watch';
                emailGroup.classList.add('hidden');
                emailInput.required = false;
                usernameLabel.textContent = 'Username / Email';
                termsGroup.classList.add('hidden');
                agreeTerms.required = false;
                btnText.textContent = 'Sign In';
                toggleBtn.textContent = "Don't Have An Account? Sign Up";
            }
        }

        async function handleSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSignup');
            const feedback = document.getElementById('signupFeedback');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (isSignupMode) {
                // Sign Up Action
                const email = document.getElementById('email').value.trim();
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
                        feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-emerald-950/80 text-emerald-300 border border-emerald-700/60 shadow-lg';
                        feedback.textContent = '✅ ลงทะเบียนสำเร็จ! กำลังพาเข้าสู่ระบบ...';
                        feedback.classList.remove('hidden');

                        setTimeout(() => {
                            window.location.href = REDIRECT_URL;
                        }, 1000);
                    } else {
                        feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-700/60 shadow-lg';
                        feedback.textContent = `❌ ${data.message || 'ไม่สามารถลงทะเบียนได้'}`;
                        feedback.classList.remove('hidden');
                        resetBtn('Sign Up');
                    }
                } catch (err) {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-700/60 shadow-lg';
                    feedback.textContent = '⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                    feedback.classList.remove('hidden');
                    resetBtn('Sign Up');
                }

            } else {
                // Sign In Action
                btn.disabled = true;
                btn.innerHTML = `<span>Signing In...</span>`;

                try {
                    const res = await fetch(`${API_BASE}/auth.php?action=login`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            username: username,
                            password: password
                        })
                    });

                    const data = await res.json();

                    if (data.status === 'success' || data.success === true) {
                        feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-emerald-950/80 text-emerald-300 border border-emerald-700/60 shadow-lg';
                        feedback.textContent = '✅ เข้าสู่ระบบสำเร็จ! กำลังพาไปยังระบบ...';
                        feedback.classList.remove('hidden');

                        setTimeout(() => {
                            window.location.href = REDIRECT_URL;
                        }, 800);
                    } else {
                        feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-700/60 shadow-lg';
                        feedback.textContent = `❌ ${data.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'}`;
                        feedback.classList.remove('hidden');
                        resetBtn('Sign In');
                    }
                } catch (err) {
                    feedback.className = 'text-xs sm:text-sm rounded-xl p-3 text-center font-medium bg-red-950/80 text-red-300 border border-red-700/60 shadow-lg';
                    feedback.textContent = '⚠️ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                    feedback.classList.remove('hidden');
                    resetBtn('Sign In');
                }
            }
        }

        function resetBtn(label) {
            const btn = document.getElementById('btnSignup');
            btn.disabled = false;
            btn.innerHTML = `<span>${label}</span> <span class="font-extrabold text-sm sm:text-base">&gt;</span>`;
        }
    </script>
</body>
</html>
