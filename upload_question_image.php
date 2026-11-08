<?php
/**
 * upload_question_image.php
 * Nahraje obrázek k zadání úkolu (volá se z create.js při výběru souboru).
 * Vrací cestu k uloženému souboru, která se pak vloží do waypointu před uložením hry.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Chybí obrázek']);
    exit;
}

require_once 'config.php';

try {
    $path = storeUploadedImage($_FILES['image'], 'questions');
    echo json_encode(['success' => true, 'path' => $path]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
