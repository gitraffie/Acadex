<?php
include 'includes/connection.php';

try {
    // Create assessment_items table
    $sql1 = "
        CREATE TABLE IF NOT EXISTS assessment_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            max_score DECIMAL(5,2) NOT NULL,
            component ENUM('class_standing', 'exam') NOT NULL,
            term ENUM('prelim', 'midterm', 'finals') NOT NULL,
            teacher_email VARCHAR(255) NOT NULL,
            date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ";

    $pdo->exec($sql1);
    echo "assessment_items table created successfully.\n";

    // Create assessment_scores table
    $sql2 = "
        CREATE TABLE IF NOT EXISTS assessment_scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assessment_item_id INT NOT NULL,
            student_id INT NOT NULL,
            score DECIMAL(5,2) NOT NULL,
            FOREIGN KEY (assessment_item_id) REFERENCES assessment_items(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ";

    $pdo->exec($sql2);
    echo "assessment_scores table created successfully.\n";

    echo "All tables created successfully!\n";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
?>
