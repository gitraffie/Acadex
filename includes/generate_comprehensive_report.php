<?php
// IMPORTANT: No whitespace before this line!
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header FIRST for API responses, but we'll override for file downloads
// header('Content-Type: application/json');

// Start output buffering
ob_start();

session_start();
require_once 'connection.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get teacher email
    $teacher_email = $_SESSION['email'] ?? null;
    if (!$teacher_email) {
        throw new Exception('Teacher email not found');
    }

    // Get parameters
    $tab = $_GET['tab'] ?? 'overview';
    $class_filter = $_GET['class'] ?? 'All Classes';
    $term_filter = $_GET['term'] ?? 'Current Term';
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $grade_range = $_GET['grade_range'] ?? 'All Grades';
    $report_type = $_GET['report_type'] ?? 'comprehensive';
    $student_filter = $_GET['student'] ?? 'All Students';
    $subject_filter = $_GET['subject'] ?? 'All Subjects';
    $time_period = $_GET['time_period'] ?? 'Last 30 Days';

    // Get teacher's classes for filtering
    $stmt = $pdo->prepare("SELECT id, class_name, section, term FROM classes WHERE user_email = ? AND archived = 0 ORDER BY class_name");
    $stmt->execute([$teacher_email]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $class_ids = array_column($classes, 'id');
    $class_map = [];
    foreach ($classes as $class) {
        $class_map[$class['id']] = $class;
    }

    // Build WHERE clause for class filtering
    $class_where = "";
    $class_params = [];
    if ($class_filter !== 'All Classes' && !empty($class_ids)) {
        // Find class ID by name
        $selected_class_id = null;
        foreach ($classes as $class) {
            if ($class['class_name'] === $class_filter) {
                $selected_class_id = $class['id'];
                break;
            }
        }
        if ($selected_class_id) {
            $class_where = " AND sc.class_id = ?";
            $class_params[] = $selected_class_id;
        }
    } elseif (!empty($class_ids)) {
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        $class_where = " AND sc.class_id IN ($placeholders)";
        $class_params = $class_ids;
    }

    // Generate report based on tab
    $report_data = [];

    switch ($tab) {
        case 'overview':
            $report_data = generateOverviewReport($pdo, $class_where, $class_params, $term_filter, $date_from, $date_to);
            break;
        case 'grades':
            $report_data = generateGradesReport($pdo, $class_where, $class_params, $term_filter, $grade_range);
            break;
        case 'attendance':
            $report_data = generateAttendanceReport($pdo, $class_where, $class_params, $date_from, $date_to);
            break;
        case 'performance':
            $report_data = generatePerformanceReport($pdo, $class_where, $class_params, $student_filter, $subject_filter, $time_period);
            break;
        case 'custom':
            $report_data = generateCustomReport($pdo, $class_where, $class_params, $report_type);
            break;
        default:
            $report_data = generateOverviewReport($pdo, $class_where, $class_params, $term_filter, $date_from, $date_to);
    }

    // Generate HTML report
    $html_content = generateHTMLReport($report_data, $tab, $classes, $_GET);

    // Set headers for file download
    $filename = 'report_' . $tab . '_' . date('Y-m-d_H-i-s') . '.html';
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Clean output buffer and send content
    ob_clean();
    echo $html_content;

} catch (PDOException $e) {
    // Log the actual error for debugging
    error_log('Database error in generate_comprehensive_report.php: ' . $e->getMessage());

    // Return error message
    header('Content-Type: text/html');
    ob_clean();
    echo '<html><body><h1>Error</h1><p>Database error occurred: ' . htmlspecialchars($e->getMessage()) . '</p></body></html>';

} catch (Exception $e) {
    header('Content-Type: text/html');
    ob_clean();
    echo '<html><body><h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
}

exit;

