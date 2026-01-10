<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Load environment variables
        $this->loadEnvironment();
    }

    private function loadEnvironment() {
        // Try multiple locations where .env might be
        $possiblePaths = [
            __DIR__ . '/../.env',           // One level up from classes folder
            __DIR__ . '/../../.env',        // Two levels up (outside public_html)
            dirname(__DIR__, 2) . '/.env',  // Outside public_html
            '.env',                         // Current directory
        ];
        
        foreach ($possiblePaths as $envPath) {
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || $line[0] === '#') continue;
                    
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        
                        // Store in a global array or as static
                        $GLOBALS['_ENV'][$key] = $value;
                    }
                }
                break; // Stop after first found .env file
            }
        }
        
        // Set database credentials with fallbacks
        $this->host = $GLOBALS['_ENV']['DB_HOST'] ?? 'localhost';
        $this->db_name = $GLOBALS['_ENV']['DB_NAME'] ?? 'fit-for-the-king';
        $this->username = $GLOBALS['_ENV']['DB_USER'] ?? 'root';
        $this->password = $GLOBALS['_ENV']['DB_PASS'] ?? '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Better error handling for production
            error_log("Database Error [" . date('Y-m-d H:i:s') . "]: " . $exception->getMessage());
            
            // User-friendly message
            if (($GLOBALS['_ENV']['APP_ENV'] ?? 'production') === 'development') {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo "Database connection error. Please try again later.";
            }
        }
        return $this->conn;
    }
}
?>