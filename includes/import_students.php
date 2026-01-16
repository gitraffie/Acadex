<?php
// Start output buffering to prevent any HTML output
ob_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    session_start();
    include 'connection.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit();
    }
    // Get class ID from form data
    $classIdRaw = $_POST['classId'] ?? null;
    if ($classIdRaw === null || $classIdRaw === '') {
        echo json_encode(['success' => false, 'message' => 'Class ID is required']);
        exit();
    }
    $classId = (int) $classIdRaw;

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit();
    }

    $teacherEmail = $_SESSION['email'];

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];

    // Validate file size (max 200MB)
    if ($fileSize > 200 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 200MB limit']);
        exit();
    }

    // Validate file type
    $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['csv', 'xls', 'xlsx'];

    if (!in_array($fileType, $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only CSV, XLS, and XLSX files are allowed']);
        exit();
    }

    $students = [];
    $errors = [];

    if ($fileExtension === 'csv') {
        // Parse CSV
        if (($handle = fopen($fileTmpName, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');
            if (!$header) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSV file']);
                exit();
            }

            // Expected columns: Student Number, Email, First Name, Last Name (required), Middle Initial, Suffix (optional)
            $requiredColumns = ['Student Number', 'Email', 'First Name', 'Last Name'];
            $optionalColumns = ['Middle Initial', 'Suffix', 'Program'];
            $headerLower = array_map('strtolower', array_map('trim', $header));

            $columnMap = [];
            foreach ($requiredColumns as $col) {
                $index = array_search(strtolower($col), $headerLower);
                if ($index === false) {
                    echo json_encode(['success' => false, 'message' => "Missing required column: $col"]);
                    exit();
                }
                $columnMap[$col] = $index;
            }

            // Optional columns
            foreach ($optionalColumns as $col) {
                $index = array_search(strtolower($col), $headerLower);
                $columnMap[$col] = $index !== false ? $index : null;
            }

            $rowNumber = 1;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNumber++;
                if (count($data) < count($requiredColumns)) {
                    $errors[] = "Row $rowNumber: Insufficient columns";
                    continue;
                }

                $studentNumber = trim($data[$columnMap['Student Number']] ?? '');
                $studentEmail = trim($data[$columnMap['Email']] ?? '');
                $firstName = trim($data[$columnMap['First Name']] ?? '');
                $lastName = trim($data[$columnMap['Last Name']] ?? '');
                $middleInitial = $columnMap['Middle Initial'] !== null ? trim($data[$columnMap['Middle Initial']] ?? '') : '';
                $suffix = $columnMap['Suffix'] !== null ? trim($data[$columnMap['Suffix']] ?? '') : '';
                $program = $columnMap['Program'] !== null ? trim($data[$columnMap['Program']] ?? '') : '';

                if (empty($studentNumber) || empty($firstName) || empty($lastName)) {
                    $errors[] = "Row $rowNumber: Missing required fields";
                    continue;
                }

                $students[] = [
                    'student_number' => $studentNumber,
                    'student_email' => $studentEmail,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_initial' => $middleInitial,
                    'suffix' => $suffix,
                    'program' => $program
                ];
            }
            fclose($handle);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to open CSV file']);
            exit();
        }
    } else {
        // For XLS/XLSX, you would need PhpSpreadsheet library
        // For now, return an error
        echo json_encode(['success' => false, 'message' => 'Excel file support requires additional libraries. Please use CSV format.']);
        exit();
    }

    if (empty($students)) {
        echo json_encode(['success' => false, 'message' => 'No valid students found in the file']);
        exit();
    }

    // Insert students into database
    $pdo->beginTransaction();
    $inserted = 0;
    $duplicates = 0;

    foreach ($students as $student) {
        // Check for duplicates
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ? LIMIT 1");
        $stmt->execute([$student['student_number']]);
        if ($stmt->fetch()) {
            $duplicates++;
            continue;
        }

        // Insert student
        $stmt = $pdo->prepare("INSERT INTO students (class_id, student_number, student_email, first_name, last_name, middle_initial, suffix, program, created_at, teacher_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([
            $classId,
            $student['student_number'],
            $student['student_email'],
            $student['first_name'],
            $student['last_name'],
            $student['middle_initial'],
            $student['suffix'],
            $student['program'],
            $teacherEmail
        ]);
        $inserted++;
    }

    $pdo->commit();

    $message = "Import completed. $inserted students added.";
    if ($duplicates > 0) {
        $message .= " $duplicates duplicates skipped.";
    }
    if (!empty($errors)) {
        $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5)); // Show first 5 errors
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('General error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while importing students']);
}
?>
