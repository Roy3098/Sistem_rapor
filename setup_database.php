<?php
// Only output if called directly
$direct_call = !defined('INCLUDED_FROM_INIT');
if ($direct_call) {
    require_once 'config/database.php';
    echo "Setting up Baiturrahman Web Database...\n";
} else {
    // Called from initialization, don't output
}

try {
    // Get connection without database selection first
    $database = new Database();
    $db = $database->connect();
    
    // Create database if it doesn't exist
    $db->exec("CREATE DATABASE IF NOT EXISTS baiturrahman_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE baiturrahman_web");
    
    if ($direct_call) echo "Database created/selected successfully.\n";
    
    // Users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    if ($direct_call) echo "Users table created.\n";
    
    // Classes table
    $db->exec("
        CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            teacher VARCHAR(100) DEFAULT 'Belum ditentukan',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    if ($direct_call) echo "Classes table created.\n";
    
    // Subjects table
    $db->exec("
        CREATE TABLE IF NOT EXISTS subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    if ($direct_call) echo "Subjects table created.\n";
    
    // Students table
    $db->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) UNIQUE DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            class_id INT NOT NULL,
            birth_place VARCHAR(50) DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            guardian VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
        )
    ");
    if ($direct_call) echo "Students table created.\n";
    
    // Grades table
    $db->exec("
        CREATE TABLE IF NOT EXISTS grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            grade DECIMAL(5,2) NOT NULL,
            semester ENUM('1', '2') NOT NULL,
            academic_year VARCHAR(9) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_subject_semester (student_id, subject_id, semester, academic_year)
        )
    ");
    if ($direct_call) echo "Grades table created.\n";
    
    // Exams table
    $db->exec("
        CREATE TABLE IF NOT EXISTS exams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            subject_id INT NOT NULL,
            class_id INT NOT NULL,
            type ENUM('file', 'questions') NOT NULL DEFAULT 'questions',
            file_name VARCHAR(255) DEFAULT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            file_type VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
        )
    ");
    if ($direct_call) echo "Exams table created.\n";
    
    // Questions table
    $db->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            question_text TEXT NOT NULL,
            question_type ENUM('essay', 'multiple_choice') NOT NULL,
            option_a VARCHAR(500) DEFAULT NULL,
            option_b VARCHAR(500) DEFAULT NULL,
            option_c VARCHAR(500) DEFAULT NULL,
            option_d VARCHAR(500) DEFAULT NULL,
            correct_answer CHAR(1) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        )
    ");
    if ($direct_call) echo "Questions table created.\n";
    
    // Insert default subjects
    $stmt = $db->prepare("SELECT COUNT(*) FROM subjects");
    $stmt->execute();
    $subjectCount = $stmt->fetchColumn();
    
    if ($subjectCount == 0) {
        $subjects = [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Fisika', 'Kimia',
            'Biologi', 'Sejarah', 'Geografi', 'Ekonomi', 'Sosiologi',
            'PKn', 'Seni Budaya', 'Pendidikan Jasmani', 'Prakarya', 'Bahasa Daerah',
            'Agama', 'Teknologi Informasi', 'Bahasa Jepang', 'Kewirausahaan', 'Bimbingan Konseling'
        ];
        
        $stmt = $db->prepare("INSERT INTO subjects (name) VALUES (?)");
        foreach ($subjects as $subject) {
            $stmt->execute([$subject]);
        }
        if ($direct_call) echo "Default subjects inserted.\n";
    }
    
    // Insert default classes
    $stmt = $db->prepare("SELECT COUNT(*) FROM classes");
    $stmt->execute();
    $classCount = $stmt->fetchColumn();
    
    if ($classCount == 0) {
        $classes = [
            ['X IPA 1', 'Belum ditentukan'], ['X IPA 2', 'Belum ditentukan'], ['X IPS 1', 'Belum ditentukan'],
            ['XI IPA 1', 'Belum ditentukan'], ['XI IPA 2', 'Belum ditentukan'], ['XI IPS 1', 'Belum ditentukan'],
            ['XII IPA 1', 'Belum ditentukan'], ['XII IPA 2', 'Belum ditentukan'], ['XII IPS 1', 'Belum ditentukan']
        ];
        
        $stmt = $db->prepare("INSERT INTO classes (name, teacher) VALUES (?, ?)");
        foreach ($classes as $class) {
            $stmt->execute($class);
        }
        if ($direct_call) echo "Default classes inserted.\n";
    }
    
    if ($direct_call) {
        echo "\n✅ Database setup completed successfully!\n";
        echo "You can now use the Baiturrahman Web system.\n";
    }
    
} catch (PDOException $e) {
    if ($direct_call) {
        echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    } else {
        throw $e;
    }
}
?>