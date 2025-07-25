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
    // Hapus semua nilai siswa di semester tertentu
    $db = getDbConnection();
    $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND semester = ?");
    if ($stmt->execute([$student_id, $semester])) {
        echo json_encode(['success' => true, 'message' => 'Semua nilai siswa berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus nilai!']);
    }
    exit;
}

$result = deleteGrade($student_id, $subject_id, $semester, $academic_year);
echo json_encode($result);