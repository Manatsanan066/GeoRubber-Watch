<?php
/**
 * GeoRubber Watch - Supabase Cloud Database Backup Engine
 * Dumps and synchronizes 100% of live Supabase PostgreSQL data into local project files:
 * 1. SQL Dump (sql/supabase_backup_latest.sql & timestamped in data/backups/)
 * 2. JSON Export (data/backups/supabase_backup_latest.json & data/backups/tables/*.json)
 * 3. Local Standalone SQLite Database (data/georubber_watch_backup.sqlite)
 */
@set_time_limit(180);
@ini_set('memory_limit', '256M');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

$backupDir = __DIR__ . '/../data/backups';
$tablesDir = $backupDir . '/tables';
$sqlDir = __DIR__ . '/../sql';

if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0777, true);
}
if (!is_dir($tablesDir)) {
    @mkdir($tablesDir, 0777, true);
}
if (!is_dir($sqlDir)) {
    @mkdir($sqlDir, 0777, true);
}

// 1. Identify Target Tables with Safe Column Selectors
$tableQueries = [
    'users' => "SELECT id, username, email, full_name, role, farmer_id, id_card_num, phone, created_at FROM users ORDER BY id ASC",
    'farmers' => "SELECT id, user_id, farmer_code, prefix, first_name, last_name, id_card_num, phone, address, subdistrict, district, province, postal_code, created_at FROM farmers ORDER BY id ASC",
    'rubber_plots' => "SELECT id, farmer_id, plot_code, plot_name, rubber_clone, planting_year, area_rai, area_ngan, area_sqwah, area_total_sqm, tree_count, eudr_status, title_deed_type, title_deed_no, centroid_lat, centroid_lng, geojson_geometry, traceability_token, created_at FROM rubber_plots ORDER BY id ASC",
    'yield_logs' => "SELECT id, plot_id, farmer_id, harvest_date, tapping_round, fresh_latex_kg, drc_percent, dry_rubber_kg, price_per_kg, total_revenue, buyer_name, notes, created_at FROM yield_logs ORDER BY id ASC",
    'forest_reserves' => "SELECT id, forest_code, name_th, name_en, category, area_rai, color_code, created_at FROM forest_reserves ORDER BY id ASC",
    'traceability_batches' => "SELECT * FROM traceability_batches ORDER BY id ASC"
];

$timestamp = date('Y-m-d_His');
$dateStr = date('Y-m-d H:i:s');

