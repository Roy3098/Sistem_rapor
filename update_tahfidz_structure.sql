-- Update struktur tabel tahfidz_grades untuk mendukung sistem juz
-- Script ini akan menambahkan kolom baru jika belum ada

-- Menambahkan kolom untuk informasi juz
ALTER TABLE tahfidz_grades 
ADD COLUMN IF NOT EXISTS from_juz INT DEFAULT NULL AFTER semester,
ADD COLUMN IF NOT EXISTS to_juz INT DEFAULT NULL AFTER from_juz,
ADD COLUMN IF NOT EXISTS until_surah VARCHAR(100) DEFAULT NULL AFTER to_juz;

-- Update komentar kolom yang sudah ada untuk kejelasan
ALTER TABLE tahfidz_grades 
MODIFY COLUMN surah_name VARCHAR(100) COMMENT 'Surat terakhir yang dihafal',
MODIFY COLUMN ayah_range VARCHAR(50) COMMENT 'Informasi juz (contoh: Juz 1-2)',
MODIFY COLUMN memorization_quality INT COMMENT 'Nilai Tahfidz (1-100)',
MODIFY COLUMN fluency_score INT COMMENT 'Tidak digunakan untuk sistem juz',
MODIFY COLUMN tajweed_score INT COMMENT 'Nilai Tajwid (1-100)';

-- Index untuk performa query
CREATE INDEX IF NOT EXISTS idx_tahfidz_juz ON tahfidz_grades(from_juz, to_juz);
CREATE INDEX IF NOT EXISTS idx_tahfidz_surah ON tahfidz_grades(until_surah);