// Report generation functions
function generateOverviewReport($pdo, $class_where, $class_params, $term_filter, $date_from, $date_to) {
    $data = [
        'title' => 'Overview Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'statistics' => [],
        'grade_distribution' => [],
        'performance_trends' => [],
        'classes' => []
    ];

    // Get basic statistics
    $query = "
        SELECT
            COUNT(DISTINCT s.student_number) as total_students,
            AVG(cg.final_grade) as average_grade,
            COUNT(CASE WHEN cg.final_grade < 75 THEN 1 END) as at_risk_students
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND sc.class_id = cg.class_id
        WHERE 1=1 $class_where
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($class_params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $data['statistics'] = [
        'total_students' => (int)$stats['total_students'],
        'average_grade' => round($stats['average_grade'] ?? 0, 2),
        'at_risk_students' => (int)$stats['at_risk_students']
    ];

    // Get attendance rate (last 30 days if no date range specified)
    $attendance_from = $date_from ?: date('Y-m-d', strtotime('-30 days'));
    $attendance_to = $date_to ?: date('Y-m-d');

    $query = "
        SELECT
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(*) as attendance_rate
        FROM attendance a
        JOIN student_classes sc ON sc.class_id = a.class_id
        JOIN students s ON a.student_number = s.student_number AND sc.student_id = s.id
        WHERE a.attendance_date BETWEEN ? AND ? $class_where
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$attendance_from, $attendance_to], $class_params));
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['statistics']['attendance_rate'] = round($attendance['attendance_rate'] ?? 0, 2);

    // Get grade distribution
    $query = "
        SELECT
            CASE
                WHEN cg.final_grade >= 90 THEN '90-100'
                WHEN cg.final_grade >= 85 THEN '85-89'
                WHEN cg.final_grade >= 80 THEN '80-84'
                WHEN cg.final_grade >= 75 THEN '75-79'
                WHEN cg.final_grade >= 70 THEN '70-74'
                ELSE '69 and below'
            END as grade_range,
            COUNT(*) as count
        FROM calculated_grades cg
        JOIN student_classes sc ON sc.class_id = cg.class_id
        JOIN students s ON cg.student_number = s.student_number AND sc.student_id = s.id
        WHERE cg.final_grade > 0 $class_where
        GROUP BY
            CASE
                WHEN cg.final_grade >= 90 THEN '90-100'
                WHEN cg.final_grade >= 85 THEN '85-89'
                WHEN cg.final_grade >= 80 THEN '80-84'
                WHEN cg.final_grade >= 75 THEN '75-79'
                WHEN cg.final_grade >= 70 THEN '70-74'
                ELSE '69 and below'
            END
        ORDER BY grade_range
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($class_params);
    $data['grade_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get performance trends (last 6 months)
    for ($i = 5; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));

        $query = "
            SELECT AVG(cg.final_grade) as avg_grade
            FROM calculated_grades cg
            JOIN student_classes sc ON sc.class_id = cg.class_id
            JOIN students s ON cg.student_number = s.student_number AND sc.student_id = s.id
            WHERE cg.created_at BETWEEN ? AND ? $class_where
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute(array_merge([$month_start . ' 00:00:00', $month_end . ' 23:59:59'], $class_params));
        $trend = $stmt->fetch(PDO::FETCH_ASSOC);

        $data['performance_trends'][] = [
            'month' => date('M Y', strtotime($month_start)),
            'average' => round($trend['avg_grade'] ?? 0, 2)
        ];
    }

    return $data;
}

