<?php
/**
 * GeoRubber Watch - Database Installation, Diagnostics & Visual Manager
 * Clean Eco-Minimalist Design
 */
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$message = '';
$error = '';

// Handle Delete Record
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['table']) && isset($_GET['id'])) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']);
    $id = (int)$_GET['id'];
    $allowed = ['rubber_plots', 'farmers', 'forest_reserves', 'yield_logs', 'traceability_batches', 'users'];
    if (in_array($table, $allowed) && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
            $stmt->execute([$id]);
            $message = "ลบข้อมูล ID #{$id} จากตาราง {$table} เรียบร้อยแล้ว";
        } catch (Exception $e) {
            $error = "ไม่สามารถลบข้อมูลได้: " . $e->getMessage();
        }
    }
}

// Handle Re-seed or Reset Action
if (isset($_POST['action']) && $_POST['action'] === 'reset_database') {
    try {
        $tables = ['traceability_batches', 'yield_logs', 'rubber_plots', 'forest_reserves', 'farmers', 'users'];
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS {$table} CASCADE");
        }

        require_once __DIR__ . '/config/seed_data.php';
        seedDatabase($pdo);
        $message = 'รีเซ็ตฐานข้อมูลและสร้างข้อมูลจำลองรอบ ม.อ. สุราษฎร์ธานี เรียบร้อยแล้ว!';
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// Check Table Counts & Allowed Tables
$allowedTables = [
    'rubber_plots' => '🌲 แปลงปลูกยางพารา (rubber_plots)',
    'farmers' => '👨‍🌾 เกษตรกร (farmers)',
    'forest_reserves' => '🏞️ ป่าสงวน 26 แนว (forest_reserves)',
    'yield_logs' => '⚖️ ผลผลิตน้ำยาง (yield_logs)',
    'traceability_batches' => '📦 ล็อตส่งออก EUDR (traceability_batches)',
    'users' => '👥 ผู้ใช้งาน (users)'
];

$tableCounts = [];
foreach ($allowedTables as $tbl => $tblName) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
        $tableCounts[$tbl] = $count;
    } catch (Exception $e) {
        $tableCounts[$tbl] = 0;
    }
}

// Current Selected Table for Data Viewer
$currentTable = $_GET['table'] ?? 'rubber_plots';
if (!array_key_exists($currentTable, $allowedTables)) {
    $currentTable = 'rubber_plots';
}

$search = trim($_GET['q'] ?? '');
$records = [];
$columns = [];

