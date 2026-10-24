<?php
/**
 * friends.php
 * Přidá nebo odebere vzájemné přátelství mezi přihlášeným uživatelem a cílovým uživatelem.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

require_once 'config.php';

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$targetId = trim($_POST['user_id'] ?? '');

if ($targetId && $targetId !== $userId) {
    $pdo = getDB();

    if ($action === 'add') {
        $ins = $pdo->prepare("INSERT IGNORE INTO friendships (user_id, friend_id) VALUES (:a, :b)");
        $ins->execute(['a' => $userId, 'b' => $targetId]);
        $ins->execute(['a' => $targetId, 'b' => $userId]);
    } elseif ($action === 'remove') {
        $del = $pdo->prepare("DELETE FROM friendships WHERE user_id = :a AND friend_id = :b");
        $del->execute(['a' => $userId, 'b' => $targetId]);
        $del->execute(['a' => $targetId, 'b' => $userId]);
    }
}

header('Location: profile.php');
exit;
