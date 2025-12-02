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

// Fetch classes for the current teacher
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
    <title>Attendance Management - Acadex</title>
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
            margin-bottom: 0.5rem;
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

        /* Selected Class Section */
        .selected-class-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            padding: 1rem;
        }

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



        /* Stats Cards */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.present::before { background: #28a745; }
        .stat-card.absent::before { background: #dc3545; }
        .stat-card.late::before { background: #ffc107; }
        .stat-card.excused::before { background: #6c757d; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.present .stat-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-card.absent .stat-icon { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .stat-card.late .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.excused .stat-icon { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Enhanced Controls */
        .attendance-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .date-input-wrapper {
            position: relative;
        }

        .date-input-wrapper input {
            padding: 0.75rem 1rem;
            padding-left: 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .date-input-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .date-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            border: 2px solid #e0e0e0;
        }

        .btn-secondary:hover {
            background: #e8eaf0;
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

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            justify-content: center;
            border-radius: 50%;
        }

        /* Search and Filter */
        .search-filter-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            padding-left: 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Bulk Actions */
        .bulk-actions {
            background: #fff3cd;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
            align-items: center;
            justify-content: space-between;
            border: 2px solid #ffc107;
        }

        .bulk-actions.active {
            display: flex;
        }

        .bulk-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bulk-count {
            background: #ffc107;
            color: #000;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .bulk-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Enhanced Table */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
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

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .attendance-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .attendance-table thead th:first-child {
            width: 50px;
        }

        .attendance-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .attendance-table tbody tr.selected {
            background: #e3f2fd;
        }

        .attendance-table tbody td {
            padding: 1rem 1.5rem;
        }

        .student-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .student-details {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.2rem;
        }

        .student-number {
            color: #999;
            font-size: 0.85rem;
            font-family: 'Courier New', monospace;
        }

        .attendance-status {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status-radio {
            display: none;
        }

        .status-label {
            padding: 0.5rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }

        .status-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .status-radio:checked + .status-label {
            transform: scale(1.05);
        }

        .status-radio:checked + .status-label.status-present {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }

        .status-radio:checked + .status-label.status-absent {
            border-color: #dc3545;
            background: #dc3545;
            color: white;
        }

        .status-radio:checked + .status-label.status-late {
            border-color: #ffc107;
            background: #ffc107;
            color: #000;
        }

        .status-radio:checked + .status-label.status-excused {
            border-color: #6c757d;
            background: #6c757d;
            color: white;
        }

        /* Attendance History Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow: hidden;
            animation: slideIn 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        .history-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .history-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background: #e0e0e0;
        }

        .history-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }

        .history-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 3px solid #667eea;
        }

        .history-date {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .history-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .history-status.present { background: #d4edda; color: #155724; }
        .history-status.absent { background: #f8d7da; color: #721c24; }
        .history-status.late { background: #fff3cd; color: #856404; }
        .history-status.excused { background: #e2e3e5; color: #383d41; }

        /* Export Menu */
        .export-menu {
            position: relative;
        }

        .export-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            min-width: 180px;
            z-index: 100;
        }

        .export-dropdown.active {
            display: block;
        }

        .export-option {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .export-option:first-child {
            border-radius: 10px 10px 0 0;
        }

        .export-option:last-child {
            border-radius: 0 0 10px 10px;
        }

        .export-option:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        /* Success Toast */
        .success-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            gap: 0.75rem;
            z-index: 3000;
            animation: slideInRight 0.3s ease;
        }

        .success-toast.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

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

        .no-students {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 2rem;
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
            .attendance-controls {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            .date-selector {
                justify-content: center;
            }
            .attendance-actions {
                justify-content: center;
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

        .no-students-info {
            text-align: center;
            color: #cf5300ff;
            font-style: italic;
            padding: 1rem;
            background: #cf530023;
            border: 1px solid #cf5300ff;
            border-radius: 0.5rem;
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
                <a href="t-grades.php" class="nav-link">
                    <i class="fas fa-edit nav-icon"></i>
                    <span>Grade Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="t-attendance.php" class="nav-link active">
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
                <h1>Attendance Management</h1>
                <p>Track and manage student attendance for your classes.</p>
            </div>
            <div class="top-actions">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">5</span>
                </button>
                <button class="logout-btn" onclick="logout()">Logout</button>
            </div>
        </div>

        <!-- Classes Grid -->
        <div class="classes-grid" id="classesGrid">
            <?php if (empty($classes)): ?>
                <div class="class-card">
                    <div class="class-banner">
                        <h3>No Classes Found</h3>
                        <p>You haven't added any classes yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($classes as $class): ?>
                    <div class="class-card" data-class-id="<?php echo $class['id']; ?>" data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>" data-section="<?php echo htmlspecialchars($class['section']); ?>" data-term="<?php echo htmlspecialchars($class['term']); ?>">
                        <div class="class-banner">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p><?php echo htmlspecialchars($class['section']); ?> • <?php echo htmlspecialchars($class['term']); ?></p>
                        </div>
                        <div class="class-stats">
                            <div class="class-stat">
                                <div class="class-stat-value">0</div>
                                <div class="class-stat-label">Students</div>
                            </div>
                            <div class="class-stat">
                                <div class="class-stat-value">0%</div>
                                <div class="class-stat-label">Attendance Today</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Selected Class Section (hidden by default)-->
        <div class="selected-class-container" id="selectedClassSection" style="display: none; margin-top: 2rem;">
            <div class="selected-class-header">
                <h2 id="selectedClassName">Selected Class</h2>
                <button class="close-selected-btn" onclick="closeSelectedClass()">&times;</button>
            </div>

            <!-- BEGIN: NEW INSIDE ATTENDANCE UI (appears AFTER selecting a class) -->
            <section id="insideAttendanceUI">
                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card present">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value" id="presentCount">0</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-card absent">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value" id="absentCount">0</div>
                        <div class="stat-label">Absent</div>
                    </div>
                    <div class="stat-card late">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value" id="lateCount">0</div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-card excused">
                        <div class="stat-icon"><i class="fas fa-file-medical"></i></div>
                        <div class="stat-value" id="excusedCount">0</div>
                        <div class="stat-label">Excused</div>
                    </div>
                </div>


                <!-- Controls -->
                <div class="attendance-controls">
                    <div class="controls-row">
                        <div class="control-group">
                            <div class="date-input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="attendanceDate" value="<?= date('Y-m-d'); ?>">
                            </div>
                            <div class="session-input-wrapper">
                                <select id="attendanceSession" class="filter-select" style="min-width:150px;">
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                    <option value="makeup">Make-up Class</option>
                                </select>
                            </div>
                            <button class="btn btn-secondary" onclick="loadAttendance()"><i class="fas fa-download"></i> Load</button>
                        </div>
                        <div class="quick-actions">
                            <button class="btn btn-secondary btn-icon" onclick="previousDay()" title="Previous Day"><i class="fas fa-chevron-left"></i></button>
                            <button class="btn btn-secondary" onclick="setToday()"><i class="fas fa-calendar-day"></i> Today</button>
                            <button class="btn btn-secondary btn-icon" onclick="nextDay()" title="Next Day"><i class="fas fa-chevron-right"></i></button>
                            <button class="btn btn-success" onclick="saveAttendance()"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </div>


                <!-- Search and Filter -->
                <div class="search-filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchStudent" placeholder="Search by name or student number..." oninput="filterStudents()">
                    </div>
                    <select class="filter-select" id="filterStatus" onchange="filterByStatus()">
                        <option value="all">All Students</option>
                        <option value="present">Present Only</option>
                        <option value="absent">Absent Only</option>
                        <option value="late">Late Only</option>
                        <option value="excused">Excused Only</option>
                    </select>
                    <div class="export-menu">
                        <button class="btn btn-secondary" onclick="toggleExportMenu()"><i class="fas fa-download"></i> Export</button>
                        <div class="export-dropdown" id="exportDropdown">
                            <div class="export-option" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> Download File</div>
                            <div class="export-option" onclick="printAttendance()"><i class="fas fa-print"></i> Print</div>
                        </div>
                    </div>
                </div>
            


                <!-- Bulk Actions -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="bulk-info"><span class="bulk-count" id="selectedCount">0</span><span>students selected</span></div>
                    <div class="bulk-buttons">
                        <button class="btn btn-secondary" onclick="markBulk('present')"><i class="fas fa-check"></i> Mark Present</button>
                        <button class="btn btn-secondary" onclick="markBulk('absent')"><i class="fas fa-times"></i> Mark Absent</button>
                        <button class="btn btn-secondary" onclick="markBulk('late')"><i class="fas fa-clock"></i> Mark Late</button>
                        <button class="btn btn-secondary" onclick="markBulk('excused')"><i class="fas fa-file-medical"></i> Mark Excused</button>
                        <button class="btn btn-secondary" onclick="clearSelection()"><i class="fas fa-undo"></i> Clear</button>
                    </div>
                </div>

                <!-- No attendance record for today's date and session message -->
                <div class="no-students-info" id="noAttendanceMessage" style="display: none;"></div>

                <!-- Attendance Table -->
                <div class="table-container">
                    <div class="table-header">
                        <div class="table-title"><i class="fas fa-users"></i> Student Attendance (<span id="totalStudents">0</span>)</div>
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-icon" onclick="selectAll()" title="Select All"><i class="fas fa-check-double"></i></button>
                            <button class="btn btn-secondary btn-icon" onclick="refreshTable()" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="student-checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" style="display: none;"></th>
                                <th>Student</th>
                                <th>Program</th>
                                <th>Attendance Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTableBody"><!-- rows injected --></tbody>
                    </table>
                </div>
            </section>
            <!-- END: NEW INSIDE ATTENDANCE UI -->
        </div>
    </main>

    <!-- History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> Attendance History</h2>
                <button class="close-btn" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <h3 id="historyStudentHeader" style="margin-bottom:1rem;"></h3>
                <div class="history-timeline" id="historyTimeline"><!-- items injected --></div>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="success-toast" id="successToast"><i class="fas fa-check-circle"></i><span id="toastMessage">Action completed successfully!</span></div>

    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

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

        // Function to reset attendance UI
        function resetAttendanceUI() {
            // Reset stats
            document.getElementById('presentCount').textContent = '0';
            document.getElementById('absentCount').textContent = '0';
            document.getElementById('lateCount').textContent = '0';
            document.getElementById('excusedCount').textContent = '0';

            // Reset date to today
            document.getElementById('attendanceDate').value = new Date().toISOString().slice(0,10);

            // Reset search and filter
            document.getElementById('searchStudent').value = '';
            document.getElementById('filterStatus').value = 'all';

            // Reset bulk actions
            document.getElementById('selectedCount').textContent = '0';
            document.getElementById('bulkActions').classList.remove('active');
            document.getElementById('selectAllCheckbox').checked = false;

            // Clear table
            document.getElementById('attendanceTableBody').innerHTML = '';
            document.getElementById('totalStudents').textContent = '0';

            // Hide export dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }

        // Class selection functionality
        function selectClass(className, classId) {
            document.getElementById('selectedClassName').textContent = className;
            document.getElementById('selectedClassSection').style.display = 'block';
            document.getElementById('selectedClassSection').setAttribute('data-class-id', classId);
            // Hide classes grid
            document.getElementById('classesGrid').style.display = 'none';
            // Scroll to selected class section
            document.getElementById('selectedClassSection').scrollIntoView({ behavior: 'smooth' });
            // Reset attendance UI
            resetAttendanceUI();
            // Load students for attendance
            loadStudentsForAttendance();
        }

        function closeSelectedClass() {
            document.getElementById('selectedClassSection').style.display = 'none';
            // Show classes grid
            document.getElementById('classesGrid').style.display = 'grid';
        }

        // Function to load students for attendance
        function loadStudentsForAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            fetch('../includes/get_students.php?class_id=' + classId)
                .then(res => res.json())
                .then(data => {
                    const body = document.getElementById('attendanceTableBody');
                    body.innerHTML = '';
                    if (!data.success || data.students.length === 0) return;

                    data.students.forEach(st => {
                        const avatar = (st.first_name[0] + st.last_name[0]).toUpperCase();
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td><input type="checkbox" class="row-checkbox" onclick="updateBulkSelection()"></td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">${avatar}</div>
                                    <div class="student-details">
                                        <div class="student-name">${st.last_name}, ${st.first_name} ${st.middle_initial || ''}</div>
                                        <div class="student-number">${st.student_number}</div>
                                    </div>
                                </div>
                            </td>
                            <td>${st.program || 'N/A'}</td>
                            <td class="attendance-status">
                                <input type="radio" name="status_${st.id}" value="present" class="status-radio" id="present_${st.id}" checked onchange="updateStats()">
                                <label for="present_${st.id}" class="status-label status-present">Present</label>

                                <input type="radio" name="status_${st.id}" value="absent" class="status-radio" id="absent_${st.id}" onchange="updateStats()">
                                <label for="absent_${st.id}" class="status-label status-absent">Absent</label>

                                <input type="radio" name="status_${st.id}" value="late" class="status-radio" id="late_${st.id}" onchange="updateStats()">
                                <label for="late_${st.id}" class="status-label status-late">Late</label>

                                <input type="radio" name="status_${st.id}" value="excused" class="status-radio" id="excused_${st.id}" onchange="updateStats()">
                                <label for="excused_${st.id}" class="status-label status-excused">Excused</label>
                            </td>
                            <td>
                                <button class="btn btn-secondary btn-icon" onclick="viewHistory(${st.id})"><i class="fas fa-history"></i></button>
                            </td>
                        `;
                        body.appendChild(row);
                    });

                    document.getElementById('totalStudents').textContent = data.students.length;
                    updateStats();
                    // Load existing attendance after students are loaded
                    loadAttendance();
                });
        }

        function updateStats() {
            const present = document.querySelectorAll('.status-radio[value="present"]:checked').length;
            const absent = document.querySelectorAll('.status-radio[value="absent"]:checked').length;
            const late = document.querySelectorAll('.status-radio[value="late"]:checked').length;
            const excused = document.querySelectorAll('.status-radio[value="excused"]:checked').length;

            document.getElementById('presentCount').textContent = present;
            document.getElementById('absentCount').textContent = absent;
            document.getElementById('lateCount').textContent = late;
            document.getElementById('excusedCount').textContent = excused;
        }

        function toggleSelectAll() {
            const master = document.getElementById('selectAllCheckbox');
            document.querySelectorAll('.row-checkbox').forEach(box => box.checked = master.checked);
            updateBulkSelection();
        }

        function updateBulkSelection() {
            const selected = document.querySelectorAll('.row-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = selected;
            const bulk = document.getElementById('bulkActions');
            bulk.classList.toggle('active', selected > 0);
        }

        function markBulk(status) {
            document.querySelectorAll('.row-checkbox:checked').forEach(box => {
                const row = box.closest('tr');
                const id = row.querySelector('.status-radio').name.split('_')[1];
                document.getElementById(status + '_' + id).checked = true;
            });
            updateStats();
        }

        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(b => b.checked = false);
            updateBulkSelection();
        }

        function selectAll() {
            document.querySelectorAll('.row-checkbox').forEach(box => box.checked = true);
            updateBulkSelection();
        }

        function refreshTable() {
            loadStudentsForAttendance();
        }

        function filterStudents() {
            const query = document.getElementById('searchStudent').value.toLowerCase();
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        function filterByStatus() {
            const filter = document.getElementById('filterStatus').value;
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                    return;
                }
                const checked = row.querySelector(`.status-radio[value="${filter}"]`).checked;
                row.style.display = checked ? '' : 'none';
            });
        }

        function viewHistory(id) {
            const modal = document.getElementById('historyModal');
            const timeline = document.getElementById('historyTimeline');
            const header = document.getElementById('historyStudentHeader');
            timeline.innerHTML = '<p>Loading...</p>';
            modal.style.display = 'block';

            fetch('../includes/get_history.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        timeline.innerHTML = '<p>No history found.</p>';
                        return;
                    }
                    header.textContent = `Attendance History for Student ID: ${id}`;
                    timeline.innerHTML = data.history.map(h => `
                        <div class="history-item">
                            <div class="history-date">${h.date}</div>
                            <span class="history-status ${h.status}">${h.status.toUpperCase()}</span>
                        </div>
                    `).join('');
                });
        }

        function closeHistoryModal() {
            document.getElementById('historyModal').style.display = 'none';
        }

        function previousDay() {
            const d = new Date(document.getElementById('attendanceDate').value);
            d.setDate(d.getDate() - 1);
            document.getElementById('attendanceDate').value = d.toISOString().slice(0,10);
            loadAttendance();
        }

        function nextDay() {
            const d = new Date(document.getElementById('attendanceDate').value);
            d.setDate(d.getDate() + 1);
            document.getElementById('attendanceDate').value = d.toISOString().slice(0,10);
            loadAttendance();
        }

        function setToday() {
            document.getElementById('attendanceDate').value = new Date().toISOString().slice(0,10);
            loadAttendance();
        }

        // Function to load existing attendance for a date
        function loadAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const session = document.getElementById('attendanceSession').value;

            // Reset all radios to present (default) before loading new data
            document.querySelectorAll('.status-radio[value="present"]').forEach(radio => radio.checked = true);

            fetch(`../includes/get_attendance.php?class_id=${classId}&date=${date}&session=${session}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('noAttendanceMessage').style.display = 'none';
                        return;
                    }

                    data.attendance.forEach(r => {
                        const radio = document.getElementById(`${r.status}_${r.student_id}`);
                        if (radio) radio.checked = true;
                    });
                    updateStats();

                    // Check if no attendance records for this date and session
                    if (data.attendance.length === 0) {
                        const dateObj = new Date(date);
                        const month = dateObj.toLocaleString('default', { month: 'short' });
                        const day = dateObj.getDate();
                        const year = dateObj.getFullYear();
                        const formattedDate = `${month}. ${day}, ${year}`;
                        const sessionText = document.getElementById('attendanceSession').options[document.getElementById('attendanceSession').selectedIndex].text;
                        document.getElementById('noAttendanceMessage').innerHTML = `<i class="fas fa-info-circle"></i> You haven't recorded any attendance for <strong>${formattedDate}</strong> (${sessionText}).`;
                        document.getElementById('noAttendanceMessage').style.display = 'block';
                    } else {
                        document.getElementById('noAttendanceMessage').style.display = 'none';
                    }
                });
        }

        // Function to save attendance
        function saveAttendance() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const session = document.getElementById('attendanceSession').value;

            const attendance = [];
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentId = row.querySelector('.status-radio').name.split('_')[1];
                const status = row.querySelector('.status-radio:checked').value;
                attendance.push({ student_id: studentId, status, date, session, class_id: classId });
            });

            fetch('../includes/save_attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ attendance })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('successToast').classList.add('active');
                setTimeout(() => document.getElementById('successToast').classList.remove('active'), 3000);
            });
        }

        // Add click event listeners to class cards
        document.addEventListener('DOMContentLoaded', function() {
            const classCards = document.querySelectorAll('.class-card');
            classCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    const className = this.getAttribute('data-class-name');
                    const classId = this.getAttribute('data-class-id');
                    selectClass(className, classId);
                });
            });

            // Add event listeners to load attendance when date or session changes
            document.getElementById('attendanceDate').addEventListener('change', loadAttendance);
            document.getElementById('attendanceSession').addEventListener('change', function() {
                console.log('Selected session:', this.value);
                loadAttendance();
            });
        });

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

        function toggleExportMenu() {
            const dropdown = document.getElementById('exportDropdown');
            dropdown.classList.toggle('active');
        }

        function exportToCSV() {
            const classId = document.getElementById('selectedClassSection').dataset.classId;
            const date = document.getElementById('attendanceDate').value;
            const className = document.getElementById('selectedClassName').textContent;

            // Prepare CSV headers
            let csv = 'Student Number,Name,Program,Status\n';

            // Get all table rows
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentNumber = row.querySelector('.student-number').textContent;
                const studentName = row.querySelector('.student-name').textContent;
                const program = row.querySelector('td:nth-child(3)').textContent;
                const status = row.querySelector('.status-radio:checked').value;

                csv += `"${studentNumber}","${studentName}","${program}","${status}"\n`;
            });

            // Create and download CSV file
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `Acadex_${className.replace(/\s+/g, '_')}_${date}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Hide dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }

        function printAttendance() {
            const className = document.getElementById('selectedClassName').textContent;
            const date = document.getElementById('attendanceDate').value;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Acadex Attendance Report - ${className}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #667eea; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .present { color: #28a745; }
                        .absent { color: #dc3545; }
                        .late { color: #ffc107; }
                        .excused { color: #6c757d; }
                    </style>
                </head>
                <body>
                    <h1>Acadex Attendance Report</h1>
                    <p><strong>Class:</strong> ${className} - <?php echo htmlspecialchars($class['section']); ?> - <?php echo htmlspecialchars($class['term']); ?></p>
                    <p><strong>Date:</strong> ${date}</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Name</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `);

            // Add table rows
            document.querySelectorAll('#attendanceTableBody tr').forEach(row => {
                const studentNumber = row.querySelector('.student-number').textContent;
                const studentName = row.querySelector('.student-name').textContent;
                const program = row.querySelector('td:nth-child(3)').textContent;
                const status = row.querySelector('.status-radio:checked').value;

                printWindow.document.write(`
                    <tr>
                        <td>${studentNumber}</td>
                        <td>${studentName}</td>
                        <td>${program}</td>
                        <td class="${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</td>
                    </tr>
                `);
            });

            printWindow.document.write(`
                        </tbody>
                    </table>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.print();

            // Hide dropdown
            document.getElementById('exportDropdown').classList.remove('active');
        }
    </script>
</body>
</html>