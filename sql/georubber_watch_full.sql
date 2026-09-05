-- =============================================================================
-- GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations
-- Database: georubber_watch (MySQL / MariaDB / phpMyAdmin)
-- Prince of Songkla University, Surat Thani Campus
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `georubber_watch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `georubber_watch`;

-- -----------------------------------------------------------------------------
-- 1. Users Table
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `traceability_batches`;
DROP TABLE IF EXISTS `yield_logs`;
DROP TABLE IF EXISTS `rubber_plots`;
DROP TABLE IF EXISTS `forest_reserves`;
DROP TABLE IF EXISTS `farmers`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20),
    `role` ENUM('admin', 'farmer') DEFAULT 'farmer',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Farmers Table
-- -----------------------------------------------------------------------------
CREATE TABLE `farmers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `farmer_code` VARCHAR(30) UNIQUE NOT NULL,
    `prefix` VARCHAR(20) DEFAULT 'นาย',
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `id_card_num` VARCHAR(20),
    `phone` VARCHAR(20),
    `address` TEXT,
    `subdistrict` VARCHAR(50) DEFAULT 'มะขามเตี้ย',
    `district` VARCHAR(50) DEFAULT 'เมืองสุราษฎร์ธานี',
    `province` VARCHAR(50) DEFAULT 'สุราษฎร์ธานี',
    `postal_code` VARCHAR(10) DEFAULT '84000',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_farmer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Forest Reserves Table
