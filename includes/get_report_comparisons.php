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
    // Get current term averages (assuming Finals is the most recent)
    $stmt = $pdo->prepare("
        SELECT
            AVG(prelim) as current_prelim_avg,
            AVG(midterm) as current_midterm_avg,
            AVG(finals) as current_finals_avg
        FROM calculated_grades
        WHERE class_id = ? AND prelim > 0 AND midterm > 0 AND finals > 0
    ");
    $stmt->execute([$class_id]);
    $current_averages = $stmt->fetch(PDO::FETCH_ASSOC);

    // For comparison, we'll use prelim vs midterm as current vs previous
    // In a real scenario, you'd compare different academic years or semesters
    $current_average = $current_averages['current_finals_avg'] ?? 0;
    $previous_average = $current_averages['current_midterm_avg'] ?? 0;
    $change = $previous_average > 0 ? round($current_average - $previous_average, 2) : 0;

    // Get attendance comparison (current month vs previous month)
    $current_month = date('Y-m');
    $previous_month = date('Y-m', strtotime('-1 month'));

    // Current month attendance
    $stmt = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
            COUNT(*) as total_sessions
        FROM attendance
        WHERE class_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ?
    ");
    $stmt->execute([$class_id, $current_month]);
    $current_attendance = $stmt->fetch();

    // Previous month attendance
    $stmt->execute([$class_id, $previous_month]);
    $previous_attendance = $stmt->fetch();

    $current_attendance_rate = $current_attendance['total_sessions'] > 0
        ? round(($current_attendance['present_count'] / $current_attendance['total_sessions']) * 100, 1)
        : 0;

    $previous_attendance_rate = $previous_attendance['total_sessions'] > 0
        ? round(($previous_attendance['present_count'] / $previous_attendance['total_sessions']) * 100, 1)
        : 0;

    $attendance_change = round($current_attendance_rate - $previous_attendance_rate, 1);

    $comparisons = [
        'current_average' => round($current_average, 2),
        'previous_average' => round($previous_average, 2),
        'change' => $change,
        'current_attendance' => $current_attendance_rate,
        'previous_attendance' => $previous_attendance_rate,
        'attendance_change' => $attendance_change
    ];

    echo json_encode(['success' => true, 'comparisons' => $comparisons]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
