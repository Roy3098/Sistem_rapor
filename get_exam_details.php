<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

requireLogin();

// Initialize database if needed
try {
    initializeDatabase();
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database not initialized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_id'])) {
    $exam_id = $_POST['exam_id'];
    
    try {
        $db = getDbConnection();
        
        // Get exam details
        $stmt = $db->prepare("
            SELECT e.*, s.name as subject_name, c.name as class_name
            FROM exams e 
            JOIN subjects s ON e.subject_id = s.id 
            JOIN classes c ON e.class_id = c.id
            WHERE e.id = ?
        ");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) {
            echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
            exit();
        }
        
        $html = '';
        
        if ($exam['type'] === 'file') {
            $html = "
                <div class='space-y-3'>
                    <div><strong>Mata Pelajaran:</strong> " . htmlspecialchars($exam['subject_name']) . "</div>
                    <div><strong>Kelas:</strong> " . htmlspecialchars($exam['class_name']) . "</div>
                    <div><strong>Nama File:</strong> " . htmlspecialchars($exam['file_name']) . "</div>
                    <div><strong>Tipe File:</strong> " . htmlspecialchars($exam['file_type']) . "</div>
                    <div><strong>Tanggal Upload:</strong> " . date('d/m/Y H:i', strtotime($exam['created_at'])) . "</div>
                </div>
            ";
        } else {
            // Get questions for this exam
            $stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
            $stmt->execute([$exam_id]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = "
                <div class='space-y-3'>
                    <div><strong>Mata Pelajaran:</strong> " . htmlspecialchars($exam['subject_name']) . "</div>
                    <div><strong>Kelas:</strong> " . htmlspecialchars($exam['class_name']) . "</div>
                    <div><strong>Tanggal Dibuat:</strong> " . date('d/m/Y H:i', strtotime($exam['created_at'])) . "</div>
            ";
            
            if (!empty($questions)) {
                $html .= "<div class='mt-4'><strong>Soal:</strong></div>";
                foreach ($questions as $index => $question) {
                    $questionNum = $index + 1;
                    $html .= "<div class='bg-gray-100 p-3 rounded-lg mt-2'>";
                    $html .= "<div class='font-semibold'>Soal {$questionNum}:</div>";
                    $html .= "<div class='mt-1'>" . nl2br(htmlspecialchars($question['question_text'])) . "</div>";
                    
                    if ($question['question_type'] === 'multiple_choice') {
                        $html .= "<div class='mt-2'><strong>Pilihan:</strong></div>";
                        $html .= "<div>A. " . htmlspecialchars($question['option_a']) . "</div>";
                        $html .= "<div>B. " . htmlspecialchars($question['option_b']) . "</div>";
                        $html .= "<div>C. " . htmlspecialchars($question['option_c']) . "</div>";
                        $html .= "<div>D. " . htmlspecialchars($question['option_d']) . "</div>";
                        $html .= "<div class='mt-1 text-green-600'><strong>Jawaban: " . htmlspecialchars($question['correct_answer']) . "</strong></div>";
                    }
                    $html .= "</div>";
                }
            } else {
                $html .= "<div class='mt-4 text-gray-500'>Tidak ada soal yang ditemukan.</div>";
            }
            
            $html .= "</div>";
        }
        
        echo json_encode(['success' => true, 'html' => $html]);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>