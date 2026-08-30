-- =============================================================================
-- GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations
-- Database Schema for MySQL / MariaDB (XAMPP / phpMyAdmin Compatible)
-- Prince of Songkla University, Surat Thani Campus
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `georubber_watch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `georubber_watch`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE,
    `phone` VARCHAR(20),
    `role` ENUM('admin', 'farmer') DEFAULT 'farmer',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Farmers Table
CREATE TABLE IF NOT EXISTS `farmers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Forest Reserves Table
CREATE TABLE IF NOT EXISTS `forest_reserves` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `forest_code` VARCHAR(30) UNIQUE NOT NULL,
    `name_th` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100),
    `category` VARCHAR(50) DEFAULT 'ป่าสงวนแห่งชาติ',
    `geojson_geometry` LONGTEXT NOT NULL,
    `area_rai` DECIMAL(10, 2) DEFAULT 0,
    `color_code` VARCHAR(20) DEFAULT '#ef4444',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Rubber Plots Table
CREATE TABLE IF NOT EXISTS `rubber_plots` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Yield Logs Table
CREATE TABLE IF NOT EXISTS `yield_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Traceability Batches Table
CREATE TABLE IF NOT EXISTS `traceability_batches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
