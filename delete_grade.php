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
    // Debug: cek data di database sebelum delete
    $debugSelect = null;
    if ($academic_year && $academic_year !== 'null' && $academic_year !== '') {
        $stmtDebug = $db->prepare("SELECT * FROM grades WHERE student_id = ? AND semester = ? AND academic_year = ?");
        $stmtDebug->execute([$student_id, $semester, $academic_year]);
        $debugSelect = $stmtDebug->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmtDebug = $db->prepare("SELECT * FROM grades WHERE student_id = ? AND semester = ? AND (academic_year IS NULL OR academic_year = '')");
        $stmtDebug->execute([$student_id, $semester]);
        $debugSelect = $stmtDebug->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($academic_year && $academic_year !== 'null' && $academic_year !== '') {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND semester = ? AND academic_year = ?");
        $stmt->execute([$student_id, $semester, $academic_year]);
    } else {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND semester = ? AND (academic_year IS NULL OR academic_year = '')");
        $stmt->execute([$student_id, $semester]);
    }
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Semua nilai siswa berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dihapus. Data tidak ditemukan.', 'debug' => [
            'params' => compact('student_id','semester','academic_year'),
            'select_result' => $debugSelect
        ]]);
    }
    exit;
}

if ($subject_id) {
    $db = getDbConnection();
    if ($academic_year && $academic_year !== 'null' && $academic_year !== '') {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND subject_id = ? AND semester = ? AND academic_year = ?");
        $stmt->execute([$student_id, $subject_id, $semester, $academic_year]);
    } else {
        $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND subject_id = ? AND semester = ? AND (academic_year IS NULL OR academic_year = '')");
        $stmt->execute([$student_id, $subject_id, $semester]);
    }
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Nilai berhasil dihapus!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dihapus. Data tidak ditemukan.']);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Parameter subject_id tidak lengkap.']);
}