<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Baiturrahman Web</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Install Baiturrahman Web</h1>
                <p class="text-gray-600 text-sm">Setup database untuk sistem rapor</p>
            </div>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo '<div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">';
                echo '<h3 class="font-semibold text-blue-800 mb-2">Installing Database...</h3>';
                echo '<div class="text-sm text-blue-700">';
                
                // Capture output from setup script
                ob_start();
                include 'setup_database.php';
                $output = ob_get_clean();
                
                // Convert output to HTML
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (trim($line)) {
                        if (strpos($line, '✅') !== false) {
                            echo '<div class="text-green-600 font-semibold">' . htmlspecialchars($line) . '</div>';
                        } elseif (strpos($line, '❌') !== false) {
                            echo '<div class="text-red-600 font-semibold">' . htmlspecialchars($line) . '</div>';
                        } else {
                            echo '<div>' . htmlspecialchars($line) . '</div>';
                        }
                    }
                }
                
                echo '</div>';
                echo '<div class="mt-4">';
                echo '<a href="login.php" class="bg-green-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-600 transition-all">Lanjut ke Login</a>';
                echo '</div>';
                echo '</div>';
            } else {
            ?>
            
            <div class="space-y-4">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-semibold text-yellow-800 mb-2">Persyaratan:</h3>
                    <ul class="text-sm text-yellow-700 space-y-1">
                        <li>• XAMPP/WAMP sudah terinstall</li>
                        <li>• MySQL server sudah berjalan</li>
                        <li>• PHP 7.4 atau lebih tinggi</li>
                    </ul>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Yang akan dibuat:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Database: baiturrahman_web</li>
                        <li>• Tabel: users, classes, subjects, students, grades, exams, questions</li>
                        <li>• Data default: mata pelajaran dan kelas</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition-all">
                        🚀 Install Database
                    </button>
                </form>
            </div>
            
            <?php } ?>
        </div>
    </div>
</body>
</html>