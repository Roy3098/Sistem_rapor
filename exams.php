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
                
            case 'create_question':
                $subject_id = $_POST['subject_id'];
                $class_id = $_POST['class_id'];
                $question_type = $_POST['question_type'];
                $question_text = trim($_POST['question_text']);
                $exam_id = !empty($_POST['exam_id']) ? $_POST['exam_id'] : null;
                
                if (empty($subject_id) || empty($class_id) || empty($question_type) || empty($question_text)) {
                    $error = 'Harap lengkapi semua field!';
                } else {
                    $question_data = ['text' => $question_text];
                    
                    if ($question_type === 'multiple_choice') {
                        $option_a = trim($_POST['option_a'] ?? '');
                        $option_b = trim($_POST['option_b'] ?? '');
                        $option_c = trim($_POST['option_c'] ?? '');
                        $option_d = trim($_POST['option_d'] ?? '');
                        $correct_answer = strtoupper(trim($_POST['correct_answer'] ?? ''));
                        
                        if (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_answer)) {
                            $error = 'Harap lengkapi semua pilihan jawaban!';
                            break;
                        }
                        
                        if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                            $error = 'Jawaban benar harus A, B, C, atau D!';
                            break;
                        }
                        
                        $question_data['option_a'] = $option_a;
                        $question_data['option_b'] = $option_b;
                        $question_data['option_c'] = $option_c;
                        $question_data['option_d'] = $option_d;
                        $question_data['correct_answer'] = $correct_answer;
                    }
                    
                    $result = createExam($subject_id, $class_id, 'questions', null, $question_type, $question_data, $exam_id);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
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
            // Generate Word document for questions
            generateWordDocument($exam);
            exit();
        }
    } else {
        $error = 'Soal tidak ditemukan!';
    }
}

$classes = getClasses();
$subjects = getSubjects();
$exams = getExams();
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
            <div class="grid grid-cols-2 gap-3 mb-6">
                <button onclick="showUploadModal()" class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all text-sm">
                    📁 Upload File
                </button>
                <button onclick="showQuestionModal()" class="bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-4 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all text-sm">
                    ✏️ Buat Soal
                </button>
            </div>

            <!-- Exams List -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Daftar Soal & File Ujian</h3>
                <div class="space-y-3">
                    <?php 
                    $currentClass = '';
                    foreach ($exams as $exam): 
                        if ($currentClass !== $exam['class_name']):
                            if ($currentClass !== '') echo '</div>';
                            $currentClass = $exam['class_name'];
                    ?>
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-lg font-semibold">
                            Kelas <?php echo htmlspecialchars($exam['class_name']); ?>
                        </div>
                        <div class="ml-4 space-y-2">
                    <?php endif; ?>
                    
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600">Mata Pelajaran: <?php echo htmlspecialchars($exam['subject_name']); ?></p>
                                <?php if ($exam['type'] === 'file'): ?>
                                    <p class="text-sm text-gray-600">File: <?php echo htmlspecialchars($exam['file_name']); ?></p>
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mt-1">📁 File Ujian</span>
                                <?php else: ?>
                                    <?php
                                    // Get question type from questions table
                                    $db = getDbConnection();
                                    $stmt = $db->prepare("SELECT question_type FROM questions WHERE exam_id = ? LIMIT 1");
                                    $stmt->execute([$exam['id']]);
                                    $question = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $questionType = $question ? $question['question_type'] : 'unknown';
                                    ?>
                                    <p class="text-sm text-gray-600">Tipe: <?php echo $questionType === 'essay' ? 'Essay' : ($questionType === 'multiple_choice' ? 'Pilihan Ganda' : 'Tidak diketahui'); ?></p>
                                    <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full mt-1">
                                        <?php echo $questionType === 'essay' ? '✏️ Soal Essay' : ($questionType === 'multiple_choice' ? '☑️ Pilihan Ganda' : '❓ Soal'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2 mt-3">
                            <a href="?action=download&id=<?php echo $exam['id']; ?>" class="text-purple-500 hover:text-purple-700 text-sm font-medium">
                                ⬇️ Unduh
                            </a>
                            <button onclick="showQuestionDetails(<?php echo $exam['id']; ?>)" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                👁️ Lihat
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus soal ini?')">
                                <input type="hidden" name="action" value="delete_exam">
                                <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php endforeach; ?>
                    <?php if (!empty($exams)) echo '</div>'; ?>
                    
                    <?php if (empty($exams)): ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500">Belum ada soal atau file ujian yang tersimpan</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
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
                <input type="hidden" name="action" value="create_question">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                        <select name="subject_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Mata Pelajaran</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                        <select name="class_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Ujian (opsional, untuk menambah soal ke ujian yang sudah ada)</label>
                        <select name="exam_id" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Buat Ujian Baru</option>
                            <?php foreach ($exams as $exam): ?>
                                <?php if ($exam['type'] === 'questions'): ?>
                                    <option value="<?php echo $exam['id']; ?>">Ujian: <?php echo htmlspecialchars($exam['title']); ?> (<?php echo htmlspecialchars($exam['subject_name']); ?> - Kelas <?php echo htmlspecialchars($exam['class_name']); ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Soal</label>
                        <select name="question_type" onchange="toggleQuestionFields()" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Tipe Soal</option>
                            <option value="essay">Essay</option>
                            <option value="multiple_choice">Pilihan Ganda</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                        <textarea name="question_text" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-32" placeholder="Tuliskan soal di sini..."></textarea>
                    </div>
                    
                    <!-- Multiple Choice Options -->
                    <div id="mcOptions" style="display: none;">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan A</label>
                                <input type="text" name="option_a" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan A">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan B</label>
                                <input type="text" name="option_b" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan B">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan C</label>
                                <input type="text" name="option_c" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan C">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilihan D</label>
                                <input type="text" name="option_d" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilihan D">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jawaban Benar</label>
                                <select name="correct_answer" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
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
        <div class="modal-content">
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
        function showUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }

        function hideUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function showQuestionModal() {
            document.getElementById('questionModal').style.display = 'flex';
        }

        function hideQuestionModal() {
            document.getElementById('questionModal').style.display = 'none';
            document.getElementById('questionForm').reset();
            document.getElementById('mcOptions').style.display = 'none';
        }

        function toggleQuestionFields() {
            const questionType = document.querySelector('select[name="question_type"]').value;
            const mcOptions = document.getElementById('mcOptions');
            
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
    </script>
</body>
</html>