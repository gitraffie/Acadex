<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'acadex_db';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Optional: Set timezone (adjust as needed)
    $pdo->exec("SET time_zone = '+00:00'");

} catch (PDOException $e) {
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}

// Optional: Create mysqli connection for backward compatibility
$conn = new mysqli($host, $username, $password, $dbname);

// Check mysqli connection
if ($conn->connect_error) {
    die("MySQLi connection failed: " . $conn->connect_error);
}

// Set charset for mysqli
$conn->set_charset("utf8");
?>
