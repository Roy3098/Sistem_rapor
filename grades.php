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

// Get data
try {
    $classes = getClasses();
    $subjects = getSubjects();
} catch(Exception $e) {
    header('Location: install.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save_grades_bulk') {
            $subject_id = $_POST['subject_id'];
            $class_id = $_POST['class_id'];
            $semester = $_POST['semester'];
            $grades = $_POST['grades'] ?? [];
            
            if (empty($subject_id) || empty($class_id) || empty($semester)) {
                $error = 'Harap lengkapi semua field!';
            } elseif (empty($grades)) {
                $error = 'Harap masukkan minimal satu nilai!';
            } else {
                $saved_count = 0;
                $errors = [];
                
                foreach ($grades as $student_id => $grade) {
                    if (!empty($grade) && is_numeric($grade)) {
                        $grade = (float)$grade;
                        if ($grade >= 0 && $grade <= 100) {
                            $result = saveGrade($student_id, $subject_id, $grade, $semester);
                            if ($result['success']) {
                                $saved_count++;
                            } else {
                                $errors[] = $result['message'];
                            }
                        } else {
                            $errors[] = "Nilai untuk siswa ID {$student_id} harus antara 0-100";
                        }
                    }
                }
                
                if ($saved_count > 0) {
                    $message = "Berhasil menyimpan {$saved_count} nilai!";
                    if (!empty($errors)) {
                        $message .= " Namun ada beberapa error: " . implode(', ', $errors);
                    }
                } else {
                    $error = 'Tidak ada nilai yang berhasil disimpan. ' . implode(', ', $errors);
                }
            }
        } elseif ($_POST['action'] === 'save_grade') {
            $student_id = $_POST['student_id'];
            $subject_id = $_POST['subject_id'];
            $grade = $_POST['grade'];
            $semester = $_POST['semester'];
            
            if (empty($student_id) || empty($subject_id) || empty($grade) || empty($semester)) {
                $error = 'Harap lengkapi semua field!';
            } elseif (!is_numeric($grade) || $grade < 0 || $grade > 100) {
                $error = 'Nilai harus berupa angka antara 0-100!';
            } else {
                $result = saveGrade($student_id, $subject_id, $grade, $semester);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Handle AJAX requests for students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_students') {
        $class_id = $_POST['class_id'];
        $students = getStudents($class_id);
        echo json_encode(['success' => true, 'students' => $students]);
        exit();
    } else if ($_POST['action'] === 'edit_grade') {
        $student_id = $_POST['student_id'];
        $subject_id = $_POST['subject_id'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'] ?? null;
        $grade = $_POST['grade'];
        $result = saveGrade($student_id, $subject_id, $grade, $semester, $academic_year);
        echo json_encode($result);
        exit();
    } else if ($_POST['action'] === 'delete_grade') {
        $student_id = $_POST['student_id'];
        $subject_id = $_POST['subject_id'];
        $semester = $_POST['semester'];
        $academic_year = $_POST['academic_year'] ?? null;
        require_once 'includes/functions.php';
        $result = deleteGrade($student_id, $subject_id, $semester, $academic_year);
        echo json_encode($result);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai - Baiturrahman Web</title>
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
                        <h1 class="text-lg font-bold text-gray-800">Nilai Siswa</h1>
                        <p class="text-xs text-gray-600">Input dan lihat nilai siswa</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-2xl shadow-lg mb-4 fade-in">
            <div class="flex p-2">
                <button onclick="showTab('input')" id="inputTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm active">
                    📝 Input Nilai
                </button>
                <button onclick="showTab('results')" id="resultsTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm text-gray-600">
                    📊 Lihat Nilai
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
            
            <!-- Input Tab -->
            <div id="inputContent" class="tab-content active">
            
                <!-- Bulk Input Form -->
                <div id="bulkInputForm">
                    <form method="POST" class="space-y-4" id="bulkGradeForm">
                        <input type="hidden" name="action" value="save_grades_bulk">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran</label>
                            <select name="subject_id" id="bulkSubjectSelect" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Mata Pelajaran</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Kelas</label>
                            <select name="class_id" id="bulkClassSelect" onchange="loadStudentsForBulk()" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                            <select name="semester" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                            </select>
                        </div>
                        
                        <div id="studentsGradeContainer" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Input Nilai Siswa</h3>
                            <div id="studentsList" class="space-y-3">
                                <!-- Students will be loaded here -->
                            </div>
                            
                            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all mt-4">
                                💾 Simpan Semua Nilai
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Tab -->
            <div id="resultsContent" class="tab-content">
                <!-- Filter -->
                <div class="mb-6">
                    <div class="flex space-x-2 mb-4">
                        <select id="filterClass" onchange="filterResults()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">Kelas <?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filterSemester" onchange="filterResults()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                    </div>
                    <a href="export.php?action=export_excel" onclick="return addFilterParams(this)" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        📊 Ekspor ke Excel
                    </a>
                </div>

                <!-- Results Container -->
                <div id="resultsContainer">
                    <!-- Akan diisi oleh JavaScript -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="text-center py-8" style="display: none;">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">Belum ada data nilai yang tersimpan</p>
                </div>
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
                <a href="grades.php" class="flex flex-col items-center py-2 px-3 text-blue-600 bg-blue-50 rounded-lg transition-all">
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
        function loadStudentsForBulk() {
            const classSelect = document.getElementById('bulkClassSelect');
            const container = document.getElementById('studentsGradeContainer');
            const studentsList = document.getElementById('studentsList');
            const classId = classSelect.value;
            
            if (!classId) {
                container.style.display = 'none';
                return;
            }
            
            fetch('grades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_students&class_id=${classId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    studentsList.innerHTML = '';
                    data.students.forEach(student => {
                        const div = document.createElement('div');
                        div.className = 'bg-gray-50 rounded-lg p-3 flex items-center justify-between';
                        div.innerHTML = `
                            <span class="font-medium text-gray-800">${student.name}</span>
                            <input type="number" name="grades[${student.id}]" min="0" max="100" 
                                   class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                   placeholder="0-100">
                        `;
                        studentsList.appendChild(div);
                    });
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                    alert('Error loading students: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.style.display = 'none';
            });
        }

        function loadStudents() {
            const classSelect = document.getElementById('classSelect');
            const studentSelect = document.getElementById('studentSelect');
            const classId = classSelect.value;
            
            if (!classId) {
                studentSelect.innerHTML = '<option value="">Pilih kelas terlebih dahulu</option>';
                studentSelect.disabled = true;
                return;
            }
            
            studentSelect.innerHTML = '<option value="">Memuat...</option>';
            studentSelect.disabled = true;
            
            fetch('grades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_students&class_id=${classId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    studentSelect.innerHTML = '<option value="">Pilih Siswa</option>';
                    data.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = student.name;
                        studentSelect.appendChild(option);
                    });
                    studentSelect.disabled = false;
                } else {
                    studentSelect.innerHTML = '<option value="">Error memuat siswa</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                studentSelect.innerHTML = '<option value="">Error memuat siswa</option>';
            });
        }
    </script>

    <style>
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
            
            // Load results if switching to results tab
            if (tabName === 'results') {
                loadResults();
            }
        }

        function loadResults() {
            fetch('results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&action=get_grades'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.grades);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function filterResults() {
            const classFilter = document.getElementById('filterClass').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            
            fetch('results.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_grades&class_id=${classFilter}&semester=${semesterFilter}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.grades);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function displayResults(grades) {
            const container = document.getElementById('resultsContainer');
            const emptyState = document.getElementById('emptyState');
            
            if (grades.length === 0) {
                container.innerHTML = '';
                emptyState.style.display = 'block';
                return;
            }
            
            emptyState.style.display = 'none';
            
            // Group grades by student
            const groupedGrades = {};
            grades.forEach(grade => {
                const key = `${grade.student_name}_${grade.class_name}_${grade.semester}`;
                if (!groupedGrades[key]) {
                    groupedGrades[key] = {
                        student_name: grade.student_name,
                        student_id: grade.student_id,
                        class_name: grade.class_name,
                        semester: grade.semester,
                        grades: []
                    };
                }
                groupedGrades[key].grades.push({
                    subject: grade.subject_name,
                    subject_id: grade.subject_id,
                    grade: parseFloat(grade.grade),
                    semester: grade.semester,
                    academic_year: grade.academic_year
                });
            });
            
            let html = '';
            Object.values(groupedGrades).forEach(student => {
                const totalGrade = student.grades.reduce((sum, g) => sum + g.grade, 0);
                const average = (totalGrade / student.grades.length).toFixed(1);
                html += `
                    <div class="bg-gradient-to-br from-blue-400/80 to-indigo-500/80 rounded-2xl p-4 mb-4 shadow-xl cursor-pointer hover:scale-105 hover:shadow-2xl transition-all duration-200 border-2 border-white/60 relative overflow-hidden group" onclick='showGradeDetailsModal(${JSON.stringify(student).replace(/'/g, "&#39;")})'>
                        <div class="absolute -top-4 -right-4 opacity-10 text-8xl pointer-events-none select-none group-hover:opacity-20 transition-all">📊</div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-bold text-white text-lg flex items-center gap-2"><span class='inline-block bg-white/20 rounded-full p-1'><svg class='w-5 h-5 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 14l9-5-9-5-9 5 9 5zm0 7v-6m0 6H5a2 2 0 01-2-2V7m17 12a2 2 0 002-2V7'></path></svg></span>${student.student_name}</div>
                                <div class="text-indigo-100 text-xs mt-1">Kelas ${student.class_name} • Semester ${student.semester}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-extrabold text-2xl text-white drop-shadow">${totalGrade}</div>
                                <div class="text-indigo-100 text-xs">Total</div>
                                <div class="font-bold text-lg text-yellow-200">${average}</div>
                                <div class="text-indigo-100 text-xs">Rata2</div>
                                <div class="text-indigo-100 text-xs mt-1">${student.grades.length} mapel</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // Modal HTML
        if (!document.getElementById('gradeDetailModal')) {
            const modal = document.createElement('div');
            modal.id = 'gradeDetailModal';
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden';
            modal.innerHTML = `
                <div class="bg-gradient-to-br from-white via-blue-50 to-indigo-100 rounded-2xl shadow-2xl w-full max-w-md p-0 relative border-2 border-blue-200 animate-fadeIn">
                    <div class='flex items-center justify-between px-6 py-4 border-b border-blue-100 bg-gradient-to-r from-blue-500/80 to-indigo-400/80 rounded-t-2xl'>
                        <div class='flex items-center gap-2 text-white'>
                            <svg class='w-7 h-7' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 14l9-5-9-5-9 5 9 5zm0 7v-6m0 6H5a2 2 0 01-2-2V7m17 12a2 2 0 002-2V7'></path></svg>
                            <span class='font-bold text-lg'>Detail Nilai Siswa</span>
                        </div>
                        <button onclick="closeGradeDetailModal()" class="text-white hover:text-red-200 text-2xl font-bold">✕</button>
                    </div>
                    <div id="gradeDetailContent" class='p-6'></div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        let modalEditState = {};
        function showGradeDetailsModal(student) {
            const modal = document.getElementById('gradeDetailModal');
            const content = document.getElementById('gradeDetailContent');
            let html = `<div class='mb-4'>
                <div class='font-bold text-indigo-700 text-lg mb-1 flex items-center gap-2'><svg class='w-5 h-5 text-indigo-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7'></path></svg>${student.student_name}</div>
                <div class='text-xs text-indigo-400 mb-2'>Kelas ${student.class_name} • Semester ${student.semester}</div>
            </div>`;
            html += '<table class="w-full text-xs mb-2 rounded overflow-hidden"><thead><tr class="bg-blue-100 text-indigo-700"><th class="text-left py-2 px-2">Mapel</th><th>Nilai</th><th class="text-center">Aksi</th></tr></thead><tbody>';
            modalEditState = {};
            student.grades.forEach((g, idx) => {
                modalEditState[idx] = {edit: false, value: g.grade};
                html += `<tr class='border-b border-blue-50 hover:bg-blue-50 transition-all'>
                    <td class='py-2 px-2'>${g.subject}</td>
                    <td class='py-2 px-2'>
                        <input type='number' min='0' max='100' value='${g.grade}' id='edit-grade-${idx}' class='border rounded px-2 w-16 text-center bg-gray-100 focus:bg-white focus:ring-2 focus:ring-blue-300 transition-all' disabled />
                    </td>
                    <td class='py-2 px-2 text-center'>
                        <button id='edit-btn-${idx}' onclick='startEditGrade(${JSON.stringify({student_id: student.student_id, subject_id: g.subject_id, semester: g.semester, academic_year: g.academic_year, idx})})' class='text-blue-600 hover:bg-blue-100 rounded px-2 py-1 text-xs font-semibold'>Edit</button>
                        <button id='save-btn-${idx}' onclick='saveEditGrade(${JSON.stringify({student_id: student.student_id, subject_id: g.subject_id, semester: g.semester, academic_year: g.academic_year, idx})})' class='text-green-600 bg-green-50 border border-green-200 rounded px-2 py-1 text-xs font-semibold ml-1 hidden'>Simpan</button>
                        <button onclick='deleteGrade(${JSON.stringify({student_id: student.student_id, subject_id: g.subject_id, semester: g.semester, academic_year: g.academic_year})})' class='text-red-500 hover:bg-red-50 rounded px-2 py-1 text-xs font-semibold ml-1'>Hapus</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            content.innerHTML = html;
            modal.classList.remove('hidden');
        }
        function closeGradeDetailModal() {
            document.getElementById('gradeDetailModal').classList.add('hidden');
        }
        // Edit mode: enable input and show save button
        function startEditGrade(data) {
            document.getElementById('edit-grade-' + data.idx).disabled = false;
            document.getElementById('edit-grade-' + data.idx).focus();
            document.getElementById('edit-btn-' + data.idx).classList.add('hidden');
            document.getElementById('save-btn-' + data.idx).classList.remove('hidden');
        }
        // Save edited grade
        function saveEditGrade(data) {
            const newGrade = document.getElementById('edit-grade-' + data.idx).value;
            if (newGrade === '' || isNaN(newGrade) || newGrade < 0 || newGrade > 100) {
                alert('Nilai harus 0-100');
                return;
            }
            fetch('grades.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax=1&action=edit_grade&student_id=${data.student_id}&subject_id=${data.subject_id}&semester=${data.semester}&academic_year=${data.academic_year}&grade=${newGrade}`
            })
            .then(r=>r.json()).then(res=>{
                alert(res.message);
                if(res.success){
                    // Update value and disable input again
                    document.getElementById('edit-grade-' + data.idx).disabled = true;
                    document.getElementById('save-btn-' + data.idx).classList.add('hidden');
                    document.getElementById('edit-btn-' + data.idx).classList.remove('hidden');
                    loadResults();
                    // Optionally, update value in modal without closing
                }
            });
        }
        // AJAX Delete Grade
        function deleteGrade(data) {
            if (!confirm('Yakin hapus nilai ini?')) return;
            fetch('grades.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ajax=1&action=delete_grade&student_id=${data.student_id}&subject_id=${data.subject_id}&semester=${data.semester}&academic_year=${data.academic_year}`
            })
            .then(r=>r.json()).then(res=>{
                alert(res.message);
                if(res.success){ closeGradeDetailModal(); loadResults(); }
            });
        }

        function addFilterParams(link) {
            const classFilter = document.getElementById('filterClass').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            
            let url = link.href;
            
            if (classFilter) url += (url.includes('?') ? '&' : '?') + 'class_id=' + encodeURIComponent(classFilter);
            if (semesterFilter) url += (url.includes('?') ? '&' : '?') + 'semester=' + encodeURIComponent(semesterFilter);
            
            link.href = url;
            return true;
        }
    </script>
</body>
</html>