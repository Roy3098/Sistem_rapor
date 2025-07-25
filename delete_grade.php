<?php
require_once 'includes/functions.php';
header('Content-Type: application/json');

$student_id = $_POST['student_id'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;
$semester = $_POST['semester'] ?? null;
$academic_year = $_POST['academic_year'] ?? null;
$all = $_POST['all'] ?? null;

if (!$student_id || !$semester) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

if ($all) {
    $db = getDbConnection();
    if ($academic_year) {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND semester = ? AND academic_year = ?");
        $stmt->execute([$student_id, $semester, $academic_year]);
    } else {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND semester = ?");
        $stmt->execute([$student_id, $semester]);
    }
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Semua nilai siswa berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dihapus. Data tidak ditemukan.']);
    }
    exit;
}

if ($subject_id) {
    $result = deleteGrade($student_id, $subject_id, $semester, $academic_year);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Parameter subject_id tidak lengkap.']);
}