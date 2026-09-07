<?php
/**
 * GeoRubber Watch - Ngrok Public URL Detection API
 * Automatically detects active ngrok tunnels or manages public access URL
 */
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configFilePath = __DIR__ . '/../config/public_url.json';

// Handle POST to save custom public URL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? ($_POST['action'] ?? '');
    
    if ($action === 'set_custom_url') {
        $customUrl = trim($input['url'] ?? ($_POST['url'] ?? ''));
        if (!empty($customUrl)) {
            // Normalize URL (strip trailing slashes)
            $customUrl = rtrim($customUrl, '/');
            if (!preg_match('~^(?:f|ht)tps?://~i', $customUrl)) {
                $customUrl = 'https://' . $customUrl;
            }
            if (!file_exists(dirname($configFilePath))) {
                @mkdir(dirname($configFilePath), 0777, true);
            }
            file_put_contents($configFilePath, json_encode([
                'custom_url' => $customUrl,
                'updated_at' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            echo json_encode([
                'success' => true,
                'message' => 'Custom public URL saved successfully',
                'public_url' => $customUrl
            ]);
            exit;
        } else {
            if (file_exists($configFilePath)) {
                @unlink($configFilePath);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Custom public URL cleared'
            ]);
            exit;
        }
    }
}

// 1. Check if the current incoming request is already routed through ngrok / reverse proxy
$forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';

$directHost = !empty($forwardedHost) ? $forwardedHost : $host;
$isDirectNgrok = (strpos($directHost, 'ngrok') !== false || (!in_array(explode(':', $directHost)[0], ['localhost', '127.0.0.1', '::1']) && !filter_var(explode(':', $directHost)[0], FILTER_VALIDATE_IP)));

if ($isDirectNgrok) {
    $publicOrigin = "{$proto}://{$directHost}";
    echo json_encode([
        'success' => true,
        'source' => 'direct_request',
        'is_ngrok' => true,
        'public_url' => $publicOrigin,
        'hostname' => $directHost,
        'protocol' => $proto
    ]);
    exit;
}

// 2. Query ngrok local management API (Default on port 4040)
$ngrokApiUrl = 'http://127.0.0.1:4040/api/tunnels';
$ngrokData = null;

if (function_exists('curl_init')) {
    $ch = curl_init($ngrokApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 300);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($response)) {
        $ngrokData = json_decode($response, true);
    }
}

if (!$ngrokData && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 0.4,
            'ignore_errors' => true
        ]
    ]);
    $response = @file_get_contents($ngrokApiUrl, false, $ctx);
    if (!empty($response)) {
        $ngrokData = json_decode($response, true);
    }
}

if (!empty($ngrokData['tunnels']) && is_array($ngrokData['tunnels'])) {
    $httpsTunnel = null;
    $fallbackTunnel = null;

    foreach ($ngrokData['tunnels'] as $tunnel) {
        if (!empty($tunnel['public_url'])) {
            if (isset($tunnel['proto']) && $tunnel['proto'] === 'https') {
                $httpsTunnel = $tunnel['public_url'];
                break;
            }
            if (!$fallbackTunnel) {
                $fallbackTunnel = $tunnel['public_url'];
            }
        }
    }

    $detectedPublicUrl = $httpsTunnel ?? $fallbackTunnel;
    if ($detectedPublicUrl) {
        $detectedPublicUrl = rtrim($detectedPublicUrl, '/');
        echo json_encode([
            'success' => true,
            'source' => 'ngrok_api',
            'is_ngrok' => true,
            'public_url' => $detectedPublicUrl,
            'tunnels_count' => count($ngrokData['tunnels'])
        ]);
        exit;
    }
}

// 3. Check saved custom public URL in config file
if (file_exists($configFilePath)) {
    $savedConfig = json_decode(@file_get_contents($configFilePath), true);
    if (!empty($savedConfig['custom_url'])) {
        echo json_encode([
            'success' => true,
            'source' => 'saved_config',
            'is_ngrok' => (strpos($savedConfig['custom_url'], 'ngrok') !== false),
            'public_url' => rtrim($savedConfig['custom_url'], '/'),
            'updated_at' => $savedConfig['updated_at'] ?? null
        ]);
        exit;
    }
}

// 4. Fallback to default configured ngrok URL
$defaultNgrok = 'https://earthling-retype-aroma.ngrok-free.dev';
$serverIp = '192.168.1.139';
$port = !empty($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], ['80', '443']) ? ':' . $_SERVER['SERVER_PORT'] : '';
$lanOrigin = "http://{$serverIp}{$port}";

echo json_encode([
    'success' => true,
    'source' => 'default_ngrok',
    'is_ngrok' => true,
    'public_url' => $defaultNgrok,
    'lan_url' => $lanOrigin,
    'local_origin' => "{$proto}://{$host}",
    'message' => 'Active ngrok URL configured: ' . $defaultNgrok
]);
