<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer-master/src/PHPMailer.php';
require '../vendor/PHPMailer-master/src/Exception.php';
require '../vendor/PHPMailer-master/src/SMTP.php';

function ensureEmailLogTable($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_email VARCHAR(255) NOT NULL,
            student_email VARCHAR(255) NOT NULL,
            class_id INT NULL,
            email_type VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_teacher_date (teacher_email, created_at)
        )
    ");
}

function logEmailSend($pdo, $teacher_email, $student_email, $email_type, $class_id = null) {
    if (empty($teacher_email) || empty($student_email)) {
        return;
    }

    try {
        ensureEmailLogTable($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (teacher_email, student_email, class_id, email_type, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$teacher_email, $student_email, $class_id, $email_type]);
    } catch (PDOException $e) {
        // Fail silently to avoid blocking email send on log issues.
    }
}

function resolveStudentRequest($pdo, $teacher_email, $student_email, $request_type, $class_id = null, $term = null, $resolve_all_terms = false) {
    if (empty($teacher_email) || empty($student_email) || empty($request_type)) {
        return;
    }

    $params = [$teacher_email, $student_email, $request_type];
    $conditions = "teacher_email = ? AND student_email = ? AND request_type = ? AND status = 'pending'";

    if ($class_id !== null) {
        $conditions .= " AND (class_id = ? OR class_id IS NULL)";
        $params[] = $class_id;
    }

    if (!$resolve_all_terms && $term !== null) {
        $conditions .= " AND term = ?";
        $params[] = $term;
    }

    $paramsWithResolver = array_merge([$teacher_email], $params);

    try {
        $stmt = $pdo->prepare("
            UPDATE student_requests
            SET status = 'resolved',
                resolved_at = NOW(),
                resolved_by = ?
            WHERE $conditions
        ");
        $stmt->execute($paramsWithResolver);
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                UPDATE student_requests
                SET status = 'resolved'
                WHERE $conditions
            ");
            $stmt->execute($params);
        } catch (PDOException $ignored) {
        }
    }
}

// ---------------- EMAIL FUNCTION ------------------

if (!function_exists('emailTermGrade')) {
function emailTermGrade($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name, $term, $grades, $total_grade) {
    // Fetch class details
    $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_row) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        return;
    }
    $class_name = $class_row['class_name'];
    $section = $class_row['section'];
    $semester = $class_row['term'];

    $rows = "";
    $componentsCount = count($grades);
    $firstRow = true;

    foreach ($grades as $comp => $value) {
        $label = ucfirst(str_replace('_', ' ', $comp));

        if ($firstRow) {
            // First row includes the TERM merged across all rows
            $rows .= "
                <tr>
                    <td rowspan='{$componentsCount}' style='vertical-align: middle; font-weight: bold;'>" . ucfirst($term) . "</td>
                    <td>$label</td>
                    <td>$value</td>
                </tr>
            ";
            $firstRow = false;
        } else {
            // Remaining rows only include component + grade
            $rows .= "
                <tr>
                    <td>$label</td>
                    <td>$value</td>
                </tr>
            ";
        }
    }

    // Add the final grade row
    $rows .= "
        <tr>
            <td colspan='2'><strong>Final Grade</strong></td>
            <td><strong>$total_grade</strong></td>
        </tr>
    ";

    // Build HTML email body
    $html_body = "
        <html><body>
        <h2>Grade Notification</h2>
        <p>
            <table cellpadding='0' cellspacing='0' style='border-collapse: collapse; border: none;'>
                <tr>
                    <td><strong>Class:</strong> $class_name</td>
                    <td style='padding-left:25px;'><strong>Semester:</strong> $semester</td>
                </tr>
                <tr>
                    <td><strong>Name:</strong> $student_name</td>
                    <td style='padding-left:25px;'><strong>Section:</strong> $section</td>
                </tr>
                <tr>
                    <td><strong>Student Number:</strong> $student_number</td>
                    <td></td>
                </tr>
            </table>
        </p>

        <table border='1' cellpadding='6' cellspacing='0'>
            <tr>
                <th>Term</th>
                <th>Component</th>
                <th>Grade</th>
            </tr>
            $rows
        </table>

        <p>Best regards,<br>{$teacher_name}</p>
        </body></html>
        ";

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex - Grade Reports');
        $mail->addAddress($student_email);

        $mail->isHTML(true);
        $mail->Subject = "Grade Report for $class_name";
        $mail->Body = $html_body;

        $mail->send();
        $teacherEmail = $_SESSION['email'] ?? null;
        logEmailSend($pdo, $teacherEmail, $student_email, 'term_grades', $class_id);
        resolveStudentRequest($pdo, $teacherEmail, $student_email, 'grade', $class_id, $term, false);
        echo json_encode(['success' => true, 'message' => 'Email sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
    }
}

