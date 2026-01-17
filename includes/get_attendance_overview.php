<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID is required']);
    exit();
}

$classId = $_GET['class_id'];

try {
    // Get total students in the class
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_students FROM student_classes WHERE class_id = ?");
    $stmt->execute([$classId]);
    $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'];

    // Get attendance statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_records,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count,
            COUNT(DISTINCT attendance_date) as total_days,
            COUNT(DISTINCT session) as total_sessions
        FROM attendance
        WHERE class_id = ?
    ");
    $stmt->execute([$classId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent attendance records (last 10 days)
    $stmt = $pdo->prepare("
        SELECT
            attendance_date,
            session,
            COUNT(*) as total_marked,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
        FROM attendance
        WHERE class_id = ?
        GROUP BY attendance_date, session
        ORDER BY attendance_date DESC, session DESC
        LIMIT 20
    ");
    $stmt->execute([$classId]);
    $recentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate overall attendance percentage
    $overallAttendance = 0;
    if ($stats['total_records'] > 0) {
        $overallAttendance = round(($stats['present_count'] / $stats['total_records']) * 100, 1);
    }

    echo json_encode([
        'success' => true,
        'overview' => [
            'total_students' => $totalStudents,
            'total_records' => $stats['total_records'],
            'total_days' => $stats['total_days'],
            'total_sessions' => $stats['total_sessions'],
            'present_count' => $stats['present_count'],
            'absent_count' => $stats['absent_count'],
            'late_count' => $stats['late_count'],
            'excused_count' => $stats['excused_count'],
            'overall_attendance_percentage' => $overallAttendance
        ],
        'recent_records' => $recentRecords
    ]);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching attendance overview']);
}
?>
