<?php
include 'connection.php';

try {
    // Create grades table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            teacher_email VARCHAR(255) NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            class_standing DECIMAL(5,2),
            exam DECIMAL(5,2),
            term ENUM('prelim', 'midterm', 'finals') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_term (student_number, class_id, term)
        )
    ");

    // Create calculated_grades table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS calculated_grades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            teacher_email VARCHAR(255) NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            prelim DECIMAL(5,2),
            midterm DECIMAL(5,2),
            finals DECIMAL(5,2),
            final_grade DECIMAL(5,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_class (student_number, class_id)
        )
    ");

    // Create weights table for per-class grade calculation weights
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS weights (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            teacher_email VARCHAR(255) NOT NULL,
            class_standing DECIMAL(5,2) NOT NULL DEFAULT 70.00,
            exam DECIMAL(5,2) NOT NULL DEFAULT 30.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_class_weights (class_id)
        )
    ");

    // Create attendance table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            attendance_date DATE NOT NULL,
            session VARCHAR(20) NOT NULL DEFAULT 'morning',
            status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_date_session (student_id, attendance_date, class_id, session)
        )
    ");

    // Create email logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_email VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            class_id INT NULL,
            email_type VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_date (teacher_email, created_at)
        )
    ");

    // Create student requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            student_number VARCHAR(50) NOT NULL,
            student_name VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            class_id INT NULL,
            class_name VARCHAR(255) NULL,
            teacher_email VARCHAR(255) NOT NULL,
            request_type ENUM('grade','attendance') NOT NULL,
            term ENUM('prelim','midterm','finals','all') NULL,
            message TEXT NULL,
            status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
            resolved_at TIMESTAMP NULL,
            resolved_by VARCHAR(255) NULL,
            is_seen TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_status (teacher_email, status, created_at)
        )
    ");

    // Add session column if it doesn't exist (for existing tables)
    $pdo->exec("
        ALTER TABLE attendance
        ADD COLUMN IF NOT EXISTS session VARCHAR(20) NOT NULL DEFAULT 'morning'
    ");

    // Drop old unique key and add new one if needed
    $pdo->exec("
        ALTER TABLE attendance
        DROP INDEX IF EXISTS unique_student_date,
        ADD UNIQUE KEY IF NOT EXISTS unique_student_date_session (student_id, attendance_date, class_id, session)
    ");

    echo "Tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
