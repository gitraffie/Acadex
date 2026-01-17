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
    $student_id = $_GET['student_id'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Validate class_id is numeric
    if (!is_numeric($class_id)) {
        throw new Exception('Invalid class ID');
    }

    // Validate student_id if provided
    if ($student_id !== null && !is_numeric($student_id)) {
        throw new Exception('Invalid student ID');
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

    // Build query to get grades
    $query = "
        SELECT
            g.id as grade_id,
            g.student_number,
            g.class_standing,
            g.exam,
            g.term,
            s.id as student_id,
            s.first_name,
            s.last_name
        FROM grades g
        INNER JOIN students s ON g.student_number = s.student_number
        INNER JOIN student_classes sc ON sc.student_id = s.id AND sc.class_id = g.class_id
        WHERE g.class_id = ?
    ";
    
    $params = [$class_id];

    // Add student filter if provided
    if ($student_id) {
        $query .= " AND s.id = ?";
        $params[] = $student_id;
    }

    $query .= " ORDER BY s.last_name, s.first_name, g.term";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format grades data as flat array
    $grades = [];
    foreach ($results as $row) {
        $grades[] = [
            'student_id' => $row['student_id'],
            'student_number' => $row['student_number'],
            'term' => $row['term'] ?? 'Unknown',
            'class_standing' => $row['class_standing'] ?? '',
            'exam' => $row['exam'] ?? '',
            'student_name' => $row['first_name'] . ' ' . $row['last_name']
        ];
    }

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true, 
        'grades' => $grades,
        'count' => count($grades)
    ]);

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in get_grades.php: ' . $e->getMessage());
    
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
