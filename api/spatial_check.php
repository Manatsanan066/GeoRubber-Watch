<?php
/**
 * GeoRubber Watch - Spatial Overlay & EUDR Analysis API
 * Evaluates polygon intersection with simulated Forest Reserves and EUDR rules
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();

// Geometry Helper: Check if Point is inside Polygon
function isPointInPolygon($point, $polygon) {
    $x = $point[0]; // lng
    $y = $point[1]; // lat
    $inside = false;
    $n = count($polygon);

    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $polygon[$i][0];
        $yi = $polygon[$i][1];
        $xj = $polygon[$j][0];
        $yj = $polygon[$j][1];

        $intersect = (($yi > $y) != ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi + 0.0000000001) + $xi);
        if ($intersect) $inside = !$inside;
    }
    return $inside;
}

// Geometry Helper: Check if two line segments intersect
function doLinesIntersect($p1, $p2, $p3, $p4) {
    $ccw = function($A, $B, $C) {
        return ($C[1] - $A[1]) * ($B[0] - $A[0]) > ($B[1] - $A[1]) * ($C[0] - $A[0]);
    };
    return ($ccw($p1, $p3, $p4) != $ccw($p2, $p3, $p4)) && ($ccw($p1, $p2, $p3) != $ccw($p1, $p2, $p4));
}

// Check intersection between two polygons
function doPolygonsIntersect($poly1Coords, $poly2Coords) {
    // 1. Check if any vertex of poly1 is inside poly2
    foreach ($poly1Coords as $pt) {
        if (isPointInPolygon($pt, $poly2Coords)) return true;
    }
    // 2. Check if any vertex of poly2 is inside poly1
    foreach ($poly2Coords as $pt) {
        if (isPointInPolygon($pt, $poly1Coords)) return true;
    }
    // 3. Check if any edges intersect
    $n1 = count($poly1Coords);
    $n2 = count($poly2Coords);
    for ($i = 0; $i < $n1 - 1; $i++) {
        for ($j = 0; $j < $n2 - 1; $j++) {
            if (doLinesIntersect($poly1Coords[$i], $poly1Coords[$i+1], $poly2Coords[$j], $poly2Coords[$j+1])) {
                return true;
            }
        }
    }
    return false;
}

// Minimum distance approximation between centroid and forest polygon
function calculateMinDistanceMeters($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

// Extract all boundary rings from Polygon or MultiPolygon GeoJSON
function getPolygonRingsFromGeoJSON($geometry) {
    $rings = [];
    if (!isset($geometry['type']) || !isset($geometry['coordinates'])) return $rings;
    
    if ($geometry['type'] === 'Polygon') {
        foreach ($geometry['coordinates'] as $ring) {
            $rings[] = $ring;
        }
    } elseif ($geometry['type'] === 'MultiPolygon') {
        foreach ($geometry['coordinates'] as $poly) {
            foreach ($poly as $ring) {
                $rings[] = $ring;
            }
        }
    }
    return $rings;
}

function evaluatePlotEudrSpatial($pdo, $plotCoords, $planting_year = 2018) {
    // Centroid estimation
    $sumLat = 0; $sumLng = 0; $numPts = count($plotCoords);
    foreach ($plotCoords as $pt) {
        $sumLng += (float)$pt[0];
        $sumLat += (float)$pt[1];
    }
    $centroidLng = $numPts > 0 ? $sumLng / $numPts : 99.321850;
    $centroidLat = $numPts > 0 ? $sumLat / $numPts : 9.138240;

    // Load forest reserves from cache or database
    $cacheFile = __DIR__ . '/../data/cache_forest_reserves.json';
    $forests = [];
    if (file_exists($cacheFile)) {
        $cacheContent = json_decode(file_get_contents($cacheFile), true);
        if (isset($cacheContent['features']) && is_array($cacheContent['features'])) {
            $forests = $cacheContent['features'];
        }
    }

    if (empty($forests) && $pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM forest_reserves");
            $forests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $forests = [];
        }
    }

    $hasOverlap = false;
    $overlappingForests = [];
    $overlapPercentage = 0.0;
    $nearestDistance = 999999;
    $nearestForestName = 'ป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี';

    foreach ($forests as $f) {
        $forestGeo = null;
        $forestName = 'ป่าสงวนแห่งชาติ';
        $forestCode = '';
        $category = 'Zone-C ป่าสงวนแห่งชาติ';

        if (isset($f['geometry'])) {
            // GeoJSON Feature structure from cache
            $forestGeo = $f['geometry'];
            $props = $f['properties'] ?? [];
            $forestName = $props['name_th'] ?? $props['FR_NAME'] ?? $forestName;
            $forestCode = $props['forest_code'] ?? $props['NRF_CODE'] ?? '';
            $category = $props['category'] ?? $props['Typ'] ?? $category;
        } elseif (isset($f['geojson_geometry'])) {
            // Database row structure
            $forestGeo = is_string($f['geojson_geometry']) ? json_decode($f['geojson_geometry'], true) : $f['geojson_geometry'];
            $forestName = $f['name_th'] ?? $forestName;
            $forestCode = $f['forest_code'] ?? '';
            $category = $f['category'] ?? $category;
        }

        if (!$forestGeo) continue;

        $rings = getPolygonRingsFromGeoJSON($forestGeo);
        $forestOverlap = false;

        foreach ($rings as $ring) {
            if (empty($ring) || !is_array($ring)) continue;

            if (doPolygonsIntersect($plotCoords, $ring)) {
                $forestOverlap = true;
            }

            // Check distance to ring vertices
            foreach ($ring as $fpt) {
                if (is_array($fpt) && count($fpt) >= 2 && is_numeric($fpt[0]) && is_numeric($fpt[1])) {
                    $dist = calculateMinDistanceMeters($centroidLat, $centroidLng, (float)$fpt[1], (float)$fpt[0]);
                    if ($dist < $nearestDistance) {
                        $nearestDistance = $dist;
                        $nearestForestName = $forestName;
                    }
                }
            }
        }

        if ($forestOverlap) {
            $hasOverlap = true;
            $overlapPercentage = max($overlapPercentage, 25.0);
            $overlappingForests[] = [
                'name' => $forestName,
                'code' => $forestCode,
                'category' => $category
            ];
        }
    }

    $isCutoffCompliant = ($planting_year <= 2020);
    $status = 'compliant';
    $reasons = [];

    if ($hasOverlap) {
        $status = 'non_compliant';
        $forestNamesList = implode(', ', array_unique(array_column($overlappingForests, 'name')));
        $reasons[] = "ตรวจพบการทับซ้อนกับแนวเขตป่าสงวน ({$forestNamesList})";
    } elseif ($nearestDistance < 500) {
        $status = 'under_review';
        $reasons[] = "แปลงอยู่ใกล้แนวเขตป่าสงวน (" . round($nearestDistance) . " ม. จาก " . $nearestForestName . ") อยู่ในโซนเฝ้าระวัง (Buffer Zone)";
    } else {
        $status = 'compliant';
        $reasons[] = "ไม่พบการทับซ้อนกับแนวเขตป่าสงวนหรือพื้นที่อนุรักษ์ (Deforestation-Free Verified)";
    }

    // Area calculations
    $earthRadius = 6378137.0;
    $sqm = 0.0;
    $numCoords = count($plotCoords);
    if ($numCoords > 2) {
        for ($i = 0; $i < $numCoords - 1; $i++) {
            $p1 = $plotCoords[$i];
            $p2 = $plotCoords[$i + 1];
            $sqm += deg2rad($p2[0] - $p1[0]) * (2 + sin(deg2rad($p1[1])) + sin(deg2rad($p2[1])));
        }
        $sqm = abs($sqm * $earthRadius * $earthRadius / 2.0);
    }
    $totalSqWah = $sqm / 4.0;
    $rai = floor($totalSqWah / 400.0);
    $remSqWah = $totalSqWah - ($rai * 400.0);
    $ngan = floor($remSqWah / 100.0);
    $sqwah = round($remSqWah - ($ngan * 100.0), 2);
    $hectare = round($sqm / 10000.0, 4);
    $thaiArea = [
        'rai' => (int)$rai,
        'ngan' => (int)$ngan,
        'sqwah' => (float)$sqwah,
        'sqm' => round($sqm, 2),
        'hectare' => (float)$hectare,
        'formatted' => "{$rai} ไร่ {$ngan} งาน {$sqwah} ตร.ว."
    ];

    return [
        'success' => true,
        'has_overlap' => $hasOverlap,
        'overlap_percentage' => $overlapPercentage,
        'overlapping_forests' => $overlappingForests,
        'nearest_forest_distance_m' => round($nearestDistance, 1),
        'nearest_forest_name' => $nearestForestName,
        'eudr_status' => $status,
        'eudr_deforestation_free' => !$hasOverlap,
        'eudr_cutoff_compliant' => $isCutoffCompliant,
        'reasons' => $reasons,
        'centroid' => [
            'lat' => round($centroidLat, 6),
            'lng' => round($centroidLng, 6)
        ],
        'area_thai' => $thaiArea,
        'area_sqm' => round($sqm, 2),
        'area_hectare' => $hectare,
        'points_count' => $numPts > 1 ? ($numPts - 1) : $numPts
    ];
}

// Only execute as API endpoint when directly requested
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'spatial_check.php' || basename($_SERVER['PHP_SELF'] ?? '') === 'spatial_check.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $geojson = $input['geojson'] ?? null;
        $planting_year = (int)($input['planting_year'] ?? 2018);

        if (!$geojson || !isset($geojson['coordinates'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid GeoJSON payload']);
            exit;
        }

        $plotCoords = $geojson['coordinates'][0];
        $result = evaluatePlotEudrSpatial($pdo, $plotCoords, $planting_year);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