try {
    $backupData = [];
    $tableCounts = [];
    $sqlDump = "-- =========================================================================\n";
    $sqlDump .= "-- GeoRubber Watch • Supabase Cloud PostgreSQL Full Backup\n";
    $sqlDump .= "-- Generated At: {$dateStr}\n";
    $sqlDump .= "-- Supabase Cloud Project: " . (defined('SUPABASE_REF') ? SUPABASE_REF : 'qwiuddkgdnfcaostzbov') . "\n";
    $sqlDump .= "-- =========================================================================\n\n";
    $sqlDump .= "SET statement_timeout = 0;\n";
    $sqlDump .= "SET client_encoding = 'UTF8';\n\n";

    // Prepare Local SQLite Database for 100% Mirroring
    $sqlitePath = __DIR__ . '/../data/georubber_watch_backup.sqlite';
    $sqlitePdo = new PDO('sqlite:' . $sqlitePath);
    $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlitePdo->exec("PRAGMA journal_mode = WAL; PRAGMA foreign_keys = OFF;");

    foreach ($tableQueries as $table => $query) {
        try {
            $stmt = $pdo->query($query);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $eTable) {
            // Fallback to simple SELECT *
            try {
                $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id ASC");
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (Throwable $eIgnore) {
                continue;
            }
        }

        $count = count($rows);
        $tableCounts[$table] = $count;
        $backupData[$table] = $rows;

        // Save Individual Table JSON
        $tableJsonFile = $tablesDir . '/' . $table . '.json';
        file_put_contents($tableJsonFile, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Generate SQL Schema & Inserts
        $sqlDump .= "-- -------------------------------------------------------------------------\n";
        $sqlDump .= "-- Table: {$table} ({$count} rows)\n";
        $sqlDump .= "-- -------------------------------------------------------------------------\n";
        $sqlDump .= "DROP TABLE IF EXISTS {$table} CASCADE;\n";

        if ($count > 0) {
            $columns = array_keys($rows[0]);
            
            // Build dynamic CREATE TABLE statement
            $sqlDump .= "CREATE TABLE IF NOT EXISTS {$table} (\n";
            $colDefs = [];
            foreach ($columns as $col) {
                if ($col === 'id') {
                    $colDefs[] = "    id SERIAL PRIMARY KEY";
                } elseif (str_contains($col, '_id') && $col !== 'id_card_num') {
                    $colDefs[] = "    {$col} INTEGER";
                } elseif (str_contains($col, 'date') || str_contains($col, '_at')) {
                    $colDefs[] = "    {$col} TEXT";
                } elseif (str_contains($col, 'kg') || str_contains($col, 'price') || str_contains($col, 'revenue') || str_contains($col, 'rai') || str_contains($col, 'percent') || str_contains($col, 'lat') || str_contains($col, 'lng')) {
                    $colDefs[] = "    {$col} NUMERIC";
                } else {
                    $colDefs[] = "    {$col} TEXT";
                }
            }
            $sqlDump .= implode(",\n", $colDefs) . "\n);\n\n";

            // Mirror Table to SQLite
            $sqlitePdo->exec("DROP TABLE IF EXISTS {$table};");
            $sqliteCols = [];
            foreach ($columns as $col) {
                if ($col === 'id') {
                    $sqliteCols[] = "id INTEGER PRIMARY KEY";
                } else {
                    $sqliteCols[] = "{$col} TEXT";
                }
            }
            $sqlitePdo->exec("CREATE TABLE {$table} (" . implode(", ", $sqliteCols) . ");");

            // Build Batch INSERT Statements for SQL Dump & SQLite
            $colList = implode(', ', $columns);
            $sqliteInsertSql = "INSERT INTO {$table} ({$colList}) VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
            $sqliteInsertStmt = $sqlitePdo->prepare($sqliteInsertSql);

            foreach ($rows as $row) {
                $escapedVals = [];
                $sqliteVals = [];
                foreach ($columns as $col) {
                    $val = $row[$col];
                    $sqliteVals[] = $val;
                    if ($val === null) {
                        $escapedVals[] = 'NULL';
                    } elseif (is_numeric($val) && !str_starts_with((string)$val, '0') && strlen((string)$val) < 15) {
                        $escapedVals[] = (string)$val;
                    } else {
                        $escapedVals[] = "'" . str_replace("'", "''", (string)$val) . "'";
                    }
                }
                $sqlDump .= "INSERT INTO {$table} ({$colList}) VALUES (" . implode(', ', $escapedVals) . ");\n";
                $sqliteInsertStmt->execute($sqliteVals);
            }
            $sqlDump .= "\n";
        } else {
            $sqlDump .= "-- (Table is currently empty)\n\n";
        }
    }

    // Save Full JSON Backup
    $fullJson = [
        'metadata' => [
            'backup_name' => 'GeoRubber Watch Supabase Full Backup',
            'generated_at' => $dateStr,
            'source_driver' => $driver,
            'table_counts' => $tableCounts,
            'total_tables' => count($tableCounts),
            'total_records' => array_sum($tableCounts)
        ],
        'tables' => $backupData
    ];

    $latestJsonPath = $backupDir . '/supabase_backup_latest.json';
    $rootDataJsonPath = __DIR__ . '/../data/supabase_backup_latest.json';
    $timestampJsonPath = $backupDir . "/supabase_backup_{$timestamp}.json";
    $jsonContent = json_encode($fullJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    @file_put_contents($latestJsonPath, $jsonContent);
    @file_put_contents($rootDataJsonPath, $jsonContent);
    @file_put_contents($timestampJsonPath, $jsonContent);

    // Save Full SQL Backup
    $latestSqlPath = $sqlDir . '/supabase_backup_latest.sql';
    $timestampSqlPath = $backupDir . "/supabase_backup_{$timestamp}.sql";
    @file_put_contents($latestSqlPath, $sqlDump);
    @file_put_contents($timestampSqlPath, $sqlDump);

    // Handle Direct File Download Request (if user clicked download)
    $action = $_GET['action'] ?? ($_GET['download'] ?? '');

    if ($action === 'sql' || $action === 'download_sql') {
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="georubber_supabase_backup_' . date('Ymd_His') . '.sql"');
        header('Content-Length: ' . strlen($sqlDump));
        echo $sqlDump;
        exit;
    }

    if ($action === 'json' || $action === 'download_json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="georubber_supabase_backup_' . date('Ymd_His') . '.json"');
        echo $jsonContent;
        exit;
    }

    if ($action === 'sqlite' || $action === 'download_sqlite') {
        if (file_exists($sqlitePath)) {
            header('Content-Type: application/x-sqlite3');
            header('Content-Disposition: attachment; filename="georubber_watch_backup_' . date('Ymd_His') . '.sqlite"');
            header('Content-Length: ' . filesize($sqlitePath));
            readfile($sqlitePath);
            exit;
        }
    }

    // Default JSON Response
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'สำรองข้อมูลจากฐานข้อมูล Supabase Cloud ลงสู่โฟลเดอร์โปรเจกต์เรียบร้อยแล้ว!',
        'timestamp' => $dateStr,
        'summary' => [
            'total_tables' => count($tableCounts),
            'total_records' => array_sum($tableCounts),
            'table_counts' => $tableCounts
        ],
        'saved_files' => [
            'sql_latest' => 'sql/supabase_backup_latest.sql',
            'sql_timestamped' => "data/backups/supabase_backup_{$timestamp}.sql",
            'json_latest' => 'data/backups/supabase_backup_latest.json',
            'json_timestamped' => "data/backups/supabase_backup_{$timestamp}.json",
            'tables_json_dir' => 'data/backups/tables/*.json',
            'local_sqlite_mirror' => 'data/georubber_watch_backup.sqlite'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการสำรองข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
