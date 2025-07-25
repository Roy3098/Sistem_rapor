<?php
require_once 'includes/functions.php';
header('Content-Type: application/json');

$student_id = $_POST['student_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$semester = $_POST['semester'] ?? null;
$academic_year = $_POST['academic_year'] ?? null;

if (!$student_id || !$subject_id || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$result = deleteGrade($student_id, $subject_id, $semester, $academic_year);
echo json_encode($result);