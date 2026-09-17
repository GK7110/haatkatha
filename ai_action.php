<?php
require 'config.php';
require 'functions.php';

header('Content-Type: application/json');
$user = current_user();
if (!$user || $user['role'] !== 'artisan') {
    echo json_encode(['ok' => false, 'text' => 'Not authorised.']);
    exit;
}

$type = $_POST['type'] ?? '';

if ($type === 'description') {
    $result = ai_generate_description(
        clean($_POST['name'] ?? ''),
        clean($_POST['material'] ?? ''),
        clean($_POST['location'] ?? '')
    );
    ai_log($pdo, $user['id'], 'description', $_POST['name'] ?? '', $result['text']);

} elseif ($type === 'translate') {
    $result = ai_translate($_POST['text'] ?? '', $_POST['target'] ?? 'as');
    ai_log($pdo, $user['id'], 'translation', $_POST['text'] ?? '', $result['text']);

} elseif ($type === 'keywords') {
    $result = ai_suggest_keywords(
        clean($_POST['name'] ?? ''),
        clean($_POST['material'] ?? ''),
        clean($_POST['category'] ?? '')
    );
    ai_log($pdo, $user['id'], 'keywords', $_POST['name'] ?? '', $result['text']);

} else {
    $result = ['ok' => false, 'text' => 'Unknown action.'];
}

echo json_encode($result);
