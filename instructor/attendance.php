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

// Get instructor's fullname
 $instructor_name = $_SESSION['fullname'];

// Get current week dates for navigation
 $current_week_start = date('Y-m-d', strtotime('monday this week'));
 $current_week_end = date('Y-m-d', strtotime('sunday this week'));

// Handle week navigation
 $week_offset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
 $display_week_start = date('Y-m-d', strtotime("$week_offset week", strtotime($current_week_start)));
 $display_week_end = date('Y-m-d', strtotime("$week_offset week", strtotime($current_week_end)));

// Get instructor's schedule for the selected week
 $schedule_query = "
    SELECT 
        id,
        subject,
        room_name,
        section,
        start_time,
        end_time,
        day,
        year_level
    FROM room_schedules
    WHERE instructor = ?
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
             start_time ASC
";

 $schedule_data = [];
 $schedule_stmt = $db->prepare($schedule_query);
if ($schedule_stmt) {
    $schedule_stmt->bind_param("s", $instructor_name);
    $schedule_stmt->execute();
    $result = $schedule_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $schedule_data[] = $row;
    }
    $schedule_stmt->close();
}

// Get today's day for highlighting
 $today_day = date("l");

// Group schedule by day
 $schedule_by_day = [];
foreach ($schedule_data as $class) {
    $schedule_by_day[$class['day']][] = $class;
}

