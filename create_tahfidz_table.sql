-- Create table for tahfidz (Quranic memorization) grades
CREATE TABLE IF NOT EXISTS tahfidz_grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    student_name VARCHAR(100) NOT NULL,
    class_id INT,
    semester INT NOT NULL CHECK (semester IN (1, 2)),
    surah_name VARCHAR(100) NOT NULL,
    ayah_range VARCHAR(50), -- e.g., "1-10", "15-30", or "Seluruh"
    memorization_quality INT NOT NULL CHECK (memorization_quality BETWEEN 1 AND 100),
    fluency_score INT NOT NULL CHECK (fluency_score BETWEEN 1 AND 100),
    tajweed_score INT NOT NULL CHECK (tajweed_score BETWEEN 1 AND 100),
    total_score DECIMAL(5,2) GENERATED ALWAYS AS ((memorization_quality + fluency_score + tajweed_score) / 3) STORED,
    grade_letter VARCHAR(2) GENERATED ALWAYS AS (
        CASE 
            WHEN ((memorization_quality + fluency_score + tajweed_score) / 3) >= 90 THEN 'A'
            WHEN ((memorization_quality + fluency_score + tajweed_score) / 3) >= 80 THEN 'B'
            WHEN ((memorization_quality + fluency_score + tajweed_score) / 3) >= 70 THEN 'C'
            WHEN ((memorization_quality + fluency_score + tajweed_score) / 3) >= 60 THEN 'D'
            ELSE 'E'
        END
    ) STORED,
    notes TEXT,
    test_date DATE DEFAULT CURDATE(),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_student_semester (student_id, semester),
    INDEX idx_class_semester (class_id, semester),
    INDEX idx_surah (surah_name)
);

-- Insert some common surahs for reference
CREATE TABLE IF NOT EXISTS surahs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    arabic_name VARCHAR(200),
    number INT NOT NULL,
    total_ayahs INT NOT NULL,
    type ENUM('Makkiyah', 'Madaniyah') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_number (number)
);

-- Insert common surahs that are typically memorized
INSERT IGNORE INTO surahs (name, arabic_name, number, total_ayahs, type) VALUES
('Al-Fatihah', 'الفاتحة', 1, 7, 'Makkiyah'),
('Al-Baqarah', 'البقرة', 2, 286, 'Madaniyah'),
('Ali-Imran', 'آل عمران', 3, 200, 'Madaniyah'),
('An-Nisa', 'النساء', 4, 176, 'Madaniyah'),
('Al-Maidah', 'المائدة', 5, 120, 'Madaniyah'),
('Al-Mulk', 'الملك', 67, 30, 'Makkiyah'),
('Al-Qalam', 'القلم', 68, 52, 'Makkiyah'),
('Al-Haqqah', 'الحاقة', 69, 52, 'Makkiyah'),
('Al-Maarij', 'المعارج', 70, 44, 'Makkiyah'),
('Nuh', 'نوح', 71, 28, 'Makkiyah'),
('Al-Jinn', 'الجن', 72, 28, 'Makkiyah'),
('Al-Muzzammil', 'المزمل', 73, 20, 'Makkiyah'),
('Al-Muddaththir', 'المدثر', 74, 56, 'Makkiyah'),
('Al-Qiyamah', 'القيامة', 75, 40, 'Makkiyah'),
('Al-Insan', 'الإنسان', 76, 31, 'Madaniyah'),
('Al-Mursalat', 'المرسلات', 77, 50, 'Makkiyah'),
('An-Naba', 'النبأ', 78, 40, 'Makkiyah'),
('An-Naziat', 'النازعات', 79, 46, 'Makkiyah'),
('Abasa', 'عبس', 80, 42, 'Makkiyah'),
('At-Takwir', 'التكوير', 81, 29, 'Makkiyah'),
('Al-Infitar', 'الإنفطار', 82, 19, 'Makkiyah'),
('Al-Mutaffifin', 'المطففين', 83, 36, 'Makkiyah'),
('Al-Inshiqaq', 'الإنشقاق', 84, 25, 'Makkiyah'),
('Al-Buruj', 'البروج', 85, 22, 'Makkiyah'),
('At-Tariq', 'الطارق', 86, 17, 'Makkiyah'),
('Al-Ala', 'الأعلى', 87, 19, 'Makkiyah'),
('Al-Ghashiyah', 'الغاشية', 88, 26, 'Makkiyah'),
('Al-Fajr', 'الفجر', 89, 30, 'Makkiyah'),
('Al-Balad', 'البلد', 90, 20, 'Makkiyah'),
('Ash-Shams', 'الشمس', 91, 15, 'Makkiyah'),
('Al-Layl', 'الليل', 92, 21, 'Makkiyah'),
('Ad-Duha', 'الضحى', 93, 11, 'Makkiyah'),
('Ash-Sharh', 'الشرح', 94, 8, 'Makkiyah'),
('At-Tin', 'التين', 95, 8, 'Makkiyah'),
('Al-Alaq', 'العلق', 96, 19, 'Makkiyah'),
('Al-Qadr', 'القدر', 97, 5, 'Makkiyah'),
('Al-Bayyinah', 'البينة', 98, 8, 'Madaniyah'),
('Az-Zalzalah', 'الزلزلة', 99, 8, 'Madaniyah'),
('Al-Adiyat', 'العاديات', 100, 11, 'Makkiyah'),
('Al-Qariah', 'القارعة', 101, 11, 'Makkiyah'),
('At-Takathur', 'التكاثر', 102, 8, 'Makkiyah'),
('Al-Asr', 'العصر', 103, 3, 'Makkiyah'),
('Al-Humazah', 'الهمزة', 104, 9, 'Makkiyah'),
('Al-Fil', 'الفيل', 105, 5, 'Makkiyah'),
('Quraysh', 'قريش', 106, 4, 'Makkiyah'),
('Al-Maun', 'الماعون', 107, 7, 'Makkiyah'),
('Al-Kawthar', 'الكوثر', 108, 3, 'Makkiyah'),
('Al-Kafirun', 'الكافرون', 109, 6, 'Makkiyah'),
('An-Nasr', 'النصر', 110, 3, 'Madaniyah'),
('Al-Masad', 'المسد', 111, 5, 'Makkiyah'),
('Al-Ikhlas', 'الإخلاص', 112, 4, 'Makkiyah'),
('Al-Falaq', 'الفلق', 113, 5, 'Makkiyah'),
('An-Nas', 'الناس', 114, 6, 'Makkiyah');