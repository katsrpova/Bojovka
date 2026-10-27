<?php
/**
 * leaderboard.php
 * Vrátí pořadí hráčů v rámci konkrétní skupinové hry (session).
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

$code = strtoupper(trim($_GET['session'] ?? ''));

if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'session je povinné']);
    exit;
}

$pdo = getDB();

$sessStmt = $pdo->prepare("SELECT game_id FROM game_sessions WHERE code = :code");
$sessStmt->execute(['code' => $code]);
$gameId = $sessStmt->fetchColumn();

if (!$gameId) {
    http_response_code(404);
    echo json_encode(['error' => 'Session nenalezena']);
    exit;
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM waypoints WHERE game_id = :id");
$totalStmt->execute(['id' => $gameId]);
$total = (int)$totalStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT u.name, gp.waypoints_completed, gp.completed, gp.completed_at, gp.started_at
     FROM game_progress gp JOIN users u ON u.id = gp.user_id
     WHERE gp.session_code = :code
     ORDER BY gp.completed DESC, gp.waypoints_completed DESC, gp.completed_at ASC, gp.started_at ASC"
);
$stmt->execute(['code' => $code]);
$rows = $stmt->fetchAll();

$players = array_map(function ($row) use ($total) {
    return [
        'name'      => $row['name'],
        'completed' => (bool)$row['completed'],
        'progress'  => (int)$row['waypoints_completed'],
        'total'     => $total,
    ];
}, $rows);

echo json_encode([
    'success' => true,
    'players' => $players,
]);
