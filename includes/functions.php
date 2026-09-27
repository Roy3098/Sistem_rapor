<?php
define('INCLUDED_FROM_INIT', true);
require_once 'config/database.php';
require_once 'session.php';

// User functions
function registerUser($username, $password, $full_name) {
    $db = getDbConnection();
    
    // Check if username exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Username sudah digunakan!'];
    }
    
    // Insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$username, $hashed_password, $full_name])) {
        return ['success' => true, 'message' => 'Akun berhasil dibuat!'];
    } else {
        return ['success' => false, 'message' => 'Gagal membuat akun!'];
    }
}

function loginUser($username, $password) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        setUserSession($user);
        return ['success' => true, 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Username atau password salah!'];
    }
}

function resetPassword($username, $new_password) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Username tidak ditemukan!'];
    }
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    
    if ($stmt->execute([$hashed_password, $username])) {
        return ['success' => true, 'message' => 'Password berhasil direset!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mereset password!'];
    }
}

// Class functions
function getClasses() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT * FROM classes ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addClass($name, $teacher = 'Belum ditentukan') {
    $db = getDbConnection();
    
    // Check if class exists
    $stmt = $db->prepare("SELECT id FROM classes WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Kelas sudah ada!'];
    }
    
    $stmt = $db->prepare("INSERT INTO classes (name, teacher) VALUES (?, ?)");
    if ($stmt->execute([$name, $teacher])) {
        return ['success' => true, 'message' => 'Kelas berhasil ditambahkan!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menambahkan kelas!'];
    }
}

function updateClass($id, $name, $teacher) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE classes SET name = ?, teacher = ? WHERE id = ?");
    if ($stmt->execute([$name, $teacher, $id])) {
        return ['success' => true, 'message' => 'Kelas berhasil diubah!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengubah kelas!'];
    }
}

function deleteClass($id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("DELETE FROM classes WHERE id = ?");
    if ($stmt->execute([$id])) {
        return ['success' => true, 'message' => 'Kelas berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus kelas!'];
    }
}

// Subject functions
function getSubjects() {
    $db = getDbConnection();
    $stmt = $db->query("SELECT * FROM subjects ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addSubject($name) {
    $db = getDbConnection();
    
    // Check if subject exists
    $stmt = $db->prepare("SELECT id FROM subjects WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Mata pelajaran sudah ada!'];
    }
    
    $stmt = $db->prepare("INSERT INTO subjects (name) VALUES (?)");
    if ($stmt->execute([$name])) {
        return ['success' => true, 'message' => 'Mata pelajaran berhasil ditambahkan!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menambahkan mata pelajaran!'];
    }
}

function updateSubject($id, $name) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE subjects SET name = ? WHERE id = ?");
    if ($stmt->execute([$name, $id])) {
        return ['success' => true, 'message' => 'Mata pelajaran berhasil diubah!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengubah mata pelajaran!'];
    }
}

function deleteSubject($id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
    if ($stmt->execute([$id])) {
        return ['success' => true, 'message' => 'Mata pelajaran berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus mata pelajaran!'];
    }
}

// Student functions
function getStudents($class_id = null) {
    $db = getDbConnection();
    
    if ($class_id) {
        $stmt = $db->prepare("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id WHERE s.class_id = ? ORDER BY s.name");
        $stmt->execute([$class_id]);
    } else {
        $stmt = $db->query("SELECT s.*, c.name as class_name FROM students s JOIN classes c ON s.class_id = c.id ORDER BY c.name, s.name");
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addStudent($student_id, $name, $class_id, $birth_place = null, $birth_date = null, $guardian = null) {
    $db = getDbConnection();
    
    // Check if student exists in the same class
    $stmt = $db->prepare("SELECT id FROM students WHERE name = ? AND class_id = ?");
    $stmt->execute([$name, $class_id]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'Siswa sudah ada di kelas ini!'];
    }
    
    // Check if student_id is unique
    if ($student_id) {
        $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'NIS sudah digunakan!'];
        }
    }
    
    $stmt = $db->prepare("INSERT INTO students (student_id, name, class_id, birth_place, birth_date, guardian) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$student_id, $name, $class_id, $birth_place, $birth_date, $guardian])) {
        return ['success' => true, 'message' => 'Siswa berhasil ditambahkan!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menambahkan siswa!'];
    }
}

