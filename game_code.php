<?php
/**
 * game_code.php
 * Správa herních kódů - generování, ověření, uvolnění
 * 
 * Kódy jsou 6 znaků, alfanumerické (A-Z, 0-9), bez matoucích znaků (0,O,I,1,L)
 * Kódy se ukládají do souboru codes.json (v produkci použít databázi)
 */

session_start();
header('Content-Type: application/json');

// Pouze přihlášení uživatelé
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

// Soubor pro uložení kódů (v produkci nahradit DB)
$CODES_FILE = __DIR__ . '/data/game_codes.json';

// Ujisti se, že složka existuje
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

/**
 * Načte všechny kódy ze souboru
 */
function loadCodes(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Uloží kódy do souboru
 */
function saveCodes(string $file, array $codes): void {
    file_put_contents($file, json_encode($codes, JSON_PRETTY_PRINT));
}

/**
 * Vygeneruje unikátní 6-znakový alfanumerický kód
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

/**
 * Vyčistí expirované kódy (starší než 24 hodin)
 */
function cleanExpiredCodes(array &$codes): void {
    $now = time();
    foreach ($codes as $code => $data) {
        // Kód expiruje po 24 hodinách od vytvoření nebo po uvolnění
        if (isset($data['expires_at']) && $data['expires_at'] < $now) {
            unset($codes[$code]);
        }
    }
}

// ==================== ROUTER ====================
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ----- GENEROVÁNÍ KÓDU -----
    case 'generate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $gameId   = intval($_POST['game_id'] ?? 0);
        $gameTitle = trim($_POST['game_title'] ?? '');

        if (!$gameId || !$gameTitle) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id a game_title jsou povinné']);
            exit;
        }

        $codes = loadCodes($CODES_FILE);
        cleanExpiredCodes($codes);

        // Zkontroluj, zda hra již nemá aktivní kód
        foreach ($codes as $existingCode => $data) {
            if ($data['game_id'] === $gameId && $data['status'] === 'active') {
                // Vrať existující kód
                echo json_encode([
                    'success' => true,
                    'code'    => $existingCode,
                    'reused'  => true,
                    'expires_at' => $data['expires_at'],
                ]);
                exit;
            }
        }

        // Vygeneruj nový unikátní kód (max 20 pokusů)
        $newCode = '';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = generateCode();
            if (!isset($codes[$candidate]) || $codes[$candidate]['status'] !== 'active') {
                $newCode = $candidate;
                break;
            }
        }

        if (!$newCode) {
            http_response_code(500);
            echo json_encode(['error' => 'Nepodařilo se vygenerovat unikátní kód. Zkuste znovu.']);
            exit;
        }

        $expiresAt = time() + (24 * 3600); // 24 hodin

        $codes[$newCode] = [
            'game_id'    => $gameId,
            'game_title' => $gameTitle,
            'creator_id' => $_SESSION['user_id'],
            'status'     => 'active',   // active | released
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'join_count' => 0,
        ];

        saveCodes($CODES_FILE, $codes);

        echo json_encode([
            'success'    => true,
            'code'       => $newCode,
            'reused'     => false,
            'expires_at' => $expiresAt,
        ]);
        break;

    // ----- OVĚŘENÍ KÓDU (při připojení hráče) -----
    case 'validate':
        $code = strtoupper(trim($_GET['code'] ?? $_POST['code'] ?? ''));

        if (strlen($code) !== 6) {
            echo json_encode(['valid' => false, 'error' => 'Kód musí mít 6 znaků']);
            exit;
        }

        $codes = loadCodes($CODES_FILE);
        cleanExpiredCodes($codes);

        if (!isset($codes[$code])) {
            echo json_encode(['valid' => false, 'error' => 'Kód neexistuje nebo vypršel']);
            exit;
        }

        $entry = $codes[$code];

        if ($entry['status'] !== 'active') {
            echo json_encode(['valid' => false, 'error' => 'Tento kód již není aktivní']);
            exit;
        }

        if ($entry['expires_at'] < time()) {
            echo json_encode(['valid' => false, 'error' => 'Platnost kódu vypršela']);
            exit;
        }

        // Zaznamenej připojení
        $codes[$code]['join_count']++;
        $codes[$code]['last_join'] = time();
        saveCodes($CODES_FILE, $codes);

        echo json_encode([
            'valid'      => true,
            'game_id'    => $entry['game_id'],
            'game_title' => $entry['game_title'],
            'join_count' => $codes[$code]['join_count'],
        ]);
        break;

    // ----- UVOLNĚNÍ KÓDU (tvůrce hry ho deaktivuje) -----
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

        $codes = loadCodes($CODES_FILE);

        if (!isset($codes[$code])) {
            echo json_encode(['success' => false, 'error' => 'Kód nenalezen']);
            exit;
        }

        // Pouze tvůrce může uvolnit kód
        if ($codes[$code]['creator_id'] !== $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Nemáte oprávnění uvolnit tento kód']);
            exit;
        }

        // Označ jako uvolněný + nastav krátkou expiraci (kód lze znovu použít za chvíli)
        $codes[$code]['status']     = 'released';
        $codes[$code]['expires_at'] = time() + 3600; // uvolní se za hodinu

        saveCodes($CODES_FILE, $codes);

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

        $codes = loadCodes($CODES_FILE);

        if (!isset($codes[$code])) {
            echo json_encode(['found' => false]);
            exit;
        }

        $entry = $codes[$code];
        echo json_encode([
            'found'      => true,
            'status'     => $entry['status'],
            'game_id'    => $entry['game_id'],
            'game_title' => $entry['game_title'],
            'join_count' => $entry['join_count'],
            'expires_at' => $entry['expires_at'],
            'is_mine'    => ($entry['creator_id'] === $_SESSION['user_id']),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Neznámá akce: ' . htmlspecialchars($action)]);
        break;
}
?>