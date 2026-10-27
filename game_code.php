<?php
/**
 * game_code.php
 * Správa herních kódů pro skupinové hraní - generování, ověření, uvolnění.
 * Kódy jsou 6 znaků, alfanumerické (A-Z, 0-9), bez matoucích znaků (0,O,I,1,L)
 * a odpovídají řádku v tabulce game_sessions.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

/**
 * Vygeneruje unikátní 6-znakový alfanumerický kód.
 * Vynechány matoucí znaky: 0, O, I, 1, L
 */
function generateCode(): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----- GENEROVÁNÍ KÓDU -----
    case 'generate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $gameId = trim($_POST['game_id'] ?? '');

        if (!$gameId) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id je povinné']);
            exit;
        }

        // Existuje už aktivní session, kterou založil tenhle uživatel pro tuhle hru?
        $existingStmt = $pdo->prepare(
            "SELECT code, expires_at FROM game_sessions
             WHERE game_id = :gid AND host_id = :uid AND status = 'active' AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1"
        );
        $existingStmt->execute(['gid' => $gameId, 'uid' => $_SESSION['user_id']]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            echo json_encode([
                'success'    => true,
                'code'       => $existing['code'],
                'reused'     => true,
                'expires_at' => strtotime($existing['expires_at']),
            ]);
            exit;
        }

        // Vygeneruj nový unikátní kód (max 20 pokusů)
        $newCode = '';
        $checkStmt = $pdo->prepare("SELECT 1 FROM game_sessions WHERE code = :code AND status = 'active' AND expires_at > NOW()");
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = generateCode();
            $checkStmt->execute(['code' => $candidate]);
            if (!$checkStmt->fetch()) {
                $newCode = $candidate;
                break;
            }
        }

        if (!$newCode) {
            http_response_code(500);
            echo json_encode(['error' => 'Nepodařilo se vygenerovat unikátní kód. Zkuste znovu.']);
            exit;
        }

        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24 hodin

        $pdo->prepare(
            "INSERT INTO game_sessions (code, game_id, host_id, status, expires_at)
             VALUES (:code, :gid, :uid, 'active', :expires_at)"
        )->execute([
            'code'       => $newCode,
            'gid'        => $gameId,
            'uid'        => $_SESSION['user_id'],
            'expires_at' => $expiresAt,
        ]);

        echo json_encode([
            'success'    => true,
            'code'       => $newCode,
            'reused'     => false,
            'expires_at' => strtotime($expiresAt),
        ]);
        break;

    // ----- OVĚŘENÍ KÓDU (při připojení hráče) -----
    case 'validate':
        $code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));

        if (strlen($code) !== 6) {
            echo json_encode(['valid' => false, 'error' => 'Kód musí mít 6 znaků']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT gs.game_id, g.title AS game_title, gs.status, gs.expires_at
             FROM game_sessions gs JOIN games g ON g.id = gs.game_id
             WHERE gs.code = :code"
        );
        $stmt->execute(['code' => $code]);
        $entry = $stmt->fetch();

        if (!$entry) {
            echo json_encode(['valid' => false, 'error' => 'Kód neexistuje']);
            exit;
        }

        if ($entry['status'] !== 'active') {
            echo json_encode(['valid' => false, 'error' => 'Tento kód již není aktivní']);
            exit;
        }

        if (strtotime($entry['expires_at']) < time()) {
            echo json_encode(['valid' => false, 'error' => 'Platnost kódu vypršela']);
            exit;
        }

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM game_progress WHERE session_code = :code");
        $countStmt->execute(['code' => $code]);

        echo json_encode([
            'valid'      => true,
            'game_id'    => $entry['game_id'],
            'game_title' => $entry['game_title'],
            'join_count' => (int)$countStmt->fetchColumn(),
        ]);
        break;

    // ----- UVOLNĚNÍ KÓDU (tvůrce ho deaktivuje) -----
    case 'release':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Kód je povinný']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT host_id FROM game_sessions WHERE code = :code");
        $stmt->execute(['code' => $code]);
        $hostId = $stmt->fetchColumn();

        if ($hostId === false) {
            echo json_encode(['success' => false, 'error' => 'Kód nenalezen']);
            exit;
        }

        if ($hostId !== $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Nemáte oprávnění uvolnit tento kód']);
            exit;
        }

        $pdo->prepare("UPDATE game_sessions SET status = 'released' WHERE code = :code")
            ->execute(['code' => $code]);

        echo json_encode(['success' => true, 'message' => 'Kód byl uvolněn']);
        break;

    // ----- STAV KÓDU (pro živou kontrolu) -----
    case 'status':
        $code = strtoupper(trim($_GET['code'] ?? ''));

        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'Kód je povinný']);
            exit;
        }

        $stmt = $pdo->prepare(
            "SELECT gs.game_id, g.title AS game_title, gs.status, gs.expires_at, gs.host_id
             FROM game_sessions gs JOIN games g ON g.id = gs.game_id
             WHERE gs.code = :code"
        );
        $stmt->execute(['code' => $code]);
        $entry = $stmt->fetch();

        if (!$entry) {
            echo json_encode(['found' => false]);
            exit;
        }

        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM game_progress WHERE session_code = :code");
        $countStmt->execute(['code' => $code]);

        echo json_encode([
            'found'      => true,
            'status'     => $entry['status'],
            'game_id'    => $entry['game_id'],
            'game_title' => $entry['game_title'],
            'join_count' => (int)$countStmt->fetchColumn(),
            'expires_at' => strtotime($entry['expires_at']),
            'is_mine'    => ($entry['host_id'] === $_SESSION['user_id']),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Neznámá akce: ' . htmlspecialchars($action)]);
        break;
}
