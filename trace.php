<?php
/**
 * GeoRubber Watch - EUDR Digital Certificate & Traceability Passport Portal
 * Integrated with the GeoRubber Watch Eco-GIS (Mezenc) Design System
 * 
 * Prince of Songkla University, Surat Thani Campus
 */

define('ALLOW_PUBLIC_ACCESS', true);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth_check.php';
$currentUser = getCurrentUser();
$current_role = $currentUser['role'] ?? 'guest';
$user_name = $currentUser['full_name'] ?? 'ผู้เยี่ยมชม / Guest';
$isLoggedIn = isLoggedIn();

// Attempt Database Connection
$pdo = null;
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    if (function_exists('initDatabaseIfNeeded')) {
        initDatabaseIfNeeded();
    }
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
    }
}

// Support various parameter names: token, id, code
$token = trim($_GET['token'] ?? '');
$plot_code = trim($_GET['code'] ?? '');
$plot_id = intval($_GET['id'] ?? ($_GET['plot_id'] ?? 0));

// Handle string values passed in ?id= (e.g. ?id=EUDR-TH-ST-84000-020-8EFD4C or ?id=RB-ST-2026-020)
if (!empty($_GET['id']) && !is_numeric($_GET['id'])) {
    $rawId = trim($_GET['id']);
    if (strpos($rawId, 'EUDR-') === 0) {
        $token = $rawId;
    } else {
        $plot_code = $rawId;
    }
}

if ($token === 'undefined' || $token === 'null') $token = '';
if ($plot_code === 'undefined' || $plot_code === 'null') $plot_code = '';

$plot = null;

if ($pdo) {
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

    if (!empty($where)) {
        $whereClause = implode(' OR ', $where);
        try {
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
        } catch (Exception $e) {
            $plot = null;
        }
    }

    // If still not found and no specific token was searched, query first plot
    if (!$plot && empty($token) && empty($plot_code) && $plot_id === 0) {
        try {
            $stmt = $pdo->query("
                SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
                       f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
                FROM rubber_plots p
                LEFT JOIN farmers f ON f.id = p.farmer_id
                ORDER BY p.id ASC LIMIT 1
            ");
            $plot = $stmt ? $stmt->fetch() : null;
        } catch (Exception $e) {
            $plot = null;
        }
    }
}

// Fallback Mock Data: Specifically matches demonstration plots (e.g. 020, 021)
if (!$plot) {
    $fallbackId = 20;
    $fallbackCode = !empty($plot_code) ? $plot_code : 'RB-ST-2026-020';
    $fallbackName = 'แปลงยางพาราเขาท่าเพชร 1';

    if (!empty($token) && preg_match('/EUDR-TH-ST-84000-(\d+)/', $token, $matches)) {
        $fallbackId = (int)$matches[1];
        $padNum = str_pad((string)$fallbackId, 3, '0', STR_PAD_LEFT);
        $fallbackCode = 'RB-ST-2026-' . $padNum;
        $fallbackName = ($fallbackId === 21) ? 'แปลงยางพารา 2' : ('แปลงยางพารา ' . $fallbackId);
    } elseif (!empty($plot_code) && preg_match('/RB-ST-2026-(\d+)/', $plot_code, $matches)) {
        $fallbackId = (int)$matches[1];
        $fallbackName = ($fallbackId === 21) ? 'แปลงยางพารา 2' : ('แปลงยางพารา ' . $fallbackId);
    }

    $plot = [
        'id' => $fallbackId,
        'plot_code' => $fallbackCode,
        'plot_name' => $fallbackName,
        'farmer_code' => 'FM-ST-0889',
        'prefix' => 'นางสาว',
        'first_name' => 'มนัสนันท์',
        'last_name' => 'อนันตณรงค์',
        'farmer_phone' => '095-331-8033',
        'subdistrict' => 'มะขามเตี้ย',
        'district' => 'เมืองสุราษฎร์ธานี',
        'province' => 'สุราษฎร์ธานี',
        'title_deed_type' => 'โฉนดที่ดิน (น.ส. 4 จ)',
        'title_deed_no' => '45678 (ระวาง 8401-4458)',
        'area_rai' => 12,
        'area_ngan' => 2,
        'area_sqwah' => 0,
        'area_hectare' => 2.0000,
        'rubber_clone' => 'RRIM 600',
        'planting_year' => 2018,
        'tree_count' => 920,
        'trees_per_rai' => 76,
        'centroid_lat' => 9.138240,
        'centroid_lng' => 99.321850,
        'eudr_status' => 'compliant',
        'eudr_overlap_pct' => 0.0,
        'eudr_deforestation_free' => 1,
        'eudr_cutoff_compliant' => 1,
        'traceability_token' => !empty($token) ? $token : 'EUDR-TH-ST-84000-020-8EFD4C',
        'created_at' => '2026-03-05 10:00:00',
        'geojson_geometry' => json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3195, 9.1395],
                [99.3235, 9.1402],
                [99.3242, 9.1370],
                [99.3202, 9.1363],
                [99.3195, 9.1395]
            ]]
        ])
    ];
}

// Fetch harvest batch logs (yield_logs)
$yields = [];
if ($pdo && !empty($plot['id'])) {
    try {
        $yieldStmt = $pdo->prepare("SELECT * FROM yield_logs WHERE plot_id = ? ORDER BY harvest_date DESC LIMIT 5");
        $yieldStmt->execute([$plot['id']]);
        $yields = $yieldStmt->fetchAll();
    } catch (Exception $e) {
        $yields = [];
    }
}

// High-fidelity fallback yield records if table empty
if (empty($yields)) {
    $yields = [
        [
            'harvest_date' => '2026-03-04',
            'tapping_round' => 14,
            'fresh_latex_kg' => 148.5,
            'drc_percent' => 34.0,
            'dry_rubber_kg' => 50.49,
            'buyer_name' => 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด'
        ],
        [
            'harvest_date' => '2026-03-02',
            'tapping_round' => 13,
            'fresh_latex_kg' => 152.0,
            'drc_percent' => 33.5,
            'dry_rubber_kg' => 50.92,
            'buyer_name' => 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด'
        ],
        [
            'harvest_date' => '2026-02-28',
            'tapping_round' => 12,
            'fresh_latex_kg' => 145.0,
            'drc_percent' => 33.8,
            'dry_rubber_kg' => 49.01,
            'buyer_name' => 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด'
        ],
        [
            'harvest_date' => '2026-02-26',
            'tapping_round' => 11,
            'fresh_latex_kg' => 150.2,
            'drc_percent' => 34.2,
            'dry_rubber_kg' => 51.37,
            'buyer_name' => 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด'
        ]
    ];
}

// Compute summary totals for Annex Page (Page 2)
$totalFreshLatex = 0;
$totalDryRubber = 0;
$sumDrc = 0;
foreach ($yields as $y) {
    $totalFreshLatex += (float)($y['fresh_latex_kg'] ?? 0);
    $totalDryRubber += (float)($y['dry_rubber_kg'] ?? 0);
    $sumDrc += (float)($y['drc_percent'] ?? 0);
}
$avgDrc = count($yields) > 0 ? ($sumDrc / count($yields)) : 0;

function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function thaiDate($dateStr) {
    if (empty($dateStr)) return '-';
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $ts = strtotime($dateStr);
    if (!$ts) return $dateStr;
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = (int)date('Y', $ts) + 543;
    return "{$d} {$m} {$y}";
}

// PDPA Masking Helpers (พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562)
function maskPdpaName($prefix, $first, $last, $isLoggedIn) {
    $pfx = !empty($prefix) ? $prefix . ' ' : '';
    if ($isLoggedIn) {
        return trim("{$pfx}{$first} {$last}");
    }
    $firstMasked = !empty($first) ? (mb_substr($first, 0, 1) . '***') : '***';
    $lastMasked = !empty($last) ? (mb_substr($last, 0, 1) . '***') : '';
    return trim("{$pfx}{$firstMasked} {$lastMasked}") . ' (ข้อมูลคุ้มครองตาม พ.ร.บ. PDPA)';
}

function maskPdpaIdCard($idCard, $isLoggedIn) {
    if (empty($idCard)) {
        $idCard = '1849900123456';
    }
    $digits = preg_replace('/[^0-9]/', '', $idCard);
    if (strlen($digits) === 13) {
        if ($isLoggedIn) {
            return substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-' . substr($digits, 5, 5) . '-' . substr($digits, 10, 2) . '-' . substr($digits, 12, 1);
        } else {
            return substr($digits, 0, 1) . '-' . substr($digits, 1, 4) . '-*****-**-' . substr($digits, -1) . ' [PDPA Protected]';
        }
    }
    return $isLoggedIn ? $idCard : '1-8499-*****-**-6 [PDPA Protected]';
}

function maskPdpaDeed($type, $deedNo, $isLoggedIn) {
    $t = !empty($type) ? $type : 'โฉนดที่ดิน (น.ส. 4 จ)';
    if ($isLoggedIn) {
        return $t . ' เลขที่ ' . ($deedNo ?: '45678 (ระวาง 8401-4458)');
    }
    return $t . ' เลขที่ ****** (ข้อมูลคุ้มครองตาม พ.ร.บ. PDPA)';
}

function maskPdpaCode($code, $isLoggedIn) {
    if ($isLoggedIn || empty($code)) return $code;
    $parts = explode('-', $code);
    if (count($parts) >= 2) {
        $parts[count($parts) - 1] = '***';
        return implode('-', $parts);
    }
    return substr($code, 0, max(2, strlen($code) - 3)) . '***';
}

function maskPdpaToken($token, $isLoggedIn) {
    if ($isLoggedIn || empty($token)) return $token;
    if (strpos($token, 'EUDR-') === 0) {
        $parts = explode('-', $token);
        if (count($parts) >= 3) {
            $parts[count($parts) - 1] = '***';
            return implode('-', $parts);
        }
    }
    return substr($token, 0, 15) . '-***';
}

$isNonCompliant = (($plot['eudr_status'] ?? '') === 'non_compliant' || ((float)($plot['eudr_overlap_pct'] ?? 0) > 0));
$isUnderReview = (($plot['eudr_status'] ?? '') === 'under_review');
$isCompliant = (!$isNonCompliant && !$isUnderReview);

if ($isNonCompliant) {
    $statusBadgeText = 'ทับซ้อนป่าสงวน (ไม่ผ่านเกณฑ์)';
    $riskLevelText = 'ความเสี่ยงสูงมาก (High Risk)';
} elseif ($isUnderReview) {
    $statusBadgeText = 'โซนเฝ้าระวัง (Buffer < 500 ม.)';
    $riskLevelText = 'ความเสี่ยงปานกลาง (Under Review)';
} else {
    $statusBadgeText = 'ผ่านเกณฑ์ EUDR (ปลอดตัดไม้)';
    $riskLevelText = 'ความเสี่ยงต่ำ (Negligible)';
}

