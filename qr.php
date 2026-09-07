<?php
/**
 * GeoRubber Watch / Credential Verification Portal - Certificate of Achievement
 * Responsive Certificate Verification & EUDR Traceability Proof
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection (Optional if token/id/code is provided)
$plot = null;
$token = trim($_GET['token'] ?? '');
$plot_code = trim($_GET['code'] ?? '');
$plot_id = intval($_GET['id'] ?? ($_GET['plot_id'] ?? 0));

if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/config/database.php';
        initDatabaseIfNeeded();
        $pdo = getDatabaseConnection();

        if (!empty($token) || !empty($plot_code) || $plot_id > 0) {
            $where = [];
            $params = [];
            if (!empty($token)) {
                $where[] = "(p.traceability_token = ? OR p.plot_code = ?)";
                $params[] = $token;
                $params[] = $token;
            }
            if (!empty($plot_code)) {
                $where[] = "p.plot_code = ?";
                $params[] = $plot_code;
            }
            if ($plot_id > 0) {
                $where[] = "p.id = ?";
                $params[] = $plot_id;
            }
            
            $whereClause = implode(' OR ', $where);
            $sql = "
                SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
                       f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                WHERE {$whereClause}
                LIMIT 1
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $plot = $stmt->fetch();
        }
    } catch (Exception $e) {
        // Fallback gracefully without DB error breaking page
    }
}

// Data Resolution with graceful defaults matching user template
$recipientName = 'Alex Morgan';
$companyName = 'VERISECURE';
$issuerName = 'Global Institute of Excellence';
$credentialId = 'ACH-2025-88419X';
$issueDate = 'January 2, 2025';
$issueDateUpper = 'JANUARY 2ND 2025';
$signatoryName = 'J.ly Morgan';
$ledgerHash = '0x7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069';
$shortLedgerHash = '0x7f83...6d9069';
$citationTitle = 'EUDR Zero Deforestation Compliance & Traceability Verified';
$citationBody = 'This official credential certifies that the verified agricultural rubber plot and associated harvesting batches strictly adhere to EU Regulation (EU) 2023/1115 (EUDR) zero-deforestation mandates, lawful land tenure rights, and verifiable geolocation standards with full cryptographic chain of custody.';

if ($plot) {
    $farmerPrefix = $plot['prefix'] ?? '';
    $farmerFirst = $plot['first_name'] ?? '';
    $farmerLast = $plot['last_name'] ?? '';
    $fullName = trim("{$farmerPrefix} {$farmerFirst} {$farmerLast}");
    if (!empty($fullName)) {
        $recipientName = $fullName;
    }
    if (!empty($plot['traceability_token'])) {
        $credentialId = $plot['traceability_token'];
        $ledgerHash = '0x' . hash('sha256', $plot['traceability_token'] . ($plot['plot_code'] ?? ''));
        $shortLedgerHash = substr($ledgerHash, 0, 6) . '...' . substr($ledgerHash, -6);
    }
    if (!empty($plot['created_at'])) {
        $ts = strtotime($plot['created_at']);
        $issueDate = date('F j, Y', $ts);
        $issueDateUpper = strtoupper(date('F jS Y', $ts));
    }
    $companyName = 'GEORUBBER WATCH';
    $issuerName = 'PSU Surat Thani & EUDR Registry';
    $signatoryName = 'Assoc. Prof. Dr. Surat';
}

// Active Ngrok / Public Verification URL
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$ngrokBase = 'https://earthling-retype-aroma.ngrok-free.dev';
$publicVerifyUrl = $ngrokBase . '/RB/qr.php' . (!empty($credentialId) ? '?token=' . urlencode($credentialId) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Credential Verification Portal - Certificate of Achievement</title>

  <!-- Tailwind CSS v3 with Plugins -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <!-- Google Fonts: Inter, Montserrat, & Dancing Script for authentic handwritten signature -->
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@600;700&family=Montserrat:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>

  <!-- QRCode.js for dynamic QR rendering -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            display: ['Montserrat', 'sans-serif'],
            signature: ['"Dancing Script"', 'cursive'],
          },
          colors: {
            brand: {
              50: '#eef6ff',
              100: '#d9eaff',
              200: '#bcdbff',
              400: '#5297ff',
              500: '#2b7fff',
              600: '#1d63ed',
              700: '#184ec7',
              800: '#19419f',
              900: '#1b3a7d',
            }
          }
        }
      }
    }
  </script>

  <!-- BEGIN: Custom CSS Styling -->
  <style data-purpose="certificate-styling">
    /* Certificate 1.414 aspect ratio matching landscape A4 standards */
    .cert-aspect {
      aspect-ratio: 1.414 / 1;
    }
    
    /* Background wave layers replicating the geometric/curved overlays of the reference */
    .bg-wave-gradient-1 {
      background: linear-gradient(135deg, rgba(74, 144, 226, 0.45) 0%, rgba(30, 94, 186, 0.7) 100%);
    }
    .bg-wave-gradient-2 {
      background: linear-gradient(135deg, rgba(147, 197, 253, 0.5) 0%, rgba(59, 130, 246, 0.35) 100%);
    }
    .bg-wave-gradient-3 {
      background: linear-gradient(140deg, #1d63ed 0%, #0f3d99 100%);
    }

    /* Serrated Rosette Badge CSS */
    .rosette-outer {
      background: radial-gradient(circle, #ffffff 65%, #eff6ff 100%);
      box-shadow: 0 4px 15px -1px rgba(29, 99, 237, 0.25);
    }
    
    .rosette-starburst {
      position: absolute;
      inset: -6px;
      border-radius: 50%;
      border: 3px dashed #3b82f6;
      animation: spin-slow 40s linear infinite;
    }

    @keyframes spin-slow {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>

  <style data-purpose="print-styles">
    @media print {
      body * {
        visibility: hidden;
      }
      #certificate-canvas, #certificate-canvas * {
        visibility: visible;
      }
      #certificate-canvas {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        margin: 0;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
  <!-- END: Custom CSS Styling -->
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col font-sans antialiased selection:bg-brand-500 selection:text-white">

<!-- BEGIN: TopNavigationBar -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-40 px-4 lg:px-8 py-3.5 shadow-sm no-print" data-purpose="site-header">
  <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
    <!-- Organization / Issuer Brand Info -->
    <div class="flex items-center gap-3">
      <a href="map.php" aria-label="Go Back" class="p-2 -ml-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Back to Map">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
        </svg>
      </a>
      <div class="flex items-center gap-2.5">
        <!-- Diamond Logo Emblem -->
        <div class="flex -space-x-1 items-center">
          <div class="w-4 h-4 bg-slate-900 rotate-45 rounded-sm"></div>
          <div class="w-4 h-4 bg-blue-600 rotate-45 rounded-sm shadow-sm"></div>
        </div>
        <div>
          <span class="text-xs font-black tracking-widest text-slate-900 block font-display leading-none uppercase"><?= htmlspecialchars($companyName) ?></span>
          <span class="text-[10px] text-slate-500 font-medium tracking-tight leading-tight">Accreditation Registry</span>
        </div>
      </div>
      <div class="hidden sm:block h-5 w-px bg-slate-200 mx-2"></div>
      <!-- Verification Status Indicator -->
      <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm" data-purpose="trust-badge">
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
        </span>
        <span>Verified Official Credential</span>
      </div>
    </div>

    <!-- Quick Action Controls -->
    <div class="flex items-center gap-2.5">
      <button class="inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm cursor-pointer" data-purpose="print-action" onclick="window.print()">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
        </svg>
        <span class="hidden md:inline">Print</span>
      </button>
      <button class="inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm cursor-pointer" data-purpose="download-action" onclick="window.print()">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
        </svg>
        <span>Download PDF</span>
      </button>
      <a class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs md:text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm" data-purpose="linkedin-share" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($publicVerifyUrl) ?>" rel="noopener noreferrer" target="_blank">
        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
          <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"></path>
        </svg>
        <span class="hidden md:inline">Add to LinkedIn</span>
      </a>
    </div>
  </div>
</header>
<!-- END: TopNavigationBar -->

<!-- BEGIN: MainContentArea -->
<main class="flex-1 max-w-7xl w-full mx-auto p-4 md:p-6 lg:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" data-purpose="main-layout">

  <!-- Left / Centerpiece: Landscape Certificate Canvas -->
  <section aria-label="Certificate Preview" class="lg:col-span-8 flex flex-col items-center justify-center">
    
    <!-- Certificate Visual Container with frame shadow & borders -->
    <div class="w-full max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden cert-aspect relative border border-slate-200/90 select-none flex flex-col justify-between" data-purpose="certificate-renderer" id="certificate-canvas">
      
      <!-- BEGIN: Certificate Decorative Wave Graphics Replicating IMAGE_2 -->
      <!-- Top-Right Layered Abstract Waves -->
      <div class="absolute -top-16 -right-16 w-96 h-96 pointer-events-none z-0">
        <!-- Light Soft Blue Wave Layer -->
        <div class="absolute top-0 right-0 w-80 h-72 rounded-[45%] rotate-[-25deg] bg-gradient-to-br from-blue-100 via-sky-200 to-blue-300 opacity-60 filter blur-[1px]"></div>
        <!-- Mid Blue Flow Shape -->
        <div class="absolute -top-10 right-4 w-72 h-64 rounded-[42%] rotate-[18deg] bg-gradient-to-bl from-blue-400 via-sky-400 to-blue-500 opacity-50"></div>
        <!-- Upper Gradient Backdrop Corner -->
        <div class="absolute -top-24 -right-10 w-96 h-80 rounded-b-[40%] rotate-[-12deg] bg-gradient-to-l from-blue-500 to-sky-300 opacity-30"></div>
      </div>

      <!-- Left Gradient Side Tint matching reference -->
      <div class="absolute top-0 left-0 bottom-0 w-36 bg-gradient-to-r from-blue-200/50 via-blue-100/25 to-transparent pointer-events-none z-0"></div>

      <!-- Bottom-Left Layered Waves -->
      <div class="absolute -bottom-24 -left-20 w-80 h-80 pointer-events-none z-0">
        <div class="absolute bottom-6 left-6 w-64 h-64 rounded-[45%] rotate-[30deg] bg-gradient-to-tr from-blue-600 to-sky-400 opacity-70"></div>
        <div class="absolute -bottom-8 -left-4 w-60 h-60 rounded-[48%] rotate-[-15deg] bg-gradient-to-t from-blue-800 to-blue-600 opacity-90"></div>
      </div>
      <!-- END: Certificate Decorative Wave Graphics -->

      <!-- BEGIN: Certificate Content Wrapper -->
      <div class="relative z-10 w-full h-full p-8 md:p-12 lg:p-14 flex flex-col justify-between">
        
        <!-- Top Section: Company Logo Header -->
        <div class="flex items-center justify-between w-full" data-purpose="cert-header">
          <div class="flex items-center gap-3">
            <!-- Geometric Dual Diamonds -->
            <div class="flex items-center -space-x-1">
              <div class="w-4 h-4 bg-slate-900 rotate-45 rounded-[1px]"></div>
              <div class="w-4 h-4 bg-blue-600 rotate-45 rounded-[1px]"></div>
            </div>
            <span class="text-xs md:text-sm font-bold tracking-wider text-slate-800 uppercase font-display"><?= htmlspecialchars($companyName) ?></span>
          </div>
          <!-- Subtle Credential ID print reference -->
          <span class="text-[10px] tracking-wider text-slate-400 font-mono">ID: <?= htmlspecialchars($credentialId) ?></span>
        </div>

        <!-- Middle Section: Main Certificate Titles and Recipient Name -->
        <div class="my-auto pl-2 md:pl-4 relative flex justify-between items-center" data-purpose="cert-body">
          <div class="max-w-xl">
            <!-- Main "Certificate" Title -->
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-blue-600 tracking-tight font-display mb-1.5 drop-shadow-xs">
              Certificate
            </h1>
            <!-- "OF ACHIEVEMENT" Subtitle -->
            <h2 class="text-xs sm:text-sm md:text-base font-bold text-slate-900 tracking-widest font-display mb-1 uppercase">
              OF ACHIEVEMENT
            </h2>
            <!-- "THIS CERTIFICATE IS PRESENTED TO" -->
            <p class="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4 md:mb-5">
              THIS CERTIFICATE IS PRESENTED TO
            </p>
            <!-- Recipient Name -->
            <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-blue-600 font-display mb-4 md:mb-5 tracking-tight">
              <?= htmlspecialchars($recipientName) ?>
            </div>
            <!-- Body Paragraph / Citation -->
            <div class="space-y-1.5 pr-4 md:pr-10">
              <p class="text-[11px] sm:text-xs md:text-sm font-bold text-slate-800">
                <?= htmlspecialchars($citationTitle) ?>
              </p>
              <p class="text-[9px] sm:text-[10px] md:text-xs text-slate-500 leading-relaxed max-w-lg">
                <?= htmlspecialchars($citationBody) ?>
              </p>
            </div>
          </div>

          <!-- Rosette Award Medal Seal Replicating Reference Design -->
          <div class="hidden sm:flex flex-col items-center justify-center relative shrink-0 mr-4 lg:mr-8 -mt-10" data-purpose="award-ribbon-badge">
            <!-- Rosette Outer Disc with Serrated Border Effect -->
            <div class="w-28 h-28 md:w-32 md:h-32 rounded-full rosette-outer border-4 border-blue-500 relative flex items-center justify-center p-2 shadow-xl">
              <!-- Inner Dashed Ring -->
              <div class="w-full h-full rounded-full border-2 border-dashed border-blue-400 flex flex-col items-center justify-center text-center p-2 bg-white/90">
                <!-- Star Embellishments -->
                <div class="flex items-center justify-center gap-1 text-blue-600 text-[10px] mb-0.5">
                  <span>★</span>
                  <span>★</span>
                  <span>★</span>
                </div>
                <!-- Rosette Text -->
                <div class="text-xs md:text-sm font-black text-blue-700 uppercase font-display leading-none tracking-tight">
                  BEST
                </div>
                <div class="text-[11px] md:text-xs font-bold text-blue-600 uppercase font-display tracking-tight leading-none mt-0.5">
                  AWARD
                </div>
                <!-- Bottom Stars -->
                <div class="flex items-center justify-center gap-1 text-blue-600 text-[10px] mt-1">
                  <span>★</span>
                  <span>★</span>
                </div>
              </div>

              <!-- Award Hanging Ribbons Below -->
              <div class="absolute -bottom-6 flex justify-center -space-x-1 pointer-events-none z-[-1]">
                <!-- Left Ribbon Tail -->
                <div class="w-7 h-10 bg-gradient-to-b from-blue-500 to-sky-400 rotate-12 origin-top transform clip-ribbon shadow-sm" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);"></div>
                <!-- Right Ribbon Tail -->
                <div class="w-7 h-10 bg-gradient-to-b from-blue-600 to-sky-500 -rotate-12 origin-top transform clip-ribbon shadow-sm" style="clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Section: Date & Signature Lines -->
        <div class="pt-6 grid grid-cols-2 items-end w-full pl-2 md:pl-4" data-purpose="cert-footer">
          <!-- Date Column -->
          <div>
            <div class="text-[11px] sm:text-xs md:text-sm font-extrabold text-slate-800 tracking-wider uppercase font-display mb-1.5">
              <?= htmlspecialchars($issueDateUpper) ?>
            </div>
            <div class="w-32 sm:w-40 border-b border-slate-300"></div>
            <div class="text-[9px] sm:text-[10px] font-semibold tracking-widest text-slate-500 uppercase mt-1">
              DATE
            </div>
          </div>

          <!-- Signature Column -->
          <div class="flex flex-col items-end sm:items-start sm:ml-auto pr-2 md:pr-4">
            <!-- Handwritten Signature Artwork Simulation -->
            <div class="h-10 flex items-end mb-0.5 select-none pr-4">
              <span class="font-signature text-3xl sm:text-4xl text-slate-900 leading-none -rotate-3 tracking-wide">
                <?= htmlspecialchars($signatoryName) ?>
              </span>
            </div>
            <div class="w-32 sm:w-40 border-b border-slate-800"></div>
            <div class="text-[9px] sm:text-[10px] font-semibold tracking-widest text-slate-500 uppercase mt-1">
              SIGNATURE
            </div>
          </div>
        </div>

      </div>
      <!-- END: Certificate Content Wrapper -->
    </div>

    <!-- Certificate Action Note & Zoom Tools below canvas -->
    <div class="mt-4 flex items-center justify-between w-full max-w-4xl px-2 text-xs text-slate-500 no-print" data-purpose="canvas-subtext">
      <span class="flex items-center gap-1.5">
        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
          <path clip-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" fill-rule="evenodd"></path>
        </svg>
        Tamper-evident digital proof cryptographically signed.
      </span>
      <div class="flex items-center gap-3">
        <button class="hover:text-slate-800 inline-flex items-center gap-1 font-medium transition cursor-pointer" onclick="toggleFullScreen()">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
          </svg>
          Fullscreen
        </button>
      </div>
    </div>
  </section>

  <!-- Right: Credential & Issuer Verification Metadata Sidebar -->
  <aside aria-label="Verification Summary" class="lg:col-span-4 w-full space-y-5 no-print">
    <!-- Card: Credential Status -->
    <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm" data-purpose="verification-card">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider font-display">
          Verification Details
        </h3>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
          Valid &amp; Active
        </span>
      </div>

      <div class="divide-y divide-slate-100 text-sm">
        <!-- Recipient -->
        <div class="py-3 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Recipient</span>
          <span class="text-slate-900 font-semibold"><?= htmlspecialchars($recipientName) ?></span>
        </div>

        <!-- Credential ID -->
        <div class="py-3 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Credential ID</span>
          <div class="flex items-center gap-1.5 font-mono text-xs font-semibold text-slate-800 bg-slate-100 px-2 py-1 rounded">
            <span><?= htmlspecialchars($credentialId) ?></span>
            <button class="text-slate-400 hover:text-slate-700 transition cursor-pointer" onclick="copyCredentialId('<?= htmlspecialchars($credentialId, ENT_QUOTES) ?>')" title="Copy ID">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Issuer -->
        <div class="py-3 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Issuer</span>
          <span class="text-slate-900 font-semibold text-right"><?= htmlspecialchars($issuerName) ?></span>
        </div>

        <!-- Issue Date -->
        <div class="py-3 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Issue Date</span>
          <span class="text-slate-900 font-semibold"><?= htmlspecialchars($issueDate) ?></span>
        </div>

        <!-- Blockchain Signature -->
        <div class="py-3 flex justify-between items-center">
          <span class="text-slate-500 font-medium">Ledger Hash</span>
          <span class="font-mono text-[11px] text-blue-600 truncate max-w-[170px]" title="<?= htmlspecialchars($ledgerHash) ?>">
            <?= htmlspecialchars($shortLedgerHash) ?>
          </span>
        </div>
      </div>

      <!-- QR Code Proof Verification Container -->
      <div class="mt-5 bg-slate-50 rounded-lg p-4 border border-slate-200/80 flex items-center gap-4" data-purpose="qr-proof-block">
        <!-- Dynamic Live QR Code Container -->
        <div id="live-qr-canvas" class="p-1.5 bg-white rounded border border-slate-200 shadow-sm shrink-0 flex items-center justify-center">
          <!-- QR Canvas generated via JavaScript below -->
        </div>
        <div>
          <span class="text-xs font-bold text-slate-800 block">Instant Verification</span>
          <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
            Point your smartphone camera at the code to inspect immutable issuing metadata.
          </p>
        </div>
      </div>
    </div>

    <!-- Card: Digital Wallets & Integrations -->
    <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3" data-purpose="wallet-integrations">
      <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 font-display">Add to Digital Passports</h4>
      <div class="grid grid-cols-2 gap-2.5">
        <button class="flex items-center justify-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-800 transition cursor-pointer" onclick="alert('Digital credential format prepared for Apple Wallet.')">
          <svg class="w-4 h-4 fill-current text-slate-900" viewBox="0 0 24 24">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.85-.92.04-2.02.62-2.66 1.37-.56.65-.98 1.72-.85 2.74 1.03.08 2.06-.51 2.59-1.26z"></path>
          </svg>
          Apple Wallet
        </button>
        <button class="flex items-center justify-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-800 transition cursor-pointer" onclick="alert('Digital pass link prepared for Google Wallet.')">
          <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z" fill="#4285F4"></path>
            <path d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z" fill="#34A853"></path>
            <path d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.14-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15Z" fill="#FBBC05"></path>
            <path d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z" fill="#EA4335"></path>
          </svg>
          Google Wallet
        </button>
      </div>
    </div>
  </aside>

</main>
<!-- END: MainContentArea -->

<!-- BEGIN: SiteFooter -->
<footer class="bg-white border-t border-slate-200 py-6 mt-auto no-print" data-purpose="site-footer">
  <div class="max-w-7xl mx-auto px-4 md:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
    <div class="flex items-center gap-2">
      <span class="font-semibold text-slate-800"><?= htmlspecialchars($companyName) ?> Credential Service</span>
      <span>© <?= date('Y') ?> All Rights Reserved.</span>
    </div>
    <div class="flex items-center gap-6">
      <a class="hover:underline hover:text-slate-700" href="#privacy">Privacy Policy</a>
      <a class="hover:underline hover:text-slate-700" href="#terms">Terms of Issuance</a>
      <a class="hover:underline hover:text-slate-700" href="#report">Report Infringement</a>
    </div>
  </div>
</footer>
<!-- END: SiteFooter -->

<!-- Dynamic QR Rendering & Helper Scripts -->
<script>
  const VERIFY_URL = <?= json_encode($publicVerifyUrl, JSON_UNESCAPED_SLASHES) ?>;

  // Initialize dynamic QR code in the sidebar
  document.addEventListener('DOMContentLoaded', () => {
    const qrContainer = document.getElementById('live-qr-canvas');
    if (qrContainer && typeof QRCode !== 'undefined') {
      qrContainer.innerHTML = '';
      new QRCode(qrContainer, {
        text: VERIFY_URL,
        width: 68,
        height: 68,
        colorDark: "#1e3a8a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
      });
    }
  });

  // Copy credential ID helper
  function copyCredentialId(text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        alert('Credential ID copied to clipboard: ' + text);
      }).catch(() => {
        prompt('Copy Credential ID:', text);
      });
    } else {
      prompt('Copy Credential ID:', text);
    }
  }

  // Toggle Fullscreen Preview
  function toggleFullScreen() {
    const el = document.getElementById('certificate-canvas');
    if (!el) return;
    if (!document.fullscreenElement) {
      if (el.requestFullscreen) {
        el.requestFullscreen();
      } else if (el.webkitRequestFullscreen) {
        el.webkitRequestFullscreen();
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  }
</script>

</body>
</html>
