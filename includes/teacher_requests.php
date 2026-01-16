<?php
if (!isset($pdo) || !isset($userEmail)) {
    return;
}

if (!function_exists('formatTimeAgo')) {
    function formatTimeAgo($datetime) {
        if (!$datetime) {
            return 'Just now';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return 'Just now';
        }

        $diff = time() - $timestamp;
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . ' minute/s ago';
        if ($diff < 86400) return floor($diff / 3600) . ' hour/s ago';
        if ($diff < 604800) return floor($diff / 86400) . ' day/s ago';
        return date('M j, Y', $timestamp);
    }
}

try {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS student_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                student_number VARCHAR(50) NOT NULL,
                student_name VARCHAR(255) NOT NULL,
                student_email VARCHAR(255) NOT NULL,
                class_id INT NULL,
                class_name VARCHAR(255) NULL,
                teacher_email VARCHAR(255) NOT NULL,
                request_type ENUM('grade','attendance') NOT NULL,
                term ENUM('prelim','midterm','finals','all') NULL,
                message TEXT NULL,
                status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
                resolved_at TIMESTAMP NULL,
                resolved_by VARCHAR(255) NULL,
                is_seen TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_teacher_status (teacher_email, status, created_at)
            )
        ");
        $pdo->exec("ALTER TABLE student_requests ADD COLUMN IF NOT EXISTS is_seen TINYINT(1) NOT NULL DEFAULT 0");
        $pdo->exec("ALTER TABLE student_requests ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL");
        $pdo->exec("ALTER TABLE student_requests ADD COLUMN IF NOT EXISTS resolved_by VARCHAR(255) NULL");
    } catch (PDOException $e) {
        // Table or column might already exist without IF NOT EXISTS support.
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as request_count FROM student_requests WHERE teacher_email = ? AND status = 'pending' AND is_seen = 0");
        $stmt->execute([$userEmail]);
        $requestCountResult = $stmt->fetch();
        $requestCount = $requestCountResult['request_count'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT id, student_id, student_name, class_id, class_name, request_type, term, created_at, is_seen
            FROM student_requests
            WHERE teacher_email = ? AND status = 'pending'
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userEmail]);
        $studentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT id, student_id, student_name, class_id, class_name, request_type, term, created_at, is_seen
            FROM student_requests
            WHERE teacher_email = ? AND status = 'pending'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userEmail]);
        $pendingRequestsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT id, student_id, student_name, class_id, class_name, request_type, term, created_at, is_seen
            FROM student_requests
            WHERE teacher_email = ? AND status = 'resolved'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userEmail]);
        $completedRequestsAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare("
            SELECT id, student_id, student_name, class_id, class_name, request_type, term, created_at, 0 as is_seen
            FROM student_requests
            WHERE teacher_email = ? AND status = 'pending'
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userEmail]);
        $studentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $requestCount = count($studentRequests);
        $pendingRequestsAll = $studentRequests;
        $completedRequestsAll = [];
    }
} catch (PDOException $e) {
    $requestCount = 0;
    $studentRequests = [];
    $pendingRequestsAll = [];
    $completedRequestsAll = [];
}
