<?php
include 'connection.php';

function truncate_text($text, $limit = 40) {
    $text = trim((string)$text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $limit - 3)) . '...';
}

// Start session if not already started (for AJAX calls)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If called via AJAX, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['error' => 'Unauthorized access']);
        exit();
    }
    // If included in page, don't output anything
    return;
}

// Get user email from session
$userEmail = $_SESSION['email'] ?? '';

if (empty($userEmail)) {
    // If called via AJAX, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['error' => 'User email not found']);
        exit();
    }
    // If included in page, don't output anything
    return;
}

// Get search, filter, and pagination parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$hasClassFilter = array_key_exists('class_id', $_GET);
$classFilter = $hasClassFilter ? (int)$_GET['class_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$perPage = $perPage > 0 ? min($perPage, 50) : 10; // cap page size to avoid abuse
$offset = ($page - 1) * $perPage;

// Build the base query parts
$baseQuery = "
    FROM students s
    LEFT JOIN student_classes sc ON sc.student_id = s.id
    LEFT JOIN classes c ON c.id = sc.class_id AND c.user_email = ?
    WHERE 1=1
";
$params = [$userEmail];

if (!empty($search)) {
    $baseQuery .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ? OR s.student_email LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($classFilter > 0) {
    $baseQuery .= " AND sc.class_id = ? AND c.id IS NOT NULL";
    $params[] = $classFilter;
} elseif ($hasClassFilter && $classFilter === 0) {
    $baseQuery .= " AND c.id IS NULL";
}

$orderClause = " ORDER BY s.last_name, s.first_name";

try {
    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT s.id) " . $baseQuery);
    $countStmt->execute($params);
    $totalStudents = (int)$countStmt->fetchColumn();
    $totalPages = $totalStudents > 0 ? (int)ceil($totalStudents / $perPage) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    // Fetch paginated results
    // Note: Some MySQL/MariaDB configurations don't allow bound parameters for LIMIT/OFFSET.
    // Values are already sanitized (ints), so inject directly.
    $dataQuery = "
        SELECT
            s.id as student_id,
            s.student_number,
            s.first_name,
            s.last_name,
            s.middle_initial,
            s.suffix,
            s.program,
            s.student_email,
            MIN(c.id) as primary_class_id,
            GROUP_CONCAT(DISTINCT CONCAT(c.class_name, ' - ', c.section, ' (', c.term, ')') ORDER BY c.class_name SEPARATOR ', ') as class_list,
            COUNT(DISTINCT c.id) as class_count
        " . $baseQuery . "
        GROUP BY s.id
        " . $orderClause . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo '<div class="no-students">
                <p>No students found matching your criteria.</p>
              </div>';
    } else {
        $start = $offset + 1;
        $end = min($offset + $perPage, $totalStudents);
        echo '<table class="students-table">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Program</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($students as $student) {
            $primaryClassId = isset($student['primary_class_id']) ? (int)$student['primary_class_id'] : 0;
            echo '<tr>
                    <td>' . htmlspecialchars($student['student_number']) . '</td>
                    <td class="student-name">';
            $lastName = mb_strtoupper(trim((string)$student['last_name']), 'UTF-8');
            $firstName = mb_strtoupper(trim((string)$student['first_name']), 'UTF-8');
            $fullNameRaw = $lastName . ', ' . $firstName;
            $fullName = htmlspecialchars($fullNameRaw);
            if (!empty($student['middle_initial'])) {
                $mi = trim($student['middle_initial']);
                $miInitial = $mi !== '' ? mb_strtoupper(mb_substr($mi, 0, 1, 'UTF-8'), 'UTF-8') : '';
                if ($miInitial !== '') {
                    $fullNameRaw .= ' ' . $miInitial . '.';
                    $fullName = htmlspecialchars($fullNameRaw);
                }
            }
            if (!empty($student['suffix'])) {
                $fullNameRaw .= ' ' . mb_strtoupper(trim((string)$student['suffix']), 'UTF-8');
                $fullName = htmlspecialchars($fullNameRaw);
            }
            $classList = $student['class_list'] ?? '';
            $classListDisplay = truncate_text($classList, 40);
            $classListTitle = $classList !== '' ? ' title="' . htmlspecialchars($classList) . '"' : '';
            echo $fullName . '<br><span class="student-email">' . htmlspecialchars($student['student_email']) . '</span></td>
                    <td' . $classListTitle . '>' . (empty($classList) ? '--' : htmlspecialchars($classListDisplay)) . '</td>
                    <td>' . htmlspecialchars($student['program']) . '</td>
                    <td>
                        <div class="student-actions">
                            <button type="button" class="student-action-btn edit" onclick="openEditStudentModal(this)"
                                data-student-id="' . htmlspecialchars($student['student_id']) . '"
                                data-primary-class-id="' . htmlspecialchars((string)$primaryClassId) . '"
                                data-student-number="' . htmlspecialchars($student['student_number']) . '"
                                data-first-name="' . htmlspecialchars($student['first_name']) . '"
                                data-last-name="' . htmlspecialchars($student['last_name']) . '"
                                data-middle-initial="' . htmlspecialchars($student['middle_initial']) . '"
                                data-suffix="' . htmlspecialchars($student['suffix']) . '"
                                data-program="' . htmlspecialchars($student['program']) . '"
                                data-email="' . htmlspecialchars($student['student_email']) . '"
                                title="Edit student"
                                aria-label="Edit student">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="student-action-btn delete" onclick="confirmDeleteStudent(this)"
                                data-student-id="' . htmlspecialchars($student['student_id']) . '"
                                data-primary-class-id="' . htmlspecialchars((string)$primaryClassId) . '"
                                data-student-name="' . htmlspecialchars($fullNameRaw) . '"
                                title="Delete student"
                                aria-label="Delete student">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                  </tr>';
        }
        echo '</tbody></table>';

        // Pagination controls
        echo '<div class="pagination">';
        echo '<div class="pagination-info">Showing ' . $start . '-' . $end . ' of ' . $totalStudents . ' students</div>';
        echo '<div class="pagination-controls">';
        $prevPage = max(1, $page - 1);
        $nextPage = min($totalPages, $page + 1);
        $prevDisabled = $page <= 1 ? 'disabled' : '';
        $nextDisabled = $page >= $totalPages ? 'disabled' : '';
        echo '<button class="page-btn" ' . $prevDisabled . ' onclick="changeStudentsPage(' . $prevPage . ')"><i class=\"fas fa-chevron-left\"></i> Prev</button>';
        echo '<span class="page-number">Page ' . $page . ' of ' . $totalPages . '</span>';
        echo '<button class="page-btn" ' . $nextDisabled . ' onclick="changeStudentsPage(' . $nextPage . ')">Next <i class=\"fas fa-chevron-right\"></i></button>';
        echo '</div>';
        echo '</div>';
    }
} catch (PDOException $e) {
    echo '<div class="no-students">
            <p>Error loading students: ' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
}
?>
