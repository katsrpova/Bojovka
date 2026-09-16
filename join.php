<?php
/**
 * join.php
 * Přijme kód (z QR nebo zadání), ověří ho a přesměruje na hru.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    // Ulož cílový kód do session, po přihlášení se sem vrátí
    $_SESSION['redirect_code'] = $_GET['code'] ?? '';
    header('Location: index.html');
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));

if (!$code || strlen($code) !== 6) {
    header('Location: dashboard.php?error=invalid_code');
    exit;
}

// Ulož si session ID, pak zámek nad session souborem uvolni - jinak by interní
// HTTP volání na game_code.php (které si session zamyká znovu) uvázlo v deadlocku.
$sessionId = session_id();
session_write_close();

// Ověř kód přes API endpoint
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $baseUrl . '/game_code.php?action=validate&code=' . urlencode($code),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE         => 'PHPSESSID=' . $sessionId,
]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!$data || !$data['valid']) {
    $err = urlencode($data['error'] ?? 'Neplatný kód');
    header("Location: dashboard.php?error=$err");
    exit;
}

// Přesměruj na hru
header('Location: play.php?id=' . urlencode($data['game_id']));
exit;
?>