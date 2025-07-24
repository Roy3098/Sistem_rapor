-- Database Schema for Baiturrahman Web System

CREATE DATABASE IF NOT EXISTS baiturrahman_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE baiturrahman_web;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Classes table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    teacher VARCHAR(100) DEFAULT 'Belum ditentukan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Subjects table
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
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
);

-- Grades table
CREATE TABLE grades (
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
);

-- Exams table
CREATE TABLE exams (
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
);

-- Questions table
CREATE TABLE questions (
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
);

-- Insert default subjects
INSERT INTO subjects (name) VALUES 
('Matematika'), ('Bahasa Indonesia'), ('Bahasa Inggris'), ('Fisika'), ('Kimia'),
('Biologi'), ('Sejarah'), ('Geografi'), ('Ekonomi'), ('Sosiologi'),
('PKn'), ('Seni Budaya'), ('Pendidikan Jasmani'), ('Prakarya'), ('Bahasa Daerah'),
('Agama'), ('Teknologi Informasi'), ('Bahasa Jepang'), ('Kewirausahaan'), ('Bimbingan Konseling');

-- Insert default classes
INSERT INTO classes (name, teacher) VALUES 
('X IPA 1', 'Belum ditentukan'), ('X IPA 2', 'Belum ditentukan'), ('X IPS 1', 'Belum ditentukan'),
('XI IPA 1', 'Belum ditentukan'), ('XI IPA 2', 'Belum ditentukan'), ('XI IPS 1', 'Belum ditentukan'),
('XII IPA 1', 'Belum ditentukan'), ('XII IPA 2', 'Belum ditentukan'), ('XII IPS 1', 'Belum ditentukan');