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

function evaluatePlotEudrSpatial($pdo, $plotCoords, $planting_year = 2018) {
    // Centroid estimation
    $sumLat = 0; $sumLng = 0; $numPts = count($plotCoords);
    foreach ($plotCoords as $pt) {
        $sumLng += $pt[0];
        $sumLat += $pt[1];
    }
    $centroidLng = $numPts > 0 ? $sumLng / $numPts : 0;
    $centroidLat = $numPts > 0 ? $sumLat / $numPts : 0;

    // Fetch all forest reserves
    $stmt = $pdo->query("SELECT * FROM forest_reserves");
    $forests = $stmt->fetchAll();

    $hasOverlap = false;
    $overlappingForests = [];
    $overlapPercentage = 0.0;
    $nearestDistance = 999999;
    $nearestForestName = '';

    foreach ($forests as $f) {
        $forestGeo = json_decode($f['geojson_geometry'], true);
        if (!$forestGeo || !isset($forestGeo['coordinates'])) continue;
        
        $forestCoords = $forestGeo['coordinates'][0];
        if (doPolygonsIntersect($plotCoords, $forestCoords)) {
            $hasOverlap = true;
            $overlapPercentage = max($overlapPercentage, 25.0);
            $overlappingForests[] = [
                'name' => $f['name_th'],
                'code' => $f['forest_code'],
                'category' => $f['category']
            ];
        }

        // Check distance to forest vertices
        foreach ($forestCoords as $fpt) {
            $dist = calculateMinDistanceMeters($centroidLat, $centroidLng, $fpt[1], $fpt[0]);
            if ($dist < $nearestDistance) {
                $nearestDistance = $dist;
                $nearestForestName = $f['name_th'];
            }
        }
    }

    $isCutoffCompliant = ($planting_year <= 2020);
    $status = 'compliant';
    $reasons = [];

    if ($hasOverlap) {
        $status = 'non_compliant';
        $reasons[] = "ตรวจพบการทับซ้อนกับแนวเขตป่าสงวน (" . implode(', ', array_column($overlappingForests, 'name')) . ")";
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
