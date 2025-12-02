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
        http_response_code(401);
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Method not allowed');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        throw new Exception('Invalid JSON input');
    }

    $class_id = $input['class_id'] ?? null;
    $teacher_email = $input['teacher_email'] ?? null;
    $class_standing = $input['class_standing'] ?? null;
    $exam = $input['exam'] ?? null;

    // Validate required fields
    if (!$class_id || !$teacher_email || $class_standing === null || $exam === null) {
        http_response_code(400);
        throw new Exception('Missing required fields: class_id, teacher_email, class_standing, exam');
    }

    // Convert to float for calculations
    $class_standing = floatval($class_standing);
    $exam = floatval($exam);

    // Validate numeric values
    if (!is_numeric($class_standing) || !is_numeric($exam)) {
        http_response_code(400);
        throw new Exception('Weights must be numeric values');
    }

    // Validate ranges - Frontend sends decimals (0-1), but also accept percentages (0-100)
    // If values are > 1, treat as percentages and convert to decimals
    if ($class_standing > 1) {
        $class_standing = $class_standing / 100;
    }
    if ($exam > 1) {
        $exam = $exam / 100;
    }

    // Validate weights are in valid range
    if ($class_standing < 0 || $exam < 0) {
        http_response_code(400);
        throw new Exception('Weights cannot be negative');
    }

    if ($class_standing > 1 || $exam > 1) {
        http_response_code(400);
        throw new Exception('Weights cannot exceed 1.0 (100%)');
    }

    // Validate total equals 1.0 (100%)
    // Allow for small floating point errors
    $total = $class_standing + $exam;
    if (abs($total - 1.0) > 0.01) {
        http_response_code(400);
        throw new Exception('Weights must sum to 1.0 (100%). Current total: ' . round($total * 100, 2) . '%');
    }

    // Verify teacher owns this class (security check)
    $stmt = $pdo->prepare("SELECT id, user_email FROM classes WHERE id = ? AND user_email = ?");
    $stmt->execute([$class_id, $teacher_email]);
    $class = $stmt->fetch();
    
    if (!$class) {
        http_response_code(403);
        throw new Exception('You do not have permission to set weights for this class');
    }

    // Round to 2 decimal places for storage
    $class_standing = round($class_standing, 2);
    $exam = round($exam, 2);

    // Begin transaction for data consistency
    $pdo->beginTransaction();

    try {
        // Check if weights already exist for this class
        $checkStmt = $pdo->prepare("SELECT id FROM weights WHERE class_id = ?");
        $checkStmt->execute([$class_id]);
        $existingWeight = $checkStmt->fetch();

        if ($existingWeight) {
            // UPDATE existing record
            $stmt = $pdo->prepare("
                UPDATE weights 
                SET class_standing = ?, 
                    exam = ?, 
                    teacher_email = ?,
                    updated_at = NOW()
                WHERE class_id = ?
            ");
            $stmt->execute([$class_standing, $exam, $teacher_email, $class_id]);
            $action = 'updated';
        } else {
            // INSERT new record
            $stmt = $pdo->prepare("
                INSERT INTO weights (class_id, teacher_email, class_standing, exam, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$class_id, $teacher_email, $class_standing, $exam]);
            $action = 'created';
        }

        // Commit transaction
        $pdo->commit();

        // Clean output buffer
        ob_clean();

        echo json_encode([
            'success' => true,
            'message' => 'Weights ' . $action . ' successfully',
            'action' => $action,
            'weights' => [
                'class_standing' => $class_standing,
                'exam' => $exam,
                'total' => $class_standing + $exam
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

} catch (PDOException $e) {
    // Rollback transaction if still active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_clean();
    http_response_code(500);
    error_log('Database error in save_weights.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    ob_clean();
    // Error code should already be set by the throw statement
    if (!http_response_code()) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
?>