<?php
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_GET['class_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Class ID and date are required']);
    exit();
}

$classId = $_GET['class_id'];
$date = $_GET['date'];
$session = $_GET['session'];

try {
    $stmt = $pdo->prepare("
        SELECT student_id, status
        FROM attendance
        WHERE class_id = ? AND attendance_date = ? AND session = ?
        ORDER BY student_id
    ");
    $stmt->execute([$classId, $date, $session]);
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'attendance' => $attendanceRecords
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
