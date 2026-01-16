<?php
header('Content-Type: application/json');
include 'connection.php';

$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit;
}

try {
    // Grade distribution
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN final_grade >= 90 THEN 'Excellent (90-100)'
                WHEN final_grade >= 80 THEN 'Good (80-89)'
                WHEN final_grade >= 75 THEN 'Passing (75-79)'
                ELSE 'Failing (<75)'
            END as grade_range,
            COUNT(*) as count
        FROM calculated_grades
        WHERE class_id = ? AND final_grade IS NOT NULL
        GROUP BY grade_range
        ORDER BY
            CASE grade_range
                WHEN 'Excellent (90-100)' THEN 1
                WHEN 'Good (80-89)' THEN 2
                WHEN 'Passing (75-79)' THEN 3
                ELSE 4
            END
    ");
    $stmt->execute([$class_id]);
    $grade_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grade_distribution = [
        'labels' => array_column($grade_dist, 'grade_range'),
        'values' => array_column($grade_dist, 'count')
    ];

    // Attendance data
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count
        FROM attendance
        WHERE class_id = ?
        GROUP BY status
    ");
    $stmt->execute([$class_id]);
    $attendance_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $attendance_data_formatted = [
        'values' => [
            $attendance_data['present'] ?? 0,
            $attendance_data['absent'] ?? 0,
            $attendance_data['late'] ?? 0,
            $attendance_data['excused'] ?? 0,
            $attendance_data['unexcused'] ?? 0
        ]
    ];

    // Performance trend (average grades by term - simulated since we don't have historical data)
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN term = 'prelim' THEN 'Prelim'
                WHEN term = 'midterm' THEN 'Midterm'
                WHEN term = 'finals' THEN 'Finals'
            END as term_name,
            AVG(class_standing + exam) as average
        FROM grades
        WHERE class_id = ?
        GROUP BY term
        ORDER BY
            CASE term
                WHEN 'prelim' THEN 1
                WHEN 'midterm' THEN 2
                WHEN 'finals' THEN 3
            END
    ");
    $stmt->execute([$class_id]);
    $trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $performance_trend = [
        'labels' => array_column($trend_data, 'term_name'),
        'values' => array_map(function($val) { return round($val, 2); }, array_column($trend_data, 'average'))
    ];

    // Pass/Fail ratio
    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN final_grade >= 75 THEN 1 ELSE 0 END) as passing,
            SUM(CASE WHEN final_grade < 75 THEN 1 ELSE 0 END) as failing
        FROM calculated_grades
        WHERE class_id = ? AND final_grade IS NOT NULL
    ");
    $stmt->execute([$class_id]);
    $pass_fail = $stmt->fetch();

    $pass_fail_data = [
        'passing' => (int)$pass_fail['passing'],
        'failing' => (int)$pass_fail['failing']
    ];

    // Term comparison
    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN term = 'prelim' THEN 'Prelim'
                WHEN term = 'midterm' THEN 'Midterm'
                WHEN term = 'finals' THEN 'Finals'
            END as term_name,
            AVG(class_standing + exam) as average
        FROM grades
        WHERE class_id = ?
        GROUP BY term
        ORDER BY
            CASE term
                WHEN 'prelim' THEN 1
                WHEN 'midterm' THEN 2
                WHEN 'finals' THEN 3
            END
    ");
    $stmt->execute([$class_id]);
    $term_comp = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $term_comparison = [
        'values' => array_map(function($val) { return round($val, 2); }, array_column($term_comp, 'average'))
    ];

    $charts_data = [
        'grade_distribution' => $grade_distribution,
        'attendance_data' => $attendance_data_formatted,
        'performance_trend' => $performance_trend,
        'pass_fail' => $pass_fail_data,
        'term_comparison' => $term_comparison
    ];

    echo json_encode(['success' => true] + $charts_data);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
