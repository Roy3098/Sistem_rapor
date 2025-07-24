<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'], $_POST['subject'], $_POST['semester'], $_POST['grade'])) {
    $student_name = $_POST['student_name'];
    $subject = $_POST['subject'];
    $semester = $_POST['semester'];
    $grade = $_POST['grade'];
    $db = getDbConnection();
    // Cari student_id dan subject_id
    $stmt = $db->prepare("SELECT id FROM students WHERE name = ? LIMIT 1");
    $stmt->execute([$student_name]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT id FROM subjects WHERE name = ? LIMIT 1");
    $stmt->execute([$subject]);
    $subjectRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student || !$subjectRow) {
        echo json_encode(['success' => false, 'message' => 'Siswa atau mapel tidak ditemukan']);
        exit();
    }
    $student_id = $student['id'];
    $subject_id = $subjectRow['id'];
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