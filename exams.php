<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';
require_once 'config/database.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

requireLogin();
$currentUser = getCurrentUser();

// Initialize database if needed
try {
    initializeDatabase();
} catch(Exception $e) {
    header('Location: install.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload_file':
                $subject_id = $_POST['subject_id'];
                $class_id = $_POST['class_id'];
                
                if (empty($subject_id) || empty($class_id)) {
                    $error = 'Harap lengkapi semua field!';
                } elseif (!isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Harap pilih file untuk diunggah!';
                } else {
                    $upload_result = uploadFile($_FILES['exam_file']);
                    if ($upload_result['success']) {
                        $result = createExam($subject_id, $class_id, 'file', $upload_result);
                        if ($result['success']) {
                            $message = $result['message'];
                        } else {
                            $error = $result['message'];
                        }
                    } else {
                        $error = $upload_result['message'];
                    }
                }
                break;
                
            case 'create_questions':
                $subject_id = $_POST['subject_id'];
                $class_id = $_POST['class_id'];
                $question_type = $_POST['question_type'];
                $questions = $_POST['questions'] ?? [];
                
                if (empty($subject_id) || empty($class_id) || empty($question_type) || empty($questions)) {
                    $error = 'Harap lengkapi semua field!';
                } else {
                    // Validate questions
                    $valid_questions = [];
                    foreach ($questions as $question) {
                        $question_text = trim($question['text'] ?? '');
                        if (empty($question_text)) {
                            continue; // Skip empty questions
                        }
                        
                        $question_data = ['text' => $question_text];
                        
                        if ($question_type === 'multiple_choice') {
                            $option_a = trim($question['option_a'] ?? '');
                            $option_b = trim($question['option_b'] ?? '');
                            $option_c = trim($question['option_c'] ?? '');
                            $option_d = trim($question['option_d'] ?? '');
                            $correct_answer = strtoupper(trim($question['correct_answer'] ?? ''));
                            
                            if (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answer)) {
                                $error = 'Harap lengkapi semua pilihan jawaban untuk setiap soal!';
                                break 2;
                            }
                            
                            if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                                $error = 'Jawaban benar harus A, B, C, atau D!';
                                break 2;
                            }
                            
                            $question_data['option_a'] = $option_a;
                            $question_data['option_b'] = $option_b;
                            $question_data['option_c'] = $option_c;
                            $question_data['option_d'] = $option_d;
                            $question_data['correct_answer'] = $correct_answer;
                        }
                        
                        $valid_questions[] = $question_data;
                    }
                    
                    if (empty($valid_questions)) {
                        $error = 'Minimal harus ada 1 soal yang valid!';
                    } else {
                        $result = createMultipleExams($subject_id, $class_id, 'questions', null, $question_type, $valid_questions);
                        if ($result['success']) {
                            $message = $result['message'];
                        } else {
                            $error = $result['message'];
                        }
                    }
                }
                break;
                
            case 'delete_exam':
                $id = $_POST['id'];
                $result = deleteExam($id);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Handle download requests
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $exam = getExamById($_GET['id']);
    if ($exam) {
        if ($exam['type'] === 'file') {
            // Download uploaded file
            $file_path = $exam['file_path'];
            if (file_exists($file_path)) {
                header('Content-Type: ' . $exam['file_type']);
                header('Content-Disposition: attachment; filename="' . $exam['file_name'] . '"');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit();
            } else {
                $error = 'File tidak ditemukan!';
            }
        } else {
            // Check if TXT format is requested
            if (isset($_GET['format']) && $_GET['format'] === 'txt') {
                // Generate TXT document for questions
                generateTxtDocument($exam);
                exit();
            } else {
                // Generate Word document for questions
                generateWordDocument($exam);
                exit();
            }
        }
    } else {
        $error = 'Soal tidak ditemukan!';
    }
}

// Get filter parameters
$filter_class = isset($_GET['filter_class']) && $_GET['filter_class'] !== '' ? $_GET['filter_class'] : null;
$filter_type = isset($_GET['filter_type']) && $_GET['filter_type'] !== '' ? $_GET['filter_type'] : null;

