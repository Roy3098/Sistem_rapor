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
                    grade: parseFloat(grade.grade),
                    subject_id: grade.subject_id
                });
            });
            
            let html = '';
            Object.values(groupedGrades).forEach(student => {
                const totalGrade = student.grades.reduce((sum, g) => sum + g.grade, 0);
                const average = (totalGrade / student.grades.length).toFixed(1);
                html += `
                    <div class="bg-white/80 shadow-md rounded-xl p-3 mb-3 border border-blue-100 flex items-center gap-3 hover:shadow-lg transition-all min-h-[80px]">
                        <div class="flex flex-col items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 text-white font-bold text-lg shrink-0">
                            <span>${student.student_name.charAt(0)}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-gray-800 text-sm truncate" title="${student.student_name}">${student.student_name}</span>
                                <span class="text-xs text-gray-500">Kls ${student.class_name} • Smt ${student.semester}</span>
                            </div>
                            <div class="flex flex-wrap gap-1 mb-1">
                                ${student.grades.map(g => `<span class='inline-block bg-blue-50 border border-blue-200 text-blue-700 rounded px-2 py-0.5 text-xs font-medium' title='${g.subject}'>${g.subject}: <span class='font-bold'>${g.grade}</span></span>`).join('')}
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500">Total: ${student.grades.length} mapel</span>
                                <span class="text-xs text-gray-500">Rata-rata: <span class="font-bold text-blue-600">${average}</span></span>
                            </div>
                        </div>
                        <button onclick="showGradeDetails('${student.student_name}', '${student.class_name}', '${student.semester}', ${JSON.stringify(student.grades).replace(/"/g, '&quot;')}, ${student.student_id}, '${student.class_name}')" class="ml-2 text-blue-600 hover:text-blue-800 text-xs font-medium underline shrink-0">Detail</button>
                    </div>
                `;
            });
            
            container.innerHTML = html;
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

    <!-- MODAL DETAIL NILAI -->
    <div id="gradeDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative animate-fadeIn">
            <button onclick="closeGradeDetailModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <h2 class="text-lg font-bold text-gray-800 mb-2" id="modalStudentName">Detail Nilai</h2>
            <div class="text-sm text-gray-600 mb-2" id="modalStudentInfo"></div>
            <div id="modalGradesList" class="mb-4"></div>
            <div class="flex justify-end">
                <button onclick="closeGradeDetailModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold">Tutup</button>
            </div>
        </div>
    </div>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn { animation: fadeIn 0.3s; }
    </style>
    <script>
        function showGradeDetails(studentName, className, semester, grades, studentId, classId) {
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalStudentInfo').textContent = `Kelas ${className} • Semester ${semester}`;
            let html = '<table class="w-full text-sm"><thead><tr><th class="text-left py-1">Mata Pelajaran</th><th class="text-right py-1">Nilai</th><th></th></tr></thead><tbody>';
            grades.forEach((g, idx) => {
                html += `<tr>
                    <td class='py-1'>${g.subject}</td>
                    <td class='py-1 text-right font-semibold' id='gradeValue${idx}'>${g.grade}</td>
                    <td class='py-1 text-right'>
                        <button class='text-blue-600 hover:underline text-xs font-medium' onclick='showEditGrade(${idx}, ${studentId}, "${g.subject}", ${g.grade}, ${g.subject_id}, ${semester})'>Edit</button>
                    </td>
                </tr>
                <tr id='editRow${idx}' style='display:none;'>
                    <td colspan='3'>
                        <form onsubmit='submitEditGrade(event, ${idx}, ${studentId}, "${g.subject}", ${g.grade}, ${g.subject_id}, ${semester})' class='flex items-center gap-2'>
                            <input type='number' min='0' max='90' id='editInput${idx}' value='${g.grade}' class='border px-2 py-1 rounded w-20'>
                            <button type='submit' class='bg-green-500 text-white px-3 py-1 rounded text-xs font-semibold'>Simpan</button>
                            <button type='button' onclick='hideEditGrade(${idx})' class='bg-gray-200 text-gray-700 px-3 py-1 rounded text-xs font-semibold'>Batal</button>
                        </form>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('modalGradesList').innerHTML = html;
            document.getElementById('gradeDetailModal').classList.remove('hidden');
        }
        function showEditGrade(idx, studentId, subject, grade, subjectId, semester) {
            document.getElementById('editRow'+idx).style.display = '';
        }
        function hideEditGrade(idx) {
            document.getElementById('editRow'+idx).style.display = 'none';
        }
        function submitEditGrade(e, idx, studentId, subject, oldGrade, subjectId, semester) {
            e.preventDefault();
            const newGrade = document.getElementById('editInput'+idx).value;
            if (newGrade > 90) {
                alert('Nilai maksimal adalah 90!');
                return;
            }
            // Kirim update ke backend
            fetch('update_grade.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `student_id=${studentId}&subject_id=${subjectId}&semester=${semester}&grade=${newGrade}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('gradeValue'+idx).textContent = newGrade;
                    hideEditGrade(idx);
                } else {
                    alert('Gagal update nilai: ' + data.message);
                }
            });
        }
        function closeGradeDetailModal() {
            document.getElementById('gradeDetailModal').classList.add('hidden');
        }
        // Optional: close modal on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeGradeDetailModal();
        });
    </script>
</body>
</html>