<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$current_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['full_name'] ?? ($current_role === 'admin' ? 'รศ.ดร.สุพัตรา พุฒิเนาวรัตน์' : 'นางสาวมาทินี โรยนรินทร์');
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GeoRubber Watch - แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา</title>
  
  <!-- Google Fonts: Google Sans, Open Sans & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Sarabun:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
  
  <!-- Leaflet & Leaflet.draw CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

  <!-- Platform Stylesheet (Clean Eco-Minimalist Theme) -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Floating Pill Navigation Header -->
  <header class="top-navbar">
    <div class="nav-pill-container">
      
      <!-- Brand Logo -->
      <a href="index.php" class="brand-wrapper">
        <div class="brand-icon" style="padding: 3px;">
          <svg class="w-5 h-5 text-white" viewBox="0 0 192 192" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;">
            <path stroke="#ffffff" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M142.07 46.129c5.265-3.387 20.107-6.337 24.539 2.417 4.433 8.268-5.427 14.978-15.24 21.198-8.221 5.557-16.062 8.81-23.778 13.532-7.046-7.025-18.528-5.021-25.629.828-8.983 6.65-15.848 15.812-25.36 21.809 12.522 10.193 32.174 12.691 45.335 2.244 7.46-5.283 12.071-16.873 5.724-24.725m-81.55 27.92c-5.265 3.387-17.535 7.623-21.967-.81-3.79-8.91 4.462-14.334 13.632-20.876 8.221-5.558 16.062-8.81 23.778-13.532 7.045 7.024 18.528 5.02 25.629-.829 8.982-6.65 15.848-15.812 25.36-21.809-12.522-10.193-32.174-12.69-45.335-2.243-7.46 5.283-12.071 16.873-5.724 24.725M94.218 22.01c-25.433-.535-49.944 18.01-55.391 43.022-3.294 13.583-1.059 28.205 6.059 40.23C57.005 129.747 74.968 150.826 94.219 170c20.368-18.544 39.793-39.311 51.27-64.662 3.97-8.554 6.45-18.898 6.32-27.734.01-6.628-1.505-15.028-4.575-21.661-8.904-20.6-30.684-34.165-53.015-33.931z"></path>
          </svg>
        </div>
        <div>
          <div class="brand-title">GeoRubber Watch</div>
          <div class="brand-subtitle">Intelligent GIS • PSU Surat Thani</div>
        </div>
      </a>

      <!-- Navigation Links (Ordered per user specification) -->
      <nav>
        <ul class="nav-links">
          <li>
            <a href="index.php" class="nav-item <?= ($current_page === 'index.php') ? 'active' : '' ?>">
              🏠 หน้าแรก
            </a>
          </li>
          <li>
            <a href="overview.php" class="nav-item <?= ($current_page === 'overview.php') ? 'active' : '' ?>">
              🛰️ แผนที่ GIS
            </a>
          </li>
          <li>
            <a href="dashboard.php" class="nav-item <?= ($current_page === 'dashboard.php') ? 'active' : '' ?>">
              📊 แดชบอร์ด
            </a>
          </li>
          <li>
            <a href="map.php" class="nav-item <?= ($current_page === 'map.php') ? 'active' : '' ?>">
              📍 แปลงปลูก
            </a>
          </li>
          <li>
            <a href="yields.php" class="nav-item <?= ($current_page === 'yields.php') ? 'active' : '' ?>">
              🧪 ผลผลิต
            </a>
          </li>
          <li>
            <a href="setup.php" class="nav-item <?= ($current_page === 'setup.php') ? 'active' : '' ?>">
              🗄️ ฐานข้อมูล
            </a>
          </li>
          <li>
            <a href="contact.php" class="nav-item <?= ($current_page === 'contact.php') ? 'active' : '' ?>">
              📞 ติดต่อเรา
            </a>
          </li>
          <li>
            <a href="login.php" class="nav-item <?= ($current_page === 'login.php') ? 'active' : '' ?>">
              👤 เข้าสู่ระบบ
            </a>
          </li>
        </ul>
      </nav>

      <!-- Right Nav Actions -->
      <div class="nav-actions">
        <!-- Quick Role Switcher -->
        <div style="display: flex; align-items: center; gap: 4px; background: var(--bg-main); border: 1px solid var(--border-subtle); padding: 3px 6px; border-radius: var(--radius-pill); font-size: 11px;">
          <button onclick="App.switchRole('admin')" class="btn btn-sm <?= $current_role === 'admin' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 2px 8px; font-size: 11px; height: 26px;">
            Admin
          </button>
          <button onclick="App.switchRole('farmer')" class="btn btn-sm <?= $current_role === 'farmer' ? 'btn-primary' : 'btn-outline' ?>" style="padding: 2px 8px; font-size: 11px; height: 26px;">
            Farmer
          </button>
        </div>

        <div style="text-align: right;">
          <div id="user-name-display" style="font-size: 0.85rem; font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($user_name) ?></div>
          <span id="user-role-badge" class="role-badge <?= $current_role === 'admin' ? 'role-admin' : 'role-farmer' ?>">
            <?= $current_role === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'เกษตรกร (Farmer)' ?>
          </span>
        </div>
      </div>

    </div>
  </header>