function emailSpecificGrade($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name, $term, $normalizedComponent, $grade) {
    // Fetch class details
    $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_row) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        return;
    }
    $class_name = $class_row['class_name'];
    $section = $class_row['section'];
    $semester = $class_row['term'];

    // Build HTML email body
    $html_body = "
    <html><body>
    <h2>Grade Notification</h2>
    <p>
        <table cellpadding='0' cellspacing='0' style='border-collapse: collapse; border: none;'>
            <tr>
                <td><strong>Class:</strong> $class_name</td>
                <td style='padding-left:25px;'><strong>Semester:</strong> $semester</td>
            </tr>
            <tr>
                <td><strong>Name:</strong> $student_name</td>
                <td style='padding-left:25px;'><strong>Section:</strong> $section</td>
            </tr>
            <tr>
                <td><strong>Student Number:</strong> $student_number</td>
                <td></td>
            </tr>
        </table>
    </p>

    <table border='1' cellpadding='6' cellspacing='0'>
        <tr>
            <th>Term</th><th>Component</th><th>Grade</th>
        </tr>
        <tr>
            <td>" . ucfirst($term) . "</td>
            <td>$normalizedComponent</td>
            <td>$grade</td>
        </tr>
    </table>

    <p>Best regards,<br>{$teacher_name}</p>
    </body></html>
    ";

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex - Grade Reports');
        $mail->addAddress($student_email);

        $mail->isHTML(true);
        $mail->Subject = "Grade Report for $class_name";
        $mail->Body = $html_body;

        $mail->send();
        $teacherEmail = $_SESSION['email'] ?? null;
        logEmailSend($pdo, $teacherEmail, $student_email, 'component_grade', $class_id);
        resolveStudentRequest($pdo, $teacherEmail, $student_email, 'grade', $class_id, $term, false);
        echo json_encode(['success' => true, 'message' => 'Email sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
    }
}

