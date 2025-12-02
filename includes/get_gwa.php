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
        throw new Exception('Unauthorized access');
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    // Get and validate parameters
    $class_id = $_GET['class_id'] ?? null;
    $student_number = $_GET['student_number'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Validate class_id is numeric
    if (!is_numeric($class_id)) {
        throw new Exception('Invalid class ID');
    }

    // Validate student_number if provided
    if ($student_number !== null && !is_numeric($student_number)) {
        throw new Exception('Invalid student number');
    }

    // Verify teacher owns this class (security check)
    $teacher_email = $_SESSION['email'] ?? null;
    if ($teacher_email) {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND user_email = ?");
        $stmt->execute([$class_id, $teacher_email]);
        if (!$stmt->fetch()) {
            throw new Exception('You do not have permission to view this class');
        }
    }

    // Build query to get calculated grades
    $query = "
        SELECT
            cg.id,
            cg.class_id,
            cg.teacher_email,
            cg.student_number,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade,
            s.first_name,
            s.last_name
        FROM calculated_grades cg
        INNER JOIN students s ON cg.student_number = s.student_number
        WHERE cg.class_id = ?
    ";

    $params = [$class_id];

    // Add student filter if provided
    if ($student_number) {
        $query .= " AND cg.student_number = ?";
        $params[] = $student_number;
    }

    $query .= " ORDER BY s.last_name, s.first_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Calculate final grades for all students using weights
    $updated_grades = [];
    foreach ($results as $row) {
        $prelim = $row['prelim'] ?? 0;
        $midterm = $row['midterm'] ?? 0;
        $finals = $row['finals'] ?? 0;

        // Calculate weighted final grade if all three terms have valid grades (> 0)
        $overall_final_grade = 0;
        if ($prelim > 0 && $midterm > 0 && $finals > 0) {
            // Each term grade is already weighted (class_standing + exam), so we use equal weighting for terms
            $overall_final_grade = round(($prelim + $midterm + $finals) / 3, 2);
        }

        // Update the final_grade in database if it differs
        if ($overall_final_grade != ($row['final_grade'] ?? 0)) {
            $update_stmt = $pdo->prepare("UPDATE calculated_grades SET final_grade = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$overall_final_grade, $row['id']]);
        }

        // Format calculated grades data
        $updated_grades[] = [
            'id' => $row['id'],
            'class_id' => $row['class_id'],
            'teacher_email' => $row['teacher_email'],
            'student_number' => $row['student_number'],
            'student_name' => $row['first_name'] . ' ' . $row['last_name'],
            'prelim' => $row['prelim'] ?? '',
            'midterm' => $row['midterm'] ?? '',
            'finals' => $row['finals'] ?? '',
            'final_grade' => $overall_final_grade
        ];
    }

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true,
        'calculated_grades' => $updated_grades,
        'count' => count($updated_grades)
    ]);

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in get_gwa.php: ' . $e->getMessage());

    // Return generic error to user
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
