<?php
/**
 * submission_status.php
 * Hráč si ověřuje, jestli už hostitel schválil/zamítl jeho čekající fotku.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$gameId = trim($_GET['game_id'] ?? '');
$waypointIndex = intval($_GET['waypoint_index'] ?? -1);
$sessionCode = strtoupper(trim($_GET['session'] ?? '')) ?: null;

if (!$gameId || $waypointIndex < 0 || !$sessionCode) {
    http_response_code(400);
    echo json_encode(['error' => 'game_id, waypoint_index a session jsou povinné']);
    exit;
}

$pdo = getDB();

$wpStmt = $pdo->prepare(
    "SELECT id FROM waypoints WHERE game_id = :gid ORDER BY order_index ASC LIMIT 1 OFFSET :idx"
);
$wpStmt->bindValue('gid', $gameId);
$wpStmt->bindValue('idx', $waypointIndex, PDO::PARAM_INT);
$wpStmt->execute();
$waypointId = $wpStmt->fetchColumn();

if (!$waypointId) {
    http_response_code(404);
    echo json_encode(['error' => 'Úkol nenalezen']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT status FROM waypoint_submissions
     WHERE user_id = :uid AND waypoint_id = :wid AND session_code = :code
     ORDER BY submitted_at DESC LIMIT 1"
);
$stmt->execute(['uid' => $userId, 'wid' => $waypointId, 'code' => $sessionCode]);
$status = $stmt->fetchColumn() ?: null;

echo json_encode(['success' => true, 'status' => $status]);
