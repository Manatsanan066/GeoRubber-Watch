<?php
/**
 * GeoRubber Watch - Root Configuration Bridge
 * Links root includes to /config/database.php and initializes $pdo
 */
require_once __DIR__ . '/config/database.php';
initDatabaseIfNeeded();
$pdo = getDatabaseConnection();
