<?php
session_start();
require_once 'config.php';

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

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Vytvoř unikátní ID pro hru
$gameId = uniqid('game_', true);

$pdo = null;
try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $gameStmt = $pdo->prepare(
        "INSERT INTO games (id, title, description, difficulty, estimated_time, creator_id, creator_name)
         VALUES (:id, :title, :description, :difficulty, :estimated_time, :creator_id, :creator_name)"
    );
    $gameStmt->execute([
        'id'             => $gameId,
        'title'          => $gameName,
        'description'    => $gameDescription,
        'difficulty'     => $difficulty,
        'estimated_time' => $estimatedTime,
        'creator_id'     => $userId,
        'creator_name'   => $userName,
    ]);

    $wpStmt = $pdo->prepare(
        "INSERT INTO waypoints (game_id, order_index, name, description, question, question_image, answer, answer_type, lat, lng)
         VALUES (:game_id, :order_index, :name, :description, :question, :question_image, :answer, :answer_type, :lat, :lng)"
    );
    foreach ($waypoints as $index => $wp) {
        $answerType = ($wp['answer_type'] ?? 'text') === 'photo' ? 'photo' : 'text';

        // Obrázek musí být cesta, kterou skutečně vrátil upload_question_image.php
        $questionImage = $wp['question_image'] ?? null;
        if ($questionImage !== null && !preg_match('#^uploads/questions/[a-f0-9]{32}\.(jpg|png|webp|gif)$#', $questionImage)) {
            $questionImage = null;
        }

        $wpStmt->execute([
            'game_id'        => $gameId,
            'order_index'    => $index,
            'name'           => $wp['name'] ?? ('Úkol ' . ($index + 1)),
            'description'    => $wp['description'] ?? '',
            'question'       => $wp['question'] ?? '',
            'question_image' => $questionImage,
            'answer'         => $answerType === 'text' ? ($wp['answer'] ?? '') : '',
            'answer_type'    => $answerType,
            'lat'            => $wp['lat'],
            'lng'            => $wp['lng'],
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo !== null && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['errors'] = ["Chyba při ukládání hry do databáze: " . $e->getMessage()];
    $_SESSION['form_data'] = $_POST;
    header('Location: create.php');
    exit;
}

// Úspěšná zpráva
$_SESSION['success'] = "Hra '{$gameName}' byla úspěšně vytvořena!";
header('Location: dashboard.php');
exit;
?>