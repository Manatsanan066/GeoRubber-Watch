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

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $password_admin, 'ดร. สุพัตรา พุฒิเนาวรัตน์ (ผู้ดูแลระบบ GIS)', 'admin@georubber.psu.ac.th', '085-077-7847', 'admin']);
    $stmt->execute(['matinee', $password_farmer, 'นางสาวมาทินี โรยนรินทร์', '6640011044@psu.ac.th', '093-578-2399', 'farmer']);
    $stmt->execute(['manatsanan', $password_farmer, 'นางสาวมนัสนันท์ อนันตณรงค์', '6640011066@psu.ac.th', '095-331-8033', 'farmer']);
    $stmt->execute(['somchai', $password_farmer, 'นายสมชาย ยางเจริญสุข', 'somchai.rubber@gmail.com', '081-234-5678', 'farmer']);

    // 3. Insert Farmer Profiles
    $stmt = $pdo->prepare("INSERT INTO farmers (user_id, farmer_code, prefix, first_name, last_name, id_card_num, phone, address, subdistrict, district, province, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([2, 'FM-PSU-001', 'นางสาว', 'มาทินี', 'โรยนรินทร์', '1849900123451', '093-578-2399', '31 หมู่ 6 มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000']);
    $stmt->execute([3, 'FM-PSU-002', 'นางสาว', 'มนัสนันท์', 'อนันตณรงค์', '1849900234562', '095-331-8033', '31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000']);
    $stmt->execute([4, 'FM-PSU-003', 'นาย', 'สมชาย', 'ยางเจริญสุข', '1849900345673', '081-234-5678', '145 หมู่ 2 ต.ขุนทะเล อ.เมือง', 'ขุนทะเล', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000']);

    // 4. Insert Simulated Forest Reserves in Surat Thani (Zone around PSU / Khao Tha Phet)
    $forest1_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3590, 9.0980],
            [99.3750, 9.1020],
            [99.3820, 9.0850],
            [99.3690, 9.0790],
            [99.3590, 9.0980]
        ]]
    ]);

    $forest2_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3300, 9.0650],
            [99.3450, 9.0680],
            [99.3480, 9.0520],
            [99.3280, 9.0510],
            [99.3300, 9.0650]
        ]]
    ]);

    $stmt = $pdo->prepare("INSERT INTO forest_reserves (forest_code, name_th, name_en, category, geojson_geometry, area_rai, color_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['FOR-ST-001', 'เขตป่าสงวนแห่งชาติเขาท่าเพชร (จำลอง)', 'Khao Tha Phet National Forest Reserve (Simulated)', 'ป่าสงวนแห่งชาติ', $forest1_geo, 2450.75, '#ef4444']);
    $stmt->execute(['FOR-ST-002', 'เขตป่าคุ้มครองต้นน้ำขุนทะเล (จำลอง)', 'Khun Thale Watershed Protected Forest (Simulated)', 'ป่าคุ้มครองแหล่งน้ำ', $forest2_geo, 1820.30, '#dc2626']);

    // 5. Insert Rubber Plots around Prince of Songkla University, Surat Thani Campus (9.0799, 99.3512)
    
    // Plot 1: PSU Experimental Rubber Plot A (Compliant)
    $plot1_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3502, 9.0815],
            [99.3525, 9.0822],
            [99.3531, 9.0798],
            [99.3508, 9.0792],
            [99.3502, 9.0815]
        ]]
    ]);
    
    // Plot 2: Matinee Sustainable Plot B (Compliant)
    $plot2_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3460, 9.0840],
            [99.3485, 9.0848],
            [99.3490, 9.0825],
            [99.3465, 9.0818],
            [99.3460, 9.0840]
        ]]
    ]);

    // Plot 3: Makham Tia High-Yield Plot C (Compliant)
    $plot3_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3540, 9.0870],
            [99.3575, 9.0878],
            [99.3582, 9.0850],
            [99.3546, 9.0842],
            [99.3540, 9.0870]
        ]]
    ]);

    // Plot 4: Forest Border At-Risk Plot D (Partially Intersects Forest Reserve 1)
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

    // Plot 5: Khun Thale Model Plot E (Compliant)
    $plot5_geo = json_encode([
        "type" => "Polygon",
        "coordinates" => [[
            [99.3410, 9.0750],
            [99.3448, 9.0758],
            [99.3455, 9.0725],
            [99.3418, 9.0718],
            [99.3410, 9.0750]
        ]]
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO rubber_plots (
            plot_code, farmer_id, plot_name, title_deed_type, title_deed_no,
            geojson_geometry, centroid_lat, centroid_lng, area_rai, area_ngan,
            area_sqwah, area_sqm, area_hectare, rubber_clone, planting_year,
            tree_count, trees_per_rai, tapping_status, eudr_status, eudr_overlap_pct,
            eudr_deforestation_free, eudr_cutoff_compliant, eudr_verified_at, traceability_token, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        'RB-ST-2026-001', 1, 'แปลงยางทดสอบอัจฉริยะ ม.อ. สุราษฎร์ฯ (Plot A)',
        'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-4458',
        $plot1_geo, 9.0807, 99.3517, 6, 2, 45.0, 10580.0, 1.058,
        'RRIM 600', 2018, 520, 80, 'tapping', 'compliant', 0.0,
        1, 1, '2026-01-15 10:30:00', 'EUDR-TH-ST-84000-001-A9F32', 'แปลงทดลองระบบ EUDR ไม่พบการบุกรุกป่าสงวน'
    ]);

    $stmt->execute([
        'RB-ST-2026-002', 1, 'สวนยางพาราสายรุ้ง ป่ามะพร้าว (Plot B)',
        'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-7892',
        $plot2_geo, 9.0830, 99.3475, 7, 1, 12.0, 11648.0, 1.165,
        'RRIT 251', 2019, 580, 78, 'tapping', 'compliant', 0.0,
        1, 1, '2026-02-01 14:20:00', 'EUDR-TH-ST-84000-002-B8E44', 'ผลผลิตคุณภาพสูง ผ่านการประเมินความยั่งยืน'
    ]);

    $stmt->execute([
        'RB-ST-2026-003', 2, 'สวนยางพารามะขามเตี้ย ยั่งยืน (Plot C)',
        'หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)', 'นส3ก-8401-1024',
        $plot3_geo, 9.0860, 99.3560, 11, 3, 50.0, 18900.0, 1.890,
        'RRIT 452', 2017, 910, 76, 'tapping', 'compliant', 0.0,
        1, 1, '2026-02-10 09:15:00', 'EUDR-TH-ST-84000-003-C7D55', 'แปลงใหญ่ บันทึกผลผลิตต่อเนื่อง'
    ]);

    $stmt->execute([
        'RB-ST-2026-004', 2, 'สวนยางชายขอบเขาท่าเพชร (Plot D)',
        'หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01', 'สปก-8401-9921',
        $plot4_geo, 9.0963, 99.3607, 14, 0, 80.0, 22720.0, 2.272,
        'BPM 24', 2021, 1050, 75, 'tapping', 'non_compliant', 28.5,
        0, 0, '2026-03-01 16:45:00', 'EUDR-TH-ST-84000-004-D6C66', 'แจ้งเตือน: พบการทับซ้อนแนวเขตป่าสงวน 28.5% ต้องตรวจสอบเอกสารสิทธิ์'
    ]);

    $stmt->execute([
        'RB-ST-2026-005', 3, 'สวนยางขุนทะเลอินทรีย์ (Plot E)',
        'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-3329',
        $plot5_geo, 9.0738, 99.3433, 13, 2, 10.0, 21640.0, 2.164,
        'RRIM 600', 2016, 1020, 75, 'tapping', 'compliant', 0.0,
        1, 1, '2026-03-10 11:00:00', 'EUDR-TH-ST-84000-005-E5B77', 'แปลงยางเกษตรกรต้นแบบ ได้มาตรฐาน GAP และ EUDR'
    ]);

    // 6. Insert Latex Yield Logs (Recent harvest batches)
    $yield_stmt = $pdo->prepare("
        INSERT INTO yield_logs (plot_id, farmer_id, harvest_date, tapping_round, fresh_latex_kg, drc_percent, dry_rubber_kg, price_per_kg, total_revenue, buyer_name, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $sample_yields = [
        [1, 1, '2026-08-01', 1, 95.0, 34.0, 32.30, 68.50, 2212.55, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'น้ำยางสดคุณภาพดี ไม่มีฝนตก'],
        [1, 1, '2026-08-03', 2, 98.5, 33.5, 33.00, 69.00, 2277.00, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'กรีดช่วงเวลา 03:00 น.'],
        [1, 1, '2026-08-06', 3, 92.0, 35.0, 32.20, 69.50, 2237.90, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'DRC สูงเป็นพิเศษ'],
        [1, 1, '2026-08-09', 4, 102.0, 34.2, 34.88, 70.00, 2441.60, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'ส่งออกล็อต EUDR-EU'],
        [1, 1, '2026-08-12', 5, 96.0, 33.8, 32.45, 70.50, 2287.73, 'สหกรณ์สวนยาง ม.อ. สุราษฎร์ธานี', 'รอบกรีดประจำสัปดาห์'],
        
        [2, 1, '2026-08-02', 1, 115.0, 33.0, 37.95, 68.50, 2599.58, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'แปลง RRIT 251 ให้น้ำยางสม่ำเสมอ'],
        [2, 1, '2026-08-05', 2, 118.0, 33.5, 39.53, 69.00, 2727.57, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'ยอดเก็บเกี่ยวล็อต A'],
        [2, 1, '2026-08-08', 3, 112.5, 34.0, 38.25, 69.50, 2658.38, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'คุณภาพเยี่ยม'],
        [2, 1, '2026-08-11', 4, 120.0, 33.2, 39.84, 70.00, 2788.80, 'จุดรับซื้อน้ำยางสดบ้านดอน', 'ส่งโรงงานแปรรูปยางแผ่น'],

        [3, 2, '2026-08-01', 1, 180.0, 34.5, 62.10, 68.50, 4253.85, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'แปลงใหญ่ แปลง C'],
        [3, 2, '2026-08-04', 2, 185.0, 34.0, 62.90, 69.00, 4340.10, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'พร้อมส่งออก EU'],
        [3, 2, '2026-08-07', 3, 178.0, 35.0, 62.30, 69.50, 4329.85, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'มาตรฐานสากล'],
        [3, 2, '2026-08-10', 4, 192.0, 33.8, 64.90, 70.00, 4543.00, 'บริษัท ยางพาราสุราษฎร์ เทรดดิ้ง จำกัด', 'รอบกรีดสมบูรณ์'],

        [5, 3, '2026-08-02', 1, 210.0, 34.0, 71.40, 68.50, 4890.90, 'สหกรณ์การเกษตรขุนทะเล จำกัด', 'แปลงขุนทะเลโมเดล'],
        [5, 3, '2026-08-06', 2, 215.0, 34.5, 74.18, 69.50, 5155.51, 'สหกรณ์การเกษตรขุนทะเล จำกัด', 'ยางอินทรีย์แปลง E'],
        [5, 3, '2026-08-10', 3, 208.0, 35.0, 72.80, 70.50, 5132.40, 'สหกรณ์การเกษตรขุนทะเล จำกัด', 'EUDR Certified Batch']
    ];

    foreach ($sample_yields as $y) {
        $yield_stmt->execute($y);
    }

    // 7. Insert Traceability Batches
    $batch_stmt = $pdo->prepare("
        INSERT INTO traceability_batches (batch_code, plot_id, harvest_start_date, harvest_end_date, total_weight_kg, average_drc, destination_country, export_cert_no, qr_token, dds_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $batch_stmt->execute([
        'BATCH-2026-EU-001', 1, '2026-08-01', '2026-08-12', 484.5, 34.1, 'Germany (EU)', 'EXP-TH-EUDR-84001', 'BATCH-QR-PSU-001-XYZ', 'EUDR Verified & Due Diligence Cleared'
    ]);
    $batch_stmt->execute([
        'BATCH-2026-EU-002', 2, '2026-08-01', '2026-08-11', 465.5, 33.4, 'France (EU)', 'EXP-TH-EUDR-84002', 'BATCH-QR-PSU-002-ABC', 'EUDR Verified & Due Diligence Cleared'
    ]);
    $batch_stmt->execute([
        'BATCH-2026-EU-003', 5, '2026-08-01', '2026-08-10', 633.0, 34.5, 'Italy (EU)', 'EXP-TH-EUDR-84005', 'BATCH-QR-PSU-005-OPQ', 'EUDR Verified & Due Diligence Cleared'
    ]);
}
