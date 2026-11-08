<?php
/**
 * progress.php
 * Uloží postup hráče ve hře po správně zodpovězené textové otázce.
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
$sessionCode = strtoupper(trim($_POST['session'] ?? '')) ?: null;

if (!$gameId || $waypointIndex < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'game_id a waypoint_index jsou povinné']);
    exit;
}

$result = completeWaypoint(getDB(), $userId, $gameId, $sessionCode, $waypointIndex);

if (isset($result['error'])) {
    http_response_code(404);
    echo json_encode($result);
    exit;
}

echo json_encode(array_merge(['success' => true], $result));
