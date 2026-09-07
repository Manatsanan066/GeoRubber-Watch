<?php
/**
 * GeoRubber Watch - View Plot & Traceability Passport Alias
 */
$id = $_GET['id'] ?? ($_GET['plot_id'] ?? '');
$token = $_GET['token'] ?? '';
$code = $_GET['code'] ?? '';

$params = [];
if (!empty($token)) $params['token'] = $token;
if (!empty($code)) $params['code'] = $code;
if (!empty($id)) $params['id'] = $id;

$queryString = !empty($params) ? '?' . http_build_query($params) : '';
header("Location: trace.php{$queryString}");
exit;
