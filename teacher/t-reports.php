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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title p {
            color: #666;
            font-size: 1rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.blue::before { background: #667eea; }
        .stat-card.green::before { background: #28a745; }
        .stat-card.orange::before { background: #ffc107; }
        .stat-card.red::before { background: #dc3545; }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-card.blue .stat-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .stat-card.green .stat-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-card.orange .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.red .stat-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .trend-up {
            color: #28a745;
        }

        .trend-down {
            color: #dc3545;
        }

        /* Report Type Tabs */
        .report-tabs {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .tab-buttons {
            display: flex;
            background: #f8f9fa;
            overflow-x: auto;
        }

        .tab-btn {
            flex: 1;
            padding: 1.25rem 1.5rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .tab-btn:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .tab-btn.active {
            background: white;
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-content {
            display: none;
            padding: 2rem;
        }

        .tab-content.active {
            display: block;
        }

        /* Filter Controls */
        .filter-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
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
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }

        .chart-placeholder {
            height: 350px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            position: relative;
            overflow: hidden;
        }

        .chart-placeholder::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.1),
                rgba(255, 255, 255, 0.1) 10px,
                transparent 10px,
                transparent 20px
            );
            animation: shimmer 20s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translate(-50%, -50%); }
            100% { transform: translate(0%, 0%); }
        }

        /* Data Table */
        .data-table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e0e0e0;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .table-search {
            position: relative;
        }

        .table-search input {
            padding: 0.5rem 1rem;
            padding-left: 2.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            width: 250px;
        }

        .table-search i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        .data-table tbody td {
            padding: 1rem 1.5rem;
        }

        .grade-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .grade-badge.excellent {
            background: #d4edda;
            color: #155724;
        }

        .grade-badge.good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .grade-badge.average {
            background: #fff3cd;
            color: #856404;
        }

        .grade-badge.poor {
            background: #f8d7da;
            color: #721c24;
        }

        /* Report Cards Grid */
        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .report-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .report-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .report-card-body {
            padding: 1.5rem;
        }

        .report-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .report-card-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .report-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .report-date {
            color: #999;
            font-size: 0.85rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .main-content {
                margin-left: 0;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .tab-buttons {
                flex-direction: column;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .chart-placeholder {
                height: 250px;
            }

            .report-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .page-header,
            .filter-section,
            .btn,
            .chart-actions {
                display: none;
            }

            .chart-container,
            .data-table-container {
                box-shadow: none;
                break-inside: avoid;
            }
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
                <a href="t-classes.php" class="nav-link">
                    <i class="fas fa-users nav-icon"></i>
                    <span>My Classes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
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
                <a href="t-reports.php" class="nav-link active">
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
    
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
                    <p>Comprehensive insights into student performance and class statistics</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                    <button class="btn btn-primary" onclick="generateReport()">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                    </div>
                    <div class="stat-value" id="totalStudents">--</div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Loading...</span>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="stat-value" id="averageGrade">--</div>
                    <div class="stat-label">Average Grade</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>Loading...</span>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <div class="stat-value" id="attendanceRate">--</div>
                    <div class="stat-label">Attendance Rate</div>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        <span>Loading...</span>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <div class="stat-value" id="atRiskStudents">--</div>
                    <div class="stat-label">At Risk Students</div>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        <span>Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Report Tabs -->
            <div class="report-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="switchTab('overview')">
                        <i class="fas fa-chart-pie"></i> Overview
                    </button>
                    <button class="tab-btn" onclick="switchTab('grades')">
                        <i class="fas fa-graduation-cap"></i> Grade Reports
                    </button>
                    <button class="tab-btn" onclick="switchTab('attendance')">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </button>
                    <button class="tab-btn" onclick="switchTab('performance')">
                        <i class="fas fa-chart-line"></i> Performance
                    </button>
                    <button class="tab-btn" onclick="switchTab('custom')">
                        <i class="fas fa-sliders-h"></i> Custom Reports
                    </button>
                </div>

                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Class</label>
                                <select>
                                    <option>All Classes</option>
                                    <option>Advanced Mathematics 101</option>
                                    <option>Calculus II</option>
                                    <option>Statistics & Probability</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Term</label>
                                <select>
                                    <option>Current Term</option>
                                    <option>Prelim</option>
                                    <option>Midterm</option>
                                    <option>Finals</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Date Range</label>
                                <input type="date" value="2025-01-01">
                            </div>
                            <div class="filter-group">
                                <label>&nbsp;</label>
                                <input type="date" value="2025-01-15">
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-bar"></i>
                                Grade Distribution
                            </div>
                            <div class="chart-actions">
                                <button class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Grade Distribution Chart</p>
                                <small style="opacity: 0.8;">Integrate with Chart.js or similar library</small>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i>
                                Performance Trends
                            </div>
                            <div class="chart-actions">
                                <button class="btn btn-secondary">
                                    <i class="fas fa-expand"></i> Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Performance Trends Over Time</p>
                                <small style="opacity: 0.8;">Line chart showing grade trends</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grade Reports Tab -->
                <div id="grades" class="tab-content">
                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Class</label>
                                <select>
                                    <option>Advanced Mathematics 101</option>
                                    <option>Calculus II</option>
                                    <option>Statistics & Probability</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Term</label>
                                <select>
                                    <option>Prelim</option>
                                    <option>Midterm</option>
                                    <option>Finals</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Grade Range</label>
                                <select>
                                    <option>All Grades</option>
                                    <option>90-100 (Excellent)</option>
                                    <option>80-89 (Good)</option>
                                    <option>70-79 (Average)</option>
                                    <option>Below 70 (At Risk)</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="btn btn-secondary">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </div>

                    <!-- Grade Table -->
                    <div class="data-table-container">
                        <div class="table-header">
                            <div class="table-title">Grade Summary - Advanced Mathematics 101</div>
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search students...">
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Name</th>
                                    <th>Class Standing</th>
                                    <th>Exam</th>
                                    <th>Project</th>
                                    <th>Final Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2021001</td>
                                    <td>Doe, John A.</td>
                                    <td>88</td>
                                    <td>92</td>
                                    <td>90</td>
                                    <td><strong>90.2</strong></td>
                                    <td><span class="grade-badge excellent">Excellent</span></td>
                                </tr>
                                <tr>
                                    <td>2021002</td>
                                    <td>Smith, Jane B.</td>
                                    <td>85</td>
                                    <td>88</td>
                                    <td>87</td>
                                    <td><strong>86.8</strong></td>
                                    <td><span class="grade-badge good">Good</span></td>
                                </tr>
                                <tr>
                                    <td>2021003</td>
                                    <td>Johnson, Mike</td>
                                    <td>78</td>
                                    <td>82</td>
                                    <td>80</td>
                                    <td><strong>80.2</strong></td>
                                    <td><span class="grade-badge good">Good</span></td>
                                </tr>
                                <tr>
                                    <td>2021004</td>
                                    <td>Williams, Anna C.</td>
                                    <td>72</td>
                                    <td>75</td>
                                    <td>73</td>
                                    <td><strong>73.3</strong></td>
                                    <td><span class="grade-badge average">Average</span></td>
                                </tr>
                                <tr>
                                    <td>2021005</td>
                                    <td>Garcia, Lisa E.</td>
                                    <td>65</td>
                                    <td>68</td>
                                    <td>70</td>
                                    <td><strong>68.1</strong></td>
                                    <td><span class="grade-badge poor">At Risk</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Attendance Tab -->
                <div id="attendance" class="tab-content">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-calendar-alt"></i>
                                Attendance Overview
                            </div>
                            <div class="chart-actions">
                                <button class="btn btn-secondary">
                                    <i class="fas fa-download"></i> Download Report
                                </button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-chart-pie" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Attendance Statistics</p>
                                <small style="opacity: 0.8;">Present, Absent, Late, Excused breakdown</small>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-calendar-week"></i>
                                Weekly Attendance Trend
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-chart-area" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Attendance Trends</p>
                                <small style="opacity: 0.8;">Weekly attendance patterns</small>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <div class="data-table-container">
                        <div class="table-header">
                            <div class="table-title">Recent Attendance Records</div>
                            <div class="table-search">
                                <i class="fas fa-search"></i>
                                <input type="text" placeholder="Search students...">
                            </div>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Session</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2025-01-15</td>
                                    <td>Doe, John A.</td>
                                    <td>Morning</td>
                                    <td><span class="grade-badge excellent">Present</span></td>
                                    <td>8:30 AM</td>
                                </tr>
                                <tr>
                                    <td>2025-01-15</td>
                                    <td>Smith, Jane B.</td>
                                    <td>Morning</td>
                                    <td><span class="grade-badge good">Present</span></td>
                                    <td>8:45 AM</td>
                                </tr>
                                <tr>
                                    <td>2025-01-15</td>
                                    <td>Johnson, Mike</td>
                                    <td>Morning</td>
                                    <td><span class="grade-badge average">Late</span></td>
                                    <td>9:15 AM</td>
                                </tr>
                                <tr>
                                    <td>2025-01-14</td>
                                    <td>Williams, Anna C.</td>
                                    <td>Afternoon</td>
                                    <td><span class="grade-badge poor">Absent</span></td>
                                    <td>--</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Performance Tab -->
                <div id="performance" class="tab-content">
                    <div class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label>Student</label>
                                <select>
                                    <option>All Students</option>
                                    <option>Doe, John A. (2021001)</option>
                                    <option>Smith, Jane B. (2021002)</option>
                                    <option>Johnson, Mike (2021003)</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Subject</label>
                                <select>
                                    <option>All Subjects</option>
                                    <option>Mathematics</option>
                                    <option>Science</option>
                                    <option>English</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Time Period</label>
                                <select>
                                    <option>Last 30 Days</option>
                                    <option>Last 3 Months</option>
                                    <option>Last 6 Months</option>
                                    <option>Academic Year</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <button class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> Analyze
                            </button>
                        </div>
                    </div>

                    <!-- Performance Charts -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-chart-line"></i>
                                Individual Student Progress
                            </div>
                            <div class="chart-actions">
                                <button class="btn btn-secondary">
                                    <i class="fas fa-expand"></i> Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Student Performance Over Time</p>
                                <small style="opacity: 0.8;">Track individual student improvement</small>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-bullseye"></i>
                                Performance Insights
                            </div>
                        </div>
                        <div class="chart-placeholder">
                            <div style="position: relative; z-index: 1;">
                                <i class="fas fa-lightbulb" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>AI-Powered Insights</p>
                                <small style="opacity: 0.8;">Automated performance analysis</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Reports Tab -->
                <div id="custom" class="tab-content">
                    <div class="report-cards">
                        <div class="report-card" onclick="createCustomReport('grade-summary')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">Grade Summary Report</div>
                                    <div class="report-card-description">Comprehensive grade analysis across all terms</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Generate detailed grade summaries with breakdowns by term, component, and student performance levels.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: Never</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>

                        <div class="report-card" onclick="createCustomReport('attendance-analysis')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">Attendance Analysis</div>
                                    <div class="report-card-description">Detailed attendance patterns and trends</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Analyze attendance patterns, identify trends, and generate reports for individual students or entire classes.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: 2 days ago</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>

                        <div class="report-card" onclick="createCustomReport('progress-tracking')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">Progress Tracking</div>
                                    <div class="report-card-description">Monitor student progress over time</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Track individual student progress, identify improvement areas, and generate comparative analysis reports.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: 1 week ago</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>

                        <div class="report-card" onclick="createCustomReport('class-comparison')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">Class Comparison</div>
                                    <div class="report-card-description">Compare performance across different classes</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Compare performance metrics, attendance rates, and grade distributions across multiple classes or sections.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: 3 days ago</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>

                        <div class="report-card" onclick="createCustomReport('risk-assessment')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">At-Risk Students</div>
                                    <div class="report-card-description">Identify students needing additional support</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Identify students at risk of failing, analyze contributing factors, and generate intervention recommendations.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: Today</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>

                        <div class="report-card" onclick="createCustomReport('custom-query')">
                            <div class="report-card-header">
                                <div class="report-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <div>
                                    <div class="report-card-title">Custom Query Report</div>
                                    <div class="report-card-description">Build your own custom reports</div>
                                </div>
                            </div>
                            <div class="report-card-body">
                                <p>Create highly customized reports using advanced filtering options and data selection criteria.</p>
                            </div>
                            <div class="report-meta">
                                <div class="report-date">Last generated: 5 days ago</div>
                                <button class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Report Builder -->
                    <div class="chart-container" id="custom-builder" style="display: none;">
                        <div class="chart-header">
                            <div class="chart-title">
                                <i class="fas fa-tools"></i>
                                Custom Report Builder
                            </div>
                            <div class="chart-actions">
                                <button class="btn btn-secondary" onclick="hideCustomBuilder()">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                        <div style="padding: 2rem;">
                            <div class="filter-section">
                                <div class="filter-row">
                                    <div class="filter-group">
                                        <label>Report Type</label>
                                        <select id="report-type">
                                            <option>Grade Analysis</option>
                                            <option>Attendance Report</option>
                                            <option>Performance Summary</option>
                                            <option>Custom Query</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label>Data Fields</label>
                                        <select multiple size="3">
                                            <option>Student Name</option>
                                            <option>Student Number</option>
                                            <option>Grades</option>
                                            <option>Attendance</option>
                                            <option>Class</option>
                                            <option>Term</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label>Filters</label>
                                        <select multiple size="3">
                                            <option>Grade Range: 90-100</option>
                                            <option>Grade Range: 80-89</option>
                                            <option>Grade Range: 70-79</option>
                                            <option>Grade Range: Below 70</option>
                                            <option>Attendance: Excellent</option>
                                            <option>Attendance: Good</option>
                                            <option>Attendance: Poor</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-row">
                                    <div class="filter-group">
                                        <label>Sort By</label>
                                        <select>
                                            <option>Student Name</option>
                                            <option>Grade (High to Low)</option>
                                            <option>Grade (Low to High)</option>
                                            <option>Attendance Rate</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label>Format</label>
                                        <select>
                                            <option>Table View</option>
                                            <option>Chart View</option>
                                            <option>PDF Export</option>
                                            <option>Excel Export</option>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label>Report Name</label>
                                        <input type="text" placeholder="Enter report name">
                                    </div>
                                </div>
                                <div class="filter-actions">
                                    <button class="btn btn-secondary">
                                        <i class="fas fa-save"></i> Save Template
                                    </button>
                                    <button class="btn btn-success">
                                        <i class="fas fa-play"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
        }

        sidebarToggle.addEventListener('click', toggleSidebarMode);

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/teacher-login.php';
            }
        }

        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Generate report functionality
        function generateReport() {
            const activeTab = document.querySelector('.tab-content.active').id;
            let params = new URLSearchParams();
            params.append('tab', activeTab);

            // Get filters based on active tab
            if (activeTab === 'overview') {
                const selects = document.querySelectorAll('#overview select');
                const inputs = document.querySelectorAll('#overview input[type="date"]');
                if (selects.length >= 2) {
                    params.append('class', selects[0].value);
                    params.append('term', selects[1].value);
                }
                if (inputs.length >= 2) {
                    params.append('date_from', inputs[0].value);
                    params.append('date_to', inputs[1].value);
                }
            } else if (activeTab === 'grades') {
                const selects = document.querySelectorAll('#grades select');
                if (selects.length >= 3) {
                    params.append('class', selects[0].value);
                    params.append('term', selects[1].value);
                    params.append('grade_range', selects[2].value);
                }
            } else if (activeTab === 'attendance') {
                // No specific filters for attendance tab
                params.append('report_type', 'attendance');
            } else if (activeTab === 'performance') {
                const selects = document.querySelectorAll('#performance select');
                if (selects.length >= 3) {
                    params.append('student', selects[0].value);
                    params.append('subject', selects[1].value);
                    params.append('time_period', selects[2].value);
                }
            } else if (activeTab === 'custom') {
                const reportType = document.getElementById('report-type');
                if (reportType) {
                    params.append('report_type', reportType.value);
                }
                // Add other custom fields if needed
            }

            // Open report in new window
            window.open('../includes/generate_comprehensive_report.php?' + params.toString(), '_blank');
        }

        // Apply filters functionality
        function applyFilters() {
            alert('Filters applied! In a real implementation, this would update the charts and data tables based on the selected filters.');
        }

        // Create custom report functionality
        function createCustomReport(reportType) {
            const builder = document.getElementById('custom-builder');
            builder.style.display = 'block';
            builder.scrollIntoView({ behavior: 'smooth' });

            // Pre-select report type
            const reportTypeSelect = document.getElementById('report-type');
            reportTypeSelect.value = reportType.charAt(0).toUpperCase() + reportType.slice(1).replace('-', ' ');
        }

        // Hide custom builder
        function hideCustomBuilder() {
            document.getElementById('custom-builder').style.display = 'none';
        }

        // Fetch and update quick stats
        async function loadQuickStats() {
            try {
                const response = await fetch('../includes/get_teacher_reports.php');
                const data = await response.json();

                if (data.success) {
                    const stats = data.statistics;

                    // Update stat values
                    document.getElementById('totalStudents').textContent = stats.total_students;
                    document.getElementById('averageGrade').textContent = stats.average_grade + '%';
                    document.getElementById('attendanceRate').textContent = stats.attendance_rate + '%';
                    document.getElementById('atRiskStudents').textContent = stats.at_risk_students;

                    // Calculate and update grade trend
                    const trends = data.performance_trends;
                    if (trends.length >= 2) {
                        const current = trends[trends.length - 1].average;
                        const previous = trends[trends.length - 2].average;
                        if (previous > 0) {
                            const change = ((current - previous) / previous) * 100;
                            const trendElement = document.querySelector('.stat-card.green .stat-trend span');
                            if (change > 0) {
                                trendElement.innerHTML = '<i class="fas fa-arrow-up"></i> ' + Math.abs(change).toFixed(1) + '% improvement';
                                trendElement.parentElement.classList.remove('trend-down');
                                trendElement.parentElement.classList.add('trend-up');
                            } else if (change < 0) {
                                trendElement.innerHTML = '<i class="fas fa-arrow-down"></i> ' + Math.abs(change).toFixed(1) + '% from last month';
                                trendElement.parentElement.classList.remove('trend-up');
                                trendElement.parentElement.classList.add('trend-down');
                            } else {
                                trendElement.textContent = 'No change from last month';
                            }
                        }
                    }
                } else {
                    console.error('Failed to load stats:', data.message);
                }
            } catch (error) {
                console.error('Error loading quick stats:', error);
            }
        }

        // Table search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInputs = document.querySelectorAll('.table-search input');

            searchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = this.closest('.data-table-container').querySelector('.data-table');
                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });

        // Chart placeholder interactions
        document.addEventListener('DOMContentLoaded', function() {
            const chartPlaceholders = document.querySelectorAll('.chart-placeholder');

            chartPlaceholders.forEach(placeholder => {
                placeholder.addEventListener('click', function() {
                    alert('Chart integration would be implemented here using Chart.js or similar library. This placeholder shows where interactive charts would be displayed.');
                });
            });
        });

        // Report card hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const reportCards = document.querySelectorAll('.report-card');

            reportCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                    this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.2)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
                });
            });

            // Load quick stats on page load
            loadQuickStats();
        });
    </script>
</body>
</html>