function emailStudentReport($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name) {
    // Fetch class details
    $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class_row) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        return;
    }
    $class_name = $class_row['class_name'];
    $section = $class_row['section'];
    $semester = $class_row['term'];

    // Prepare default grade template
    $grades_data = [
        'prelim' => ['class_standing' => '--', 'exam' => '--', 'term_grade' => '--'],
        'midterm' => ['class_standing' => '--', 'exam' => '--', 'term_grade' => '--'],
        'finals' => ['class_standing' => '--', 'exam' => '--', 'term_grade' => '--'],
        'final_grade' => '--'
    ];

    // Fetch raw component scores
    $stmt = $pdo->prepare("
        SELECT term, class_standing, exam
        FROM grades
        WHERE student_number = ? AND class_id = ?
    ");
    $stmt->execute([$student_number, $class_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $term = strtolower($row['term']);
        if (isset($grades_data[$term])) {
            $grades_data[$term]['class_standing'] = $row['class_standing'] ?? '--';
            $grades_data[$term]['exam'] = $row['exam'] ?? '--';
        }
    }

    // Fetch calculated term grades + final grade
    $stmt = $pdo->prepare("
        SELECT prelim, midterm, finals, final_grade
        FROM calculated_grades
        WHERE student_number = ? AND class_id = ?
    ");
    $stmt->execute([$student_number, $class_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $grades_data['prelim']['term_grade'] = $row['prelim'] ?? '--';
        $grades_data['midterm']['term_grade'] = $row['midterm'] ?? '--';
        $grades_data['finals']['term_grade'] = $row['finals'] ?? '--';
        $grades_data['final_grade'] = $row['final_grade'] ?? '--';
    }

    // Build HTML email body
    $html_body = "
    <html><body>
    <h2>Grade Report</h2>
    <p>
        <table cellpadding='0' cellspacing='0' style='border-collapse: collapse; border: none;'>
            <tr>
                <td><strong>Class:</strong> $class_name</td>
                <td style='padding-left:25px;'><strong>Semester:</strong> $semester</td>
            </tr>
            <tr>
                <td><strong>Name:</strong> $student_name</td>
                <td style='padding-left:25px;'><strong>Section:</strong> $section</td>
            </tr>
            <tr>
                <td><strong>Student Number:</strong> $student_number</td>
                <td></td>
            </tr>
        </table>
    </p>

    <table border='1' cellpadding='6' cellspacing='0'>
        <tr>
            <th>Term</th><th>Class Standing</th><th>Exam</th>
            <th>Term Grade</th>
        </tr>
        <tr>
            <td>Prelim</td>
            <td>{$grades_data['prelim']['class_standing']}</td>
            <td>{$grades_data['prelim']['exam']}</td>
            <td>{$grades_data['prelim']['term_grade']}</td>
        </tr>
        <tr>
            <td>Midterm</td>
            <td>{$grades_data['midterm']['class_standing']}</td>
            <td>{$grades_data['midterm']['exam']}</td>
            <td>{$grades_data['midterm']['term_grade']}</td>
        </tr>
        <tr>
            <td>Finals</td>
            <td>{$grades_data['finals']['class_standing']}</td>
            <td>{$grades_data['finals']['exam']}</td>
            <td>{$grades_data['finals']['term_grade']}</td>
        </tr>
        <tr>
            <td colspan='3'><strong>Final Grade</strong></td>
            <td><strong>{$grades_data['final_grade']}</strong></td>
        </tr>
    </table>
    <p>Best regards,<br>{$teacher_name}</p>
    </body></html>
    ";

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex - Grade Reports');
        $mail->addAddress($student_email);

        $mail->isHTML(true);
        $mail->Subject = "Grade Report for $class_name";
        $mail->Body = $html_body;

        $mail->send();
        $teacherEmail = $_SESSION['email'] ?? null;
        logEmailSend($pdo, $teacherEmail, $student_email, 'student_report', $class_id);
        resolveStudentRequest($pdo, $teacherEmail, $student_email, 'grade', $class_id, null, true);
        echo json_encode(['success' => true, 'message' => 'Email sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
    }
}

// Function to send attendance email
function emailAttendance($student_email, $student_name, $date, $session, $status, $pdo, $teacher_name, $class_name, $section, $semester, $class_id = null) {
    // Build HTML email body
    $html_body = "
        <html><body>
        <h2>Attendance Notification</h2>
        <p>
            <table cellpadding='0' cellspacing='0' style='border-collapse: collapse; border: none;'>
                <tr>
                    <td><strong>Class:</strong> $class_name</td>
                    <td style='padding-left:25px;'><strong>Semester:</strong> $semester</td>
                </tr>
                <tr>
                    <td><strong>Name:</strong> $student_name</td>
                    <td style='padding-left:25px;'><strong>Section:</strong> $section</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong> $date</td>
                    <td style='padding-left:25px;'><strong>Session:</strong> " . ucfirst($session) . "</td>
                </tr>
            </table>
        </p>

        <div style='margin-top:20px; border: 1px solid #ABCCFF; border-radius: 10px; padding:10px; text-align: center;'>
            " . ($status == 'present' ? 'You were marked as <strong>Present</strong>.' : ($status == 'absent' ? 'You were marked as <strong>Absent</strong>.' : ($status == 'late' ? 'You were marked as <strong>Late</strong>.' : 'You were marked as <strong>Excused</strong>.'))) . "
        </div>

        <p>Best regards,<br>{$teacher_name}</p>
        </body></html>
        ";

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex - Attendance Reports');
        $mail->addAddress($student_email);

        $mail->isHTML(true);
        $mail->Subject = "Attendance Report for $class_name";
        $mail->Body = $html_body;

        $mail->send();
        $teacherEmail = $_SESSION['email'] ?? null;
        logEmailSend($pdo, $teacherEmail, $student_email, 'attendance', $class_id);
        resolveStudentRequest($pdo, $teacherEmail, $student_email, 'attendance', $class_id, null, false);
        return ['success' => true, 'message' => 'Email sent'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
}

function emailAttendanceHistory($student_email, $student_name, $records, $pdo, $teacher_name, $class_id = null) {
    if (empty($records)) {
        echo json_encode(['success' => false, 'message' => 'No attendance records provided']);
        return;
    }

    $class_name = 'Class';
    $section = '';
    $semester = '';
    if ($class_id) {
        $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($class_row) {
            $class_name = $class_row['class_name'];
            $section = $class_row['section'];
            $semester = $class_row['term'];
        }
    }

    $rows = '';
    foreach ($records as $record) {
        $dateText = !empty($record['attendance_date'])
            ? date('M d, Y', strtotime($record['attendance_date']))
            : ($record['display_date'] ?? '');
        $session = $record['session'] ?? '';
        $status = strtoupper($record['status'] ?? '');
        $rows .= "
            <tr>
                <td>{$dateText}</td>
                <td>" . ($session ? ucfirst($session) : '-') . "</td>
                <td>{$status}</td>
            </tr>
        ";
    }

    $html_body = "
        <html><body>
        <h2>Attendance Record</h2>
        <p>
            <table cellpadding='0' cellspacing='0' style='border-collapse: collapse; border: none;'>
                <tr>
                    <td><strong>Class:</strong> {$class_name}</td>
                    <td style='padding-left:25px;'><strong>Semester:</strong> {$semester}</td>
                </tr>
                <tr>
                    <td><strong>Name:</strong> {$student_name}</td>
                    <td style='padding-left:25px;'><strong>Section:</strong> {$section}</td>
                </tr>
            </table>
        </p>

        <table border='1' cellpadding='6' cellspacing='0'>
            <tr>
                <th>Date</th>
                <th>Session</th>
                <th>Status</th>
            </tr>
            {$rows}
        </table>

        <p>Best regards,<br>{$teacher_name}</p>
        </body></html>
    ";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'acadex3@gmail.com';
        $mail->Password = 'ipit lqby byab gtob';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('acadex3@gmail.com', 'Acadex - Attendance Records');
        $mail->addAddress($student_email);

        $mail->isHTML(true);
        $mail->Subject = "Attendance Record for {$class_name}";
        $mail->Body = $html_body;

        $mail->send();
        $teacherEmail = $_SESSION['email'] ?? null;
        logEmailSend($pdo, $teacherEmail, $student_email, 'attendance_history', $class_id);
        resolveStudentRequest($pdo, $teacherEmail, $student_email, 'attendance', $class_id, null, false);
        echo json_encode(['success' => true, 'message' => 'Email sent']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
    }
}

// Set JSON header
header('Content-Type: application/json');

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? null;

if ($action === 'email_student_report') {

    $student_email = $_POST['student_email'] ?? null;
    $student_name = $_POST['student_name'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $student_number = $_POST['student_number'] ?? null;
    $teacher_name = $_POST['teacher_name'] ?? null;

    if (!$student_email || !$student_name || !$class_id || !$student_number) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    emailStudentReport($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name);
    exit;
}

if ($action == 'email_student_component_grade') {

    $student_email = $_POST['student_email'] ?? null;
    $student_name = $_POST['student_name'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $student_number = $_POST['student_number'] ?? null;
    $teacher_name = $_POST['teacher_name'] ?? null;
    $term = $_POST['term'] ?? null;
    $component = $_POST['component'] ?? null;
    $grade = $_POST['grade'] ?? null;

    if (!$student_email || !$class_id || !$grade || !$student_name || !$student_number || !$term || !$component) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $normalizedComponent = '';
    if ($component === 'class_standing') {
        $normalizedComponent = 'Class Standing';
    } elseif ($component === 'exam') {
        $normalizedComponent = 'Exam';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid component']);
        exit;
    }

    emailSpecificGrade($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name, $term, $normalizedComponent, $grade);
    exit;
}

if ($action == 'email_student_term_grades') {

    $student_email = $_POST['student_email'] ?? null;
    $student_name = $_POST['student_name'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $student_number = $_POST['student_number'] ?? null;
    $teacher_name = $_POST['teacher_name'] ?? null;
    $term = $_POST['term'] ?? null;
    $grades = json_decode($_POST['grade'], true);
    $total_grade = $_POST['total_grade'] ?? null;

    if (!$student_email || !$class_id || !$grades || !$student_name || !$student_number || !$term) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    emailTermGrade($student_email, $student_name, $class_id, $student_number, $pdo, $teacher_name, $term, $grades, $total_grade);
    exit;
}

if ($action == 'email_attendance') {
    $student_email = $_POST['student_email'] ?? null;
    $student_name = $_POST['student_name'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $session = $_POST['session'] ?? null;
    $status = $_POST['status'] ?? null;
    $teacher_name = $_POST['teacher_name'] ?? null;

    if (!$student_email || !$student_name || !$class_id || !$date || !$session || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Get class details
    $stmt = $pdo->prepare("SELECT class_name, section, term FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit;
    }

    emailAttendance($student_email, $student_name, $date, $session, $status, $pdo, $teacher_name, $class['class_name'], $class['section'], $class['term'], $class_id);
    exit;
}

if ($action == 'email_attendance_history') {
    $student_id = $_POST['student_id'] ?? null;
    $student_email = $_POST['student_email'] ?? null;
    $student_name = $_POST['student_name'] ?? null;
    $class_id = $_POST['class_id'] ?? null;
    $records = json_decode($_POST['records'] ?? '[]', true);
    $teacher_name = $_POST['teacher_name'] ?? null;

    if (!$student_id || !$student_email || !$student_name || empty($records)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT sc.id
        FROM student_classes sc
        JOIN classes c ON c.id = sc.class_id AND c.user_email = ?
        WHERE sc.student_id = ? AND sc.class_id = ?
    ");
    $stmt->execute([$_SESSION['email'] ?? '', $student_id, $class_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    emailAttendanceHistory($student_email, $student_name, $records, $pdo, $teacher_name, $class_id);
    exit;
}
?>
