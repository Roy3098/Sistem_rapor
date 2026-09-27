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

$message = '';
$error = '';

// Get data
try {
    $classes = getClasses();
    
    // Get surahs for dropdown  
    $db = getDbConnection();
    $stmt = $db->query("SELECT * FROM surahs ORDER BY number");
    $surahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    header('Location: install.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

                
            case 'save_grades_bulk':
                try {
                    $db->beginTransaction();
                    
                    $stmt = $db->prepare("
                        INSERT INTO tahfidz_grades (
                            student_id, student_name, class_id, semester, surah_name, 
                            ayah_range, memorization_quality, fluency_score, tajweed_score, 
                            notes, test_date
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($_POST['students'] as $student) {
                        if (!empty($student['tahfidz_score']) && 
                            !empty($student['tajweed_score'])) {
                            
                            $stmt->execute([
                                $student['student_id'],
                                $student['student_name'],
                                $_POST['class_id'],
                                $_POST['semester'],
                                '', // surah_name - kosong
                                '', // ayah_range - kosong
                                $student['tahfidz_score'], // memorization_quality (nilai tahfidz)
                                1, // fluency_score - set ke 1 untuk memenuhi constraint
                                $student['tajweed_score'], // tajweed_score (nilai tajwid)
                                $student['notes'] ?? '', // catatan hafalan
                                date('Y-m-d') // test_date - tanggal hari ini
                            ]);
                        }
                    }
                    
                    $db->commit();
                    $message = 'Nilai tahfidz berhasil disimpan!';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Gagal menyimpan nilai: ' . $e->getMessage();
                }
                break;
                
            case 'edit_grade':
                try {
                    $stmt = $db->prepare("
                        UPDATE tahfidz_grades 
                        SET memorization_quality = ?, tajweed_score = ?, notes = ?, test_date = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['tahfidz_score'],
                        $_POST['tajweed_score'],
                        $_POST['notes'],
                        $_POST['test_date'],
                        $_POST['grade_id']
                    ]);
                    $message = 'Nilai tahfidz berhasil diperbarui!';
                } catch (Exception $e) {
                    $error = 'Gagal memperbarui nilai: ' . $e->getMessage();
                }
                break;
                
            case 'delete_grade':
                try {
                    $stmt = $db->prepare("DELETE FROM tahfidz_grades WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Nilai tahfidz berhasil dihapus!';
                } catch (Exception $e) {
                    $error = 'Gagal menghapus nilai: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_students':
                $classId = $_GET['class_id'] ?? '';
                if ($classId) {
                    $stmt = $db->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY name");
                    $stmt->execute([$classId]);
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'students' => $students]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Class ID required']);
                }
                exit();
                
            case 'get_grades':
                $classFilter = $_GET['class_id'] ?? '';
                $semesterFilter = $_GET['semester'] ?? '';
                
                $query = "
                    SELECT tg.*, c.name as class_name 
                    FROM tahfidz_grades tg 
                    LEFT JOIN classes c ON tg.class_id = c.id 
                    WHERE 1=1
                ";
                $params = [];
                
                if ($classFilter) {
                    $query .= " AND tg.class_id = ?";
                    $params[] = $classFilter;
                }
                
                if ($semesterFilter) {
                    $query .= " AND tg.semester = ?";
                    $params[] = $semesterFilter;
                }
                
                $query .= " ORDER BY c.name, tg.student_name, tg.test_date DESC";
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'grades' => $grades]);
                exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Tahfidz - Baiturrahman Web</title>
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
        
        .tab-button.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
                        <h1 class="text-lg font-bold text-gray-800">Nilai Tahfidz</h1>
                        <p class="text-xs text-gray-600">Hafalan Al-Quran</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tab Navigation -->
        <div class="bg-white rounded-2xl shadow-lg mb-4 fade-in">
            <div class="flex p-2">
                <button onclick="switchTab('input')" id="inputTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm active">
                    📝 Input Nilai
                </button>
                <button onclick="switchTab('view')" id="viewTab" class="tab-button flex-1 py-3 px-4 rounded-xl font-semibold text-sm text-gray-600">
                    📊 Lihat Nilai
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-20">
            <!-- Messages -->
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

            <!-- Input Tab Content -->
            <div id="inputContent" class="tab-content">
                <!-- Input Nilai -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Input Nilai</h3>
                
                <form method="POST" id="bulkForm" class="space-y-4">
                    <input type="hidden" name="action" value="save_grades_bulk">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Kelas</label>
                            <select name="class_id" id="bulkClassSelect" onchange="loadStudentsForBulk()" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Semester</label>
                            <select name="semester" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="">Pilih Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="studentsGradeContainer" style="display: none;">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Input Nilai Siswa</h4>
                        <p class="text-xs text-gray-600 mb-3">💡 Tuliskan informasi hafalan pada kolom "Informasi Hafalan" (contoh: Juz 1-2, Al-Fatihah sampai Al-Baqarah ayat 50)</p>
                        <div id="studentsList" class="space-y-3">
                            <!-- Students will be loaded here -->
                        </div>
                        
                            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all mt-4">
                                💾 Simpan Nilai
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Tab Content -->
            <div id="viewContent" class="tab-content" style="display: none;">
                <!-- Filter -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Nilai</h3>
                <div class="grid grid-cols-1 gap-3">
                    <select id="filterClass" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">Kelas <?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterSemester" onchange="filterResults()" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Semua Semester</option>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                    </div>
                </div>

                <!-- Results Container -->
                <div id="resultsContainer">
                    <!-- Akan diisi oleh JavaScript -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="text-center py-8" style="display: none;">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">📖</span>
                    </div>
                    <p class="text-gray-500">Belum ada data nilai tahfidz yang tersimpan</p>
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
                <a href="grades.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 4h.01M9 12h.01M9 16h.01M13 12h6m-3-3v6"></path>
                    </svg>
                    <span class="text-xs font-medium">Nilai</span>
                </a>
                <a href="tahfidz.php" class="flex flex-col items-center py-2 px-3 text-green-600 bg-green-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="text-xs font-medium">Tahfidz</span>
                </a>
                <a href="manage-data.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 515.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 919.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
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
        // Tab switching
        function switchTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(tabBtn => {
                tabBtn.classList.remove('active');
                tabBtn.classList.add('text-gray-600');
            });
            
            // Show selected tab content
            document.getElementById(tab + 'Content').style.display = 'block';
            
            // Add active class to selected tab
            const activeTab = document.getElementById(tab + 'Tab');
            activeTab.classList.add('active');
            activeTab.classList.remove('text-gray-600');
            
            // Load results if switching to view tab
            if (tab === 'view') {
                filterResults();
            }
        }

        // Load students for bulk input
        function loadStudentsForBulk() {
            const classId = document.getElementById('bulkClassSelect').value;
            if (!classId) {
                document.getElementById('studentsGradeContainer').style.display = 'none';
                return;
            }
            
            fetch(`?ajax=1&action=get_students&class_id=${classId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudentsForBulk(data.students);
                        document.getElementById('studentsGradeContainer').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displayStudentsForBulk(students) {
            const container = document.getElementById('studentsList');
            let html = '';
            
            students.forEach((student, index) => {
                html += `
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium text-gray-800 text-sm">${student.name}</h4>
                            <span class="text-xs text-gray-500">NIS: ${student.student_id || 'Belum diisi'}</span>
                        </div>
                        <input type="hidden" name="students[${index}][student_id]" value="${student.student_id || ''}">
                        <input type="hidden" name="students[${index}][student_name]" value="${student.name}">
                        
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Informasi Hafalan</label>
                            <input type="text" name="students[${index}][notes]" placeholder="Contoh: Juz 1-2, Al-Fatihah sampai Al-Baqarah ayat 50" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-green-500 mb-2">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Nilai Tahfidz (Max: 90)</label>
                                <input type="number" name="students[${index}][tahfidz_score]" min="1" max="90" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Nilai Tajwid (Max: 90)</label>
                                <input type="number" name="students[${index}][tajweed_score]" min="1" max="90" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-green-500">
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Filter results
        function filterResults() {
            const classFilter = document.getElementById('filterClass').value;
            const semesterFilter = document.getElementById('filterSemester').value;
            
            const params = new URLSearchParams({
                ajax: '1',
                action: 'get_grades'
            });
            
            if (classFilter) params.append('class_id', classFilter);
            if (semesterFilter) params.append('semester', semesterFilter);
            
            fetch(`?${params.toString()}`)
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
            
            // Group by class for display
            const classByName = {};
            grades.forEach(grade => {
                const classKey = `${grade.class_name}_${grade.semester}`;
                if (!classByName[classKey]) {
                    classByName[classKey] = {
                        class_name: grade.class_name,
                        semester: grade.semester,
                        grades: []
                    };
                }
                classByName[classKey].grades.push(grade);
            });
            
            let html = '<div class="space-y-3">';
            let currentClass = '';
            
            // Sort class groups
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
                    const classAverages = classGroup.grades.map(grade => parseFloat(grade.total_score));
                    const classAverage = classAverages.length > 0 ? 
                        (classAverages.reduce((sum, avg) => sum + avg, 0) / classAverages.length).toFixed(1) : 0;
                    
                    html += `
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-3 rounded-lg font-semibold text-sm">
                            <div class="flex justify-between items-center">
                                <div>
                                    📖 Kelas ${classGroup.class_name} - Semester ${classGroup.semester}
                                </div>
                                <div class="text-xs font-normal">
                                    ${classGroup.grades.length} nilai • Rata-rata: ${classAverage}
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 space-y-2">
                    `;
                }
                
                // Sort by student name and test date
                classGroup.grades.sort((a, b) => {
                    if (a.student_name !== b.student_name) {
                        return a.student_name.localeCompare(b.student_name);
                    }
                    return new Date(b.test_date) - new Date(a.test_date);
                });
                
                classGroup.grades.forEach(grade => {
                    const gradeColor = getGradeColor(grade.grade_letter);
                    
                    html += `
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-3 border border-green-100">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold text-gray-800 text-sm">${grade.student_name}</h4>
                                    <p class="text-xs text-gray-600">NIS: ${grade.student_id || 'Belum diisi'}</p>
                                    ${grade.notes ? `<p class="text-xs text-green-600 font-medium">📖 ${grade.notes}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold ${gradeColor}">${grade.grade_letter}</div>
                                    <div class="text-xs text-gray-500">${grade.total_score}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div class="text-center">
                                    <div class="text-sm font-medium text-green-600">${grade.memorization_quality}</div>
                                    <div class="text-xs text-gray-500">Nilai Tahfidz</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-medium text-purple-600">${grade.tajweed_score}</div>
                                    <div class="text-xs text-gray-500">Nilai Tajwid</div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-gray-500 mt-2">
                                <button onclick="showTahfidzDetail('${grade.student_name}', '${grade.class_name}', '${grade.semester}', '${grade.memorization_quality}', '${grade.tajweed_score}', '${grade.total_score}', '${grade.notes}', '${grade.test_date}', '${grade.id}')" 
                                        class="text-blue-600 hover:text-blue-800 font-medium underline">
                                    📊 Lihat Detail
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus nilai ini?')">
                                    <input type="hidden" name="action" value="delete_grade">
                                    <input type="hidden" name="id" value="${grade.id}">
                                    <button type="submit" class="text-red-500 hover:text-red-700">🗑️ Hapus</button>
                                </form>
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

        function getGradeColor(grade) {
            switch (grade) {
                case 'A': return 'text-green-600';   // MUMTAZ (86-90)
                case 'B': return 'text-blue-600';    // JAYYID JIDDAN (81-85)
                case 'C': return 'text-yellow-600';  // JAYYID (71-80)
                case 'D': return 'text-orange-600';  // MAQBUL (60-70)
                case 'E': return 'text-red-600';     // RISIB (<60)
                default: return 'text-gray-600';
            }
        }
        
        function getGradeDescription(average) {
            if (average >= 86) return 'A (MUMTAZ)';
            if (average >= 81) return 'B (JAYYID JIDDAN)';
            if (average >= 71) return 'C (JAYYID)';
            if (average >= 60) return 'D (MAQBUL)';
            return 'E (RISIB)';
        }

        // Global variable untuk menyimpan data yang sedang diedit
        let currentEditData = {};
        
        // Show tahfidz detail modal
        function showTahfidzDetail(studentName, className, semester, tahfidzScore, tajwidScore, totalScore, hafalanInfo, testDate, gradeId) {
            // Simpan data untuk edit
            currentEditData = {
                id: gradeId,
                studentName: studentName,
                className: className,
                semester: semester,
                tahfidzScore: tahfidzScore,
                tajwidScore: tajwidScore,
                hafalanInfo: hafalanInfo,
                testDate: testDate
            };
            
            document.getElementById('modalTahfidzStudentName').textContent = studentName;
            document.getElementById('modalTahfidzClassSemester').textContent = 'Kelas ' + className + ' • Semester ' + semester;
            document.getElementById('modalTahfidzScore').textContent = tahfidzScore || '-';
            document.getElementById('modalTajwidScore').textContent = tajwidScore || '-';
            document.getElementById('modalTotalTahfidzScore').textContent = totalScore || '-';
            document.getElementById('modalHafalanInfo').textContent = hafalanInfo || 'Belum ada informasi hafalan';
            
            document.getElementById('tahfidzDetailModal').classList.remove('hidden');
        }
        
        // Close tahfidz modal
        function closeTahfidzModal() {
            document.getElementById('tahfidzDetailModal').classList.add('hidden');
        }
        
        // Function to edit tahfidz grade
        function editTahfidzGrade() {
            if (!currentEditData.id) {
                alert('Data tidak tersedia untuk diedit');
                return;
            }
            
            // Tutup modal detail
            closeTahfidzModal();
            
            // Populate edit form
            document.getElementById('editGradeId').value = currentEditData.id;
            document.getElementById('editStudentInfo').textContent = 
                currentEditData.studentName + ' • Kelas ' + currentEditData.className + ' • Semester ' + currentEditData.semester;
            document.getElementById('editTahfidzScore').value = currentEditData.tahfidzScore || '';
            document.getElementById('editTajwidScore').value = currentEditData.tajwidScore || '';
            document.getElementById('editHafalanNotes').value = currentEditData.hafalanInfo || '';
            document.getElementById('editTestDate').value = currentEditData.testDate || '';
            
            // Show edit modal
            document.getElementById('editTahfidzModal').classList.remove('hidden');
        }
        
        // Close edit modal
        function closeEditModal() {
            document.getElementById('editTahfidzModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const detailModal = document.getElementById('tahfidzDetailModal');
            const editModal = document.getElementById('editTahfidzModal');
            
            if (event.target == detailModal) {
                closeTahfidzModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set default tab
            switchTab('input');
        });
    </script>
    
    <!-- Grade Detail Modal -->
    <div id="tahfidzDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800" id="modalTahfidzStudentName">Student Name</h3>
                        <p class="text-gray-600 text-sm" id="modalTahfidzClassSemester">Class • Semester</p>
                    </div>
                    <button onclick="closeTahfidzModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-6">
                    <div class="bg-green-50 rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Nilai Tahfidz</div>
                                <div class="text-2xl font-bold text-green-600" id="modalTahfidzScore">0</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Nilai Tajwid</div>
                                <div class="text-2xl font-bold text-purple-600" id="modalTajwidScore">0</div>
                            </div>
                        </div>
                        <div class="text-center border-t border-green-200 pt-3">
                            <div class="text-sm text-gray-600">Total Nilai</div>
                            <div class="text-2xl font-bold text-blue-600" id="modalTotalTahfidzScore">0</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-800 mb-3">📚 Informasi Hafalan</h4>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-gray-700" id="modalHafalanInfo">Belum ada informasi hafalan</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button onclick="editTahfidzGrade()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        ✏️ Edit Nilai
                    </button>
                    <button onclick="closeTahfidzModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editTahfidzModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md">
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="edit_grade">
                <input type="hidden" name="grade_id" id="editGradeId">
                
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Edit Nilai Tahfidz</h3>
                        <p class="text-gray-600 text-sm" id="editStudentInfo">Student • Class • Semester</p>
                    </div>
                    <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Tahfidz (1-90)</label>
                        <input type="number" name="tahfidz_score" id="editTahfidzScore" min="1" max="90" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nilai Tajwid (1-90)</label>
                        <input type="number" name="tajweed_score" id="editTajwidScore" min="1" max="90" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Informasi Hafalan</label>
                        <textarea name="notes" id="editHafalanNotes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Contoh: Juz 1-2, Al-Fatihah sampai Al-Baqarah ayat 50"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Tes</label>
                        <input type="date" name="test_date" id="editTestDate" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        💾 Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>