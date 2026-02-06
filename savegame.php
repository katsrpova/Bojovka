<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Kontrola, že je to POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

// Získej data z formuláře
$gameName = trim($_POST['game_name'] ?? '');
$gameDescription = trim($_POST['game_description'] ?? '');
$difficulty = $_POST['difficulty'] ?? 'medium';
$estimatedTime = intval($_POST['estimated_time'] ?? 60);
$waypointsJson = $_POST['waypoints'] ?? '[]';

// Validace
$errors = [];

if (empty($gameName)) {
    $errors[] = "Název hry je povinný";
}

if (empty($gameDescription)) {
    $errors[] = "Popis hry je povinný";
}

// Dekóduj waypoints
$waypoints = json_decode($waypointsJson, true);
if (!is_array($waypoints) || count($waypoints) === 0) {
    $errors[] = "Musíte přidat alespoň jeden waypoint";
}

// Pokud jsou chyby, vrať se zpět
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: create.php');
    exit;
}

// TODO: Zde by mělo být uložení do databáze
// Pro teď jen vypíšeme data

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Vytvoř unikátní ID pro hru
$gameId = uniqid('game_', true);

// Data pro uložení
$gameData = [
    'id' => $gameId,
    'name' => $gameName,
    'description' => $gameDescription,
    'difficulty' => $difficulty,
    'estimated_time' => $estimatedTime,
    'creator_id' => $userId,
    'creator_name' => $userName,
    'waypoints' => $waypoints,
    'created_at' => date('Y-m-d H:i:s')
];

// Pro demo účely - uložíme do session (v produkci by to bylo v DB)
if (!isset($_SESSION['games'])) {
    $_SESSION['games'] = [];
}
$_SESSION['games'][$gameId] = $gameData;

// Uložíme také do souboru pro persistenci
$gamesDir = __DIR__ . '/games';
if (!is_dir($gamesDir)) {
    mkdir($gamesDir, 0755, true);
}

$gameFile = $gamesDir . '/' . $gameId . '.json';
file_put_contents($gameFile, json_encode($gameData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Úspěšná zpráva
$_SESSION['success'] = "Hra '{$gameName}' byla úspěšně vytvořena!";
header('Location: dashboard.php');
exit;
?>