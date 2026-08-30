-- =============================================================================
-- GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations
-- Database Schema for PostgreSQL with PostGIS Extension (SRID: 4326 WGS84)
-- Prince of Songkla University, Surat Thani Campus
-- =============================================================================

-- Enable PostGIS Extension
CREATE EXTENSION IF NOT EXISTS postgis;

-- 1. Users Table (Authentication & Roles)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'farmer', -- 'admin' or 'farmer'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Farmers Table (Detailed Farmer Profile)
CREATE TABLE IF NOT EXISTS farmers (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
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

-- 3. Protected Forest Reserves (National Forest Reserve Layers for EUDR Overlay Analysis)
CREATE TABLE IF NOT EXISTS forest_reserves (
    id SERIAL PRIMARY KEY,
    forest_code VARCHAR(30) UNIQUE NOT NULL,
    name_th VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    category VARCHAR(50) DEFAULT 'ป่าสงวนแห่งชาติ',
    geom GEOMETRY(MultiPolygon, 4326),
    geojson_geometry TEXT,
    area_rai NUMERIC(10, 2) DEFAULT 0,
    color_code VARCHAR(20) DEFAULT '#ef4444',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create Spatial Index for Forest Reserves
CREATE INDEX IF NOT EXISTS idx_forest_reserves_geom ON forest_reserves USING GIST (geom);

-- 4. Rubber Plantation Plots (Core Spatial GIS Entity)
CREATE TABLE IF NOT EXISTS rubber_plots (
    id SERIAL PRIMARY KEY,
    plot_code VARCHAR(30) UNIQUE NOT NULL,
    farmer_id INT NOT NULL REFERENCES farmers(id) ON DELETE CASCADE,
    plot_name VARCHAR(100) NOT NULL,
    title_deed_type VARCHAR(50) DEFAULT 'โฉนดที่ดิน (น.ส. 4 จ)',
    title_deed_no VARCHAR(50),
    geom GEOMETRY(Polygon, 4326),
    geojson_geometry TEXT NOT NULL,
    centroid_lat NUMERIC(10, 7) NOT NULL,
    centroid_lng NUMERIC(10, 7) NOT NULL,
    area_rai INT DEFAULT 0,
    area_ngan INT DEFAULT 0,
    area_sqwah NUMERIC(10, 2) DEFAULT 0,
    area_sqm NUMERIC(12, 2) DEFAULT 0,
    area_hectare NUMERIC(10, 4) DEFAULT 0,
    rubber_clone VARCHAR(50) DEFAULT 'RRIM 600',
    planting_year INT DEFAULT 2018,
    tree_count INT DEFAULT 300,
    trees_per_rai INT DEFAULT 76,
    tapping_status VARCHAR(20) DEFAULT 'tapping', -- 'tapping' or 'not_tapping'
    eudr_status VARCHAR(30) DEFAULT 'compliant', -- 'compliant', 'non_compliant', 'under_review'
    eudr_overlap_pct NUMERIC(5, 2) DEFAULT 0.0,
    eudr_deforestation_free INT DEFAULT 1, -- 1 = true, 0 = false
    eudr_cutoff_compliant INT DEFAULT 1, -- 1 = pre-2020 planted, 0 = post-2020 cut-off risk
    eudr_verified_at TIMESTAMP WITH TIME ZONE,
    traceability_token VARCHAR(64) UNIQUE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create Spatial Index for Rubber Plots
CREATE INDEX IF NOT EXISTS idx_rubber_plots_geom ON rubber_plots USING GIST (geom);

-- 5. Rubber Yield Logs (Latex Production Records)
CREATE TABLE IF NOT EXISTS yield_logs (
    id SERIAL PRIMARY KEY,
    plot_id INT NOT NULL REFERENCES rubber_plots(id) ON DELETE CASCADE,
    farmer_id INT NOT NULL REFERENCES farmers(id) ON DELETE CASCADE,
    harvest_date DATE NOT NULL,
    tapping_round INT DEFAULT 1,
    fresh_latex_kg NUMERIC(10, 2) NOT NULL,
    drc_percent NUMERIC(5, 2) DEFAULT 33.5, -- Dry Rubber Content %
    dry_rubber_kg NUMERIC(10, 2) NOT NULL,
    price_per_kg NUMERIC(8, 2) DEFAULT 65.0,
    total_revenue NUMERIC(12, 2) NOT NULL,
    buyer_name VARCHAR(100) DEFAULT 'สหกรณ์กองทุนสวนยาง ม.อ. สุราษฎร์ธานี จำกัด',
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. EUDR Traceability Export Batches
CREATE TABLE IF NOT EXISTS traceability_batches (
    id SERIAL PRIMARY KEY,
    batch_code VARCHAR(50) UNIQUE NOT NULL,
    plot_id INT NOT NULL REFERENCES rubber_plots(id) ON DELETE CASCADE,
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

-- =============================================================================
-- Spatial Analysis Functions & Views
-- =============================================================================

-- Spatial View: Check Intersections between Rubber Plots and Forest Reserves
CREATE OR REPLACE VIEW v_plot_forest_intersections AS
SELECT 
    p.id AS plot_id,
    p.plot_code,
    p.plot_name,
    f.id AS forest_id,
    f.name_th AS forest_name,
    ST_Intersects(p.geom, f.geom) AS has_overlap,
    CASE 
        WHEN ST_Intersects(p.geom, f.geom) 
        THEN ROUND((ST_Area(ST_Intersection(p.geom::geography, f.geom::geography)) / NULLIF(ST_Area(p.geom::geography), 0) * 100)::numeric, 2)
        ELSE 0 
    END AS overlap_percentage
FROM rubber_plots p
CROSS JOIN forest_reserves f;
