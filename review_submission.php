<?php
/**
 * review_submission.php
 * Hostitel skupinové hry si prohlíží a schvaluje/zamítá fotky nahrané hráči.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

$pdo = getDB();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----- SEZNAM ČEKAJÍCÍCH FOTEK PRO DANOU SESSION -----
    case 'list':
        $code = strtoupper(trim($_GET['session'] ?? ''));

        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'session je povinné']);
            exit;
        }

        $hostStmt = $pdo->prepare("SELECT host_id FROM game_sessions WHERE code = :code");
        $hostStmt->execute(['code' => $code]);
        $hostId = $hostStmt->fetchColumn();

        if ($hostId === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Session nenalezena']);
            exit;
        }

        if ($hostId !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Jen hostitel může schvalovat odpovědi']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT ws.id, ws.image_path, ws.submitted_at, u.name AS user_name, w.name AS waypoint_name
             FROM waypoint_submissions ws
             JOIN users u ON u.id = ws.user_id
             JOIN waypoints w ON w.id = ws.waypoint_id
             WHERE ws.session_code = :code AND ws.status = 'pending'
             ORDER BY ws.submitted_at ASC"
        );
        $stmt->execute(['code' => $code]);

        echo json_encode(['success' => true, 'submissions' => $stmt->fetchAll()]);
        break;

    // ----- SCHVÁLENÍ / ZAMÍTNUTÍ -----
    case 'approve':
    case 'reject':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $submissionId = intval($_POST['submission_id'] ?? 0);

        if (!$submissionId) {
            http_response_code(400);
            echo json_encode(['error' => 'submission_id je povinné']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT ws.*, w.order_index, gs.host_id
             FROM waypoint_submissions ws
             JOIN waypoints w ON w.id = ws.waypoint_id
             JOIN game_sessions gs ON gs.code = ws.session_code
             WHERE ws.id = :id"
        );
        $stmt->execute(['id' => $submissionId]);
        $submission = $stmt->fetch();

        if (!$submission) {
            http_response_code(404);
            echo json_encode(['error' => 'Odpověď nenalezena']);
            exit;
        }

        if ($submission['host_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Jen hostitel může schvalovat odpovědi']);
            exit;
        }

        if ($submission['status'] !== 'pending') {
            echo json_encode(['success' => true, 'already_reviewed' => true]);
            exit;
        }

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare("UPDATE waypoint_submissions SET status = :status, reviewed_at = NOW() WHERE id = :id")
            ->execute(['status' => $newStatus, 'id' => $submissionId]);

        if ($newStatus === 'approved') {
            $result = completeWaypoint(
                $pdo,
                $submission['user_id'],
                $submission['game_id'],
                $submission['session_code'],
                (int)$submission['order_index']
            );
            echo json_encode(array_merge(['success' => true, 'status' => 'approved'], $result));
            exit;
        }

        echo json_encode(['success' => true, 'status' => 'rejected']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Neznámá akce']);
        break;
}
