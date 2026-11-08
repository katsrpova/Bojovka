<?php
/**
 * submit_photo_answer.php
 * Hráč nahraje fotku jako odpověď na úkol typu "photo".
 * Sólo hra: fotka se počítá okamžitě (sám hráč potvrzuje splnění).
 * Skupinová hra: fotka čeká na schválení hostitelem (viz review_submission.php).
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Chybí obrázek']);
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

$pdo = getDB();

$wpStmt = $pdo->prepare(
    "SELECT id, answer_type FROM waypoints WHERE game_id = :gid ORDER BY order_index ASC LIMIT 1 OFFSET :idx"
);
$wpStmt->bindValue('gid', $gameId);
$wpStmt->bindValue('idx', $waypointIndex, PDO::PARAM_INT);
$wpStmt->execute();
$waypoint = $wpStmt->fetch();

if (!$waypoint) {
    http_response_code(404);
    echo json_encode(['error' => 'Úkol nenalezen']);
    exit;
}

if ($waypoint['answer_type'] !== 'photo') {
    http_response_code(400);
    echo json_encode(['error' => 'Tento úkol neočekává foto odpověď']);
    exit;
}

try {
    $imagePath = storeUploadedImage($_FILES['image'], 'answers');
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Sólo hra: hráč si splnění potvrzuje sám, fotka se rovnou schvaluje.
// Skupinová hra: čeká na schválení hostitelem.
$status = $sessionCode ? 'pending' : 'approved';

$pdo->prepare(
    "INSERT INTO waypoint_submissions (waypoint_id, user_id, game_id, session_code, image_path, status, reviewed_at)
     VALUES (:wid, :uid, :gid, :code, :path, :status, :reviewed_at)"
)->execute([
    'wid'         => $waypoint['id'],
    'uid'         => $userId,
    'gid'         => $gameId,
    'code'        => $sessionCode,
    'path'        => $imagePath,
    'status'      => $status,
    'reviewed_at' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
]);

if ($status === 'approved') {
    $result = completeWaypoint($pdo, $userId, $gameId, $sessionCode, $waypointIndex);
    echo json_encode(array_merge(['success' => true, 'status' => 'approved'], $result));
    exit;
}

echo json_encode([
    'success' => true,
    'status'  => 'pending',
]);
