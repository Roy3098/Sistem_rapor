<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

// Initialize database if needed
try {
    initializeDatabase();
} catch(Exception $e) {
    header('Location: install.php');
    exit();
}

if (!isset($_GET['action'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_GET['action'] === 'export_excel') {
    $class_id = !empty($_GET['class_id']) ? $_GET['class_id'] : null;
    $semester = !empty($_GET['semester']) ? $_GET['semester'] : null;
    
    try {
        $grades = getGrades($class_id, $semester);
    } catch(Exception $e) {
        echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }
    
    if (empty($grades)) {
        echo "<script>alert('Tidak ada data untuk diekspor!'); window.history.back();</script>";
        exit();
    }
    
    // Group grades by student
    $groupedGrades = [];
    $allSubjects = [];
    
    foreach ($grades as $grade) {
        $key = $grade['student_name'] . '_' . $grade['class_name'] . '_' . $grade['semester'];
        if (!isset($groupedGrades[$key])) {
            $groupedGrades[$key] = [
                'student_name' => $grade['student_name'],
                'student_id' => $grade['student_id'],
                'class_name' => $grade['class_name'],
                'semester' => $grade['semester'],
                'subjects' => []
            ];
        }
        $groupedGrades[$key]['subjects'][$grade['subject_name']] = $grade['grade'];
        $allSubjects[$grade['subject_name']] = true;
    }
    
    $allSubjects = array_keys($allSubjects);
    sort($allSubjects);
    
    // Create CSV content for Excel compatibility
    $csvContent = "data:text/csv;charset=utf-8,";
    
    // Add BOM for proper UTF-8 encoding in Excel
    $csvContent = "\xEF\xBB\xBF";
    
    // Create headers
    $headers = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Semester'];
    $headers = array_merge($headers, $allSubjects);
    $headers[] = 'Total';
    $headers[] = 'Rata-rata';
    
    // Add headers to CSV
    $csvContent .= implode(',', array_map(function($header) {
        return '"' . str_replace('"', '""', $header) . '"';
    }, $headers)) . "\n";
    
    // Add data rows
    $no = 1;
    foreach ($groupedGrades as $student) {
        $row = [];
        $row[] = $no;
        $row[] = $student['student_id'] ?: 'Belum diisi';
        $row[] = $student['student_name'];
        $row[] = $student['class_name'];
        $row[] = 'Semester ' . $student['semester'];
        
        $total = 0;
        $count = 0;
        
        foreach ($allSubjects as $subject) {
            $grade = isset($student['subjects'][$subject]) ? $student['subjects'][$subject] : '';
            $row[] = $grade;
            if ($grade !== '') {
                $total += $grade;
                $count++;
            }
        }
        
        $row[] = $total;
        $row[] = $count > 0 ? round($total / $count, 1) : 0;
        
        // Escape CSV values
        $csvRow = array_map(function($value) {
            return '"' . str_replace('"', '""', $value) . '"';
        }, $row);
        
        $csvContent .= implode(',', $csvRow) . "\n";
        $no++;
    }
    
    // Generate filename
    $filename = 'Nilai_Siswa_' . date('Y-m-d_H-i-s');
    if ($class_id) {
        $classInfo = getClasses();
        foreach ($classInfo as $class) {
            if ($class['id'] == $class_id) {
                $filename .= '_Kelas_' . $class['name'];
                break;
            }
        }
    }
    if ($semester) {
        $filename .= '_Semester_' . $semester;
    }
    $filename .= '.csv';
    
    // Set headers for CSV download (Excel will open it)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output CSV content
    echo $csvContent;
    exit();
}
?>