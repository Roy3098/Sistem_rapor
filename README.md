# Baiturrahman Web - Sistem Rapor Digital

Sistem manajemen nilai dan ujian untuk sekolah dengan interface yang modern dan mobile-friendly.

## 🚀 Instalasi

### Persyaratan Sistem
- XAMPP/WAMP/LAMP
- PHP 7.4+
- MySQL 5.7+
- Web browser modern

### Langkah Instalasi

1. **Download dan Extract**
   - Download semua file ke folder `htdocs/web_rapor4/`

2. **Jalankan XAMPP**
   - Start Apache dan MySQL

3. **Install Database**
   - Buka browser dan akses: `http://localhost/web_rapor4/install.php`
   - Klik tombol "Install Database"
   - Tunggu hingga instalasi selesai

4. **Akses Sistem**
   - Buka: `http://localhost/web_rapor4/`
   - Daftar akun baru atau login

## 📱 Fitur Utama

### 🎯 Dashboard Statistik
- Total siswa dan nilai
- Siswa berprestasi per kelas
- Distribusi nilai (Sangat Baik, Baik, Perlu Perbaikan)
- Rata-rata per mata pelajaran
- Juara umum (Top 5 siswa)

### 📝 Manajemen Nilai
- Input nilai per mata pelajaran
- Input nilai massal per kelas
- Lihat dan filter nilai
- Export ke Excel (CSV)

### 🗂️ Kelola Data
- **Mata Pelajaran**: Tambah, edit, hapus
- **Kelas**: Manajemen kelas dan wali kelas
- **Siswa**: Data lengkap siswa (NIS, TTL, wali)

### 📋 Manajemen Soal
- Upload file soal (PDF, DOC, DOCX)
- Buat soal essay dan pilihan ganda
- Export ke Word document
- Lihat detail soal

### 👤 Profil Pengguna
- Edit profil dan foto
- Ubah password
- Informasi akun

## 🔧 Konfigurasi

### Database
File: `config/database.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'baiturrahman_web');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Upload Directory
- Buat folder `uploads/` dengan permission 755
- Subfolder: `uploads/profiles/` untuk foto profil

## 📊 Struktur Database

### Tabel Utama:
- `users` - Data pengguna
- `classes` - Data kelas
- `subjects` - Mata pelajaran
- `students` - Data siswa
- `grades` - Nilai siswa
- `exams` - Data ujian/soal
- `questions` - Pertanyaan ujian

## 🎨 Teknologi

- **Backend**: PHP 7.4+, MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Tailwind CSS
- **Icons**: Heroicons
- **Export**: CSV (Excel compatible)
- **Upload**: Multi-format file support

## 📱 Mobile Responsive

Sistem dirancang mobile-first dengan:
- Touch-friendly interface
- Responsive design
- Bottom navigation
- Modal dialogs
- Optimized untuk semua ukuran layar

## 🔒 Keamanan

- Password hashing (PHP password_hash)
- SQL injection prevention (PDO prepared statements)
- File upload validation
- Session management
- Input sanitization

## 🆘 Troubleshooting

### Database Error
Jika muncul error "Table doesn't exist":
1. Akses `http://localhost/web_rapor4/install.php`
2. Klik "Install Database"
3. Refresh halaman

### Upload Error
1. Pastikan folder `uploads/` ada dan writable
2. Check PHP upload settings di `php.ini`

### Permission Error
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
```

## 📞 Support

Untuk bantuan teknis atau pertanyaan, silakan hubungi administrator sistem.

---

**Baiturrahman Web** - Sistem Rapor Digital Modern 🎓