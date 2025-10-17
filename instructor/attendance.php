<?php
include 'header.php';   
include '../connection.php';
session_start();

// Session & Role Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'instructor') {
    header("Location: index.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

// Capture filter params - AUTO-POPULATE FROM DASHBOARD CLICK
$filter_year    = isset($_GET['year']) ? $_GET['year'] : null;
$filter_section = isset($_GET['section']) ? $_GET['section'] : null;
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : null;
$filter_date    = isset($_GET['date']) ? $_GET['date'] : null;
$filter_status  = isset($_GET['status']) ? $_GET['status'] : null;
$filter_month   = isset($_GET['month']) ? $_GET['month'] : null;
$print_view     = isset($_GET['print']) ? true : false;

// Check if this is a direct link from dashboard (has year and section but no form submission)
$from_dashboard = ($filter_year && $filter_section && !isset($_GET['form_submitted']));

$attendance_data = [];
$available_dates = [];
$available_classes = [];
$summary_data = [];
$monthly_stats = [];

// Get available summary records for this instructor
$summary_query = "SELECT DISTINCT 
                  id,
                  year_level as year,
                  section,
                  subject_name as subject,
                  session_date as date,
                  instructor_name,
                  time_in,
                  time_out,
                  total_students,
                  present_count,
                  absent_count,
                  attendance_rate
                  FROM instructor_attendance_summary 
                  WHERE instructor_id = ? 
                  ORDER BY session_date DESC, year_level, section";
$summary_stmt = $db->prepare($summary_query);
$summary_stmt->bind_param("s", $instructor_id);
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();

while ($summary_row = $summary_result->fetch_assoc()) {
    $available_classes[] = [
        'year' => $summary_row['year'],
        'section' => $summary_row['section'],
        'subject' => $summary_row['subject']
    ];
    
    if ($summary_row['date']) {
        $available_dates[] = $summary_row['date'];
    }
    
    // Store summary data for display
    $summary_key = $summary_row['year'] . '-' . $summary_row['section'] . '-' . $summary_row['date'];
    $summary_data[$summary_key] = $summary_row;
}
$summary_stmt->close();

// Get monthly statistics
$monthly_query = "SELECT 
                  YEAR(session_date) as year,
                  MONTH(session_date) as month,
                  COUNT(DISTINCT session_date) as session_count,
                  SUM(total_students) as total_students,
                  SUM(present_count) as total_present,
                  AVG(attendance_rate) as avg_attendance_rate
                  FROM instructor_attendance_summary 
                  WHERE instructor_id = ?
                  GROUP BY YEAR(session_date), MONTH(session_date)
                  ORDER BY year DESC, month DESC";
$monthly_stmt = $db->prepare($monthly_query);
$monthly_stmt->bind_param("s", $instructor_id);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();

while ($month_row = $monthly_result->fetch_assoc()) {
    $monthly_stats[] = $month_row;
}
$monthly_stmt->close();

// Remove duplicate dates and classes
$available_dates = array_unique($available_dates);
rsort($available_dates); // Sort dates in descending order
$available_classes = array_unique($available_classes, SORT_REGULAR);

// Get detailed attendance records when filters are applied
if ($filter_year && $filter_section) {
    $attendance_query = "
        SELECT 
            a.id,
            a.student_id,
            a.id_number,
            a.fullname as student_name,
            a.time_in,
            a.time_out,
            a.department,
            a.location as subject,
            a.instructor_id,
            a.status,
            a.session_date as date,
            a.year_level as year,
            a.section as section
        FROM archived_attendance_logs a
        WHERE a.instructor_id = ? 
        AND a.year_level = ?
        AND a.section = ?
    ";
    
    $params = [$instructor_id, $filter_year, $filter_section];
    $types = "sss";
    
    // Add date filter if specified
    if ($filter_date) {
        $attendance_query .= " AND a.session_date = ?";
        $params[] = $filter_date;
        $types .= "s";
    }
    
    // Add subject filter if specified
    if ($filter_subject) {
        $attendance_query .= " AND a.subject_name = ?";
        $params[] = $filter_subject;
        $types .= "s";
    }
    
    // Add status filter if specified
    if ($filter_status && $filter_status !== 'all') {
        $attendance_query .= " AND a.status = ?";
        $params[] = ucfirst($filter_status);
        $types .= "s";
    }
    
    // Add month filter if specified
    if ($filter_month) {
        $month_year = explode('-', $filter_month);
        if (count($month_year) == 2) {
            $attendance_query .= " AND YEAR(a.session_date) = ? AND MONTH(a.session_date) = ?";
            $params[] = $month_year[0];
            $params[] = $month_year[1];
            $types .= "ii";
        }
    }
    
    $attendance_query .= " ORDER BY a.status DESC, a.fullname, a.time_in DESC";
    
    $stmt = $db->prepare($attendance_query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $attendance_data[] = $row;
        }
        $stmt->close();
    }
}

