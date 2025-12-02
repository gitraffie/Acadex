<?php
// IMPORTANT: No whitespace before this line!
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header FIRST
header('Content-Type: application/json');

// Start output buffering
ob_start();

session_start();
require_once 'connection.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get POST data
    $class_id = $_POST['class_id'] ?? null;
    $student_number = $_POST['student_number'] ?? null;
    $term = $_POST['term'] ?? null;
    $class_standing = $_POST['class_standing'] ?? null;
    $exam = $_POST['exam'] ?? null;
    $teacher_email = $_SESSION['email'] ?? null;

    // Validate required fields
    if (!$class_id || !$student_number || !$term || !$teacher_email) {
        throw new Exception('Missing required fields');
    }

    // Validate term and map to column name (prevent SQL injection)
    $valid_terms = [
        'Prelim' => 'prelim',
        'Midterm' => 'midterm',
        'Finals' => 'finals'
    ];

    if (!isset($valid_terms[$term])) {
        throw new Exception('Invalid term');
    }

    $column = $valid_terms[$term]; // Safe column name

    // Validate numeric values if provided
    if ($class_standing !== null && $class_standing !== '' && !is_numeric($class_standing)) {
        throw new Exception('Class standing must be a number');
    }
    if ($exam !== null && $exam !== '' && !is_numeric($exam)) {
        throw new Exception('Exam score must be a number');
    }

    // Get student_id from student_number
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
    $stmt->execute([$student_number]);
    $student = $stmt->fetch();

    if (!$student) {
        throw new Exception('Student not found');
    }

    $student_id = $student['id'];

    // Begin transaction for data consistency
    $pdo->beginTransaction();

    // Check if grade record already exists for this student, class, and term
    $stmt = $pdo->prepare("SELECT id FROM grades WHERE student_number = ? AND class_id = ? AND term = ?");
    $stmt->execute([$student_number, $class_id, $term]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing record
        $stmt = $pdo->prepare("UPDATE grades SET class_standing = ?, exam = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$class_standing, $exam, $existing['id']]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO grades (class_id, teacher_email, student_number, class_standing, exam, term, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$class_id, $teacher_email, $student_number, $class_standing, $exam, $term]);
    }

    // Get weights for the class
    $stmt = $pdo->prepare("SELECT class_standing, exam FROM weights WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $weights = $stmt->fetch();

    // Use default weights if no custom weights are set
    if (!$weights) {
        $weights = [
            'class_standing' => 0.7,
            'exam' => 0.3
        ];
    }

    // Calculate final grade for the current term using saved weights
    $final_grade = 0;
    if ($class_standing !== null && $class_standing !== '') {
        $final_grade += (float)$class_standing * (float)$weights['class_standing'];
    }
    if ($exam !== null && $exam !== '') {
        $final_grade += (float)$exam * (float)$weights['exam'];
    }

    // Round to 2 decimal places
    $final_grade = round($final_grade, 2);

    // Check if record exists in calculated_grades
    $stmt = $pdo->prepare("SELECT id FROM calculated_grades WHERE class_id = ? AND student_number = ?");
    $stmt->execute([$class_id, $student_number]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update the specific column using prepared statement with safe column name
        $sql = "UPDATE calculated_grades SET {$column} = ?, updated_at = NOW() WHERE class_id = ? AND student_number = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$final_grade, $class_id, $student_number]);
    } else {
        // Insert new record with the specific column set
        $sql = "INSERT INTO calculated_grades (class_id, teacher_email, student_number, {$column}, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$class_id, $teacher_email, $student_number, $final_grade]);
    }

    // Commit transaction
    $pdo->commit();

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true, 
        'message' => 'Scores saved successfully',
        'final_grade' => $final_grade
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Database error in save_scores.php: ' . $e->getMessage());
    
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

exit;
?>