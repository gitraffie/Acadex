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