function updateStudent($id, $student_id, $name, $class_id, $birth_place, $birth_date, $guardian) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE students SET student_id = ?, name = ?, class_id = ?, birth_place = ?, birth_date = ?, guardian = ? WHERE id = ?");
    if ($stmt->execute([$student_id, $name, $class_id, $birth_place, $birth_date, $guardian, $id])) {
        return ['success' => true, 'message' => 'Data siswa berhasil diubah!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengubah data siswa!'];
    }
}

function deleteStudent($id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
    if ($stmt->execute([$id])) {
        return ['success' => true, 'message' => 'Siswa berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus siswa!'];
    }
}

// Grade functions
function saveGrade($student_id, $subject_id, $grade, $semester, $academic_year = null) {
    $db = getDbConnection();
    
    if (!$academic_year) {
        $academic_year = date('Y') . '/' . (date('Y') + 1);
    }
    
    $stmt = $db->prepare("
        INSERT INTO grades (student_id, subject_id, grade, semester, academic_year) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE grade = VALUES(grade), updated_at = CURRENT_TIMESTAMP
    ");
    
    if ($stmt->execute([$student_id, $subject_id, $grade, $semester, $academic_year])) {
        return ['success' => true, 'message' => 'Nilai berhasil disimpan!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menyimpan nilai!'];
    }
}

function getGrades($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    // Get regular grades
    $sql = "
        SELECT g.*, s.name as student_name, s.student_id, sub.name as subject_name, c.name as class_name, s.class_id, g.subject_id,
               'regular' as grade_type, g.grade as display_grade, '' as tahfidz_info
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        JOIN subjects sub ON g.subject_id = sub.id 
        JOIN classes c ON s.class_id = c.id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($semester) {
        $conditions[] = "g.semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $regularGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tahfidz grades - split into two separate subjects
    $tahfidzSql = "
        SELECT tg.id, s.id as student_id, s.name as student_name, s.student_id, 'Tahfidz' as subject_name, 
               c.name as class_name, s.class_id, 0 as subject_id, 'tahfidz' as grade_type,
               tg.memorization_quality as grade, tg.memorization_quality as display_grade, tg.semester, tg.notes as tahfidz_info,
               tg.memorization_quality as tahfidz_score, tg.tajweed_score, tg.created_at, tg.updated_at,
               '' as academic_year, 'tahfidz' as tahfidz_subject_type
        FROM tahfidz_grades tg 
        JOIN students s ON (tg.student_id = s.student_id OR tg.student_name = s.name)
        JOIN classes c ON s.class_id = c.id
    ";
    
    $tajwidSql = "
        SELECT tg.id, s.id as student_id, s.name as student_name, s.student_id, 'Tajwid' as subject_name, 
               c.name as class_name, s.class_id, 0 as subject_id, 'tajwid' as grade_type,
               tg.tajweed_score as grade, tg.tajweed_score as display_grade, tg.semester, tg.notes as tahfidz_info,
               tg.memorization_quality as tahfidz_score, tg.tajweed_score, tg.created_at, tg.updated_at,
               '' as academic_year, 'tajwid' as tahfidz_subject_type
        FROM tahfidz_grades tg 
        JOIN students s ON (tg.student_id = s.student_id OR tg.student_name = s.name)
        JOIN classes c ON s.class_id = c.id
    ";
    
    $tahfidzParams = [];
    $tajwidParams = [];
    $tahfidzConditions = [];
    $tajwidConditions = [];
    
    if ($class_id) {
        $tahfidzConditions[] = "s.class_id = ?";
        $tahfidzParams[] = $class_id;
        $tajwidConditions[] = "s.class_id = ?";
        $tajwidParams[] = $class_id;
    }
    
    if ($semester) {
        $tahfidzConditions[] = "tg.semester = ?";
        $tahfidzParams[] = $semester;
        $tajwidConditions[] = "tg.semester = ?";
        $tajwidParams[] = $semester;
    }
    
    if (!empty($tahfidzConditions)) {
        $tahfidzSql .= " WHERE " . implode(" AND ", $tahfidzConditions);
        $tajwidSql .= " WHERE " . implode(" AND ", $tajwidConditions);
    }
    
    $stmt = $db->prepare($tahfidzSql);
    $stmt->execute($tahfidzParams);
    $tahfidzGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare($tajwidSql);
    $stmt->execute($tajwidParams);
    $tajwidGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine all arrays
    $allGrades = array_merge($regularGrades, $tahfidzGrades, $tajwidGrades);
    
    // Sort the combined array
    usort($allGrades, function($a, $b) {
        // First by class name
        $classCompare = strcmp($a['class_name'], $b['class_name']);
        if ($classCompare !== 0) return $classCompare;
        
        // Then by student name
        $studentCompare = strcmp($a['student_name'], $b['student_name']);
        if ($studentCompare !== 0) return $studentCompare;
        
        // Then by subject name
        return strcmp($a['subject_name'], $b['subject_name']);
    });
    
    return $allGrades;
}

// Update grade function
function updateGrade($grade_id, $grade) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("UPDATE grades SET grade = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    
    if ($stmt->execute([$grade, $grade_id])) {
        return ['success' => true, 'message' => 'Nilai berhasil diperbarui!'];
    } else {
        return ['success' => false, 'message' => 'Gagal memperbarui nilai!'];
    }
}

// Delete grade function
function deleteGrade($grade_id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("DELETE FROM grades WHERE id = ?");
    
    if ($stmt->execute([$grade_id])) {
        return ['success' => true, 'message' => 'Nilai berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus nilai!'];
    }
}

// Statistics functions
function getStudentStatistics($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    // Total students
    if ($class_id) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
        $stmt->execute([$class_id]);
    } else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM students");
    }
    $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total grades
    $gradeConditions = [];
    $gradeParams = [];
    
    if ($class_id) {
        $gradeConditions[] = "s.class_id = ?";
        $gradeParams[] = $class_id;
    }
    
    if ($semester) {
        $gradeConditions[] = "g.semester = ?";
        $gradeParams[] = $semester;
    }
    
    $gradeSql = "SELECT COUNT(*) as total FROM grades g JOIN students s ON g.student_id = s.id";
    if (!empty($gradeConditions)) {
        $gradeSql .= " WHERE " . implode(" AND ", $gradeConditions);
    }
    
    $stmt = $db->prepare($gradeSql);
    $stmt->execute($gradeParams);
    $totalGrades = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return [
        'total_students' => $totalStudents,
        'total_grades' => $totalGrades
    ];
}

