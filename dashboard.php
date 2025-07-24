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
    // If initialization fails, redirect to install page
    header('Location: install.php');
    exit();
}

// Get statistics with error handling
try {
    $statistics = getStudentStatistics();
    $classes = getClasses();
    $subjects = getSubjects();
} catch(Exception $e) {
    // If database error, redirect to install
    header('Location: install.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_statistics':
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
                
                $stats = getStudentStatistics($class_id, $semester);
                echo json_encode(['success' => true, 'data' => $stats]);
                exit();
                
            case 'get_top_grades_by_class':
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
                
                $topGrades = getTopGradesByClass($class_id, $semester);
                echo json_encode(['success' => true, 'data' => $topGrades]);
                exit();
                
            case 'get_grade_distribution':
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
                
                $distribution = getGradeDistribution($class_id, $semester);
                echo json_encode(['success' => true, 'data' => $distribution]);
                exit();
                
            case 'get_subject_averages':
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
                
                $averages = getSubjectAverages($class_id, $semester);
                echo json_encode(['success' => true, 'data' => $averages]);
                exit();
                
            case 'get_top_students':
                $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
                $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
                
                $topStudents = getTopStudents($class_id, $semester);
                echo json_encode(['success' => true, 'data' => $topStudents]);
                exit();
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit();
        }
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Baiturrahman Web</title>
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
                    <a href="profile.php" class="flex items-center">
                        <img src="<?php echo $currentUser['profile_picture'] ?: 'https://placehold.co/40x40/cbd5e1/475569?text=👤'; ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover mr-3 border-2 border-blue-400">
                        <div>
                            <h1 class="text-lg font-bold text-gray-800">Baiturrahman Web</h1>
                            <p class="text-xs text-gray-600">Selamat datang, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
                        </div>
                    </a>
                </div>
                <a href="profile.php" class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-all" title="Akun Saya">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="bg-white rounded-2xl shadow-lg p-4 mb-20 fade-in">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Dashboard Statistik</h1>
                <p class="text-gray-600 text-sm">Selamat datang, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</p>
            </div>
            
            <!-- Filter Statistik -->
            <div class="mb-6">
                <div class="flex space-x-2 mb-4">
                    <select id="statsFilterClass" onchange="loadStatistics()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">Kelas <?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="statsFilterSemester" onchange="loadStatistics()" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Semua Semester</option>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>
            </div>

            <!-- Statistik Umum -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-xl border border-blue-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600" id="totalStudents"><?php echo $statistics['total_students']; ?></div>
                        <div class="text-sm text-blue-700">Total Siswa</div>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-xl border border-green-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600" id="totalGrades"><?php echo $statistics['total_grades']; ?></div>
                        <div class="text-sm text-green-700">Total Nilai</div>
                    </div>
                </div>
            </div>

            <!-- Siswa Berprestasi per Kelas -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">🏆 Siswa Berprestasi per Kelas</h3>
                <div id="topGradesContainer" class="space-y-3">
                    <!-- Akan diisi oleh JavaScript -->
                </div>
            </div>

            <!-- Distribusi Nilai -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">📊 Distribusi Nilai</h3>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-xl border border-green-200 text-center">
                        <div class="text-xl font-bold text-green-600" id="excellentCount">0</div>
                        <div class="text-sm text-green-700">Sangat Baik</div>
                        <div class="text-xs text-gray-500">(80-90)</div>
                    </div>
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-xl border border-yellow-200 text-center">
                        <div class="text-xl font-bold text-yellow-600" id="goodCount">0</div>
                        <div class="text-sm text-yellow-700">Baik</div>
                        <div class="text-xs text-gray-500">(60-79)</div>
                    </div>
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 p-4 rounded-xl border border-red-200 text-center">
                        <div class="text-xl font-bold text-red-600" id="needsImprovementCount">0</div>
                        <div class="text-sm text-red-700">Perlu Perbaikan</div>
                        <div class="text-xs text-gray-500">(0-59)</div>
                    </div>
                </div>
            </div>

            <!-- Rata-rata per Mata Pelajaran -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">📚 Rata-rata per Mata Pelajaran</h3>
                <div id="subjectAveragesContainer" class="space-y-2">
                    <!-- Akan diisi oleh JavaScript -->
                </div>
            </div>

            <!-- Juara Umum (Top 5 Siswa) -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">🥇 Juara Umum (Top 5 Siswa)</h3>
                <div id="topStudentsContainer" class="space-y-3">
                    <!-- Akan diisi oleh JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-40">
        <div class="container mx-auto px-4 max-w-md">
            <div class="flex justify-around py-2">
                <a href="dashboard.php" class="flex flex-col items-center py-2 px-3 text-blue-600 bg-blue-50 rounded-lg transition-all">
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
        function loadStatistics() {
            const classId = document.getElementById('statsFilterClass').value;
            const semester = document.getElementById('statsFilterSemester').value;
            
            // Load all statistics
            loadBasicStatistics(classId, semester);
            loadTopGradesByClass(classId, semester);
            loadGradeDistribution(classId, semester);
            loadSubjectAverages(classId, semester);
            loadTopStudents(classId, semester);
        }

        function loadBasicStatistics(classId, semester) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_statistics&class_id=${classId}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalStudents').textContent = data.data.total_students || 0;
                    document.getElementById('totalGrades').textContent = data.data.total_grades || 0;
                }
            })
            .catch(error => console.error('Error loading basic statistics:', error));
        }

        function loadTopGradesByClass(classId, semester) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_top_grades_by_class&class_id=${classId}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('topGradesContainer');
                container.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(classData => {
                        const div = document.createElement('div');
                        div.className = 'bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-xl border border-yellow-200';
                        div.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-gray-800">Kelas ${classData.class_name}</div>
                                    <div class="text-sm text-gray-600">${classData.student_name} • ${classData.subject_count} mata pelajaran</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-yellow-600">${classData.average}</div>
                                    <div class="text-xs text-gray-500">Rata-rata Tertinggi</div>
                                </div>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">Belum ada data nilai</p>';
                }
            })
            .catch(error => console.error('Error loading top grades:', error));
        }

        function loadGradeDistribution(classId, semester) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_grade_distribution&class_id=${classId}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('excellentCount').textContent = data.data.excellent || 0;
                    document.getElementById('goodCount').textContent = data.data.good || 0;
                    document.getElementById('needsImprovementCount').textContent = data.data.needs_improvement || 0;
                }
            })
            .catch(error => console.error('Error loading grade distribution:', error));
        }

        function loadSubjectAverages(classId, semester) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_subject_averages&class_id=${classId}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('subjectAveragesContainer');
                container.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(subject => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                        div.innerHTML = `
                            <div>
                                <span class="font-medium text-gray-800">${subject.subject_name}</span>
                                <span class="text-sm text-gray-600 ml-2">(${subject.grade_count} nilai)</span>
                            </div>
                            <div class="text-right">
                                <span class="font-bold ${subject.average >= 80 ? 'text-green-600' : subject.average >= 60 ? 'text-yellow-600' : 'text-red-500'}">${subject.average}</span>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">Belum ada data nilai</p>';
                }
            })
            .catch(error => console.error('Error loading subject averages:', error));
        }

        function loadTopStudents(classId, semester) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=get_top_students&class_id=${classId}&semester=${semester}`
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('topStudentsContainer');
                container.innerHTML = '';
                if (data.success && data.data.length > 0) {
                    data.data.forEach((student, index) => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200';
                        div.innerHTML = `
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                    ${index + 1}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">${student.student_name}</div>
                                    <div class="text-sm text-gray-600">Kelas ${student.class_name} • ${student.subject_count} mata pelajaran</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold text-green-600">${student.average}</div>
                                <div class="text-xs text-gray-500">Rata-rata</div>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">Belum ada data siswa atau nilai belum lengkap.</p>';
                }
            })
            .catch(error => {
                const container = document.getElementById('topStudentsContainer');
                container.innerHTML = '<p class="text-red-500 text-center py-4">Gagal memuat data juara umum.</p>';
                console.error('Error loading top students:', error);
            });
        }

        // Load statistics on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
        });
    </script>
</body>
</html>