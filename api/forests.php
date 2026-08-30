<?php
/**
 * GeoRubber Watch - Forest Reserves API (GeoJSON & Import)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch all Forest Reserves as GeoJSON FeatureCollection
if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM forest_reserves ORDER BY id ASC");
        $reserves = $stmt->fetchAll();

        // If table is empty, auto-seed from Zone-c.geojson
        if (empty($reserves)) {
            $zoneCPath = __DIR__ . '/../Zone-c.geojson';
            if (file_exists($zoneCPath)) {
                $rawGeo = json_decode(file_get_contents($zoneCPath), true);
                if ($rawGeo && isset($rawGeo['features'])) {
                    $ins = $pdo->prepare("INSERT INTO forest_reserves (forest_code, name_th, name_en, category, area_rai, geojson_geometry, color_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($rawGeo['features'] as $idx => $f) {
                        $p = $f['properties'] ?? [];
                        $code = $p['forest_code'] ?? $p['FOREST_COD'] ?? ('FOR-ST-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT));
                        $nameTh = $p['name_th'] ?? $p['NAME_TH'] ?? $p['name'] ?? ('ป่าสงวนแห่งชาติผืนที่ ' . ($idx + 1));
                        $nameEn = $p['name_en'] ?? $p['NAME_EN'] ?? 'National Forest Reserve';
                        $cat = $p['category'] ?? 'ป่าสงวนแห่งชาติ (Zone C)';
                        $rai = (float)($p['area_rai'] ?? $p['AREA_RAI'] ?? 0);
                        $ins->execute([$code, $nameTh, $nameEn, $cat, $rai, json_encode($f['geometry']), '#0e4d4e']);
                    }
                    $stmt = $pdo->query("SELECT * FROM forest_reserves ORDER BY id ASC");
                    $reserves = $stmt->fetchAll();
                }
            }
        }

        $features = [];
        foreach ($reserves as $row) {
            $geometry = json_decode($row['geojson_geometry'], true);
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => (int)$row['id'],
                    'forest_code' => $row['forest_code'],
                    'name_th' => $row['name_th'],
                    'name_en' => $row['name_en'],
                    'category' => $row['category'],
                    'area_rai' => (float)$row['area_rai'],
                    'color_code' => $row['color_code'] ?? '#0e4d4e'
                ],
                'geometry' => $geometry
            ];
        }

        echo json_encode([
            'type' => 'FeatureCollection',
            'features' => $features
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Handle POST: Import GeoJSON File or Raw JSON
if ($method === 'POST') {
    try {
        $geojsonContent = '';
        $replaceExisting = isset($_POST['replace_existing']) && $_POST['replace_existing'] === 'true';

        // 1. Check if file was uploaded via multipart/form-data
        if (isset($_FILES['geojson_file']) && $_FILES['geojson_file']['error'] === UPLOAD_ERR_OK) {
            $geojsonContent = file_get_contents($_FILES['geojson_file']['tmp_name']);
        } else {
            // 2. Check JSON payload in request body
            $rawInput = file_get_contents('php://input');
            $jsonData = json_decode($rawInput, true);
            if ($jsonData) {
                if (isset($jsonData['features']) || isset($jsonData['type'])) {
                    $geojsonContent = $rawInput;
                } elseif (isset($jsonData['geojson_data'])) {
                    $geojsonContent = is_string($jsonData['geojson_data']) ? $jsonData['geojson_data'] : json_encode($jsonData['geojson_data']);
                    $replaceExisting = $jsonData['replace_existing'] ?? false;
                }
            }
        }

        if (empty($geojsonContent)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์หรือข้อมูล GeoJSON ที่ถูกต้อง']);
            exit;
        }

        $geoObj = json_decode($geojsonContent, true);
        if (!$geoObj) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'รูปแบบ GeoJSON ไม่ถูกต้อง (Invalid JSON format)']);
            exit;
        }

        // Normalize to list of features
        $features = [];
        if (isset($geoObj['type'])) {
            if ($geoObj['type'] === 'FeatureCollection' && isset($geoObj['features'])) {
                $features = $geoObj['features'];
            } elseif ($geoObj['type'] === 'Feature') {
                $features = [$geoObj];
            } elseif (in_array($geoObj['type'], ['Polygon', 'MultiPolygon'])) {
                $features = [[
                    'type' => 'Feature',
                    'properties' => [
                        'name_th' => 'เขตป่าสงวนแห่งชาติ (นำเข้าใหม่)',
                        'forest_code' => 'FR-IMP-' . time()
                    ],
                    'geometry' => $geoObj
                ]];
            }
        }

        if (empty($features)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไม่พบฟีเจอร์เชิงพื้นที่ (Polygon Feature) ในไฟล์ GeoJSON']);
            exit;
        }

        $pdo->beginTransaction();

        // Optionally clear existing forest reserves
        if ($replaceExisting) {
            $pdo->exec("DELETE FROM forest_reserves");
        }

        $stmt = $pdo->prepare("
            INSERT INTO forest_reserves (forest_code, name_th, name_en, category, area_rai, geojson_geometry, color_code)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $importedCount = 0;
        foreach ($features as $idx => $f) {
            $geom = $f['geometry'] ?? null;
            if (!$geom || !in_array($geom['type'], ['Polygon', 'MultiPolygon'])) {
                continue;
            }

            $props = $f['properties'] ?? [];
            
            // Auto-detect attributes with fallback
            $nameTh = $props['name_th'] ?? $props['NAME_TH'] ?? $props['name'] ?? $props['NAME'] ?? $props['forest_name'] ?? ('แนวเขตป่าสงวนที่ ' . ($importedCount + 1));
            $nameEn = $props['name_en'] ?? $props['NAME_EN'] ?? $props['name_e'] ?? 'National Forest Reserve';
            $code = $props['forest_code'] ?? $props['FOREST_COD'] ?? $props['code'] ?? $props['ID'] ?? ('FR-' . str_pad($importedCount + 1, 3, '0', STR_PAD_LEFT));
            $cat = $props['category'] ?? $props['CATEGORY'] ?? $props['type'] ?? 'ป่าสงวนแห่งชาติ';
            $areaRai = (float)($props['area_rai'] ?? $props['AREA_RAI'] ?? $props['rai'] ?? 0);
            $color = $props['color_code'] ?? '#ef4444';

            $stmt->execute([
                $code,
                $nameTh,
                $nameEn,
                $cat,
                $areaRai,
                json_encode($geom, JSON_UNESCAPED_UNICODE),
                $color
            ]);

            $importedCount++;
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "นำเข้าข้อมูลแนวเขตป่าสงวนสำเร็จทั้งหมด {$importedCount} แปลง",
            'imported_count' => $importedCount
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
    exit;
}
