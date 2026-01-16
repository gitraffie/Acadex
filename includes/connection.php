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
    date_default_timezone_set('Asia/Manila');
    $pdo->exec("SET time_zone = '+08:00'");

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

// Ensure first/last name columns exist for users; fallback to legacy full_name if needed
try {
    $hasFirst = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_name'")->fetch();
    if (!$hasFirst) {
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN first_name VARCHAR(255) NOT NULL DEFAULT '' AFTER id,
            ADD COLUMN last_name VARCHAR(255) NOT NULL DEFAULT '' AFTER first_name");
        // Best effort backfill from full_name
        $pdo->exec("
            UPDATE users
            SET 
                first_name = TRIM(SUBSTRING_INDEX(full_name, ' ', 1)),
                last_name = TRIM(SUBSTRING(full_name, LENGTH(SUBSTRING_INDEX(full_name, ' ', 1)) + 2))
            WHERE full_name IS NOT NULL AND full_name != ''
        ");
    }

    // Keep full_name in sync for legacy code paths
    $pdo->exec("
        UPDATE users
        SET full_name = TRIM(CONCAT(first_name, ' ', last_name))
        WHERE (full_name IS NULL OR full_name = '')
    ");
} catch (PDOException $e) {
    // Silent fail to avoid blocking the app; consider logging in production
}

// Ensure password and must_change_password columns for students
try {
    $hasStudentPassword = $pdo->query("SHOW COLUMNS FROM students LIKE 'password'")->fetch();
    if (!$hasStudentPassword) {
        $pdo->exec("ALTER TABLE students ADD COLUMN password VARCHAR(255) NULL AFTER student_email");
    }
    $hasMustChange = $pdo->query("SHOW COLUMNS FROM students LIKE 'must_change_password'")->fetch();
    if (!$hasMustChange) {
        $pdo->exec("ALTER TABLE students ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER password");
    }
    // Backfill default passwords (hashed student_number) where missing
    $studentsToBackfill = $pdo->query("SELECT id, student_number FROM students WHERE (password IS NULL OR password = '')")->fetchAll();
    if ($studentsToBackfill) {
        $updateStmt = $pdo->prepare("UPDATE students SET password = ?, must_change_password = 1 WHERE id = ?");
        foreach ($studentsToBackfill as $stu) {
            $hashed = password_hash($stu['student_number'], PASSWORD_DEFAULT);
            $updateStmt->execute([$hashed, $stu['id']]);
        }
    }
} catch (PDOException $e) {
    // Silent fail to avoid blocking the app; consider logging in production
}
?>
