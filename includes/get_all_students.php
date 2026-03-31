<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Fetch all students with enrollment counts
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_number, s.student_email, s.first_name, s.last_name, s.middle_initial, s.suffix, s.program, s.created_at,
               COUNT(sc.class_id) as class_count
        FROM students s
        LEFT JOIN student_classes sc ON sc.student_id = s.id
        GROUP BY s.id
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute();
    $rawStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format students data with full_name
    $students = [];
    foreach ($rawStudents as $student) {
        $mi = trim((string)($student['middle_initial'] ?? ''));
        $miInitial = $mi !== '' ? mb_strtoupper(mb_substr($mi, 0, 1, 'UTF-8'), 'UTF-8') : '';
        $firstName = mb_strtoupper(trim((string)$student['first_name']), 'UTF-8');
        $lastName = mb_strtoupper(trim((string)$student['last_name']), 'UTF-8');
        $suffix = !empty($student['suffix']) ? ' ' . mb_strtoupper(trim((string)$student['suffix']), 'UTF-8') : '';
        $fullName = trim($firstName . ' ' . ($miInitial ? $miInitial . ' ' : '') . $lastName . $suffix);
        $students[] = [
            'id' => $student['id'],
            'class_count' => (int)($student['class_count'] ?? 0),
            'student_number' => $student['student_number'],
            'student_email' => $student['student_email'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'middle_initial' => $student['middle_initial'],
            'suffix' => $student['suffix'],
            'program' => $student['program'],
            'created_at' => $student['created_at'],
            'full_name' => $fullName
        ];
    }

    echo json_encode(['success' => true, 'students' => $students]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching students']);
}
?>
