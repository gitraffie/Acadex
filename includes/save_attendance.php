<?php
ini_set('max_execution_time', 300); // Increase execution time to 5 minutes for email sending
include 'connection.php';
include_once 'send_email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['attendance']) || !is_array($input['attendance'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid attendance data']);
    exit();
}

$attendanceData = $input['attendance'];

try {
    $pdo->beginTransaction();

    foreach ($attendanceData as $record) {
        if (!isset($record['student_id']) || !isset($record['status']) || !isset($record['date']) || !isset($record['class_id'])) {
            throw new Exception('Missing required fields in attendance record');
        }

        $studentId = $record['student_id'];
        $status = $record['status'];
        $date = $record['date'];
        $session = $record['session'];
        $classId = $record['class_id'];

        // Get student number for the record
        $stmt = $pdo->prepare("SELECT student_number FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            throw new Exception('Student not found');
        }

        $studentNumber = $student['student_number'];

        // Check if attendance record already exists using unique combination
        $checkStmt = $pdo->prepare("
            SELECT id FROM attendance
            WHERE student_id = ? AND attendance_date = ? AND session = ? AND class_id = ?
        ");
        $checkStmt->execute([$studentId, $date, $session, $classId]);
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRecord) {
            // UPDATE existing record
            $updateStmt = $pdo->prepare("
                UPDATE attendance
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$status, $existingRecord['id']]);
        } else {
            // INSERT new record
            $insertStmt = $pdo->prepare("
                INSERT INTO attendance (class_id, student_id, student_number, attendance_date, session, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([$classId, $studentId, $studentNumber, $date, $session, $status]);
        }
    }

    $pdo->commit();

    // Send emails to students after successful save
    $emailResults = [];
    foreach ($attendanceData as $record) {
        $studentId = $record['student_id'];
        $status = $record['status'];
        $date = $record['date'];
        $session = $record['session'];
        $classId = $record['class_id'];

        // Get student details
        $stmt = $pdo->prepare("SELECT student_email, CONCAT(first_name, ' ', last_name) as student_name FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && !empty($student['student_email'])) {
            // Get class details
            $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
            $stmt->execute([$classId]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($class) {
                // Get teacher name from session (assuming teacher is logged in)
                $teacherName = $_SESSION['full_name'] ?? 'Teacher';

                // Send email
                $emailResult = emailAttendance(
                    $student['student_email'],
                    $student['student_name'],
                    $date,
                    $session,
                    $status,
                    $pdo,
                    $teacherName,
                    $class['class_name'],
                    $class['section'],
                    $class['term'],
                    $classId
                );
                $emailResults[] = $emailResult;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Attendance saved successfully',
        'email_results' => $emailResults
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); // Set appropriate error status code
    echo json_encode([
        'success' => false,
        'message' => 'Error saving attendance: ' . $e->getMessage()
    ]);
}
?>
