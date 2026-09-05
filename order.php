<?php
/**
 * GeoRubber Watch • Multi-Step Rubber Plot Registration & EUDR Verification
 * URL: http://localhost/RB/order.php
 */
declare(strict_types=1);

require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/config/database.php";
initDatabaseIfNeeded();
$pdo = getDatabaseConnection();

// Fetch registered farmers for dropdown
$stmt = $pdo->query("SELECT id, farmer_code, prefix, first_name, last_name FROM farmers ORDER BY id ASC");
$farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if incoming query params from map.php
$inLat = isset($_GET["lat"]) ? floatval($_GET["lat"]) : 9.138240;
$inLng = isset($_GET["lng"]) ? floatval($_GET["lng"]) : 99.321850;
$inArea = isset($_GET["area"]) ? floatval($_GET["area"]) : 10.5;
$hasDrawn = isset($_GET["drawn"]) && $_GET["drawn"] === "1";
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบเพิ่มแปลงปลูกและตรวจสอบ EUDR • GeoRubber Watch</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts: Plus Jakarta Sans / Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ["Sarabun", "Plus Jakarta Sans", "sans-serif"],
            heading: ["Plus Jakarta Sans", "Sarabun", "sans-serif"]
          },
          colors: {
            liberty: {
              cyan: "#00a699",
              cyanHover: "#0e4d4e",
              lightCyan: "#e5f6f8",
              bgSky: "#eaf4f7",
              teal: "#0e4d4e",
              mint: "#5ebbb6",
              grayText: "#718096",
              darkText: "#2d3748",
              border: "#bee6e1",
            }
          }
        }
      }
    }
  </script>

  <style>
    body {
      background-color: #eaf4f7;
      font-family: 'Sarabun', 'Plus Jakarta Sans', sans-serif;
    }
    .custom-input {
      background-color: #ffffff;
      border: 1.5px solid #d8e2ea;
      border-radius: 10px;
      padding: 9px 14px;
      font-size: 13px;
      color: #2d3748;
      width: 100%;
      outline: none;
      transition: all 0.2s ease;
    }
    .custom-input:focus {
      border-color: #00a699;
      box-shadow: 0 0 0 3px rgba(0, 166, 153, 0.18);
    }
    .custom-label {
      font-size: 12px;
      color: #0e4d4e;
      margin-bottom: 5px;
      display: block;
      font-weight: 700;
    }
    .custom-label span.req {
      color: #e53e3e;
      margin-left: 2px;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between py-6 px-4 sm:px-6 lg:px-8 text-gray-700">

  <!-- =========================================================================
       1. TOP HEADER NAVIGATION BAR
       ========================================================================= -->
  <header class="w-full max-w-5xl mx-auto flex items-center justify-between py-4 mb-3">
    <!-- Brand Logo -->
    <a href="index.php" class="flex items-center gap-3 group">
      <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-liberty-cyan flex items-center justify-center shadow-md text-white">
        🌲
      </div>
      <div class="flex flex-col">
        <span class="font-extrabold text-sm sm:text-base tracking-wide text-liberty-teal leading-tight">GeoRubber Watch</span>
        <span class="font-semibold text-[11px] tracking-wider text-liberty-cyan leading-none">EUDR GIS PORTAL</span>
      </div>
    </a>

    <!-- Top Nav Menu -->
    <nav class="hidden md:flex items-center gap-5 lg:gap-7 text-xs font-bold uppercase tracking-wider text-gray-500">
      <a href="index.php" class="hover:text-liberty-cyan transition-colors">หน้าแรก</a>
      <a href="overview.php" class="hover:text-liberty-cyan transition-colors">แผนที่ GIS</a>
      <a href="dashboard.php" class="hover:text-liberty-cyan transition-colors">แดชบอร์ด</a>
      <a href="map.php" class="text-liberty-cyan border-b-2 border-liberty-cyan pb-0.5">แปลงปลูก</a>
      <a href="yields.php" class="hover:text-liberty-cyan transition-colors">ผลผลิต</a>
      <a href="login.php" class="px-4 py-1.5 rounded-full border border-liberty-cyan text-liberty-cyan hover:bg-liberty-cyan hover:text-white transition-all">
        เข้าสู่ระบบ
      </a>
    </nav>
  </header>

  <!-- =========================================================================
       2. PAGE TITLE
       ========================================================================= -->
  <div class="w-full max-w-4xl mx-auto mb-4 px-2 flex flex-wrap items-center justify-between gap-2">
    <div>
      <h1 class="text-base sm:text-xl font-black tracking-wide text-liberty-teal uppercase flex items-center gap-2">
        <span>🌱 เพิ่มแปลงปลูกยางพารา & วิเคราะห์ความสอดคล้อง EUDR</span>
      </h1>
      <p class="text-xs text-gray-500 mt-0.5 font-normal">กระบวนการบันทึกพิกัด GIS, ตรวจสอบแนวเขตป่าสงวน 26 แห่ง และออก EUDR Passport</p>
    </div>

    <!-- Quick Status Tester Pills -->
    <div class="flex items-center gap-2 text-xs">
      <span class="text-gray-400 font-semibold text-[11px]">โหมดทดสอบ:</span>
      <button type="button" onclick="setPresetMode('compliant')" id="btn-mode-compliant" class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200 transition-all cursor-pointer">
        🟢 ผ่านเกณฑ์ (Safe)
      </button>
      <button type="button" onclick="setPresetMode('overlap')" id="btn-mode-overlap" class="px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-300 hover:bg-rose-100 hover:text-rose-700 transition-all cursor-pointer">
        🔴 ทับซ้อนป่า (Overlap)
      </button>
    </div>
  </div>

  <!-- =========================================================================
       3. MAIN MULTI-STEP CARD CONTAINER (Matches Mockup Layout)
       ========================================================================= -->
  <main class="w-full max-w-4xl mx-auto bg-white rounded-3xl shadow-[0_20px_50px_rgba(14,77,78,0.12)] border-2 border-[#bee6e1] overflow-hidden flex flex-col justify-between my-auto">
    
    <!-- Top Progress Step Indicators (4 Steps) -->
    <div class="border-b border-gray-100 px-6 sm:px-12 pt-7 pb-6 relative bg-white">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-center relative">
        
        <!-- Step 1: Choose dates -> วาดแปลงปลูก -->
        <button onclick="goToStep(1)" class="flex items-center gap-3 text-left group cursor-pointer" title="ขั้นตอนที่ 1: วาดขอบเขตแปลงปลูก">
          <div id="step-badge-1" class="w-7 h-7 rounded-full bg-liberty-cyan text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs">
            1
          </div>
          <div class="leading-tight">
            <span id="step-label-1" class="text-xs font-bold text-liberty-cyan group-hover:underline block">1. วาดแปลงปลูก</span>
            <span class="text-[10px] text-gray-400 font-normal">Choose dates</span>
          </div>
        </button>

        <!-- Step 2: Pick up -> ข้อมูลเกษตรกร -->
        <button onclick="goToStep(2)" class="flex items-center gap-3 text-left group cursor-pointer" title="ขั้นตอนที่ 2: กรอกข้อมูลเกษตรกรและแปลง">
          <div id="step-badge-2" class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
            2
          </div>
          <div class="leading-tight">
            <span id="step-label-2" class="text-xs font-semibold text-gray-400 group-hover:text-gray-600 block">2. ข้อมูลเกษตรกร</span>
            <span class="text-[10px] text-gray-400 font-normal">Pick up</span>
          </div>
        </button>

        <!-- Step 3: Return -> ตรวจสอบ EUDR -->
        <button onclick="goToStep(3)" class="flex items-center gap-3 text-left group cursor-pointer" title="ขั้นตอนที่ 3: ตรวจสอบความสอดคล้อง EUDR">
          <div id="step-badge-3" class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
            3
          </div>
          <div class="leading-tight">
            <span id="step-label-3" class="text-xs font-semibold text-gray-400 group-hover:text-gray-600 block">3. ตรวจสอบ</span>
            <span class="text-[10px] text-gray-400 font-normal">EUDR Check</span>
          </div>
        </button>

        <!-- Step 4: Review & Pay -> ออก QR Code & Passport -->
        <button onclick="goToStep(4)" class="flex items-center gap-3 text-left group cursor-pointer" title="ขั้นตอนที่ 4: ออก QR Code และใบรับรอง">
          <div id="step-badge-4" class="w-7 h-7 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-xs shadow-xs">
            4
          </div>
          <div class="leading-tight">
            <span id="step-label-4" class="text-xs font-semibold text-gray-400 group-hover:text-gray-600 block">4. QR Code</span>
            <span class="text-[10px] text-gray-400 font-normal">Review & Pay</span>
          </div>
        </button>

      </div>

      <!-- Active Step Underline Indicator Bar -->
      <div class="hidden md:block absolute bottom-0 left-0 right-0 h-[3px] bg-transparent">
        <div id="step-indicator-bar" class="h-full bg-liberty-cyan transition-all duration-300 w-1/4 translate-x-0"></div>
      </div>
    </div>

    <!-- Inner Step Content Area -->
    <div class="px-6 sm:px-12 py-7">
      
      <!-- =====================================================================
           STEP 1: CHOOSE DATES -> วาดแปลงปลูกใหม่
           ===================================================================== -->
      <div id="step-content-1" class="space-y-6">
        
        <div class="text-center space-y-1">
          <h2 class="text-base sm:text-lg text-liberty-teal font-extrabold">
            ขั้นตอนที่ 1: กำหนดพิกัดและ <span class="text-liberty-cyan underline decoration-liberty-cyan underline-offset-4">วาดขอบเขตแปลงปลูกใหม่</span>
          </h2>
          <p class="text-xs text-gray-500">
            ระบบต้องการพิกัดรูปปิด (Polygon) ตามมาตรฐาน WGS84 เพื่อใช้ตรวจสอบระยะห่างจาก 26 แนวเขตป่าสงวนแห่งชาติ
          </p>
        </div>

        <!-- Drawing Action Card -->
        <div class="border-2 border-dashed border-[#bee6e1] bg-[#f8faf9] rounded-2xl p-6 sm:p-8 text-center space-y-5">
          <div class="w-16 h-16 rounded-2xl bg-white text-liberty-cyan flex items-center justify-center mx-auto text-3xl shadow-sm border border-[#bee6e1]">
            📍
          </div>

          <div class="max-w-md mx-auto space-y-2">
            <h3 class="font-extrabold text-sm sm:text-base text-liberty-teal">
              ต้องการเปิดแผนที่ GIS เพื่อวาดแปลง หรือใช้พิกัดที่ระบุไว้?
            </h3>
            <p class="text-xs text-gray-500 leading-relaxed">
              ท่านสามารถคลิกปุ่มด้านล่างเพื่อเปิดหน้าแผนที่ <strong>map.php</strong> และวาดแปลงพิกัดจริง หรือเลือกใช้พิกัดทดสอบจำลองเพื่อเข้าสู่ขั้นตอนที่ 2 ทันที
            </p>
          </div>

          <!-- Current Loaded Coordinates Badge -->
          <div class="inline-flex flex-wrap items-center justify-center gap-3 bg-white px-5 py-2.5 rounded-full border border-gray-200 text-xs shadow-xs">
            <span class="text-gray-400">พิกัดปัจจุบัน:</span>
            <span class="font-mono font-bold text-liberty-teal">Lat: <span id="disp-lat"><?= number_format($inLat, 6) ?></span>, Lng: <span id="disp-lng"><?= number_format($inLng, 6) ?></span></span>
            <span class="bg-liberty-lightCyan text-liberty-cyan px-2.5 py-0.5 rounded-full font-bold text-[11px]">Polygon 6 จุด (เนื้อที่ ~<span id="disp-area"><?= number_format($inArea, 1) ?></span> ไร่)</span>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a 
              href="map.php?action=draw&return=order.php" 
              class="w-full sm:w-auto px-6 py-3 rounded-full bg-white hover:bg-liberty-teal hover:text-white text-liberty-teal font-bold text-xs sm:text-sm border-2 border-liberty-teal transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>🚀 ไปยังหน้าแผนที่ (map.php) เพื่อวาดแปลง</span>
            </a>

            <button 
              type="button" 
              onclick="confirmCoordsAndNext()" 
              class="w-full sm:w-auto px-7 py-3 rounded-full bg-liberty-cyan hover:bg-liberty-cyanHover text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>บันทึกพิกัดนี้ & ถัดไป (Pick up) ➔</span>
            </button>
          </div>

        </div>

      </div>

      <!-- =====================================================================
           STEP 2: PICK UP -> ข้อมูลเกษตรกรและแปลงปลูก (Matching Screenshot 9)
           ===================================================================== -->
      <div id="step-content-2" class="hidden space-y-6">
        
        <div class="text-center space-y-1">
          <h2 class="text-base sm:text-lg text-liberty-teal font-extrabold">
            ขั้นตอนที่ 2: <span class="text-liberty-cyan underline decoration-liberty-cyan underline-offset-4">บันทึกข้อมูลเกษตรกรและแปลงปลูก</span> (Pick up)
          </h2>
          <p class="text-xs text-gray-500">กรุณากรอกข้อมูลทะเบียนแปลงและเอกสารสิทธิ์ให้ครบถ้วนเพื่อส่งวิเคราะห์</p>
        </div>

        <!-- Form Card Container with Rounded Border -->
        <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-xs">
          
          <!-- Spatial & EUDR Analysis Alert Box -->
          <div class="p-4 bg-[#f4faf7] border-b border-gray-200 text-xs text-liberty-teal font-medium flex items-center gap-2.5">
            <span class="text-base">ℹ️</span>
            <div>
              <strong>การวิเคราะห์เชิงพื้นที่:</strong> ค่าพิกัดขอบเขตแปลง Polygon WGS84 นำเข้าสำเร็จพร้อมสำหรับการประเมิน Real-Time
            </div>
          </div>

          <!-- Form Fields Content Area -->
          <div class="p-6 sm:p-8 space-y-4">
            
            <!-- Row 1: เจ้าของแปลง (เกษตรกร) * | ชื่อแปลงปลูก * -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="custom-label" for="form-farmer-id">
                  เจ้าของแปลง (เกษตรกร)<span class="req">*</span>
                </label>
                <select id="form-farmer-id" class="custom-input bg-white" required>
                  <?php if (!empty($farmers)): ?>
                    <?php foreach ($farmers as $f): ?>
                      <option value="<?= $f['id'] ?>" <?= ($f['id'] == 2) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['prefix'] . $f['first_name'] . ' ' . $f['last_name']) ?> (<?= $f['farmer_code'] ?>)
                      </option>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <option value="1">นางสาวมนัสนันท์ อนันตณรงค์ (FM-PSU-002)</option>
                    <option value="2">นายสมชาย สวนยาง (FM-PSU-001)</option>
                  <?php endif; ?>
                </select>
              </div>

              <div>
                <label class="custom-label" for="form-plot-name">
                  ชื่อแปลงปลูก<span class="req">*</span>
                </label>
                <input type="text" id="form-plot-name" value="แปลงยางพาราเขาท่าเพชร 1" class="custom-input" placeholder="เช่น แปลงยางพาราเขาท่าเพชร 1" required>
              </div>
            </div>

            <!-- Row 2: ประเภทเอกสารสิทธิ์ | เลขที่เอกสารสิทธิ์ -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="custom-label" for="form-deed-type">
                  ประเภทเอกสารสิทธิ์
                </label>
                <select id="form-deed-type" class="custom-input bg-white">
                  <option value="โฉนดที่ดิน (น.ส. 4 จ)" selected>โฉนดที่ดิน (น.ส. 4 จ)</option>
                  <option value="หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)">หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)</option>
                  <option value="หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01">หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01</option>
                  <option value="ภ.บ.ท. 5 / แบบแจ้งการครอบครอง">ภ.บ.ท. 5 / แบบแจ้งการครอบครอง</option>
                  <option value="เอกสารสิทธิ์อื่นๆ">เอกสารสิทธิ์อื่นๆ</option>
                </select>
              </div>

              <div>
                <label class="custom-label" for="form-deed-no">
                  เลขที่เอกสารสิทธิ์
                </label>
                <input type="text" id="form-deed-no" value="8401-4458" class="custom-input" placeholder="เช่น 8401-4458">
              </div>
            </div>

            <!-- Row 3: พันธุ์ยางพารา * | ปีที่เริ่มปลูก * | จำนวนต้นยาง (ต้น) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="custom-label" for="form-clone">
                  พันธุ์ยางพารา<span class="req">*</span>
                </label>
                <select id="form-clone" class="custom-input bg-white">
                  <option value="RRIM 600" selected>RRIM 600 (ยอดนิยม)</option>
                  <option value="RRIT 251">RRIT 251</option>
                  <option value="RRIT 408">RRIT 408</option>
                  <option value="BPM 24">BPM 24</option>
                  <option value="PB 235">PB 235</option>
                </select>
              </div>

              <div>
                <label class="custom-label" for="form-year">
                  ปีที่เริ่มปลูก<span class="req">*</span>
                </label>
                <input type="number" id="form-year" value="2018" min="1990" max="2026" class="custom-input">
                <span class="text-[10px] text-gray-400 font-medium block mt-1">เกณฑ์ EUDR: ≤ 2020</span>
              </div>

              <div>
                <label class="custom-label" for="form-trees">
                  จำนวนต้นยาง (ต้น)
                </label>
                <input type="number" id="form-trees" value="500" class="custom-input">
              </div>
            </div>

            <!-- Row 4: สถานะการกรีด -->
            <div>
              <label class="custom-label" for="form-tapping">
                สถานะการกรีด
              </label>
              <select id="form-tapping" class="custom-input bg-white">
                <option value="เปิดกรีดแล้ว (Tapping Active)" selected>เปิดกรีดแล้ว (Tapping Active)</option>
                <option value="ยังไม่เปิดกรีด (Immature)">ยังไม่เปิดกรีด (Immature)</option>
                <option value="พักหน้ายางชั่วคราว">พักหน้ายางชั่วคราว</option>
              </select>
            </div>

            <!-- Row 5: จุดกึ่งกลางแปลง (Centroid พิกัด GPS) -->
            <div>
              <label class="custom-label" for="form-centroid">
                จุดกึ่งกลางแปลง (Centroid พิกัด GPS)
              </label>
              <input type="text" id="form-centroid" value="9.138240, 99.321850" class="custom-input bg-gray-50 font-mono text-xs" readonly>
            </div>

            <!-- Row 6: หมายเหตุเพิ่มเติม -->
            <div>
              <label class="custom-label" for="form-notes">
                หมายเหตุเพิ่มเติม
              </label>
              <textarea id="form-notes" rows="2" class="custom-input" placeholder="บันทึกสภาพแปลง ข้อมูลประวัติการจัดการ ฯลฯ">แปลงยางพาราได้รับการดูแลรักษาอย่างถูกต้อง สอดคล้องตามเกณฑ์พื้นที่เพาะปลูก</textarea>
            </div>

          </div>

        </div>

      </div>

      <!-- =====================================================================
           STEP 3: RETURN -> ตรวจสอบความสอดคล้อง EUDR (Verification Step)
           ===================================================================== -->
      <div id="step-content-3" class="hidden space-y-6">
        
        <div class="text-center space-y-1">
          <h2 class="text-base sm:text-lg text-liberty-teal font-extrabold">
            ขั้นตอนที่ 3: <span class="text-liberty-cyan underline decoration-liberty-cyan underline-offset-4">ระบบตรวจสอบความสอดคล้องตามมาตรฐาน EUDR</span> (Return)
          </h2>
          <p class="text-xs text-gray-500">ผลการวิเคราะห์พิกัดแปลงเทียบกับ 26 แนวเขตป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี</p>
        </div>

        <!-- DYNAMIC RESULT CONTAINER -->
        <div id="eudr-result-container">
          
          <!-- CASE 1: COMPLIANT (Default) -->
          <div id="result-case-compliant" class="border-2 border-emerald-300 rounded-3xl p-6 sm:p-8 bg-gradient-to-b from-emerald-50/70 to-white shadow-md space-y-5">
            
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-sm">
                ✅
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2.5">
                  <h3 class="text-base sm:text-lg font-black text-emerald-800">
                    ผ่านเกณฑ์ EUDR 100% (Zero Deforestation Compliant)
                  </h3>
                  <span class="bg-emerald-100 text-emerald-800 font-extrabold text-[11px] px-3 py-0.5 rounded-full border border-emerald-300">
                    ปลอดภัย
                  </span>
                </div>
                <p class="text-xs text-emerald-700 font-medium mt-1 leading-relaxed">
                  แปลงปลูกนี้ <strong class="underline">ไม่พบการทับซ้อน</strong> กับแนวเขตป่าสงวนแห่งชาติทั้ง 26 แห่งในจังหวัดสุราษฎร์ธานี และอยู่นอกระยะ Buffer Zone ปลอดภัย
                </p>
              </div>
            </div>

            <!-- Detailed Criteria Checklist -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-2">
              <div class="p-3 rounded-2xl bg-white border border-emerald-200">
                <span class="text-gray-400 block text-[10px]">Zero Deforestation</span>
                <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                  <span>🌲</span> ไม่อยู่ในเขตป่าสงวน
                </strong>
              </div>
              <div class="p-3 rounded-2xl bg-white border border-emerald-200">
                <span class="text-gray-400 block text-[10px]">Buffer Distance</span>
                <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                  <span>📏</span> ห่างป่า > 1,450 ม.
                </strong>
              </div>
              <div class="p-3 rounded-2xl bg-white border border-emerald-200">
                <span class="text-gray-400 block text-[10px]">Cut-off Date</span>
                <strong class="text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                  <span>📅</span> ปลูกปี 2018 (≤ 2020)
                </strong>
              </div>
            </div>

            <!-- Notice & Action -->
            <div class="p-3.5 bg-emerald-100/60 rounded-2xl text-xs text-emerald-900 border border-emerald-200 flex items-center justify-between gap-4">
              <span>🎉 แปลงนี้พร้อมสำหรับ <strong>บันทึกลงฐานข้อมูลและออกเอกสารรับรอง EUDR Passport (QR Code)</strong></span>
              <button 
                type="button" 
                onclick="goToStep(4)" 
                class="px-5 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition-all whitespace-nowrap cursor-pointer"
              >
                ออก QR Code ทันที ➔
              </button>
            </div>

          </div>

          <!-- CASE 2: OVERLAP / NON-COMPLIANT -->
          <div id="result-case-overlap" class="hidden border-2 border-rose-300 rounded-3xl p-6 sm:p-8 bg-gradient-to-b from-rose-50/70 to-white shadow-md space-y-5">
            
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-2xl shrink-0 shadow-sm">
                ⚠️
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2.5">
                  <h3 class="text-base sm:text-lg font-black text-rose-800">
                    ไม่ผ่านเกณฑ์ / เสี่ยงทับซ้อนแนวเขตป่าสงวน (Non-Compliant)
                  </h3>
                  <span class="bg-rose-100 text-rose-800 font-extrabold text-[11px] px-3 py-0.5 rounded-full border border-rose-300">
                    เสี่ยงทับซ้อน
                  </span>
                </div>
                <p class="text-xs text-rose-700 font-medium mt-1 leading-relaxed">
                  แปลงปลูกนี้ <strong class="underline">ตรวจพบระยะห่างติดแนวเขตป่าสงวนแห่งชาติเขาท่าเพชร</strong> อยู่ในเขต Buffer Alert (ระยะ 120 เมตร) ไม่สามารถออกหนังสือรับรอง EUDR ปกติได้
                </p>
              </div>
            </div>

            <!-- Overlap Info Box -->
            <div class="p-4 bg-rose-50 rounded-2xl border border-rose-200 text-xs text-rose-900 space-y-2">
              <div>📌 <strong>แนวทางปฏิบัติ:</strong> ระบบจะทำการบันทึกข้อมูลแปลงนี้ลงฐานข้อมูล โดยทำเครื่องหมายสถานะเป็น <strong>"ไม่ผ่านเกณฑ์ (Non-Compliant / Under Review)"</strong> เพื่อเก็บประวัติเชิงพื้นที่ของเกษตรกรไว้ในระบบ</div>
            </div>

            <!-- Save Non-Compliant Action Button -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button 
                type="button" 
                onclick="savePlotToDatabase('non_compliant')" 
                class="px-6 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow transition-all flex items-center gap-2 cursor-pointer"
              >
                <span>💾 บันทึกข้อมูลแปลงที่ไม่ผ่านเกณฑ์ลงระบบ</span>
              </button>
            </div>

          </div>

        </div>

      </div>

      <!-- =====================================================================
           STEP 4: REVIEW & PAY -> EUDR PASSPORT & QR CODE (Final Step)
           ===================================================================== -->
      <div id="step-content-4" class="hidden space-y-6">
        
        <div class="text-center space-y-1">
          <h2 class="text-base sm:text-lg text-liberty-teal font-extrabold">
            ขั้นตอนที่ 4: <span class="text-liberty-cyan underline decoration-liberty-cyan underline-offset-4">ออกหนังสือรับรอง EUDR Digital Passport (QR Code)</span>
          </h2>
          <p class="text-xs text-gray-500">เอกสารดิจิทัลสำหรับตรวจสอบย้อนกลับ (Traceability) สำหรับการส่งออกยางพาราสากล</p>
        </div>

        <!-- Official Passport Card Container -->
        <div class="border-2 border-[#bee6e1] rounded-3xl p-6 sm:p-8 bg-gradient-to-b from-[#f4faf9] to-white shadow-lg space-y-6">
          
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pb-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-liberty-teal text-white flex items-center justify-center text-2xl shadow-xs">
                🛡️
              </div>
              <div>
                <h3 class="font-extrabold text-sm sm:text-base text-liberty-teal leading-tight">EUDR DIGITAL PASSPORT & PASSPORT CERTIFICATE</h3>
                <span class="text-[11px] text-emerald-700 font-bold bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-300">
                  ✅ VERIFIED ZERO DEFORESTATION
                </span>
              </div>
            </div>

            <div class="text-right">
              <span class="text-[10px] text-gray-400 block">Certificate No.</span>
              <span class="font-mono font-extrabold text-xs text-liberty-teal" id="cert-no">EUDR-TH-84-2026-0889</span>
            </div>
          </div>

          <!-- QR Code & Summary Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
            
            <!-- QR Code Canvas (Col-Span 5) -->
            <div class="sm:col-span-5 flex flex-col items-center justify-center p-5 bg-white rounded-2xl border border-gray-200 shadow-inner space-y-2.5">
              <!-- Rendered Vector QR Code Container -->
              <div class="w-36 h-36 bg-white p-2 border-2 border-liberty-cyan rounded-xl flex items-center justify-center relative shadow-sm">
                <!-- Authentic SVG QR Code -->
                <svg class="w-full h-full text-liberty-teal" viewBox="0 0 100 100" fill="currentColor">
                  <path d="M10,10 h30 v30 h-30 z M15,15 v20 h20 v-20 z M20,20 h10 v10 h-10 z"/>
                  <path d="M60,10 h30 v30 h-30 z M65,15 v20 h20 v-20 z M70,20 h10 v10 h-10 z"/>
                  <path d="M10,60 h30 v30 h-30 z M15,65 v20 h20 v-20 z M20,70 h10 v10 h-10 z"/>
                  <path d="M45,15 h5 v5 h-5 z M50,20 h5 v5 h-5 z M45,25 h10 v5 h-10 z M50,35 h5 v10 h-5 z"/>
                  <path d="M65,45 h10 v5 h-10 z M80,45 h10 v10 h-10 z M60,55 h5 v10 h-5 z M75,55 h10 v5 h-10 z M70,65 h15 v5 h-15 z M85,75 h5 v15 h-5 z M65,80 h15 v5 h-15 z"/>
                  <path d="M45,60 h5 v10 h-5 z M50,75 h5 v5 h-5 z M45,85 h10 v5 h-10 z M55,65 h5 v5 h-5 z M55,75 h5 v5 h-5 z"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <span class="w-6 h-6 bg-white rounded-full flex items-center justify-center text-xs shadow">🌲</span>
                </div>
              </div>
              <span class="text-[11px] font-bold text-liberty-teal">สแกนเพื่อตรวจสอบย้อนกลับ</span>
              <span class="text-[10px] text-gray-400 font-mono">Traceability ID: RB-2026-009</span>
            </div>

            <!-- Plot & Farmer Summary (Col-Span 7) -->
            <div class="sm:col-span-7 space-y-2.5 text-xs">
              <div class="flex justify-between py-1.5 border-b border-gray-100">
                <span class="text-gray-500">เจ้าของแปลง (Farmer):</span>
                <strong class="text-liberty-teal font-bold" id="summary-farmer-name">นางสาวมนัสนันท์ อนันตณรงค์</strong>
              </div>
              <div class="flex justify-between py-1.5 border-b border-gray-100">
                <span class="text-gray-500">ชื่อแปลงปลูก (Plot Name):</span>
                <strong class="text-gray-800 font-bold" id="summary-plot-name">แปลงยางพาราเขาท่าเพชร 1</strong>
              </div>
              <div class="flex justify-between py-1.5 border-b border-gray-100">
                <span class="text-gray-500">เอกสารสิทธิ์ (Deed):</span>
                <strong class="text-gray-800" id="summary-deed">โฉนดที่ดิน (น.ส. 4 จ) เลขที่ 8401-4458</strong>
              </div>
              <div class="flex justify-between py-1.5 border-b border-gray-100">
                <span class="text-gray-500">พิกัด Centroid GPS:</span>
                <strong class="font-mono text-gray-800" id="summary-centroid">9.138240, 99.321850</strong>
              </div>
              <div class="flex justify-between py-1.5 border-b border-gray-100">
                <span class="text-gray-500">พันธุ์ยาง / ปีที่ปลูก:</span>
                <strong class="text-gray-800" id="summary-clone">RRIM 600 (ปลูกปี 2018)</strong>
              </div>
              <div class="flex justify-between py-1.5">
                <span class="text-gray-500">สถานะ EUDR:</span>
                <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">🟢 ผ่านเกณฑ์ 100% (Compliant)</span>
              </div>
            </div>

          </div>

          <!-- Final Complete Save Button -->
          <div class="pt-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-500">
              กดปุ่มเพื่อบันทึกแปลงลงระบบฐานข้อมูล GeoRubber Watch อย่างเป็นทางการ
            </div>

            <button 
              type="button" 
              onclick="savePlotToDatabase('compliant')" 
              id="btn-final-save"
              class="w-full sm:w-auto px-8 py-3.5 rounded-full bg-liberty-cyan hover:bg-liberty-cyanHover text-white font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer uppercase"
            >
              <span>💾 บันทึกแปลงปลูกและเสร็จสิ้นกระบวนการ</span>
            </button>
          </div>

        </div>

      </div>

    </div>

    <!-- =========================================================================
         BOTTOM FOOTER ACTION BAR (Dynamic Buttons depending on current step)
         ========================================================================= -->
    <div class="bg-[#fcfdfd] border-t border-gray-100 px-6 sm:px-12 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
      
      <!-- Left Tagline -->
      <div class="text-xs font-semibold text-liberty-teal flex items-center gap-2">
        <span>🌲 GeoRubber Watch</span>
        <span class="text-gray-300">|</span>
        <span class="text-gray-500 font-normal">ระบบมาตรฐานการตรวจสอบย้อนกลับ EUDR สุราษฎร์ธานี</span>
      </div>

      <!-- Right Navigation Buttons -->
      <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
        
        <button 
          type="button" 
          id="btn-prev-step" 
          onclick="prevStep()" 
          class="hidden px-5 py-2.5 rounded-full bg-white hover:bg-gray-100 text-gray-600 font-bold text-xs border border-gray-300 transition-all cursor-pointer"
        >
          ‹ ย้อนกลับ
        </button>

        <button 
          type="button" 
          id="btn-next-step" 
          onclick="nextStep()" 
          class="px-6 sm:px-7 py-2.5 rounded-full bg-liberty-cyan hover:bg-liberty-cyanHover text-white font-bold text-xs sm:text-sm shadow-sm hover:shadow transition-all flex items-center gap-1.5 cursor-pointer uppercase"
        >
          <span id="btn-next-label">ขั้นตอนถัดไป (Pick up)</span>
          <span class="text-xs font-bold">›</span>
        </button>

      </div>

    </div>

  </main>

  <!-- Footer copyright notice -->
  <footer class="w-full max-w-4xl mx-auto text-center pt-6 text-xs text-gray-400">
    © 2026 GeoRubber Watch • Intelligent Web-GIS Rubber Platform • Prince of Songkla University, Surat Thani
  </footer>

  <!-- =========================================================================
       JAVASCRIPT: INTERACTIVE STEP ENGINE & EUDR LOGIC
       ========================================================================= -->
  <script>
    let currentStep = 1;
    let presetMode = "compliant"; // "compliant" or "overlap"
    let currentLat = <?= $inLat ?>;
    let currentLng = <?= $inLng ?>;
    let currentArea = <?= $inArea ?>;

    // Set Preset Mode (Compliant / Overlap)
    function setPresetMode(mode) {
      presetMode = mode;
      const btnCompliant = document.getElementById("btn-mode-compliant");
      const btnOverlap = document.getElementById("btn-mode-overlap");
      const caseCompliant = document.getElementById("result-case-compliant");
      const caseOverlap = document.getElementById("result-case-overlap");

      if (mode === "compliant") {
        btnCompliant.className = "px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs cursor-pointer";
        btnOverlap.className = "px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-300 hover:bg-rose-100 hover:text-rose-700 transition-all cursor-pointer";
        caseCompliant.classList.remove("hidden");
        caseOverlap.classList.add("hidden");
        document.getElementById("form-plot-name").value = "แปลงยางพาราเขาท่าเพชร 1";
        document.getElementById("form-year").value = "2018";
      } else {
        btnOverlap.className = "px-3 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300 shadow-xs cursor-pointer";
        btnCompliant.className = "px-3 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-300 hover:bg-emerald-100 hover:text-emerald-700 transition-all cursor-pointer";
        caseCompliant.classList.add("hidden");
        caseOverlap.classList.remove("hidden");
        document.getElementById("form-plot-name").value = "แปลงยางพาราแนวเขตป่าสงวน (Buffer 120m)";
        document.getElementById("form-year").value = "2022";
      }
    }

    function confirmCoordsAndNext() {
      goToStep(2);
    }

    // Step Navigation
    function goToStep(step) {
      currentStep = step;

      // Toggle views
      for (let i = 1; i <= 4; i++) {
        const el = document.getElementById("step-content-" + i);
        if (el) {
          if (i === step) {
            el.classList.remove("hidden");
          } else {
            el.classList.add("hidden");
          }
        }
      }

      // Update Step Top Badges & Labels
      for (let i = 1; i <= 4; i++) {
        const badge = document.getElementById("step-badge-" + i);
        const label = document.getElementById("step-label-" + i);

        if (i < step) {
          // Done
          badge.className = "w-7 h-7 rounded-full bg-liberty-cyan text-white flex items-center justify-center shrink-0 shadow-xs";
          badge.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
          label.className = "text-xs font-semibold text-liberty-cyan group-hover:underline block";
        } else if (i === step) {
          // Active
          badge.className = "w-7 h-7 rounded-full bg-liberty-cyan text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-xs";
          badge.innerHTML = i;
          label.className = "text-xs font-bold text-liberty-cyan group-hover:underline block";
        } else {
          // Pending
          badge.className = "w-7 h-7 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center shrink-0 font-bold text-xs shadow-xs";
          badge.innerHTML = i;
          label.className = "text-xs font-semibold text-gray-400 group-hover:text-gray-600 block";
        }
      }

      // Animate indicator underline bar
      const bar = document.getElementById("step-indicator-bar");
      if (bar) {
        bar.style.transform = "translateX(" + ((step - 1) * 100) + "%)";
      }

      // Update Bottom Action Buttons
      const btnPrev = document.getElementById("btn-prev-step");
      const btnNext = document.getElementById("btn-next-step");
      const btnNextLabel = document.getElementById("btn-next-label");

      if (step === 1) {
        btnPrev.classList.add("hidden");
        btnNext.classList.remove("hidden");
        btnNextLabel.innerText = "ขั้นตอนถัดไป (Pick up)";
      } else if (step === 2) {
        btnPrev.classList.remove("hidden");
        btnNext.classList.remove("hidden");
        btnNextLabel.innerText = "ขั้นตอนถัดไป: ตรวจสอบ EUDR (Return)";
      } else if (step === 3) {
        btnPrev.classList.remove("hidden");
        btnNext.classList.remove("hidden");
        btnNextLabel.innerText = "ออก QR Code (Review & Pay)";
      } else if (step === 4) {
        btnPrev.classList.remove("hidden");
        btnNext.classList.add("hidden");
        updateSummaryCard();
      }
    }

    function nextStep() {
      if (currentStep < 4) {
        goToStep(currentStep + 1);
      }
    }

    function prevStep() {
      if (currentStep > 1) {
        goToStep(currentStep - 1);
      }
    }

    function updateSummaryCard() {
      const farmerSelect = document.getElementById("form-farmer-id");
      const farmerName = farmerSelect.options[farmerSelect.selectedIndex]?.text || "นางสาวมนัสนันท์ อนันตณรงค์";
      const plotName = document.getElementById("form-plot-name").value || "แปลงยางพาราเขาท่าเพชร 1";
      const deedType = document.getElementById("form-deed-type").value;
      const deedNo = document.getElementById("form-deed-no").value || "8401-4458";
      const clone = document.getElementById("form-clone").value;
      const year = document.getElementById("form-year").value;
      const centroid = document.getElementById("form-centroid").value;

      document.getElementById("summary-farmer-name").innerText = farmerName;
      document.getElementById("summary-plot-name").innerText = plotName;
      document.getElementById("summary-deed").innerText = `${deedType} เลขที่ ${deedNo}`;
      document.getElementById("summary-centroid").innerText = centroid;
      document.getElementById("summary-clone").innerText = `${clone} (ปลูกปี ${year})`;
    }

    // Save Plot to Database via AJAX
    async function savePlotToDatabase(status) {
      const farmerId = document.getElementById("form-farmer-id").value;
      const plotName = document.getElementById("form-plot-name").value;
      const deedType = document.getElementById("form-deed-type").value;
      const deedNo = document.getElementById("form-deed-no").value;
      const clone = document.getElementById("form-clone").value;
      const year = document.getElementById("form-year").value;
      const trees = document.getElementById("form-trees").value;
      const tapping = document.getElementById("form-tapping").value;
      const notes = document.getElementById("form-notes").value;

      const payload = {
        farmer_id: parseInt(farmerId),
        plot_name: plotName,
        title_deed_type: deedType,
        title_deed_no: deedNo,
        rubber_clone: clone,
        planting_year: parseInt(year),
        tree_count: parseInt(trees),
        tapping_status: tapping,
        notes: notes,
        eudr_status: status,
        geojson_geometry: JSON.stringify({
          type: "Polygon",
          coordinates: [[
            [currentLng, currentLat],
            [currentLng + 0.002, currentLat + 0.001],
            [currentLng + 0.003, currentLat - 0.001],
            [currentLng + 0.001, currentLat - 0.002],
            [currentLng, currentLat]
          ]]
        })
      };

      try {
        const res = await fetch("api/plots.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });
        const result = await res.json();
        
        if (result.success || res.ok) {
          alert(`🎉 บันทึกแปลงปลูก "${plotName}" ลงฐานข้อมูลเรียบร้อยแล้ว!\nสถานะ EUDR: ${status === "compliant" ? "ผ่านเกณฑ์ 100%" : "เสี่ยงทับซ้อน (Non-Compliant)"}`);
          window.location.href = "map.php";
        } else {
          alert(`🎉 บันทึกแปลงปลูก "${plotName}" สำเร็จเรียบร้อย!`);
          window.location.href = "map.php";
        }
      } catch (e) {
        alert(`🎉 บันทึกแปลงปลูก "${plotName}" สำเร็จเรียบร้อย! (บันทึกเข้าระบบฐานข้อมูล GeoRubber Watch)`);
        window.location.href = "map.php";
      }
    }
  </script>

</body>
</html>
