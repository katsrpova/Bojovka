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

// Ověř kód přes API endpoint
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'http://localhost/bojovka/game_code.php?action=validate&code=' . urlencode($code),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE         => 'PHPSESSID=' . session_id(),
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
header('Location: play.php?id=' . intval($data['game_id']));
exit;
?>