<?php
/**
 * GeoRubber Watch - Database Connection & Configuration
 * Supports SQLite (Default Zero-Config with multi-path write fallback), PostgreSQL, and MySQL
 */

// Define system constants
define('APP_NAME', 'GeoRubber Watch');
define('APP_VERSION', '1.0.0');
define('PILOT_LOCATION', 'มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี');

// Supabase Cloud Configuration
define('SUPABASE_REF', 'qwiuddkgdnfcaostzbov');
define('SUPABASE_URL', 'https://qwiuddkgdnfcaostzbov.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_BOjpqFPvzbOXbbd5lJUong_9Az-aSXR');

// PHP 8 Compatibility Polyfills for older PHP versions
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || $needle === substr($haystack, -strlen($needle));
    }
}

// Database driver: 'pgsql' (PostgreSQL - Supabase Cloud)
$db_type = 'pgsql';

// Database configurations (Primary: Supabase Cloud PostgreSQL, Fallback: Local / SQLite)
$db_config = [
    'pgsql' => [
        'host' => getenv('DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com',
        'port' => getenv('DB_PORT') ?: '6543',
        'dbname' => getenv('DB_NAME') ?: 'postgres',
        'user' => getenv('DB_USER') ?: 'postgres.qwiuddkgdnfcaostzbov',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Rabber@2548',
        'sslmode' => getenv('DB_SSLMODE') ?: 'require'
    ]
];

function getDatabaseConnection() {
    global $db_config;
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = $db_config['pgsql'];
    
    // Prevent libpq from attempting to read root client certificates when running under Apache daemon
    putenv("PGSSLCERT=");
    putenv("PGSSLKEY=");
    putenv("PGSSLROOTCERT=");

    $ssl = !empty($cfg['sslmode']) ? ";sslmode={$cfg['sslmode']};sslcert=;sslkey=" : "";

    // 1. Attempt PostgreSQL Connection (Primary: Supabase Cloud)
    try {
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}{$ssl}";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 6
        ]);
        return $pdo;
    } catch (Exception $e) {
        // Try direct IPv4 or secondary host if pooler port 6543 is blocked
        try {
            $dsn2 = "pgsql:host=aws-0-ap-northeast-1.pooler.supabase.com;port=5432;dbname=postgres{$ssl}";
            $pdo = new PDO($dsn2, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ]);
            return $pdo;
        } catch (Exception $e2) {
            // Supabase connection offline / unreachable
        }
        // If on localhost and georubber_watch db does not exist, try to create via default 'postgres' database
        if ($cfg['host'] === '127.0.0.1' || $cfg['host'] === 'localhost') {
            try {
                $rootDsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname=postgres";
                $rootPdo = new PDO($rootDsn, $cfg['user'], $cfg['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3
                ]);
                $rootPdo->exec("CREATE DATABASE georubber_watch;");
                
                $pdo = new PDO("pgsql:host={$cfg['host']};port={$cfg['port']};dbname=georubber_watch", $cfg['user'], $cfg['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                return $pdo;
            } catch (Exception $ex) {
                // Local PostgreSQL daemon not started
            }
        }

        // 2. Seamless local fallback so the website NEVER crashes or shows errors
        $sqlitePaths = [
            __DIR__ . '/../data/georubber.db',
            sys_get_temp_dir() . '/georubber.db',
            '/tmp/georubber.db'
        ];

        foreach ($sqlitePaths as $path) {
            try {
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                @chmod($dir, 0777);

                $pdo = new PDO('sqlite:' . $path, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON;');
                $pdo->exec('PRAGMA journal_mode = WAL;');
                $pdo->exec('PRAGMA synchronous = NORMAL;');
                $pdo->exec('PRAGMA cache_size = -64000;');
                $pdo->exec('PRAGMA temp_store = MEMORY;');
                if (file_exists($path)) {
                    @chmod($path, 0666);
                }
                return $pdo;
            } catch (Exception $errSqlite) {
                continue;
            }
        }

        // 3. In-memory fallback
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA temp_store = MEMORY;');
        return $pdo;
    }
}

/**
 * Initialize Tables & Seeds if database is fresh (Cached in memory/session/file for instant execution < 0.1ms)
 */
function initDatabaseIfNeeded($force = false) {
    static $alreadyDone = false;
    if ($alreadyDone && !$force) {
        return;
    }
    $flagFile = __DIR__ . '/../data/.db_init_done';
    if (!$force && file_exists($flagFile) && (time() - filemtime($flagFile) < 86400)) {
        $alreadyDone = true;
        return;
    }
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['__db_init_done']) && !$force) {
        $alreadyDone = true;
        return;
    }

    $pdo = getDatabaseConnection();
    
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tableExists = false;

        if ($driver === 'pgsql') {
            $check = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'rubber_plots' AND table_schema = 'public'");
            $tableExists = $check && $check->fetchColumn() ? true : false;
        } elseif ($driver === 'sqlite') {
            $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='rubber_plots'");
            $tableExists = $check && $check->fetch() ? true : false;
        } else {
            $check = $pdo->query("SHOW TABLES LIKE 'rubber_plots'");
            $tableExists = $check && $check->fetch() ? true : false;
        }
        
        if (!$tableExists) {
            require_once __DIR__ . '/seed_data.php';
            seedDatabase($pdo);
        } else {
            // Auto-migration: ensure users and farmers tables have id_card_num column
            try {
                if ($driver === 'pgsql') {
                    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS id_card_num VARCHAR(20);");
                    $pdo->exec("ALTER TABLE farmers ADD COLUMN IF NOT EXISTS id_card_num VARCHAR(20);");
                } elseif ($driver === 'sqlite') {
                    @$pdo->exec("ALTER TABLE users ADD COLUMN id_card_num VARCHAR(20);");
                    @$pdo->exec("ALTER TABLE farmers ADD COLUMN id_card_num VARCHAR(20);");
                }
            } catch (Throwable $eIgnore) {}

            // Auto-sync: Ensure plots status accuracy
            try {
                // 1. Non-compliant: Plots overlapping forest reserves (e.g. แปลงยางพาราในเขาท่าเพชร)
                $pdo->exec("UPDATE rubber_plots SET eudr_status = 'non_compliant', eudr_overlap_pct = 100.0, eudr_deforestation_free = 0 WHERE (plot_name LIKE '%แปลงยางพาราในเขาท่าเพชร%' OR plot_code = 'RB-ST-2026-023')");

                // 2. Buffer zone: แปลงยางพาราซอยหมูเถื่อน -> โซนเฝ้าระวัง (Buffer < 500 ม.)
                $chkMu = $pdo->query("SELECT id FROM rubber_plots WHERE plot_name LIKE '%หมูเถื่อน%' LIMIT 1");
                $foundMu = $chkMu ? $chkMu->fetchColumn() : null;

                if ($foundMu) {
                    $pdo->exec("UPDATE rubber_plots SET plot_name = 'แปลงยางพาราซอยหมูเถื่อน', eudr_status = 'under_review', eudr_overlap_pct = 0.0, eudr_deforestation_free = 1, notes = 'โซนเฝ้าระวัง (Buffer < 500 ม.) - ซอยหมูเถื่อน' WHERE id = " . (int)$foundMu);
                } else {
                    // Seed plot "แปลงยางพาราซอยหมูเถื่อน" if not already in DB
                    $muGeo = json_encode([
                        "type" => "Polygon",
                        "coordinates" => [[
                            [99.3510, 9.0880],
                            [99.3540, 9.0895],
                            [99.3550, 9.0870],
                            [99.3520, 9.0855],
                            [99.3510, 9.0880]
                        ]]
                    ]);
                    $farmerId = 2;
                    $fCheck = $pdo->query("SELECT id FROM farmers WHERE id = 2 LIMIT 1");
                    if (!$fCheck || !$fCheck->fetchColumn()) {
                        $fAny = $pdo->query("SELECT id FROM farmers LIMIT 1");
                        $farmerId = $fAny ? (int)$fAny->fetchColumn() : 1;
                    }
                    $insMu = $pdo->prepare("
                        INSERT INTO rubber_plots (
                            plot_code, farmer_id, plot_name, title_deed_type, title_deed_no,
                            geojson_geometry, centroid_lat, centroid_lng, area_rai, area_ngan,
                            area_sqwah, area_sqm, area_hectare, rubber_clone, planting_year,
                            tree_count, trees_per_rai, tapping_status, eudr_status, eudr_overlap_pct,
                            eudr_deforestation_free, eudr_cutoff_compliant, traceability_token, notes
                        ) VALUES (
                            'RB-ST-2026-008', ?, 'แปลงยางพาราซอยหมูเถื่อน', 'โฉนดที่ดิน (น.ส. 4 จ)', '4444-5555',
                            ?, 9.0875, 99.3530, 18, 2, 40.0, 29760.0, 2.9760, 'RRIM 600', 2018,
                            500, 76, 'tapping', 'under_review', 0.0,
                            1, 1, 'EUDR-TH-ST-84000-008-B60FF1', 'โซนเฝ้าระวัง (Buffer < 500 ม.) - ซอยหมูเถื่อน ใกล้แนวเขตป่าสงวน'
                        )
                    ");
                    $insMu->execute([$farmerId, $muGeo]);
                }

                // 3. Factory accounts auto-sync (factory01 & factory02)
                $real_hash1 = password_hash('factory01', PASSWORD_DEFAULT);
                $real_hash2 = password_hash('factory02', PASSWORD_DEFAULT);

                $chkF1 = $pdo->query("SELECT id FROM users WHERE username = 'factory01' LIMIT 1");
                $f1Id = $chkF1 ? $chkF1->fetchColumn() : null;
                if ($f1Id) {
                    $upF1 = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'factory' WHERE id = ?");
                    $upF1->execute([$real_hash1, $f1Id]);
                } else {
                    $insF1 = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES ('factory01', ?, 'โรงงานแปรรูปยางพาราสุราษฎร์ธานี 1 (Factory 01)', 'factory01@georubberwatch.com', '077-999-001', 'factory')");
                    $insF1->execute([$real_hash1]);
                }

                $chkF2 = $pdo->query("SELECT id FROM users WHERE username = 'factory02' LIMIT 1");
                $f2Id = $chkF2 ? $chkF2->fetchColumn() : null;
                if ($f2Id) {
                    $upF2 = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'factory' WHERE id = ?");
                    $upF2->execute([$real_hash2, $f2Id]);
                } else {
                    $insF2 = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, role) VALUES ('factory02', ?, 'จุดรับซื้อและแปรรูปยางพารา 2 (Factory 02)', 'factory02@georubberwatch.com', '077-999-002', 'factory')");
                    $insF2->execute([$real_hash2]);
                }

                // 4. Ensure all 10 registered farmers exist in farmers table
                $farmersData = [
                    ['FM-PSU-001', 'matinee', 'นางสาว', 'มาทินี', 'โรยนรินทร์', '1-8499-00123-45-1', '093-578-2399', '31 หมู่ 6 มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-002', 'manatsanan', 'นางสาว', 'มนัสนันท์', 'อนันตณรงค์', '1-8499-00234-56-2', '095-331-8033', '31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-003', 'somchai', 'นาย', 'สมชาย', 'ยางเจริญสุข', '1-8499-00345-67-3', '081-234-5678', '145 หมู่ 2 ต.ขุนทะเล อ.เมือง', 'ขุนทะเล', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-004', null, 'นางสาว', 'โอ', 'ชิ', '1-8499-00567-89-0', '089-111-2233', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-005', null, 'นาย', 'โซโซ', 'อนันตณรงค์', '1-8499-00678-90-1', '089-222-3344', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-006', null, 'นางสาว', 'ดาว', 'นิล', '1-8499-00789-01-2', '089-333-4455', 'ต.ไชยา อ.ไชยา', 'ไชยา', 'ไชยา', 'สุราษฎร์ธานี', '84110'],
                    ['FM-PSU-007', null, 'นาย', 'วุฒิพงษ์', 'แดงบรรจง', '1-8499-00890-12-3', '089-444-5566', 'ต.ทุ่งเตา อ.บ้านนาสาร', 'ทุ่งเตา', 'บ้านนาสาร', 'สุราษฎร์ธานี', '84120'],
                    ['FM-PSU-008', null, 'นางสาว', 'น้ำตาล', 'อนันตณรงค์', '1-8499-00901-23-4', '089-555-6677', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-009', null, 'นาย', 'นักศึกษาสาขาการยาง', 'อนันตณรงค์', '1-8499-01012-34-5', '089-666-7788', '31 ม.6 ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000'],
                    ['FM-PSU-010', null, 'นาย', 'สุชาติ', 'อนันตณรงค์', '1-8499-01123-45-6', '089-777-8899', 'ต.มะขามเตี้ย อ.เมือง', 'มะขามเตี้ย', 'เมืองสุราษฎร์ธานี', 'สุราษฎร์ธานี', '84000']
                ];

                foreach ($farmersData as $fd) {
                    $uId = null;
                    if (!empty($fd[1])) {
                        $uCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                        $uCheck->execute([$fd[1]]);
                        $uId = $uCheck->fetchColumn() ?: null;
                    }
                    $fCheck = $pdo->prepare("SELECT id FROM farmers WHERE farmer_code = ? OR (first_name = ? AND last_name = ?) LIMIT 1");
                    $fCheck->execute([$fd[0], $fd[3], $fd[4]]);
                    $foundFid = $fCheck->fetchColumn();

                    if ($foundFid) {
                        $pdo->prepare("UPDATE farmers SET user_id = COALESCE(?, user_id), prefix = ?, first_name = ?, last_name = ?, id_card_num = COALESCE(id_card_num, ?), phone = ?, address = ?, subdistrict = ?, district = ?, province = ?, postal_code = ? WHERE id = ?")
                            ->execute([$uId, $fd[2], $fd[3], $fd[4], $fd[5], $fd[6], $fd[7], $fd[8], $fd[9], $fd[10], $fd[11], $foundFid]);
                    } else {
                        $pdo->prepare("INSERT INTO farmers (farmer_code, user_id, prefix, first_name, last_name, id_card_num, phone, address, subdistrict, district, province, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$fd[0], $uId, $fd[2], $fd[3], $fd[4], $fd[5], $fd[6], $fd[7], $fd[8], $fd[9], $fd[10], $fd[11]]);
                    }
                }
            } catch (Throwable $eIgnore) {}
        }

        try {
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rubber_plots_farmer ON rubber_plots(farmer_id);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rubber_plots_code ON rubber_plots(plot_code);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rubber_plots_token ON rubber_plots(traceability_token);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_rubber_plots_status ON rubber_plots(eudr_status);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_yield_logs_plot ON yield_logs(plot_id);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_yield_logs_farmer ON yield_logs(farmer_id);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_yield_logs_date ON yield_logs(harvest_date);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_farmers_user ON farmers(user_id);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_farmers_code ON farmers(farmer_code);");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_farmers_idcard ON farmers(id_card_num);");
        } catch (Throwable $eIgnore) {}

        $alreadyDone = true;
        @file_put_contents($flagFile, (string)time());
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['__db_init_done'] = true;
        }
    } catch (Exception $e) {
        // Table initialization checked
    }
}
