<?php
// Load Composer autoloader if available
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
    // Load environment variables from .env if phpdotenv is available
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        // safeLoad() won't throw if .env is missing
        $dotenv->safeLoad();
    }
}

// Fallback to sensible defaults if env vars are not set
$servername = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$username   = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$password   = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
// $dbname = $_ENV['DB_NAME'] ?? 'testproject';
<<<<<<< Updated upstream
$dbname     = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'franmaxindia';
=======
$dbname     = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'testproject';
>>>>>>> Stashed changes

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}
?>