try {
    $colStmt = $pdo->query("SELECT * FROM {$currentTable} LIMIT 1");
    if ($colStmt) {
        for ($i = 0; $i < $colStmt->columnCount(); $i++) {
            $meta = $colStmt->getColumnMeta($i);
            $columns[] = $meta['name'];
        }
    }

    if (!empty($search)) {
        $searchFields = [];
        $params = [];
        foreach ($columns as $c) {
            if ($c !== 'geojson_geometry') {
                $searchFields[] = "CAST({$c} AS TEXT) LIKE ?";
                $params[] = "%{$search}%";
            }
        }
        $where = !empty($searchFields) ? "WHERE " . implode(' OR ', $searchFields) : "";
        $stmt = $pdo->prepare("SELECT * FROM {$currentTable} {$where} ORDER BY id DESC LIMIT 100");
        $stmt->execute($params);
        $records = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM {$currentTable} ORDER BY id DESC LIMIT 100");
        $records = $stmt ? $stmt->fetchAll() : [];
    }
} catch (Exception $e) {
    $error = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="main-container">
  
  <div style="margin-bottom: 2rem;">
    <div style="font-size: 0.85rem; font-weight: 700; color: var(--sage-600); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
      Database Administration & Explorer
    </div>
    <h1 class="font-heading" style="font-size: 2rem; color: var(--pine-900);">
      จัดการและดูข้อมูลในฐานข้อมูล (Visual Database Manager)
    </h1>
    <p style="font-size: 0.925rem; color: var(--text-muted); margin-top: 4px;">
      ตรวจสอบตาราง เรียกดูเรคคอร์ด ค้นหา ลบข้อมูล นำเข้าไฟล์แนวเขตป่าสงวน และรีเซ็ตฐานข้อมูล
    </p>
  </div>

  <?php if ($message): ?>
    <div style="background: var(--success-bg); border: 1px solid rgba(46,125,50,0.3); color: var(--success-text); padding: 1.1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.75rem; font-weight: 600;">
      ✅ <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div style="background: var(--danger-bg); border: 1px solid rgba(198,40,40,0.3); color: var(--danger-text); padding: 1.1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.75rem; font-weight: 600;">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Database Connection Status Card -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="clean-card">
      <div class="chart-header">
        <h3 class="font-heading" style="font-size: 1.25rem; color: var(--pine-900);">🔌 สถานะการเชื่อมต่อฐานข้อมูล</h3>
        <span class="badge badge-compliant">ออนไลน์ (Connected)</span>
      </div>

      <div style="font-size: 0.9rem; line-height: 2; color: var(--text-body);">
        <div><strong>ประเภทฐานข้อมูล:</strong> <span style="font-family: monospace; background: var(--sage-50); color: var(--pine-900); padding: 3px 8px; border-radius: 6px; font-weight: 700;"><?= strtoupper($driver) ?></span></div>
        <div><strong>โฮสต์และฐานข้อมูล:</strong> <span style="font-size: 0.825rem; color: var(--text-muted); word-break: break-all;"><?= ($driver === 'pgsql') ? htmlspecialchars($db_config['pgsql']['host'] . ':' . $db_config['pgsql']['port'] . ' / ' . $db_config['pgsql']['dbname']) : 'Local SQLite Storage' ?></span></div>
        <div><strong>สถานะ:</strong> <span style="color: var(--success); font-weight: 700;">พร้อมใช้งาน 100% (Ready)</span></div>
      </div>

      <form method="POST" style="margin-top: 1.5rem;" onsubmit="return confirm('คำเตือน: คุณต้องการรีเซ็ตฐานข้อมูลและสร้างข้อมูลตัวอย่างใหม่ทั้งหมดใช่หรือไม่? ข้อมูลเก่าจะถูกลบและสร้างใหม่');">
        <input type="hidden" name="action" value="reset_database">
        <button type="submit" class="btn btn-outline" style="width: 100%; border-color: #fcd34d; color: #b45309; background: #fffbeb;">
          🔄 รีเซ็ตและสร้างข้อมูลจำลองใหม่ (1-Click Re-Seed)
        </button>
      </form>
    </div>

    <!-- Table Summary Card -->
    <div class="clean-card">
      <div class="chart-header">
        <h3 class="font-heading" style="font-size: 1.25rem; color: var(--pine-900);">📊 จำนวนเรคคอร์ดในแต่ละตาราง</h3>
      </div>

      <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
        <thead>
          <tr style="background: var(--bg-main); border-bottom: 2px solid var(--border-subtle); text-align: left;">
            <th style="padding: 8px 12px; color: var(--text-dark); font-weight: 700;">ตาราง</th>
            <th style="padding: 8px 12px; text-align: right; color: var(--text-dark); font-weight: 700;">จำนวน</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tableCounts as $tbl => $cnt): ?>
            <tr style="border-bottom: 1px solid var(--border-subtle);">
              <td style="padding: 8px 12px;">
                <a href="setup.php?table=<?= $tbl ?>#table-viewer" style="font-family: monospace; font-weight: 700; color: var(--pine-900); text-decoration: underline;">
                  <?= $tbl ?>
                </a>
              </td>
              <td style="padding: 8px 12px; text-align: right; font-weight: 700; color: var(--sage-600);">
                <?= is_numeric($cnt) ? number_format($cnt) : $cnt ?> รายการ
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- VISUAL DATA TABLE VIEWER & EDITOR -->
  <div id="table-viewer" class="clean-card" style="margin-bottom: 2rem; border: 2px solid var(--sage-300);">
    <div class="chart-header" style="flex-wrap: wrap; gap: 1rem;">
      <div>
        <h3 class="font-heading" style="font-size: 1.35rem; color: var(--pine-900); display: flex; align-items: center; gap: 8px;">
          <span>📋</span> ตัวเปิดดูและแก้ไขข้อมูลในตาราง: <code style="color: var(--sage-600);"><?= $currentTable ?></code>
        </h3>
        <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 2px;">
          เลือกแท็บตารางด้านล่างเพื่อสลับดูข้อมูล ค้นหา หรือลบแถวข้อมูลที่ไม่ต้องการ
        </p>
      </div>

      <!-- Search Box -->
      <form method="GET" style="display: flex; gap: 8px; align-items: center;">
        <input type="hidden" name="table" value="<?= htmlspecialchars($currentTable) ?>">
        <input 
          type="text" 
          name="q" 
          value="<?= htmlspecialchars($search) ?>" 
          placeholder="ค้นหาในตารางนี้..." 
          class="form-control" 
          style="width: 220px; font-size: 0.85rem; padding: 6px 12px;"
        >
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 6px 14px;">ค้นหา</button>
        <?php if ($search): ?>
          <a href="setup.php?table=<?= $currentTable ?>#table-viewer" class="btn btn-outline btn-sm" style="padding: 6px 10px;">ล้าง</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Table Tabs Switcher -->
    <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px solid var(--border-subtle);">
      <?php foreach ($allowedTables as $tKey => $tName): ?>
        <?php $isActive = ($currentTable === $tKey); ?>
        <a 
          href="setup.php?table=<?= $tKey ?>#table-viewer" 
          style="padding: 6px 14px; border-radius: 9999px; font-size: 0.825rem; font-weight: 700; text-decoration: none; white-space: nowrap; transition: all 0.2s; <?= $isActive ? 'background: var(--pine-900); color: #ffffff;' : 'background: var(--bg-main); color: var(--text-body); border: 1px solid var(--border-subtle);' ?>"
        >
          <?= $tName ?> (<?= number_format($tableCounts[$tKey] ?? 0) ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Live Data Table -->
    <div style="overflow-x: auto; max-height: 480px;">
      <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
        <thead>
          <tr style="background: var(--bg-main); border-bottom: 2px solid var(--border-subtle); position: sticky; top: 0; z-index: 10;">
            <th style="padding: 10px 12px; color: var(--text-dark); font-weight: 700; text-align: center;">จัดการ</th>
            <?php foreach ($columns as $col): ?>
              <?php if ($col === 'geojson_geometry'): ?>
                <th style="padding: 10px 12px; color: var(--text-dark); font-weight: 700;">พิกัด GIS (GeoJSON)</th>
              <?php else: ?>
                <th style="padding: 10px 12px; color: var(--text-dark); font-weight: 700; white-space: nowrap;"><?= htmlspecialchars($col) ?></th>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr>
              <td colspan="<?= count($columns) + 1 ?>" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                ไม่พบข้อมูลในตารางนี้
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($records as $row): ?>
              <tr style="border-bottom: 1px solid var(--border-subtle);">
                <!-- Action Delete Button -->
                <td style="padding: 8px 12px; text-align: center; white-space: nowrap;">
                  <a 
                    href="setup.php?table=<?= $currentTable ?>&action=delete&id=<?= $row['id'] ?>#table-viewer" 
                    onclick="return confirm('คุณต้องการลบข้อมูล ID #<?= $row['id'] ?> ใช่หรือไม่?');"
                    style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; text-decoration: none;"
                  >
                    🗑️ ลบ
                  </a>
                </td>

                <!-- Columns -->
                <?php foreach ($columns as $col): ?>
                  <td style="padding: 8px 12px; max-width: 280px; vertical-align: top;">
                    <?php $val = $row[$col] ?? ''; ?>
                    <?php if ($col === 'geojson_geometry'): ?>
                      <details style="cursor: pointer;">
                        <summary style="font-size: 0.775rem; font-family: monospace; color: var(--sage-600); text-decoration: underline;">
                          🗺️ ดู GeoJSON (<?= strlen($val) ?> ตัวอักษร)
                        </summary>
                        <pre style="margin-top: 6px; padding: 8px; background: #0f172a; color: #34d399; font-size: 0.725rem; border-radius: 8px; overflow-x: auto; max-height: 180px;"><?= htmlspecialchars($val) ?></pre>
                      </details>
                    <?php elseif ($col === 'eudr_status'): ?>
                      <?php if ($val === 'compliant'): ?>
                        <span class="badge badge-compliant">🟢 ผ่านเกณฑ์</span>
                      <?php elseif ($val === 'non_compliant'): ?>
                        <span class="badge badge-non_compliant">🔴 ทับซ้อนป่า</span>
                      <?php else: ?>
                        <span class="badge badge-under_review">⚠️ เฝ้าระวัง</span>
                      <?php endif; ?>
                    <?php elseif (str_contains($col, 'token') || str_contains($col, 'code')): ?>
                      <span style="font-family: monospace; font-weight: 700; color: var(--pine-900); font-size: 0.8rem;"><?= htmlspecialchars($val) ?></span>
                    <?php elseif (is_numeric($val) && (str_contains($col, 'price') || str_contains($col, 'revenue'))): ?>
                      <span style="font-family: monospace; font-weight: 700; color: var(--success);"><?= number_format((float)$val, 2) ?> ฿</span>
                    <?php else: ?>
                      <span style="word-break: break-word;"><?= htmlspecialchars($val) ?></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- GeoJSON Import Tool Card -->
  <div class="clean-card" style="border: 2px solid var(--sage-300); background: #ffffff; margin-bottom: 2rem; box-shadow: var(--shadow-md);">
    <div class="chart-header">
      <div>
        <h3 class="font-heading" style="font-size: 1.35rem; color: var(--pine-900); display: flex; align-items: center; gap: 8px;">
          <span>🌲</span> นำเข้าไฟล์แนวเขตป่าสงวนแห่งชาติ (Import GeoJSON Forest Boundaries)
        </h3>
        <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 2px;">
          รองรับไฟล์ <strong>.geojson</strong> หรือ <strong>.json</strong> โดยตรง 100% ระบบจะนำไปใช้วิเคราะห์การทับซ้อน EUDR อัตโนมัติ
        </div>
      </div>
      <span class="badge badge-compliant" style="font-size: 0.8rem;">Native GeoJSON Supported</span>
    </div>

    <form id="geojson-upload-form" onsubmit="handleGeoJSONUpload(event)" style="margin-top: 1rem;">
      <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-size: 0.9rem;">📂 เลือกไฟล์ .geojson จากเครื่องของคุณ:</label>
          <input type="file" id="geojson-file-input" accept=".geojson,.json" class="form-control" style="padding: 8px 12px; font-size: 0.875rem;" required>
          <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 4px;">
            ตัวอย่างไฟล์: <code>forest_reserves.geojson</code>, <code>khao_tha_phet.geojson</code> (FeatureCollection หรือ Polygon Feature)
          </div>
        </div>

        <div style="display: flex; gap: 8px;">
          <label style="display: flex; align-items: center; gap: 6px; font-size: 0.825rem; color: var(--text-body); cursor: pointer; margin-bottom: 8px;">
            <input type="checkbox" id="replace-existing-checkbox" checked>
            <span>แทนที่ข้อมูลป่าเดิม</span>
          </label>
          <button type="submit" id="btn-submit-geojson" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">
            🚀 เริ่มนำเข้าข้อมูล GeoJSON
          </button>
        </div>
      </div>

      <div id="geojson-upload-status" style="display: none; margin-top: 1rem; padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.875rem;"></div>
    </form>
  </div>

  <!-- External GUI Database Tools Guide -->
  <div class="clean-card" style="margin-bottom: 2rem;">
    <div class="chart-header">
      <h3 class="font-heading" style="font-size: 1.25rem; color: var(--pine-900);">💡 วิธีการดูและแก้ไขฐานข้อมูลผ่านโปรแกรมภายนอก (GUI Tools)</h3>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; font-size: 0.875rem; color: var(--text-body);">
      
      <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md);">
        <strong style="color: var(--pine-900); font-size: 1rem; display: block; margin-bottom: 6px;">1. โปรแกรม DBeaver / TablePlus / Postico</strong>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">
          โปรแกรมจัดการฐานข้อมูลยอดนิยมบน Mac สามารถเชื่อมต่อเพื่อดูตารางและแก้ไขแถวข้อมูลได้เหมือน Excel
        </p>
        <div style="font-size: 0.8rem; background: #ffffff; padding: 8px; border-radius: 6px; border: 1px solid var(--border-medium); font-family: monospace;">
          Host: 127.0.0.1<br>
          Port: 5432<br>
          Database: georubber_watch<br>
          User: postgres
        </div>
      </div>

      <div style="background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 1.25rem; border-radius: var(--radius-md);">
        <strong style="color: var(--pine-900); font-size: 1rem; display: block; margin-bottom: 6px;">2. คำสั่ง Terminal (psql CLI)</strong>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">
          เปิด Terminal บนเครื่อง Mac แล้วพิมพ์คำสั่งเพื่อเข้าสู่ PostgreSQL Prompt ทันที:
        </p>
        <div style="font-size: 0.8rem; background: #0f172a; color: #38bdf8; padding: 8px 12px; border-radius: 6px; font-family: monospace;">
          psql -U postgres -d georubber_watch
        </div>
      </div>

    </div>
  </div>

