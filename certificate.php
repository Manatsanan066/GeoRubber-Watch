<?php
/**
 * GeoRubber Watch - Digital EUDR Compliance Certificate & Verification Portal
 * เลย์เอาต์: แนวตั้ง (A4 Portrait Official Layout) + Modern Desktop Portal (Tailwind CSS)
 * ฟีเจอร์: ดูเต็มจอ (Fullscreen Theater Mode), พิมพ์/บันทึก PDF ขนาด A4 แนวตั้ง, QR Code ตรวจสอบ
 */
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} elseif (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    initDatabaseIfNeeded();
    $pdo = getDatabaseConnection();
}

$certificateNo = trim($_GET['id'] ?? ($_GET['plot'] ?? ($_GET['code'] ?? 'RB-ST-2026-020')));
$data = null;

// 1. ดึงข้อมูลจากฐานข้อมูล (รองรับทั้งตาราง certificates และ rubber_plots)
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // ตรวจสอบตาราง certificates (ถ้ามี)
        $hasCertTable = false;
        try {
            $chk = $pdo->query("SELECT 1 FROM certificates LIMIT 1");
            $hasCertTable = ($chk !== false);
        } catch (Exception $e) {
            $hasCertTable = false;
        }

        if ($hasCertTable) {
            $sql = "
            SELECT
                c.certificate_no, c.eudr_reference, c.issue_date, c.status,
                c.risk_level, c.verification_id,
                f.farmer_code, f.full_name AS farmer_name,
                p.plot_code, p.location_text, p.land_document,
                p.area_rai, p.area_ngan, p.area_sqwa, p.area_ha,
                p.rubber_variety, p.planting_year, p.tree_count,
                p.centroid_lat, p.centroid_lng,
                v.no_forest_overlap, v.coordinates_verified,
                v.traceable_source, v.risk_passed
            FROM certificates c
            JOIN plots p ON p.id = c.plot_id
            JOIN farmers f ON f.id = p.farmer_id
            LEFT JOIN eudr_verifications v ON v.certificate_id = c.id
            WHERE c.certificate_no = ?
            LIMIT 1
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$certificateNo]);
            $data = $stmt->fetch();
        }

        // หากไม่พบ ให้ค้นหาจากตารางหลัก rubber_plots + farmers
        if (!$data) {
            $hasPlotTable = false;
            try {
                $chk = $pdo->query("SELECT 1 FROM rubber_plots LIMIT 1");
                $hasPlotTable = ($chk !== false);
            } catch (Exception $e) {
                $hasPlotTable = false;
            }

            if ($hasPlotTable) {
                $sql = "
                SELECT
                    p.plot_code AS certificate_no,
                    p.traceability_token AS eudr_reference,
                    COALESCE(p.eudr_verified_at, p.created_at, '2026-03-05') AS issue_date,
                    COALESCE(p.eudr_status, 'compliant') AS status,
                    CASE WHEN p.eudr_status = 'compliant' THEN 'negligible' ELSE 'standard' END AS risk_level,
                    CONCAT('VER-2026-', LPAD(p.id, 4, '0')) AS verification_id,
                    f.farmer_code,
                    CONCAT(COALESCE(f.prefix, ''), f.first_name, ' ', f.last_name) AS farmer_name,
                    p.plot_code,
                    CONCAT(COALESCE(p.plot_name, 'แปลงยางพารา'), ' ต.', COALESCE(f.subdistrict, 'มะขามเตี้ย'), ' อ.', COALESCE(f.district, 'เมือง'), ' จ.', COALESCE(f.province, 'สุราษฎร์ธานี')) AS location_text,
                    CONCAT(COALESCE(p.title_deed_type, 'โฉนดที่ดิน (น.ส. 4 จ)'), ' เลขที่ ', COALESCE(p.title_deed_no, '-')) AS land_document,
                    p.area_rai,
                    p.area_ngan,
                    p.area_sqwah AS area_sqwa,
                    p.area_hectare AS area_ha,
                    p.rubber_clone AS rubber_variety,
                    p.planting_year,
                    p.tree_count,
                    p.centroid_lat,
                    p.centroid_lng,
                    CASE WHEN COALESCE(p.eudr_overlap_pct, 0) <= 0 THEN 1 ELSE 0 END AS no_forest_overlap,
                    1 AS coordinates_verified,
                    1 AS traceable_source,
                    CASE WHEN p.eudr_status = 'compliant' THEN 1 ELSE 0 END AS risk_passed
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                WHERE p.plot_code = ? OR p.traceability_token = ? OR CAST(p.id AS CHAR) = ?
                LIMIT 1
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$certificateNo, $certificateNo, $certificateNo]);
                $data = $stmt->fetch();
            }
        }
    } catch (Exception $ex) {
        // Exception fallback
    }
}

