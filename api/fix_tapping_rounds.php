<?php
/**
 * GeoRubber Watch - Fix & Re-sequence Existing Yield Logs Tapping Rounds
 * Re-sequences all historical yield logs per plot and month to start from 1, 2, 3...
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
initDatabaseIfNeeded();

$pdo = getDatabaseConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    // 1. Fetch all yield logs ordered chronologically by plot and date
    $monthExpr = ($driver === 'pgsql') ? "TO_CHAR(harvest_date, 'YYYY-MM')" : "SUBSTR(CAST(harvest_date AS TEXT), 1, 7)";
    $stmt = $pdo->query("
        SELECT id, plot_id, harvest_date, tapping_round, {$monthExpr} as harvest_month
        FROM yield_logs
        ORDER BY plot_id ASC, harvest_date ASC, id ASC
    ");
    $allLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($allLogs as $log) {
        $plotId = (int)($log['plot_id'] ?? 0);
        $month = $log['harvest_month'] ?: substr($log['harvest_date'], 0, 7);
        $key = $plotId . '_' . $month;
        if (!isset($groups[$key])) {
            $groups[$key] = [];
        }
        $groups[$key][] = $log;
    }

    $updateStmt = $pdo->prepare("UPDATE yield_logs SET tapping_round = ? WHERE id = ?");

    $updatedCount = 0;
    $details = [];

    $pdo->beginTransaction();

    foreach ($groups as $key => $logs) {
        $round = 1;
        foreach ($logs as $log) {
            $logId = (int)$log['id'];
            $currentRound = (int)$log['tapping_round'];
            if ($currentRound !== $round) {
                $updateStmt->execute([$round, $logId]);
                $updatedCount++;
                $details[] = [
                    'id' => $logId,
                    'plot_id' => $log['plot_id'],
                    'harvest_date' => $log['harvest_date'],
                    'old_round' => $currentRound,
                    'new_round' => $round
                ];
            }
            $round++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "ปรับปรุงรอบการกรีดยางประวัติเดิมให้เริ่มต้นจาก 1 เรียบร้อยแล้ว (อัปเดตทั้งหมด {$updatedCount} รายการ)",
        'total_records' => count($allLogs),
        'updated_count' => $updatedCount,
        'changes' => array_slice($details, 0, 50)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการปรับปรุงข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
