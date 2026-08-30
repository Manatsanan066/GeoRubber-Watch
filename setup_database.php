<?php
/**
 * GeoRubber Watch • MySQL Database Setup & phpMyAdmin Migration Assistant
 * Prince of Songkla University, Surat Thani Campus
 */
require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'idle'; // 'success' | 'error' | 'idle'
$details = [];

if (isset($_POST['action']) && $_POST['action'] === 'setup_mysql') {
    $host = $_POST['host'] ?? '127.0.0.1';
    $port = $_POST['port'] ?? '3306';
    $user = $_POST['user'] ?? 'root';
    $pass = $_POST['pass'] ?? '';
    $dbname = $_POST['dbname'] ?? 'georubber_watch';

    try {
        // 1. Connect to MySQL Server (without dbname first)
        $dsnRoot = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdoRoot = new PDO($dsnRoot, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $details[] = "✅ เชื่อมต่อไปยัง MySQL Server สำเร็จ ({$host}:{$port})";

        // 2. Create Database
        $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $details[] = "✅ สร้างฐานข้อมูล `{$dbname}` ใน phpMyAdmin เรียบร้อยแล้ว";

        // 3. Connect to the specific database
        $dsnDb = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdoDb = new PDO($dsnDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // 4. Run Full SQL Migration Script
        $sqlPath = __DIR__ . '/sql/georubber_watch_full.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("ไม่พบไฟล์สคริปต์ SQL: {$sqlPath}");
        }

        $sqlContent = file_get_contents($sqlPath);
        
        // Execute multi-query statements
        $pdoDb->exec($sqlContent);
        $details[] = "✅ สร้างโครงสร้างตาราง (Tables) และ Foreign Keys ทั้งหมด 6 ตาราง สำเร็จ";
        $details[] = "✅ นำเข้าข้อมูลเริ่มต้น (Seed Data) ผู้ใช้งาน, เกษตรกร, แนวเขตป่าสงวน 26 ผืนป่า, แปลงปลูก และผลผลิต สำเร็จ";

        // 5. Verify counts
        $usersCount = $pdoDb->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $plotsCount = $pdoDb->query("SELECT COUNT(*) FROM rubber_plots")->fetchColumn();
        $forestCount = $pdoDb->query("SELECT COUNT(*) FROM forest_reserves")->fetchColumn();
        $yieldsCount = $pdoDb->query("SELECT COUNT(*) FROM yield_logs")->fetchColumn();

        $details[] = "📊 สรุปข้อมูลใน MySQL:";
        $details[] = "   • ตาราง `users`: {$usersCount} รายการ";
        $details[] = "   • ตาราง `rubber_plots`: {$plotsCount} แปลง";
        $details[] = "   • ตาราง `forest_reserves`: {$forestCount} ผืนป่า";
        $details[] = "   • ตาราง `yield_logs`: {$yieldsCount} บันทึกผลผลิต";

        $status = 'success';
        $message = "ติดตั้งฐานข้อมูล MySQL (`{$dbname}`) สมบูรณ์แบบ พร้อมใช้งานร่วมกับ phpMyAdmin ทันที!";

    } catch (Exception $e) {
        $status = 'error';
        $message = "เกิดข้อผิดพลาดในการติดตั้ง: " . $e->getMessage();
        $details[] = "❌ คำแนะนำ: กรุณาตรวจสอบว่าเปิด MySQL ใน XAMPP Control Panel แล้วหรือยัง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ตั้งค่าฐานข้อมูล MySQL (phpMyAdmin) • GeoRubber Watch</title>
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    body { font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif; }
  </style>
</head>
<body class="min-h-screen bg-[#f4faf9] text-gray-800 flex items-center justify-center p-4 sm:p-8">

  <div class="w-full max-w-2xl bg-white rounded-3xl shadow-xl border border-[#bee6e1] p-6 sm:p-10 space-y-6">
    
    <!-- Header -->
    <div class="text-center space-y-2">
      <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#e8f7f5] text-[#0e4d4e] text-xs font-bold rounded-full border border-[#5ebbb6]/40">
        <span>🐬 XAMPP & phpMyAdmin Setup Assistant</span>
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-[#0e4d4e] tracking-tight">
        ตั้งค่าฐานข้อมูล MySQL สำหรับ GeoRubber Watch
      </h1>
      <p class="text-sm text-gray-500">
        คลิกปุ่มด้านล่างเพื่อสร้างฐานข้อมูล <code class="font-bold text-[#00a699]">georubber_watch</code> และนำเข้าตารางข้อมูลเข้าสู่ phpMyAdmin อัตโนมัติ
      </p>
    </div>

    <!-- Status Alert -->
    <?php if ($status === 'success'): ?>
      <div class="p-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 space-y-3">
        <div class="flex items-center gap-2 font-bold text-base text-emerald-800">
          <span class="text-xl">🎉</span> <?= htmlspecialchars($message) ?>
        </div>
        <ul class="text-xs sm:text-sm font-mono space-y-1 bg-white/70 p-3.5 rounded-xl border border-emerald-100">
          <?php foreach ($details as $d): ?>
            <li><?= htmlspecialchars($d) ?></li>
          <?php endforeach; ?>
        </ul>
        <div class="pt-2 flex flex-wrap gap-3">
          <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=georubber_watch" target="_blank" class="px-5 py-2.5 rounded-full bg-[#0e4d4e] hover:bg-[#00a699] text-white font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
            <span>🌐 เปิดดูใน phpMyAdmin</span>
          </a>
          <a href="map.php" class="px-5 py-2.5 rounded-full bg-[#00a699] hover:bg-[#0e4d4e] text-white font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
            <span>🗺️ ไปที่หน้าวาดแปลง (GIS Map)</span>
          </a>
          <a href="index.php" class="px-4 py-2.5 rounded-full bg-white hover:bg-gray-100 text-[#0e4d4e] font-bold text-xs border border-gray-300 transition-all">
            <span>หน้าแรก</span>
          </a>
        </div>
      </div>
    <?php elseif ($status === 'error'): ?>
      <div class="p-5 rounded-2xl bg-red-50 border border-red-200 text-red-900 space-y-2">
        <div class="font-bold text-sm text-red-800 flex items-center gap-2">
          <span>⚠️</span> <?= htmlspecialchars($message) ?>
        </div>
        <ul class="text-xs font-mono space-y-1 text-red-700">
          <?php foreach ($details as $d): ?>
            <li><?= htmlspecialchars($d) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Setup Form -->
    <?php if ($status !== 'success'): ?>
    <form method="POST" class="space-y-4 bg-[#f8faf9] p-6 rounded-2xl border border-gray-200">
      <input type="hidden" name="action" value="setup_mysql">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
        <div>
          <label class="block font-bold text-gray-700 mb-1">MySQL Host</label>
          <input type="text" name="host" value="127.0.0.1" class="w-full px-3 py-2 rounded-xl border border-gray-300 focus:border-[#00a699] outline-none">
        </div>
        <div>
          <label class="block font-bold text-gray-700 mb-1">Port</label>
          <input type="text" name="port" value="3306" class="w-full px-3 py-2 rounded-xl border border-gray-300 focus:border-[#00a699] outline-none">
        </div>
        <div>
          <label class="block font-bold text-gray-700 mb-1">Database Name (ชื่อฐานข้อมูล)</label>
          <input type="text" name="dbname" value="georubber_watch" class="w-full px-3 py-2 rounded-xl border border-gray-300 focus:border-[#00a699] outline-none font-bold text-[#0e4d4e]">
        </div>
        <div>
          <label class="block font-bold text-gray-700 mb-1">User (ค่าเริ่มต้น XAMPP คือ root)</label>
          <input type="text" name="user" value="root" class="w-full px-3 py-2 rounded-xl border border-gray-300 focus:border-[#00a699] outline-none">
        </div>
        <div class="sm:col-span-2">
          <label class="block font-bold text-gray-700 mb-1">Password (ค่าเริ่มต้น XAMPP ปล่อยว่างได้)</label>
          <input type="password" name="pass" value="" placeholder="ปล่อยว่างไว้สำหรับ XAMPP Default" class="w-full px-3 py-2 rounded-xl border border-gray-300 focus:border-[#00a699] outline-none">
        </div>
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full py-3.5 px-6 rounded-full bg-[#00a699] hover:bg-[#0e4d4e] text-white font-black text-sm sm:text-base shadow-lg hover:shadow-xl transition-all cursor-pointer flex items-center justify-center gap-2">
          <span>🚀 เริ่มติดตั้งและสร้างฐานข้อมูล MySQL (1-Click Setup)</span>
        </button>
      </div>
    </form>
    <?php endif; ?>

    <!-- Step-by-Step Manual Instructions -->
    <div class="border-t border-gray-200 pt-5 space-y-3 text-xs text-gray-600">
      <div class="font-bold text-[#0e4d4e] text-sm">💡 วิธีการนำเข้าไฟล์ SQL ด้วยตนเองใน phpMyAdmin:</div>
      <ol class="list-decimal pl-5 space-y-1.5 leading-relaxed">
        <li>เปิดเบราว์เซอร์ไปที่ <strong><a href="http://localhost/phpmyadmin" target="_blank" class="text-[#00a699] underline">http://localhost/phpmyadmin</a></strong></li>
        <li>คลิกแท็บ <strong>"นำเข้า" (Import)</strong> หรือเลือกฐานข้อมูล <code class="bg-gray-100 px-1.5 py-0.5 rounded font-bold">georubber_watch</code></li>
        <li>กดปุ่ม <strong>Choose File (เลือกไฟล์)</strong> แล้วเลือกไฟล์ที่เตรียมไว้: <br><code class="text-[#0e4d4e] font-mono font-bold bg-white px-2 py-0.5 border border-gray-200 rounded mt-1 inline-block">/Applications/XAMPP/xamppfiles/htdocs/RB/sql/georubber_watch_full.sql</code></li>
        <li>กดปุ่ม <strong>"นำเข้า" (Import / Go)</strong> ที่ด้านล่างสุด เป็นอันเสร็จสิ้น!</li>
      </ol>
    </div>

    <!-- Footer Back Button -->
    <div class="text-center pt-2">
      <a href="index.php" class="text-xs text-gray-400 hover:text-[#0e4d4e] transition-colors">
        ← กลับสู่หน้าหลัก GeoRubber Watch
      </a>
    </div>

  </div>

</body>
</html>
