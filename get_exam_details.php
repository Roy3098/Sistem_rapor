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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle delete question
    if (isset($_POST['action']) && $_POST['action'] === 'delete_question' && isset($_POST['question_id'])) {
        require_once 'includes/functions.php';
        $result = deleteQuestion($_POST['question_id']);
        echo json_encode($result);
        exit();
    }
    
    // Handle get question for edit
    if (isset($_POST['action']) && $_POST['action'] === 'get_question' && isset($_POST['question_id'])) {
        require_once 'includes/functions.php';
        $question = getQuestionById($_POST['question_id']);
        if ($question) {
            echo json_encode(['success' => true, 'question' => $question]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Soal tidak ditemukan']);
        }
        exit();
    }
    
    // Handle update question
    if (isset($_POST['action']) && $_POST['action'] === 'update_question' && isset($_POST['question_id'])) {
        require_once 'includes/functions.php';
        $question_id = $_POST['question_id'];
        $question_text = $_POST['question_text'];
        
        if (isset($_POST['option_a'])) {
            // Multiple choice question
            $option_a = $_POST['option_a'];
            $option_b = $_POST['option_b'];
            $option_c = $_POST['option_c'];
            $option_d = $_POST['option_d'];
            $correct_answer = $_POST['correct_answer'];
            $result = updateQuestion($question_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer);
        } else {
            // Essay question
            $result = updateQuestion($question_id, $question_text);
        }
        
        echo json_encode($result);
        exit();
    }
    
    // Handle get exam details
    if (isset($_POST['exam_id'])) {
    $exam_id = $_POST['exam_id'];
    
    try {
        $db = getDbConnection();
        
        // Get exam details
        $stmt = $db->prepare("
            SELECT e.*, s.name as subject_name, c.name as class_name, u.full_name as creator_name, u.username as creator_username
            FROM exams e 
            JOIN subjects s ON e.subject_id = s.id 
            JOIN classes c ON e.class_id = c.id
            LEFT JOIN users u ON e.created_by = u.id
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
            $creator_info = '';
            if ($exam['creator_name']) {
                $creator_info = "<div><strong>Dibuat oleh:</strong> " . htmlspecialchars($exam['creator_name']) . " (" . htmlspecialchars($exam['creator_username']) . ")</div>";
            } else {
                $creator_info = "<div><strong>Dibuat oleh:</strong> <em class='text-gray-500'>Data tidak tersedia</em></div>";
            }
            
            $html = "
                <div class='space-y-3'>
                    <div><strong>Mata Pelajaran:</strong> " . htmlspecialchars($exam['subject_name']) . "</div>
                    <div><strong>Kelas:</strong> " . htmlspecialchars($exam['class_name']) . "</div>
                    <div><strong>Nama File:</strong> " . htmlspecialchars($exam['file_name']) . "</div>
                    <div><strong>Tipe File:</strong> " . htmlspecialchars($exam['file_type']) . "</div>
                    " . $creator_info . "
                    <div><strong>Tanggal Upload:</strong> " . date('d/m/Y H:i', strtotime($exam['created_at'])) . "</div>
                </div>
            ";
        } else {
            // Get all exams for the same class and subject as this exam
            $stmt = $db->prepare("
                SELECT q.*
                FROM questions q
                JOIN exams e ON q.exam_id = e.id
                WHERE e.class_id = ? AND e.subject_id = ?
                ORDER BY CASE q.question_type WHEN 'multiple_choice' THEN 1 WHEN 'essay' THEN 2 ELSE 3 END, q.id
            ");
            $stmt->execute([$exam['class_id'], $exam['subject_id']]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $creator_info = '';
            if ($exam['creator_name']) {
                $creator_info = "<div><strong>Dibuat oleh:</strong> " . htmlspecialchars($exam['creator_name']) . " (" . htmlspecialchars($exam['creator_username']) . ")</div>";
            } else {
                $creator_info = "<div><strong>Dibuat oleh:</strong> <em class='text-gray-500'>Data tidak tersedia</em></div>";
            }
            
            $html = "
                <div class='space-y-3'>
                    <div><strong>Mata Pelajaran:</strong> " . htmlspecialchars($exam['subject_name']) . "</div>
                    <div><strong>Kelas:</strong> " . htmlspecialchars($exam['class_name']) . "</div>
                    " . $creator_info . "
                    <div><strong>Tanggal Dibuat:</strong> " . date('d/m/Y H:i', strtotime($exam['created_at'])) . "</div>
            ";
            
            if (!empty($questions)) {
                // Separate essay and multiple choice questions
                $essay_questions = array_filter($questions, function($q) { return $q['question_type'] === 'essay'; });
                $mc_questions = array_filter($questions, function($q) { return $q['question_type'] === 'multiple_choice'; });
                
                // Display multiple choice questions first
                if (!empty($mc_questions)) {
                    $html .= "<div class='mt-4'><strong>Soal Pilihan Ganda:</strong></div>";
                    foreach ($mc_questions as $index => $question) {
                        $questionNum = $index + 1;
                        $html .= "<div class='bg-gray-100 p-3 rounded-lg mt-2'>";
                        $html .= "<div class='font-semibold'>Soal Pilihan Ganda {$questionNum}:</div>";
                        $html .= "<div class='mt-1'>" . nl2br(htmlspecialchars($question['question_text'])) . "</div>";
                        $html .= "<div class='mt-2'><strong>Pilihan:</strong></div>";
                        $html .= "<div>A. " . htmlspecialchars($question['option_a']) . "</div>";
                        $html .= "<div>B. " . htmlspecialchars($question['option_b']) . "</div>";
                        $html .= "<div>C. " . htmlspecialchars($question['option_c']) . "</div>";
                        $html .= "<div>D. " . htmlspecialchars($question['option_d']) . "</div>";
                        $html .= "<div class='mt-1 text-green-600'><strong>Jawaban: " . htmlspecialchars($question['correct_answer']) . "</strong></div>";
                        $html .= "<div class='mt-2'>";
                        $html .= "<button class='edit-btn bg-blue-500 text-white px-3 py-1 rounded text-sm mr-2' data-id='" . $question['id'] . "' data-type='multiple_choice'>Edit</button>";
                        $html .= "<button class='delete-btn bg-red-500 text-white px-3 py-1 rounded text-sm' data-id='" . $question['id'] . "' data-type='multiple_choice'>Hapus</button>";
                        $html .= "</div>";
                        $html .= "</div>";
                    }
                }
                
                // Display essay questions after multiple choice questions
                if (!empty($essay_questions)) {
                    $html .= "<div class='mt-4'><strong>Soal Essay:</strong></div>";
                    foreach ($essay_questions as $index => $question) {
                        $questionNum = $index + 1;
                        $html .= "<div class='bg-gray-100 p-3 rounded-lg mt-2'>";
                        $html .= "<div class='font-semibold'>Soal Essay {$questionNum}:</div>";
                        $html .= "<div class='mt-1'>" . nl2br(htmlspecialchars($question['question_text'])) . "</div>";
                        $html .= "<div class='mt-2'>";
                        $html .= "<button class='edit-btn bg-blue-500 text-white px-3 py-1 rounded text-sm mr-2' data-id='" . $question['id'] . "' data-type='essay'>Edit</button>";
                        $html .= "<button class='delete-btn bg-red-500 text-white px-3 py-1 rounded text-sm' data-id='" . $question['id'] . "' data-type='essay'>Hapus</button>";
                        $html .= "</div>";
                        $html .= "</div>";
                    }
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
}
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>