// Days of the week
 $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Schedule - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #54555cff;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --sidebar-bg: #79abf7ff;
            --sidebar-hover: #b2d7fdff;
            --sidebar-active: #4361ee;
        }

        body {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
            font-family: 'Inter', sans-serif;
            color: var(--dark-text);
        }

        .content {
            background: transparent;
        }

        .bg-light {
            background-color: var(--light-bg) !important;
            border-radius: var(--border-radius);
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            background: white;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px 15px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .stats-card.text-info::before { background: linear-gradient(135deg, #36b9cc, #2e59d9); }
        .stats-card.text-primary::before { background: linear-gradient(135deg, #4e73df, #2e59d9); }
        .stats-card.text-danger::before { background: linear-gradient(135deg, #e74a3b, #be2617); }
        .stats-card.text-success::before { background: linear-gradient(135deg, #1cc88a, #17a673); }
        .stats-card.text-warning::before { background: linear-gradient(135deg, #f6c23e, #f4b619); }
        .stats-card.text-secondary::before { background: linear-gradient(135deg, #858796, #6c757d); }
        .stats-card.text-dark::before { background: linear-gradient(135deg, #5a5c69, #373840); }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .stats-content h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-text);
        }

        .stats-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .stats-detail {
            font-size: 0.75rem;
            color: #495057;
            margin-top: 5px;
        }

        /* Modern Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            z-index: 1000;
            padding-top: 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
        }

        .sidebar-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .sidebar-subtitle {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 5px 0 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .sidebar .nav {
            flex-direction: column;
            padding: 0 15px;
        }

        .sidebar .nav-item {
            margin-bottom: 5px;
            position: relative;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 15px;
            margin: 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .sidebar .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--sidebar-active), #2e59d9);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: white;
            border-radius: 0 4px 4px 0;
        }

        .sidebar-footer {
            padding: 15px;
        }

        .sidebar-profile {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }

        .sidebar-profile:hover {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--icon-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 12px;
        }

        .sidebar-profile-info {
            flex: 1;
        }

        .sidebar-profile-name {
            color: white;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-profile-role {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 0;
        }

        .sidebar-profile-toggle {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .sidebar-profile-toggle:hover {
            color: white;
        }

        /* Mobile sidebar toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--sidebar-bg);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px;
            box-shadow: var(--box-shadow);
            cursor: pointer;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .navbar {
            background: linear-gradient(180deg,  #1a252f 100%,var(--icon-color) 0%,);
            padding: 10px 20px;
            margin-left: 260px;
            width: calc(100% - 260px);
            position: fixed;
            top: 0;
            z-index: 998;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
        }

        .welcome-header {
            background: linear-gradient(135deg, var(--icon-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            z-index: 1;
        }
        
        .welcome-content {
            position: relative;
            z-index: 2;
        }
        
        .instructor-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .info-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-right: 10px;
            margin-bottom: 5px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        .list-group-item {
            border: none;
            border-bottom: 1px solid #e9ecef;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        .attendance-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 10px;
        }
        
        .attendance-progress {
            height: 6px;
            margin-top: 5px;
        }
        
        .class-status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .status-in-progress {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }
        
        .status-upcoming {
            background-color: #17a2b8;
        }
        
        .status-completed {
            background-color: #6c757d;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .table th {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 12px;
        }

        .table td {
            padding: 12px;
            border-color: rgba(0,0,0,0.05);
            vertical-align: middle;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .badge {
            font-size: 0.85em;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .btn-outline-primary {
            border-color: var(--icon-color);
            color: var(--icon-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--icon-color);
            border-color: var(--icon-color);
            color: white;
        }

        .time-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .attendance-stats {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .view-attendance-btn {
            transition: all 0.3s ease;
        }
        
        .view-attendance-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        /* Schedule specific styles */
        .schedule-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .day-header {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-header.today {
            background: linear-gradient(135deg, var(--success-color), #17a673);
        }

        .class-item {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--icon-color);
            transition: var(--transition);
        }

        .class-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .class-time {
            font-weight: 600;
            color: var(--icon-color);
            margin-bottom: 5px;
        }

        .class-subject {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .class-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .class-room {
            background: rgba(92, 149, 233, 0.1);
            color: var(--icon-color);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .class-section {
            background: rgba(92, 149, 233, 0.1);
            color: var(--icon-color);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .week-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .week-display {
            background: white;
            border-radius: var(--border-radius);
            padding: 10px 20px;
            box-shadow: var(--box-shadow);
            font-weight: 600;
            color: var(--dark-text);
        }

        .empty-day {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .navbar, .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .welcome-header {
                padding: 20px;
            }
            
            .instructor-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Modern Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../uploads/it.png" alt="Institution Logo" class="header-logo me-3" style="width: 80px; height: 80px; border-radius: 10px; border: 3px solid rgba(255,255,255,0.3);">
            </div>
            <h5 class="sidebar-title">Instructor Portal</h5>
            <p class="sidebar-subtitle">RFID Attendance System</p>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedule.php" class="nav-link active">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="sidebar-footer">
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['fullname']); ?>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid pt-4 px-4">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <!-- Enhanced Welcome Header -->
                    <div class="welcome-header">
                        <div class="welcome-content">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        <div>
                                            <h2 class="mb-1">Class Schedule</h2>
                                            <p class="mb-0">View your weekly teaching schedule</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="info-badge">
                                            <i class="fas fa-id-card me-1"></i>
                                            ID: <?php echo htmlspecialchars($_SESSION['id_number'] ?? 'N/A'); ?>
                                        </span>
                                        <span class="info-badge">
                                            <i class="fas fa-building me-1"></i>
                                            Department: <?php echo htmlspecialchars($_SESSION['department']); ?>
                                        </span>
                                        <span class="info-badge">
                                            <i class="fas fa-chalkboard-teacher me-1"></i>
                                            <?php echo count($schedule_data); ?> Classes This Week
                                        </span>
                                    </div>
                                    <p class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today is <?php echo date('l, F j, Y'); ?></p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="card bg-light bg-opacity-50 border-0">
                                        <div class="card-body text-dark">
                                            <h6 class="card-title"><i class="fas fa-clock me-2"></i>Current Time</h6>
                                            <h4 id="current-time" class="mb-0"><?php echo date('g:i A'); ?></h4>
                                            <small id="current-date"><?php echo date('M j, Y'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Week Navigation -->
                    <div class="week-navigation">
                        <a href="schedule.php?week=<?php echo $week_offset - 1; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-chevron-left me-2"></i>Previous Week
                        </a>
                        <div class="week-display">
                            <?php echo date('F j', strtotime($display_week_start)); ?> - <?php echo date('F j, Y', strtotime($display_week_end)); ?>
                        </div>
                        <a href="schedule.php?week=<?php echo $week_offset + 1; ?>" class="btn btn-outline-primary">
                            Next Week<i class="fas fa-chevron-right ms-2"></i>
                        </a>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row g-4 mb-4">
                        <!-- Total Classes -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-info">
                                <div class="stats-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stats-content">
                                    <h3><?php echo count($schedule_data); ?></h3>
                                    <p>Total Classes</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">This week</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Classes -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-success">
                                <div class="stats-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $today_classes = isset($schedule_by_day[$today_day]) ? count($schedule_by_day[$today_day]) : 0;
                                        echo $today_classes;
                                        ?>
                                    </h3>
                                    <p>Today's Classes</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">Scheduled for today</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teaching Hours -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-warning">
                                <div class="stats-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $total_hours = 0;
                                        foreach ($schedule_data as $class) {
                                            $start_time = new DateTime($class['start_time']);
                                            $end_time = new DateTime($class['end_time']);
                                            $interval = $start_time->diff($end_time);
                                            $total_hours += $interval->h + ($interval->i / 60);
                                        }
                                        echo round($total_hours, 1);
                                        ?>
                                    </h3>
                                    <p>Teaching Hours</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">This week</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Different Rooms -->
                        <div class="col-sm-6 col-md-4 col-xl-3">
                            <div class="stats-card text-primary">
                                <div class="stats-icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="stats-content">
                                    <h3>
                                        <?php 
                                        $unique_rooms = array_unique(array_column($schedule_data, 'room_name'));
                                        echo count($unique_rooms);
                                        ?>
                                    </h3>
                                    <p>Different Rooms</p>
                                    <div class="stats-detail">
                                        <small class="text-muted">This week</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Schedule -->
                    <div class="row">
                        <?php foreach ($days_of_week as $day): ?>
                            <div class="col-lg-12 mb-4">
                                <div class="schedule-card">
                                    <div class="day-header <?php echo ($day === $today_day) ? 'today' : ''; ?>">
                                        <span><?php echo $day; ?></span>
                                        <?php if ($day === $today_day): ?>
                                            <span class="badge bg-light text-dark">Today</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (isset($schedule_by_day[$day]) && !empty($schedule_by_day[$day])): ?>
                                        <?php foreach ($schedule_by_day[$day] as $class): ?>
                                            <div class="class-item">
                                                <div class="class-time">
                                                    <i class="fas fa-clock me-2"></i>
                                                    <?php echo date("g:i A", strtotime($class['start_time'])); ?> - <?php echo date("g:i A", strtotime($class['end_time'])); ?>
                                                </div>
                                                <div class="class-subject">
                                                    <i class="fas fa-book me-2"></i>
                                                    <?php echo htmlspecialchars($class['subject']); ?>
                                                </div>
                                                <div class="class-details">
                                                    <div>
                                                        <span class="class-room">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            <?php echo htmlspecialchars($class['room_name']); ?>
                                                        </span>
                                                        <span class="class-section ms-2">
                                                            <i class="fas fa-users me-1"></i>
                                                            <?php echo htmlspecialchars($class['section']); ?>
                                                        </span>
                                                        <span class="class-section ms-2">
                                                            <i class="fas fa-graduation-cap me-1"></i>
                                                            Year <?php echo htmlspecialchars($class['year_level']); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-clipboard-check me-1"></i>View Attendance
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="empty-day">
                                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                            <p>No classes scheduled for <?php echo $day; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time every second
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                second: '2-digit',
                hour12: true 
            });
            const dateString = now.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
            
            document.getElementById('current-time').textContent = timeString;
            document.getElementById('current-date').textContent = dateString;
        }

        // Update time immediately and every second
        updateCurrentTime();
        setInterval(updateCurrentTime, 1000);

        // Sidebar toggle functionality for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        });

        // Add interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to class items
            const classItems = document.querySelectorAll('.class-item');
            classItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.transition = 'transform 0.2s ease';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Highlight current time if class is in progress
            const now = new Date();
            const currentTime = now.getHours() * 60 + now.getMinutes();
            
            <?php if ($today_day && isset($schedule_by_day[$today_day])): ?>
                const todayClasses = <?php echo json_encode($schedule_by_day[$today_day] ?? []); ?>;
                
                todayClasses.forEach((classInfo, index) => {
                    const startTime = classInfo.start_time.split(':');
                    const endTime = classInfo.end_time.split(':');
                    const startMinutes = parseInt(startTime[0]) * 60 + parseInt(startTime[1]);
                    const endMinutes = parseInt(endTime[0]) * 60 + parseInt(endTime[1]);
                    
                    if (currentTime >= startMinutes && currentTime <= endMinutes) {
                        const classElement = document.querySelectorAll('.class-item')[index];
                        if (classElement) {
                            classElement.style.borderLeftColor = '#28a745';
                            classElement.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
                            
                            // Add "In Progress" indicator
                            const header = classElement.querySelector('.class-time');
                            if (header && !header.querySelector('.in-progress')) {
                                const indicator = document.createElement('span');
                                indicator.className = 'badge bg-success ms-2 in-progress';
                                indicator.innerHTML = '<i class="fas fa-play me-1"></i>In Progress';
                                header.appendChild(indicator);
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php mysqli_close($db); ?>