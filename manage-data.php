<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

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
            // Subject actions
            case 'add_subject':
                $name = trim($_POST['name']);
                
                if (empty($name)) {
                    $error = 'Nama mata pelajaran tidak boleh kosong!';
                } else {
                    $result = addSubject($name);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'update_subject':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                
                if (empty($name)) {
                    $error = 'Nama mata pelajaran tidak boleh kosong!';
                } else {
                    $result = updateSubject($id, $name);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'delete_subject':
                $id = $_POST['id'];
                $result = deleteSubject($id);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            // Class actions
            case 'add_class':
                $name = trim($_POST['name']);
                $teacher = trim($_POST['teacher']);
                
                if (empty($name)) {
                    $error = 'Nama kelas tidak boleh kosong!';
                } else {
                    $result = addClass($name, $teacher);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'update_class':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $teacher = trim($_POST['teacher']);
                
                if (empty($name)) {
                    $error = 'Nama kelas tidak boleh kosong!';
                } else {
                    $result = updateClass($id, $name, $teacher);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'delete_class':
                $id = $_POST['id'];
                $result = deleteClass($id);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            // Student actions
            case 'add_student':
                $student_id = trim($_POST['student_id']);
                $name = trim($_POST['name']);
                $class_id = $_POST['class_id'];
                $birth_place = trim($_POST['birth_place']);
                $birth_date = $_POST['birth_date'];
                $guardian = trim($_POST['guardian']);
                
                if (empty($name) || empty($class_id)) {
                    $error = 'Nama siswa dan kelas harus diisi!';
                } else {
                    $result = addStudent($student_id, $name, $class_id, $birth_place, $birth_date, $guardian);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'update_student':
                $id = $_POST['id'];
                $student_id = trim($_POST['student_id']);
                $name = trim($_POST['name']);
                $class_id = $_POST['class_id'];
                $birth_place = trim($_POST['birth_place']);
                $birth_date = $_POST['birth_date'];
                $guardian = trim($_POST['guardian']);
                
                if (empty($name) || empty($class_id)) {
                    $error = 'Nama siswa dan kelas harus diisi!';
                } else {
                    $result = updateStudent($id, $student_id, $name, $class_id, $birth_place, $birth_date, $guardian);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'delete_student':
                $id = $_POST['id'];
                $result = deleteStudent($id);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

$classes = getClasses();
$subjects = getSubjects();
$students = getStudents();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data - Baiturrahman Web</title>
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
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: linear-gradient(to right, #3b82f6, #6366f1);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
                        <h1 class="text-lg font-bold text-gray-800">Kelola Data</h1>
                        <p class="text-xs text-gray-600">Manajemen mata pelajaran, kelas & siswa</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-2xl shadow-lg mb-4 fade-in">
            <div class="flex p-2">
                <button onclick="showTab('subjects')" id="subjectsTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm active">
                    📚 Mata Pelajaran
                </button>
                <button onclick="showTab('classes')" id="classesTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm text-gray-600">
                    🏫 Kelas
                </button>
                <button onclick="showTab('students')" id="studentsTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm text-gray-600">
                    👥 Siswa
                </button>
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

            <!-- Subjects Tab -->
            <div id="subjectsContent" class="tab-content active">
                <!-- Add Subject -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Tambah Mata Pelajaran</h3>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="add_subject">
                        <div class="flex space-x-2">
                            <input type="text" name="name" placeholder="Nama mata pelajaran baru" required class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                            <button type="submit" class="bg-purple-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-purple-600 transition-all">
                                Tambah
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Subjects List -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Daftar Mata Pelajaran</h3>
                    <div class="space-y-2">
                        <?php foreach ($subjects as $subject): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($subject['name']); ?></span>
                                <div class="flex space-x-2">
                                    <button onclick="editSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['name'], ENT_QUOTES); ?>')" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini? Semua nilai terkait akan ikut terhapus!')">
                                        <input type="hidden" name="action" value="delete_subject">
                                        <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($subjects)): ?>
                            <p class="text-gray-500 text-center py-4">Belum ada mata pelajaran yang terdaftar</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Classes Tab -->
            <div id="classesContent" class="tab-content">
                <!-- Add Class -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Tambah Kelas</h3>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="add_class">
                        <input type="text" name="name" placeholder="Nama kelas baru (contoh: X IPA 1)" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        <input type="text" name="teacher" placeholder="Nama wali kelas" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        <button type="submit" class="w-full bg-orange-500 text-white py-3 rounded-xl font-semibold hover:bg-orange-600 transition-all">
                            Tambah Kelas
                        </button>
                    </form>
                </div>

                <!-- Classes List -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Daftar Kelas</h3>
                    <div class="space-y-2">
                        <?php foreach ($classes as $class): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($class['name']); ?></div>
                                        <div class="text-sm text-gray-600 mt-1">Wali Kelas: <?php echo htmlspecialchars($class['teacher']); ?></div>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <button onclick="editClass(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($class['teacher'], ENT_QUOTES); ?>')" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini? Semua siswa dan nilai di kelas ini akan ikut terhapus!')">
                                            <input type="hidden" name="action" value="delete_class">
                                            <input type="hidden" name="id" value="<?php echo $class['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                🗑️ Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($classes)): ?>
                            <p class="text-gray-500 text-center py-4">Belum ada kelas yang terdaftar</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Students Tab -->
            <div id="studentsContent" class="tab-content">
                <!-- Add Student -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Tambah Siswa</h3>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="add_student">
                        <input type="text" name="student_id" placeholder="No Induk Siswa (NIS)" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <input type="text" name="name" placeholder="Nama lengkap siswa" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <select name="class_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="birth_place" placeholder="Tempat lahir" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <input type="date" name="birth_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <input type="text" name="guardian" placeholder="Nama wali/orang tua" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                        <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-xl font-semibold hover:bg-green-600 transition-all">
                            Tambah Siswa
                        </button>
                    </form>
                </div>

                <!-- Students List -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Daftar Siswa</h3>
                    <div class="space-y-3">
                        <?php 
                        $currentClass = '';
                        foreach ($students as $student): 
                            if ($currentClass !== $student['class_name']):
                                if ($currentClass !== '') echo '</div>';
                                $currentClass = $student['class_name'];
                        ?>
                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-lg font-semibold">
                                Kelas <?php echo htmlspecialchars($student['class_name']); ?>
                            </div>
                            <div class="ml-4 space-y-2">
                        <?php endif; ?>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name']); ?></div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <div>NIS: <?php echo htmlspecialchars($student['student_id'] ?: 'Belum diisi'); ?></div>
                                        <div>TTL: <?php echo htmlspecialchars($student['birth_place'] ?: 'Belum diisi'); ?>, <?php echo $student['birth_date'] ? date('d/m/Y', strtotime($student['birth_date'])) : 'Belum diisi'; ?></div>
                                    </div>
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="editStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['student_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($student['name'], ENT_QUOTES); ?>', <?php echo $student['class_id']; ?>, '<?php echo htmlspecialchars($student['birth_place'], ENT_QUOTES); ?>', '<?php echo $student['birth_date']; ?>', '<?php echo htmlspecialchars($student['guardian'], ENT_QUOTES); ?>')" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        ✏️ Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus siswa ini? Semua nilai siswa ini akan ikut terhapus!')">
                                        <input type="hidden" name="action" value="delete_student">
                                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                        <?php if (!empty($students)) echo '</div>'; ?>
                        
                        <?php if (empty($students)): ?>
                            <p class="text-gray-500 text-center py-4">Belum ada siswa yang terdaftar</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div id="editSubjectModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Mata Pelajaran</h2>
            <form method="POST" id="editSubjectForm">
                <input type="hidden" name="action" value="update_subject">
                <input type="hidden" name="id" id="editSubjectId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Mata Pelajaran</label>
                        <input type="text" name="name" id="editSubjectName" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-purple-500 text-white py-3 rounded-xl font-semibold hover:bg-purple-600 transition-all">
                        Simpan Perubahan
                    </button>
                    <button type="button" onclick="hideEditSubjectModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div id="editClassModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Kelas</h2>
            <form method="POST" id="editClassForm">
                <input type="hidden" name="action" value="update_class">
                <input type="hidden" name="id" id="editClassId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Kelas</label>
                        <input type="text" name="name" id="editClassName" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Wali Kelas</label>
                        <input type="text" name="teacher" id="editClassTeacher" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-orange-500 text-white py-3 rounded-xl font-semibold hover:bg-orange-600 transition-all">
                        Simpan Perubahan
                    </button>
                    <button type="button" onclick="hideEditClassModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editStudentModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Data Siswa</h2>
            <form method="POST" id="editStudentForm">
                <input type="hidden" name="action" value="update_student">
                <input type="hidden" name="id" id="editStudentId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">No Induk Siswa (NIS)</label>
                        <input type="text" name="student_id" id="editStudentNIS" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                        <input type="text" name="name" id="editStudentName" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kelas *</label>
                        <select name="class_id" id="editStudentClass" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tempat Lahir</label>
                        <input type="text" name="birth_place" id="editStudentBirthPlace" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir</label>
                        <input type="date" name="birth_date" id="editStudentBirthDate" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Wali/Orang Tua</label>
                        <input type="text" name="guardian" id="editStudentGuardian" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-green-500 text-white py-3 rounded-xl font-semibold hover:bg-green-600 transition-all">
                        Simpan Perubahan
                    </button>
                    <button type="button" onclick="hideEditStudentModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
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
                <a href="manage-data.php" class="flex flex-col items-center py-2 px-3 text-blue-600 bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-xs font-medium">Kelola Data</span>
                </a>
                <a href="exams.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span class="text-xs font-medium">Soal</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
                button.classList.add('text-gray-600');
            });
            
            // Show selected tab content
            document.getElementById(tabName + 'Content').classList.add('active');
            
            // Add active class to selected tab button
            const activeButton = document.getElementById(tabName + 'Tab');
            activeButton.classList.add('active');
            activeButton.classList.remove('text-gray-600');
        }

        // Subject functions
        function editSubject(id, currentName) {
            document.getElementById('editSubjectId').value = id;
            document.getElementById('editSubjectName').value = currentName;
            document.getElementById('editSubjectModal').style.display = 'flex';
        }

        function hideEditSubjectModal() {
            document.getElementById('editSubjectModal').style.display = 'none';
        }

        // Class functions
        function editClass(id, name, teacher) {
            document.getElementById('editClassId').value = id;
            document.getElementById('editClassName').value = name;
            document.getElementById('editClassTeacher').value = teacher;
            document.getElementById('editClassModal').style.display = 'flex';
        }

        function hideEditClassModal() {
            document.getElementById('editClassModal').style.display = 'none';
        }

        // Student functions
        function editStudent(id, studentId, name, classId, birthPlace, birthDate, guardian) {
            document.getElementById('editStudentId').value = id;
            document.getElementById('editStudentNIS').value = studentId;
            document.getElementById('editStudentName').value = name;
            document.getElementById('editStudentClass').value = classId;
            document.getElementById('editStudentBirthPlace').value = birthPlace;
            document.getElementById('editStudentBirthDate').value = birthDate;
            document.getElementById('editStudentGuardian').value = guardian;
            document.getElementById('editStudentModal').style.display = 'flex';
        }

        function hideEditStudentModal() {
            document.getElementById('editStudentModal').style.display = 'none';
        }
    </script>
</body>
</html>