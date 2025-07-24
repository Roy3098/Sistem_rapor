<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id']) && isset($_POST['question_text'])) {
    $question_id = $_POST['question_id'];
    $question_text = $_POST['question_text'];
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$question) {
        echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
        exit();
    }
    if ($question['question_type'] === 'multiple_choice') {
        $option_a = $_POST['option_a'] ?? '';
        $option_b = $_POST['option_b'] ?? '';
        $option_c = $_POST['option_c'] ?? '';
        $option_d = $_POST['option_d'] ?? '';
        $correct_answer = $_POST['correct_answer'] ?? '';
        $stmt = $db->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=? WHERE id=?");
        $result = $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $question_id]);
    } else {
        $stmt = $db->prepare("UPDATE questions SET question_text=? WHERE id=?");
        $result = $stmt->execute([$question_text, $question_id]);
    }
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update soal']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}