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
        <div style="font-size: 2rem; margin-bottom: 12px; color: var(--pine-800);">
          <svg style="width: 36px; height: 36px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
        </div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          GeoJSON Spatial Dataset
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          ไฟล์ข้อมูลขอบเขตแปลงปลูก (Polygon Geometry) พิกัด Centroid และ Attributes ครบถ้วน สำหรับนำเข้าสู่โปรแกรม QGIS, ArcGIS หรือระบบตรวจสอบย้อนกลับ EUDR
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>จำนวนแปลง: <strong style="color:var(--pine-900);"><?= $totalPlots ?> แปลง</strong></div>
          <div>พิกัดอ้างอิง: <strong style="color:var(--sage-600);">EPSG:4326 (WGS84)</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=geojson" class="btn btn-primary" style="width: 100%;">
        ดาวน์โหลด GeoJSON
      </a>
    </div>

    <!-- Plots CSV Export Card -->
    <div class="clean-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--sage-400);">
      <div>
        <div style="font-size: 2rem; margin-bottom: 12px; color: var(--sage-600);">
          <svg style="width: 36px; height: 36px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        </div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          ทะเบียนแปลงปลูกยางพารา (CSV)
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          ตารางรายชื่อเกษตรกร รหัสแปลง ขนาดเนื้อที่ (ไร่-งาน-ตร.ว.) พันธุ์ยาง ปีที่เริ่มปลูก เอกสารสิทธิ์ และสถานะการประเมิน EUDR
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>เกษตรกร: <strong style="color:var(--pine-900);"><?= $totalFarmers ?> ราย</strong></div>
          <div>พื้นที่รวม: <strong style="color:var(--sage-600);"><?= number_format($totalRai) ?> ไร่</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=plots_csv" class="btn btn-primary" style="width: 100%;">
        ดาวน์โหลด CSV แปลงปลูก
      </a>
    </div>

    <!-- Yields CSV Export Card -->
    <div class="clean-card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--success);">
      <div>
        <div style="font-size: 2rem; margin-bottom: 12px; color: var(--success);">
          <svg style="width: 36px; height: 36px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
        </div>
        <h3 class="font-heading" style="font-size: 1.3rem; color: var(--pine-900); margin-bottom: 6px;">
          ประวัติผลผลิตน้ำยางสด (CSV)
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-body); margin-bottom: 1.25rem; line-height: 1.6;">
          บันทึกน้ำหนักน้ำยางสด ปริมาณเนื้อยางแห้ง (% DRC) ราคาขาย รายได้ และจุดรวบรวมผลผลิตรายรอบการกรีด
        </p>
        <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.825rem; margin-bottom: 1.5rem;">
          <div>บันทึกผลผลิต: <strong style="color:var(--pine-900);"><?= $totalYields ?> รายการ</strong></div>
          <div>รองรับ: <strong style="color:var(--sage-600);">Microsoft Excel (UTF-8 BOM)</strong></div>
        </div>
      </div>
      <a href="api/export.php?type=yields_csv" class="btn btn-primary" style="width: 100%;">
        ดาวน์โหลด CSV ผลผลิต
      </a>
    </div>

  </div>

  <!-- Supabase Cloud Database Backup & Local Persistence Section -->
  <div class="clean-card" style="margin-bottom: 2.5rem; border-top: 4px solid #3ecf8e; background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
      <div style="display: flex; gap: 1rem; align-items: center;">
        <div style="width: 52px; height: 52px; border-radius: 14px; background: #3ecf8e1a; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; border: 1px solid #3ecf8e40; color: #065f46;">
          <svg style="width: 28px; height: 28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <h3 class="font-heading" style="font-size: 1.35rem; color: #064e3b; margin: 0;">
              ระบบสำรองฐานข้อมูล Supabase Cloud ลงโฟลเดอร์โปรเจกต์ (Local Database Backup)
            </h3>
            <span style="background: #3ecf8e; color: #064e3b; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">
              Cloud Synchronized
            </span>
          </div>
          <p style="font-size: 0.875rem; color: #047857; margin-top: 4px; margin-bottom: 0;">
            ดึงและคัดลอกข้อมูลทั้งหมด 100% จาก Supabase Cloud มาบันทึกลงในโฟลเดอร์ของโปรเจกต์ ป้องกันข้อมูลสูญหายและสามารถใช้งานแบบออฟไลน์ได้
          </p>
        </div>
      </div>
      <div>
        <button id="btnSyncBackup" onclick="triggerSupabaseBackup()" class="btn" style="background: #059669; color: white; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 10px 20px; box-shadow: 0 2px 6px rgba(5,150,105,0.25);">
          <span id="syncIcon" style="display:inline-block;">◐</span> <span id="syncText">ซิงค์ & สำรองข้อมูลทันที</span>
        </button>
      </div>
    </div>

    <div id="backupStatusAlert" style="display: none; margin-bottom: 1.25rem; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.875rem;"></div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
      <div style="background: #ffffff; border: 1px solid #d1fae5; padding: 1rem 1.25rem; border-radius: var(--radius-sm);">
        <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; text-transform: uppercase; margin-bottom: 4px;">ตำแหน่งไฟล์ในโปรเจกต์</div>
        <div style="font-family: monospace; font-size: 0.8rem; color: #1e293b; line-height: 1.7;">
          • <strong>sql/supabase_backup_latest.sql</strong><br>
          • <strong>data/backups/supabase_backup_latest.json</strong><br>
          • <strong>data/backups/tables/*.json</strong><br>
          • <strong>data/georubber_watch_backup.sqlite</strong>
        </div>
      </div>

      <div style="background: #ffffff; border: 1px solid #d1fae5; padding: 1rem 1.25rem; border-radius: var(--radius-sm); display: flex; flex-direction: column; justify-content: center;">
        <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; text-transform: uppercase; margin-bottom: 8px;">ดาวน์โหลดสำรองข้อมูลสู่เครื่อง</div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <a href="api/backup_supabase.php?download=sql" class="btn" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.8rem; padding: 6px 12px;">
            SQL Dump (.sql)
          </a>
          <a href="api/backup_supabase.php?download=json" class="btn" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.8rem; padding: 6px 12px;">
            JSON Data (.json)
          </a>
          <a href="api/backup_supabase.php?download=sqlite" class="btn" style="background: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 0.8rem; padding: 6px 12px;">
            SQLite (.sqlite)
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
  async function triggerSupabaseBackup() {
    const btn = document.getElementById('btnSyncBackup');
    const syncIcon = document.getElementById('syncIcon');
    const syncText = document.getElementById('syncText');
    const alertBox = document.getElementById('backupStatusAlert');
    
    btn.disabled = true;
    syncIcon.style.display = 'inline-block';
    syncIcon.style.animation = 'spin 1s linear infinite';
    syncText.innerText = 'กำลังดึงข้อมูลและสำรองไฟล์...';
    alertBox.style.display = 'none';

    try {
      const res = await fetch('api/backup_supabase.php');
      const data = await res.json();
      
      if (data.success) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#d1fae5';
        alertBox.style.color = '#065f46';
        alertBox.style.border = '1px solid #6ee7b7';
        
        let countsHtml = Object.entries(data.summary.table_counts)
          .map(([t, c]) => `<span><strong>${t}:</strong> ${c} รายการ</span>`)
          .join(' • ');

        alertBox.innerHTML = `
          <strong>${data.message}</strong> (${data.timestamp})<br>
          <div style="margin-top: 4px; font-size: 0.825rem;">
            รวมทั้งหมด ${data.summary.total_records} รายการ จาก ${data.summary.total_tables} ตาราง (${countsHtml})
          </div>
        `;
      } else {
        throw new Error(data.message || 'สำรองข้อมูลไม่สำเร็จ');
      }
    } catch (err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.style.border = '1px solid #fca5a5';
      alertBox.innerHTML = `<strong>ผิดพลาด:</strong> ${err.message}`;
    } finally {
      btn.disabled = false;
      syncIcon.style.animation = 'none';
      syncText.innerText = 'ซิงค์ & สำรองข้อมูลทันที';
    }
  }
  </script>
  <style>
  @keyframes spin { 100% { transform: rotate(360deg); } }
  </style>

  <!-- EUDR Due Diligence Audit Guide (Clean Cards) -->
  <div class="clean-card">
    <div class="chart-header">
      <div>
        <h3 class="font-heading" style="font-size: 1.25rem; color: var(--pine-900);">
          เอกสารประกอบการตรวจสอบความสอดคล้องตามมาตรฐาน EUDR (Due Diligence Checklist)
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
