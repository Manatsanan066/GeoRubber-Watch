<?php
/**
 * GeoRubber Watch - Public EUDR Traceability Passport & Due Diligence Verification
 * Designed with Clean Eco-Minimalist Scandinavian Nature Aesthetics
 */
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$token = $_GET['token'] ?? '';
$plot_code = $_GET['code'] ?? '';

$plot = null;
if (!empty($token) || !empty($plot_code)) {
    $sql = "
        SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
               f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
        FROM rubber_plots p
        JOIN farmers f ON f.id = p.farmer_id
        WHERE " . (!empty($token) ? "p.traceability_token = ?" : "p.plot_code = ?") . "
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([!empty($token) ? $token : $plot_code]);
    $plot = $stmt->fetch();
}

// Fallback to Plot 1 if accessed directly
if (!$plot) {
    $stmt = $pdo->query("
        SELECT p.*, f.farmer_code, f.prefix, f.first_name, f.last_name, f.phone as farmer_phone,
               f.id_card_num, f.address as farmer_address, f.subdistrict, f.district, f.province
        FROM rubber_plots p
        JOIN farmers f ON f.id = p.farmer_id
        ORDER BY p.id ASC LIMIT 1
    ");
    $plot = $stmt->fetch();
}

// Fetch harvest batch logs
$yieldStmt = $pdo->prepare("SELECT * FROM yield_logs WHERE plot_id = ? ORDER BY harvest_date DESC LIMIT 8");
$yieldStmt->execute([$plot['id']]);
$yields = $yieldStmt->fetchAll();

$isCompliant = ($plot['eudr_status'] === 'compliant');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EUDR Digital Traceability Passport - <?= htmlspecialchars($plot['plot_name']) ?></title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: var(--bg-main); padding-bottom: 3.5rem;">

  <!-- Simple Header for Public View -->
  <header style="background: rgba(255,255,255,0.92); backdrop-filter: blur(16px); border-bottom: 1px solid var(--border-subtle); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="index.php" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--text-dark);">
      <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1rem;">🌿</div>
      <div>
        <div style="font-family: 'Open Sans', 'Google Sans', sans-serif; font-weight: 700; font-size: 1.15rem; line-height: 1.1; color: var(--pine-900);">GeoRubber Watch</div>
        <div style="font-size: 0.7rem; color: var(--sage-500); font-weight: 600;">ระบบตรวจสอบย้อนกลับมาตรฐานความยั่งยืน EUDR</div>
      </div>
    </a>

    <div class="no-print" style="display: flex; gap: 8px;">
      <a href="index.php" class="btn btn-outline btn-sm">🏠 หน้าแรก</a>
      <a href="map.php" class="btn btn-primary btn-sm">🗺️ แผนที่ GIS</a>
      <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ พิมพ์หนังสือรับรอง (DDS)</button>
    </div>
  </header>

  <div class="main-container">
    <div class="passport-container">
      
      <!-- Passport Header Banner (Nordic Forest Pine) -->
      <div class="passport-header" style="<?= $isCompliant ? 'background: linear-gradient(135deg, #0e2017, #1c4532);' : 'background: linear-gradient(135deg, #450a0a, #7f1d1d);' ?>">
        
        <div class="passport-badge-certified" style="<?= $isCompliant ? '' : 'border-color:#fca5a5; color:#ffffff;' ?>">
          <?php if ($isCompliant): ?>
            <span>🛡️ EUDR COMPLIANT VERIFIED</span>
          <?php else: ?>
            <span>⚠️ AT-RISK NON-COMPLIANT</span>
          <?php endif; ?>
        </div>

        <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-bottom: 6px; color: #a7f3d0; font-weight: 600;">
          EU Deforestation Regulation (EUDR) Digital Product Passport
        </div>
        <h1 class="font-heading" style="font-size: 2.1rem; margin-bottom: 8px; color: #ffffff;">
          <?= htmlspecialchars($plot['plot_name']) ?>
        </h1>
        <div style="font-size: 0.925rem; color: #e2e8f0;">
          รหัสแปลง: <span style="font-family: monospace; font-weight: 700; color: #a7f3d0;"><?= $plot['plot_code'] ?></span> | 
          รหัสรับรองสากล: <span style="font-family: monospace;"><?= $plot['traceability_token'] ?></span>
        </div>
      </div>

      <!-- Passport Body (Clean White) -->
      <div style="padding: 2.5rem; background: #ffffff;">
        
        <!-- EUDR Due Diligence Statement Card -->
        <div style="background: <?= $isCompliant ? 'var(--sage-50)' : 'var(--danger-bg)' ?>; border: 1px solid <?= $isCompliant ? 'var(--sage-200)' : '#fca5a5' ?>; padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
          <div style="display: flex; align-items: flex-start; gap: 1.25rem;">
            <div style="font-size: 2.25rem;">
              <?= $isCompliant ? '✅' : '⛔' ?>
            </div>
            <div>
              <h3 class="font-heading" style="font-size: 1.2rem; color: <?= $isCompliant ? 'var(--pine-900)' : 'var(--danger-text)' ?>; margin-bottom: 6px;">
                <?= $isCompliant ? 'หนังสือรับรองการปลอดการตัดไม้ทำลายป่า (Deforestation-Free Declaration)' : 'รายงานแจ้งเตือน: แปลงอยู่ในพื้นที่ทับซ้อนป่าสงวน' ?>
              </h3>
              <p style="font-size: 0.9rem; color: var(--text-body); line-height: 1.65;">
                <?php if ($isCompliant): ?>
                  แปลงยางพารานี้ได้รับการตรวจสอบเชิงพื้นที่ด้วยระบบสารสนเทศภูมิศาสตร์ (GIS) และภาพถ่ายดาวเทียมแล้ว ยืนยันว่า<strong>ไม่มีการบุกรุกหรือตัดไม้ทำลายป่าสงวนแห่งชาติ</strong> และมีการเพาะปลูกอย่างถูกต้องตามข้อกำหนด <strong>Regulation (EU) 2023/1115 (EUDR)</strong> ซึ่งเริ่มเพาะปลูกก่อนเกณฑ์กำหนด Cut-off Date (31 ธันวาคม 2020)
                <?php else: ?>
                  แปลงนี้ตรวจพบการทับซ้อนกับแนวเขตป่าสงวนแห่งชาติ คิดเป็น <strong><?= $plot['eudr_overlap_pct'] ?>%</strong> ของพื้นที่แปลง ไม่สามารถออกใบรับรองการส่งออกไปยังตลาดยุโรปได้จนกว่าจะมีการพิสูจน์สิทธิ์ในที่ดินอย่างเป็นทางการ
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>

        <!-- 2 Columns: Map & Information -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2.25rem;">
          
          <!-- Plot Satellite Map -->
          <div>
            <div style="font-weight: 700; font-size: 1rem; margin-bottom: 10px; color: var(--pine-900); font-family: 'Open Sans', 'Google Sans', sans-serif;">📍 พิกัดเชิงพื้นที่และขอบเขตแปลง (Spatial Boundary)</div>
            <div id="passport-map" style="height: 320px; border-radius: var(--radius-md); border: 1px solid var(--border-medium); overflow: hidden; box-shadow: var(--shadow-sm);"></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 10px; display: flex; justify-content: space-between;">
              <span>Centroid: <strong style="color:var(--pine-900);"><?= $plot['centroid_lat'] ?>, <?= $plot['centroid_lng'] ?></strong></span>
              <span>ระบบพิกัด: WGS84 (EPSG:4326)</span>
            </div>
          </div>

          <!-- Plot Attributes (Clean Minimalist Table) -->
          <div>
            <div style="font-weight: 700; font-size: 1rem; margin-bottom: 10px; color: var(--pine-900); font-family: 'Open Sans', 'Google Sans', sans-serif;">📋 ข้อมูลแปลงและการเพาะปลูก</div>
            
            <div style="border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden; font-size: 0.875rem;">
              
              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; background: var(--bg-main); border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">เกษตรกรผู้ปลูก:</span>
                <strong style="color: var(--pine-900);"><?= $plot['prefix'] ?><?= $plot['first_name'] ?> <?= $plot['last_name'] ?> (<?= $plot['farmer_code'] ?>)</strong>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">ที่ตั้งแปลง:</span>
                <span style="color: var(--text-body);">ต.<?= $plot['subdistrict'] ?> อ.<?= $plot['district'] ?> จ.<?= $plot['province'] ?></span>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; background: var(--bg-main); border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">เอกสารสิทธิ์ที่ดิน:</span>
                <span style="color: var(--text-body);"><?= $plot['title_deed_type'] ?> เลขที่ <?= $plot['title_deed_no'] ?: '-' ?></span>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">เนื้อที่แปลง:</span>
                <strong style="color: var(--success);"><?= $plot['area_rai'] ?> ไร่ <?= $plot['area_ngan'] ?> งาน <?= $plot['area_sqwah'] ?> ตร.ว. (<?= $plot['area_hectare'] ?> ha)</strong>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; background: var(--bg-main); border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">พันธุ์ยางพารา:</span>
                <span style="font-weight: 700; color: var(--pine-900);"><?= $plot['rubber_clone'] ?></span>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; border-bottom: 1px solid var(--border-subtle);">
                <span style="color: var(--text-muted);">ปีที่เริ่มปลูก:</span>
                <span style="color: var(--text-body);">ปี ค.ศ. <?= $plot['planting_year'] ?> (อายุประมาณ <?= date('Y') - $plot['planting_year'] ?> ปี)</span>
              </div>

              <div style="display: grid; grid-template-columns: 140px 1fr; padding: 11px 16px; background: var(--bg-main);">
                <span style="color: var(--text-muted);">จำนวนต้นยาง:</span>
                <span style="color: var(--pine-900); font-weight: 600;"><?= number_format($plot['tree_count']) ?> ต้น (เฉลี่ย <?= $plot['trees_per_rai'] ?> ต้น/ไร่)</span>
              </div>

            </div>
          </div>

        </div>

        <!-- Harvest Yield Batch Records (Clean Table) -->
        <div>
          <h3 class="font-heading" style="font-size: 1.2rem; margin-bottom: 12px; color: var(--pine-900);">📦 ประวัติรอบการเก็บเกี่ยวผลผลิตน้ำยางสด (Production History)</h3>
          
          <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden;">
            <thead>
              <tr style="background: var(--bg-main); border-bottom: 2px solid var(--border-subtle); text-align: left;">
                <th style="padding: 12px 16px; color: var(--text-dark); font-weight: 700;">วันที่เก็บเกี่ยว</th>
                <th style="padding: 12px 16px; color: var(--text-dark); font-weight: 700;">รอบกรีด</th>
                <th style="padding: 12px 16px; text-align: right; color: var(--text-dark); font-weight: 700;">น้ำยางสด (กก.)</th>
                <th style="padding: 12px 16px; text-align: center; color: var(--text-dark); font-weight: 700;">% DRC</th>
                <th style="padding: 12px 16px; text-align: right; color: var(--text-dark); font-weight: 700;">ยางแห้ง (กก.)</th>
                <th style="padding: 12px 16px; color: var(--text-dark); font-weight: 700;">ผู้รับซื้อ / จุดรวบรวม</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($yields)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">ยังไม่มีประวัติการเก็บเกี่ยว</td>
                </tr>
              <?php else: ?>
                <?php foreach ($yields as $y): ?>
                  <tr style="border-bottom: 1px solid var(--border-subtle); transition: background 0.2s;" onmouseover="this.style.background='var(--sage-50)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 12px 16px; font-weight: 600; color: var(--text-dark);"><?= $y['harvest_date'] ?></td>
                    <td style="padding: 12px 16px; color: var(--text-body);">รอบที่ <?= $y['tapping_round'] ?></td>
                    <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: var(--text-dark);"><?= number_format($y['fresh_latex_kg'], 1) ?></td>
                    <td style="padding: 12px 16px; text-align: center; font-weight: 600; color: var(--sage-600);"><?= number_format($y['drc_percent'], 1) ?>%</td>
                    <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: var(--success);"><?= number_format($y['dry_rubber_kg'], 2) ?></td>
                    <td style="padding: 12px 16px; color: var(--text-muted);"><?= htmlspecialchars($y['buyer_name']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Verification Signature & Authority Box -->
        <div style="margin-top: 2.75rem; padding-top: 1.75rem; border-top: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <div style="font-size: 0.825rem; color: var(--text-muted); line-height: 1.6;">
            <div><strong>หน่วยงานรับรองข้อมูลเชิงพื้นที่:</strong> สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม</div>
            <div>มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี (Prince of Songkla University, Surat Thani Campus)</div>
            <div>รหัสโครงงาน: GeoRubber Watch Intelligent GIS Monitoring Platform</div>
            <div style="margin-top: 4px; font-family: monospace; font-size: 11px; color: var(--sage-600);">Verification Hash: <?= hash('sha256', $plot['traceability_token'] . $plot['created_at']) ?></div>
          </div>

          <div style="text-align: right; font-size: 0.825rem;">
            <div style="font-weight: 700; color: var(--pine-900); font-size: 0.95rem;">GeoRubber Watch EUDR Platform</div>
            <div style="color: var(--text-light);">ตรวจสอบข้อมูลเมื่อ: <?= date('d/m/Y H:i:s') ?></div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const plotGeo = <?= $plot['geojson_geometry'] ?>;
      const centroid = [<?= $plot['centroid_lat'] ?>, <?= $plot['centroid_lng'] ?>];
      const isCompliant = <?= $isCompliant ? 'true' : 'false' ?>;

      const map = L.map('passport-map', {
        center: centroid,
        zoom: 16,
        zoomControl: false,
        attributionControl: false
      });

      // Satellite Basemap
      L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
      }).addTo(map);

      // Add Plot Polygon
      const plotLayer = L.geoJSON(plotGeo, {
        style: {
          color: isCompliant ? '#2e7d32' : '#c62828',
          fillColor: isCompliant ? '#2e7d32' : '#c62828',
          fillOpacity: 0.45,
          weight: 3
        }
      }).addTo(map);

      // Centroid Marker
      L.circleMarker(centroid, {
        radius: 6,
        color: '#ffffff',
        fillColor: isCompliant ? '#1b5e20' : '#b71c1c',
        fillOpacity: 1,
        weight: 2
      }).addTo(map);

      map.fitBounds(plotLayer.getBounds(), { padding: [20, 20] });
    });
  </script>
</body>
</html>