// If print view is requested, show simplified printable version
if ($print_view && $filter_year && $filter_section && !empty($attendance_data)) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Attendance Report - Print View</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
            .print-header h1 { margin: 0; color: #333; }
            .print-header .subtitle { color: #666; margin: 5px 0; }
            .session-info { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
            .stats-summary { display: flex; justify-content: space-around; margin: 20px 0; text-align: center; }
            .stat-item { padding: 10px; }
            .stat-number { font-size: 24px; font-weight: bold; }
            .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .table th { background-color: #333; color: white; padding: 10px; text-align: left; }
            .table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .present { color: #28a745; }
            .absent { color: #dc3545; }
            .print-footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
                .table { page-break-inside: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="print-header">
            <h1>ATTENDANCE REPORT</h1>
            <div class="subtitle">Class: <?php echo htmlspecialchars($filter_year . ' - ' . $filter_section); ?></div>
            <?php if ($filter_subject): ?>
                <div class="subtitle">Subject: <?php echo htmlspecialchars($filter_subject); ?></div>
            <?php endif; ?>
            <?php if ($filter_date): ?>
                <div class="subtitle">Date: <?php echo date('F j, Y', strtotime($filter_date)); ?></div>
            <?php endif; ?>
            <div class="subtitle">Generated on: <?php echo date('F j, Y g:i A'); ?></div>
        </div>

        <?php 
        $summary_key = $filter_year . '-' . $filter_section . '-' . $filter_date;
        $current_summary = $summary_data[$summary_key] ?? null;
        ?>
        
        <?php if ($current_summary): ?>
        <div class="session-info">
            <strong>Session Summary:</strong><br>
            Instructor: <?php echo htmlspecialchars($current_summary['instructor_name']); ?> | 
            Time: <?php echo date('g:i A', strtotime($current_summary['time_in'])); ?> - <?php echo date('g:i A', strtotime($current_summary['time_out'])); ?> | 
            Attendance Rate: <?php echo $current_summary['attendance_rate']; ?>%
        </div>
        <?php endif; ?>

        <div class="stats-summary">
            <?php
            $present_count = 0;
            $absent_count = 0;
            foreach ($attendance_data as $record) {
                if (strtolower($record['status']) == 'present') $present_count++;
                else $absent_count++;
            }
            $total_count = count($attendance_data);
            ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo $present_count; ?></div>
                <div>Present</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $absent_count; ?></div>
                <div>Absent</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_count; ?></div>
                <div>Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_count > 0 ? round(($present_count / $total_count) * 100, 1) : 0; ?>%</div>
                <div>Rate</div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Number</th>
                    <th>Student Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_data as $index => $record): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($record['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                        <td>
                            <?php if ($record['time_in']): ?>
                                <?php echo date('M j, Y g:i A', strtotime($record['time_in'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($record['time_out']): ?>
                                <?php echo date('M j, Y g:i A', strtotime($record['time_out'])); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="<?php echo strtolower($record['status']); ?>">
                            <strong><?php echo htmlspecialchars($record['status']); ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="print-footer">
            Generated by Class Checker System | <?php echo date('Y'); ?>
        </div>

        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Attendance - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #87abe0ff;
            --secondary-color: #6c8bc7;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        body {
            font-family: 'Heebo', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            padding-top: 56px;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--secondary-color);
        }
        .navbar {
            background-color: var(--primary-color);
            padding: 10px 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 56px;
        }
        .card-dashboard {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
        }
        .bg-primary-custom {
            background-color: var(--primary-color);
            color: white;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: none;
            color: white;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .date-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }
        .session-info {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .attendance-table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        .quick-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-bottom: 20px;
        }
        .quick-stat-item {
            padding: 15px;
        }
        .quick-stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .quick-stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .filter-badge {
            background-color: var(--info-color);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
        }
        .print-btn {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            border: none;
            color: white;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .attendance-timeline {
            position: relative;
            padding-left: 30px;
        }
        .attendance-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--primary-color);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        .auto-load-banner {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-in;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .navbar, .main-content {
                margin-left: 0;
                width: 100%;
            }
            .quick-stats {
                flex-direction: column;
            }
        }
        @media print {
            .sidebar, .navbar, .filter-section, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                margin-top: 0;
                padding: 0;
            }
            .card-dashboard {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay (for auto-submit) -->
    <?php if ($from_dashboard): ?>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
            <h5>Loading Attendance Data...</h5>
            <p>Please wait while we fetch the records for <?php echo htmlspecialchars($filter_year . ' - ' . $filter_section); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-4 text-white">
            <a href="#" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="fs-5 d-none d-sm-inline">Instructor Panel</span>
            </a>
            <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
                <li class="nav-item w-100">
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item w-100">
                    <a href="attendance.php" class="nav-link active"><i class="fas fa-clipboard-check me-2"></i> Attendance</a>
                </li>
                <li class="nav-item w-100">
                    <a href="schedule.php" class="nav-link"><i class="fas fa-calendar-alt me-2"></i> Schedule</a>
                </li>
                <li class="nav-item w-100">
                    <a href="profile.php" class="nav-link"><i class="fas fa-user me-2"></i> Profile</a>
                </li>
                <li class="nav-item w-100">
                    <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar -->
    <div class="collapse d-md-none" id="sidebarMenu">
        <div class="bg-primary-custom p-3">
            <ul class="nav flex-column">
                <li class="nav-item"><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="attendance.php" class="nav-link text-white active"><i class="fas fa-clipboard-check me-2"></i> Attendance</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white"><i class="fas fa-calendar-alt me-2"></i> Schedule</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link text-white"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Auto-load Banner -->
        <?php if ($from_dashboard && !empty($attendance_data)): ?>
        <div class="auto-load-banner">
            <div class="d-flex align-items-center">
                <i class="fas fa-bolt fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Auto-loaded Attendance Data</h5>
                    <p class="mb-0">
                        Showing attendance for <?php echo htmlspecialchars($filter_year . ' - ' . $filter_section); ?>
                        <?php if ($filter_subject): ?> in <?php echo htmlspecialchars($filter_subject); ?><?php endif; ?>
                        on <?php echo $filter_date ? date('F j, Y', strtotime($filter_date)) : 'all dates'; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="quick-stats">
                    <div class="quick-stat-item">
                        <div class="quick-stat-number"><?php echo count($available_classes); ?></div>
                        <div class="quick-stat-label">Total Classes</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number"><?php echo count($available_dates); ?></div>
                        <div class="quick-stat-label">Sessions</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-number">
                            <?php 
                            $total_sessions = count($available_dates);
                            $total_months = count($monthly_stats);
                            echo $total_months > 0 ? round($total_sessions / $total_months, 1) : 0;
                            ?>
                        </div>
                        <div class="quick-stat-label">Avg Sessions/Month</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Filter Section -->
        <div class="filter-section no-print">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filter Attendance Records</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="toggleAdvancedFilters">
                        <i class="fas fa-sliders-h me-1"></i>Advanced
                    </button>
                </div>
            </div>
            
            <form method="GET" action="attendance.php" id="filterForm">
                <!-- Hidden field to track form submission -->
                <input type="hidden" name="form_submitted" value="1">
                
                <div class="row g-3">
                    <!-- Basic Filters - Matching Dashboard Structure -->
                    <div class="col-md-3">
                        <label for="year" class="form-label"><i class="fas fa-graduation-cap me-1"></i>Year Level</label>
                        <select class="form-select" id="year" name="year">
                            <option value="">Select Year Level</option>
                            <?php 
                            $unique_years = array_unique(array_column($available_classes, 'year'));
                            sort($unique_years);
                            foreach ($unique_years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" 
                                    <?php echo ($filter_year == $year) ? 'selected' : ''; ?>>
                                    Year <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="section" class="form-label"><i class="fas fa-users me-1"></i>Section</label>
                        <select class="form-select" id="section" name="section">
                            <option value="">Select Section</option>
                            <?php 
                            $filtered_sections = [];
                            foreach ($available_classes as $class) {
                                if (!$filter_year || $class['year'] == $filter_year) {
                                    $filtered_sections[] = $class['section'];
                                }
                            }
                            $unique_sections = array_unique($filtered_sections);
                            sort($unique_sections);
                            foreach ($unique_sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section); ?>" 
                                    <?php echo ($filter_section == $section) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($section); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="subject" class="form-label"><i class="fas fa-book me-1"></i>Subject</label>
                        <select class="form-select" id="subject" name="subject">
                            <option value="">Select Subject</option>
                            <?php 
                            $filtered_subjects = [];
                            foreach ($available_classes as $class) {
                                if ((!$filter_year || $class['year'] == $filter_year) && 
                                    (!$filter_section || $class['section'] == $filter_section) && 
                                    !empty($class['subject'])) {
                                    $filtered_subjects[] = $class['subject'];
                                }
                            }
                            $unique_subjects = array_unique($filtered_subjects);
                            sort($unique_subjects);
                            foreach ($unique_subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>" 
                                    <?php echo ($filter_subject == $subject) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date" class="form-label"><i class="fas fa-calendar-day me-1"></i>Date</label>
                        <select class="form-select" id="date" name="date">
                            <option value="">All Dates</option>
                            <?php foreach ($available_dates as $date): ?>
                                <option value="<?php echo $date; ?>" <?php echo ($filter_date == $date) ? 'selected' : ''; ?>>
                                    <?php echo date('M j, Y (D)', strtotime($date)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="attendance.php" class="btn btn-secondary">
                                    <i class="fas fa-refresh me-2"></i>Reset
                                </a>
                                <button type="button" class="btn btn-outline-info" id="quickToday">
                                    <i class="fas fa-calendar-day me-2"></i>Today's Records
                                </button>
                            </div>
                            
                            <?php if ($filter_year && $filter_section && !empty($attendance_data)): ?>
                            <div class="d-flex gap-2">
                                <a href="attendance.php?<?php 
                                    echo http_build_query([
                                        'year' => $filter_year,
                                        'section' => $filter_section,
                                        'date' => $filter_date,
                                        'subject' => $filter_subject,
                                        'status' => $filter_status,
                                        'month' => $filter_month,
                                        'print' => '1'
                                    ]); 
                                ?>" target="_blank" class="btn print-btn">
                                    <i class="fas fa-print me-2"></i>Print Report
                                </a>
                                <a href="export_attendance.php?year=<?php echo $filter_year; ?>&section=<?php echo $filter_section; ?>&date=<?php echo $filter_date; ?>&subject=<?php echo $filter_subject; ?>&status=<?php echo $filter_status; ?>&month=<?php echo $filter_month; ?>" 
                                class="btn export-btn">
                                    <i class="fas fa-download me-2"></i>Export to Excel
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Advanced Filters (Initially Hidden) -->
                    <div class="col-12 advanced-filters" style="display: none;">
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-cogs me-2"></i>Advanced Filters</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label"><i class="fas fa-user-check me-1"></i>Attendance Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo ($filter_status == 'all' || !$filter_status) ? 'selected' : ''; ?>>All Students</option>
                                    <option value="present" <?php echo ($filter_status == 'present') ? 'selected' : ''; ?>>Present Only</option>
                                    <option value="absent" <?php echo ($filter_status == 'absent') ? 'selected' : ''; ?>>Absent Only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="month" class="form-label"><i class="fas fa-calendar-alt me-1"></i>Month</label>
                                <select class="form-select" id="month" name="month">
                                    <option value="">All Months</option>
                                    <?php 
                                    $months = [];
                                    foreach ($available_dates as $date) {
                                        $month_key = date('Y-m', strtotime($date));
                                        if (!in_array($month_key, $months)) {
                                            $months[] = $month_key;
                                            echo '<option value="' . $month_key . '" ' . ($filter_month == $month_key ? 'selected' : '') . '>';
                                            echo date('F Y', strtotime($date));
                                            echo '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-info-circle me-1"></i>Quick Info</label>
                                <div class="form-control bg-light">
                                    <small class="text-muted">
                                        <?php if ($filter_year && $filter_section): ?>
                                            <i class="fas fa-database me-1"></i>
                                            <?php echo count($attendance_data); ?> records found
                                        <?php else: ?>
                                            <i class="fas fa-filter me-1"></i>
                                            Select filters to view data
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filters Display -->
                    <?php if ($filter_year || $filter_section || $filter_date || $filter_status || $filter_subject || $filter_month): ?>
                    <div class="col-12">
                        <div class="d-flex align-items-center flex-wrap gap-2 p-3 bg-light rounded">
                            <small class="text-muted me-2"><i class="fas fa-filter me-1"></i>Active Filters:</small>
                            <?php if ($filter_year): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    Year: <?php echo htmlspecialchars($filter_year); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_section): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-users me-1"></i>
                                    Section: <?php echo htmlspecialchars($filter_section); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_subject): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-book me-1"></i>
                                    Subject: <?php echo htmlspecialchars($filter_subject); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_date): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    Date: <?php echo date('M j, Y', strtotime($filter_date)); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_status && $filter_status !== 'all'): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-user-check me-1"></i>
                                    Status: <?php echo ucfirst($filter_status); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_month): ?>
                                <span class="filter-badge">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Month: <?php echo date('F Y', strtotime($filter_month . '-01')); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($attendance_data)): ?>
                                <span class="filter-badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <?php echo count($attendance_data); ?> Records
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Main Content Card -->
        <div class="card card-dashboard">
            <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>
                    <?php if ($filter_year && $filter_section): ?>
                        Attendance Records - <?php echo htmlspecialchars($filter_year . " - " . $filter_section); ?>
                        <?php if ($filter_subject): ?> (<?php echo htmlspecialchars($filter_subject); ?>)<?php endif; ?>
                        <?php if ($filter_date): ?>
                            <span class="date-badge ms-2"><?php echo date('M j, Y', strtotime($filter_date)); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        Attendance Records - Select filters to view data
                    <?php endif; ?>
                </h5>
                <?php if (!empty($attendance_data)): ?>
                <span class="badge bg-light text-dark">
                    <?php echo count($attendance_data); ?> records
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($filter_year && $filter_section): ?>
                    <!-- Session Summary -->
                    <?php 
                    $summary_key = $filter_year . '-' . $filter_section . '-' . $filter_date;
                    $current_summary = $summary_data[$summary_key] ?? null;
                    ?>
                    
                    <?php if ($current_summary): ?>
                    <div class="session-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Session Summary</h6>
                                <div class="attendance-timeline mt-3">
                                    <div class="timeline-item">
                                        <strong>Instructor:</strong> <?php echo htmlspecialchars($current_summary['instructor_name']); ?>
                                    </div>
                                    <div class="timeline-item">
                                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($current_summary['time_in'])); ?> - <?php echo date('g:i A', strtotime($current_summary['time_out'])); ?>
                                    </div>
                                    <div class="timeline-item">
                                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($current_summary['date'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-chart-pie me-2"></i>Class Statistics</h6>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="text-center p-2">
                                            <div class="fw-bold text-success"><?php echo $current_summary['present_count']; ?></div>
                                            <small>Present</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2">
                                            <div class="fw-bold text-danger"><?php echo $current_summary['absent_count']; ?></div>
                                            <small>Absent</small>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $current_summary['attendance_rate']; ?>%"></div>
                                            <div class="progress-bar bg-danger" style="width: <?php echo 100 - $current_summary['attendance_rate']; ?>%"></div>
                                        </div>
                                        <div class="text-center mt-1">
                                            <small class="text-muted">Attendance Rate: <?php echo $current_summary['attendance_rate']; ?>%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($attendance_data)): ?>
                        <!-- Statistics Cards -->
                        <?php
                        $present_count = 0;
                        $absent_count = 0;
                        $early_count = 0;
                        $late_count = 0;
                        
                        foreach ($attendance_data as $record) {
                            if (strtolower($record['status']) == 'present') {
                                $present_count++;
                                // Simple logic for early/late (you can enhance this)
                                if ($record['time_in'] && $current_summary) {
                                    $scan_time = strtotime($record['time_in']);
                                    $session_time = strtotime($current_summary['time_in']);
                                    if ($scan_time <= $session_time + 900) { // 15 minutes grace period
                                        $early_count++;
                                    } else {
                                        $late_count++;
                                    }
                                }
                            } else {
                                $absent_count++;
                            }
                        }
                        $total_count = count($attendance_data);
                        $attendance_rate = $total_count > 0 ? round(($present_count / $total_count) * 100, 1) : 0;
                        ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card bg-success">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stats-number"><?php echo $present_count; ?></div>
                                    <div>Present Students</div>
                                    <?php if ($present_count > 0): ?>
                                    <div class="stats-detail small mt-2">
                                        <?php echo $early_count; ?> on time, <?php echo $late_count; ?> late
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-danger">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <div class="stats-number"><?php echo $absent_count; ?></div>
                                    <div>Absent Students</div>
                                    <div class="stats-detail small mt-2">
                                        <?php echo round(($absent_count / $total_count) * 100, 1); ?>% of class
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-info">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-number"><?php echo $total_count; ?></div>
                                    <div>Total Records</div>
                                    <div class="stats-detail small mt-2">
                                        Filtered results
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-warning">
                                    <div class="stats-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stats-number"><?php echo $attendance_rate; ?>%</div>
                                    <div>Attendance Rate</div>
                                    <div class="stats-detail small mt-2">
                                        Overall performance
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover attendance-table">
                                <thead>
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Student Name</th>
                                        <th>Section</th>
                                        <th>Year</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Status</th>
                                        <th>Subject</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_data as $record): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($record['id_number']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($record['section']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['year']); ?></td>
                                            <td>
                                                <?php if ($record['time_in']): ?>
                                                    <span class="text-success">
                                                        <?php echo date('M j, Y g:i A', strtotime($record['time_in'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['time_out']): ?>
                                                    <span class="text-info">
                                                        <?php echo date('M j, Y g:i A', strtotime($record['time_out'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo (strtolower($record['status']) == 'present') ? 'bg-success' : 'bg-danger'; ?>">
                                                    <i class="fas <?php echo (strtolower($record['status']) == 'present') ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($record['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($record['subject']); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Records Found</h4>
                            <p class="text-muted">No attendance records match your current filters.</p>
                            <div class="mt-3">
                                <a href="attendance.php" class="btn btn-primary me-2">View All Records</a>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">Clear Filters</button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-check fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Welcome to Attendance Records</h4>
                        <p class="text-muted">Select year level and section above to view detailed attendance records.</p>
                        
                        <?php if (empty($available_classes)): ?>
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No attendance records found yet. 
                                <br>Attendance data will appear here after you save sessions from the scanner page.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-4">
                                <i class="fas fa-check-circle me-2"></i>
                                You have <?php echo count($available_classes); ?> class session(s) in your records.
                                <br><small class="text-muted">Select filters above to get started.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    // Toggle advanced filters
    document.getElementById('toggleAdvancedFilters').addEventListener('click', function() {
        const advancedFilters = document.querySelector('.advanced-filters');
        const icon = this.querySelector('i');
        
        if (advancedFilters.style.display === 'none') {
            advancedFilters.style.display = 'block';
            icon.className = 'fas fa-sliders-h me-1';
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
        } else {
            advancedFilters.style.display = 'none';
            icon.className = 'fas fa-sliders-h me-1';
            this.classList.remove('btn-primary');
            this.classList.add('btn-outline-primary');
        }
    });

    // Auto-submit form when coming from dashboard
    document.addEventListener('DOMContentLoaded', function() {
        const yearSelect = document.getElementById('year');
        const sectionSelect = document.getElementById('section');
        const subjectSelect = document.getElementById('subject');
        const dateSelect = document.getElementById('date');
        
        <?php if ($from_dashboard): ?>
        // If coming from dashboard with parameters, auto-submit the form
        console.log('Auto-submitting form with parameters from dashboard');
        setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 100);
        <?php endif; ?>

        // Update sections when year changes
        yearSelect.addEventListener('change', function() {
            const selectedYear = this.value;
            sectionSelect.disabled = !selectedYear;
            subjectSelect.disabled = !selectedYear;
            
            // Update sections and subjects based on selected year
            updateFilters();
        });

        // Update subjects when section changes
        sectionSelect.addEventListener('change', function() {
            updateFilters();
        });

        // Auto-submit form when basic filters change (for quick filtering)
        yearSelect.addEventListener('change', function() {
            if (this.value && document.getElementById('section').value) {
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            }
        });

        sectionSelect.addEventListener('change', function() {
            if (this.value && document.getElementById('year').value) {
                setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            }
        });

        // Quick Today's Records button
        document.getElementById('quickToday').addEventListener('click', function() {
            const today = new Date().toISOString().split('T')[0];
            dateSelect.value = today;
            document.getElementById('filterForm').submit();
        });

        // Initialize filters based on current selections
        function updateFilters() {
            const selectedYear = yearSelect.value;
            const selectedSection = sectionSelect.value;
            
            // You can add AJAX here to dynamically update sections and subjects
            // For now, we'll rely on the pre-populated options
        }

        // Initialize date picker if needed
        if (document.getElementById('datePicker')) {
            flatpickr("#datePicker", {
                dateFormat: "Y-m-d",
                maxDate: "today"
            });
        }

        // Auto-scroll to results if data is loaded
        <?php if (!empty($attendance_data)): ?>
            setTimeout(function() {
                const attendanceTable = document.querySelector('.attendance-table');
                if (attendanceTable) {
                    attendanceTable.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 500);
        <?php endif; ?>

        // Hide loading overlay after page load
        <?php if ($from_dashboard): ?>
            setTimeout(function() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
            }, 1000);
        <?php endif; ?>
    });

    function viewStudentDetails(studentId) {
        // You can implement a modal or redirect to student details page
        alert('Viewing details for student: ' + studentId);
        // Example: window.location.href = 'student_details.php?id=' + studentId;
    }

    function clearFilters() {
        document.getElementById('filterForm').reset();
        window.location.href = 'attendance.php';
    }

    // Print functionality
    function printReport() {
        window.print();
    }

    // Add some interactive features
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to table rows
        const tableRows = document.querySelectorAll('.attendance-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.2s ease';
            });
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // Auto-expand advanced filters if advanced filters are being used
        <?php if ($filter_subject || $filter_month || $filter_status !== 'all'): ?>
            document.getElementById('toggleAdvancedFilters').click();
        <?php endif; ?>
    });
</script>
</body>
</html>