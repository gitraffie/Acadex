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
        throw new Exception('Unauthorized');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    // Get class_id from query parameters
    $class_id = $_GET['class_id'] ?? null;

    if (!$class_id) {
        throw new Exception('Class ID is required');
    }

    // Get teacher email from session
    $teacher_email = $_SESSION['email'] ?? null;

    if (!$teacher_email) {
        throw new Exception('Teacher email not found in session');
    }

    // Get weights for the class filtered by class_id AND teacher_email
    $stmt = $pdo->prepare("
        SELECT class_standing, exam 
        FROM weights 
        WHERE class_id = ? AND teacher_email = ? 
        LIMIT 1
    ");
    $stmt->execute([$class_id, $teacher_email]);
    $weights = $stmt->fetch(PDO::FETCH_ASSOC);

    // Clean output buffer
    ob_clean();

    if ($weights) {
        // Weights found in database - convert to decimals and return
        echo json_encode([
            'success' => true,
            'source' => 'database',
            'weights' => [
                'class_standing' => floatval($weights['class_standing']),
                'exam' => floatval($weights['exam'])
            ]
        ]);
    } else {
        // No weights exist - return default values as decimals
        echo json_encode([
            'success' => true,
            'source' => 'default',
            'weights' => [
                'class_standing' => 0.7,   // 70%
                'exam' => 0.3              // 30%
            ]
        ]);
    }

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Database error in get_weights.php: ' . $e->getMessage());

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