</div>

</div>

<script>
async function handleGeoJSONUpload(e) {
  e.preventDefault();
  const fileInput = document.getElementById('geojson-file-input');
  const replaceExisting = document.getElementById('replace-existing-checkbox').checked;
  const statusBox = document.getElementById('geojson-upload-status');
  const submitBtn = document.getElementById('btn-submit-geojson');

  if (!fileInput.files || fileInput.files.length === 0) {
    App.showToast('กรุณาเลือกไฟล์ .geojson', 'warning');
    return;
  }

  const formData = new FormData();
  formData.append('geojson_file', fileInput.files[0]);
  formData.append('replace_existing', replaceExisting ? 'true' : 'false');

  submitBtn.disabled = true;
  submitBtn.textContent = '⏳ กำลังนำเข้าข้อมูล...';
  statusBox.style.display = 'block';
  statusBox.style.background = 'var(--sage-50)';
  statusBox.style.border = '1px solid var(--sage-300)';
  statusBox.style.color = 'var(--pine-900)';
  statusBox.innerHTML = '⏳ กำลังอ่านโครงสร้างพิกัดเชิงพื้นที่และบันทึกลงฐานข้อมูล...';

  try {
    const res = await fetch('api/forests.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      statusBox.style.background = 'var(--success-bg)';
      statusBox.style.border = '1px solid rgba(46,125,50,0.3)';
      statusBox.style.color = 'var(--success-text)';
      statusBox.innerHTML = `✅ <strong>${data.message}</strong><br><span style="font-size:12px;">ข้อมูลป่าสงวนชุดใหม่พร้อมแสดงผลบน <a href="map.php" style="text-decoration:underline; font-weight:bold;">แผนที่ Web-GIS</a> และระบบตรวจสอบ EUDR แล้ว!</span>`;
      App.showToast(data.message, 'success');
      fileInput.value = '';
    } else {
      statusBox.style.background = 'var(--danger-bg)';
      statusBox.style.border = '1px solid rgba(198,40,40,0.3)';
      statusBox.style.color = 'var(--danger-text)';
      statusBox.innerHTML = `⚠️ <strong>นำเข้าไม่สำเร็จ:</strong> ${data.message}`;
      App.showToast(data.message || 'นำเข้าไม่สำเร็จ', 'error');
    }
  } catch (err) {
    statusBox.style.background = 'var(--danger-bg)';
    statusBox.style.border = '1px solid rgba(198,40,40,0.3)';
    statusBox.style.color = 'var(--danger-text)';
    statusBox.innerHTML = `⚠️ <strong>เกิดข้อผิดพลาดในการเชื่อมต่อ:</strong> ${err.message}`;
    App.showToast('เกิดข้อผิดพลาดในการอัปโหลดไฟล์', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = '🚀 เริ่มนำเข้าข้อมูล GeoJSON';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
