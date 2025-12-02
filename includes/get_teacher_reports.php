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

    // Get teacher email
    $teacher_email = $_SESSION['email'] ?? null;
    if (!$teacher_email) {
        throw new Exception('Teacher email not found');
    }

    // Get all classes for this teacher
    $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE user_email = ? AND archived = 0");
    $stmt->execute([$teacher_email]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $class_ids = array_column($classes, 'id');

    if (empty($class_ids)) {
        // No classes, return empty stats
        ob_clean();
        echo json_encode([
            'success' => true,
            'statistics' => [
                'total_students' => 0,
                'average_grade' => 0,
                'attendance_rate' => 0,
                'at_risk_students' => 0
            ],
            'grade_distribution' => [
                '90-100' => 0,
                '85-89' => 0,
                '80-84' => 0,
                '75-79' => 0,
                '70-74' => 0,
                '69 and below' => 0
            ],
            'performance_trends' => [],
            'classes' => []
        ]);
        exit;
    }

    // Get total students across all classes
    $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id IN ($placeholders)");
    $stmt->execute($class_ids);
    $total_students = $stmt->fetch()['total'];

    // Get calculated grades for all students in teacher's classes
    $stmt = $pdo->prepare("
        SELECT cg.final_grade, cg.prelim, cg.midterm, cg.finals, s.class_id
        FROM calculated_grades cg
        JOIN students s ON cg.student_number = s.student_number AND cg.class_id = s.class_id
        WHERE s.class_id IN ($placeholders)
    ");
    $stmt->execute($class_ids);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $total_grades = 0;
    $grade_count = 0;
    $at_risk_count = 0;
    $grade_distribution = [
        '90-100' => 0,
        '85-89' => 0,
        '80-84' => 0,
        '75-79' => 0,
        '70-74' => 0,
        '69 and below' => 0
    ];

    foreach ($grades as $grade) {
        if ($grade['final_grade'] > 0) {
            $total_grades += $grade['final_grade'];
            $grade_count++;

            // Grade distribution
            $g = $grade['final_grade'];
            if ($g >= 90) $grade_distribution['90-100']++;
            elseif ($g >= 85) $grade_distribution['85-89']++;
            elseif ($g >= 80) $grade_distribution['80-84']++;
            elseif ($g >= 75) $grade_distribution['75-79']++;
            elseif ($g >= 70) $grade_distribution['70-74']++;
            else $grade_distribution['69 and below']++;

            // At risk students (below 75)
            if ($g < 75) $at_risk_count++;
        }
    }

    $average_grade = $grade_count > 0 ? round($total_grades / $grade_count, 2) : 0;

    // Get attendance rate (last 30 days)
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN status = 'Present' THEN 1 END) as present_count,
            COUNT(*) as total_records
        FROM attendance
        WHERE class_id IN ($placeholders) AND date >= ?
    ");
    $stmt->execute(array_merge($class_ids, [$thirty_days_ago]));
    $attendance_data = $stmt->fetch();
    $attendance_rate = $attendance_data['total_records'] > 0
        ? round(($attendance_data['present_count'] / $attendance_data['total_records']) * 100, 2)
        : 0;

    // Get performance trends (monthly averages for last 6 months)
    $performance_trends = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));

        $stmt = $pdo->prepare("
            SELECT AVG(cg.final_grade) as avg_grade
            FROM calculated_grades cg
            JOIN students s ON cg.student_number = s.student_number AND cg.class_id = s.class_id
            WHERE s.class_id IN ($placeholders) AND cg.created_at BETWEEN ? AND ?
        ");
        $stmt->execute(array_merge($class_ids, [$month_start . ' 00:00:00', $month_end . ' 23:59:59']));
        $trend_data = $stmt->fetch();
        $performance_trends[] = [
            'month' => date('M Y', strtotime($month_start)),
            'average' => round($trend_data['avg_grade'] ?? 0, 2)
        ];
    }

    // Get class list for filters
    $class_list = array_map(function($class) {
        return [
            'id' => $class['id'],
            'name' => $class['class_name']
        ];
    }, $classes);

    // Clean output buffer
    ob_clean();

    echo json_encode([
        'success' => true,
        'statistics' => [
            'total_students' => (int)$total_students,
            'average_grade' => $average_grade,
            'attendance_rate' => $attendance_rate,
            'at_risk_students' => $at_risk_count
        ],
        'grade_distribution' => $grade_distribution,
        'performance_trends' => $performance_trends,
        'classes' => $class_list
    ]);

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in get_teacher_reports.php: ' . $e->getMessage());

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