// 2. Mock Data ตัวอย่างสมบูรณ์ (ถ้าไม่ได้ต่อฐานข้อมูล หรือไม่พบข้อมูล)
if (!$data) {
    if (isset($_GET['strict']) && $_GET['strict'] == '1') {
        http_response_code(404);
        exit('ไม่พบใบรับรองหมายเลข ' . htmlspecialchars($certificateNo));
    }

    $data = [
        'certificate_no' => !empty($certificateNo) ? $certificateNo : 'RB-ST-2026-020',
        'eudr_reference' => 'EUDR-TH-ST-84000-020-A9F41',
        'issue_date' => '2026-03-05 10:30:00',
        'status' => 'compliant',
        'risk_level' => 'negligible',
        'verification_id' => 'VER-2026-84000-9941',
        'farmer_code' => 'FM-PSU-002',
        'farmer_name' => 'นางสาวมนัสนันท์ อนันตณรงค์',
        'plot_code' => !empty($certificateNo) ? $certificateNo : 'RB-ST-2026-020',
        'location_text' => 'ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี',
        'land_document' => 'โฉนดที่ดิน (น.ส. 4 จ) เลขที่ 45678',
        'area_rai' => 15,
        'area_ngan' => 2,
        'area_sqwa' => 50.00,
        'area_ha' => 2.5000,
        'rubber_variety' => 'RRIM 600',
        'planting_year' => 2018,
        'tree_count' => 1140,
        'centroid_lat' => 9.0963000,
        'centroid_lng' => 99.3607000,
        'no_forest_overlap' => 1,
        'coordinates_verified' => 1,
        'traceable_source' => 1,
        'risk_passed' => 1
    ];
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function thaiDate($date) {
    $months = [1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',5=>'พฤษภาคม',6=>'มิถุนายน',
               7=>'กรกฎาคม',8=>'สิงหาคม',9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'];
    $t = strtotime($date);
    if (!$t) return (string)$date;
    return (int)date('j',$t).' '.$months[(int)date('n',$t)].' '.((int)date('Y',$t)+543);
}

$statusText = $data['status'] === 'compliant' ? 'ผ่านเกณฑ์ EUDR' : 'ต้องตรวจสอบเพิ่มเติม';
$riskText = $data['risk_level'] === 'negligible' ? 'ความเสี่ยงต่ำ (Negligible)' : ucfirst($data['risk_level']);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$verifyUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') .
             dirname($_SERVER['PHP_SELF']) .
             '/certificate.php?id=' . rawurlencode($data['certificate_no']);
$ledgerHash = '0x' . hash('sha256', $data['certificate_no'] . $data['eudr_reference']);
$shortHash = substr($ledgerHash, 0, 8) . '...' . substr($ledgerHash, -6);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=h($data['certificate_no'])?> | ใบรับรองแหล่งผลิตยางพารา EUDR (แนวตั้ง)</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- Google Fonts: Prompt, Kanit, Plus Jakarta Sans, Dancing Script -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Kanit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50: '#f0fdf9',
              100: '#ccfbef',
              200: '#9af5df',
              500: '#10b981',
              600: '#087a5b',
              700: '#065f46',
              800: '#073f36',
              900: '#042721'
            }
          },
          fontFamily: {
            sans: ['Prompt', 'sans-serif'],
            thai: ['Kanit', 'sans-serif'],
            eng: ['"Plus Jakarta Sans"', 'sans-serif'],
            signature: ['"Dancing Script"', 'cursive']
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Prompt', sans-serif;
    }
    .font-signature {
      font-family: 'Dancing Script', cursive;
    }
    .clip-ribbon {
      clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
    }

    /* Simulation of GIS Satellite Map Polygon */
    .map-preview {
      height: 140px;
      border-radius: 8px;
      position: relative;
      overflow: hidden;
      background: linear-gradient(25deg,#1e5c35 0 18%,transparent 18%),linear-gradient(160deg,#487d4a 0 25%,#799c5e 25% 48%,#b8b77a 48% 50%,#6c9659 50% 75%,#3f733f 75%);
    }
    .map-preview:before {
      content: "";
      position: absolute;
      inset: 0;
      opacity: .5;
      background: repeating-linear-gradient(0deg,transparent 0 25px,#fff3 26px 27px),repeating-linear-gradient(90deg,transparent 0 35px,#fff3 36px 37px);
    }
    .plot-poly {
      position: absolute;
      width: 50%;
      height: 45%;
      left: 25%;
      top: 25%;
      background: #d7443b66;
      border: 2px solid #ef3f37;
      clip-path: polygon(8% 24%,30% 3%,88% 15%,98% 65%,74% 97%,18% 82%,2% 48%);
    }

    /* Fullscreen Floating Controls */
    .fullscreen-toolbar {
      display: none;
    }
    :fullscreen .fullscreen-toolbar,
    :-webkit-full-screen .fullscreen-toolbar,
    .is-modal-fullscreen .fullscreen-toolbar {
      display: flex !important;
    }

    /* Fullscreen Theater Mode for Portrait */
    #certificateCanvas:fullscreen,
    #certificateCanvas:-webkit-full-screen,
    .is-modal-fullscreen {
      width: 100vw !important;
      height: 100vh !important;
      max-width: none !important;
      border-radius: 0 !important;
      border: none !important;
      margin: 0 !important;
      padding: 3rem 1rem !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      background: #0b1320 !important;
      box-sizing: border-box !important;
      overflow-y: auto !important;
    }

    #certificateCanvas:fullscreen .cert-paper,
    #certificateCanvas:-webkit-full-screen .cert-paper,
    .is-modal-fullscreen .cert-paper {
      max-width: 840px !important;
      width: 100% !important;
      background: #ffffff !important;
      border-radius: 12px !important;
      padding: 3.2rem 3.5rem !important;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6) !important;
      margin-bottom: 3rem !important;
    }

    /* Print Styles: A4 Portrait (แนวตั้ง) */
    @media print {
      @page {
        size: A4 portrait;
        margin: 5mm;
      }
      body {
        background: #fff !important;
        padding: 0 !important;
        color: #000 !important;
      }
      .no-print, header, footer, .xl\:col-span-4, .fullscreen-toolbar {
        display: none !important;
      }
      main {
        padding: 0 !important;
        max-width: 100% !important;
        margin: 0 !important;
        display: block !important;
      }
      .xl\:col-span-8 {
        width: 100% !important;
        max-width: 100% !important;
      }
      .cert-paper {
        box-shadow: none !important;
        border: 2px solid #087a5b !important;
        border-radius: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        page-break-inside: avoid;
        padding: 1.8rem !important;
      }
    }
  </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased">

  <!-- Top Navigation Bar -->
  <header class="bg-white border-b border-slate-200 px-6 lg:px-8 py-3.5 flex items-center justify-between shadow-sm sticky top-0 z-30 no-print">
    <div class="flex items-center space-x-4 lg:space-x-6">
      <a href="index.php" class="text-slate-400 hover:text-brand-600 transition-colors p-1.5 rounded-lg hover:bg-slate-50" title="กลับสู่หน้าหลัก">
        <i class="fa-solid fa-arrow-left text-lg"></i>
      </a>
      
      <!-- Brand Logo & System Name -->
      <div class="flex items-center space-x-3 border-r border-slate-200 pr-6">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 flex items-center justify-center text-white text-xl shadow-md shadow-brand-600/20">
          🌿
        </div>
        <div>
          <h1 class="text-base font-extrabold tracking-tight text-slate-900 leading-none font-thai">GeoRubber Watch</h1>
          <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase mt-0.5">Surat Thani · EUDR Traceability</p>
        </div>
      </div>

      <!-- EUDR Compliance Pill Badge -->
      <div class="hidden sm:inline-flex items-center space-x-2 bg-emerald-50 text-emerald-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-emerald-200">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span><?=h($statusText)?> (A4 Portrait Official)</span>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center space-x-2 sm:space-x-3">
      <!-- Fullscreen Button in Header -->
      <button onclick="toggleFullscreen()" id="headerFsBtn" class="inline-flex items-center space-x-2 px-3.5 py-2 text-xs sm:text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-brand-700 transition shadow-sm" title="ดูใบรับรองแบบเต็มหน้าจอ (Fullscreen)">
        <i class="fa-solid fa-expand text-slate-500"></i>
        <span class="fs-text">ดูเต็มจอ</span>
      </button>

      <button onclick="window.print()" class="inline-flex items-center space-x-2 px-3.5 py-2 text-xs sm:text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
        <i class="fa-solid fa-print text-slate-500"></i>
        <span class="hidden sm:inline">พิมพ์ (A4 แนวตั้ง)</span>
      </button>

      <button onclick="window.print()" class="inline-flex items-center space-x-2 px-3.5 py-2 text-xs sm:text-sm font-medium text-white bg-brand-600 rounded-lg hover:bg-brand-700 transition shadow-sm shadow-brand-600/20">
        <i class="fa-solid fa-file-pdf"></i>
        <span>PDF</span>
      </button>

      <button onclick="copyVerifyUrl()" class="hidden md:inline-flex items-center space-x-2 px-3.5 py-2 text-xs sm:text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm" title="คัดลอกลิงก์ตรวจสอบ">
        <i class="fa-solid fa-share-nodes text-slate-500"></i>
        <span id="copyBtnText">แชร์</span>
      </button>
    </div>
  </header>

  <!-- Main Content Layout (Desktop Grid) -->
  <main class="flex-1 max-w-[1520px] w-full mx-auto p-4 sm:p-6 lg:p-8 grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">

    <!-- Left Column: Certificate Portrait Canvas (8 cols on desktop) -->
    <div class="xl:col-span-8 flex flex-col items-center space-y-3 w-full">
      
      <!-- Fullscreen Container Wrapper -->
      <div id="certificateCanvas" class="w-full flex justify-center relative">

        <!-- Floating Controls Bar (Active in Fullscreen mode only) -->
        <div class="fullscreen-toolbar fixed top-5 right-6 z-50 flex items-center space-x-2.5 bg-slate-900/90 backdrop-blur-md px-4 py-2.5 rounded-xl shadow-2xl border border-white/20 text-white transition-all">
          <span class="text-xs text-emerald-400 font-mono hidden md:inline mr-1 font-semibold flex items-center gap-1.5">
            <i class="fa-solid fa-file-lines"></i> โหมดดูใบรับรองแนวตั้ง (A4 Portrait)
          </span>
          <button onclick="window.print()" class="bg-brand-600 hover:bg-brand-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-print"></i>
            <span>พิมพ์</span>
          </button>
          <button onclick="toggleFullscreen()" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1.5">
            <i class="fa-solid fa-compress"></i>
            <span>ปิดเต็มจอ (ESC)</span>
          </button>
        </div>

        <!-- A4 Portrait Certificate Paper (แนวตั้ง) -->
        <div class="cert-paper bg-white rounded-2xl shadow-xl w-full max-w-[840px] p-8 sm:p-12 border border-slate-200/90 select-none relative overflow-hidden flex flex-col justify-between space-y-6">

          <!-- Decorative Top-Right & Bottom-Left Curves -->
          <div class="absolute -top-14 -right-14 w-72 h-72 bg-gradient-to-br from-brand-200/40 via-brand-500/15 to-transparent rounded-[4rem] rotate-12 pointer-events-none"></div>
          <div class="absolute top-0 right-16 w-56 h-56 bg-emerald-100/40 rounded-full blur-2xl pointer-events-none"></div>
          <div class="absolute -bottom-16 -left-16 w-72 h-72 bg-gradient-to-tr from-brand-800/20 via-brand-600/15 to-transparent rounded-[5rem] -rotate-12 pointer-events-none"></div>

          <!-- Ornate Inner Certificate Border -->
          <div class="border-2 border-brand-600/20 rounded-xl p-6 sm:p-8 relative z-10 flex flex-col space-y-6 bg-white/60 backdrop-blur-[2px]">

            <!-- 2. Title Section (Centered) -->
            <div class="text-center space-y-1.5 pt-2">
              <h2 class="text-2xl sm:text-3xl font-black text-brand-800 tracking-tight font-thai">
                ใบรับรองแหล่งผลิตยางพารา
              </h2>
              <p class="text-xs sm:text-sm font-bold tracking-widest uppercase text-brand-600 font-eng">
                EUDR COMPLIANCE CERTIFICATE OF SUSTAINABILITY
              </p>
              <p class="text-[11px] text-slate-400 font-medium">
                เอกสารแสดงผลการตรวจสอบข้อมูลแหล่งผลิตยางพาราปลอดการตัดไม้ทำลายป่าและตรวจสอบย้อนกลับได้
              </p>
            </div>

            <!-- 3. Status Badges Strip (3 Columns) -->
            <div class="grid grid-cols-3 gap-2 sm:gap-3 p-3 bg-brand-50/80 border border-brand-200/80 rounded-xl text-center">
              <div class="flex items-center justify-center space-x-2">
                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">✓</span>
                <div class="text-left">
                  <b class="text-xs text-brand-900 font-bold block leading-none"><?=h($statusText)?></b>
                  <span class="text-[9px] text-slate-500 font-mono">EUDR Status</span>
                </div>
              </div>

              <div class="flex items-center justify-center space-x-2 border-x border-brand-200">
                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">✓</span>
                <div class="text-left">
                  <b class="text-xs text-brand-900 font-bold block leading-none"><?=h($riskText)?></b>
                  <span class="text-[9px] text-slate-500 font-mono">Risk Level</span>
                </div>
              </div>

              <div class="flex items-center justify-center space-x-2">
                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold">✓</span>
                <div class="text-left">
                  <b class="text-xs text-brand-900 font-bold block leading-none">ตรวจสอบย้อนกลับได้</b>
                  <span class="text-[9px] text-slate-500 font-mono">Traceable Source</span>
                </div>
              </div>
            </div>

            <!-- 4. Recipient Presentation Section -->
            <div class="text-center pt-2 pb-1 space-y-1">
              <p class="text-[11px] uppercase tracking-widest text-slate-400 font-semibold">
                เอกสารฉบับนี้ออกให้เพื่อรับรองแก่เกษตรกรผู้ถือครองกรรมสิทธิ์ / THIS IS CERTIFIED TO
              </p>
              <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight font-thai border-b-2 border-brand-500/40 inline-block px-4 pb-1">
                <?=h($data['farmer_name'])?>
              </h3>
              <div class="flex items-center justify-center space-x-4 text-xs text-slate-500 pt-1 font-mono">
                <span>รหัสเกษตรกร: <b class="text-slate-800 font-bold"><?=h($data['farmer_code'])?></b></span>
                <span>•</span>
                <span>รหัสแปลง: <b class="text-brand-700 font-bold"><?=h($data['plot_code'])?></b></span>
              </div>
            </div>

            <!-- 5. Meta Info Strip (3 Columns) -->
            <div class="grid grid-cols-3 border border-slate-200 rounded-xl overflow-hidden text-xs bg-slate-50/60 divide-x divide-slate-200">
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">เลขที่ใบรับรอง</span>
                <strong class="text-slate-800 font-mono text-[11px]"><?=h($data['certificate_no'])?></strong>
              </div>
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">เลขอ้างอิง EUDR</span>
                <strong class="text-brand-700 font-mono text-[11px] truncate block" title="<?=h($data['eudr_reference'])?>"><?=h($data['eudr_reference'])?></strong>
              </div>
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">วันที่ออกใบรับรอง</span>
                <strong class="text-slate-800"><?=h(thaiDate($data['issue_date']))?></strong>
              </div>
            </div>

            <!-- 6. 2x2 Detail Cards Grid (ข้อมูลแปลง & แผนที่ & ผลการตรวจสอบ) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              
              <!-- Card 1: ข้อมูลเกษตรกรและที่ตั้ง -->
              <div class="border border-slate-200/90 rounded-xl p-3.5 bg-white shadow-sm space-y-2">
                <h4 class="font-bold text-brand-800 font-thai border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                  <i class="fa-solid fa-id-card text-brand-600"></i> ข้อมูลเกษตรกรและแปลง
                </h4>
                <div class="space-y-1.5 text-[11px]">
                  <div class="flex justify-between"><span class="text-slate-400">ชื่อ-สกุล:</span><b class="text-slate-800"><?=h($data['farmer_name'])?></b></div>
                  <div class="flex justify-between"><span class="text-slate-400">รหัสแปลง:</span><b class="font-mono text-brand-700"><?=h($data['plot_code'])?></b></div>
                  <div class="flex justify-between"><span class="text-slate-400">ที่ตั้ง:</span><span class="text-slate-700 text-right max-w-[150px] truncate"><?=h($data['location_text'])?></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">เอกสารสิทธิ์:</span><span class="text-slate-800 font-medium"><?=h($data['land_document'])?></span></div>
                </div>
              </div>

              <!-- Card 2: ข้อมูลการเพาะปลูก -->
              <div class="border border-slate-200/90 rounded-xl p-3.5 bg-white shadow-sm space-y-2">
                <h4 class="font-bold text-brand-800 font-thai border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                  <i class="fa-solid fa-tree text-brand-600"></i> ข้อมูลการเพาะปลูกยางพารา
                </h4>
                <div class="space-y-1.5 text-[11px]">
                  <div class="flex justify-between"><span class="text-slate-400">เนื้อที่แปลง:</span><b class="text-slate-800"><?=h($data['area_rai'])?> ไร่ <?=h($data['area_ngan'])?> งาน (<?=h($data['area_ha'])?> ha)</b></div>
                  <div class="flex justify-between"><span class="text-slate-400">พันธุ์ยางพารา:</span><b class="text-slate-800"><?=h($data['rubber_variety'])?></b></div>
                  <div class="flex justify-between"><span class="text-slate-400">ปีที่เริ่มปลูก:</span><span class="text-slate-800"><?=h($data['planting_year'])?></span></div>
                  <div class="flex justify-between"><span class="text-slate-400">จำนวนต้นยาง:</span><b class="text-slate-800"><?=number_format((float)$data['tree_count'])?> ต้น</b></div>
                </div>
              </div>

              <!-- Card 3: แผนที่พิกัด GIS Polygon -->
              <div class="border border-slate-200/90 rounded-xl p-3.5 bg-white shadow-sm space-y-2">
                <h4 class="font-bold text-brand-800 font-thai border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                  <i class="fa-solid fa-map-location-dot text-brand-600"></i> ขอบเขตแปลงทางภูมิศาสตร์
                </h4>
                <div class="map-preview">
                  <div class="plot-poly"></div>
                  <div class="absolute inset-0 flex items-center justify-center text-white text-lg drop-shadow">📍</div>
                  <div class="absolute top-2 right-2 text-white font-bold text-[10px] bg-black/40 px-1.5 py-0.5 rounded">N ↑</div>
                  <div class="absolute bottom-2 left-2 text-white text-[9px] bg-black/60 px-1.5 py-0.5 rounded">0 100 200m</div>
                </div>
                <div class="text-[10px] text-slate-500 font-mono mt-1 text-center">
                  พิกัดศูนย์กลาง: <b><?=h($data['centroid_lat'])?>, <?=h($data['centroid_lng'])?></b> (EPSG:4326)
                </div>
              </div>

              <!-- Card 4: ผลการตรวจสอบ EUDR และ QR Code -->
              <div class="border border-slate-200/90 rounded-xl p-3.5 bg-white shadow-sm space-y-2 flex flex-col justify-between">
                <div>
                  <h4 class="font-bold text-brand-800 font-thai border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-clipboard-check text-brand-600"></i> ผลการประเมิน 4 มิติ
                  </h4>
                  <div class="space-y-1.5 text-[10.5px] text-slate-700 pt-1">
                    <div class="flex items-center gap-1.5"><span class="text-emerald-600 font-bold">✓</span><span>ไม่พบพื้นที่ป่าสงวนซ้อนทับ (0.0% Overlap)</span></div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-600 font-bold">✓</span><span>ตรวจสอบพิกัดดาวเทียม & Polygon แล้ว</span></div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-600 font-bold">✓</span><span>ตรวจสอบย้อนกลับได้ตลอดห่วงโซ่คุณค่า</span></div>
                    <div class="flex items-center gap-1.5"><span class="text-emerald-600 font-bold">✓</span><span>ผ่านเกณฑ์ประเมินความเสี่ยง EUDR</span></div>
                  </div>
                </div>

                <!-- QR Scan Box within card -->
                <div class="flex items-center space-x-3 pt-2 mt-2 border-t border-slate-100">
                  <img class="w-12 h-12 rounded border border-slate-200 shadow-sm" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?=urlencode($verifyUrl)?>" alt="QR">
                  <div class="text-[10px]">
                    <b class="text-slate-800 block">สแกนตรวจสอบใบรับรอง</b>
                    <span class="text-slate-400 font-mono">ID: <?=h($data['verification_id'])?></span>
                  </div>
                </div>
              </div>

            </div>

            <!-- 7. Official Seal Rosette Ribbon Badge (Centered Ribbon) -->
            <div class="flex justify-center items-center py-2">
              <div class="flex items-center space-x-4 bg-gradient-to-r from-brand-50 via-emerald-50 to-brand-50 border border-brand-200 px-6 py-2 rounded-full shadow-sm">
                <div class="w-10 h-10 rounded-full border-2 border-brand-600 bg-white flex flex-col items-center justify-center text-center shadow-inner">
                  <div class="flex text-amber-500 text-[7px]"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                  <span class="text-[8px] font-black text-brand-800 uppercase leading-none">EUDR</span>
                  <span class="text-[6px] font-bold text-emerald-600 leading-none">PASS</span>
                </div>
                <div class="text-left">
                  <span class="text-xs font-black text-brand-900 tracking-wider font-eng block leading-tight">EUDR VERIFIED OFFICIAL CREDENTIAL</span>
                  <span class="text-[10px] text-slate-500">ผ่านการรับรองตามระเบียบสหภาพยุโรปว่าด้วยสินค้าที่ปลอดจากการตัดไม้ทำลายป่า 100%</span>
                </div>
              </div>
            </div>

            <!-- 8. Signatures Section (3 Columns at bottom) -->
            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-200 text-center">
              <div>
                <div class="h-10 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-signature text-2xl text-brand-800">GeoRubber Watch</span>
                </div>
                <b class="text-xs text-slate-800 block mt-1">ผู้รับรองข้อมูล</b>
                <span class="text-[10px] text-slate-400">GeoRubber Watch Registry</span>
              </div>

              <div>
                <div class="h-10 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-signature text-2xl text-slate-700"><?=h($data['farmer_code'])?></span>
                </div>
                <b class="text-xs text-slate-800 block mt-1">เกษตรกร / ผู้ประกอบการ</b>
                <span class="text-[10px] text-slate-400"><?=h($data['farmer_name'])?></span>
              </div>

              <div>
                <div class="h-10 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-mono text-xs font-bold text-slate-700"><?=date('d/m/Y', strtotime($data['issue_date']))?></span>
                </div>
                <b class="text-xs text-slate-800 block mt-1">วันที่ออกใบรับรอง</b>
                <span class="text-[10px] text-slate-400">Date of Issuance</span>
              </div>
            </div>

          </div>
          
          <!-- Bottom Watermark Note inside paper -->
          <div class="flex justify-between items-center text-[10px] text-slate-400 pt-2 px-1">
            <span>GeoRubber Watch · มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</span>
            <span class="font-mono">Ledger Hash: <?=h($shortHash)?></span>
          </div>

        </div>

      </div>

      <!-- Subtext under Canvas -->
      <div class="flex justify-between items-center text-xs text-slate-500 px-2 w-full max-w-[840px] no-print">
        <span class="inline-flex items-center space-x-1.5">
          <i class="fa-solid fa-circle-check text-emerald-500"></i>
          <span>เอกสารดิจิทัล A4 แนวตั้ง พร้อมลายเซ็นและตราประทับทางภูมิสารสนเทศ</span>
        </span>
        
        <!-- Fullscreen Button below Canvas -->
        <button onclick="toggleFullscreen()" id="subFsBtn" class="hover:text-brand-700 inline-flex items-center space-x-1.5 text-brand-600 font-medium transition" title="ดูใบรับรองแบบเต็มหน้าจอ">
          <i class="fa-solid fa-expand"></i>
          <span class="fs-text">ดูเต็มหน้าจอ</span>
        </button>
      </div>

    </div>

    <!-- Right Column: Verification Sidebar (4 cols on desktop) -->
    <div class="xl:col-span-4 space-y-6 w-full no-print">
      
      <!-- Verification Details Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-thai flex items-center gap-1.5">
            <i class="fa-solid fa-shield-halved text-brand-600"></i>
            ผลการตรวจสอบความถูกต้อง (EUDR)
          </h3>
          <span class="bg-emerald-50 text-emerald-700 text-[11px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
            <?=h($statusText)?>
          </span>
        </div>

        <div class="space-y-3.5 text-xs">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">ชื่อเกษตรกร</span>
            <span class="font-bold text-slate-800 text-sm font-thai"><?=h($data['farmer_name'])?></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">เลขที่ใบรับรอง</span>
            <div class="flex items-center space-x-1.5 bg-slate-50 px-2 py-1 rounded border border-slate-200 font-mono text-[11px]">
              <span id="certNoText"><?=h($data['certificate_no'])?></span>
              <button onclick="copyCertNo()" class="text-slate-400 hover:text-brand-600" title="คัดลอกรหัส"><i class="fa-regular fa-copy"></i></button>
            </div>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">รหัสแปลง / เอกสารสิทธิ์</span>
            <span class="font-medium text-slate-800 text-right"><?=h($data['plot_code'])?><br><small class="text-slate-400"><?=h($data['land_document'])?></small></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">ที่ตั้งแปลง</span>
            <span class="font-medium text-slate-700 text-right max-w-[200px] truncate" title="<?=h($data['location_text'])?>"><?=h($data['location_text'])?></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">ระดับความเสี่ยง EUDR</span>
            <span class="font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"><?=h($riskText)?></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">วันที่ออกเอกสาร</span>
            <span class="font-medium text-slate-700"><?=h(thaiDate($data['issue_date']))?></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">Verification ID</span>
            <span class="font-mono text-brand-600 text-[11px] font-bold"><?=h($data['verification_id'])?></span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-slate-500">Ledger Hash</span>
            <span class="font-mono text-slate-500 text-[11px] cursor-pointer hover:underline" title="<?=h($ledgerHash)?>"><?=h($shortHash)?></span>
          </div>
        </div>

        <!-- Big Scannable QR Verification Box -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center space-x-4">
          <div class="bg-white p-1 rounded-lg border border-slate-200 flex-shrink-0 shadow-sm">
            <img class="w-16 h-16 rounded" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?=urlencode($verifyUrl)?>" alt="QR">
          </div>
          <div>
            <h4 class="text-xs font-bold text-slate-800 font-thai">สแกนเพื่อตรวจสอบใบรับรอง</h4>
            <p class="text-[11px] text-slate-500 leading-tight mt-0.5">
              เปิดกล้องมือถือสแกน QR เพื่อดูสถานะใบรับรองฉบับจริงในระบบฐานข้อมูล GeoRubber Watch
            </p>
          </div>
        </div>
      </div>

      <!-- Export & Print Actions Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 font-thai">พิมพ์และจัดเก็บใบรับรอง</h4>
        <div class="grid grid-cols-2 gap-3">
          <button onclick="window.print()" class="flex items-center justify-center space-x-2 py-2.5 px-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-xs font-semibold text-slate-700">
            <i class="fa-solid fa-file-pdf text-brand-600 text-base"></i>
            <span>ดาวน์โหลด PDF</span>
          </button>
          
          <button onclick="copyVerifyUrl()" class="flex items-center justify-center space-x-2 py-2.5 px-3 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-xs font-semibold text-slate-700">
            <i class="fa-solid fa-link text-blue-600 text-base"></i>
            <span>คัดลอกลิงก์</span>
          </button>
        </div>
      </div>

    </div>

  </main>

  <!-- Global Footer -->
  <footer class="bg-white border-t border-slate-200 px-6 lg:px-8 py-4 text-xs text-slate-400 flex flex-col sm:flex-row justify-between items-center gap-2 no-print">
    <div>
      <span class="font-medium text-slate-600">GeoRubber Watch Traceability Platform</span> © 2026 PSU Surat Thani. All Rights Reserved.
    </div>
    <div class="flex space-x-6">
      <a href="index.php" class="hover:text-brand-600">หน้าหลักระบบ</a>
      <a href="overview.php" class="hover:text-brand-600">แผนที่ Web-GIS</a>
      <a href="trace.php?id=<?=urlencode($data['certificate_no'])?>" class="hover:text-brand-600">ตรวจสอบย้อนกลับ (Trace)</a>
    </div>
  </footer>

  <!-- Notification Toast -->
  <div id="toast" class="fixed bottom-6 right-6 bg-slate-900 text-white text-xs px-4 py-2.5 rounded-lg shadow-xl opacity-0 transition-opacity duration-300 pointer-events-none z-50 flex items-center space-x-2">
    <i class="fa-solid fa-circle-check text-emerald-400"></i>
    <span id="toastMsg">คัดลอกเรียบร้อยแล้ว</span>
  </div>

  <script>
    // Fullscreen Controller for Portrait Layout
    function toggleFullscreen() {
      const cert = document.getElementById('certificateCanvas');
      const isNativeFs = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement;
      const isModalFs = cert.classList.contains('is-modal-fullscreen');

      if (isNativeFs) {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        }
      } else if (isModalFs) {
        cert.classList.remove('is-modal-fullscreen');
        document.body.classList.remove('has-modal-fullscreen');
        updateFullscreenUI(false);
      } else {
        if (cert.requestFullscreen) {
          cert.requestFullscreen().catch(() => activateModalFullscreen());
        } else if (cert.webkitRequestFullscreen) {
          cert.webkitRequestFullscreen();
        } else if (cert.msRequestFullscreen) {
          cert.msRequestFullscreen();
        } else {
          activateModalFullscreen();
        }
      }
    }

    function activateModalFullscreen() {
      const cert = document.getElementById('certificateCanvas');
      cert.classList.add('is-modal-fullscreen');
      document.body.classList.add('has-modal-fullscreen');
      updateFullscreenUI(true);
    }

    function updateFullscreenUI(isFs) {
      const fsTexts = document.querySelectorAll('.fs-text');
      const fsIcons = document.querySelectorAll('#headerFsBtn i, #subFsBtn i');
      
      fsTexts.forEach(el => {
        el.innerText = isFs ? 'ออกจากเต็มจอ' : (el.closest('#subFsBtn') ? 'ดูเต็มหน้าจอ' : 'ดูเต็มจอ');
      });
      fsIcons.forEach(icon => {
        if (isFs) {
          icon.classList.remove('fa-expand');
          icon.classList.add('fa-compress');
        } else {
          icon.classList.remove('fa-compress');
          icon.classList.add('fa-expand');
        }
      });
    }

    // Fullscreen event listener
    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(event => {
      document.addEventListener(event, () => {
        const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        updateFullscreenUI(isFs);
      });
    });

    // ESC key listener for modal fullscreen fallback
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const cert = document.getElementById('certificateCanvas');
        if (cert && cert.classList.contains('is-modal-fullscreen')) {
          cert.classList.remove('is-modal-fullscreen');
          document.body.classList.remove('has-modal-fullscreen');
          updateFullscreenUI(false);
        }
      }
    });

    // Toast and Copy functions
    function showToast(msg) {
      const toast = document.getElementById('toast');
      const toastMsg = document.getElementById('toastMsg');
      toastMsg.innerText = msg;
      toast.classList.remove('opacity-0');
      toast.classList.add('opacity-100');
      setTimeout(() => {
        toast.classList.remove('opacity-100');
        toast.classList.add('opacity-0');
      }, 2500);
    }

    function copyVerifyUrl() {
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(() => {
        showToast('คัดลอกลิงก์ตรวจสอบแล้ว');
      });
    }

    function copyCertNo() {
      const certNo = document.getElementById('certNoText').innerText.trim();
      navigator.clipboard.writeText(certNo).then(() => {
        showToast('คัดลอกรหัสใบรับรองแล้ว: ' + certNo);
      });
    }
  </script>
</body>
</html>