-- -----------------------------------------------------------------------------
CREATE TABLE `forest_reserves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `forest_code` VARCHAR(30) UNIQUE NOT NULL,
    `name_th` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100),
    `category` VARCHAR(50) DEFAULT 'ป่าสงวนแห่งชาติ',
    `geojson_geometry` LONGTEXT NOT NULL,
    `area_rai` DECIMAL(10, 2) DEFAULT 0,
    `color_code` VARCHAR(20) DEFAULT '#ef4444',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Rubber Plots Table
-- -----------------------------------------------------------------------------
CREATE TABLE `rubber_plots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plot_code` VARCHAR(30) UNIQUE NOT NULL,
    `farmer_id` INT NOT NULL,
    `plot_name` VARCHAR(100) NOT NULL,
    `title_deed_type` VARCHAR(50) DEFAULT 'โฉนดที่ดิน (น.ส. 4 จ)',
    `title_deed_no` VARCHAR(50),
    `geojson_geometry` LONGTEXT NOT NULL,
    `centroid_lat` DECIMAL(10, 7) NOT NULL,
    `centroid_lng` DECIMAL(10, 7) NOT NULL,
    `area_rai` INT DEFAULT 0,
    `area_ngan` INT DEFAULT 0,
    `area_sqwah` DECIMAL(10, 2) DEFAULT 0,
    `area_sqm` DECIMAL(12, 2) DEFAULT 0,
    `area_hectare` DECIMAL(10, 4) DEFAULT 0,
    `rubber_clone` VARCHAR(50) DEFAULT 'RRIM 600',
    `planting_year` INT DEFAULT 2018,
    `tree_count` INT DEFAULT 300,
    `trees_per_rai` INT DEFAULT 76,
    `tapping_status` ENUM('tapping', 'not_tapping') DEFAULT 'tapping',
    `eudr_status` ENUM('compliant', 'non_compliant', 'under_review') DEFAULT 'compliant',
    `eudr_overlap_pct` DECIMAL(5, 2) DEFAULT 0.0,
    `eudr_deforestation_free` TINYINT(1) DEFAULT 1,
    `eudr_cutoff_compliant` TINYINT(1) DEFAULT 1,
    `eudr_verified_at` DATETIME,
    `traceability_token` VARCHAR(64) UNIQUE NOT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_plot_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Yield Logs Table
-- -----------------------------------------------------------------------------
CREATE TABLE `yield_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plot_id` INT NOT NULL,
    `farmer_id` INT NOT NULL,
    `harvest_date` DATE NOT NULL,
    `tapping_round` INT DEFAULT 1,
    `fresh_latex_kg` DECIMAL(10, 2) NOT NULL,
    `drc_percent` DECIMAL(5, 2) DEFAULT 33.50,
    `dry_rubber_kg` DECIMAL(10, 2) NOT NULL,
    `price_per_kg` DECIMAL(8, 2) DEFAULT 65.00,
    `total_revenue` DECIMAL(12, 2) NOT NULL,
    `buyer_name` VARCHAR(100) DEFAULT 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด',
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_yield_plot` FOREIGN KEY (`plot_id`) REFERENCES `rubber_plots` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_yield_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Traceability Batches Table
-- -----------------------------------------------------------------------------
CREATE TABLE `traceability_batches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `batch_code` VARCHAR(50) UNIQUE NOT NULL,
    `plot_id` INT NOT NULL,
    `harvest_start_date` DATE NOT NULL,
    `harvest_end_date` DATE NOT NULL,
    `total_weight_kg` DECIMAL(10, 2) NOT NULL,
    `average_drc` DECIMAL(5, 2) DEFAULT 33.00,
    `destination_country` VARCHAR(100) DEFAULT 'European Union (EU)',
    `export_cert_no` VARCHAR(50),
    `qr_token` VARCHAR(64) UNIQUE NOT NULL,
    `dds_status` VARCHAR(50) DEFAULT 'Verified EUDR Compliant',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_batch_plot` FOREIGN KEY (`plot_id`) REFERENCES `rubber_plots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA INSERTION
-- =============================================================================

-- Users
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`) VALUES
(1, 'admin', '$2y$10$e8PZp.aG78eZqO01aU5R.OWtQzB9qgH2b0qG4r8t0w2r4t6y8u0ia', 'ดร. สุพัตรา พุฒิเนาวรัตน์ (ผู้ดูแลระบบ GIS)', 'admin@georubber.psu.ac.th', '085-077-7847', 'admin'),
(2, 'matinee', '$2y$10$e8PZp.aG78eZqO01aU5R.OWtQzB9qgH2b0qG4r8t0w2r4t6y8u0ia', 'นางสาวมาทินี โรยนรินทร์', '6640011044@psu.ac.th', '093-578-2399', 'farmer'),
(3, 'manatsanan', '$2y$10$e8PZp.aG78eZqO01aU5R.OWtQzB9qgH2b0qG4r8t0w2r4t6y8u0ia', 'นางสาวมนัสนันท์ อนันตณรงค์', '6640011066@psu.ac.th', '095-331-8033', 'farmer'),
(4, 'somchai', '$2y$10$e8PZp.aG78eZqO01aU5R.OWtQzB9qgH2b0qG4r8t0w2r4t6y8u0ia', 'นายสมชาย ยางเจริญสุข', 'somchai.rubber@gmail.com', '081-234-5678', 'farmer');

