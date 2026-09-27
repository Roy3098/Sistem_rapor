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
                            $errors[] = "Nilai untuk siswa ID {$student_id} harus antara 0-90";
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
            } elseif (!is_numeric($grade) || $grade < 0 || $grade > 90) {
                $error = 'Nilai harus berupa angka antara 0-90!';
            } else {
                $result = saveGrade($student_id, $subject_id, $grade, $semester);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } elseif ($_POST['action'] === 'edit_grade') {
            $grade_id = $_POST['grade_id'];
            $grade = $_POST['grade'];
            
            if (empty($grade_id) || empty($grade)) {
                $error = 'Data tidak lengkap!';
            } elseif (!is_numeric($grade) || $grade < 0 || $grade > 90) {
                $error = 'Nilai harus berupa angka antara 0-90!';
            } else {
                $result = updateGrade($grade_id, $grade);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } elseif ($_POST['action'] === 'delete_grade') {
            $grade_id = $_POST['grade_id'];
            
            if (empty($grade_id)) {
                $error = 'ID nilai tidak valid!';
            } else {
                $result = deleteGrade($grade_id);
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
                        <select id="filterClass" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">Kelas <?php echo htmlspecialchars($class['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filterSemester" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm">
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
                            <input type="number" name="grades[${student.id}]" min="0" max="90" 
                                   class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                                   placeholder="0-90">
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
            
            // Group grades by student first
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
                    id: grade.id,
                    subject: grade.subject_name,
                    grade: parseFloat(grade.grade),
                    grade_type: grade.grade_type || 'regular',
                    tahfidz_info: grade.tahfidz_info || '',
                    tahfidz_score: grade.tahfidz_score || null,
                    tajweed_score: grade.tajweed_score || null,
                    tahfidz_subject_type: grade.tahfidz_subject_type || null
                });
            });
            
            // Group by class for display
            const classByName = {};
            Object.values(groupedGrades).forEach(student => {
                const classKey = `${student.class_name}_${student.semester}`;
                if (!classByName[classKey]) {
                    classByName[classKey] = {
                        class_name: student.class_name,
                        semester: student.semester,
                        students: []
                    };
                }
                classByName[classKey].students.push(student);
            });
            
            let html = '<div class="space-y-3">';
            let currentClass = '';
            
            // Sort class groups by class name and semester
            const sortedClassGroups = Object.values(classByName).sort((a, b) => {
                if (a.class_name !== b.class_name) {
                    return a.class_name.localeCompare(b.class_name);
                }
                return a.semester - b.semester;
            });
            
            sortedClassGroups.forEach(classGroup => {
                const classHeader = `${classGroup.class_name} - Semester ${classGroup.semester}`;
                
                if (currentClass !== classHeader) {
                    if (currentClass !== '') {
                        html += '</div>'; // Close previous class group
                    }
                    currentClass = classHeader;
                    
                    // Calculate class statistics
                    const classAverages = classGroup.students.map(student => {
                        const totalGrade = student.grades.reduce((sum, g) => sum + g.grade, 0);
                        return totalGrade / student.grades.length;
                    });
                    const classAverage = classAverages.length > 0 ? 
                        (classAverages.reduce((sum, avg) => sum + avg, 0) / classAverages.length).toFixed(1) : 0;
                    
                    html += `
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-3 rounded-lg font-semibold text-sm">
                            <div class="flex justify-between items-center">
                                <div>
                                    📚 Kelas ${classGroup.class_name} - Semester ${classGroup.semester}
                                </div>
                                <div class="text-xs font-normal">
                                    ${classGroup.students.length} siswa • Rata-rata kelas: ${classAverage}
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 space-y-2">
                    `;
                }
                
                // Sort students alphabetically
                classGroup.students.sort((a, b) => a.student_name.localeCompare(b.student_name));
                
                classGroup.students.forEach(student => {
                    const totalGrade = student.grades.reduce((sum, g) => sum + g.grade, 0);
                    const average = (totalGrade / student.grades.length).toFixed(1);
                    
                    html += `
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 mb-2 border border-blue-100">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm">${student.student_name}</h3>
                                    <p class="text-xs text-gray-600">NIS: ${student.student_id || 'Belum diisi'}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-blue-600">${totalGrade}</div>
                                    <div class="text-xs text-gray-500">Total</div>
                                    <div class="text-sm font-bold text-blue-600">${average}</div>
                                    <div class="text-xs text-gray-500">Rata-rata</div>
                                </div>
                            </div>
                            <div class="mb-1">
                                <button onclick="showGradeDetails('${student.student_name}', '${student.class_name}', '${student.semester}', ${JSON.stringify(student.grades).replace(/"/g, '&quot;')})" 
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium underline">
                                    📊 Lihat Detail (${student.grades.length} mapel)
                                </button>
                            </div>
                        </div>
                    `;
                });
            });
            
            if (currentClass !== '') {
                html += '</div>'; // Close last class group
            }
            html += '</div>';
            
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

        // Function to show grade details in modal
        function showGradeDetails(studentName, className, semester, grades) {
            // Update modal content
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('modalClassSemester').textContent = 'Kelas ' + className + ' • Semester ' + semester;
            
            // Calculate total and average
            const totalGrade = grades.reduce((sum, g) => sum + g.grade, 0);
            const average = (totalGrade / grades.length).toFixed(1);
            document.getElementById('modalTotalGrade').textContent = totalGrade;
            document.getElementById('modalAverageGrade').textContent = average;
            
            // Generate grade list
            const gradeList = document.getElementById('modalGradeList');
            gradeList.innerHTML = '';
            
            grades.forEach(grade => {
                const div = document.createElement('div');
                div.className = 'py-2 border-b border-gray-100';
                
                // Different display for Tahfidz, Tajwid vs regular subjects
                if (grade.grade_type === 'tahfidz' || grade.grade_type === 'tajwid') {
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="text-gray-700">${grade.subject}</span>
                                ${grade.tahfidz_info ? `<span class="text-xs text-gray-500 mt-1"> ${grade.tahfidz_info}</span>` : ''}
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold ${grade.grade >= 86 ? 'text-green-600' : grade.grade >= 81 ? 'text-blue-600' : grade.grade >= 71 ? 'text-yellow-600' : grade.grade >= 60 ? 'text-orange-600' : 'text-red-500'}">${grade.grade}</span>
                                <div class="flex space-x-1">
                                    <button onclick="alert('Edit nilai ${grade.subject.toLowerCase()} melalui halaman Tahfidz')" class="text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">${grade.subject}</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-semibold ${grade.grade >= 86 ? 'text-green-600' : grade.grade >= 81 ? 'text-blue-600' : grade.grade >= 71 ? 'text-yellow-600' : grade.grade >= 60 ? 'text-orange-600' : 'text-red-500'}">${grade.grade}</span>
                                <div class="flex space-x-1">
                                    <button onclick="editGrade(${grade.id}, ${grade.grade}, '${grade.subject}')" class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteGrade(${grade.id}, '${grade.subject}')" class="text-red-600 hover:text-red-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
                gradeList.appendChild(div);
            });
            
            // Show modal
            document.getElementById('gradeDetailModal').classList.remove('hidden');
        }
        
        // Function to edit grade
        function editGrade(gradeId, currentGrade, subjectName) {
            const newGrade = prompt(`Masukkan nilai baru untuk ${subjectName}:`, currentGrade);
            
            if (newGrade !== null) {
                const grade = parseFloat(newGrade);
                
                if (isNaN(grade) || grade < 0 || grade > 90) {
                    alert('Nilai harus berupa angka antara 0-90!');
                    return;
                }
                
                // Submit the form to edit the grade
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'edit_grade';
                form.appendChild(actionInput);
                
                const gradeIdInput = document.createElement('input');
                gradeIdInput.type = 'hidden';
                gradeIdInput.name = 'grade_id';
                gradeIdInput.value = gradeId;
                form.appendChild(gradeIdInput);
                
                const gradeInput = document.createElement('input');
                gradeInput.type = 'hidden';
                gradeInput.name = 'grade';
                gradeInput.value = grade;
                form.appendChild(gradeInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Function to delete grade
        function deleteGrade(gradeId, subjectName) {
            if (confirm(`Apakah Anda yakin ingin menghapus nilai untuk ${subjectName}?`)) {
                // Submit the form to delete the grade
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_grade';
                form.appendChild(actionInput);
                
                const gradeIdInput = document.createElement('input');
                gradeIdInput.type = 'hidden';
                gradeIdInput.name = 'grade_id';
                gradeIdInput.value = gradeId;
                form.appendChild(gradeIdInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Function to close modal
        function closeGradeModal() {
            document.getElementById('gradeDetailModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('gradeDetailModal');
            if (event.target == modal) {
                closeGradeModal();
            }
        }
    </script>
    
    <!-- Grade Detail Modal -->
    <div id="gradeDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800" id="modalStudentName">Student Name</h3>
                        <p class="text-gray-600 text-sm" id="modalClassSemester">Class • Semester</p>
                    </div>
                    <button onclick="closeGradeModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between items-center bg-blue-50 rounded-xl p-4">
                        <div>
                            <div class="text-sm text-gray-600">Total Nilai</div>
                            <div class="text-2xl font-bold text-blue-600" id="modalTotalGrade">0</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Rata-rata</div>
                            <div class="text-2xl font-bold text-blue-600" id="modalAverageGrade">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Detail Nilai per Mata Pelajaran</h4>
                    <div id="modalGradeList" class="space-y-2">
                        <!-- Grade items will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>