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
(3, 4, 'FM-PSU-003', 'นาย', 'สมชาย', 'ยางเจริญสุข', '1849900345673', '081-234-5678', '145 หมู่ 2 ต.ขุนทะเล อ.เมือง', 'ขุนทะเล', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000');

-- Forest Reserves (Surat Thani)
INSERT INTO `forest_reserves` (`id`, `forest_code`, `name_th`, `name_en`, `category`, `geojson_geometry`, `area_rai`, `color_code`) VALUES
(1, 'FOR-ST-001', 'เขตป่าสงวนแห่งชาติเขาท่าเพชร', 'Khao Tha Phet National Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.3590,9.0980],[99.3750,9.1020],[99.3820,9.0850],[99.3690,9.0790],[99.3590,9.0980]]]}', 2450.75, '#ef4444'),
(2, 'FOR-ST-002', 'เขตป่าคุ้มครองต้นน้ำขุนทะเล', 'Khun Thale Watershed Protected Forest', 'ป่าคุ้มครองแหล่งน้ำ', '{"type":"Polygon","coordinates":[[[99.3300,9.0650],[99.3450,9.0680],[99.3480,9.0520],[99.3280,9.0510],[99.3300,9.0650]]]}', 1820.30, '#dc2626'),
(3, 'FOR-ST-003', 'ป่าสงวนแห่งชาติป่าคลองน้ำเฒ่า', 'Khlong Nam Thao Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.2100,8.9500],[99.2450,8.9550],[99.2500,8.9200],[99.2050,8.9150],[99.2100,8.9500]]]}', 32000.00, '#ef4444'),
(4, 'FOR-ST-004', 'ป่าสงวนแห่งชาติป่าเขานาเตย', 'Khao Na Toei Forest Reserve', 'ป่าสงวนแห่งชาติ', '{"type":"Polygon","coordinates":[[[99.1200,9.2500],[99.1550,9.2550],[99.1600,9.2200],[99.1150,9.2150],[99.1200,9.2500]]]}', 15800.00, '#ef4444');

-- Rubber Plots (Around PSU Surat Thani)
INSERT INTO `rubber_plots` (`id`, `plot_code`, `farmer_id`, `plot_name`, `title_deed_type`, `title_deed_no`, `geojson_geometry`, `centroid_lat`, `centroid_lng`, `area_rai`, `area_ngan`, `area_sqwah`, `area_sqm`, `area_hectare`, `rubber_clone`, `planting_year`, `tree_count`, `trees_per_rai`, `tapping_status`, `eudr_status`, `eudr_overlap_pct`, `eudr_deforestation_free`, `eudr_cutoff_compliant`, `eudr_verified_at`, `traceability_token`, `notes`) VALUES
(1, 'RB-ST-2026-001', 1, 'แปลงยางทดสอบอัจฉริยะ ม.อ. สุราษฎร์ฯ (Plot A)', 'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-4458', '{"type":"Polygon","coordinates":[[[99.3502,9.0815],[99.3525,9.0822],[99.3531,9.0798],[99.3508,9.0792],[99.3502,9.0815]]]}', 9.0807, 99.3517, 6, 2, 45.0, 10580.0, 1.058, 'RRIM 600', 2018, 520, 80, 'tapping', 'compliant', 0.0, 1, 1, '2026-01-15 10:30:00', 'EUDR-TH-ST-84000-001-A9F32', 'แปลงทดลองระบบ EUDR ไม่พบการบุกรุกป่าสงวน'),
(2, 'RB-ST-2026-002', 1, 'สวนยางพาราสายรุ้ง ป่ามะพร้าว (Plot B)', 'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-7892', '{"type":"Polygon","coordinates":[[[99.3460,9.0840],[99.3485,9.0848],[99.3490,9.0825],[99.3465,9.0818],[99.3460,9.0840]]]}', 9.0830, 99.3475, 7, 1, 12.0, 11648.0, 1.165, 'RRIT 251', 2019, 580, 78, 'tapping', 'compliant', 0.0, 1, 1, '2026-02-01 14:20:00', 'EUDR-TH-ST-84000-002-B8E44', 'ผลผลิตคุณภาพสูง ผ่านการประเมินความยั่งยืน'),
(3, 'RB-ST-2026-003', 2, 'สวนยางพารามะขามเตี้ย ยั่งยืน (Plot C)', 'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-1123', '{"type":"Polygon","coordinates":[[[99.3540,9.0870],[99.3575,9.0878],[99.3582,9.0850],[99.3546,9.0842],[99.3540,9.0870]]]}', 9.0860, 99.3560, 8, 3, 20.0, 14080.0, 1.408, 'RRIM 600', 2017, 680, 78, 'tapping', 'compliant', 0.0, 1, 1, '2026-01-20 09:15:00', 'EUDR-TH-ST-84000-003-C7D55', 'แปลงมาตรฐานผ่านเกณฑ์ EUDR ไม่ทับซ้อนป่าสงวน'),
(4, 'RB-ST-2026-004', 2, 'สวนยางพาราแนวเขตเขาท่าเพชร (Plot D)', 'หนังสือรับรองการทำประโยชน์ (น.ส. 3 ก)', 'นส3-8401-9988', '{"type":"Polygon","coordinates":[[[99.3580,9.0965],[99.3620,9.0990],[99.3635,9.0955],[99.3592,9.0940],[99.3580,9.0965]]]}', 9.0962, 99.3607, 10, 0, 0.0, 16000.0, 1.600, 'RRIM 600', 2021, 760, 76, 'tapping', 'non_compliant', 28.5, 0, 0, '2026-02-10 16:45:00', 'EUDR-TH-ST-84000-004-D6C66', 'พบแนวแปลงเหลื่อมล้ำเข้าไปในเขตป่าสงวนแห่งชาติเขาท่าเพชร (28.5%)'),
(5, 'RB-ST-2026-005', 3, 'สวนยางสมชาย ขุนทะเลเขียวขจี (Plot E)', 'โฉนดที่ดิน (น.ส. 4 จ)', 'กษ-8401-3344', '{"type":"Polygon","coordinates":[[[99.3410,9.0750],[99.3448,9.0758],[99.3455,9.0725],[99.3418,9.0718],[99.3410,9.0750]]]}', 9.0738, 99.3433, 9, 2, 8.0, 15232.0, 1.523, 'RRIT 251', 2016, 720, 76, 'tapping', 'compliant', 0.0, 1, 1, '2026-01-25 11:00:00', 'EUDR-TH-ST-84000-005-E5B77', 'แปลงปลูกปลอดการตัดไม้ทำลายป่า 100%');

-- Yield Logs
INSERT INTO `yield_logs` (`id`, `plot_id`, `farmer_id`, `harvest_date`, `tapping_round`, `fresh_latex_kg`, `drc_percent`, `dry_rubber_kg`, `price_per_kg`, `total_revenue`, `buyer_name`, `notes`) VALUES
(1, 1, 1, '2026-02-18', 1, 145.50, 34.20, 49.76, 68.50, 9966.75, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'น้ำยางสดคุณภาพดี ไม่เจือปน'),
(2, 1, 1, '2026-02-15', 2, 138.00, 33.80, 46.64, 67.00, 9246.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'กรีดช่วงเช้ามืด สภาพอากาศแจ่มใส'),
(3, 2, 1, '2026-02-17', 1, 162.00, 35.00, 56.70, 68.50, 11097.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'ผลผลิตรอบที่ 1 คุณภาพดีเยี่ยม'),
(4, 3, 2, '2026-02-19', 1, 195.00, 33.50, 65.33, 69.00, 13455.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'ส่งน้ำยางรอบเช้า'),
(5, 5, 3, '2026-02-16', 1, 210.00, 34.00, 71.40, 68.00, 14280.00, 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด', 'น้ำยางข้น คุณภาพตรงตามเกณฑ์');
