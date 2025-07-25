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
    $academic_year = $_POST['academic_year'] ?? null;
    if ($grade > 90) {
        echo json_encode(['success' => false, 'message' => 'Nilai maksimal adalah 90!']);
        exit();
    }
    $db = getDbConnection();
    if ($academic_year) {
        $stmt = $db->prepare("UPDATE grades SET grade = ? WHERE student_id = ? AND subject_id = ? AND semester = ? AND academic_year = ?");
        $stmt->execute([$grade, $student_id, $subject_id, $semester, $academic_year]);
    } else {
        $stmt = $db->prepare("UPDATE grades SET grade = ? WHERE student_id = ? AND subject_id = ? AND semester = ?");
        $stmt->execute([$grade, $student_id, $subject_id, $semester]);
    }
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update nilai. Data tidak ditemukan atau tidak berubah.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}