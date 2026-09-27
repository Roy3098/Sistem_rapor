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
    header('Location: install.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = trim($_POST['full_name']);
                
                if (empty($full_name)) {
                    $error = 'Nama lengkap tidak boleh kosong!';
                } else {
                    $profile_picture = null;
                    
                    // Handle profile picture upload
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                            $error = 'Format gambar tidak didukung! Gunakan JPG, PNG, atau GIF.';
                        } elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) { // 2MB
                            $error = 'Ukuran gambar terlalu besar! Maksimal 2MB.';
                        } else {
                            $upload_result = uploadFile($_FILES['profile_picture'], 'uploads/profiles/');
                            if ($upload_result['success']) {
                                $profile_picture = $upload_result['path'];
                            } else {
                                $error = $upload_result['message'];
                            }
                        }
                    }
                    
                    if (!$error) {
                        $result = updateUserProfile($currentUser['id'], $full_name, $profile_picture);
                        if ($result['success']) {
                            $message = $result['message'];
                            // Update session
                            $_SESSION['full_name'] = $full_name;
                            if ($profile_picture) {
                                $_SESSION['profile_picture'] = $profile_picture;
                            }
                            $currentUser = getCurrentUser();
                        } else {
                            $error = $result['message'];
                        }
                    }
                }
                break;
                
            case 'change_password':
                $old_password = $_POST['old_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'Harap lengkapi semua field password!';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password baru minimal 6 karakter!';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Password baru dan konfirmasi tidak sama!';
                } else {
                    $result = changeUserPassword($currentUser['id'], $old_password, $new_password);
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Baiturrahman Web</title>
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
        
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            animation: fadeIn 0.3s ease-out;
            max-height: 90vh;
            overflow-y: auto;
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
                        <h1 class="text-lg font-bold text-gray-800">Profil Saya</h1>
                        <p class="text-xs text-gray-600">Kelola akun dan pengaturan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-20">
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
            
            <!-- Profile Info -->
            <div class="text-center mb-6">
                <img src="<?php echo $currentUser['profile_picture'] ? htmlspecialchars($currentUser['profile_picture']) : 'https://placehold.co/120x120/cbd5e1/475569?text=👤'; ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-blue-400 shadow-md">
                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($currentUser['full_name']); ?></h3>
                <p class="text-gray-600 text-sm">@<?php echo htmlspecialchars($currentUser['username']); ?></p>
            </div>

            <!-- Profile Actions -->
            <div class="space-y-3 mb-6">
                <button onclick="showUpdateProfileModal()" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all">
                    ✏️ Edit Profil
                </button>
                <button onclick="showChangePasswordModal()" class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 text-white py-3 rounded-xl font-semibold hover:from-yellow-600 hover:to-orange-600 transition-all">
                    🔑 Ubah Password
                </button>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?')" class="block w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-3 rounded-xl font-semibold hover:from-red-600 hover:to-pink-600 transition-all text-center">
                    🚪 Logout
                </a>
            </div>

            <!-- Account Info -->
            <div class="bg-gray-50 rounded-xl p-4">
                <h4 class="font-semibold text-gray-800 mb-3">Informasi Akun</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Username:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nama Lengkap:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Bergabung:</span>
                        <span class="font-medium">
                            <?php 
                            $user_details = getUserDetails($currentUser['id']);
                            echo $user_details ? date('d/m/Y', strtotime($user_details['created_at'])) : 'Tidak diketahui';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div id="updateProfileModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Edit Profil</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Profil</label>
                        <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/gif" class="w-full text-gray-700 bg-gray-50 border border-gray-300 rounded-xl p-3 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                        <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF. Maksimal 2MB.</p>
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-600 transition-all">
                        Simpan Perubahan
                    </button>
                    <button type="button" onclick="hideUpdateProfileModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Ubah Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama</label>
                        <input type="password" name="old_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Masukkan password lama">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Minimal 6 karakter">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Ulangi password baru">
                    </div>
                </div>
                <div class="flex space-x-3 mt-6">
                    <button type="submit" class="flex-1 bg-yellow-500 text-white py-3 rounded-xl font-semibold hover:bg-yellow-600 transition-all">
                        Ubah Password
                    </button>
                    <button type="button" onclick="hideChangePasswordModal()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        Batal
                    </button>
                </div>
            </form>
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
                <a href="manage-data.php" class="flex flex-col items-center py-2 px-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all">
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
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
        function showUpdateProfileModal() {
            document.getElementById('updateProfileModal').style.display = 'flex';
        }

        function hideUpdateProfileModal() {
            document.getElementById('updateProfileModal').style.display = 'none';
        }

        function showChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'flex';
        }

        function hideChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }
    </script>
</body>
</html>