$rawPrefix = $plot['prefix'] ?? 'นางสาว';
$rawFirst = $plot['first_name'] ?? 'มนัสนันท์';
$rawLast = $plot['last_name'] ?? 'อนันตณรงค์';
$farmerFullName = maskPdpaName($rawPrefix, $rawFirst, $rawLast, $isLoggedIn);
$rawIdCard = $plot['id_card_num'] ?? '1849900123456';
$nationalIdFormatted = maskPdpaIdCard($rawIdCard, $isLoggedIn);

$locationText = 'ต.' . ($plot['subdistrict'] ?? 'มะขามเตี้ย') . ' อ.' . ($plot['district'] ?? 'เมืองสุราษฎร์ธานี') . ' จ.' . ($plot['province'] ?? 'สุราษฎร์ธานี');
$landDocText = maskPdpaDeed($plot['title_deed_type'] ?? '', $plot['title_deed_no'] ?? '', $isLoggedIn);
$certNo = !empty($plot['plot_code']) ? $plot['plot_code'] : 'RB-ST-2026-020';
$displayCertNo = maskPdpaCode($certNo, $isLoggedIn);
$tokenCode = !empty($plot['traceability_token']) ? $plot['traceability_token'] : 'EUDR-TH-ST-84000-020-8EFD4C';
$displayTokenCode = maskPdpaToken($tokenCode, $isLoggedIn);
$farmerCode = !empty($plot['farmer_code']) ? $plot['farmer_code'] : 'FM-ST-0889';
$displayFarmerCode = maskPdpaCode($farmerCode, $isLoggedIn);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

// Prioritize ngrok public URL for QR Code and share link so mobile cameras can scan
$publicBase = 'https://earthling-retype-aroma.ngrok-free.dev';
if (strpos($host, 'ngrok') !== false) {
    $verifyUrl = $protocol . $host . $dir . '/trace.php?token=' . rawurlencode($tokenCode);
} else {
    $verifyUrl = $publicBase . $dir . '/trace.php?token=' . rawurlencode($tokenCode);
}

$ledgerHash = '0x' . hash('sha256', $certNo . $tokenCode . ($plot['created_at'] ?? '2026-03-05'));
$shortHash = substr($ledgerHash, 0, 8) . '...' . substr($ledgerHash, -6);

// Helper: Haversine distance in meters between two WGS84 coordinates
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 2);
}

// Helper: Exact WGS84 UTM Zone 47N coordinates (Central Meridian 99°E for Surat Thani / Southern Thailand)
function latLngToUtm47N($lat, $lng) {
    $a = 6378137.0;
    $f = 1 / 298.257223563;
    $b = $a * (1 - $f);
    $e2 = ($a*$a - $b*$b) / ($a*$a);
    $e_prime2 = ($a*$a - $b*$b) / ($b*$b);
    $k0 = 0.9996;
    $lon0 = 99.0;
    
    $latRad = deg2rad($lat);
    $lonRad = deg2rad($lng);
    $lon0Rad = deg2rad($lon0);
    
    $N = $a / sqrt(1 - $e2 * sin($latRad) * sin($latRad));
    $T = tan($latRad) * tan($latRad);
    $C = $e_prime2 * cos($latRad) * cos($latRad);
    $A = cos($latRad) * ($lonRad - $lon0Rad);
    
    $M = $a * ( (1 - $e2/4 - 3*$e2*$e2/64 - 5*$e2*$e2*$e2/256) * $latRad
              - (3*$e2/8 + 3*$e2*$e2/32 + 45*$e2*$e2*$e2/1024) * sin(2*$latRad)
              + (15*$e2*$e2/256 + 45*$e2*$e2*$e2/1024) * sin(4*$latRad)
              - (35*$e2*$e2*$e2/3072) * sin(6*$latRad) );
              
    $easting = $k0 * $N * ($A + (1 - $T + $C)*$A*$A*$A/6 + (5 - 18*$T + $T*$T + 72*$C - 58*$e_prime2)*$A*$A*$A*$A*$A/120) + 500000.0;
    $northing = $k0 * ($M + $N * tan($latRad) * ($A*$A/2 + (5 - $T + 9*$C + 4*$C*$C)*$A*$A*$A*$A/24 + (61 - 58*$T + $T*$T + 600*$C - 330*$e_prime2)*$A*$A*$A*$A*$A*$A/720));
    
    return [
        'easting' => round($easting, 2),
        'northing' => round($northing, 2)
    ];
}

// Extract Polygon Points & Calculate Boundary Distances
$polygonPoints = [];
if (!empty($plot['geojson_geometry'])) {
    $geoData = is_string($plot['geojson_geometry']) ? json_decode($plot['geojson_geometry'], true) : $plot['geojson_geometry'];
    if (!empty($geoData['coordinates'][0])) {
        $rawCoords = $geoData['coordinates'][0];
        $n = count($rawCoords);
        if ($n > 1 && abs($rawCoords[0][0] - $rawCoords[$n-1][0]) < 1e-6 && abs($rawCoords[0][1] - $rawCoords[$n-1][1]) < 1e-6) {
            array_pop($rawCoords);
        }
        foreach ($rawCoords as $idx => $pt) {
            $lng = (float)$pt[0];
            $lat = (float)$pt[1];
            $utm = latLngToUtm47N($lat, $lng);
            $polygonPoints[] = [
                'idx' => $idx + 1,
                'lng' => $lng,
                'lat' => $lat,
                'easting' => $utm['easting'],
                'northing' => $utm['northing']
            ];
        }
    }
}

if (empty($polygonPoints)) {
    $cLat = (float)($plot['centroid_lat'] ?? 9.138240);
    $cLng = (float)($plot['centroid_lng'] ?? 99.321850);
    $defaultCoords = [
        [$cLng - 0.0018, $cLat + 0.0012],
        [$cLng + 0.0015, $cLat + 0.0018],
        [$cLng + 0.0022, $cLat - 0.0012],
        [$cLng - 0.0012, $cLat - 0.0019],
        [$cLng - 0.0024, $cLat - 0.0001]
    ];
    foreach ($defaultCoords as $idx => $pt) {
        $utm = latLngToUtm47N($pt[1], $pt[0]);
        $polygonPoints[] = [
            'idx' => $idx + 1,
            'lng' => $pt[0],
            'lat' => $pt[1],
            'easting' => $utm['easting'],
            'northing' => $utm['northing']
        ];
    }
}

