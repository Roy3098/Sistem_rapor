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

// Get data for filters
try {
    $classes = getClasses();
    $subjects = getSubjects();
} catch(Exception $e) {
    header('Location: install.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_grades') {
        $class_id = !empty($_POST['class_id']) ? $_POST['class_id'] : null;
        $semester = !empty($_POST['semester']) ? $_POST['semester'] : null;
        
        $grades = getGrades($class_id, $semester);
        echo json_encode(['success' => true, 'grades' => $grades]);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Nilai - Baiturrahman Web</title>
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
                        <h1 class="text-lg font-bold text-gray-800">Hasil Nilai</h1>
                        <p class="text-xs text-gray-600">Lihat dan ekspor nilai siswa</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-20">
            <!-- Filter Section -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Filter Data</h3>
                <div class="space-y-3">
                    <select id="filterClass" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">Kelas <?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="filterSemester" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">Semua Semester</option>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="mb-6 space-y-3">
                <button onclick="exportToExcel()" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    📊 Ekspor ke Excel
                </button>
            </div>

            <!-- Results Container -->
            <div id="resultsContainer">
                <!-- Will be populated by JavaScript -->
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
                <a href="manage-subjects.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="text-xs font-medium">Mapel</span>
                </a>
                <a href="manage-classes.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-xs font-medium">Kelas</span>
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
        let allGrades = [];

        // Load results on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadResults();
        });

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
                    allGrades = data.grades;
                    displayResults(allGrades);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function filterResults() {
            const classFilter = document.getElementById('filterClass').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            
            let filteredGrades = allGrades;
            
            if (classFilter) {
                filteredGrades = filteredGrades.filter(grade => grade.class_id == classFilter);
            }
            
            if (semesterFilter) {
                filteredGrades = filteredGrades.filter(grade => grade.semester == semesterFilter);
            }
            
            displayResults(filteredGrades);
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
                    subject_id: grade.subject_id,
                    academic_year: grade.academic_year // pastikan field ini dikirim
                });
            });
            
            let html = '';
            Object.values(groupedGrades).forEach(student => {
                const totalGrade = student.grades.reduce((sum, g) => sum + g.grade, 0);
                const average = (totalGrade / student.grades.length).toFixed(1);
                
                html += `
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 mb-4 border border-blue-100">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-800">${student.student_name}</h3>
                                <p class="text-sm text-gray-600">NIS: ${student.student_id || 'Belum diisi'} • Kelas ${student.class_name} • Semester ${student.semester}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-blue-600">${average}</div>
                                <div class="text-xs text-gray-500">Rata-rata</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-2 mb-3">
                            ${student.grades.map(g => `
                                <div class="text-sm bg-white rounded-lg p-2 flex justify-between">
                                    <span class="text-gray-600">${g.subject}</span>
                                    <span class="font-semibold ${g.grade >= 80 ? 'text-green-600' : g.grade >= 60 ? 'text-yellow-600' : 'text-red-500'}">${g.grade}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div class="text-xs text-gray-500 text-center">
                            ${student.grades.length} mata pelajaran • Total: ${totalGrade}
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function exportToExcel() {
            const classFilter = document.getElementById('filterClass').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            
            let url = 'export.php?action=export_excel';
            if (classFilter) url += '&class_id=' + encodeURIComponent(classFilter);
            if (semesterFilter) url += '&semester=' + encodeURIComponent(semesterFilter);
            
            window.location.href = url;
        }
    </script>
</body>
</html>