-- Farmers
INSERT INTO `farmers` (`id`, `user_id`, `farmer_code`, `prefix`, `first_name`, `last_name`, `id_card_num`, `phone`, `address`, `subdistrict`, `district`, `province`, `postal_code`) VALUES
(1, 2, 'FM-PSU-001', 'นางสาว', 'มาทินี', 'โรยนรินทร์', '1849900123451', '093-578-2399', '31 หมู่ 6 มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(2, 3, 'FM-PSU-002', 'นางสาว', 'มนัสนันท์', 'อนันตณรงค์', '1849900234562', '095-331-8033', '31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(3, 4, 'FM-PSU-003', 'นาย', 'สมชาย', 'ยางเจริญสุข', '1849900345673', '081-234-5678', '145 หมู่ 2 ต.ขุนทะเล อ.เมือง', 'ขุนทะเล', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(5, NULL, 'FM-PSU-004', 'นางสาว', 'โอ', 'ชิ', '1849900567890', '089-111-2233', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(6, NULL, 'FM-PSU-005', 'นาย', 'โซโซ', 'อนันตณรงค์', '1849900678901', '089-222-3344', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(7, NULL, 'FM-PSU-006', 'นางสาว', 'ดาว', 'นิล', '1849900789012', '089-333-4455', 'ต.ไชยา อ.ไชยา', 'ไชยา', 'ไชยา', 'สุราษฎร์ธานี', '84110'),
(8, NULL, 'FM-PSU-007', 'นาย', 'วุฒิพงษ์', 'แดงบรรจง', '1849900890123', '089-444-5566', 'ต.ทุ่งเตา อ.บ้านนาสาร', 'ทุ่งเตา', 'บ้านนาสาร', 'สุราษฎร์ธานี', '84120'),
(9, NULL, 'FM-PSU-008', 'นางสาว', 'น้ำตาล', 'อนันตณรงค์', '1849900901234', '089-555-6677', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(10, NULL, 'FM-PSU-009', 'นาย', 'นักศึกษาสาขาการยาง', 'อนันตณรงค์', '1849901012345', '089-666-7788', '31 ม.6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'),
(11, NULL, 'FM-PSU-010', 'นาย', 'สุชาติ', 'อนันตณรงค์', '1849901123456', '089-777-8899', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000');

-- Forest Reserves (Surat Thani)
INSERT INTO `forest_reserves` (`id`, `forest_code`, `name_th`, `name_en`, `category`, `geojson_geometry`, `area_rai`, `color_code`) VALUES
(1, 'FOR-ST-001', 'เขตป่าสงวนแห่งชาติเขาท่าเพชร', 'Khao Tha Phet National Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.3590,9.0980],[99.3750,9.1020],[99.3820,9.0850],[99.3690,9.0790],[99.3590,9.0980]]]}', 2450.75, '#ef4444'),
(2, 'FOR-ST-002', 'เขตป่าคุ้มครองต้นน้ำขุนทะเล', 'Khun Thale Watershed Protected Forest', 'ป่าคุ้มครองแหล่งน้ำ', '{"type":"Polygon","coordinates":[[[99.3300,9.0650],[99.3450,9.0680],[99.3480,9.0520],[99.3280,9.0510],[99.3300,9.0650]]]}', 1820.30, '#dc2626'),
(3, 'FOR-ST-003', 'ป่าสงวนแห่งชาติป่าคลองน้ำเฒ่า', 'Khlong Nam Thao Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.2100,8.9500],[99.2450,8.9550],[99.2500,8.9200],[99.2050,8.9150],[99.2100,8.9500]]]}', 32000.00, '#ef4444'),
(4, 'FOR-ST-004', 'ป่าสงวนแห่งชาติป่าเขานาเตย', 'Khao Na Toei Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.1200,9.2500],[99.1550,9.2550],[99.1600,9.2200],[99.1150,9.2150],[99.1200,9.2500]]]}', 15800.00, '#ef4444');

-- Rubber Plots (9 Plots matching Supabase)
INSERT INTO `rubber_plots` (`id`, `plot_code`, `farmer_id`, `plot_name`, `title_deed_type`, `title_deed_no`, `geojson_geometry`, `centroid_lat`, `centroid_lng`, `area_rai`, `area_ngan`, `area_sqwah`, `area_sqm`, `area_hectare`, `rubber_clone`, `planting_year`, `tree_count`, `trees_per_rai`, `tapping_status`, `eudr_status`, `eudr_overlap_pct`, `eudr_deforestation_free`, `eudr_cutoff_compliant`, `eudr_verified_at`, `traceability_token`, `notes`) VALUES
(4, 'RBST-2026-004', 2, 'สวนยางชายขอบเขาท่าเพชร (Plot D)', 'หนังสือแสดงสิทธิในที่ดิน ส.ป.ก. 4-01', 'สปก-8401-9921', '{"type":"Polygon","coordinates":[[[99.3580,9.0965],[99.3620,9.0990],[99.3635,9.0955],[99.3592,9.0940],[99.3580,9.0965]]]}', 9.0963, 99.3607, 14, 0, 80.00, 22720.00, 2.2720, 'BPM 24', 2021, 1050, 75, 'tapping', 'non_compliant', 28.50, 0, 0, '2026-03-01 16:45:00', 'EUDR-TH-ST-84000-004-D6C66', 'แจ้งเตือน: พบการทับซ้อนแนวเขตป่าสงวน 28.5% ต้องตรวจสอบเอกสารสิทธิ์'),
(7, 'RB-ST-2026-006', 6, 'แปลงปลูกริมทาง', 'โฉนดที่ดิน (น.ส. 4 จ)', '1234-5678', '{"type":"Polygon","coordinates":[[[99.3480,9.0880],[99.3520,9.0895],[99.3530,9.0865],[99.3490,9.0850],[99.3480,9.0880]]]}', 9.0872, 99.3505, 35, 0, 9.25, 56037.00, 5.6037, 'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.00, 1, 1, '2026-03-05 10:00:00', 'EUDR-TH-ST-84000-006-2580E8', 'แปลงยางพาราสอดคล้องตามเกณฑ์ EUDR'),
(8, 'RB-ST-2026-008', 2, 'แปลงยางพาราเขาท่าเพชร 1', 'โฉนดที่ดิน (น.ส. 4 จ)', '4444-5555', '{"type":"Polygon","coordinates":[[[99.3560,9.0920],[99.3610,9.0935],[99.3620,9.0895],[99.3570,9.0880],[99.3560,9.0920]]]}', 9.0907, 99.3590, 55, 1, 92.13, 88768.50, 8.8769, 'RRIM 600', 2018, 300, 76, 'tapping', 'under_review', 0.00, 1, 1, '2026-03-05 10:15:00', 'EUDR-TH-ST-84000-008-B60FF1', 'โซนเฝ้าระวัง Buffer Zone ใกล้แนวเขตป่าสงวน'),
(9, 'RB-ST-2026-009', 1, 'แปลงปลูกข้างบนเขา', 'โฉนดที่ดิน (น.ส. 4 จ)', '1111-2222', '{"type":"Polygon","coordinates":[[[99.3650,9.1020],[99.3700,9.1035],[99.3710,9.0990],[99.3660,9.0975],[99.3650,9.1020]]]}', 9.1005, 99.3680, 66, 2, 92.63, 106770.50, 10.6771, 'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.00, 1, 1, '2026-03-05 10:30:00', 'EUDR-TH-ST-84000-009-174823', 'แปลงยางพาราผ่านเกณฑ์ปลอดการตัดไม้ทำลายป่า'),
(11, 'RB-ST-2026-011', 7, 'แปลงปลูกไชยา', 'โฉนดที่ดิน (น.ส. 4 จ)', '4444-5555', '{"type":"Polygon","coordinates":[[[99.1980,9.3850],[99.2020,9.3865],[99.2030,9.3830],[99.1990,9.3815],[99.1980,9.3850]]]}', 9.3840, 99.2005, 20, 1, 58.90, 32635.60, 3.2636, 'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.00, 1, 1, '2026-03-05 10:45:00', 'EUDR-TH-ST-84000-011-06786B', 'แปลงปลูก อ.ไชยา ผ่านการรับรอง EUDR'),
(13, 'RB-ST-2026-012', 8, 'แปลงยางทุ่งเตา', 'โฉนดที่ดิน (น.ส. 4 จ)', '3456-5678', '{"type":"Polygon","coordinates":[[[99.3750,8.8750],[99.3780,8.8765],[99.3790,8.8735],[99.3760,8.8720],[99.3750,8.8750]]]}', 8.8742, 99.3770, 7, 1, 12.58, 11650.30, 1.1650, 'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.00, 1, 1, '2026-03-05 11:00:00', 'EUDR-TH-ST-84000-012-780C26', 'แปลงยางพารา ต.ทุ่งเตา อ.บ้านนาสาร'),
(15, 'RB-ST-2026-014', 9, 'แปลงยางพาราซอยสิบ', 'โฉนดที่ดิน (น.ส. 4 จ)', '1111-2222', '{"type":"Polygon","coordinates":[[[99.3350,9.0780],[99.3390,9.0795],[99.3400,9.0760],[99.3360,9.0745],[99.3350,9.0780]]]}', 9.0770, 99.3375, 22, 2, 51.41, 36205.60, 3.6206, 'RRIM 600', 2018, 300, 76, 'tapping', 'compliant', 0.00, 1, 1, '2026-03-05 11:15:00', 'EUDR-TH-ST-84000-014-F2D725', 'แปลงยางพาราซอยสิบ สุราษฎร์ธานี'),
(16, 'RB-ST-2026-016', 10, 'แปลงยางทดลองนักศึกษาการยาง', 'โฉนดที่ดิน (น.ส. 4 จ)', '9999-8888', '{"type":"Polygon","coordinates":[[[99.3590,9.0970],[99.3630,9.0985],[99.3640,9.0950],[99.3600,9.0935],[99.3590,9.0970]]]}', 9.0960, 99.3615, 12, 3, 17.10, 20468.40, 2.0468, 'RRIM 600', 2018, 300, 76, 'tapping', 'non_compliant', 22.00, 0, 0, '2026-03-05 11:30:00', 'EUDR-TH-ST-84000-016-17F815', 'แปลงทดลองนักศึกษา ตรวจพบการทับซ้อนแนวเขตป่า'),
(17, 'RB-ST-2026-017', 11, 'แปลงยางพาราใกล้มหาลัย', 'โฉนดที่ดิน (น.ส. 4 จ)', '66666-7777', '{"type":"Polygon","coordinates":[[[99.3510,9.0830],[99.3550,9.0845],[99.3560,9.0810],[99.3520,9.0795],[99.3510,9.0830]]]}', 9.0822, 99.3535, 23, 1, 18.70, 37274.80, 3.7275, 'RRIM 600', 2018, 300, 76, 'tapping', 'under_review', 0.00, 1, 1, '2026-03-05 11:45:00', 'EUDR-TH-ST-84000-017-B5F194', 'แปลงยางพาราใกล้มหาวิทยาลัยสงขลานครินทร์');

-- Yield Logs
INSERT INTO `yield_logs` (`id`, `plot_id`, `farmer_id`, `harvest_date`, `tapping_round`, `fresh_latex_kg`, `drc_percent`, `dry_rubber_kg`, `price_per_kg`, `total_revenue`, `buyer_name`, `notes`) VALUES
(1, 7, 6, '2026-08-01', 1, 145.50, 34.20, 49.76, 68.50, 9966.75, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'น้ำยางสดคุณภาพดี ไม่เจือปน'),
(2, 7, 6, '2026-08-03', 2, 138.00, 33.80, 46.64, 67.00, 9246.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'กรีดช่วงเช้ามืด สภาพอากาศแจ่มใส'),
(3, 9, 1, '2026-08-02', 1, 162.00, 35.00, 56.70, 68.50, 11097.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'ผลผลิตรอบที่ 1 คุณภาพดีเยี่ยม'),
(4, 11, 7, '2026-08-01', 1, 195.00, 33.50, 65.33, 69.00, 13455.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'ส่งน้ำยางรอบเช้า'),
(5, 13, 8, '2026-08-02', 1, 210.00, 34.00, 71.40, 68.00, 14280.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'น้ำยางข้น คุณภาพตรงตามเกณฑ์');
