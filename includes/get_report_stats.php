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
    // Get total students in class
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM student_classes WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $total_students = $stmt->fetch()['total'];

    if ($total_students == 0) {
        echo json_encode(['success' => false, 'message' => 'No students in this class']);
        exit();
    }

    // Get calculated grades for statistics
    $stmt = $pdo->prepare("SELECT * FROM calculated_grades WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $passing_students = 0;
    $failing_students = 0;
    $total_final_grades = 0;
    $valid_grades_count = 0;
    $top_performer = null;
    $top_score = 0;

    foreach ($grades as $grade) {
        if ($grade['final_grade'] > 0) {
            $total_final_grades += $grade['final_grade'];
            $valid_grades_count++;

            if ($grade['final_grade'] >= 75) {
                $passing_students++;
            } else {
                $failing_students++;
            }

            if ($grade['final_grade'] > $top_score) {
                $top_score = $grade['final_grade'];
                
                // Fetch student name
                $stmt_student_name = $pdo->prepare("
                    SELECT s.first_name, s.last_name
                    FROM students s
                    JOIN student_classes sc ON sc.student_id = s.id
                    WHERE s.student_number = ? AND sc.class_id = ?
                    LIMIT 1
                ");
                $stmt_student_name->execute([$grade['student_number'], $class_id]);
                $student_name_data = $stmt_student_name->fetch(PDO::FETCH_ASSOC);

                if ($student_name_data) {
                    $top_performer = htmlspecialchars($student_name_data['first_name'] . ' ' . $student_name_data['last_name']);
                } else {
                    $top_performer = 'N/A'; // Fallback if student name not found
                }
            }
        }
    }

    $class_average = $valid_grades_count > 0 ? round($total_final_grades / $valid_grades_count, 2) : 0;
    $passing_percentage = $valid_grades_count > 0 ? round(($passing_students / $valid_grades_count) * 100, 1) : 0;

    // Get attendance rate
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
            COUNT(*) as total_sessions
        FROM attendance
        WHERE class_id = ?
    ");
    $stmt->execute([$class_id]);
    $attendance = $stmt->fetch();

    $attendance_rate = $attendance['total_sessions'] > 0
        ? round(($attendance['present_count'] / $attendance['total_sessions']) * 100, 1)
        : 0;

    $stats = [
        'total_students' => $total_students,
        'passing_students' => $passing_students,
        'passing_percentage' => $passing_percentage,
        'class_average' => $class_average,
        'failing_students' => $failing_students,
        'attendance_rate' => $attendance_rate,
        'top_performer' => $top_performer
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
