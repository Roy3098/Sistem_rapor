<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Try to initialize database
try {
    initializeDatabase();
} catch(Exception $e) {
    // If database fails, redirect to install
    header('Location: install.php');
    exit();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                
                if (empty($username) || empty($password)) {
                    $error = 'Harap masukkan username dan password!';
                } else {
                    $result = loginUser($username, $password);
                    if ($result['success']) {
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'register':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                $full_name = trim($_POST['full_name']);
                
                if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name)) {
                    $error = 'Harap lengkapi semua field!';
                } elseif (strlen($username) < 3) {
                    $error = 'Username minimal 3 karakter!';
                } elseif (strlen($password) < 6) {
                    $error = 'Password minimal 6 karakter!';
                } elseif ($password !== $confirm_password) {
                    $error = 'Password dan konfirmasi password tidak sama!';
                } else {
                    $result = registerUser($username, $password, $full_name);
                    if ($result['success']) {
                        $success = $result['message'] . ' Silakan login.';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
                
            case 'forgot_password':
                $username = trim($_POST['username']);
                $new_password = $_POST['new_password'];
                $confirm_new_password = $_POST['confirm_new_password'];
                
                if (empty($username)) {
                    $error = 'Harap masukkan username!';
                } elseif (empty($new_password) || empty($confirm_new_password)) {
                    $error = 'Harap masukkan password baru!';
                } elseif (strlen($new_password) < 6) {
                    $error = 'Password baru minimal 6 karakter!';
                } elseif ($new_password !== $confirm_new_password) {
                    $error = 'Password baru dan konfirmasi tidak sama!';
                } else {
                    $result = resetPassword($username, $new_password);
                    if ($result['success']) {
                        $success = $result['message'] . ' Silakan login.';
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
    <title>Baiturrahman Web - Login</title>
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
        
        <!-- Login Page -->
        <div id="loginPage" class="page-content">
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 fade-in">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Baiturrahman Web</h1>
                    <p class="text-gray-600 text-sm">System pengumpulan soal dan nilai ujian</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg text-sm">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4 mb-6">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Masukkan username">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Masukkan password">
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all">
                        🔐 Login
                    </button>
                </form>
                
                <div class="space-y-3">
                    <button onclick="showPage('registerPage')" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                        📝 Daftar Akun Baru
                    </button>
                    <button onclick="showForgotPasswordModal()" class="w-full text-blue-600 text-sm font-medium hover:underline py-2">
                        Lupa Password?
                    </button>
                </div>
            </div>
        </div>

        <!-- Register Page -->
        <div id="registerPage" class="page-content" style="display: none;">
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Daftar Akun Baru</h2>
                    <button onclick="showPage('loginPage')" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <p class="text-gray-600 text-sm mb-6">Buat akun baru untuk menggunakan sistem</p>
                
                <form method="POST" class="space-y-4 mb-6">
                    <input type="hidden" name="action" value="register">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Pilih username unik">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Minimal 6 karakter">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Ulangi password">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                        <input type="text" name="full_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" placeholder="Nama lengkap Anda">
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all">
                        ✅ Daftar Sekarang
                    </button>
                </form>
                
                <button onclick="showPage('loginPage')" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    ← Kembali ke Login
                </button>
            </div>
        </div>
        
        <!-- Forgot Password Modal -->
        <div id="forgotPasswordModal" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Reset Password</h2>
                <p class="text-gray-600 text-sm mb-4 text-center">Masukkan username dan password baru Anda.</p>
                
                <form method="POST" class="space-y-4 mb-6">
                    <input type="hidden" name="action" value="forgot_password">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Username Anda">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Minimal 6 karakter">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_new_password" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" placeholder="Ulangi password baru">
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-600 transition-all">
                        Reset Password
                    </button>
                </form>
                
                <button onclick="hideForgotPasswordModal()" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        function showPage(pageId) {
            // Hide all pages
            const pages = document.querySelectorAll('.page-content');
            pages.forEach(page => page.style.display = 'none');
            
            // Show selected page
            document.getElementById(pageId).style.display = 'block';
        }
        
        function showForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'flex';
        }
        
        function hideForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }
    </script>
</body>
</html>