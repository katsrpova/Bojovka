<?php
/**
 * feedback.php
 * Uloží zpětnou vazbu od uživatele do DB.
 *
 * TODO: Až bude appka na ostrém serveru, sem přidat skutečné odeslání e-mailu
 * na kat.srpova@gmail.com (PHP mail() nebo SMTP/PHPMailer - viz poznámka v paměti).
 * Prozatím se zprávy jen ukládají do tabulky `feedback` a lze je procházet přes phpMyAdmin.
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

$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($title === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Vyplňte prosím název i zprávu.']);
    exit;
}

if (mb_strlen($title) > 150) {
    $title = mb_substr($title, 0, 150);
}
if (mb_strlen($message) > 5000) {
    $message = mb_substr($message, 0, 5000);
}

getDB()->prepare(
    "INSERT INTO feedback (user_id, user_name, title, message) VALUES (:uid, :uname, :title, :message)"
)->execute([
    'uid'     => $_SESSION['user_id'],
    'uname'   => $_SESSION['user_name'],
    'title'   => $title,
    'message' => $message,
]);

echo json_encode(['success' => true]);
