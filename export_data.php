<?php
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();
require_once __DIR__ . '/includes/header.php';

$pdo = getDatabaseConnection();
$totalPlots = $pdo->query("SELECT COUNT(*) FROM rubber_plots")->fetchColumn();
$totalFarmers = $pdo->query("SELECT COUNT(*) FROM farmers")->fetchColumn();
$totalYields = $pdo->query("SELECT COUNT(*) FROM yield_logs")->fetchColumn();
$totalRai = $pdo->query("SELECT COALESCE(SUM(area_rai), 0) FROM rubber_plots")->fetchColumn();
?>

<div class="main-container">
  
  <div style="margin-bottom: 2rem;">
    <div style="font-size: 0.85rem; font-weight: 700; color: var(--sage-600); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
      Data Export & EUDR Compliance Dossier
    </div>
    <h1 class="font-heading" style="font-size: 2rem; color: var(--pine-900);">
      ศูนย์ส่งออกข้อมูลและชุดเอกสารรับรอง EUDR
    </h1>
    <p style="font-size: 0.925rem; color: var(--text-muted); margin-top: 4px;">
      ดาวน์โหลดชุดข้อมูลเชิงพื้นที่ (Spatial Data) รายงานการผลิต และเอกสารประกอบการยื่นรายงานความสอดคล้องตามมาตรฐานสหภาพยุโรป
    </p>
  </div>

  <!-- Export Options Grid (Clean Minimalist Cards) -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    
    <!-- GeoJSON Export Card -->
    <div class="clean-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--pine-800);">
      <div>
        <div style="font-size: 2.5rem; margin-bottom: 12px;">🗺️</div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          GeoJSON Spatial Dataset
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          ไฟล์ข้อมูลขอบเขตแปลงปลูก (Polygon Geometry) พิกัด Centroid และ Attributes ครบถ้วน สำหรับนำเข้าสู่โปรแกรม QGIS, ArcGIS หรือระบบตรวจสอบย้อนกลับ EUDR
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>📊 จำนวนแปลง: <strong style="color:var(--pine-900);"><?= $totalPlots ?> แปลง</strong></div>
          <div>🌐 พิกัดอ้างอิง: <strong style="color:var(--sage-600);">EPSG:4326 (WGS84)</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=geojson" class="btn btn-primary" style="width: 100%;">
        📥 ดาวน์โหลด GeoJSON
      </a>
    </div>

    <!-- Plots CSV Export Card -->
    <div class="clean-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--sage-400);">
      <div>
        <div style="font-size: 2.5rem; margin-bottom: 12px;">📑</div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          ทะเบียนแปลงปลูกยางพารา (CSV)
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          ตารางรายชื่อเกษตรกร รหัสแปลง ขนาดเนื้อที่ (ไร่-งาน-ตร.ว.) พันธุ์ยาง ปีที่เริ่มปลูก เอกสารสิทธิ์ และสถานะการประเมิน EUDR
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>👨‍🌾 เกษตรกร: <strong style="color:var(--pine-900);"><?= $totalFarmers ?> ราย</strong></div>
          <div>📐 พื้นที่รวม: <strong style="color:var(--sage-600);"><?= number_format($totalRai) ?> ไร่</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=plots_csv" class="btn btn-primary" style="width: 100%;">
        📥 ดาวน์โหลด CSV แปลงปลูก
      </a>
    </div>

    <!-- Yields CSV Export Card -->
    <div class="clean-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--success);">
      <div>
        <div style="font-size: 2.5rem; margin-bottom: 12px;">🧪</div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          ประวัติผลผลิตน้ำยางสด (CSV)
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          บันทึกน้ำหนักน้ำยางสด ปริมาณเนื้อยางแห้ง (% DRC) ราคาขาย รายได้ และจุดรวบรวมผลผลิตรายรอบการกรีด
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>📦 บันทึกผลผลิต: <strong style="color:var(--pine-900);"><?= $totalYields ?> รายการ</strong></div>
          <div>🏷️ รองรับ: <strong style="color:var(--sage-600);">Microsoft Excel (UTF-8 BOM)</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=yields_csv" class="btn btn-primary" style="width: 100%;">
        📥 ดาวน์โหลด CSV ผลผลิต
      </a>
    </div>

  </div>

  <!-- EUDR Due Diligence Audit Guide (Clean Cards) -->
  <div class="clean-card">
    <div class="chart-header">
      <div>
        <h3 class="font-heading" style="font-size: 1.25rem; color: var(--pine-900);">
          🛡️ เอกสารประกอบการตรวจสอบความสอดคล้องตามมาตรฐาน EUDR (Due Diligence Checklist)
        </h3>
        <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 2px;">
          ข้อมูลที่ระบบ GeoRubber Watch เตรียมพร้อมตามเกณฑ์ Regulation (EU) 2023/1115
        </div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; font-size: 0.875rem;">
      <div style="background: var(--sage-50); border: 1px solid var(--sage-200); padding: 1.35rem; border-radius: var(--radius-md);">
        <div style="font-weight: 700; color: var(--pine-900); margin-bottom: 6px; font-family: 'Open Sans', 'Google Sans', sans-serif;">1. Geolocation & Polygon Coordinates</div>
        <div style="color: var(--text-body); font-size: 0.825rem; line-height: 1.6;">
          แปลงที่มีขนาดมากกว่า 4 เฮกตาร์ (25 ไร่) หรือแปลงรายย่อย มีการระบุพิกัดรูปหลายเหลี่ยม (Polygon) ครบถ้วนทุกมุมแปลงตามมาตรฐานสากล WGS84
        </div>
      </div>

      <div style="background: var(--sage-50); border: 1px solid var(--sage-200); padding: 1.35rem; border-radius: var(--radius-md);">
        <div style="font-weight: 700; color: var(--pine-900); margin-bottom: 6px; font-family: 'Open Sans', 'Google Sans', sans-serif;">2. Deforestation-Free Assessment</div>
        <div style="color: var(--text-body); font-size: 0.825rem; line-height: 1.6;">
          ระบบวิเคราะห์การทับซ้อน (Overlay Analysis) ยืนยันว่าไม่มีการตัดไม้ทำลายป่าหรือบุกรุกพื้นที่ป่าสงวนแห่งชาติและป่าคุ้มครอง
        </div>
      </div>

      <div style="background: var(--sage-50); border: 1px solid var(--sage-200); padding: 1.35rem; border-radius: var(--radius-md);">
        <div style="font-weight: 700; color: var(--pine-900); margin-bottom: 6px; font-family: 'Open Sans', 'Google Sans', sans-serif;">3. Pre-2020 Cut-off Date Verified</div>
        <div style="color: var(--text-body); font-size: 0.825rem; line-height: 1.6;">
          จัดเก็บและตรวจสอบปีที่เริ่มปลูกยางพาราเทียบกับเกณฑ์วันที่ 31 ธันวาคม 2020 เพื่อยืนยันความถูกต้องของประวัติการใช้ประโยชน์ที่ดิน
        </div>
      </div>

      <div style="background: var(--sage-50); border: 1px solid var(--sage-200); padding: 1.35rem; border-radius: var(--radius-md);">
        <div style="font-weight: 700; color: var(--pine-900); margin-bottom: 6px; font-family: 'Open Sans', 'Google Sans', sans-serif;">4. Full Digital Traceability via QR Code</div>
        <div style="color: var(--text-body); font-size: 0.825rem; line-height: 1.6;">
          ทุกแปลงและทุกล็อตการเก็บเกี่ยวมีรหัสตรวจสอบย้อนกลับ (Traceability Token) และ QR Code สำหรับผู้ซื้อและหน่วยงานตรวจสอบสามารถสแกนดูข้อมูลได้ทันที
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
