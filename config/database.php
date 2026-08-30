<?php
/**
 * GeoRubber Watch - Database Connection & Configuration
 * Supports SQLite (Default Zero-Config with multi-path write fallback), PostgreSQL, and MySQL
 */

// Define system constants
define('APP_NAME', 'GeoRubber Watch');
define('APP_VERSION', '1.0.0');
define('PILOT_LOCATION', 'มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี');

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

// Database driver: 'pgsql' (PostgreSQL)
$db_type = 'pgsql';

// Database configurations
$db_config = [
    'pgsql' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '5432',
        'dbname' => getenv('DB_NAME') ?: 'georubber_watch',
        'user' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'postgres'
    ]
];

function getDatabaseConnection() {
    global $db_config;
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = $db_config['pgsql'];

    // 1. Attempt PostgreSQL Connection
    try {
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']}";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        return $pdo;
    } catch (Exception $e) {
        // If georubber_watch db does not exist, try to create via default 'postgres' database
        try {
            $rootDsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname=postgres";
            $rootPdo = new PDO($rootDsn, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2
            ]);
            $rootPdo->exec("CREATE DATABASE georubber_watch;");
            
            $pdo = new PDO("pgsql:host={$cfg['host']};port={$cfg['port']};dbname=georubber_watch", $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (Exception $ex) {
            // PostgreSQL daemon is not started or port 5432 is closed
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
        return $pdo;
    }
}

/**
 * Initialize Tables & Seeds if database is fresh
 */
function initDatabaseIfNeeded() {
    $pdo = getDatabaseConnection();
    
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tableExists = false;

        if ($driver === 'pgsql') {
            $check = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'users' AND table_schema = 'public'");
            $tableExists = $check && $check->fetchColumn() ? true : false;
        } elseif ($driver === 'sqlite') {
            $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $tableExists = $check && $check->fetch() ? true : false;
        } else {
            $check = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $check && $check->fetch() ? true : false;
        }
        
        if (!$tableExists) {
            require_once __DIR__ . '/seed_data.php';
            seedDatabase($pdo);
        }
    } catch (Exception $e) {
        require_once __DIR__ . '/seed_data.php';
        seedDatabase($pdo);
    }
}
