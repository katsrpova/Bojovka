<?php
/**
 * join.php
 * Přijme kód (z QR nebo zadání), ověří ho přímo v DB a přesměruje do dané skupinové hry.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    // Ulož cílový kód do session, po přihlášení se sem vrátí
    $_SESSION['redirect_code'] = $_GET['code'] ?? '';
    header('Location: index.html');
    exit;
}

require_once 'config.php';

$code = strtoupper(trim($_GET['code'] ?? ''));

if (!$code || strlen($code) !== 6) {
    header('Location: dashboard.php?error=invalid_code');
    exit;
}

$stmt = getDB()->prepare(
    "SELECT game_id, status, expires_at FROM game_sessions WHERE code = :code"
);
$stmt->execute(['code' => $code]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: dashboard.php?error=' . urlencode('Kód neexistuje'));
    exit;
}

if ($session['status'] !== 'active') {
    header('Location: dashboard.php?error=' . urlencode('Tento kód již není aktivní'));
    exit;
}

if (strtotime($session['expires_at']) < time()) {
    header('Location: dashboard.php?error=' . urlencode('Platnost kódu vypršela'));
    exit;
}

// Přesměruj na hru v rámci téhle skupinové session
header('Location: play.php?id=' . urlencode($session['game_id']) . '&session=' . urlencode($code));
exit;
