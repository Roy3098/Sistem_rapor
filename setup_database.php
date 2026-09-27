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
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
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
    
    // Check if created_by column exists in exams table, if not add it
    try {
        $stmt = $db->query("SHOW COLUMNS FROM exams LIKE 'created_by'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE exams ADD COLUMN created_by INT DEFAULT NULL AFTER file_type");
            $db->exec("ALTER TABLE exams ADD CONSTRAINT fk_exams_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
            if ($direct_call) echo "Added created_by column to exams table.\n";
        }
    } catch (Exception $e) {
        if ($direct_call) echo "Error adding created_by column: " . $e->getMessage() . "\n";
    }
    
    // Insert default subjects
    $stmt = $db->prepare("SELECT COUNT(*) FROM subjects");
    $stmt->execute();
    $subjectCount = $stmt->fetchColumn();
    
    if ($subjectCount == 0) {
        $subjects = [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Fisika', 'Kimia',
            'Biologi', 'Sejarah', 'Geografi', 'Ekonomi', 'Sosiologi',
            'PKn', 'Seni Budaya', 'Pendidikan Jasmani', 'Prakarya', 'Bahasa Daerah',
            'Agama', 'Teknologi Informasi', 'Bahasa Jepang', 'Kewirausahaan', 'Bimbingan Konseling',
            'Tahfidz', 'Tajwid'
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
    
    // Create tahfidz tables from external SQL file
    $tahfidzSql = file_get_contents(__DIR__ . '/create_tahfidz_table.sql');
    if ($tahfidzSql) {
        // Split SQL file by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $tahfidzSql)));
        foreach ($statements as $statement) {
            if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
                try {
                    $db->exec($statement);
                } catch (Exception $e) {
                    if ($direct_call) echo "Note: " . $e->getMessage() . "\n";
                }
            }
        }
        if ($direct_call) echo "Tahfidz tables created.\n";
    }
    
    // Update tahfidz table structure for juz system
    try {
        // Check if from_juz column exists, if not add it
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades LIKE 'from_juz'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN from_juz INT DEFAULT NULL AFTER semester");
            if ($direct_call) echo "Added from_juz column to tahfidz_grades table.\n";
        }
        
        // Check if to_juz column exists, if not add it
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades LIKE 'to_juz'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN to_juz INT DEFAULT NULL AFTER from_juz");
            if ($direct_call) echo "Added to_juz column to tahfidz_grades table.\n";
        }
        
        // Check if until_surah column exists, if not add it
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades LIKE 'until_surah'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN until_surah VARCHAR(100) DEFAULT NULL AFTER to_juz");
            if ($direct_call) echo "Added until_surah column to tahfidz_grades table.\n";
        }
        
        // Update total_score calculation for 2 values only (tahfidz and tajwid)
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades WHERE Field='total_score'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && strpos($column['Extra'], 'GENERATED') !== false) {
            // Drop and recreate the generated column
            $db->exec("ALTER TABLE tahfidz_grades DROP COLUMN total_score");
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN total_score DECIMAL(5,2) GENERATED ALWAYS AS ((memorization_quality + tajweed_score) / 2) STORED AFTER tajweed_score");
            if ($direct_call) echo "Updated total_score calculation for 2 values.\n";
        }
        
        // Update constraints to max 90
        try {
            $db->exec("ALTER TABLE tahfidz_grades DROP CHECK tahfidz_grades_chk_1");
        } catch (Exception $e) {
            // Constraint might not exist or have different name
        }
        try {
            $db->exec("ALTER TABLE tahfidz_grades DROP CHECK tahfidz_grades_chk_2");
        } catch (Exception $e) {
            // Constraint might not exist or have different name
        }
        try {
            $db->exec("ALTER TABLE tahfidz_grades DROP CHECK tahfidz_grades_chk_3");
        } catch (Exception $e) {
            // Constraint might not exist or have different name
        }
        
        // Add new constraints with max 90
        try {
            $db->exec("ALTER TABLE tahfidz_grades ADD CONSTRAINT chk_memorization_quality CHECK (memorization_quality BETWEEN 1 AND 90)");
            $db->exec("ALTER TABLE tahfidz_grades ADD CONSTRAINT chk_tajweed_score CHECK (tajweed_score BETWEEN 1 AND 90)");
            $db->exec("ALTER TABLE tahfidz_grades ADD CONSTRAINT chk_fluency_score CHECK (fluency_score BETWEEN 1 AND 90)");
            if ($direct_call) echo "Updated constraints to max 90.\n";
        } catch (Exception $e) {
            // Constraints might already exist
        }
        
        // Update grade_letter calculation with new grading system
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades WHERE Field='grade_letter'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && strpos($column['Extra'], 'GENERATED') !== false) {
            // Drop and recreate the generated column
            $db->exec("ALTER TABLE tahfidz_grades DROP COLUMN grade_letter");
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN grade_letter VARCHAR(2) GENERATED ALWAYS AS (
                CASE 
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 86 THEN 'A'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 81 THEN 'B'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 71 THEN 'C'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 60 THEN 'D'
                    ELSE 'E'
                END
            ) STORED AFTER total_score");
            if ($direct_call) echo "Updated grade_letter calculation with new grading system.\n";
        }
        
        // Add grade description column for Arabic names
        $stmt = $db->query("SHOW COLUMNS FROM tahfidz_grades LIKE 'grade_description'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE tahfidz_grades ADD COLUMN grade_description VARCHAR(20) GENERATED ALWAYS AS (
                CASE 
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 86 THEN 'MUMTAZ'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 81 THEN 'JAYYID JIDDAN'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 71 THEN 'JAYYID'
                    WHEN ((memorization_quality + tajweed_score) / 2) >= 60 THEN 'MAQBUL'
                    ELSE 'RISIB'
                END
            ) STORED AFTER grade_letter");
            if ($direct_call) echo "Added grade_description column with Arabic names.\n";
        }
        
        // Update regular grades table constraint to max 90
        try {
            $db->exec("ALTER TABLE grades ADD CONSTRAINT chk_grade_range CHECK (grade >= 0 AND grade <= 90)");
            if ($direct_call) echo "Updated grades table constraint to max 90.\n";
        } catch (Exception $e) {
            // Constraint might already exist
        }
        
    } catch (Exception $e) {
        if ($direct_call) echo "Error updating database structure: " . $e->getMessage() . "\n";
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