function generateGradesReport($pdo, $class_where, $class_params, $term_filter, $grade_range) {
    $data = [
        'title' => 'Grade Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'students' => []
    ];

    // Build grade range filter
    $grade_where = "";
    $grade_params = [];
    switch ($grade_range) {
        case '90-100 (Excellent)':
            $grade_where = " AND cg.final_grade >= 90";
            break;
        case '80-89 (Good)':
            $grade_where = " AND cg.final_grade >= 80 AND cg.final_grade < 90";
            break;
        case '70-79 (Average)':
            $grade_where = " AND cg.final_grade >= 70 AND cg.final_grade < 80";
            break;
        case 'Below 70 (At Risk)':
            $grade_where = " AND cg.final_grade > 0 AND cg.final_grade < 70";
            break;
    }

    $query = "
        SELECT
            s.student_number,
            s.first_name,
            s.last_name,
            c.class_name,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id
        JOIN classes c ON sc.class_id = c.id
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND sc.class_id = cg.class_id
        WHERE 1=1 $class_where $grade_where
        ORDER BY c.class_name, s.last_name, s.first_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge($class_params, $grade_params));
    $data['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function generateAttendanceReport($pdo, $class_where, $class_params, $date_from, $date_to) {
    $data = [
        'title' => 'Attendance Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'attendance_records' => [],
        'summary' => []
    ];

    $from_date = $date_from ?: date('Y-m-d', strtotime('-30 days'));
    $to_date = $date_to ?: date('Y-m-d');

    // Get attendance records
    $query = "
        SELECT
            a.attendance_date as date,
            s.first_name,
            s.last_name,
            s.student_number,
            c.class_name,
            a.status,
            a.time_recorded
        FROM attendance a
        JOIN student_classes sc ON sc.class_id = a.class_id
        JOIN students s ON a.student_number = s.student_number AND sc.student_id = s.id
        JOIN classes c ON a.class_id = c.id
        WHERE a.attendance_date BETWEEN ? AND ? $class_where
        ORDER BY a.attendance_date DESC, s.last_name, s.first_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$from_date, $to_date], $class_params));
    $data['attendance_records'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance summary
    $query = "
        SELECT
            s.first_name,
            s.last_name,
            s.student_number,
            c.class_name,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(*) as total_count,
            ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(*), 2) as attendance_rate
        FROM attendance a
        JOIN student_classes sc ON sc.class_id = a.class_id
        JOIN students s ON a.student_number = s.student_number AND sc.student_id = s.id
        JOIN classes c ON a.class_id = c.id
        WHERE a.attendance_date BETWEEN ? AND ? $class_where
        GROUP BY s.student_number, s.first_name, s.last_name, c.class_name
        ORDER BY attendance_rate DESC, s.last_name, s.first_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$from_date, $to_date], $class_params));
    $data['summary'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function generatePerformanceReport($pdo, $class_where, $class_params, $student_filter, $subject_filter, $time_period) {
    $data = [
        'title' => 'Performance Report',
        'generated_at' => date('Y-m-d H:i:s'),
        'student_performance' => []
    ];

    // This would be more complex in a real implementation
    // For now, return basic performance data
    $query = "
        SELECT
            s.student_number,
            s.first_name,
            s.last_name,
            c.class_name,
            cg.prelim,
            cg.midterm,
            cg.finals,
            cg.final_grade,
            cg.created_at
        FROM students s
        JOIN student_classes sc ON sc.student_id = s.id
        JOIN classes c ON sc.class_id = c.id
        LEFT JOIN calculated_grades cg ON s.student_number = cg.student_number AND sc.class_id = cg.class_id
        WHERE 1=1 $class_where
        ORDER BY s.last_name, s.first_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($class_params);
    $data['student_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $data;
}

function generateCustomReport($pdo, $class_where, $class_params, $report_type) {
    // Basic custom report - can be extended
    return generateOverviewReport($pdo, $class_where, $class_params, 'Current Term', null, null);
}

function generateHTMLReport($data, $tab, $classes, $params) {
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($data['title']) . ' - Acadex</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #667eea;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($data['title']) . '</h1>
        <p>Generated on: ' . htmlspecialchars($data['generated_at']) . '</p>
        <p>Report Type: ' . htmlspecialchars($tab) . '</p>
    </div>';

    // Add filters info
    if (!empty($params)) {
        $html .= '<div class="section">
            <h2>Report Filters</h2>
            <ul>';
        foreach ($params as $key => $value) {
            if ($key !== 'tab' && !empty($value)) {
                $html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
            }
        }
        $html .= '</ul>
        </div>';
    }

    // Generate content based on tab
    switch ($tab) {
        case 'overview':
            $html .= generateOverviewHTML($data);
            break;
        case 'grades':
            $html .= generateGradesHTML($data);
            break;
        case 'attendance':
            $html .= generateAttendanceHTML($data);
            break;
        case 'performance':
            $html .= generatePerformanceHTML($data);
            break;
        default:
            $html .= generateOverviewHTML($data);
    }

    $html .= '
    <div class="no-print" style="margin-top: 40px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 20px;">
        <p>Report generated by Acadex - Academic Excellence System</p>
    </div>
</body>
</html>';

    return $html;
}

function generateOverviewHTML($data) {
    $html = '';

    if (isset($data['statistics'])) {
        $html .= '<div class="section">
            <h2>Summary Statistics</h2>
            <div class="stats-grid">';

        foreach ($data['statistics'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $html .= '<div class="stat-card">
                <div class="stat-value">' . htmlspecialchars($value) . '</div>
                <div class="stat-label">' . htmlspecialchars($label) . '</div>
            </div>';
        }

        $html .= '</div>
        </div>';
    }

    if (isset($data['grade_distribution']) && !empty($data['grade_distribution'])) {
        $html .= '<div class="section">
            <h2>Grade Distribution</h2>
            <table>
                <thead>
                    <tr>
                        <th>Grade Range</th>
                        <th>Number of Students</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['grade_distribution'] as $dist) {
            $html .= '<tr>
                <td>' . htmlspecialchars($dist['grade_range']) . '</td>
                <td>' . htmlspecialchars($dist['count']) . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </div>';
    }

    if (isset($data['performance_trends']) && !empty($data['performance_trends'])) {
        $html .= '<div class="section">
            <h2>Performance Trends (Last 6 Months)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Average Grade</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['performance_trends'] as $trend) {
            $html .= '<tr>
                <td>' . htmlspecialchars($trend['month']) . '</td>
                <td>' . htmlspecialchars($trend['average']) . '%</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </div>';
    }

    return $html;
}

function generateGradesHTML($data) {
    $html = '<div class="section">
        <h2>Student Grades</h2>';

    if (!empty($data['students'])) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Finals</th>
                    <th>Final Grade</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($data['students'] as $student) {
            $html .= '<tr>
                <td>' . htmlspecialchars($student['student_number']) . '</td>
                <td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>
                <td>' . htmlspecialchars($student['class_name']) . '</td>
                <td>' . htmlspecialchars($student['prelim'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['midterm'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['finals'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['final_grade'] ?? '--') . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>';
    } else {
        $html .= '<p>No student data found for the selected filters.</p>';
    }

    $html .= '</div>';
    return $html;
}

function generateAttendanceHTML($data) {
    $html = '';

    if (!empty($data['summary'])) {
        $html .= '<div class="section">
            <h2>Attendance Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Present</th>
                        <th>Total Sessions</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['summary'] as $record) {
            $html .= '<tr>
                <td>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</td>
                <td>' . htmlspecialchars($record['class_name']) . '</td>
                <td>' . htmlspecialchars($record['present_count']) . '</td>
                <td>' . htmlspecialchars($record['total_count']) . '</td>
                <td>' . htmlspecialchars($record['attendance_rate']) . '%</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </div>';
    }

    if (!empty($data['attendance_records'])) {
        $html .= '<div class="section">
            <h2>Detailed Attendance Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($data['attendance_records'] as $record) {
            $html .= '<tr>
                <td>' . htmlspecialchars($record['date']) . '</td>
                <td>' . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . '</td>
                <td>' . htmlspecialchars($record['class_name']) . '</td>
                <td>' . htmlspecialchars($record['status']) . '</td>
                <td>' . htmlspecialchars($record['time_recorded'] ?? '--') . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
        </div>';
    }

    return $html;
}

function generatePerformanceHTML($data) {
    $html = '<div class="section">
        <h2>Student Performance</h2>';

    if (!empty($data['student_performance'])) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Finals</th>
                    <th>Final Grade</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($data['student_performance'] as $student) {
            $html .= '<tr>
                <td>' . htmlspecialchars($student['student_number']) . '</td>
                <td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>
                <td>' . htmlspecialchars($student['class_name']) . '</td>
                <td>' . htmlspecialchars($student['prelim'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['midterm'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['finals'] ?? '--') . '</td>
                <td>' . htmlspecialchars($student['final_grade'] ?? '--') . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>';
    } else {
        $html .= '<p>No performance data found.</p>';
    }

    $html .= '</div>';
    return $html;
}
