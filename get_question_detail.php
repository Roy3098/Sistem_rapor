<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id'])) {
    $question_id = $_POST['question_id'];
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($question) {
        echo json_encode(['success' => true, 'question' => $question]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}