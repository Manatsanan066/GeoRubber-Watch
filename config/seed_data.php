<?php
/**
 * GeoRubber Watch - Database Seed Data Generator
 * Seeds tables and realistic spatial & attribute data around PSU Surat Thani
 */

function seedDatabase($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // 1. Create Tables based on active Database Engine
    if ($driver === 'pgsql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                role VARCHAR(20) DEFAULT 'farmer',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS farmers (
                id SERIAL PRIMARY KEY,
                user_id INTEGER,
                farmer_code VARCHAR(30) UNIQUE NOT NULL,
                prefix VARCHAR(20) DEFAULT 'นาย',
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                id_card_num VARCHAR(20),
                phone VARCHAR(20),
                address TEXT,
                subdistrict VARCHAR(50) DEFAULT 'มะขามเตี้ย',
                district VARCHAR(50) DEFAULT 'เมืองสุราษฎร์ธานี',
                province VARCHAR(50) DEFAULT 'สุราษฎร์ธานี',
                postal_code VARCHAR(10) DEFAULT '84000',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS forest_reserves (
                id SERIAL PRIMARY KEY,
                forest_code VARCHAR(30) UNIQUE NOT NULL,
                name_th VARCHAR(150) NOT NULL,
                name_en VARCHAR(150),
                category VARCHAR(50) DEFAULT 'ป่าสงวนแห่งชาติ',
                geojson_geometry TEXT NOT NULL,
                area_rai NUMERIC(12, 2) DEFAULT 0,
                color_code VARCHAR(20) DEFAULT '#0e4d4e',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS rubber_plots (
                id SERIAL PRIMARY KEY,
                plot_code VARCHAR(30) UNIQUE NOT NULL,
                farmer_id INTEGER NOT NULL REFERENCES farmers(id) ON DELETE CASCADE,
                plot_name VARCHAR(100) NOT NULL,
                title_deed_type VARCHAR(50) DEFAULT 'โฉนดที่ดิน (น.ส. 4 จ)',
                title_deed_no VARCHAR(50),
                geojson_geometry TEXT NOT NULL,
                centroid_lat NUMERIC(10, 7) NOT NULL,
                centroid_lng NUMERIC(10, 7) NOT NULL,
                area_rai INTEGER DEFAULT 0,
                area_ngan INTEGER DEFAULT 0,
                area_sqwah NUMERIC(8, 2) DEFAULT 0,
                area_sqm NUMERIC(12, 2) DEFAULT 0,
                area_hectare NUMERIC(10, 4) DEFAULT 0,
                rubber_clone VARCHAR(50) DEFAULT 'RRIM 600',
                planting_year INTEGER DEFAULT 2018,
                tree_count INTEGER DEFAULT 300,
                trees_per_rai INTEGER DEFAULT 76,
                tapping_status VARCHAR(20) DEFAULT 'tapping',
                eudr_status VARCHAR(30) DEFAULT 'compliant',
                eudr_overlap_pct NUMERIC(5, 2) DEFAULT 0,
                eudr_deforestation_free SMALLINT DEFAULT 1,
                eudr_cutoff_compliant SMALLINT DEFAULT 1,
                eudr_verified_at TIMESTAMP WITH TIME ZONE,
                traceability_token VARCHAR(64) UNIQUE NOT NULL,
                notes TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS yield_logs (
                id SERIAL PRIMARY KEY,
                plot_id INTEGER NOT NULL REFERENCES rubber_plots(id) ON DELETE CASCADE,
                farmer_id INTEGER NOT NULL REFERENCES farmers(id) ON DELETE CASCADE,
                harvest_date DATE NOT NULL,
                tapping_round INTEGER DEFAULT 1,
                fresh_latex_kg NUMERIC(10, 2) NOT NULL,
                drc_percent NUMERIC(5, 2) DEFAULT 33.5,
                dry_rubber_kg NUMERIC(10, 2) NOT NULL,
                price_per_kg NUMERIC(8, 2) DEFAULT 65.0,
                total_revenue NUMERIC(12, 2) NOT NULL,
                buyer_name VARCHAR(150) DEFAULT 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด',
                notes TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS traceability_batches (
                id SERIAL PRIMARY KEY,
                batch_code VARCHAR(50) UNIQUE NOT NULL,
                plot_id INTEGER NOT NULL REFERENCES rubber_plots(id) ON DELETE CASCADE,
                harvest_start_date DATE NOT NULL,
                harvest_end_date DATE NOT NULL,
                total_weight_kg NUMERIC(10, 2) NOT NULL,
                average_drc NUMERIC(5, 2) DEFAULT 33.0,
                destination_country VARCHAR(100) DEFAULT 'European Union (EU)',
                export_cert_no VARCHAR(50),
                qr_token VARCHAR(64) UNIQUE NOT NULL,
                dds_status VARCHAR(50) DEFAULT 'Verified EUDR Compliant',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                role VARCHAR(20) DEFAULT 'farmer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS farmers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                farmer_code VARCHAR(30) UNIQUE NOT NULL,
                prefix VARCHAR(20) DEFAULT 'นาย',
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                id_card_num VARCHAR(20),
                phone VARCHAR(20),
                address TEXT,
                subdistrict VARCHAR(50) DEFAULT 'มะขามเตี้ย',
                district VARCHAR(50) DEFAULT 'เมืองสุราษฎร์ธานี',
                province VARCHAR(50) DEFAULT 'สุราษฎร์ธานี',
                postal_code VARCHAR(10) DEFAULT '84000',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS forest_reserves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                forest_code VARCHAR(30) UNIQUE NOT NULL,
                name_th VARCHAR(100) NOT NULL,
                name_en VARCHAR(100),
                category VARCHAR(50) DEFAULT 'ป่าสงวนแห่งชาติ',
                geojson_geometry TEXT NOT NULL,
                area_rai REAL DEFAULT 0,
                color_code VARCHAR(20) DEFAULT '#ef4444',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS rubber_plots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plot_code VARCHAR(30) UNIQUE NOT NULL,
                farmer_id INTEGER NOT NULL,
                plot_name VARCHAR(100) NOT NULL,
                title_deed_type VARCHAR(50) DEFAULT 'โฉนดที่ดิน (น.ส. 4 จ)',
                title_deed_no VARCHAR(50),
                geojson_geometry TEXT NOT NULL,
                centroid_lat REAL NOT NULL,
                centroid_lng REAL NOT NULL,
                area_rai INTEGER DEFAULT 0,
                area_ngan INTEGER DEFAULT 0,
                area_sqwah REAL DEFAULT 0,
                area_sqm REAL DEFAULT 0,
                area_hectare REAL DEFAULT 0,
                rubber_clone VARCHAR(50) DEFAULT 'RRIM 600',
                planting_year INTEGER DEFAULT 2018,
                tree_count INTEGER DEFAULT 300,
                trees_per_rai INTEGER DEFAULT 76,
                tapping_status VARCHAR(20) DEFAULT 'tapping',
                eudr_status VARCHAR(30) DEFAULT 'compliant',
                eudr_overlap_pct REAL DEFAULT 0,
                eudr_deforestation_free INTEGER DEFAULT 1,
                eudr_cutoff_compliant INTEGER DEFAULT 1,
                eudr_verified_at DATETIME,
                traceability_token VARCHAR(64) UNIQUE NOT NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS yield_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plot_id INTEGER NOT NULL,
                farmer_id INTEGER NOT NULL,
                harvest_date DATE NOT NULL,
                tapping_round INTEGER DEFAULT 1,
                fresh_latex_kg REAL NOT NULL,
                drc_percent REAL DEFAULT 33.5,
                dry_rubber_kg REAL NOT NULL,
                price_per_kg REAL DEFAULT 65.0,
                total_revenue REAL NOT NULL,
                buyer_name VARCHAR(100) DEFAULT 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด',
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (plot_id) REFERENCES rubber_plots(id) ON DELETE CASCADE,
                FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS traceability_batches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                batch_code VARCHAR(50) UNIQUE NOT NULL,
                plot_id INTEGER NOT NULL,
                harvest_start_date DATE NOT NULL,
                harvest_end_date DATE NOT NULL,
                total_weight_kg REAL NOT NULL,
                average_drc REAL DEFAULT 33.0,
                destination_country VARCHAR(100) DEFAULT 'European Union (EU)',
                export_cert_no VARCHAR(50),
                qr_token VARCHAR(64) UNIQUE NOT NULL,
                dds_status VARCHAR(30) DEFAULT 'Verified EUDR Compliant',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (plot_id) REFERENCES rubber_plots(id) ON DELETE CASCADE
            );
        ");
    }

    // 2. Insert Users (Admin & Farmers)
    $password_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $password_farmer = password_hash('farmer123', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', $password_admin, 'ดร. สุพัตรา พุฒิเนาวรัตน์ (ผู้ดูแลระบบ GIS)', 'admin@georubber.psu.ac.th', '085-077-7847', 'admin']);
        $stmt->execute(['matinee', $password_farmer, 'นางสาวมาทินี โรยนรินทร์', '6640011044@psu.ac.th', '093-578-2399', 'farmer']);
        $stmt->execute(['manatsanan', $password_farmer, 'นางสาวมนัสนันท์ อนันตณรงค์', '6640011066@psu.ac.th', '095-331-8033', 'farmer']);
        $stmt->execute(['somchai', $password_farmer, 'นายสมชาย ยางเจริญสุข', 'somchai.rubber@gmail.com', '081-234-5678', 'farmer']);
    } catch (Exception $e) {
        // Users already exist
    }

    // 3. Insert Farmer Profiles (10 Farmers matching Supabase)
    try {
        $stmt = $pdo->prepare("INSERT INTO farmers (id, user_id, farmer_code, prefix, first_name, last_name, id_card_num, phone, address, subdistrict, district, province, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $farmers_data = [
            [1, 2, 'FM-PSU-001', 'นางสาว', 'มาทินี', 'โรยนรินทร์', '1849900123451', '093-578-2399', '31 หมู่ 6 มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [2, 3, 'FM-PSU-002', 'นางสาว', 'มนัสนันท์', 'อนันตณรงค์', '1849900234562', '095-331-8033', '31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [3, 4, 'FM-PSU-003', 'นาย', 'สมชาย', 'ยางเจริญสุข', '1849900345673', '081-234-5678', '145 หมู่ 2 ต.ขุนทะเล อ.เมือง', 'ขุนทะเล', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [5, null, 'FM-PSU-004', 'นางสาว', 'โอ', 'ชิ', '1849900567890', '089-111-2233', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [6, null, 'FM-PSU-005', 'นาย', 'โซโซ', 'อนันตณรงค์', '1849900678901', '089-222-3344', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [7, null, 'FM-PSU-006', 'นางสาว', 'ดาว', 'นิล', '1849900789012', '089-333-4455', 'ต.ไชยา อ.ไชยา', 'ไชยา', 'ไชยา', 'สุราษฎร์ธานี', '84110'],
            [8, null, 'FM-PSU-007', 'นาย', 'วุฒิพงษ์', 'แดงบรรจง', '1849900890123', '089-444-5566', 'ต.ทุ่งเตา อ.บ้านนาสาร', 'ทุ่งเตา', 'บ้านนาสาร', 'สุราษฎร์ธานี', '84120'],
            [9, null, 'FM-PSU-008', 'นางสาว', 'น้ำตาล', 'อนันตณรงค์', '1849900901234', '089-555-6677', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [10, null, 'FM-PSU-009', 'นาย', 'นักศึกษาสาขาการยาง', 'อนันตณรงค์', '1849901012345', '089-666-7788', '31 ม.6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
            [11, null, 'FM-PSU-010', 'นาย', 'สุชาติ', 'อนันตณรงค์', '1849901123456', '089-777-8899', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000']
        ];
        foreach ($farmers_data as $fd) {
            $stmt->execute($fd);
        }
    } catch (Exception $e) {
        // Farmers already exist
    }

    // 4. Insert 26 National Forest Reserves in Surat Thani (Zone C)
    try {
        $zoneCPath = dirname(__DIR__) . '/Zone-c.geojson';
        if (file_exists($zoneCPath)) {
            $rawGeo = json_decode(file_get_contents($zoneCPath), true);
            if ($rawGeo && isset($rawGeo['features'])) {
                $enNames = [
                    'R1.001' => 'Khao Phuttha Thong Forest Reserve',
                    'R1.002' => 'Thung Rang, Khuan Siat, Bok Kai Fa & Khlong Kong Chang Forest Reserve',
                    'R1.003' => 'Khlong Sin Pun Forest Reserve',
                    'R1.004' => 'Khlong Yan Forest Reserve',
                    'R1.005' => 'Wat Pradu Forest Reserve',
                    'R1.006' => 'Hin Lat Waterfall Forest Reserve',
                    'R1.007' => 'Don Sak Mangrove Forest Reserve',
                    'R1.008' => 'Khlong Nam Thao Forest Reserve',
                    'R1.009' => 'Khao Phlu Forest Reserve',
                    'R1.010' => 'Tha Chang Mangrove Forest Reserve',
                    'R1.011' => 'Khao Tha Phet Forest Reserve',
                    'R1.012' => 'Koh Pha-ngan Forest Reserve',
                    'R1.013' => 'Ban Na, Tha Ruea & Khian Sa Forest Reserve',
                    'R1.014' => 'Tha Chana Forest Reserve',
                    'R1.015' => 'Chaiya Khram & Wat Pradu Forest Reserve',
                    'R1.016' => 'Bang Bao & Khlong Siat Forest Reserve',
                    'R1.017' => 'Khlong Tha Nian & Phum Riang Mangrove Forest Reserve',
                    'R1.018' => 'Thung Sai Chai Forest Reserve',
                    'R1.019' => 'Tha Khoei, Khlong Sai, Maluan & Bang Ngon Forest Reserve',
                    'R1.020' => 'Ban Mak & Pak Phang Forest Reserve',
                    'R1.021' => 'Khlong Sok & Khlong Phanom Forest Reserve',
                    'R1.022' => 'Yan Yao, Khao Wong & Krasum Forest Reserve',
                    'R1.023' => 'Sai Thon & Khlong Song Forest Reserve',
                    'R1.024' => 'Chaiya Khram & Wat Pradu (Plot 2) Forest Reserve',
                    'R1.025' => 'Khao Phanom & Phlu Thuean Forest Reserve',
                    'R1.026' => 'Khao Daeng Ram & Khao Na Rahu Forest Reserve'
                ];

                $colors = [
                    '#dc2626', '#ea580c', '#d97706', '#ca8a04', '#65a30d',
                    '#16a34a', '#059669', '#0d9488', '#0891b2', '#0284c7',
                    '#2563eb', '#4f46e5', '#7c3aed', '#9333ea', '#c026d3',
                    '#db2777', '#e11d48', '#b91c1c', '#c2410c', '#b45309',
                    '#4d7c0f', '#15803d', '#047857', '#0f766e', '#0369a1', '#1d4ed8'
                ];

                $stmt = $pdo->prepare("INSERT INTO forest_reserves (forest_code, name_th, name_en, category, geojson_geometry, area_rai, color_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($rawGeo['features'] as $idx => $f) {
                    $p = $f['properties'] ?? [];
                    $code = $p['NRF_CODE'] ?? sprintf('R1.%03d', $idx + 1);
                    $nameTh = $p['FR_NAME'] ?? ('ป่าสงวนแห่งชาติ ' . $code);
                    $nameEn = $enNames[$code] ?? ($nameTh . ' Forest Reserve');
                    $category = 'Zone-C ป่าสงวนแห่งชาติ';
                    $areaRai = (float)($p['AREA_RAI'] ?? ($p['rai_GIS'] ?? 0));
                    $colorCode = $colors[$idx % count($colors)];
                    $stmt->execute([$code, $nameTh, $nameEn, $category, json_encode($f['geometry']), $areaRai, $colorCode]);
                }
            }
        }
    } catch (Exception $e) {
        // Forests exist
    }

    // 5. Insert Rubber Plots (9 Plots matching Supabase)
    try {
        $plot4_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3580, 9.0965],
                [99.3620, 9.0990],
                [99.3635, 9.0955],
                [99.3592, 9.0940],
                [99.3580, 9.0965]
            ]]
        ]);

        $plot7_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3480, 9.0880],
                [99.3520, 9.0895],
                [99.3530, 9.0865],
                [99.3490, 9.0850],
                [99.3480, 9.0880]
            ]]
        ]);

        $plot8_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3560, 9.0920],
                [99.3610, 9.0935],
                [99.3620, 9.0895],
                [99.3570, 9.0880],
                [99.3560, 9.0920]
            ]]
        ]);

        $plot9_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3650, 9.1020],
                [99.3700, 9.1035],
                [99.3710, 9.0990],
                [99.3660, 9.0975],
                [99.3650, 9.1020]
            ]]
        ]);

        $plot11_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.1980, 9.3850],
                [99.2020, 9.3865],
                [99.2030, 9.3830],
                [99.1990, 9.3815],
                [99.1980, 9.3850]
            ]]
        ]);

        $plot13_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3750, 8.8750],
                [99.3780, 8.8765],
                [99.3790, 8.8735],
                [99.3760, 8.8720],
                [99.3750, 8.8750]
            ]]
        ]);

        $plot15_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3350, 9.0780],
                [99.3390, 9.0795],
                [99.3400, 9.0760],
                [99.3360, 9.0745],
                [99.3350, 9.0780]
            ]]
        ]);

        $plot16_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3590, 9.0970],
                [99.3630, 9.0985],
                [99.3640, 9.0950],
                [99.3600, 9.0935],
                [99.3590, 9.0970]
            ]]
        ]);

        $plot17_geo = json_encode([
            "type" => "Polygon",
            "coordinates" => [[
                [99.3510, 9.0830],
                [99.3550, 9.0845],
                [99.3560, 9.0810],
                [99.3520, 9.0795],
                [99.3510, 9.0830]
            ]]
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO rubber_plots (
                id, plot_code, farmer_id, plot_name, title_deed_type, title_deed_no,
                geojson_geometry, centroid_lat, centroid_lng, area_rai, area_ngan,
                area_sqwah, area_sqm, area_hectare, rubber_clone, planting_year,
                tree_count, trees_per_rai, tapping_status, eudr_status, eudr_overlap_pct,
                eudr_deforestation_free, eudr_cutoff_compliant, eudr_verified_at, traceability_token, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $plots_data = [
            [
                4, 'RBST-2026-004', 2, 'สวนยางชายขอบเขาท่าเพชร (Plot D)',
                'หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01', 'สปก-8401-9921',
                $plot4_geo, 9.0963, 99.3607, 14, 0, 80.0, 22720.0, 2.272,
                'BPM 24', 2021, 1050, 75, 'tapping', 'non_compliant', 28.5,
                0, 0, '2026-03-01 16:45:00', 'EUDR-TH-ST-84000-004-D6C66', 'แจ้งเตือน: พบการทับซ้อนแนวเขตป่าสงวน 28.5% ต้องตรวจสอบเอกสารสิทธิ์'
            ],
            [
                7, 'RB-ST-2026-006', 6, 'แปลงปลูกริมทาง',
                'โฉนดที่ดิน (น.ส. 4 จ)', '1234-5678',
                $plot7_geo, 9.0872, 99.3505, 35, 0, 9.25, 56037.0, 5.6037,
                'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.0,
                1, 1, '2026-03-05 10:00:00', 'EUDR-TH-ST-84000-006-2580E8', 'แปลงยางพาราสอดคล้องตามเกณฑ์ EUDR'
            ],
            [
                8, 'RB-ST-2026-008', 2, 'แปลงยางพาราเขาท่าเพชร 1',
                'โฉนดที่ดิน (น.ส. 4 จ)', '4444-5555',
                $plot8_geo, 9.0907, 99.3590, 55, 1, 92.13, 88768.5, 8.8769,
                'RRIM 600', 2018, 300, 76, 'tapping', 'under_review', 0.0,
                1, 1, '2026-03-05 10:15:00', 'EUDR-TH-ST-84000-008-B60FF1', 'โซนเฝ้าระวัง Buffer Zone ใกล้แนวเขตป่าสงวน'
            ],
            [
                9, 'RB-ST-2026-009', 1, 'แปลงปลูกข้างบนเขา',
                'โฉนดที่ดิน (น.ส. 4 จ)', '1111-2222',
                $plot9_geo, 9.1005, 99.3680, 66, 2, 92.63, 106770.5, 10.6771,
                'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.0,
                1, 1, '2026-03-05 10:30:00', 'EUDR-TH-ST-84000-009-174823', 'แปลงยางพาราผ่านเกณฑ์ปลอดการตัดไม้ทำลายป่า'
            ],
            [
                11, 'RB-ST-2026-011', 7, 'แปลงปลูกไชยา',
                'โฉนดที่ดิน (น.ส. 4 จ)', '4444-5555',
                $plot11_geo, 9.3840, 99.2005, 20, 1, 58.90, 32635.6, 3.2636,
                'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.0,
                1, 1, '2026-03-05 10:45:00', 'EUDR-TH-ST-84000-011-06786B', 'แปลงปลูก อ.ไชยา ผ่านการรับรอง EUDR'
            ],
            [
                13, 'RB-ST-2026-012', 8, 'แปลงยางทุ่งเตา',
                'โฉนดที่ดิน (น.ส. 4 จ)', '3456-5678',
                $plot13_geo, 8.8742, 99.3770, 7, 1, 12.58, 11650.3, 1.1650,
                'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.0,
                1, 1, '2026-03-05 11:00:00', 'EUDR-TH-ST-84000-012-780C26', 'แปลงยางพารา ต.ทุ่งเตา อ.บ้านนาสาร'
            ],
            [
                15, 'RB-ST-2026-014', 9, 'แปลงยางพาราซอยสิบ',
                'โฉนดที่ดิน (น.ส. 4 จ)', '1111-2222',
                $plot15_geo, 9.0770, 99.3375, 22, 2, 51.41, 36205.6, 3.6206,
                'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.0,
                1, 1, '2026-03-05 11:15:00', 'EUDR-TH-ST-84000-014-F2D725', 'แปลงยางพาราซอยสิบ สุราษฎร์ธานี'
            ],
            [
                16, 'RB-ST-2026-016', 10, 'แปลงยางทดลองนักศึกษาการยาง',
                'โฉนดที่ดิน (น.ส. 4 จ)', '9999-8888',
                $plot16_geo, 9.0960, 99.3615, 12, 3, 17.10, 20468.4, 2.0468,
                'RRIM 600', 2018, 300, 76, 'tapping', 'non_compliant', 22.0,
                0, 0, '2026-03-05 11:30:00', 'EUDR-TH-ST-84000-016-17F815', 'แปลงทดลองนักศึกษา ตรวจพบการทับซ้อนแนวเขตป่า'
            ],
            [
                17, 'RB-ST-2026-017', 11, 'แปลงยางพาราใกล้มหาลัย',
                'โฉนดที่ดิน (น.ส. 4 จ)', '66666-7777',
                $plot17_geo, 9.0822, 99.3535, 23, 1, 18.70, 37274.8, 3.7275,
                'RRIM 600', 2018, 300, 76, 'tapping', 'under_review', 0.0,
                1, 1, '2026-03-05 11:45:00', 'EUDR-TH-ST-84000-017-B5F194', 'แปลงยางพาราใกล้มหาวิทยาลัยสงขลานครินทร์'
            ]
        ];

        foreach ($plots_data as $pd) {
            $stmt->execute($pd);
        }
    } catch (Exception $e) {
        // Plots exist
    }

    // 6. Insert Latex Yield Logs (Recent harvest batches)
    try {
        $yield_stmt = $pdo->prepare("
            INSERT INTO yield_logs (plot_id, farmer_id, harvest_date, tapping_round, fresh_latex_kg, drc_percent, dry_rubber_kg, price_per_kg, total_revenue, buyer_name, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $sample_yields = [
            [7, 6, '2026-08-01', 1, 95.0, 34.0, 32.30, 68.50, 2212.55, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'น้ำยางสดคุณภาพดี ไม่มีฝนตก'],
            [7, 6, '2026-08-03', 2, 98.5, 33.5, 33.00, 69.00, 2277.00, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'กรีดช่วงเวลา 03:00 น.'],
            [7, 6, '2026-08-06', 3, 92.0, 35.0, 32.20, 69.50, 2237.90, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'DRC สูงเป็นพิเศษ'],
            [7, 6, '2026-08-09', 4, 102.0, 34.2, 34.88, 70.00, 2441.60, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'ส่งออกล็อต EUDR-EU'],
            [7, 6, '2026-08-12', 5, 96.0, 33.8, 32.45, 70.50, 2287.73, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'รอบกรีดประจำสัปดาห์'],
            
            [9, 1, '2026-08-02', 1, 115.0, 33.0, 37.95, 68.50, 2599.58, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'แปลงข้างบนเขา ให้น้ำยางสม่ำเสมอ'],
            [9, 1, '2026-08-05', 2, 118.0, 33.5, 39.53, 69.00, 2727.57, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'ยอดเก็บเกี่ยวล็อต A'],
            [9, 1, '2026-08-08', 3, 112.5, 34.0, 38.25, 69.50, 2658.38, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'คุณภาพเยี่ยม'],
            [9, 1, '2026-08-11', 4, 120.0, 33.2, 39.84, 70.00, 2788.80, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'ส่งโรงงานแปรรูปยางแผ่น'],

            [11, 7, '2026-08-01', 1, 180.0, 34.5, 62.10, 68.50, 4253.85, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'แปลงไชยา คุณภาพสูง'],
            [11, 7, '2026-08-04', 2, 185.0, 34.0, 62.90, 69.00, 4340.10, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'พร้อมส่งออก EU'],
            [11, 7, '2026-08-07', 3, 178.0, 35.0, 62.30, 69.50, 4329.85, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'มาตรฐานสากล'],
            [11, 7, '2026-08-10', 4, 192.0, 33.8, 64.90, 70.00, 4543.00, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'รอบกรีดสมบูรณ์'],

            [13, 8, '2026-08-02', 1, 210.0, 34.0, 71.40, 68.50, 4890.90, 'สหกรณ์การเกษตรทุ่งเตา จำกัด', 'แปลงทุ่งเตา'],
            [13, 8, '2026-08-06', 2, 215.0, 34.5, 74.18, 69.50, 5155.51, 'สหกรณ์การเกษตรทุ่งเตา จำกัด', 'ยางอินทรีย์ทุ่งเตา'],
            [13, 8, '2026-08-10', 3, 208.0, 35.0, 72.80, 70.50, 5132.40, 'สหกรณ์การเกษตรทุ่งเตา จำกัด', 'EUDR Certified Batch']
        ];

        foreach ($sample_yields as $y) {
            $yield_stmt->execute($y);
        }
    } catch (Exception $e) {
        // Yields exist
    }

    // 7. Insert Traceability Batches
    try {
        $batch_stmt = $pdo->prepare("
            INSERT INTO traceability_batches (batch_code, plot_id, harvest_start_date, harvest_end_date, total_weight_kg, average_drc, destination_country, export_cert_no, qr_token, dds_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $batch_stmt->execute([
            'BATCH-2026-EU-001', 7, '2026-08-01', '2026-08-12', 484.5, 34.1, 'Germany (EU)', 'EXP-TH-EUDR-84001', 'BATCH-QR-PSU-001-XYZ', 'EUDR Verified & Due Diligence Cleared'
        ]);
        $batch_stmt->execute([
            'BATCH-2026-EU-002', 9, '2026-08-01', '2026-08-11', 465.5, 33.4, 'France (EU)', 'EXP-TH-EUDR-84002', 'BATCH-QR-PSU-002-ABC', 'EUDR Verified & Due Diligence Cleared'
        ]);
        $batch_stmt->execute([
            'BATCH-2026-EU-003', 13, '2026-08-01', '2026-08-10', 633.0, 34.5, 'Italy (EU)', 'EXP-TH-EUDR-84005', 'BATCH-QR-PSU-005-OPQ', 'EUDR Verified & Due Diligence Cleared'
        ]);
    } catch (Exception $e) {
        // Batches exist
    }
}