// Get top grades by class
function getTopGradesByClass($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    $sql = "
        SELECT 
            c.name as class_name,
            s.name as student_name,
            ROUND(AVG(g.grade), 1) as average,
            COUNT(g.id) as subject_count
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        JOIN classes c ON s.class_id = c.id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($semester) {
        $conditions[] = "g.semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY s.class_id, s.id, s.name, c.name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by class and find top student in each class
    $topByClass = [];
    foreach ($results as $result) {
        $className = $result['class_name'];
        if (!isset($topByClass[$className]) || $result['average'] > $topByClass[$className]['average']) {
            $topByClass[$className] = $result;
        }
    }
    
    return array_values($topByClass);
}

// Get grade distribution
function getGradeDistribution($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    $sql = "SELECT g.grade FROM grades g JOIN students s ON g.student_id = s.id";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($semester) {
        $conditions[] = "g.semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $excellent = 0;
    $good = 0;
    $needsImprovement = 0;
    
    foreach ($grades as $grade) {
        if ($grade >= 80) {
            $excellent++;
        } elseif ($grade >= 60) {
            $good++;
        } else {
            $needsImprovement++;
        }
    }
    
    return [
        'excellent' => $excellent,
        'good' => $good,
        'needs_improvement' => $needsImprovement
    ];
}

// Get subject averages
function getSubjectAverages($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    $sql = "
        SELECT 
            sub.name as subject_name,
            ROUND(AVG(g.grade), 1) as average,
            COUNT(g.id) as grade_count
        FROM grades g 
        JOIN subjects sub ON g.subject_id = sub.id
        JOIN students s ON g.student_id = s.id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($semester) {
        $conditions[] = "g.semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY sub.id, sub.name ORDER BY average DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update question function
function updateQuestion($question_id, $question_text, $option_a = null, $option_b = null, $option_c = null, $option_d = null, $correct_answer = null) {
    $db = getDbConnection();
    
    // Get question to determine type
    $stmt = $db->prepare("SELECT question_type FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$question) {
        return ['success' => false, 'message' => 'Soal tidak ditemukan!'];
    }
    
    if ($question['question_type'] === 'essay') {
        $stmt = $db->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
        if ($stmt->execute([$question_text, $question_id])) {
            return ['success' => true, 'message' => 'Soal essay berhasil diperbarui!'];
        } else {
            return ['success' => false, 'message' => 'Gagal memperbarui soal essay!'];
        }
    } else {
        $stmt = $db->prepare("UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? WHERE id = ?");
        if ($stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $question_id])) {
            return ['success' => true, 'message' => 'Soal pilihan ganda berhasil diperbarui!'];
        } else {
            return ['success' => false, 'message' => 'Gagal memperbarui soal pilihan ganda!'];
        }
    }
}

// Get question by ID
function getQuestionById($question_id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Delete question function
function deleteQuestion($question_id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("DELETE FROM questions WHERE id = ?");
    if ($stmt->execute([$question_id])) {
        return ['success' => true, 'message' => 'Soal berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus soal!'];
    }
}

// Get top students overall
function getTopStudents($class_id = null, $semester = null, $limit = 5) {
    $db = getDbConnection();
    
    // Database connection handled by PDO connection string
    
    $sql = "
        SELECT 
            s.name as student_name,
            c.name as class_name,
            ROUND(AVG(g.grade), 1) as average,
            COUNT(g.id) as subject_count
        FROM grades g 
        JOIN students s ON g.student_id = s.id 
        JOIN classes c ON s.class_id = c.id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($semester) {
        $conditions[] = "g.semester = ?";
        $params[] = $semester;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY s.id, s.name, c.name ORDER BY average DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// File upload function
function uploadFile($file, $upload_dir = 'uploads/') {
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = [
        'application/pdf', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png', 
        'image/gif'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan!'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return [
            'success' => true, 
            'filename' => $new_filename,
            'path' => $file_path,
            'original_name' => $file['name']
        ];
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file!'];
    }
}

// Exam functions
function createExam($subject_id, $class_id, $type, $file_data = null, $question_type = null, $question_data = null) {
    $db = getDbConnection();
    $current_user = getCurrentUser();
    $user_id = $current_user ? $current_user['id'] : null;
    
    // Generate title based on subject and class
    $stmt = $db->prepare("SELECT s.name as subject_name, c.name as class_name FROM subjects s, classes c WHERE s.id = ? AND c.id = ?");
    $stmt->execute([$subject_id, $class_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = $info['subject_name'] . ' - Kelas ' . $info['class_name'];
    
    if ($type === 'file' && $file_data) {
        $stmt = $db->prepare("INSERT INTO exams (title, subject_id, class_id, type, file_name, file_path, file_type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $subject_id, $class_id, $type, $file_data['original_name'], $file_data['path'], mime_content_type($file_data['path']), $user_id])) {
            return ['success' => true, 'message' => 'File soal berhasil diunggah!'];
        }
    } elseif ($type === 'questions' && $question_type && $question_data) {
        // Create exam entry
        $stmt = $db->prepare("INSERT INTO exams (title, subject_id, class_id, type, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $subject_id, $class_id, $type, $user_id])) {
            $exam_id = $db->lastInsertId();
            
            // Create question entry
            if ($question_type === 'essay') {
                $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, ?)");
                if ($stmt->execute([$exam_id, $question_data['text'], $question_type])) {
                    return ['success' => true, 'message' => 'Soal essay berhasil dibuat!'];
                }
            } elseif ($question_type === 'multiple_choice') {
                $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$exam_id, $question_data['text'], $question_type, $question_data['option_a'], $question_data['option_b'], $question_data['option_c'], $question_data['option_d'], $question_data['correct_answer']])) {
                    return ['success' => true, 'message' => 'Soal pilihan ganda berhasil dibuat!'];
                }
            }
        }
    }
    
    return ['success' => false, 'message' => 'Gagal membuat soal!'];
}

function createMultipleExams($subject_id, $class_id, $type, $file_data = null, $question_type, $questions) {
    $db = getDbConnection();
    $current_user = getCurrentUser();
    $user_id = $current_user ? $current_user['id'] : null;
    
    // Check if an exam already exists for this subject and class
    $stmt = $db->prepare("SELECT id FROM exams WHERE subject_id = ? AND class_id = ? AND type = ?");
    $stmt->execute([$subject_id, $class_id, $type]);
    $existing_exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_exam) {
        // Use existing exam
        $exam_id = $existing_exam['id'];
    } else {
        // Create new exam entry
        $stmt = $db->prepare("SELECT s.name as subject_name, c.name as class_name FROM subjects s, classes c WHERE s.id = ? AND c.id = ?");
        $stmt->execute([$subject_id, $class_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        $title = $info['subject_name'] . ' - Kelas ' . $info['class_name'];
        
        $stmt = $db->prepare("INSERT INTO exams (title, subject_id, class_id, type, created_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $subject_id, $class_id, $type, $user_id])) {
            $exam_id = $db->lastInsertId();
        } else {
            return ['success' => false, 'message' => 'Gagal membuat soal!'];
        }
    }
    
    // Add questions to the exam
    $question_count = 0;
    foreach ($questions as $question_data) {
        if ($question_type === 'essay') {
            $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type) VALUES (?, ?, ?)");
            if ($stmt->execute([$exam_id, $question_data['text'], $question_type])) {
                $question_count++;
            }
        } elseif ($question_type === 'multiple_choice') {
            $stmt = $db->prepare("INSERT INTO questions (exam_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$exam_id, $question_data['text'], $question_type, $question_data['option_a'], $question_data['option_b'], $question_data['option_c'], $question_data['option_d'], $question_data['correct_answer']])) {
                $question_count++;
            }
        }
    }
    
    if ($question_count > 0) {
        return ['success' => true, 'message' => "Berhasil menambahkan {$question_count} soal!"];
    } else {
        return ['success' => false, 'message' => 'Gagal menambahkan soal!'];
    }
}

function getExams($class_id = null) {
    $db = getDbConnection();
    
    $sql = "
        SELECT e.*, s.name as subject_name, c.name as class_name
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        JOIN classes c ON e.class_id = c.id
    ";
    
    $params = [];
    if ($class_id) {
        $sql .= " WHERE e.class_id = ?";
        $params[] = $class_id;
    }
    
    $sql .= " ORDER BY c.name, s.name, e.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getConsolidatedExams($class_id = null, $type_filter = null) {
    $db = getDbConnection();
    
    // Get all exams with their question counts
    $sql = "
        SELECT
            e.id,
            e.title,
            e.subject_id,
            e.class_id,
            e.type,
            e.file_name,
            e.file_path,
            e.file_type,
            e.created_at,
            s.name as subject_name,
            c.name as class_name,
            q.question_type,
            COUNT(q.id) as question_count
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        JOIN classes c ON e.class_id = c.id
        LEFT JOIN questions q ON e.id = q.exam_id
    ";
    
    $params = [];
    $conditions = [];
    
    if ($class_id) {
        $conditions[] = "e.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($type_filter) {
        $conditions[] = "e.type = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " GROUP BY e.id, q.question_type ORDER BY c.name, s.name, e.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate file-based exams and question-based exams into different entries
    $separated = [];
    
    foreach ($exams as $exam) {
        if ($exam['type'] === 'file') {
            // File-based exam - create separate entry
            $separated[] = [
                'class_id' => $exam['class_id'],
                'class_name' => $exam['class_name'],
                'subject_id' => $exam['subject_id'],
                'subject_name' => $exam['subject_name'],
                'exams' => [
                    'file' => [
                        'id' => $exam['id'],
                        'type' => 'file',
                        'file_name' => $exam['file_name'],
                        'file_path' => $exam['file_path'],
                        'file_type' => $exam['file_type'],
                        'created_at' => $exam['created_at']
                    ]
                ],
                'total_questions' => 0
            ];
        } else {
            // Question-based exam - create separate entry for each class/subject combination
            $key = 'questions_' . $exam['class_id'] . '_' . $exam['subject_id'];
            
            if (!isset($separated[$key])) {
                $separated[$key] = [
                    'class_id' => $exam['class_id'],
                    'class_name' => $exam['class_name'],
                    'subject_id' => $exam['subject_id'],
                    'subject_name' => $exam['subject_name'],
                    'exams' => [
                        'questions' => [
                            'id' => $exam['id'],
                            'type' => 'questions',
                            'question_types' => [],
                            'total_questions' => 0,
                            'created_at' => $exam['created_at']
                        ]
                    ],
                    'total_questions' => 0
                ];
            }
            
            // Add question type and count
            if ($exam['question_type']) {
                $separated[$key]['exams']['questions']['question_types'][$exam['question_type']] = $exam['question_count'];
                $separated[$key]['exams']['questions']['total_questions'] += $exam['question_count'];
                $separated[$key]['total_questions'] += $exam['question_count'];
            }
        }
    }
    
    // Convert to indexed array
    return array_values($separated);
}

function getExamById($id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT e.*, s.name as subject_name, c.name as class_name
        FROM exams e 
        JOIN subjects s ON e.subject_id = s.id 
        JOIN classes c ON e.class_id = c.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exam && $exam['type'] === 'questions') {
        // Get questions for this exam
        $stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
        $stmt->execute([$id]);
        $exam['questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $exam;
}

function deleteExam($id) {
    $db = getDbConnection();
    
    // Get exam info first
    $exam = getExamById($id);
    if (!$exam) {
        return ['success' => false, 'message' => 'Soal tidak ditemukan!'];
    }
    
    // Delete file if it exists
    if ($exam['type'] === 'file' && $exam['file_path'] && file_exists($exam['file_path'])) {
        unlink($exam['file_path']);
    }
    
    // Delete questions first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM questions WHERE exam_id = ?");
    $stmt->execute([$id]);
    
    // Delete exam
    $stmt = $db->prepare("DELETE FROM exams WHERE id = ?");
    if ($stmt->execute([$id])) {
        return ['success' => true, 'message' => 'Soal berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus soal!'];
    }
}

// User profile functions
function updateUserProfile($user_id, $full_name, $profile_picture = null) {
    $db = getDbConnection();
    
    if ($profile_picture) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, profile_picture = ? WHERE id = ?");
        $result = $stmt->execute([$full_name, $profile_picture, $user_id]);
    } else {
        $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $result = $stmt->execute([$full_name, $user_id]);
    }
    
    if ($result) {
        return ['success' => true, 'message' => 'Profil berhasil diperbarui!'];
    } else {
        return ['success' => false, 'message' => 'Gagal memperbarui profil!'];
    }
}

function changeUserPassword($user_id, $old_password, $new_password) {
    $db = getDbConnection();
    
    // Verify old password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($old_password, $user['password'])) {
        return ['success' => false, 'message' => 'Password lama salah!'];
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$hashed_password, $user_id])) {
        return ['success' => true, 'message' => 'Password berhasil diubah!'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengubah password!'];
    }
}

function getUserDetails($user_id) {
    $db = getDbConnection();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Word document generation function
function generateWordDocument($exam) {
    require_once 'vendor/autoload.php';
    
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    
    // Title
    $section->addText($exam['title'], ['bold' => true, 'size' => 16]);
    $section->addText('Mata Pelajaran: ' . $exam['subject_name'], ['size' => 12]);
    $section->addText('Kelas: ' . $exam['class_name'], ['size' => 12]);
    $section->addTextBreak(2);
    
    if ($exam['type'] === 'questions') {
        // Get questions for this exam
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
        $stmt->execute([$exam['id']]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $questionNumber = 1;
        foreach ($questions as $question) {
            $section->addText($questionNumber . '. ' . $question['question_text'], ['size' => 12]);
            
            if ($question['question_type'] === 'multiple_choice') {
                $section->addText('A. ' . $question['option_a'], ['size' => 11]);
                $section->addText('B. ' . $question['option_b'], ['size' => 11]);
                $section->addText('C. ' . $question['option_c'], ['size' => 11]);
                $section->addText('D. ' . $question['option_d'], ['size' => 11]);
                $section->addText('Jawaban: ' . $question['correct_answer'], ['bold' => true, 'size' => 11]);
            }
            
            $section->addTextBreak(2);
            $questionNumber++;
        }
    }
    
    // Generate filename
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam['title']) . '.docx';
    
    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
}


// TXT document generation function
function generateTxtDocument($exam) {
    // Get all questions for the same class and subject as this exam
    $db = getDbConnection();
    $stmt = $db->prepare("
        SELECT q.*
        FROM questions q
        JOIN exams e ON q.exam_id = e.id
        WHERE e.class_id = ? AND e.subject_id = ?
        ORDER BY CASE q.question_type WHEN 'multiple_choice' THEN 1 WHEN 'essay' THEN 2 ELSE 3 END, q.id
    ");
    $stmt->execute([$exam['class_id'], $exam['subject_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate essay and multiple choice questions
    $essay_questions = array_filter($questions, function($q) { return $q['question_type'] === 'essay'; });
    $mc_questions = array_filter($questions, function($q) { return $q['question_type'] === 'multiple_choice'; });
    
    // Create TXT content
    $content = "SOAL UJIAN\n";
    $content .= "====================\n\n";
    $content .= "Mata Pelajaran: " . $exam['subject_name'] . "\n";
    $content .= "Kelas: " . $exam['class_name'] . "\n";
    $content .= "Tanggal Dibuat: " . date('d/m/Y H:i', strtotime($exam['created_at'])) . "\n\n";
    
    // Add multiple choice questions first
    if (!empty($mc_questions)) {
        $content .= "SOAL PILIHAN GANDA\n";
        $content .= "====================\n\n";
        
        foreach ($mc_questions as $index => $question) {
            $questionNum = $index + 1;
            $content .= $questionNum . ". " . $question['question_text'] . "\n";
            $content .= "A. " . $question['option_a'] . "\n";
            $content .= "B. " . $question['option_b'] . "\n";
            $content .= "C. " . $question['option_c'] . "\n";
            $content .= "D. " . $question['option_d'] . "\n";
            $content .= "Jawaban: " . $question['correct_answer'] . "\n\n";
        }
    }
    
    // Add essay questions after multiple choice questions
    if (!empty($essay_questions)) {
        $content .= "SOAL ESSAY\n";
        $content .= "====================\n\n";
        
        foreach ($essay_questions as $index => $question) {
            $questionNum = $index + 1;
            $content .= $questionNum . ". " . $question['question_text'] . "\n\n";
        }
    }
    
    // Generate filename
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exam['title']) . '.txt';
    
    // Set headers for download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Output content
    echo $content;
}

?>