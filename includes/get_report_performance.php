<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Class ID required']);
    exit();
}

try {
    // Get all students with their calculated grades
    $stmt = $pdo->prepare("
        SELECT
            s.id,
            s.student_number,
            CONCAT(s.first_name, ' ', s.last_name) as name,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade
        FROM students s
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND cg.class_id = s.class_id
        WHERE s.class_id = ?
        ORDER BY cg.final_grade DESC, s.last_name ASC, s.first_name ASC
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for the frontend
    $formatted_students = array_map(function($student) {
        return [
            'id' => $student['id'],
            'student_number' => $student['student_number'],
            'name' => $student['name'],
            'prelim' => $student['prelim'] ? number_format($student['prelim'], 2) : '--',
            'midterm' => $student['midterm'] ? number_format($student['midterm'], 2) : '--',
            'finals' => $student['finals'] ? number_format($student['finals'], 2) : '--',
            'final_grade' => $student['final_grade'] ? number_format($student['final_grade'], 2) : '--'
        ];
    }, $students);

    echo json_encode([
        'success' => true,
        'students' => $formatted_students
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
