<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$teacherEmail = $_SESSION['email'] ?? null;

if ($studentId <= 0 || $classId <= 0 || !$teacherEmail) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_email, s.first_name, s.last_name, s.middle_initial, s.suffix,
               c.class_name, c.section, c.term
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id AND sc.class_id = ?
        JOIN classes c ON c.id = sc.class_id AND c.user_email = ?
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->execute([$classId, $teacherEmail, $studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    $fullName = trim(
        $student['first_name'] . ' ' .
        (!empty($student['middle_initial']) ? $student['middle_initial'] . '. ' : '') .
        $student['last_name'] .
        (!empty($student['suffix']) ? ' ' . $student['suffix'] : '')
    );

    $stmt = $pdo->prepare("
        SELECT attendance_date, status, session, class_id
        FROM attendance
        WHERE student_id = ? AND class_id = ?
        ORDER BY attendance_date DESC, created_at DESC
        LIMIT 200
    ");
    $stmt->execute([$studentId, $classId]);
    $rows = $stmt->fetchAll();

    $history = array_map(function ($row) {
        return [
            'attendance_date' => $row['attendance_date'],
            'session' => $row['session'],
            'status' => $row['status'],
            'class_id' => $row['class_id'],
            'display_date' => date('M d, Y', strtotime($row['attendance_date'])) . (!empty($row['session']) ? ' (' . $row['session'] . ')' : '')
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'student' => [
            'id' => $studentId,
            'name' => $fullName,
            'email' => $student['student_email'],
            'class_id' => $classId,
            'class_name' => $student['class_name'],
            'section' => $student['section'],
            'term' => $student['term']
        ],
        'history' => $history
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
