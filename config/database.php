<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'baiturrahman_web');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;

    public function connect() {
        try {
            // First connect without database to create it if needed
            $this->conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
            
            // Create database if it doesn't exist
            $this->conn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now connect to the specific database
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
            
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            exit();
        }
        return $this->conn;
    }
}

// Create database connection
function getDbConnection() {
    $database = new Database();
    return $database->connect();
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    $db = getDbConnection();
    
    try {
        // Check if tables exist, if not create them
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            // Tables don't exist, create them
            include_once 'setup_database.php';
        }
    } catch(PDOException $e) {
        // If there's an error, try to create tables
        include_once 'setup_database.php';
    }
}
?>