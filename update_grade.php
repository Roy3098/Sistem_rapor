<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'], $_POST['subject_id'], $_POST['semester'], $_POST['grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $grade = $_POST['grade'];
    $db = getDbConnection();
    // Update grade
    $stmt = $db->prepare("UPDATE grades SET grade = ? WHERE student_id = ? AND subject_id = ? AND semester = ?");
    $result = $stmt->execute([$grade, $student_id, $subject_id, $semester]);
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update nilai']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}