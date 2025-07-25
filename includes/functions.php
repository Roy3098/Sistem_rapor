<?php
define('INCLUDED_FROM_INIT', true);
require_once 'config/database.php';

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

function deleteGrade($student_id, $subject_id, $semester, $academic_year = null) {
    $db = getDbConnection();
    if (!$academic_year) {
        $academic_year = date('Y') . '/' . (date('Y') + 1);
    }
    $stmt = $db->prepare("DELETE FROM grades WHERE student_id = ? AND subject_id = ? AND semester = ? AND academic_year = ?");
    if ($stmt->execute([$student_id, $subject_id, $semester, $academic_year])) {
        return ['success' => true, 'message' => 'Nilai berhasil dihapus!'];
    } else {
        return ['success' => false, 'message' => 'Gagal menghapus nilai!'];
    }
}

function getGrades($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
    $sql = "
        SELECT g.*, s.name as student_name, s.student_id, sub.name as subject_name, c.name as class_name, s.class_id
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
    
    $sql .= " ORDER BY c.name, s.name, sub.name, g.semester";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Statistics functions
function getStudentStatistics($class_id = null, $semester = null) {
    $db = getDbConnection();
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
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
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
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
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
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
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
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

// Get top students overall
function getTopStudents($class_id = null, $semester = null, $limit = 5) {
    $db = getDbConnection();
    
    // Use the correct database
    $db->exec("USE baiturrahman_web");
    
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
    
    // Generate title based on subject and class
    $stmt = $db->prepare("SELECT s.name as subject_name, c.name as class_name FROM subjects s, classes c WHERE s.id = ? AND c.id = ?");
    $stmt->execute([$subject_id, $class_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    $title = $info['subject_name'] . ' - Kelas ' . $info['class_name'];
    
    if ($type === 'file' && $file_data) {
        $stmt = $db->prepare("INSERT INTO exams (title, subject_id, class_id, type, file_name, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $subject_id, $class_id, $type, $file_data['original_name'], $file_data['path'], mime_content_type($file_data['path'])])) {
            return ['success' => true, 'message' => 'File soal berhasil diunggah!'];
        }
    } elseif ($type === 'questions' && $question_type && $question_data) {
        // Create exam entry
        $stmt = $db->prepare("INSERT INTO exams (title, subject_id, class_id, type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $subject_id, $class_id, $type])) {
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
?>