$classes = getClasses();
$subjects = getSubjects();
$consolidatedExams = getConsolidatedExams($filter_class, $filter_type);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal Ujian - Baiturrahman Web</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            animation: fadeIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-md">
        
        <!-- Top Header -->
        <div class="bg-white rounded-2xl shadow-lg mb-4 fade-in">
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 mr-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">Kelola Soal Ujian</h1>
                        <p class="text-xs text-gray-600">Upload file dan buat soal ujian</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-20">
            <?php if ($message): ?>
                <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg text-sm">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <button onclick="showUploadModal()" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all text-sm">
                    📁 Upload File
                </button>
                <button onclick="showQuestionModal()" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all text-sm">
                    ✏️ Buat Soal
                </button>
            </div>
            
            <!-- Filter Section -->
            <div class="mb-6">
                <form method="GET" class="flex space-x-2 mb-4">
                    <select name="filter_class" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($filter_class == $class['id']) ? 'selected' : ''; ?>>
                                Kelas <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="filter_type" onchange="this.form.submit()" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Tipe</option>
                        <option value="file" <?php echo ($filter_type == 'file') ? 'selected' : ''; ?>>File Ujian</option>
                        <option value="questions" <?php echo ($filter_type == 'questions') ? 'selected' : ''; ?>>Soal Ujian</option>
                    </select>
                    <?php if ($filter_class || $filter_type): ?>
                        <a href="exams.php" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-all">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Exams List -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Soal & File Ujian</h3>
                    <div class="text-sm text-gray-600">
                        <?php 
                        $total_results = count($consolidatedExams);
                        echo "Menampilkan {$total_results} hasil";
                        if ($filter_class || $filter_type) {
                            echo " (terfilter)";
                        }
                        ?>
                    </div>
                </div>
                
                <?php if ($filter_class || $filter_type): ?>
                    <div class="mb-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                        <div class="flex items-center text-sm">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-green-700 font-medium">Filter aktif:</span>
                            </div>
                            <div class="flex items-center ml-3">
                                <?php if ($filter_class): ?>
                                    <?php 
                                    $selected_class = array_filter($classes, function($c) use ($filter_class) { return $c['id'] == $filter_class; });
                                    $selected_class = reset($selected_class);
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                        🏫 Kelas <?php echo htmlspecialchars($selected_class['name']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($filter_type): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-2">
                                        <?php echo $filter_type === 'file' ? '📁 File Ujian' : '✏️ Soal Ujian'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-3">
                    <?php
                    $currentClass = '';
                    foreach ($consolidatedExams as $consolidated):
                        if ($currentClass !== $consolidated['class_name']):
                            if ($currentClass !== '') echo '</div>';
                            $currentClass = $consolidated['class_name'];
                    ?>
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-lg font-semibold">
                            Kelas <?php echo htmlspecialchars($consolidated['class_name']); ?>
                        </div>
                        <div class="ml-4 space-y-2">
                    <?php endif; ?>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600">Mata Pelajaran: <?php echo htmlspecialchars($consolidated['subject_name']); ?></p>
                                
                                <?php if (isset($consolidated['exams']['file'])): ?>
                                    <p class="text-sm text-gray-600">File: <?php echo htmlspecialchars($consolidated['exams']['file']['file_name']); ?></p>
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mt-1">📁 File Ujian</span>
                                <?php endif; ?>
                                
                                <?php if (isset($consolidated['exams']['questions'])): ?>
                                    <?php
                                    $questionTypes = $consolidated['exams']['questions']['question_types'];
                                    $totalQuestions = $consolidated['exams']['questions']['total_questions'];
                                    ?>
                                    <div class="mt-2">
                                        <?php if (isset($questionTypes['essay']) && $questionTypes['essay'] > 0): ?>
                                            <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full mt-1 mr-1">
                                                ✏️ Soal Essay (<?php echo $questionTypes['essay']; ?> soal)
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($questionTypes['multiple_choice']) && $questionTypes['multiple_choice'] > 0): ?>
                                            <span class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full mt-1">
                                                ☑️ Pilihan Ganda (<?php echo $questionTypes['multiple_choice']; ?> soal)
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($totalQuestions > 0): ?>
                                            <p class="text-sm text-gray-600 mt-2">Total: <?php echo $totalQuestions; ?> soal</p>
                                        <?php endif; ?>
                                        
                                        <!-- Show question details when filter is "questions" -->
                                        <?php if ($filter_type === 'questions'): ?>
                                            <?php 
                                            // Get all questions for this exam
                                            $db = getDbConnection();
                                            $stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_type, id");
                                            $stmt->execute([$consolidated['exams']['questions']['id']]);
                                            $all_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            <?php if (!empty($all_questions)): ?>
                                                <div class="mt-3 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <p class="text-sm font-semibold text-gray-700">📋 Daftar Soal Lengkap</p>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                                <?php echo count($all_questions); ?> soal
                                                            </span>
                                                            <button onclick="toggleQuestions(<?php echo $consolidated['exams']['questions']['id']; ?>)" 
                                                                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded-full transition-colors">
                                                                <span id="toggle-text-<?php echo $consolidated['exams']['questions']['id']; ?>">Sembunyikan</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div id="questions-container-<?php echo $consolidated['exams']['questions']['id']; ?>" class="space-y-4 max-h-96 overflow-y-auto">
                                                        <?php 
                                                        $essay_num = 1;
                                                        $mc_num = 1;
                                                        foreach ($all_questions as $question): 
                                                        ?>
                                                            <div class="p-3 <?php echo $question['question_type'] === 'essay' ? 'bg-green-50 border-l-4 border-green-400' : 'bg-purple-50 border-l-4 border-purple-400'; ?> rounded-r-lg">
                                                                <div class="flex items-start space-x-2">
                                                                    <span class="flex-shrink-0 w-6 h-6 <?php echo $question['question_type'] === 'essay' ? 'bg-green-500' : 'bg-purple-500'; ?> text-white text-xs font-bold rounded-full flex items-center justify-center">
                                                                        <?php echo $question['question_type'] === 'essay' ? $essay_num++ : $mc_num++; ?>
                                                                    </span>
                                                                    <div class="flex-1">
                                                                        <div class="flex items-center space-x-2 mb-2">
                                                                            <span class="text-xs font-medium <?php echo $question['question_type'] === 'essay' ? 'text-green-700' : 'text-purple-700'; ?>">
                                                                                <?php echo $question['question_type'] === 'essay' ? '✏️ SOAL ESSAY' : '☑️ PILIHAN GANDA'; ?>
                                                                            </span>
                                                                        </div>
                                                                        
                                                                        <p class="text-sm text-gray-800 font-medium mb-2">
                                                                            <?php echo htmlspecialchars($question['question_text']); ?>
                                                                        </p>
                                                                        
                                                                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                                            <div class="ml-4 space-y-1">
                                                                                <div class="flex items-center space-x-2">
                                                                                    <span class="w-6 h-6 <?php echo $question['correct_answer'] === 'A' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'; ?> text-xs font-bold rounded-full flex items-center justify-center">A</span>
                                                                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($question['option_a']); ?></span>
                                                                                </div>
                                                                                <div class="flex items-center space-x-2">
                                                                                    <span class="w-6 h-6 <?php echo $question['correct_answer'] === 'B' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'; ?> text-xs font-bold rounded-full flex items-center justify-center">B</span>
                                                                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($question['option_b']); ?></span>
                                                                                </div>
                                                                                <div class="flex items-center space-x-2">
                                                                                    <span class="w-6 h-6 <?php echo $question['correct_answer'] === 'C' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'; ?> text-xs font-bold rounded-full flex items-center justify-center">C</span>
                                                                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($question['option_c']); ?></span>
                                                                                </div>
                                                                                <div class="flex items-center space-x-2">
                                                                                    <span class="w-6 h-6 <?php echo $question['correct_answer'] === 'D' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'; ?> text-xs font-bold rounded-full flex items-center justify-center">D</span>
                                                                                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($question['option_d']); ?></span>
                                                                                </div>
                                                                                <div class="mt-2 p-2 bg-white rounded border border-green-200">
                                                                                    <span class="text-xs font-medium text-green-700">✓ Jawaban Benar: <?php echo $question['correct_answer']; ?></span>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2 mt-3">
                            <?php if (isset($consolidated['exams']['file'])): ?>
                                <a href="?action=download&id=<?php echo $consolidated['exams']['file']['id']; ?>" class="text-purple-500 hover:text-purple-700 text-sm font-medium">
                                    ⬇️ Unduh File
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_exam">
                                    <input type="hidden" name="id" value="<?php echo $consolidated['exams']['file']['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium" onclick="return confirm('Apakah Anda yakin ingin menghapus file soal ini?')">
                                        🗑️ Hapus File
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if (isset($consolidated['exams']['questions'])): ?>
                                <a href="?action=download&id=<?php echo $consolidated['exams']['questions']['id']; ?>&format=txt" class="text-green-500 hover:text-green-700 text-sm font-medium">
                                    📝 Unduh TXT
                                </a>
                                <button onclick="showQuestionDetails(<?php echo $consolidated['exams']['questions']['id']; ?>)" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                    👁️ Lihat Soal
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_exam">
                                    <input type="hidden" name="id" value="<?php echo $consolidated['exams']['questions']['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium" onclick="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
                                        🗑️ Hapus Soal
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php endforeach; ?>
                    <?php if (!empty($consolidatedExams)) echo '</div>'; ?>
                    
                    <?php if (empty($consolidatedExams)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php if ($filter_class || $filter_type): ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    <?php else: ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <?php if ($filter_class || $filter_type): ?>
                                <p class="text-gray-500 mb-3">Tidak ada soal yang sesuai dengan filter yang dipilih</p>
                                <a href="exams.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                    Reset filter untuk melihat semua soal
                                </a>
                            <?php else: ?>
                                <p class="text-gray-500 mb-3">Belum ada soal atau file ujian yang tersimpan</p>
                                <p class="text-sm text-gray-400">Mulai dengan mengupload file ujian atau membuat soal baru</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Upload File Ujian</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_file">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                        <select name="subject_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="">Pilih Mata Pelajaran</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                        <select name="class_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Ujian</label>
                        <input type="file" name="exam_file" accept=".pdf,.doc,.docx" required class="w-full text-gray-700 bg-gray-50 border border-gray-300 rounded-xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                        <p class="text-xs text-gray-500 mt-1">Format yang didukung: PDF, DOC, DOCX</p>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-600 transition-all">
                        Upload File
                    </button>
                    <button type="button" onclick="hideUploadModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Question Modal -->
    <div id="questionModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Buat Soal Ujian</h2>
            <form method="POST" id="questionForm">
                <input type="hidden" name="action" value="create_questions">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                        <select name="subject_id" id="subjectSelect" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Mata Pelajaran</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                        <select name="class_id" id="classSelect" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Soal</label>
                        <select name="question_type" id="questionTypeSelect" onchange="toggleQuestionFields()" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Tipe Soal</option>
                            <option value="essay">Essay</option>
                            <option value="multiple_choice">Pilihan Ganda</option>
                        </select>
                    </div>
                    
                    <!-- Questions Container -->
                    <div id="questionsContainer">
                        <!-- Question 1 -->
                        <div class="question-item mb-4 pb-4 border-b border-gray-200" data-question-index="1">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="font-medium text-gray-800">Soal #1</h3>
                                <button type="button" onclick="removeQuestion(1)" class="text-red-500 hover:text-red-700 text-sm" id="removeBtn1" style="display: none;">Hapus</button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                                <textarea name="questions[1][text]" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-24" placeholder="Tuliskan soal di sini..."></textarea>
                            </div>
                            
                            <!-- Multiple Choice Options -->
                            <div id="mcOptions1" style="display: none;" class="mt-3">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A</label>
                                        <input type="text" name="questions[1][option_a]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan A">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B</label>
                                        <input type="text" name="questions[1][option_b]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan B">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C</label>
                                        <input type="text" name="questions[1][option_c]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan C">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D</label>
                                        <input type="text" name="questions[1][option_d]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan D">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                                        <select name="questions[1][correct_answer]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                                            <option value="">Pilih Jawaban Benar</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Question Button -->
                    <div class="text-center">
                        <button type="button" onclick="addQuestion()" id="addQuestionBtn" class="text-green-600 hover:text-green-800 font-medium text-sm">
                            + Tambah Soal Lagi
                        </button>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-green-500 text-white py-3 rounded-xl font-semibold hover:bg-green-600 transition-all">
                        Buat Soal
                    </button>
                    <button type="button" onclick="hideQuestionModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Question Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content" data-exam-id="">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Detail Soal</h2>
                <button onclick="hideDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="questionDetailsContent" class="space-y-3 text-gray-700 text-sm">
                <!-- Content will be loaded here -->
            </div>
            <div class="mt-6 text-center">
                <button onclick="hideDetailsModal()" class="bg-blue-500 text-white py-2 px-4 rounded-xl font-semibold hover:bg-blue-600 transition-all">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40">
        <div class="container mx-auto px-4 max-w-md">
            <div class="flex justify-around py-2">
                <a href="dashboard.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>
                <a href="grades.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 4h.01M9 12h.01M9 16h.01M13 12h6m-3-3v6"></path>
                    </svg>
                    <span class="text-xs font-medium">Nilai</span>
                </a>
                <a href="tahfidz.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="text-xs font-medium">Tahfidz</span>
                </a>
                <a href="manage-data.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-xs font-medium">Kelola Data</span>
                </a>
                <a href="exams.php" class="flex flex-col items-center py-2 px-3 text-blue-600 bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span class="text-xs font-medium">Soal</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        let questionCount = 1;
        const maxQuestions = 40;

        function showUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }

        function hideUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function showQuestionModal() {
            // Reset form and question count
            document.getElementById('questionForm').reset();
            questionCount = 1;
            
            // Reset questions container to initial state
            const container = document.getElementById('questionsContainer');
            container.innerHTML = `
                <div class="question-item mb-4 pb-4 border-b border-gray-200" data-question-index="1">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-medium text-gray-800">Soal #1</h3>
                        <button type="button" onclick="removeQuestion(1)" class="text-red-500 hover:text-red-700 text-sm" id="removeBtn1" style="display: none;">Hapus</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                        <textarea name="questions[1][text]" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-24" placeholder="Tuliskan soal di sini..."></textarea>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="mcOptions1" style="display: none;" class="mt-3">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A</label>
                                <input type="text" name="questions[1][option_a]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan A">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B</label>
                                <input type="text" name="questions[1][option_b]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan B">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C</label>
                                <input type="text" name="questions[1][option_c]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan C">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D</label>
                                <input type="text" name="questions[1][option_d]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan D">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                                <select name="questions[1][correct_answer]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                                    <option value="">Pilih Jawaban Benar</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Hide remove button for the first question
            document.getElementById('removeBtn1').style.display = 'none';
            
            document.getElementById('questionModal').style.display = 'flex';
        }

        function hideQuestionModal() {
            document.getElementById('questionModal').style.display = 'none';
        }

        function toggleQuestionFields() {
            const questionType = document.getElementById('questionTypeSelect').value;
            
            // Toggle MC options for all questions
            for (let i = 1; i <= questionCount; i++) {
                const mcOptions = document.getElementById(`mcOptions${i}`);
                if (mcOptions) {
                    if (questionType === 'multiple_choice') {
                        mcOptions.style.display = 'block';
                        // Make MC fields required
                        mcOptions.querySelectorAll('input, select').forEach(field => {
                            field.required = true;
                        });
                    } else {
                        mcOptions.style.display = 'none';
                        // Remove required from MC fields
                        mcOptions.querySelectorAll('input, select').forEach(field => {
                            field.required = false;
                        });
                    }
                }
            }
        }

        function addQuestion() {
            if (questionCount >= maxQuestions) {
                alert(`Maksimal ${maxQuestions} soal!`);
                return;
            }
            
            questionCount++;
            
            const container = document.getElementById('questionsContainer');
            const questionItem = document.createElement('div');
            questionItem.className = 'question-item mb-4 pb-4 border-b border-gray-200';
            questionItem.setAttribute('data-question-index', questionCount);
            
            questionItem.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-medium text-gray-800">Soal #${questionCount}</h3>
                    <button type="button" onclick="removeQuestion(${questionCount})" class="text-red-500 hover:text-red-700 text-sm" id="removeBtn${questionCount}">Hapus</button>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                    <textarea name="questions[${questionCount}][text]" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-24" placeholder="Tuliskan soal di sini..."></textarea>
                </div>
                
                <!-- Multiple Choice Options -->
                <div id="mcOptions${questionCount}" style="display: none;" class="mt-3">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A</label>
                            <input type="text" name="questions[${questionCount}][option_a]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan A">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B</label>
                            <input type="text" name="questions[${questionCount}][option_b]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan B">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C</label>
                            <input type="text" name="questions[${questionCount}][option_c]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan C">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D</label>
                            <input type="text" name="questions[${questionCount}][option_d]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan D">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                            <select name="questions[${questionCount}][correct_answer]" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                                <option value="">Pilih Jawaban Benar</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(questionItem);
            
            // Update MC options visibility based on current question type
            toggleQuestionFields();
        }

        function removeQuestion(index) {
            if (questionCount <= 1) {
                alert('Minimal harus ada 1 soal!');
                return;
            }
            
            const questionItem = document.querySelector(`.question-item[data-question-index="${index}"]`);
            if (questionItem) {
                questionItem.remove();
                questionCount--;
                
                // Renumber remaining questions
                renumberQuestions();
            }
        }

        function renumberQuestions() {
            const questionItems = document.querySelectorAll('.question-item');
            questionItems.forEach((item, index) => {
                const newIndex = index + 1;
                item.setAttribute('data-question-index', newIndex);
                
                // Update question number display
                const heading = item.querySelector('h3');
                if (heading) {
                    heading.textContent = `Soal #${newIndex}`;
                }
                
                // Update remove button ID and onclick handler
                const removeBtn = item.querySelector('button');
                if (removeBtn) {
                    removeBtn.id = `removeBtn${newIndex}`;
                    removeBtn.setAttribute('onclick', `removeQuestion(${newIndex})`);
                    
                    // Hide remove button for the first question
                    if (newIndex === 1) {
                        removeBtn.style.display = 'none';
                    } else {
                        removeBtn.style.display = 'inline';
                    }
                }
                
                // Update textarea name
                const textarea = item.querySelector('textarea');
                if (textarea) {
                    textarea.name = `questions[${newIndex}][text]`;
                }
                
                // Update MC options container ID
                const mcOptions = item.querySelector('[id^="mcOptions"]');
                if (mcOptions) {
                    mcOptions.id = `mcOptions${newIndex}`;
                    
                    // Update MC input names
                    const inputs = mcOptions.querySelectorAll('input, select');
                    inputs.forEach(input => {
                        if (input.name) {
                            const fieldName = input.name.match(/\[([^\]]+)\]$/)[1];
                            input.name = `questions[${newIndex}][${fieldName}]`;
                        }
                    });
                }
            });
        }

        function showQuestionDetails(examId) {
            fetch('get_exam_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'exam_id=' + examId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const content = document.getElementById('questionDetailsContent');
                    content.innerHTML = data.html;
                    document.getElementById('detailsModal').style.display = 'flex';
                    // Set the exam ID in the modal content
                    document.querySelector('.modal-content').dataset.examId = examId;
                } else {
                    alert('Error loading exam details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading exam details');
            });
        }

        function hideDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Handle delete button click
        function handleDeleteQuestion(questionId) {
            if (confirm('Apakah Anda yakin ingin menghapus soal ini?')) {
                fetch('get_exam_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_question&question_id=' + questionId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Soal berhasil dihapus!');
                        // Refresh the modal content
                        const examId = document.querySelector('.modal-content').dataset.examId;
                        if (examId) {
                            showQuestionDetails(examId);
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting question');
                });
            }
        }
        
        // Add event listeners to edit and delete buttons when modal is shown
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-btn')) {
                const questionId = e.target.dataset.id;
                const questionType = e.target.dataset.type;
                
                // Get question details for editing
                fetch('get_exam_details.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_question&question_id=' + questionId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Create edit form
                        let editForm = '<div class="edit-form">';
                        editForm += '<h3 class="text-lg font-bold mb-4">Edit Soal</h3>';
                        editForm += '<form id="editQuestionForm">';
                        editForm += '<input type="hidden" name="question_id" value="' + data.question.id + '">';
                        editForm += '<div class="mb-4">';
                        editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Soal:</label>';
                        editForm += '<textarea name="question_text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4">' + data.question.question_text + '</textarea>';
                        editForm += '</div>';
                        
                        if (questionType === 'multiple_choice') {
                            editForm += '<div class="mb-4">';
                            editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A:</label>';
                            editForm += '<input type="text" name="option_a" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="' + data.question.option_a + '">';
                            editForm += '</div>';
                            editForm += '<div class="mb-4">';
                            editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B:</label>';
                            editForm += '<input type="text" name="option_b" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="' + data.question.option_b + '">';
                            editForm += '</div>';
                            editForm += '<div class="mb-4">';
                            editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C:</label>';
                            editForm += '<input type="text" name="option_c" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="' + data.question.option_c + '">';
                            editForm += '</div>';
                            editForm += '<div class="mb-4">';
                            editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D:</label>';
                            editForm += '<input type="text" name="option_d" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="' + data.question.option_d + '">';
                            editForm += '</div>';
                            editForm += '<div class="mb-4">';
                            editForm += '<label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar:</label>';
                            editForm += '<select name="correct_answer" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">';
                            editForm += '<option value="A"' + (data.question.correct_answer === 'A' ? ' selected' : '') + '>A</option>';
                            editForm += '<option value="B"' + (data.question.correct_answer === 'B' ? ' selected' : '') + '>B</option>';
                            editForm += '<option value="C"' + (data.question.correct_answer === 'C' ? ' selected' : '') + '>C</option>';
                            editForm += '<option value="D"' + (data.question.correct_answer === 'D' ? ' selected' : '') + '>D</option>';
                            editForm += '</select>';
                            editForm += '</div>';
                        }
                        
                        editForm += '<div class="flex justify-between">';
                        editForm += '<button type="button" id="cancelEditBtn" class="bg-gray-500 text-white px-4 py-2 rounded">Batal</button>';
                        editForm += '<button type="button" id="saveEditBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Simpan</button>';
                        editForm += '</div>';
                        editForm += '</form>';
                        editForm += '</div>';
                        
                        // Replace modal content with edit form
                        document.getElementById('questionDetailsContent').innerHTML = editForm;
                        
                        // Add event listeners for cancel and save buttons
                        document.getElementById('cancelEditBtn').addEventListener('click', function() {
                            // Refresh the modal content to show original question details
                            const examId = document.querySelector('.modal-content').dataset.examId;
                            if (examId) {
                                showQuestionDetails(examId);
                            }
                        });
                        
                        document.getElementById('saveEditBtn').addEventListener('click', function() {
                            // Handle save functionality
                            const formData = new FormData(document.getElementById('editQuestionForm'));
                            const data = new URLSearchParams(formData).toString();
                            
                            fetch('get_exam_details.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: data + '&action=update_question'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Soal berhasil diperbarui!');
                                    // Refresh the modal content to show updated question details
                                    const examId = document.querySelector('.modal-content').dataset.examId;
                                    if (examId) {
                                        showQuestionDetails(examId);
                                    }
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Error updating question');
                            });
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error getting question details');
                });
            } else if (e.target.classList.contains('delete-btn')) {
                const questionId = e.target.dataset.id;
                handleDeleteQuestion(questionId);
            }
        });
        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterClassSelect = document.querySelector('select[name="filter_class"]');
            const filterTypeSelect = document.querySelector('select[name="filter_type"]');
            
            // Add loading feedback when filter changes
            function showFilterLoading(selectElement) {
                const originalText = selectElement.options[selectElement.selectedIndex].text;
                selectElement.style.opacity = '0.6';
                selectElement.disabled = true;
                
                // Re-enable after form submission
                setTimeout(() => {
                    selectElement.style.opacity = '1';
                    selectElement.disabled = false;
                }, 1000);
            }
            
            // Add change event listeners
            if (filterClassSelect) {
                filterClassSelect.addEventListener('change', function() {
                    showFilterLoading(this);
                });
            }
            
            if (filterTypeSelect) {
                filterTypeSelect.addEventListener('change', function() {
                    showFilterLoading(this);
                });
            }
        });

        // Toggle questions visibility
        function toggleQuestions(examId) {
            const container = document.getElementById('questions-container-' + examId);
            const toggleText = document.getElementById('toggle-text-' + examId);
            const button = toggleText.parentElement;
            
            // Add loading state
            button.style.opacity = '0.6';
            button.disabled = true;
            
            setTimeout(() => {
                if (container.style.display === 'none') {
                    container.style.display = 'block';
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(-10px)';
                    
                    // Fade in animation
                    setTimeout(() => {
                        container.style.transition = 'all 0.3s ease';
                        container.style.opacity = '1';
                        container.style.transform = 'translateY(0)';
                    }, 10);
                    
                    toggleText.textContent = 'Sembunyikan';
                } else {
                    container.style.transition = 'all 0.3s ease';
                    container.style.opacity = '0';
                    container.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        container.style.display = 'none';
                    }, 300);
                    
                    toggleText.textContent = 'Tampilkan';
                }
                
                // Remove loading state
                button.style.opacity = '1';
                button.disabled = false;
            }, 100);
        }
    </script>
</body>
</html>
