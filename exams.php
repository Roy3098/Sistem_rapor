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
                $exam_id = null;
                $success_count = 0;
                $errors = [];
                if ($question_type === 'essay') {
                    $essay_questions = trim($_POST['essay_questions'] ?? '');
                    $lines = array_filter(array_map('trim', explode("\n", $essay_questions)));
                    foreach ($lines as $line) {
                        $question_data = ['text' => $line];
                        $result = createExam($subject_id, $class_id, 'questions', null, 'essay', $question_data, $exam_id);
                        if ($result['success']) {
                            $success_count++;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                } elseif ($question_type === 'multiple_choice') {
                    $mc_questions = $_POST['mc_question'] ?? [];
                    $option_a = $_POST['option_a'] ?? [];
                    $option_b = $_POST['option_b'] ?? [];
                    $option_c = $_POST['option_c'] ?? [];
                    $option_d = $_POST['option_d'] ?? [];
                    $correct_answer = $_POST['correct_answer'] ?? [];
                    for ($i = 0; $i < count($mc_questions); $i++) {
                        $q = trim($mc_questions[$i]);
                        if ($q === '' || empty($option_a[$i]) || empty($option_b[$i]) || empty($option_c[$i]) || empty($option_d[$i]) || empty($correct_answer[$i])) {
                            $errors[] = 'Ada soal pilihan ganda yang belum lengkap.';
                            continue;
                        }
                        $question_data = [
                            'text' => $q,
                            'option_a' => $option_a[$i],
                            'option_b' => $option_b[$i],
                            'option_c' => $option_c[$i],
                            'option_d' => $option_d[$i],
                            'correct_answer' => strtoupper($correct_answer[$i])
                        ];
                        $result = createExam($subject_id, $class_id, 'questions', null, 'multiple_choice', $question_data, $exam_id);
                        if ($result['success']) {
                            $success_count++;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
                if ($success_count > 0) {
                    $message = "Berhasil menambah {$success_count} soal!";
                    if (!empty($errors)) {
                        $message .= " Namun ada error: " . implode(', ', $errors);
                    }
                } else {
                    $error = 'Tidak ada soal yang berhasil disimpan. ' . implode(', ', $errors);
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

            case 'delete_exam_group':
                $class_id = $_POST['class_id'];
                $subject_id = $_POST['subject_id'];
                $db = getDbConnection();
                $stmt = $db->prepare("DELETE FROM exams WHERE class_id = ? AND subject_id = ?");
                if ($stmt->execute([$class_id, $subject_id])) {
                    $message = "Semua soal untuk kelas {$class_id} - {$subject_id} berhasil dihapus.";
                } else {
                    $error = "Gagal menghapus soal: " . $stmt->errorInfo()[2];
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
                    // Pastikan $db sudah diinisialisasi
                    if (!isset($db) || !$db) { $db = getDbConnection(); }
                    // Tampilkan file ujian (type=file)
                    $stmtFiles = $db->query("SELECT * FROM exams WHERE type = 'file'");
                    $fileExams = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($fileExams as $exam): ?>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-600">Mata Pelajaran: <?php echo htmlspecialchars($exam['subject_name']); ?></p>
                                    <p class="text-sm text-gray-600">File: <?php echo htmlspecialchars($exam['file_name']); ?></p>
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mt-1">📁 File Ujian</span>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2 mt-3">
                                <a href="?action=download&id=<?php echo $exam['id']; ?>" class="text-purple-500 hover:text-purple-700 text-sm font-medium">⬇️ Unduh</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php
                    // Ambil semua kombinasi kelas-mapel yang ada soal
                    $db = getDbConnection();
                    $stmt = $db->query("SELECT class_id, subject_id FROM exams WHERE type = 'questions' GROUP BY class_id, subject_id");
                    $groupedKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($groupedKeys as $row):
                        // Ambil exam perwakilan (id terkecil)
                        $stmtExam = $db->prepare("SELECT * FROM exams WHERE class_id = ? AND subject_id = ? AND type = 'questions' ORDER BY id ASC LIMIT 1");
                        $stmtExam->execute([$row['class_id'], $row['subject_id']]);
                        $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);
                        // Ambil nama kelas dan mapel
                        $stmtClass = $db->prepare("SELECT name FROM classes WHERE id = ?");
                        $stmtClass->execute([$row['class_id']]);
                        $className = $stmtClass->fetchColumn();
                        $stmtSubject = $db->prepare("SELECT name FROM subjects WHERE id = ?");
                        $stmtSubject->execute([$row['subject_id']]);
                        $subjectName = $stmtSubject->fetchColumn();
                        // Gabungkan semua soal dari semua exam id
                        $stmtAllIds = $db->prepare("SELECT id FROM exams WHERE class_id = ? AND subject_id = ? AND type = 'questions'");
                        $stmtAllIds->execute([$row['class_id'], $row['subject_id']]);
                        $allExamIds = array_column($stmtAllIds->fetchAll(PDO::FETCH_ASSOC), 'id');
                        $questions = [];
                        foreach ($allExamIds as $eid) {
                            $stmtQ = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
                            $stmtQ->execute([$eid]);
                            $questions = array_merge($questions, $stmtQ->fetchAll(PDO::FETCH_ASSOC));
                        }
                        $totalQuestions = count($questions);
                        if ($totalQuestions === 0) continue;
                    ?>
                        <div class="bg-white rounded-lg p-4 border border-green-200 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="font-semibold text-gray-800 text-sm">Kelas <?php echo htmlspecialchars($className); ?> - <?php echo htmlspecialchars($subjectName); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Total Soal: <span class="font-bold text-green-700"><?php echo $totalQuestions; ?></span></div>
                                </div>
                                <div class="flex gap-2">
                                    <a href="?action=download&id=<?php echo $exam['id']; ?>" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-semibold hover:bg-blue-200">⬇️ Download</a>
                                    <button onclick="showQuestionDetails(<?php echo $exam['id']; ?>)" class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs font-semibold hover:bg-green-200">👁️ Detail</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus semua soal untuk kelas dan mapel ini?')">
                                        <input type="hidden" name="action" value="delete_exam_group">
                                        <input type="hidden" name="class_id" value="<?php echo $exam['class_id']; ?>">
                                        <input type="hidden" name="subject_id" value="<?php echo $exam['subject_id']; ?>">
                                        <button type="submit" class="bg-red-100 text-red-700 px-3 py-1 rounded text-xs font-semibold hover:bg-red-200">🗑️ Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-2 space-y-2">
                                <?php
                                $qnum = 1;
                                foreach ($questions as $q):
                                ?>
                                <div class="bg-gray-50 border border-gray-100 rounded p-2 text-xs">
                                    <div class="font-semibold text-gray-700 mb-1"><?php echo $qnum++; ?>. <?php echo htmlspecialchars($q['question_text']); ?></div>
                                    <?php if ($q['question_type'] === 'multiple_choice'): ?>
                                        <div class="ml-3">
                                            <div>A. <?php echo htmlspecialchars($q['option_a']); ?></div>
                                            <div>B. <?php echo htmlspecialchars($q['option_b']); ?></div>
                                            <div>C. <?php echo htmlspecialchars($q['option_c']); ?></div>
                                            <div>D. <?php echo htmlspecialchars($q['option_d']); ?></div>
                                            <div class="mt-1 text-green-700 font-bold">Jawaban: <?php echo htmlspecialchars($q['correct_answer']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($groupedKeys) && empty($fileExams)): ?>
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
                    <div class="text-xs text-gray-500 bg-blue-50 border border-blue-200 rounded-lg p-2">
                        <b>Catatan:</b> Untuk soal essay, masukkan beberapa soal sekaligus (satu soal per baris). Untuk pilihan ganda, klik tombol tambah untuk menambah blok soal baru.
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Soal</label>
                        <select name="question_type" id="questionTypeSelect" onchange="toggleQuestionFields()" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Tipe Soal</option>
                            <option value="essay">Essay</option>
                            <option value="multiple_choice">Pilihan Ganda</option>
                        </select>
                    </div>
                    <div id="essayBlock" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Soal Essay (satu soal per baris)</label>
                        <textarea name="essay_questions" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all h-32" placeholder="Contoh:\nSebutkan rukun iman!\nApa ibukota Indonesia?\n..."></textarea>
                    </div>
                    <div id="mcBlocks" style="display:none;">
                        <div id="mcContainer"></div>
                        <button type="button" onclick="addMCBlock()" class="mt-2 bg-blue-500 text-white px-3 py-1 rounded text-xs font-semibold">+ Tambah Soal Pilihan Ganda</button>
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

    <!-- Edit Question Modal -->
    <div id="editQuestionModal" class="modal">
        <div class="modal-content max-w-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Soal</h2>
            <form id="editQuestionForm">
                <input type="hidden" name="question_id" id="editQuestionId">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pertanyaan</label>
                    <textarea name="question_text" id="editQuestionText" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all h-24"></textarea>
                </div>
                <div id="editMCOptions" style="display:none;">
                    <div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Pilihan A</label><input type="text" name="option_a" id="editOptionA" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Pilihan B</label><input type="text" name="option_b" id="editOptionB" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Pilihan C</label><input type="text" name="option_c" id="editOptionC" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Pilihan D</label><input type="text" name="option_d" id="editOptionD" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                    <div class="mb-2"><label class="block text-sm font-medium text-gray-700 mb-1">Jawaban Benar</label><select name="correct_answer" id="editCorrectAnswer" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select></div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-600 transition-all">Simpan</button>
                    <button type="button" onclick="hideEditQuestionModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">Batal</button>
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
            const type = document.getElementById('questionTypeSelect').value;
            document.getElementById('essayBlock').style.display = type === 'essay' ? '' : 'none';
            document.getElementById('mcBlocks').style.display = type === 'multiple_choice' ? '' : 'none';
        }

        let mcIndex = 0;
        function addMCBlock() {
            const container = document.getElementById('mcContainer');
            const idx = mcIndex++;
            const block = document.createElement('div');
            block.className = 'border border-blue-200 rounded-lg p-3 mb-2 bg-blue-50';
            block.innerHTML = `
                <div class='flex justify-between items-center mb-2'>
                    <span class='font-semibold text-blue-700'>Soal Pilihan Ganda</span>
                    <button type='button' onclick='this.parentNode.parentNode.remove()' class='text-red-500 text-xs font-bold'>Hapus</button>
                </div>
                <textarea name='mc_question[]' required class='w-full px-2 py-1 border border-gray-300 rounded mb-2' placeholder='Tulis pertanyaan...'></textarea>
                <div class='grid grid-cols-2 gap-2 mb-2'>
                    <input name='option_a[]' required class='px-2 py-1 border border-gray-300 rounded' placeholder='Pilihan A'>
                    <input name='option_b[]' required class='px-2 py-1 border border-gray-300 rounded' placeholder='Pilihan B'>
                    <input name='option_c[]' required class='px-2 py-1 border border-gray-300 rounded' placeholder='Pilihan C'>
                    <input name='option_d[]' required class='px-2 py-1 border border-gray-300 rounded' placeholder='Pilihan D'>
                </div>
                <select name='correct_answer[]' required class='w-full px-2 py-1 border border-gray-300 rounded'>
                    <option value=''>Jawaban Benar</option>
                    <option value='A'>A</option>
                    <option value='B'>B</option>
                    <option value='C'>C</option>
                    <option value='D'>D</option>
                </select>
            `;
            container.appendChild(block);
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

        function showEditQuestionModal(questionId) {
            // Ambil detail soal via AJAX
            fetch('get_question_detail.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'question_id=' + questionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editQuestionId').value = data.question.id;
                    document.getElementById('editQuestionText').value = data.question.question_text;
                    if (data.question.question_type === 'multiple_choice') {
                        document.getElementById('editMCOptions').style.display = 'block';
                        document.getElementById('editOptionA').value = data.question.option_a;
                        document.getElementById('editOptionB').value = data.question.option_b;
                        document.getElementById('editOptionC').value = data.question.option_c;
                        document.getElementById('editOptionD').value = data.question.option_d;
                        document.getElementById('editCorrectAnswer').value = data.question.correct_answer;
                    } else {
                        document.getElementById('editMCOptions').style.display = 'none';
                    }
                    document.getElementById('editQuestionModal').style.display = 'flex';
                } else {
                    alert('Gagal memuat detail soal');
                }
            });
        }

        function hideEditQuestionModal() {
            document.getElementById('editQuestionModal').style.display = 'none';
        }

        document.getElementById('editQuestionForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('update_question.php', {
                method: 'POST',
                body: new URLSearchParams([...formData])
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Soal berhasil diupdate!');
                    hideEditQuestionModal();
                    // Refresh detail modal
                    if (window.lastExamIdDetail) showQuestionDetails(window.lastExamIdDetail);
                } else {
                    alert('Gagal update soal: ' + data.message);
                }
            });
        };
        // Untuk memudahkan refresh detail setelah edit
        window.showEditQuestionModal = showEditQuestionModal;

        document.getElementById('questionForm').onsubmit = function(e) {
            const type = document.getElementById('questionTypeSelect').value;
            if (type === 'essay') {
                const val = document.querySelector('[name=essay_questions]').value.trim();
                if (!val) {
                    alert('Masukkan minimal satu soal essay!');
                    e.preventDefault();
                    return false;
                }
            } else if (type === 'multiple_choice') {
                const mcBlocks = document.querySelectorAll('#mcContainer > div');
                if (mcBlocks.length === 0) {
                    alert('Tambahkan minimal satu soal pilihan ganda!');
                    e.preventDefault();
                    return false;
                }
            }
        };

        function showAddQuestionModal(classId, subjectId) {
            showQuestionModal();
            document.querySelector('select[name=class_id]').value = classId;
            document.querySelector('select[name=subject_id]').value = subjectId;
            document.getElementById('questionTypeSelect').selectedIndex = 0;
            document.getElementById('essayBlock').style.display = 'none';
            document.getElementById('mcBlocks').style.display = 'none';
            // Kosongkan input
            if(document.querySelector('[name=essay_questions]')) document.querySelector('[name=essay_questions]').value = '';
            if(document.getElementById('mcContainer')) document.getElementById('mcContainer').innerHTML = '';
        }
        window.showAddQuestionModal = showAddQuestionModal;
    </script>
</body>
</html>