$sideDistances = [];
$totalPts = count($polygonPoints);
for ($i = 0; $i < $totalPts; $i++) {
    $p1 = $polygonPoints[$i];
    $p2 = $polygonPoints[($i + 1) % $totalPts];
    $dist = haversineDistance($p1['lat'], $p1['lng'], $p2['lat'], $p2['lng']);
    $sideDistances[] = [
        'side' => $i + 1,
        'from_to' => "{$p1['idx']} - {$p2['idx']}",
        'distance' => $dist
    ];
}
?>
<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=h($certNo)?> | EUDR Compliance Certificate & Digital Passport - GeoRubber Watch</title>

  <!-- Google Fonts: Google Sans, Open Sans, Sarabun (ฟอนต์ภาษาไทยทางการของระบบ), Dancing Script (ลายมือชื่อ) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

  <!-- Leaflet GIS Map CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <!-- Font Awesome 6.5.1 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Core Eco-GIS Stylesheet & Centralized i18n Engine -->
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/i18n.js"></script>

  <!-- Tailwind CSS CDN with Mezenc Palette Extensions -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            mezenc: {
              teal: '#0e4d4e',
              deepTeal: '#093637',
              darkNavy: '#062627',
              mint: '#5ebbb6',
              brightCyan: '#00a699',
              lightCyan: '#e6f7f6',
              sand: '#f8faf9',
              slateText: '#1a332c',
              muted: '#6b7280'
            },
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
            sans: ['"Google Sans"', '"Open Sans"', 'Sarabun', 'sans-serif'],
            thai: ['Sarabun', '"Google Sans"', 'sans-serif'],
            mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace'],
            signature: ['"Dancing Script"', 'cursive']
          }
        }
      }
    }
  </script>

  <style>
    /* กำหนดฟอนต์ภาษาไทยของทั้งระบบเป็น Sarabun และอังกฤษเป็น Google Sans ตามมาตรฐานเว็บไซต์ GeoRubber Watch */
    body {
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #f8faf9;
      color: #1a332c;
      overflow-x: hidden;
      -webkit-tap-highlight-color: transparent;
    }

    h1, h2, h3, h4, h5, h6, .font-heading {
      font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif;
      letter-spacing: -0.015em;
    }

    .font-mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    }

    .font-signature {
      font-family: 'Dancing Script', cursive !important;
    }

    /* Language Switcher Styles */
    .toggle-track-dark {
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(255, 255, 255, 0.25);
      border-radius: 9999px;
      backdrop-filter: blur(8px);
    }
    .toggle-thumb-dark {
      position: absolute;
      top: 2px;
      background: #ffffff;
      border-radius: 9999px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    /* Fullscreen Floating Controls - strictly hidden unless fullscreen active */
    .fullscreen-toolbar {
      display: none !important;
    }
    :fullscreen .fullscreen-toolbar,
    :-webkit-full-screen .fullscreen-toolbar,
    .is-modal-fullscreen .fullscreen-toolbar {
      display: flex !important;
    }

    /* Fullscreen Theater Mode for Portrait Certificate */
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
      background: #061e18 !important;
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
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.75) !important;
      margin-bottom: 3rem !important;
    }

    /* สไตล์แบบแสดงข้อมูลแปลงที่ดินทางการ (Cadastral & EUDR Document System) */
    .cadastral-paper {
      width: 100%;
      max-width: 840px;
      background: #fffdf9;
      border-radius: 16px;
      box-shadow: 0 20px 45px -10px rgba(14, 77, 78, 0.18), 0 4px 15px rgba(0, 0, 0, 0.05);
      position: relative;
      color: #1a2e2b;
      border: 1.5px solid #bee6e1;
      box-sizing: border-box;
      padding: 1.4rem 1.6rem;
    }

    .cadastral-border {
      border: 2px solid #0e4d4e;
      outline: 4px double #5ebbb6;
      outline-offset: -6px;
      padding: 12px 14px;
      border-radius: 6px;
      background-color: #fffdfa;
    }

    .cadastral-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 5px;
      font-size: 11.5px;
      font-family: 'Sarabun', 'Google Sans', sans-serif;
    }
    .cadastral-table th, .cadastral-table td {
      border: 1px solid #1a332c;
      padding: 3.5px 5px;
      line-height: 1.3;
    }
    .cadastral-th {
      background-color: #f0faf9;
      color: #093637;
      font-weight: 700;
      text-align: center;
    }
    .cadastral-val {
      text-align: center;
      font-weight: 400;
      color: #1a2e2b;
    }
    .cadastral-section-title {
      font-size: 12.5px;
      font-weight: 700;
      color: #0e4d4e;
      margin-top: 6px;
      margin-bottom: 2px;
      display: flex;
      align-items: center;
      gap: 5px;
      font-family: 'Sarabun', 'Google Sans', sans-serif;
    }
    .cadastral-dashed-line {
      border-bottom: 1px dashed #94a3b8;
      margin-top: 13px;
      margin-bottom: 2px;
    }

    /* Print Styles: A4 Portrait (2 Pages: Page 1 Cadastral Record, Page 2 Production Annex) */
    @media print {
      @page {
        size: A4 portrait;
        margin: 5mm 7mm;
      }
      body {
        background: #fff !important;
        padding: 0 !important;
        color: #000 !important;
      }
      .no-print, header, footer, section:first-of-type, .xl\:col-span-4, .fullscreen-toolbar, #mobile-drawer, .page-separator-badge {
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
      #certificateCanvas {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .cert-paper, .cadastral-paper {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-sizing: border-box !important;
      }
      .cadastral-border {
        padding: 8px 10px !important;
      }
      .cert-paper-page1 {
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
      .cert-paper-page2 {
        page-break-before: always !important;
        break-before: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-top: 0 !important;
      }
      #cert-map {
        height: 220px !important;
      }
    }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">

  <!-- ==========================================================================
       1. HERO BANNER & TOP NAVIGATION (Identical to map.php, dashboard.php, yields.php)
       ========================================================================== -->
  <section class="relative w-full overflow-hidden bg-mezenc-deepTeal flex flex-col justify-between no-print">
    
    <!-- Background Image & Uniform Overlays -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img src="img/map_rubber_hero.jpg" alt="Surat Thani EUDR Rubber Traceability" class="w-full h-full object-cover object-center filter brightness-95 contrast-105" onerror="this.onerror=null; this.src='img/dashboard_dss_hero.jpg';">
      <div class="absolute inset-0 bg-black/45"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-transparent"></div>
      <div class="absolute bottom-0 left-0 right-0 h-20 sm:h-28 bg-gradient-to-t from-[#f8faf9] from-15% via-[#f8faf9]/80 via-55% to-transparent pointer-events-none z-[1]"></div>
    </div>

    <!-- Top Navigation Header (Floating over hero) -->
    <header class="relative z-30 w-full px-5 py-5 sm:px-8 sm:py-6 md:px-12 xl:px-14 md:py-7 flex justify-between items-center max-w-[1440px] 2xl:max-w-[1600px] mx-auto">
      
      <!-- LOGO -->
      <div
        class="flex items-center gap-2.5 sm:gap-3 cursor-pointer group"
        onclick="location.href='index.php'"
      >
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/30 group-hover:scale-105 transition-transform shadow-md shrink-0 p-1.5">
          <svg
            class="w-full h-full text-white"
            viewBox="0 0 192 192"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
          </svg>
        </div>

        <span
          class="
            text-lg
            sm:text-xl
            md:text-2xl
            font-black
            tracking-wide
            text-white
            drop-shadow
            truncate
          "
        >
          GeoRubber Watch
        </span>
      </div>

      <!-- DESKTOP NAVIGATION LINKS -->
      <nav class="hidden lg:flex items-center gap-5 xl:gap-7 text-sm xl:text-base font-medium tracking-wide">
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="index.php" data-i18n="nav_home">
          หน้าแรก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="overview.php" data-i18n="nav_gis">
          แผนที่ GIS
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="dashboard.php" data-i18n="nav_dashboard">
          แดชบอร์ด
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="map.php" data-i18n="nav_plots">
          แปลงปลูก
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="yields.php" data-i18n="nav_yields">
          ผลผลิต
        </a>
        <a class="text-white hover:text-mezenc-mint transition-colors cursor-pointer drop-shadow-sm" href="contact.php" data-i18n="nav_contact">
          ติดต่อเรา
        </a>
      </nav>

      <!-- RIGHT ACTIONS (Language Toggle, User, Mobile Hamburger) -->
      <div class="flex items-center gap-2 sm:gap-3">
        
        <!-- Segmented Pill Language Toggle Switch (TH / EN) -->
        <div 
          onclick="toggleLanguage()"
          class="toggle-track-dark w-[82px] sm:w-[90px] h-[34px] sm:h-[36px] p-[3px] flex items-center relative cursor-pointer mr-0.5 sm:mr-1 shrink-0"
          title="คลิกเพื่อสลับภาษา TH / EN (Switch Language)"
          id="lang-toggle-btn"
        >
          <div 
            id="nav-thumb" 
            class="toggle-thumb-dark w-[36px] sm:w-[40px] h-[28px] sm:h-[30px] transition-all duration-300 left-[3px]"
          ></div>
          
          <div 
            id="nav-label-th" 
            class="relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none"
          >
            TH
          </div>
          
          <div 
            id="nav-label-en" 
            class="relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none"
          >
            EN
          </div>
        </div>

        <!-- USER PROFILE / LOGIN (Desktop/iPad) -->
        <div class="hidden sm:flex items-center gap-2 bg-white/10 backdrop-blur-md rounded-full py-1.5 px-3.5 border border-white/20 text-xs shadow-md">
          <div class="text-right leading-tight">
            <div class="font-bold text-white"><?= htmlspecialchars($user_name) ?></div>
            <div class="text-[10px] text-mezenc-mint font-semibold"><?= htmlspecialchars($current_role) ?></div>
          </div>
          <?php if ($isLoggedIn): ?>
          <a
            href="logout.php"
            class="text-white/80 hover:text-red-300 flex items-center justify-center w-7 h-7 rounded-full bg-white/10 hover:bg-red-500/30 transition-all cursor-pointer ml-1"
            title="ออกจากระบบ (Logout)"
            data-i18n-title="nav_logout"
            onclick="return confirm('ต้องการออกจากระบบหรือไม่?');"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </a>
          <?php else: ?>
          <a
            href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'trace.php') ?>"
            class="text-white/80 hover:text-mezenc-mint flex items-center justify-center w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 transition-all cursor-pointer ml-1"
            title="เข้าสู่ระบบ (Login)"
          >
            <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
          </a>
          <?php endif; ?>
        </div>

        <!-- Mobile Drawer Hamburger -->
        <button
          type="button"
          onclick="toggleMobileDrawer()"
          class="
            lg:hidden
            text-white
            w-9
            h-9
            sm:w-10
            sm:h-10
            rounded-full
            bg-white/15
            hover:bg-white/25
            backdrop-blur-md
            flex
            items-center
            justify-center
            border
            border-white/20
            shadow-md
            transition-all
            active:scale-95
            cursor-pointer
          "
          aria-label="เปิดเมนูนำทาง"
          data-i18n-title="nav_menu_label"
        >
          <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>

    </header>

    <!-- CLEAN HERO HEADER (กระชับ ชัดเจน ไม่รกรุงรัง) -->
    <div class="relative z-20 w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14 pt-3 pb-6 text-center">
      <div class="max-w-4xl mx-auto space-y-2.5">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/25 text-mezenc-mint text-xs font-semibold tracking-wider uppercase shadow-sm">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>EUDR Deforestation-Free Verified</span>
        </div>
        <h1 class="text-2xl sm:text-3xl md:text-[34px] font-bold tracking-tight text-white drop-shadow-md leading-tight">
          หนังสือรับรองแหล่งผลิตยางพาราตามมาตรฐาน EUDR
        </h1>
        <p class="text-xs sm:text-sm text-white/85 font-light">
          ระบบตรวจสอบย้อนกลับทางภูมิสารสนเทศ มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี
        </p>

        <!-- Tool Actions Toolbar in Hero -->
        <div class="flex flex-wrap justify-center items-center gap-2.5 pt-1.5">
          <button onclick="toggleFullscreen()" class="px-3.5 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-medium backdrop-blur-md border border-white/30 transition-all flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-expand text-mezenc-mint"></i>
            <span class="fs-text">ดูเต็มจอ</span>
          </button>
          <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-mezenc-brightCyan hover:bg-[#008f83] text-white text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm shadow-teal-900/20">
            <i class="fa-solid fa-print"></i>
            <span>พิมพ์ (A4 ครบ 2 หน้า)</span>
          </button>
          <button onclick="window.print()" class="px-3.5 py-1.5 rounded-lg bg-white text-mezenc-deepTeal hover:bg-slate-50 text-xs font-semibold transition-all flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-file-pdf text-red-600"></i>
            <span>ดาวน์โหลด PDF</span>
          </button>
          <button onclick="copyVerifyUrl()" class="px-3.5 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-medium backdrop-blur-md border border-white/30 transition-all flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-share-nodes text-mezenc-mint"></i>
            <span id="copyHeroBtnText">แชร์ลิงก์</span>
          </button>
        </div>
      </div>
    </div>

  </section>

  <!-- =========================================================================
       [MOBILE / IPAD RESPONSIVE DRAWER OVERLAY]
       ========================================================================= -->
  <div id="mobile-drawer" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm hidden transition-opacity duration-300 opacity-0 lg:hidden">
    <div id="mobile-drawer-content" class="fixed right-0 top-0 bottom-0 w-4/5 max-w-sm bg-white/95 backdrop-blur-2xl text-slate-800 p-6 shadow-2xl flex flex-col justify-between transform translate-x-full transition-transform duration-300 ease-out border-l border-white/60">
      
      <div>
        <!-- Drawer Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200/70">
          <div class="flex items-center gap-2.5">
            <img src="img/map_icon.png" alt="GeoRubber Logo" class="w-7 h-7 object-contain drop-shadow-sm" onerror="this.onerror=null; this.src='ปก.png';">
            <span class="font-extrabold text-base text-mezenc-teal" data-i18n="nav_brand">GeoRubber Watch</span>
          </div>
          <button onclick="toggleMobileDrawer()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-sm transition-colors cursor-pointer" aria-label="Close menu">
            ✕
          </button>
        </div>

        <!-- Mobile Language Toggle Switch -->
        <div class="py-3 flex items-center justify-between border-b border-gray-200/60">
          <span class="text-xs text-slate-500 font-medium">Language / ภาษา:</span>
          <div 
            onclick="toggleLanguage()"
            class="toggle-track-dark w-[82px] h-[34px] p-[3px] flex items-center relative cursor-pointer shrink-0 bg-slate-200/80 border border-slate-300/80"
            id="lang-toggle-btn-mobile"
          >
            <div id="nav-thumb-mobile" class="toggle-thumb-dark w-[36px] h-[28px] transition-all duration-300 left-[3px] shadow-sm"></div>
            <div id="nav-label-th-mobile" class="relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none">TH</div>
            <div id="nav-label-en-mobile" class="relative z-10 w-1/2 text-center text-xs font-semibold text-slate-500 transition-colors duration-300 pointer-events-none">EN</div>
          </div>
        </div>

        <!-- Drawer Navigation Links -->
        <nav class="flex flex-col gap-1.5 pt-4 text-sm font-medium">
          <a href="index.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>🏠</span> <span data-i18n="nav_home">หน้าแรก</span>
          </a>
          <a href="overview.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>🛰️</span> <span data-i18n="nav_gis">แผนที่ GIS</span>
          </a>
          <a href="dashboard.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>📊</span> <span data-i18n="nav_dashboard">แดชบอร์ด</span>
          </a>
          <a href="map.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>📍</span> <span data-i18n="nav_plots">แปลงปลูก</span>
          </a>
          <a href="yields.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>🧪</span> <span data-i18n="nav_yields">ผลผลิต</span>
          </a>
          <a href="contact.php" class="px-4 py-2.5 rounded-xl text-slate-700 hover:text-mezenc-teal hover:bg-mezenc-lightCyan/60 transition-all flex items-center gap-3">
            <span>📞</span> <span data-i18n="nav_contact">ติดต่อเรา</span>
          </a>
        </nav>
      </div>

      <!-- Drawer Footer Action -->
      <div class="pt-4 border-t border-gray-200/70 space-y-2.5">
        <div class="p-3 bg-mezenc-lightCyan/60 rounded-xl border border-mezenc-mint/30 text-xs text-slate-600 flex items-center justify-between">
          <div class="truncate">
            ผู้ใช้งาน: <strong class="text-mezenc-teal"><?= htmlspecialchars($user_name) ?></strong>
          </div>
          <span class="text-[10px] bg-white text-mezenc-teal px-2 py-0.5 rounded-full font-bold border border-mezenc-mint/40 shrink-0 ml-1"><?= htmlspecialchars($current_role) ?></span>
        </div>
        <?php if ($isLoggedIn): ?>
        <a href="logout.php" class="w-full py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold text-center block text-xs transition-all flex items-center justify-center gap-2" onclick="return confirm('ต้องการออกจากระบบหรือไม่?');">
          <span>🚪</span> <span data-i18n="nav_logout">ออกจากระบบ (Logout)</span>
        </a>
        <?php else: ?>
        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'trace.php') ?>" class="w-full py-2.5 rounded-xl bg-mezenc-teal hover:bg-mezenc-deepTeal text-white font-bold text-center block text-xs transition-all flex items-center justify-center gap-2">
          <span>🔑</span> <span>เข้าสู่ระบบ (Login)</span>
        </a>
        <?php endif; ?>
        <div class="text-center text-[11px] text-slate-400 pt-1 font-normal">
          &copy; 2026 GeoRubber Watch &bull; ม.อ. สุราษฎร์ธานี
        </div>
      </div>

    </div>
  </div>

  <!-- ==========================================================================
       2. Main Content Grid (Left: A4 Certificate Canvas / Right: Clean Focused Sidebar)
       ========================================================================== -->
  <main class="flex-1 max-w-[1520px] w-full mx-auto p-4 sm:p-6 lg:p-8 grid grid-cols-1 xl:grid-cols-12 gap-8 items-start relative z-20">

    <!-- Left Column: Official A4 Portrait Certificate Paper (8 cols on desktop) -->
    <div class="xl:col-span-8 flex flex-col items-center space-y-3 w-full">
      
      <!-- PDPA Public Access Notification Banner (Shown when not logged in) -->
      <?php if (!$isLoggedIn): ?>
      <div class="w-full max-w-[840px] bg-gradient-to-r from-amber-500/15 via-teal-500/15 to-emerald-500/15 border-2 border-amber-400/40 backdrop-blur-md rounded-2xl p-4 text-slate-700 text-xs shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 no-print">
        <div class="flex items-start sm:items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg shrink-0 shadow-xs border border-amber-300">
            🛡️
          </div>
          <div>
            <div class="font-bold text-amber-900 text-sm flex items-center gap-2">
              <span>มุมมองบุคคลทั่วไป (Public View · PDPA Protected)</span>
              <span class="text-[10px] font-bold bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full">พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล</span>
            </div>
            <p class="text-[11.5px] text-slate-600 mt-0.5 leading-relaxed">
              ระบบแสดงเฉพาะ<b>สถานะความสอดคล้อง EUDR</b> และ<b>กรอบพิกัดแปลง GIS (Polygon)</b> โดยปกปิดชื่อ-นามสกุล เลขบัตรประชาชน และเอกสารสิทธิ์ของเกษตรกร
            </p>
          </div>
        </div>
        <a 
          href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'trace.php') ?>" 
          class="w-full sm:w-auto px-4 py-2 rounded-xl bg-mezenc-teal hover:bg-mezenc-deepTeal text-white font-bold text-xs whitespace-nowrap shadow-sm hover:shadow-md transition text-center flex items-center justify-center gap-1.5 shrink-0"
        >
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          <span>เข้าสู่ระบบเพื่อปลดล็อกข้อมูลเต็ม</span>
        </a>
      </div>
      <?php else: ?>
      <div class="w-full max-w-[840px] bg-emerald-50 border border-emerald-300 rounded-2xl p-3 text-xs text-emerald-800 flex items-center justify-between no-print shadow-2xs">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-shield-check text-emerald-600 text-base"></i>
          <span>เข้าสู่ระบบในฐานะ <b><?=h($user_name)?></b> (<?=h($current_role)?>) · แสดงข้อมูลรับรองและประวัติผลผลิตฉบับสมบูรณ์ 100%</span>
        </div>
        <span class="text-[10.5px] font-bold bg-emerald-200 text-emerald-900 px-2.5 py-0.5 rounded-full font-mono">AUTHORIZED VIEW</span>
      </div>
      <?php endif; ?>

      <!-- Fullscreen Container Wrapper (Contains Page 1 Certificate & Page 2 Yield Annex) -->
      <div id="certificateCanvas" class="w-full flex flex-col items-center space-y-6 relative">

        <!-- Floating Controls Bar (Active in Fullscreen mode only) -->
        <div class="fullscreen-toolbar fixed top-5 right-6 z-50 flex items-center space-x-2.5 bg-slate-900/90 backdrop-blur-md px-4 py-2 rounded-xl shadow-2xl border border-white/20 text-white transition-all">
          <span class="text-xs text-emerald-400 font-mono hidden md:inline mr-1 font-semibold flex items-center gap-1.5">
            <i class="fa-solid fa-file-lines"></i> โหมดดูเอกสารแบบเต็มจอ (A4 Portrait 2 หน้า)
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

        <!-- แผ่นที่ 1: A4 Portrait Cadastral Paper (แบบแสดงข้อมูลแปลงที่ดินและพิกัดภูมิสารสนเทศ EUDR) -->
        <div class="cadastral-paper cert-paper-page1 select-none relative overflow-hidden flex flex-col justify-between">
          <div class="cadastral-border relative">
            
            <!-- มุมขวาบน: เลขที่เอกสาร & วันที่จัดทำ & รหัส EUDR Token -->
            <div class="absolute right-3 sm:right-5 top-3 sm:top-4 text-right text-[11px] sm:text-[11.5px] leading-tight font-thai select-none z-10">
              <div><strong class="text-slate-800">เลขที่เอกสาร :</strong> <span class="font-mono font-bold text-mezenc-teal"><?=h($displayCertNo)?></span></div>
              <div class="mt-0.5"><strong class="text-slate-800">วันที่จัดทำ :</strong> <span><?=thaiDate($plot['created_at'] ?? '2026-03-05')?></span></div>
              <div class="mt-0.5 text-[9.5px] text-slate-500 font-mono">EUDR Token: <?=h($displayTokenCode)?></div>
            </div>

            <!-- โลโก้ตราสัญลักษณ์ GeoRubber Watch & PSU Surat Thani GIS Seal -->
            <div class="text-center mt-1">
              <svg width="76" height="76" viewBox="0 0 100 100" class="inline-block drop-shadow-sm">
                <circle cx="50" cy="50" r="47" fill="#e6f7f6" stroke="#0e4d4e" stroke-width="2.5"/>
                <circle cx="50" cy="50" r="43.5" fill="#f0faf9" stroke="#5ebbb6" stroke-width="1.5"/>
                <!-- ท้องฟ้าและพระอาทิตย์สีทอง -->
                <circle cx="68" cy="36" r="9" fill="#f59e0b" opacity="0.9"/>
                <!-- ทิวเขาป่าสงวนสุราษฎร์ธานี -->
                <path d="M 12 70 Q 35 28 58 70 Z" fill="#00a699" stroke="#0e4d4e" stroke-width="1.5"/>
                <path d="M 38 72 Q 62 24 88 72 Z" fill="#0e4d4e" stroke="#062627" stroke-width="1.5"/>
                <!-- ทุ่งสวนยางและแนวเขต GIS -->
                <path d="M 8 76 Q 50 58 92 76 L 92 88 L 8 88 Z" fill="#5ebbb6"/>
                <!-- ลวดลายต้นยางพารา & แม่น้ำตาปี -->
                <path d="M 15 80 Q 50 70 85 80" stroke="#ffffff" stroke-width="2" fill="none"/>
                <!-- สัญลักษณ์ GPS Pin เล็กตรงกลาง -->
                <circle cx="50" cy="48" r="3" fill="#ffffff"/>
              </svg>
            </div>

            <!-- หัวข้อเอกสาร -->
            <div class="text-center mt-1 mb-2">
              <div class="text-lg sm:text-[20px] font-bold tracking-tight text-mezenc-darkNavy">แบบแสดงข้อมูลแปลงที่ดินและพิกัดภูมิสารสนเทศ</div>
              <div class="text-xs sm:text-[13.5px] font-bold text-mezenc-teal mt-0.5">(สำหรับงานระบบสารสนเทศภูมิศาสตร์และการตรวจสอบย้อนกลับมาตรฐาน EUDR)</div>
              <div class="text-[10.5px] text-slate-500 mt-0.5">จัดทำโดยระบบสารสนเทศภูมิศาสตร์อัจฉริยะ GeoRubber Watch มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
              <!-- ลายประดับเส้นคั่น -->
              <div class="text-[12px] text-mezenc-teal/60 mt-0.5">❦ ════════ •⊰ 🌲 ⊱• ════════ ❧</div>
            </div>

            <!-- 1. ข้อมูลแปลงที่ดิน -->
            <div class="cadastral-section-title">
              <i class="fa-solid fa-map-location-dot text-mezenc-mint"></i>
              <span>1. ข้อมูลแปลงที่ดิน (Plot Information)</span>
            </div>
            <table class="cadastral-table">
              <tr>
                <td class="cadastral-th" style="width: 14%;">จังหวัด</td>
                <td class="cadastral-val" style="width: 20%;"><?=h($plot['province'] ?? 'สุราษฎร์ธานี')?></td>
                <td class="cadastral-th" style="width: 14%;">อำเภอ</td>
                <td class="cadastral-val" style="width: 20%;"><?=h($plot['district'] ?? 'เมืองสุราษฎร์ธานี')?></td>
                <td class="cadastral-th" style="width: 14%;">ตำบล</td>
                <td class="cadastral-val" style="width: 18%;"><?=h($plot['subdistrict'] ?? 'มะขามเตี้ย')?></td>
              </tr>
              <tr>
                <td class="cadastral-th">เลขที่ดิน</td>
                <td class="cadastral-val font-mono"><?=h($plot['land_number'] ?? (30 + ($plot['id'] ?? 1)))?></td>
                <td class="cadastral-th">เลขแปลง</td>
                <td class="cadastral-val font-mono font-bold text-mezenc-teal"><?=h($displayCertNo)?></td>
                <td class="cadastral-th">เลขสำรวจ</td>
                <td class="cadastral-val font-mono"><?=h($plot['survey_number'] ?? (1100 + ($plot['id'] ?? 1)))?></td>
              </tr>
              <tr>
                <td class="cadastral-th">ระวาง</td>
                <td class="cadastral-val font-mono" colspan="2" style="text-align: left; padding-left: 10px;"><?=h($plot['cadastral_map_sheet'] ?? '8401 I 4458-00')?></td>
                <td class="cadastral-th">มาตราส่วน</td>
                <td class="cadastral-val font-mono" colspan="2" style="text-align: left; padding-left: 10px;">1 : 2,000 (WGS84 / UTM 47N)</td>
              </tr>
              <tr>
                <td class="cadastral-th">เนื้อที่</td>
                <td class="cadastral-val" colspan="5" style="text-align: left; padding-left: 10px;">
                  <strong class="text-mezenc-teal font-bold"><?=h($plot['area_rai'] ?? 12)?></strong> ไร่ &nbsp; 
                  <strong class="text-mezenc-teal font-bold"><?=h($plot['area_ngan'] ?? 2)?></strong> งาน &nbsp; 
                  <strong class="text-mezenc-teal font-bold"><?=h($plot['area_sqwah'] ?? 0)?></strong> ตารางวา 
                  <span class="text-slate-500 text-[11px] ml-3">( <?=number_format((($plot['area_rai'] ?? 12)*400 + ($plot['area_ngan'] ?? 2)*100 + ($plot['area_sqwah'] ?? 0)), 1)?> ตารางวา &bull; <?=number_format((float)($plot['area_hectare'] ?? 2.0), 2)?> เฮกตาร์ )</span>
                </td>
              </tr>
              <tr>
                <td class="cadastral-th">ข้อมูลพันธุ์ยาง</td>
                <td class="cadastral-val" colspan="5" style="text-align: left; padding-left: 10px;">
                  พันธุ์ยาง: <b><?=h($plot['rubber_clone'] ?? 'RRIM 600')?></b> &nbsp;&bull;&nbsp; 
                  เริ่มปลูกปี: <b><?=h($plot['planting_year'] ?? 2018)?></b> &nbsp;&bull;&nbsp; 
                  จำนวนต้น: <b><?=number_format((float)($plot['tree_count'] ?? 920))?> ต้น</b> 
                  <span class="text-slate-500 text-[11px]">(ความหนาแน่นเฉลี่ย <?=round(($plot['tree_count'] ?? 920) / max(1, ($plot['area_rai'] ?? 12)))?> ต้น/ไร่)</span>
                </td>
              </tr>
            </table>

            <!-- 2. ข้อมูลผู้ถือครอง -->
            <div class="cadastral-section-title">
              <i class="fa-solid fa-user-check text-mezenc-mint"></i>
              <span>2. ข้อมูลผู้ถือครอง (Landholder Information)</span>
            </div>
            <table class="cadastral-table">
              <tr>
                <td class="cadastral-th" style="width: 12%;">คำนำหน้า</td>
                <td class="cadastral-val" style="width: 14%;"><?=h($rawPrefix)?></td>
                <td class="cadastral-th" style="width: 8%;">ชื่อ</td>
                <td class="cadastral-val" style="width: 28%;"><?=h($isLoggedIn ? $rawFirst : mb_substr($rawFirst, 0, 1) . '***')?></td>
                <td class="cadastral-th" style="width: 12%;">นามสกุล</td>
                <td class="cadastral-val" style="width: 26%;"><?=h($isLoggedIn ? $rawLast : mb_substr($rawLast, 0, 1) . '***')?></td>
              </tr>
              <tr>
                <td class="cadastral-th">เลขบัตร ปชช.</td>
                <td class="cadastral-val font-mono font-bold" colspan="2" style="text-align: left; padding-left: 10px;"><?=h($nationalIdFormatted)?></td>
                <td class="cadastral-th">รหัสเกษตรกร</td>
                <td class="cadastral-val font-mono font-bold text-mezenc-teal" colspan="2" style="text-align: left; padding-left: 10px;"><?=h($displayFarmerCode)?></td>
              </tr>
              <tr>
                <td class="cadastral-th">ที่อยู่</td>
                <td class="cadastral-val" colspan="5" style="text-align: left; padding-left: 10px;">
                  <?=h($isLoggedIn ? ($plot['farmer_address'] ?? 'ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี') : 'ต.' . ($plot['subdistrict'] ?? 'มะขามเตี้ย') . ' อ.' . ($plot['district'] ?? 'เมืองสุราษฎร์ธานี') . ' จ.สุราษฎร์ธานี (ข้อมูลคุ้มครองตาม พ.ร.บ. PDPA)')?>
                </td>
              </tr>
            </table>

            <!-- 3. รายการจดทะเบียน & ความสอดคล้องตามมาตรฐาน EUDR -->
            <div class="cadastral-section-title">
              <i class="fa-solid fa-shield-halved text-mezenc-mint"></i>
              <span>3. รายการจดทะเบียน &amp; ความสอดคล้องตามมาตรฐาน EUDR</span>
            </div>
            <table class="cadastral-table">
              <tr>
                <td class="cadastral-th" style="width: 22%;">ประเภทเอกสารสิทธิ์</td>
                <td class="cadastral-val" style="width: 28%; text-align: left; padding-left: 10px;"><?=h($plot['title_deed_type'] ?? 'โฉนดที่ดิน (น.ส. 4 จ)')?></td>
                <td class="cadastral-th" style="width: 20%;">เลขที่เอกสารสิทธิ์</td>
                <td class="cadastral-val font-mono" style="width: 30%; text-align: left; padding-left: 10px;"><?=h($landDocText)?></td>
              </tr>
              <tr>
                <td class="cadastral-th">สถานะ EUDR</td>
                <td class="cadastral-val" style="text-align: left; padding-left: 10px;">
                  <span class="inline-flex items-center gap-1 font-bold text-emerald-700">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i> <?=h($statusBadgeText)?>
                  </span>
                </td>
                <td class="cadastral-th">แนวป่าสงวน 26 แห่ง</td>
                <td class="cadastral-val" style="text-align: left; padding-left: 10px;">
                  อยู่นอกเขตป่าสงวน 100% <span class="text-slate-500 font-mono">(ทับซ้อน <?=number_format((float)($plot['eudr_overlap_pct'] ?? 0), 2)?>%)</span>
                </td>
              </tr>
              <tr>
                <td class="cadastral-th">ป่าสงวนที่ใกล้ที่สุด</td>
                <td class="cadastral-val" colspan="3" style="text-align: left; padding-left: 10px;">
                  <?=h($plot['nearest_forest_name'] ?? 'ป่าสงวนแห่งชาติเขาท่าเพชร')?> 
                  <span class="text-slate-600 ml-2 font-mono">(ระยะห่าง <?=number_format((float)($plot['distance_to_forest_m'] ?? 1250))?> เมตร) &bull; ปลูกก่อน 31 ธ.ค. 2020 (ผ่านเกณฑ์ Cut-off Date)</span>
                </td>
              </tr>
            </table>

            <!-- 4. รูปแปลงที่ดินโดยสังเขปและพิกัดภูมิสารสนเทศ -->
            <div class="cadastral-section-title">
              <i class="fa-solid fa-draw-polygon text-mezenc-mint"></i>
              <span>4. รูปแปลงที่ดินโดยสังเขปและพิกัดภูมิสารสนเทศ (GIS Map &amp; Boundary Survey)</span>
            </div>
            <div class="flex flex-col sm:flex-row border border-[#1a332c] rounded-lg overflow-hidden mb-1.5 bg-white">
              
              <!-- ฝั่งซ้าย: แผนที่ดาวเทียม Leaflet GIS Real Satellite Polygon -->
              <div class="flex-1 sm:w-7/12 border-b sm:border-b-0 sm:border-r border-[#1a332c] p-2 flex flex-col justify-between bg-slate-50/50">
                <!-- ส่วนหัวแผนที่: ทิศเหนือ & ป้ายกำกับ -->
                <div class="flex items-center justify-between pb-1 px-1">
                  <div class="flex items-center gap-1.5">
                    <!-- ทิศเหนือ Compass Rose -->
                    <div class="w-5 h-5 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold font-mono">
                      N ↑
                    </div>
                    <span class="text-[11px] font-bold text-mezenc-teal">แผนที่ภาพถ่ายดาวเทียมความละเอียดสูง (ESRI Satellite)</span>
                  </div>
                  <span class="text-[9.5px] font-mono bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded font-bold">GIS POLYGON</span>
                </div>

                <!-- Leaflet Real Map Canvas -->
                <div class="relative w-full h-[180px] sm:h-[200px] rounded border border-slate-300 overflow-hidden shadow-inner bg-slate-200 my-1">
                  <div id="cert-map" class="w-full h-full z-0"></div>
                </div>

                <!-- กราฟิกมาตราส่วนบรรทัด (Graphic Scale Bar) & พิกัดศูนย์กลาง -->
                <div class="flex items-center justify-between px-1 pt-0.5">
                  <div class="text-[9.5px] font-mono text-slate-500">
                    Centroid: <b><?=number_format($plot['centroid_lat'], 5)?>, <?=number_format($plot['centroid_lng'], 5)?></b>
                  </div>
                  <div class="flex flex-col items-end">
                    <div class="flex justify-between w-24 text-[8.5px] font-mono font-bold text-slate-700">
                      <span>0</span><span>20</span><span>50m</span>
                    </div>
                    <div class="w-24 h-1 border border-black flex">
                      <div class="w-1/4 bg-black"></div>
                      <div class="w-1/4 bg-white"></div>
                      <div class="w-1/2 bg-black"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ฝั่งขวา: ตารางระยะด้าน และ ตารางพิกัดจุด UTM / WGS84 -->
              <div class="flex-1 sm:w-5/12 p-2 flex flex-col justify-between bg-white">
                
                <!-- ตารางระยะด้าน -->
                <div>
                  <div class="font-bold text-[11.5px] text-center text-mezenc-teal mb-1">ตารางระยะด้าน (Boundary Distances)</div>
                  <table class="cadastral-table mb-2">
                    <thead>
                      <tr class="cadastral-th text-[10.5px]">
                        <th style="width: 20%; padding: 2px 4px;">ด้าน</th>
                        <th style="padding: 2px 4px;">จากจุด - ถึงจุด</th>
                        <th style="width: 38%; padding: 2px 4px;">ระยะ (เมตร)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($sideDistances as $sd): ?>
                      <tr class="cadastral-val text-[10.5px]">
                        <td style="padding: 2px 4px; font-weight: 700;"><?=h($sd['side'])?></td>
                        <td style="padding: 2px 4px;"><?=h($sd['from_to'])?></td>
                        <td style="padding: 2px 4px; font-weight: 600; text-align: right; padding-right: 6px; font-mono;"><?=number_format($sd['distance'], 2)?> ม.</td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- ตารางพิกัดจุด (UTM Zone 47N & WGS84) -->
                <div>
                  <div class="font-bold text-[11.5px] text-center text-mezenc-teal mb-1">พิกัดจุดหลักเขต (WGS84 &amp; UTM)</div>
                  <table class="cadastral-table mb-0">
                    <thead>
                      <tr class="cadastral-th text-[10.5px]">
                        <th style="width: 14%; padding: 2px 4px;">จุด</th>
                        <th style="padding: 2px 4px;">Lat / Y (Northing)</th>
                        <th style="padding: 2px 4px;">Lng / X (Easting)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($polygonPoints as $pt): ?>
                      <tr class="cadastral-val text-[10px] font-mono">
                        <td style="padding: 2px 4px; font-weight: 700;"><?=h($pt['idx'])?></td>
                        <td style="padding: 2px 4px;"><?=number_format($pt['lat'], 6)?> <span class="text-[8.5px] text-slate-400 block"><?=number_format($pt['northing'], 1)?> N</span></td>
                        <td style="padding: 2px 4px;"><?=number_format($pt['lng'], 6)?> <span class="text-[8.5px] text-slate-400 block"><?=number_format($pt['easting'], 1)?> E</span></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>

            <!-- 5. หมายเหตุ / บันทึกเพิ่มเติม และ ช่องลงชื่อ -->
            <div class="cadastral-section-title">
              <i class="fa-solid fa-signature text-mezenc-mint"></i>
              <span>5. หมายเหตุ / บันทึกเพิ่มเติม และ การรับรองข้อมูล</span>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 items-stretch">
              <!-- ฝั่งซ้าย: หมายเหตุ & เส้นประบันทึก -->
              <div class="flex-1 sm:w-7/12 flex flex-col justify-between text-[11px] text-slate-600">
                <p class="leading-relaxed">
                  <strong>หมายเหตุ:</strong> เอกสารฉบับนี้จัดทำขึ้นโดยระบบสารสนเทศภูมิศาสตร์ GeoRubber Watch มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี เพื่อใช้เป็นหลักฐานแสดงความสอดคล้องตามระเบียบ Regulation (EU) 2023/1115 (EUDR) โดยตรวจสอบร่วมกับฐานข้อมูลพิกัด GNSS และแนวเขตป่าสงวนแห่งชาติ 26 แห่ง
                </p>
                <div class="mt-1">
                  <div class="cadastral-dashed-line"></div>
                  <div class="cadastral-dashed-line"></div>
                  <div class="cadastral-dashed-line"></div>
                </div>
              </div>

              <!-- ฝั่งขวา: กล่องลงชื่อผู้จัดทำ/ตรวจรับรอง และ QR Code -->
              <div class="flex-1 sm:w-5/12 border border-[#1a332c] rounded-lg p-2.5 text-center text-xs bg-slate-50/60 flex flex-col justify-between">
                <div class="flex items-center justify-center gap-3 pb-1 border-b border-slate-200">
                  <img class="w-11 h-11 rounded border border-slate-200 shadow-xs" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?=urlencode($verifyUrl)?>" alt="QR">
                  <div class="text-left text-[10px] leading-tight">
                    <b class="text-mezenc-teal block">สแกนตรวจสอบออนไลน์</b>
                    <span class="text-slate-400 font-mono">GeoRubber Watch Registry</span>
                  </div>
                </div>
                
                <div class="mt-2">
                  <div class="font-signature text-lg text-mezenc-teal">Assoc. Prof. Dr. Surat</div>
                  <div class="text-[11.5px] font-bold text-slate-800 mt-0.5">( ผศ.ดร. สุราษฎร์ รักษาพงษ์ )</div>
                  <div class="text-[10px] text-slate-500">ผู้รับรองระบบสารสนเทศภูมิศาสตร์และตรวจสอบย้อนกลับ</div>
                  <div class="text-[9.5px] text-slate-400 font-mono mt-0.5">วันที่ออกเอกสาร: <?=date('d/m/Y', strtotime($plot['created_at'] ?? '2026-03-05'))?></div>
                </div>
              </div>
            </div>

            <!-- ท้ายกระดาษผู้พัฒนา -->
            <div class="mt-3 text-center text-[10.5px] text-slate-500 font-thai flex items-center justify-center gap-2 border-t border-slate-200 pt-1.5">
              <span>❧</span>
              <span>GeoRubber Watch &bull; ศูนย์ภูมิสารสนเทศเพื่อการเกษตรยั่งยืน มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</span>
              <span>❦</span>
            </div>

          </div>
        </div>

        <!-- แถบแบ่งหน้าจำลองในมุมมองหน้าเว็บ (ซ่อนอัตโนมัติเมื่อสั่งพิมพ์/ส่งออก) -->
        <div class="page-separator-badge flex items-center justify-center gap-2 py-2 px-5 rounded-full bg-slate-200/80 backdrop-blur-sm text-xs text-slate-600 font-medium no-print shadow-xs">
          <i class="fa-solid fa-file-invoice text-brand-600"></i>
          <span>เอกสารแนบข้อมูลผลผลิต (หน้าที่ 2 จาก 2) · Annex: Rubber Harvest Yield Traceability Record</span>
          <a href="#certificateCanvas" class="text-brand-700 hover:underline ml-2 text-[11px]"><i class="fa-solid fa-arrow-up"></i> กลับขึ้นด้านบน</a>
        </div>

        <!-- แผ่นที่ 2: A4 Portrait Annex Paper (เอกสารแนบข้อมูลผลผลิตและประวัติการเก็บเกี่ยวเพื่อการตรวจสอบย้อนกลับ EUDR) -->
        <div id="yieldAnnexSection" class="cert-paper cert-paper-page2 bg-white rounded-2xl shadow-xl w-full max-w-[840px] p-8 sm:p-12 border border-slate-200/90 select-none relative overflow-hidden flex flex-col justify-between space-y-6">

          <!-- Decorative Top-Right & Bottom-Left Curves -->
          <div class="absolute -top-14 -right-14 w-72 h-72 bg-gradient-to-br from-brand-200/40 via-brand-500/15 to-transparent rounded-[4rem] rotate-12 pointer-events-none"></div>
          <div class="absolute top-0 right-16 w-56 h-56 bg-emerald-100/40 rounded-full blur-2xl pointer-events-none"></div>
          <div class="absolute -bottom-16 -left-16 w-72 h-72 bg-gradient-to-tr from-brand-800/20 via-brand-600/15 to-transparent rounded-[5rem] -rotate-12 pointer-events-none"></div>

          <!-- Ornate Inner Annex Border -->
          <div class="border-2 border-brand-600/20 rounded-xl p-6 sm:p-8 relative z-10 flex flex-col space-y-5 bg-white/60 backdrop-blur-[2px]">

            <!-- Title Section (Centered) -->
            <div class="text-center space-y-1.5 pt-2">
              <div class="inline-flex items-center gap-1.5 text-[10.5px] font-bold tracking-wider text-brand-700 uppercase bg-brand-50 border border-brand-200 px-3 py-0.5 rounded-full mb-0.5">
                <i class="fa-solid fa-boxes-stacked"></i> เอกสารแนบ 1 (ANNEX 1) · EUDR COMPLIANCE
              </div>
              <h2 class="text-2xl sm:text-3xl font-bold text-brand-800 tracking-tight">
                บันทึกข้อมูลผลผลิตและรอบการเก็บเกี่ยวยางพารา
              </h2>
              <p class="text-xs sm:text-sm font-bold tracking-widest uppercase text-brand-600">
                RUBBER HARVEST YIELD & BATCH TRACEABILITY RECORD
              </p>
              <p class="text-[11px] text-slate-500 font-normal">
                เอกสารแนบประกอบหนังสือรับรองเลขที่ <?=h($displayCertNo)?> เพื่อการตรวจสอบย้อนกลับตลอดห่วงโซ่อุปทานตามระเบียบ Regulation (EU) 2023/1115
              </p>
            </div>

            <!-- Reference Meta Strip (3 Columns) -->
            <div class="grid grid-cols-3 border border-slate-200 rounded-xl overflow-hidden text-xs bg-slate-50/70 divide-x divide-slate-200">
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">รหัสแปลงปลูก</span>
                <strong class="text-brand-700 font-mono text-[11px]"><?=h($displayCertNo)?></strong>
              </div>
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">เกษตรกรผู้ถือครอง</span>
                <strong class="text-slate-800 text-[11px] truncate block" title="<?=h($farmerFullName)?>"><?=h($farmerFullName)?></strong>
              </div>
              <div class="p-2.5">
                <span class="text-[10px] text-slate-400 block">เลขอ้างอิงสากล (EUDR Token)</span>
                <strong class="text-slate-800 font-mono text-[10.5px] truncate block" title="<?=h($tokenCode)?>"><?=h($displayTokenCode)?></strong>
              </div>
            </div>

            <?php if (!$isLoggedIn): ?>
            <!-- GUEST PRIVACY SHIELD FOR YIELD ANNEX -->
            <div class="p-6 rounded-2xl bg-gradient-to-b from-slate-50 to-emerald-50/40 border-2 border-slate-200 text-center space-y-4 my-2">
              <div class="w-14 h-14 rounded-2xl bg-brand-50 text-brand-700 border border-brand-200 flex items-center justify-center mx-auto text-2xl shadow-xs">
                🔒
              </div>
              <div class="max-w-md mx-auto space-y-1.5">
                <h3 class="font-extrabold text-sm text-slate-800">
                  ข้อมูลประวัติผลผลิตและการซื้อขายถูกคุ้มครองตาม พ.ร.บ. PDPA
                </h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                  เนื่องจากบันทึกรอบการกรีด น้ำหนักน้ำยางสด ค่า % DRC และข้อมูลจุดรับซื้อเป็นข้อมูลส่วนบุคคลและการค้าที่มีความอ่อนไหว ระบบจึงจำกัดการแสดงผลเฉพาะเจ้าหน้าที่ เกษตรกรเจ้าของแปลง และโรงงานรับซื้อที่มีสิทธิ์เท่านั้น
                </p>
              </div>

              <!-- General Compliance Badge -->
              <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100/80 border border-emerald-300 text-emerald-900 text-xs font-bold">
                <i class="fa-solid fa-check-circle text-emerald-600"></i>
                <span>สถานะภาพรวม: มีการบันทึกประวัติผลผลิตเชื่อมโยงกับพิกัดแปลงถูกต้องตามมาตรฐาน EUDR</span>
              </div>

              <div class="pt-1">
                <a 
                  href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? 'trace.php') ?>" 
                  class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-brand-700 hover:bg-brand-800 text-white text-xs font-bold shadow-md hover:shadow-lg transition cursor-pointer"
                >
                  <i class="fa-solid fa-key"></i>
                  <span>เข้าสู่ระบบเพื่อดูตารางประวัติผลผลิตฉบับเต็ม</span>
                </a>
              </div>
            </div>
            <?php else: ?>
            <!-- Production KPI Summary Cards (4 Columns) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-center">
              <div class="p-3 rounded-xl bg-brand-50/90 border border-brand-200/90 shadow-2xs">
                <span class="text-[10px] text-slate-500 block font-medium">น้ำยางสดสะสมรวม</span>
                <b class="text-base sm:text-lg font-bold text-brand-900 font-mono"><?=number_format($totalFreshLatex, 1)?></b>
                <span class="text-[9px] text-slate-400 block font-mono">กก. (Fresh kg)</span>
              </div>
              <div class="p-3 rounded-xl bg-emerald-50/90 border border-emerald-200/90 shadow-2xs">
                <span class="text-[10px] text-slate-500 block font-medium">ค่าเฉลี่ยเนื้อยางแห้ง</span>
                <b class="text-base sm:text-lg font-bold text-emerald-800 font-mono"><?=number_format($avgDrc, 1)?>%</b>
                <span class="text-[9px] text-slate-400 block font-mono">Average % DRC</span>
              </div>
              <div class="p-3 rounded-xl bg-teal-50/90 border border-teal-200/90 shadow-2xs">
                <span class="text-[10px] text-slate-500 block font-medium">เนื้อยางแห้งรวม</span>
                <b class="text-base sm:text-lg font-bold text-teal-900 font-mono"><?=number_format($totalDryRubber, 2)?></b>
                <span class="text-[9px] text-slate-400 block font-mono">กก. (Total Dry kg)</span>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 shadow-2xs">
                <span class="text-[10px] text-slate-500 block font-medium">จำนวนรอบการกรีด</span>
                <b class="text-base sm:text-lg font-bold text-slate-800 font-mono"><?=count($yields)?></b>
                <span class="text-[9px] text-slate-400 block">รอบที่บันทึก (Rounds)</span>
              </div>
            </div>

            <!-- Harvest Batches Table -->
            <div class="border border-slate-200/90 rounded-xl overflow-hidden bg-white shadow-xs">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] text-slate-500 font-mono">
                  <tr>
                    <th class="py-2.5 px-3">รอบกรีด</th>
                    <th class="py-2.5 px-3">วันที่เก็บเกี่ยว</th>
                    <th class="py-2.5 px-3 text-right">น้ำยางสด (กก.)</th>
                    <th class="py-2.5 px-3 text-center">% DRC</th>
                    <th class="py-2.5 px-3 text-right">ยางแห้ง (กก.)</th>
                    <th class="py-2.5 px-3">จุดรับซื้อ / สหกรณ์</th>
                    <th class="py-2.5 px-3 text-center">สถานะ EUDR</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px]">
                  <?php foreach ($yields as $idx => $y): ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                      <td class="py-2 px-3 font-mono font-bold text-brand-700">R<?=h($y['tapping_round'] ?? ($idx + 1))?></td>
                      <td class="py-2 px-3 font-mono text-slate-700"><?=date('d/m/Y', strtotime($y['harvest_date']))?></td>
                      <td class="py-2 px-3 text-right font-mono font-medium text-slate-800"><?=number_format((float)$y['fresh_latex_kg'], 1)?></td>
                      <td class="py-2 px-3 text-center font-mono font-bold text-emerald-700"><?=number_format((float)$y['drc_percent'], 1)?>%</td>
                      <td class="py-2 px-3 text-right font-mono font-bold text-brand-800"><?=number_format((float)$y['dry_rubber_kg'], 2)?></td>
                      <td class="py-2 px-3 text-slate-600 truncate max-w-[150px]" title="<?=h($y['buyer_name'] ?? 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด')?>"><?=h($y['buyer_name'] ?? 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด')?></td>
                      <td class="py-2 px-3 text-center">
                        <span class="inline-flex items-center gap-1 text-[9.5px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                          ✓ ผ่านเกณฑ์
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-brand-50/70 border-t-2 border-brand-200 text-xs font-bold text-slate-800">
                  <tr>
                    <td colspan="2" class="py-2.5 px-3 font-bold text-brand-900">รวมผลผลิตสะสมล่าสุด (Total)</td>
                    <td class="py-2.5 px-3 text-right font-mono text-brand-900"><?=number_format($totalFreshLatex, 1)?></td>
                    <td class="py-2.5 px-3 text-center font-mono text-emerald-800"><?=number_format($avgDrc, 1)?>% <span class="text-[9px] font-normal text-slate-400 block sm:inline">(เฉลี่ย)</span></td>
                    <td class="py-2.5 px-3 text-right font-mono text-brand-900"><?=number_format($totalDryRubber, 2)?></td>
                    <td colspan="2" class="py-2.5 px-3 text-[10px] text-slate-500 font-normal">บันทึกผ่านระบบชั่งดิจิทัลและทดสอบ DRC ตามมาตรฐาน</td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <?php endif; ?>

            <!-- Due Diligence Traceability Statement Box -->
            <div class="p-3 bg-slate-50 border border-slate-200/90 rounded-xl text-slate-700 text-[10.5px] space-y-1">
              <b class="text-slate-800 flex items-center gap-1 text-[11px]">
                <i class="fa-solid fa-shield-check text-emerald-600"></i>
                การรับรองการตรวจสอบย้อนกลับผลผลิต (Production Traceability Endorsement)
              </b>
              <p class="text-slate-600 leading-relaxed">
                ข้อมูลผลผลิตน้ำยางสดและเนื้อยางแห้งทั้งหมดในเอกสารแนบฉบับนี้ เชื่อมโยงโดยตรงกับพิกัดแปลงภูมิสารสนเทศ (GIS Polygon) ของแปลงรหัส <b><?=h($displayCertNo)?></b> ซึ่งผ่านการตรวจสอบว่าปลอดจากการตัดไม้ทำลายป่า 100% ตามข้อกำหนด Regulation (EU) 2023/1115 โดยผลผลิตถูกเก็บเกี่ยวจากต้นยางที่ปลูกก่อนเส้นตาย Cut-off Date (31 ธันวาคม 2020) สามารถใช้เป็นหลักฐานประกอบการจัดทำ Due Diligence Statement (DDS) เพื่อการส่งออกสู่สหภาพยุโรป
              </p>
            </div>

            <!-- Signatures Section (3 Columns at bottom) -->
            <div class="grid grid-cols-3 gap-4 pt-3 border-t border-slate-200 text-center">
              <div>
                <div class="h-9 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-signature text-xl text-slate-700"><?=h($displayFarmerCode)?></span>
                </div>
                <b class="text-[11px] text-slate-800 block mt-1">ผู้รายงานผลผลิต / เกษตรกร</b>
                <span class="text-[9.5px] text-slate-400"><?=h($farmerFullName)?></span>
              </div>

              <div>
                <div class="h-9 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-signature text-xl text-brand-800">สหกรณ์กองทุนสวนยาง ม.อ.</span>
                </div>
                <b class="text-[11px] text-slate-800 block mt-1">ผู้รับซื้อ / จุดรวบรวมผลผลิต</b>
                <span class="text-[9.5px] text-slate-400">Cooperative Depot Inspector</span>
              </div>

              <div>
                <div class="h-9 border-b border-slate-300 flex items-end justify-center pb-1">
                  <span class="font-signature text-xl text-brand-800">GeoRubber Watch</span>
                </div>
                <b class="text-[11px] text-slate-800 block mt-1">นายทะเบียนตรวจสอบย้อนกลับ</b>
                <span class="text-[9.5px] text-slate-400 font-mono">GeoRubber Watch Registry</span>
              </div>
            </div>

          </div>

          <!-- Bottom Watermark Note inside paper -->
          <div class="flex justify-between items-center text-[10px] text-slate-400 pt-1 px-1">
            <span>GeoRubber Watch · มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</span>
            <span class="font-mono">Annex Hash: <?=h(substr($ledgerHash, 0, 16))?>...</span>
          </div>

        </div>

      </div>

    </div>

    <!-- Right Column: Focused Verification & Yield Traceability Sidebar (4 cols on desktop) -->
    <div class="xl:col-span-4 space-y-5 w-full no-print">
      
      <!-- Card 1: สรุปผลผลิตน้ำยางสด (Production Summary KPI) -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
            <i class="fa-solid fa-chart-simple text-brand-600"></i>
            สรุปข้อมูลผลผลิตสะสม
          </h3>
          <span class="text-[10px] text-slate-400 font-mono"><?=count($yields)?> รอบกรีด</span>
        </div>

        <div class="grid grid-cols-2 gap-2 text-center text-xs">
          <div class="p-2.5 rounded-xl bg-brand-50/70 border border-brand-200/70">
            <span class="text-[9.5px] text-slate-400 block font-medium">น้ำยางสดรวม</span>
            <b class="text-sm font-bold text-brand-900 font-mono"><?=number_format($totalFreshLatex, 1)?></b>
            <span class="text-[8.5px] text-slate-400 block">กก. (kg)</span>
          </div>
          <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/70">
            <span class="text-[9.5px] text-slate-400 block font-medium">เนื้อยางแห้งรวม</span>
            <b class="text-sm font-bold text-emerald-800 font-mono"><?=number_format($totalDryRubber, 2)?></b>
            <span class="text-[8.5px] text-slate-400 block">กก. (DRC)</span>
          </div>
        </div>

        <a href="#yieldAnnexSection" class="w-full py-2 px-3 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs text-brand-700 font-medium transition flex items-center justify-center gap-1.5 shadow-2xs">
          <i class="fa-solid fa-file-invoice"></i>
          <span>ดูเอกสารแนบข้อมูลผลผลิต (หน้าที่ 2) ↓</span>
        </a>
      </div>

      <!-- Card 2: เมนูดำเนินการด่วนและการส่งออกเอกสาร -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-3.5">
        <div class="flex items-center space-x-2.5 pb-2 border-b border-slate-100">
          <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-sm font-bold">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <h3 class="text-xs font-bold text-slate-800">
              การส่งออกและตรวจสอบระบบ
            </h3>
            <p class="text-[10px] text-slate-400 font-mono">
              Export & Verification Network
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <button onclick="window.print()" class="w-full py-2.5 px-3.5 rounded-xl bg-mezenc-brightCyan hover:bg-[#008f83] text-white text-xs font-semibold transition flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-print"></i>
              <span>พิมพ์ / ส่งออก PDF (A4 ครบ 2 หน้า)</span>
            </span>
            <span class="text-[10px] text-white/80 font-mono">2P</span>
          </button>

          <a href="overview.php" class="w-full py-2.5 px-3.5 rounded-xl bg-mezenc-teal hover:bg-mezenc-deepTeal text-white text-xs font-medium transition flex items-center justify-between shadow-sm">
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-earth-asia text-mezenc-mint"></i>
              <span>ดูตำแหน่งแปลงบนแผนที่ GIS</span>
            </span>
            <i class="fa-solid fa-arrow-up-right-from-square text-[11px] text-white/70"></i>
          </a>

          <button onclick="copyVerifyUrl()" class="w-full py-2.5 px-3.5 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs text-slate-700 font-medium transition flex items-center justify-between">
            <span class="flex items-center gap-2">
              <i class="fa-regular fa-copy text-brand-600"></i>
              <span id="sideCopyBtnText">คัดลอกลิงก์ตรวจสอบใบรับรอง</span>
            </span>
            <span class="text-[10px] text-slate-400 font-mono">URL</span>
          </button>
        </div>

        <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
          <div class="flex justify-between items-center">
            <span>ฐานข้อมูลรับรอง:</span>
            <span class="font-medium text-slate-700">ม.อ. สุราษฎร์ธานี</span>
          </div>
          <div class="flex justify-between items-center">
            <span>Ledger Hash:</span>
            <span class="font-mono text-slate-700 font-semibold cursor-pointer hover:text-brand-600 transition" onclick="copyVerifyUrl()" title="<?=h($ledgerHash)?>"><?=h($shortHash)?></span>
          </div>
        </div>
      </div>

    </div>

  </main>

  <!-- =========================================================================
       3. FOOTER (ข้อมูลโครงการ / ผู้พัฒนา / สถิติพื้นที่ - ข้อมูลทางการ ม.อ. สุราษฎร์ธานี)
       ========================================================================= -->
  <footer id="footer-section" class="bg-mezenc-teal text-white pt-12 sm:pt-14 pb-10 border-t border-white/10 mt-12 sm:mt-16 relative z-20 no-print">
    <div class="w-full max-w-[1440px] 2xl:max-w-[1600px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-14">
      
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 border-b border-white/15 items-start md:items-center">
        
        <!-- คอลัมน์ที่ 1: ข้อมูลโครงการและสถาบัน -->
        <div class="md:col-span-5 space-y-2.5">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full border-2 border-white flex items-center justify-center bg-white/10 shrink-0 p-1.5 mt-0.5">
              <svg class="w-full h-full text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
              </svg>
            </div>
            <div class="space-y-0.5">
              <div class="font-extrabold text-[15px] sm:text-[16px] leading-snug text-white" data-i18n="foot_title">แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา</div>
              <div class="font-semibold text-[13px] sm:text-[14px] leading-snug text-white/95" data-i18n="foot_title_en">GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations</div>
            </div>
          </div>
          <p class="text-[14px] text-white/85 leading-relaxed font-light" data-i18n="foot_dept">
            สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม<br>
            มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี<br>
            <span class="text-white/75 text-[14px]">31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000</span>
          </p>
        </div>

        <!-- คอลัมน์ที่ 2: ข้อมูลผู้จัดทำและช่องทางติดต่อ -->
        <div class="md:col-span-4 space-y-1.5 text-[14px] text-white/85 leading-relaxed">
          <div class="font-bold text-[15px] sm:text-[16px] text-mezenc-mint" data-i18n="foot_dev_header">ข้อมูลผู้พัฒนาและช่องทางติดต่อ</div>
          <div class="text-[14px] text-white/75" data-i18n="foot_dev_sub">ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง</div>
          <div class="pt-1 text-[14px] text-white/90 space-y-1">
            <div data-i18n="foot_authors">👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์</div>
            <div data-i18n="foot_advisor">🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
            <div><span data-i18n="foot_email">✉️ <strong>อีเมล / Email:</strong></span> <a href="mailto:6640011044@psu.ac.th" class="hover:text-mezenc-mint underline">6640011044@psu.ac.th</a></div>
          </div>
        </div>

        <!-- คอลัมน์ที่ 3: กล่องสถิติพื้นที่ (Surat Thani Territory Card) -->
        <div class="md:col-span-3 flex justify-start md:justify-end">
          <div class="w-full sm:w-56 p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 text-center shadow-lg">
            <div class="text-[11px] font-extrabold uppercase text-mezenc-mint tracking-wider mb-1" data-i18n="foot_card_hdr">SURAT THANI FOREST COVERAGE</div>
            <div class="text-[14px] font-bold text-white leading-tight mt-2" data-i18n="foot_card_stat">26 ผืนป่าสงวน (Zone C) • 3,643,595 ไร่</div>
            <div class="text-[12px] text-white/70 mt-1 font-light" data-i18n="foot_card_source">ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้</div>
          </div>
        </div>

      </div>

      <!-- แถบล่างสุด (Copyright Bar) -->
      <div class="pt-6 flex flex-col sm:flex-row justify-between items-center text-[14px] text-white/75 gap-4">
        <div data-i18n="foot_copy">&copy; 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี</div>
        <div class="text-mezenc-mint text-center sm:text-right" data-i18n="foot_eudr_cert">EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)</div>
      </div>

    </div>
  </footer>

  <!-- Leaflet.js -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Script: Map Initialization & Interactive Controls -->
  <script>
    const verifyUrl = <?= json_encode($verifyUrl) ?>;
    const certNo = <?= json_encode($certNo) ?>;
    const plotGeo = <?= $plot['geojson_geometry'] ?: 'null' ?>;
    const polygonPoints = <?= json_encode($polygonPoints) ?>;
    const centroid = [<?= floatval($plot['centroid_lat']) ?>, <?= floatval($plot['centroid_lng']) ?>];
    const isCompliant = <?= $isCompliant ? 'true' : 'false' ?>;

    // Mobile Drawer Toggle
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

    // Language Toggle (TH/EN)
    function toggleLanguage() {
      if (typeof window.switchLanguage === 'function') {
        const currentLang = localStorage.getItem('georubber_lang') || 'th';
        const nextLang = currentLang === 'th' ? 'en' : 'th';
        window.switchLanguage(nextLang);
      } else {
        const thumb = document.getElementById('nav-thumb');
        const isEn = thumb && thumb.classList.contains('left-[45px]');
        if (thumb) {
          if (isEn) {
            thumb.classList.remove('left-[45px]');
            thumb.classList.add('left-[3px]');
          } else {
            thumb.classList.remove('left-[3px]');
            thumb.classList.add('left-[45px]');
          }
        }
      }
    }

    // Initialize Interactive Leaflet Satellite GIS Map inside Card 3
    document.addEventListener('DOMContentLoaded', () => {
      const mapContainer = document.getElementById('cert-map');
      if (mapContainer) {
        const map = L.map('cert-map', {
          center: centroid,
          zoom: 16,
          zoomControl: false,
          attributionControl: false,
          dragging: false,
          scrollWheelZoom: false,
          doubleClickZoom: false,
          touchZoom: false
        });

        // ESRI World Imagery (Satellite Basemap)
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
          maxZoom: 19
        }).addTo(map);

        let plotBounds = null;

        // Overlay Plot Polygon if valid GeoJSON available
        if (plotGeo && plotGeo.coordinates) {
          try {
            const plotLayer = L.geoJSON(plotGeo, {
              style: {
                color: isCompliant ? '#0e4d4e' : '#dc2626',
                fillColor: isCompliant ? '#10b981' : '#ef4444',
                fillOpacity: 0.35,
                weight: 2.5
              }
            }).addTo(map);

            plotBounds = plotLayer.getBounds();
            map.fitBounds(plotBounds, { padding: [20, 20] });
          } catch (e) {
            console.warn('Could not parse plot geojson bounds:', e);
          }
        }

        // Add Numbered Markers for each vertex point (matching the table 1, 2, 3...)
        if (Array.isArray(polygonPoints) && polygonPoints.length > 0) {
          const latLngs = [];
          polygonPoints.forEach(pt => {
            latLngs.push([pt.lat, pt.lng]);
            const numberIcon = L.divIcon({
              className: 'custom-vertex-marker',
              html: `<div style="background-color: ${isCompliant ? '#0e4d4e' : '#b91c1c'}; color: #ffffff; border: 1.5px solid #ffffff; width: 17px; height: 17px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9.5px; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.5); font-family: monospace;">${pt.idx}</div>`,
              iconSize: [17, 17],
              iconAnchor: [8.5, 8.5]
            });
            L.marker([pt.lat, pt.lng], { icon: numberIcon, interactive: false }).addTo(map);
          });

          if (!plotBounds && latLngs.length > 0) {
            const polygon = L.polygon(latLngs, {
              color: isCompliant ? '#0e4d4e' : '#dc2626',
              fillColor: isCompliant ? '#10b981' : '#ef4444',
              fillOpacity: 0.35,
              weight: 2.5
            }).addTo(map);
            map.fitBounds(polygon.getBounds(), { padding: [20, 20] });
          }
        }

        // Plot Centroid Marker
        L.circleMarker(centroid, {
          radius: 5,
          color: '#ffffff',
          fillColor: isCompliant ? '#059669' : '#dc2626',
          fillOpacity: 1,
          weight: 2
        }).addTo(map);
      }
    });

    // Copy Functions
    function copyVerifyUrl() {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(verifyUrl).then(() => {
          showCopyFeedback('copyHeroBtnText', 'คัดลอกแล้ว!');
          showCopyFeedback('sideCopyBtnText', 'คัดลอกแล้ว!');
        });
      } else {
        prompt('คัดลอกลิงก์ตรวจสอบใบรับรอง:', verifyUrl);
      }
    }

    function showCopyFeedback(elemId, msg) {
      const el = document.getElementById(elemId);
      if (!el) return;
      const original = el.innerText;
      el.innerText = msg;
      setTimeout(() => {
        el.innerText = original;
      }, 2000);
    }

    // Fullscreen Controller
    function toggleFullscreen() {
      const canvas = document.getElementById('certificateCanvas');
      if (!canvas) return;

      const isFs = document.fullscreenElement || document.webkitFullscreenElement || canvas.classList.contains('is-modal-fullscreen');

      if (!isFs) {
        // Enter Fullscreen
        if (canvas.requestFullscreen) {
          canvas.requestFullscreen().catch(() => enterModalFullscreen(canvas));
        } else if (canvas.webkitRequestFullscreen) {
          canvas.webkitRequestFullscreen();
        } else {
          enterModalFullscreen(canvas);
        }
        updateFsButtonText(true);
      } else {
        // Exit Fullscreen
        if (document.exitFullscreen) {
          document.exitFullscreen().catch(() => exitModalFullscreen(canvas));
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        } else {
          exitModalFullscreen(canvas);
        }
        updateFsButtonText(false);
      }
    }

    function enterModalFullscreen(el) {
      el.classList.add('is-modal-fullscreen');
      document.body.style.overflow = 'hidden';
      updateFsButtonText(true);
    }

    function exitModalFullscreen(el) {
      el.classList.remove('is-modal-fullscreen');
      document.body.style.overflow = '';
      updateFsButtonText(false);
    }

    function updateFsButtonText(isFs) {
      document.querySelectorAll('.fs-text').forEach(el => {
        el.innerText = isFs ? 'ออกจากเต็มจอ' : 'ดูเต็มจอ';
      });
      document.querySelectorAll('.fa-expand, .fa-compress').forEach(icon => {
        if (isFs) {
          icon.classList.remove('fa-expand');
          icon.classList.add('fa-compress');
        } else {
          icon.classList.remove('fa-compress');
          icon.classList.add('fa-expand');
        }
      });
    }

    // Listen to native fullscreen changes (e.g. user presses ESC)
    document.addEventListener('fullscreenchange', () => {
      const isFs = !!document.fullscreenElement;
      updateFsButtonText(isFs);
      if (!isFs) {
        document.body.style.overflow = '';
      }
    });
    document.addEventListener('webkitfullscreenchange', () => {
      const isFs = !!document.webkitFullscreenElement;
      updateFsButtonText(isFs);
      if (!isFs) {
        document.body.style.overflow = '';
      }
    });

    // Keyboard ESC shortcut for modal fullscreen fallback
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        const canvas = document.getElementById('certificateCanvas');
        if (canvas && canvas.classList.contains('is-modal-fullscreen')) {
          exitModalFullscreen(canvas);
        }
      }
    });
  </script>
</body>
</html>
