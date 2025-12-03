<?php
session_start();
include '../includes/connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/teacher-login.php');
    exit();
}

// Get user details from session
$userFullName = $_SESSION['full_name'] ?? 'Unknown User';
$userEmail = $_SESSION['email'] ?? 'Unknown Email';

// Fetch classes for the current teacher (only non-archived classes)
try {
    $stmt = $pdo->prepare("SELECT id, class_name, section, term, created_at FROM classes WHERE user_email = ? AND archived = 0 ORDER BY created_at DESC");
    $stmt->execute([$userEmail]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.collapsed .user-details h3,
        .sidebar.collapsed .user-details p,
        .sidebar.collapsed .nav-link span:not(.nav-icon),
        .sidebar.collapsed .logo {
            display: none;
        }

        .sidebar.collapsed .user-info {
            justify-content: center;
        }

        .sidebar.collapsed .user-details {
            display: none;
        }

        .sidebar.collapsed .user-avatar {
            border-radius: 50%;
            width: 35px;
            height: 35px;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            margin-bottom: 0;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-toggle::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar.collapsed .sidebar-toggle::before {
            content: '\f054'; /* fa-chevron-right */
        }

        .sidebar:not(.collapsed) .sidebar-toggle::before {
            content: '\f0c9'; /* fa-bars */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-details h3 {
            color: white;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .user-details p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.3rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
        }

        .nav-icon {
            font-size: 1.3rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 80px;
        }

        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .page-title p {
            color: #666;
            font-size: 0.9rem;
        }

        .top-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .notification-btn {
            position: relative;
            background: #f5f6fa;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: #e8eaf0;
            transform: scale(1.05);
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn {
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Classes Grid */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .class-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .class-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #667eea;
        }

        .class-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 15px 15px 0 0;
            margin: -1.5rem -1.5rem 1rem -1.5rem;
        }

        .class-banner h3 {
            font-size: 1.2rem;
            color: white;
            margin-bottom: 0.3rem;
        }

        .class-banner p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .class-stats {
            display: flex;
            gap: 1rem;
        }

        .class-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .class-stat {
            text-align: center;
        }

        .class-stat-value {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .class-stat-label {
            font-size: 0.75rem;
            color: #999;
        }

        .class-actions{
            display: flex;
            gap: 0.5rem;
            color: #666;
        }

        .class-action-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            color: #666;
        }

        .class-action-btn:hover {
            background: #f8f9fa;
            border-color: #666;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-btn {
            padding: 0.5rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            font-size: 0.6rem;
            color: #5a11a3ff;
        }

        .action-btn:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            transform: translateY(-1px);
        }

        .view-archive {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            color: #4e4e4eff;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .view-archive:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
        }

        .view-archive i {
            font-size: 1rem;
        }

        .action-icon {
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }

        .action-label {
            font-weight: 600;
            font-size: 0.6rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-header h2 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f5f6fa;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e8eaf0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .classes-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .top-bar {
                flex-direction: column;
                gap: 1rem;
            }

            .class-stats {
                flex-direction: column;
                gap: 0.5rem;
            }

            .modal-content {
                margin: 5% auto;
                padding: 1.5rem;
            }
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }

        .selected-class-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            padding: 1rem;
        }

        /* Selected Class Section */
        .selected-class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .selected-class-header h2 {
            color: #333;
            font-size: 1.8rem;
            margin: 0;
        }

        .close-selected-btn {
            background: none;
            border: none;
            font-size: 2.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .close-selected-btn:hover {
            color: #667eea;
        }

        .tabs {
            display: flex;
            background: white;
            border-radius: 15px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tab-btn {
            flex: 1;
            padding: 1rem 2rem;
            background: #f5f6fa;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-right: 1px solid #e0e0e0;
        }

        .tab-btn:last-child {
            border-right: none;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: #e8eaf0;
        }

        .tab-content {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 15px;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .tab-content p {
            color: #666;
            margin-bottom: 1rem;
        }

        .placeholder-content {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 2rem;
        }

        .modal-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-tab-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            background: #f5f6fa;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }

        .modal-tab-btn.active {
            background: white;
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .modal-tab-btn:hover:not(.active) {
            background: #e8eaf0;
        }

        .modal-tab-content {
            display: none;
        }

        .modal-tab-content.active {
            display: block;
        }

        .file-upload-area {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: border-color 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .file-upload-area:hover,
        .file-upload-area.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .file-upload-content {
            color: #666;
        }

        .file-upload-icon {
            font-size: 3rem;
            color: #999;
            margin-bottom: 1rem;
        }

        /* Student Table Styles */
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .student-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .student-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            font-size: medium;
            text-align: left;
            padding: 0.5rem;
        }

        .student-table tr:hover {
            background: #f8f9fa;
        }

        .student-table .student-name {
            font-weight: 600;
            color: #333;
        }

        .student-table .student-number {
            color: #666;
            font-family: 'Courier New', monospace;
        }

        .student-actions {
            gap: 0.5rem;
        }

        .student-action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .student-action-btn.edit {
            background: #28a745;
            color: white;
            margin-bottom: 10px;
        }

        .student-action-btn.edit:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .student-action-btn.delete {
            background: #dc3545;
            color: white;
        }

        .student-action-btn.delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .no-students {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 2rem;
        }

        .student-email {
            color: #858585ff;
            font-size: x-small;
        }

        .file-actions {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">Acadex</div>
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar"></button>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <img src="../image/default.webp" alt="">
            </div>
            <div class="user-details">
                <h3><?php echo htmlspecialchars($userFullName); ?></h3>
                <p><?php echo htmlspecialchars($userEmail); ?></p>
            </div>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="t-dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-classes.php" class="nav-link active">
                    <i class="fas fa-users nav-icon"></i>
                    <span>My Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-grades.php" class="nav-link">
                    <i class="fas fa-edit nav-icon"></i>
                    <span>Grade Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-attendance.php" class="nav-link">
                    <i class="fas fa-calendar-check nav-icon"></i>
                    <span>Attendance</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-reports.php" class="nav-link">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog nav-icon"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>My Classes</h1>
                <p>Manage and view all your teaching classes.</p>
            </div>
            <div class="top-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </button>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#" class="view-archive" onclick="openArchiveSection()" title="View Archive"><i class="fas fa-archive"></i> View Archived Classes</a>
            <a href="#" class="action-btn" title="Add New Class" onclick="openModal()">
                <div class="action-icon"><i class="fas fa-plus"></i> Add New Class</div>
            </a>
        </div>

        <!-- Classes Grid -->
        <div class="classes-grid">
            <?php if (empty($classes)): ?>
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Classes Found</h3>
                        <p>You haven't added any classes yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>">
                        <div class="class-banner">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                        </div>
                        <div class="class-footer">
                            <div class="class-stats">
                                <div class="class-stat">
                                    <div class="class-stat-value">0</div>
                                    <div class="class-stat-label">Students</div>
                                </div>
                                <div class="class-stat">
                                    <div class="class-stat-value">0%</div>
                                    <div class="class-stat-label">Attendance</div>
                                </div>
                            </div>
                            <div class="class-actions">
                                <button class="class-action-btn archive-btn" onclick="archiveClass(<?php echo $class['id']; ?>)"><i class="fas fa-archive"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Archive Classes Section (hidden by default) -->
        <div id="archiveSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <h2>Archived Classes</h2>
                <button class="close-selected-btn" onclick="closeArchiveSection()">&times;</button>
            </div>
            <div class="classes-grid" id="archiveClassesGrid">
                <!-- Archived classes will be loaded here -->
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Archived Classes</h3>
                        <p>No classes have been archived yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Class Section (hidden by default)-->
        <div class="selected-class-container" id="selectedClassSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <h2 id="selectedClassName">Selected Class</h2>
                <button class="close-selected-btn" onclick="closeSelectedClass()">&times;</button>
            </div>
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('attendance')">Attendance</button>
                <button class="tab-btn" onclick="openTab('grades')">Grades</button>
                <button class="tab-btn" onclick="openTab('students')">Students</button>
            </div>
            <div id="attendance" class="tab-content active">
                <h3>Attendance Records</h3>
                <p>Attendance data for the selected class will be displayed here.</p>
                <!-- Placeholder for attendance table or list -->
                <div class="placeholder-content">
                    <p>No attendance records available yet.</p>
                </div>
            </div>
            <div id="grades" class="tab-content">
                <h3>Grade Management</h3>
                <table class="student-table" id="gradesTable">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Finals</th>
                            <th>GWA (Final Grade)</th>
                        </tr>
                    </thead>
                    <tbody id="gradesTableBody">
                    </tbody>
                </table>
                <div class="no-students" id="noGradesMessage" style="display: none;">
                    <p>No grades available yet.</p>
                </div>
            </div>
            <div id="students" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Student List</h3>
                    <button class="btn btn-primary" onclick="openAddStudentModal()">
                        <i class="fas fa-plus"></i> Add Students
                    </button>
                </div>
                <table class="student-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Program</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- Students will be loaded here -->
                    </tbody>
                </table>
                <div class="no-students" id="noStudentsMessage" style="display: none;">
                    <p>No students enrolled yet.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

    <!-- Add New Class Modal -->
    <div id="addClassModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Class</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="addClassForm" action="">
                <div class="form-group">
                    <label for="className">Class Name</label>
                    <input type="text" id="className" name="className" required placeholder="Enter class name">
                </div>
                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="text" id="section" name="section" required placeholder="Enter section (e.g., A, B, C)">
                </div>
                <div class="form-group">
                    <label for="term">Term</label>
                    <select id="term" name="term" required>
                        <option value="">Select Semester</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Students Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h2>Add Students</h2>
                <button class="close-btn" onclick="closeAddStudentModal()">&times;</button>
            </div>
            <div class="modal-tabs">
                <button class="modal-tab-btn active" onclick="openStudentTab('register')">Register Student</button>
                <button class="modal-tab-btn" onclick="openStudentTab('import')">Import Files</button>
            </div>
            <div id="registerStudent" class="modal-tab-content active">
                <form id="registerStudentForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="studentNumber">Student Number</label>
                            <input type="text" id="studentNumber" name="studentNumber" required placeholder="Enter student number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="Enter email address">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" required placeholder="Enter first name">
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" required placeholder="Enter last name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="middleInitial">Middle Initial</label>
                            <input type="text" id="middleInitial" name="middleInitial" maxlength="1" placeholder="Enter middle initial">
                        </div>
                        <div class="form-group">
                            <label for="suffix">Suffix</label>
                            <input type="text" id="suffix" name="suffix" placeholder="Enter suffix (e.g., Jr., Sr.)">
                        </div>
                        <div class="form-group">
                            <label for="program">Program</label>
                            <select id="program" name="program" required>
                                <option value="">Select Program</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Computer Engineering">BS Computer Engineering</option>
                                <option value="BA Psychology">BA Psychology</option>
                                <option value="BA English">BA English</option>
                                <option value="BS Business Administration">BS Business Administration</option>
                                <option value="BS Nursing">BS Nursing</option>
                                <option value="BA Mathematics">BA Mathematics</option>
                                <option value="BS Engineering">BA Filipino</option>
                                <option value="BS Engineering">BS Engineering</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Register Student</button>
                    </div>
                </form>
            </div>
            <div id="importFiles" class="modal-tab-content">
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="file-upload-content">
                        <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                        <p>Drag and drop files here or click to select</p>
                        <input type="file" id="fileInput" name="fileInput" accept=".csv,.xlsx,.xls" style="display: none;">
                        <div class="file-actions">
                            <button type="button" class="btn btn-secondary" id="selectFileBtn" onclick="document.getElementById('fileInput').click()">Select File</button>
                            <button type="button" class="btn btn-danger" id="removeFileBtn" onclick="removeSelectedFile()" style="display: none; color: #801313; background: #ff00001f;">Remove File</button>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStudentModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="importStudents()">Import Students</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar collapse/expand functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.querySelector('.main-content');

        // Load initial state from localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
        }

        // Toggle sidebar
        function toggleSidebarMode() {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            // Update button icon if needed, but CSS handles it
        }

        sidebarToggle.addEventListener('click', toggleSidebarMode);

        // Mobile sidebar toggle (existing)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/teacher-login.php';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Add click handlers for nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all links
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                // Add active class to clicked link
                this.classList.add('active');
            });
        });

        // Function to truncate long names and emails
        function truncateUserInfo() {
            const nameElement = document.querySelector('.user-details h3');
            const emailElement = document.querySelector('.user-details p');

            if (nameElement) {
                const nameText = nameElement.textContent;
                if (nameText.length > 15) {
                    nameElement.textContent = nameText.substring(0, 12) + '...';
                }
            }

            if (emailElement) {
                const emailText = emailElement.textContent;
                if (emailText.length > 20) {
                    emailElement.textContent = emailText.substring(0, 17) + '...';
                }
            }
        }

        // Call the truncate function on page load
        truncateUserInfo();

        // Modal functions
        function openModal() {
            document.getElementById('addClassModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addClassModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addClassModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Handle form submission
        document.getElementById('addClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../includes/add_class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                    closeModal();
                    this.reset();
                    // Optionally refresh the page or update the classes list
                    location.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while adding the class.', 'error');
            });
        });

        // Simulate real-time updates (for demonstration)
        setInterval(() => {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (Math.random() > 0.7) { // 30% chance of new notification
                    badge.textContent = currentCount + 1;
                }
            }
        }, 30000); // Check every 30 seconds

        // Class selection functionality
        function selectClass(className, classId) {
            document.getElementById('selectedClassName').textContent = className;
            document.getElementById('selectedClassSection').style.display = 'block';
            document.getElementById('selectedClassSection').setAttribute('data-class-id', classId);
            // Hide classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'none';
            document.querySelector('.classes-grid').style.display = 'none';
            // Scroll to selected class section
            document.getElementById('selectedClassSection').scrollIntoView({ behavior: 'smooth' });
            // Refresh the selected class section data
            refreshSelectedClass();
        }

        function closeSelectedClass() {
            document.getElementById('selectedClassSection').style.display = 'none';
            // Show classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'flex';
            document.querySelector('.classes-grid').style.display = 'grid';
        }

        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked tab button
            event.target.classList.add('active');

            // Load students if students tab is opened
            if (tabName === 'students') {
                loadStudents();
            }
            // Load grades if grades tab is opened
            if (tabName === 'grades') {
                loadGrades();
            }
        }

        // Function to load students for the selected class
        function loadStudents() {
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) return;

            fetch('../includes/get_students.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('studentTableBody');
                    const noStudentsMessage = document.getElementById('noStudentsMessage');

                    if (data.success) {
                        if (data.students.length > 0) {
                            tableBody.innerHTML = '';
                            data.students.forEach(student => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td class="student-number">${student.student_number}</td>
                                    <td class="student-name">
                                        ${student.last_name}, ${student.first_name} ${student.middle_initial ? student.middle_initial + '.' : ''} ${student.suffix || ''}
                                        <div>
                                            <small class="student-email">${student.student_email}</small>
                                        </div>
                                    </td>
                                    <td>${student.program}</td>
                                    <td class="student-actions">
                                        <div>
                                            <button class="student-action-btn edit" onclick="editStudent(${student.id})"><i class="fas fa-edit"></i> Edit</button>
                                        </div>
                                        <div>
                                            <button class="student-action-btn delete" onclick="deleteStudent(${student.id})"><i class="fas fa-trash"></i> Delete</button>
                                        </div>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                            noStudentsMessage.style.display = 'none';
                        } else {
                            tableBody.innerHTML = '';
                            noStudentsMessage.style.display = 'block';
                        }
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading students.');
                });
        }

        // Function to render grades data on the table
        function renderGradesTable(grades) {
            const tableBody = document.getElementById('gradesTableBody');
            const noGradesMessage = document.getElementById('noGradesMessage');

            if (grades && Array.isArray(grades) && grades.length > 0) {
                tableBody.innerHTML = '';
                grades.forEach(grade => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="student-name">${grade.student_name}</td>
                        <td>${grade.prelim || '-'}</td>
                        <td>${grade.midterm || '-'}</td>
                        <td>${grade.finals || '-'}</td>
                        <td>${grade.final_grade || '-'}</td>
                    `;
                    tableBody.appendChild(row);
                });
                noGradesMessage.style.display = 'none';
            } else {
                tableBody.innerHTML = '';
                noGradesMessage.style.display = 'block';
            }
        }

        // Function to load grades for the selected class
        function loadGrades() {
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            if (!classId) return;

            fetch('../includes/get_cal_grades.php?class_id=' + classId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderGradesTable(data.grades);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading grades.');
                });
        }

        // Function to refresh the selected class section data
        function refreshSelectedClass() {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabName = activeTab.textContent.toLowerCase();
                if (tabName === 'students') {
                    loadStudents();
                }
                if (tabName === 'grades') {
                    loadGrades();
                }
                // Add refresh logic for other tabs here if needed
                // e.g., if (tabName === 'attendance') { loadAttendance(); }
            }
        }

        // Placeholder functions for edit and delete (to be implemented later)
        function editStudent(studentId) {
            alert('Edit student functionality not yet implemented. Student ID: ' + studentId);
        }

        function deleteStudent(studentId) {
            if (confirm('Are you sure you want to delete this student?')) {
                alert('Delete student functionality not yet implemented. Student ID: ' + studentId);
            }
        }

        // Add click event listeners to class cards
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            classCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Prevent navigation if clicking on the link
                    if (e.target.closest('a')) return;

                    const className = this.getAttribute('data-class-name');
                    const classId = this.getAttribute('data-class-id');
                    selectClass(className, classId);
                });
            });
        });

        // Add Students Modal functions
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }

        function closeAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'none';
        }

        function openStudentTab(tabName) {
            // Hide all modal tab contents
            const modalTabContents = document.querySelectorAll('.modal-tab-content');
            modalTabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all modal tab buttons
            const modalTabButtons = document.querySelectorAll('.modal-tab-btn');
            modalTabButtons.forEach(button => button.classList.remove('active'));

            // Show selected modal tab content
            document.getElementById(tabName === 'register' ? 'registerStudent' : 'importFiles').classList.add('active');

            // Add active class to clicked modal tab button
            event.target.classList.add('active');
        }

        // Handle student registration form submission
        document.getElementById('registerStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            formData.append('classId', classId);

            fetch('../includes/add_student.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeAddStudentModal();
                    this.reset();
                    // Optionally refresh the page or update the student list
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while registering the student.');
            });
        });

        // File upload functionality
        function importStudents() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please select a file to import.');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            const classId = document.getElementById('selectedClassSection').getAttribute('data-class-id');
            formData.append('classId', classId);

            fetch('../includes/import_students.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeAddStudentModal();
                    fileInput.value = '';
                    resetFileUploadUI();
                    // Optionally refresh the page or update the student list
                    location.reload();
                } else {
                    alert(data.message);
                    resetFileUploadUI();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while importing students.');
                resetFileUploadUI();
            });
        }

        function resetFileUploadUI() {
            const fileInput = document.getElementById('fileInput');
            const uploadText = document.querySelector('.file-upload-content p');
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            uploadText.textContent = 'Drag and drop files here or click to select';
            selectButton.style.display = 'inline-block';
            removeButton.style.display = 'none';
        }

        function removeSelectedFile() {
            const fileInput = document.getElementById('fileInput');
            const uploadText = document.querySelector('.file-upload-content p');
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            fileInput.value = '';
            uploadText.textContent = 'Drag and drop files here or click to select';
            selectButton.style.display = 'inline-block';
            removeButton.style.display = 'none';
        }

        // Drag and drop functionality for file upload
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');

        // Update UI when file is selected
        fileInput.addEventListener('change', function() {
            const selectButton = document.getElementById('selectFileBtn');
            const removeButton = document.getElementById('removeFileBtn');
            if (this.files.length > 0) {
                selectButton.style.display = 'none';
                removeButton.style.display = 'inline-block';
                document.querySelector('.file-upload-content p').textContent = this.files[0].name;
            } else {
                selectButton.style.display = 'inline-block';
                removeButton.style.display = 'none';
                document.querySelector('.file-upload-content p').textContent = 'Drag and drop files here or click to select';
            }
        });

        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const selectButton = document.getElementById('selectFileBtn');
                const removeButton = document.getElementById('removeFileBtn');
                if (fileInput.files.length > 0) {
                    selectButton.style.display = 'none';
                    removeButton.style.display = 'inline-block';
                    document.querySelector('.file-upload-content p').textContent = fileInput.files[0].name;
                } else {
                    selectButton.style.display = 'inline-block';
                    removeButton.style.display = 'none';
                    document.querySelector('.file-upload-content p').textContent = 'Drag and drop files here or click to select';
                }
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addClassModal = document.getElementById('addClassModal');
            const addStudentModal = document.getElementById('addStudentModal');
            if (event.target == addClassModal) {
                closeModal();
            }
            if (event.target == addStudentModal) {
                closeAddStudentModal();
            }
        }

        // Load student counts and attendance percentages for each class
        function loadStudentCounts() {
            fetch('../includes/student_each_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(classData => {
                            const classCards = document.querySelectorAll('.class-card');
                            classCards.forEach(card => {
                                const className = card.getAttribute('data-class-name');
                                if (className === classData.class_name) {
                                    const statValues = card.querySelectorAll('.class-stat-value');
                                    if (statValues.length >= 2) {
                                        // First stat: student count
                                        statValues[0].textContent = classData.student_count;
                                        // Second stat: attendance percentage today
                                        const percentage = classData.student_count > 0 ? Math.round((classData.present_count / classData.student_count) * 100) : 0;
                                        statValues[1].textContent = percentage + '%';
                                    }
                                }
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading student counts:', error);
                });
        }

        // Load student counts on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentCounts();
        });

        // Archive section functions
        function openArchiveSection() {
            document.getElementById('archiveSection').style.display = 'block';
            // Hide archive buttons when archive section is displayed
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'none');
            // Hide classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'none';
            document.querySelector('.classes-grid').style.display = 'none';
            loadArchivedClasses();
        }

        function closeArchiveSection() {
            document.getElementById('archiveSection').style.display = 'none';
            // Show archive buttons when archive section is closed
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'block');
            // Show classes grid and quick actions
            document.querySelector('.quick-actions').style.display = 'flex';
            document.querySelector('.classes-grid').style.display = 'grid';
        }

        // Function to load archived classes
        function loadArchivedClasses() {
            const archiveGrid = document.getElementById('archiveClassesGrid');
            const archiveBanner = document.getElementsByClassName('class-banner');
            archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Loading...</h3><p>Please wait.</p></div></div>';

            fetch('../includes/get_archived_classes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.classes.length > 0) {
                            archiveGrid.innerHTML = '';
                            data.classes.forEach(classItem => {
                                const classCard = document.createElement('div');
                                classCard.className = 'class-card';
                                classCard.setAttribute('data-class-id', classItem.id);
                                classCard.setAttribute('data-class-name', classItem.class_name);
                                classCard.innerHTML = `
                                    <div class="class-banner">
                                        <h3>${classItem.class_name}</h3>
                                        <p>${classItem.section} • ${classItem.term}</p>
                                    </div>
                                    <div class="class-footer">
                                        <div class="class-stats">
                                            <div class="class-stat">
                                                <div class="class-stat-value">0</div>
                                                <div class="class-stat-label">Students</div>
                                            </div>
                                            <div class="class-stat">
                                                <div class="class-stat-value">0%</div>
                                                <div class="class-stat-label">Attendance</div>
                                            </div>
                                        </div>
                                        <div class="class-actions">
                                            <button class="class-action-btn unarchive-btn" onclick="unarchiveClass(${classItem.id})"><i class="fas fa-box-open"></i> Unarchive</button>
                                        </div>
                                    </div>
                                `;
                                // Set gray gradient background for archived class banner
                                const classBanner = classCard.querySelector('.class-banner');
                                classBanner.style.background = 'linear-gradient(135deg, #a9a9a9ff 0%, #787878ff 100%)';
                                archiveGrid.appendChild(classCard);
                            });
                        } else {
                            archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>No Archived Classes</h3><p>No classes have been archived yet.</p></div></div>';
                        }
                    } else {
                        archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Error</h3><p>Failed to load archived classes.</p></div></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading archived classes:', error);
                    archiveGrid.innerHTML = '<div class="class-card"><div class="class-banner"><h3>Error</h3><p>An error occurred while loading archived classes.</p></div></div>';
                });
        }

        // Function to archive a class
        function archiveClass(classId) {
            if (confirm('Are you sure you want to archive this class?')) {
                const formData = new FormData();
                formData.append('class_id', classId);

                fetch('../includes/archive_class.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error archiving class:', error);
                    alert('An error occurred while archiving the class.');
                });
            }
        }

        // Function to unarchive a class
        function unarchiveClass(classId) {
            if (confirm('Are you sure you want to unarchive this class?')) {
                const formData = new FormData();
                formData.append('class_id', classId);

                fetch('../includes/unarchive_class.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error unarchiving class:', error);
                    alert('An error occurred while unarchiving the class.');
                });
            }
        }
    </script>
</body>
</html>
