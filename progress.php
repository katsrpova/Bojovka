<?php
/**
 * progress.php
 * Uloží postup hráče ve hře (game_progress) a při dokončení přidá body.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$gameId = trim($_POST['game_id'] ?? '');
$waypointIndex = intval($_POST['waypoint_index'] ?? -1);

if (!$gameId || $waypointIndex < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'game_id a waypoint_index jsou povinné']);
    exit;
}

$pdo = getDB();

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM waypoints WHERE game_id = :id");
$totalStmt->execute(['id' => $gameId]);
$total = (int)$totalStmt->fetchColumn();

if ($total === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Hra nenalezena']);
    exit;
}

$progStmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = :uid AND game_id = :gid");
$progStmt->execute(['uid' => $userId, 'gid' => $gameId]);
$progress = $progStmt->fetch();

$wasCompleted = $progress && (bool)$progress['completed'];
$newCount = $waypointIndex + 1;

if (!$progress) {
    $pdo->prepare(
        "INSERT INTO game_progress (user_id, game_id, waypoints_completed, completed)
         VALUES (:uid, :gid, :count, 0)"
    )->execute(['uid' => $userId, 'gid' => $gameId, 'count' => $newCount]);
} else {
    $newCount = max($newCount, (int)$progress['waypoints_completed']);
    $pdo->prepare(
        "UPDATE game_progress SET waypoints_completed = :count WHERE user_id = :uid AND game_id = :gid"
    )->execute(['count' => $newCount, 'uid' => $userId, 'gid' => $gameId]);
}

$nowCompleted = $newCount >= $total;
$pointsAwarded = 0;

if ($nowCompleted && !$wasCompleted) {
    $pdo->prepare(
        "UPDATE game_progress SET completed = 1, completed_at = NOW() WHERE user_id = :uid AND game_id = :gid"
    )->execute(['uid' => $userId, 'gid' => $gameId]);

    $diffStmt = $pdo->prepare("SELECT difficulty FROM games WHERE id = :id");
    $diffStmt->execute(['id' => $gameId]);
    $difficulty = $diffStmt->fetchColumn() ?: 'medium';
    $multiplier = $difficulty === 'easy' ? 1 : ($difficulty === 'hard' ? 2 : 1.5);
    $pointsAwarded = (int)round(100 * $total * $multiplier);

    $pdo->prepare("UPDATE users SET points = points + :pts WHERE id = :uid")
        ->execute(['pts' => $pointsAwarded, 'uid' => $userId]);
}

echo json_encode([
    'success'             => true,
    'waypoints_completed' => $newCount,
    'completed'           => $nowCompleted,
    'points_awarded'      => $pointsAwarded,
]);
