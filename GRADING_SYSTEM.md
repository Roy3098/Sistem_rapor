# 📊 SISTEM PENILAIAN BAITURRAHMAN WEB

## 🎯 **Nilai Maksimum: 90**

Semua input nilai memiliki batas maksimum **90 poin**, baik untuk:
- ✅ Mata pelajaran reguler 
- ✅ Tahfidz (Hafalan)
- ✅ Tajwid

## 📋 **Sistem Grading dengan Nama Arab**

| Rentang Nilai | Grade | Nama Arab | Warna Display | Keterangan |
|---------------|-------|-----------|---------------|------------|
| **86 - 90** | **A** | **MUMTAZ** | 🟢 Hijau | Sangat Baik Sekali |
| **81 - 85** | **B** | **JAYYID JIDDAN** | 🔵 Biru | Sangat Baik |
| **71 - 80** | **C** | **JAYYID** | 🟡 Kuning | Baik |
| **60 - 70** | **D** | **MAQBUL** | 🟠 Orange | Cukup |
| **< 60** | **E** | **RISIB** | 🔴 Merah | Kurang |

## 🔧 **Implementasi Teknis**

### **1. Database Constraints:**
```sql
-- Tabel grades (mata pelajaran reguler)
ALTER TABLE grades ADD CONSTRAINT chk_grade_range CHECK (grade >= 0 AND grade <= 90);

-- Tabel tahfidz_grades 
ALTER TABLE tahfidz_grades ADD CONSTRAINT chk_memorization_quality CHECK (memorization_quality BETWEEN 1 AND 90);
ALTER TABLE tahfidz_grades ADD CONSTRAINT chk_tajweed_score CHECK (tajweed_score BETWEEN 1 AND 90);
```

### **2. Grade Calculation (Tahfidz):**
```sql
-- Grade Letter
CASE 
    WHEN ((memorization_quality + tajweed_score) / 2) >= 86 THEN 'A'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 81 THEN 'B'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 71 THEN 'C'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 60 THEN 'D'
    ELSE 'E'
END

-- Grade Description  
CASE 
    WHEN ((memorization_quality + tajweed_score) / 2) >= 86 THEN 'MUMTAZ'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 81 THEN 'JAYYID JIDDAN'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 71 THEN 'JAYYID'
    WHEN ((memorization_quality + tajweed_score) / 2) >= 60 THEN 'MAQBUL'
    ELSE 'RISIB'
END
```

### **3. HTML Form Validation:**
```html
<!-- Input Nilai Reguler -->
<input type="number" min="0" max="90" placeholder="0-90">

<!-- Input Nilai Tahfidz -->
<input type="number" min="1" max="90" placeholder="1-90">
```

### **4. JavaScript Validation:**
```javascript
// Validasi client-side
if (isNaN(grade) || grade < 0 || grade > 90) {
    alert('Nilai harus berupa angka antara 0-90!');
    return;
}

// Fungsi warna berdasarkan grade
function getGradeColor(average) {
    if (average >= 86) return 'text-green-600';   // MUMTAZ
    if (average >= 81) return 'text-blue-600';    // JAYYID JIDDAN  
    if (average >= 71) return 'text-yellow-600';  // JAYYID
    if (average >= 60) return 'text-orange-600';  // MAQBUL
    return 'text-red-500';                        // RISIB
}
```

### **5. PHP Validation:**
```php
// Server-side validation
if (!is_numeric($grade) || $grade < 0 || $grade > 90) {
    $error = 'Nilai harus berupa angka antara 0-90!';
}
```

## 📈 **Dampak Perubahan**

### **✅ Yang Berubah:**
- ✅ Nilai maksimum dari **100** menjadi **90**
- ✅ Sistem grading menggunakan **nama Arab**
- ✅ Rentang nilai untuk setiap grade disesuaikan
- ✅ Tampilan warna grade di UI

### **✅ Yang Tetap:**
- ✅ Struktur database tahfidz_grades
- ✅ Cara input dan edit nilai
- ✅ Export CSV tetap berfungsi
- ✅ Modal detail nilai tetap ada

## 🎨 **Tampilan Grade di UI**

### **Card Nilai Siswa:**
```
┌─────────────────────────────────────┐
│ 👤 Ahmad              NIS: 12345    │
│ Matematika: 88 🟢 A (MUMTAZ)       │
│ B.Indonesia: 83 🔵 B (JAYYID JIDDAN)│
│ Tahfidz: 85 🔵 B (JAYYID JIDDAN)   │
│ Tajwid: 78 🟡 C (JAYYID)           │
└─────────────────────────────────────┘
```

### **Export CSV:**
| Mata Pelajaran | Nilai | Grade | Deskripsi |
|----------------|-------|-------|-----------|
| Matematika | 88 | A | MUMTAZ |
| B. Indonesia | 83 | B | JAYYID JIDDAN |
| Tahfidz | 85 | B | JAYYID JIDDAN |
| Tajwid | 78 | C | JAYYID |

## 🚀 **Cara Penggunaan**

1. **Input Nilai:** Masukkan nilai 0-90 di form input
2. **Lihat Grade:** Sistem otomatis menghitung grade dan deskripsi Arab
3. **Export Data:** CSV mencakup nilai, grade, dan deskripsi
4. **Filter & Search:** Semua fitur existing tetap berfungsi

---

**📅 Update:** Sistem ini efektif mulai implementasi database terbaru.
**👨‍💻 Dikembangkan untuk:** Baiturrahman Web - Sistem Manajemen Nilai Digital