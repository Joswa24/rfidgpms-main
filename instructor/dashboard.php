<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ✅ Session & Role Check - MUST BE AT THE VERY TOP
session_start();

// Debug session data
error_log("=== DASHBOARD ACCESS ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// Check if essential session variables exist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' ||
    !isset($_SESSION['instructor_id'])) {
    
    error_log("SESSION VALIDATION FAILED - Redirecting to index");
    header("Location: index.php");
    exit();
}

error_log("SESSION VALIDATION PASSED - Loading dashboard");

// ✅ Timeout (15 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// ✅ Hijack Prevention
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
} else {
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: index.php?hijack=1");
        exit();
    }
}

// Now include other files
include '../connection.php';

// Check database connection
if (!$db || $db->connect_error) {
    die("Database connection failed: " . ($db->connect_error ?? 'Unknown error'));
}

// ✅ Fetch Updated Instructor Information
$instructor_info = null;
$instructor_id = $_SESSION['instructor_id'];

// FIXED QUERY: Removed email and contact_number columns
$stmt = $db->prepare("
    SELECT i.fullname, i.id_number, d.department_name
    FROM instructor i 
    LEFT JOIN department d ON i.department_id = d.department_id 
    WHERE i.id = ?
");

if ($stmt) {
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $instructor_info = $result->fetch_assoc();
        
        // Update session variables with fresh data from database
        $_SESSION['fullname'] = $instructor_info['fullname'];
        $_SESSION['department'] = $instructor_info['department_name'] ?? 'Not Assigned';
        $_SESSION['id_number'] = $instructor_info['id_number'] ?? '';
        
    } else {
        // Instructor not found in database - logout user
        session_unset();
        session_destroy();
        header("Location: index.php?error=instructor_not_found");
        exit();
    }
    $stmt->close();
} else {
    die("Database error: " . $db->error);
}

// ✅ Fetch Instructor Schedules (UPDATED TO USE INSTRUCTOR NAME INSTEAD OF ID)
$today_classes = [];
$upcoming_classes = [];

// Get instructor's fullname
$instructor_name = $_SESSION['fullname'];

// Today's classes (UPDATED QUERY)
$today_day = date("l");
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor = ? AND day = ?
    ORDER BY start_time ASC
");

if ($stmt) {
    $stmt->bind_param("ss", $instructor_name, $today_day);
    $stmt->execute();
    $today_classes = $stmt->get_result();
    $stmt->close();
}

// Upcoming classes (week overview) - UPDATED QUERY
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor = ?
    ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
             start_time ASC
");

if ($stmt) {
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $upcoming_classes = $stmt->get_result();
    $stmt->close();
}

// ✅ Fetch today's attendance summary for dashboard display
$today_attendance_summary = [];
$today_date = date('Y-m-d');

$attendance_summary_query = "
    SELECT 
        year_level as year,
        section,
        subject_name as subject,
        present_count,
        absent_count,
        total_students,
        attendance_rate
    FROM instructor_attendance_summary 
    WHERE instructor_id = ? AND session_date = ?
    ORDER BY year_level, section
";

$attendance_stmt = $db->prepare($attendance_summary_query);
if ($attendance_stmt) {
    $attendance_stmt->bind_param("ss", $instructor_id, $today_date);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    
    while ($attendance_row = $attendance_result->fetch_assoc()) {
        $today_attendance_summary[] = $attendance_row;
    }
    $attendance_stmt->close();
}

// Check if header.php exists before including
if (!file_exists('header.php')) {
    die("Header file not found. Please check file structure.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #87abe0ff;
            --secondary-color: #6c8bc7;
        }
        
        body {
            font-family: 'Heebo', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
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
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--secondary-color);
            transform: translateX(5px);
        }
        
        .navbar {
            background-color: var(--primary-color);
            padding: 10px 20px;
            margin-left: 250px;
            width: calc(100% - 250px);
            position: fixed;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 56px;
            min-height: calc(100vh - 56px);
        }
        
        .card-dashboard {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        }
        
        .bg-primary-custom {
            background-color: var(--primary-color);
            color: white;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 15px;
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
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
                margin-bottom: 20px;
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
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-4 text-white">
            <a href="#" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <span class="fs-5 d-none d-sm-inline">Instructor Panel</span>
            </a>
            <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
                <li class="nav-item w-100">
                    <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item w-100">
                    <a href="attendance.php" class="nav-link"><i class="fas fa-clipboard-check me-2"></i> Attendance</a>
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
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Instructor'); ?>
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link text-white active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="attendance.php" class="nav-link text-white"><i class="fas fa-clipboard-check me-2"></i> Attendance</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white"><i class="fas fa-calendar-alt me-2"></i> Schedule</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link text-white"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Enhanced Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-content">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-3">
                            <img src="../uploads/it.png" alt="Institution Logo" class="header-logo me-3" style="width: 80px; height: 80px; border-radius: 10px; border: 3px solid rgba(255,255,255,0.3);">
                            <div>
                                <h2 class="mb-1"><?php echo htmlspecialchars($_SESSION['department']); ?> Instructor</h2>
                                <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?></p>
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
                            <?php if (!empty($today_attendance_summary)): ?>
                            <span class="info-badge">
                                <i class="fas fa-clipboard-check me-1"></i>
                                <?php echo count($today_attendance_summary); ?> Classes Tracked Today
                            </span>
                            <?php endif; ?>
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

        <div class="row">
            <!-- Today's Classes -->
            <div class="col-md-6">
                <div class="card card-dashboard h-100">
                    <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Classes</h5>
                        <span class="badge bg-light text-dark"><?php echo $today_classes ? $today_classes->num_rows : 0; ?> classes</span>
                    </div>
                    <div class="card-body">
                        <?php if ($today_classes && $today_classes->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($class = $today_classes->fetch_assoc()): 
                                    $start_time = date("g:i A", strtotime($class['start_time']));
                                    $end_time = date("g:i A", strtotime($class['end_time']));
                                    $current_time = time();
                                    $class_start = strtotime($class['start_time']);
                                    $class_end = strtotime($class['end_time']);
                                    
                                    // Determine class status
                                    if ($current_time >= $class_start && $current_time <= $class_end) {
                                        $status = 'In Progress';
                                        $status_class = 'status-in-progress';
                                        $badge_class = 'bg-success';
                                    } elseif ($current_time < $class_start) {
                                        $status = 'Upcoming';
                                        $status_class = 'status-upcoming';
                                        $badge_class = 'bg-info';
                                    } else {
                                        $status = 'Completed';
                                        $status_class = 'status-completed';
                                        $badge_class = 'bg-secondary';
                                    }
                                    
                                    // Check if attendance data exists for this class today
                                    $attendance_data = null;
                                    foreach ($today_attendance_summary as $attendance) {
                                        if ($attendance['year'] == $class['year_level'] && 
                                            $attendance['section'] == $class['section'] &&
                                            $attendance['subject'] == $class['subject']) {
                                            $attendance_data = $attendance;
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-1">
                                                <span class="class-status-indicator <?php echo $status_class; ?>"></span>
                                                <?php echo htmlspecialchars($class['subject']); ?>
                                            </h6>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </div>
                                        
                                        <p class="mb-1">
                                            <i class="fas fa-door-open me-1 text-muted"></i>
                                            Room: <?php echo htmlspecialchars($class['room_name']); ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                Section: <?php echo htmlspecialchars($class['section']); ?> | 
                                                Year: <?php echo htmlspecialchars($class['year_level'] ?? 'N/A'); ?>
                                            </small>
                                            <span class="time-badge">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo $start_time . ' - ' . $end_time; ?>
                                            </span>
                                        </div>

                                        <!-- Attendance Summary -->
                                        <?php if ($attendance_data): ?>
                                        <div class="attendance-summary mt-2 p-2 bg-light rounded">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <small class="text-success fw-bold"><?php echo $attendance_data['present_count']; ?></small>
                                                    <br><small class="text-muted">Present</small>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-danger fw-bold"><?php echo $attendance_data['absent_count']; ?></small>
                                                    <br><small class="text-muted">Absent</small>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-primary fw-bold"><?php echo $attendance_data['attendance_rate']; ?>%</small>
                                                    <br><small class="text-muted">Rate</small>
                                                </div>
                                            </div>
                                            <div class="progress attendance-progress mt-1">
                                                <div class="progress-bar bg-success" style="width: <?php echo $attendance_data['attendance_rate']; ?>%"></div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="mt-3">
                                            <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>&date=<?php echo urlencode($today_date); ?>" 
                                               class="btn btn-sm btn-outline-primary view-attendance-btn w-100">
                                                <i class="fas fa-chart-bar me-1"></i>
                                                <?php echo $attendance_data ? 'View Detailed Attendance' : 'View Attendance Records'; ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">No Classes Today</h5>
                                <p class="text-muted">Enjoy your day off! No classes scheduled for today.</p>
                                <a href="schedule.php" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Stats -->
            <div class="col-md-6">
                <div class="card card-dashboard h-100">
                    <div class="card-header bg-primary-custom">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <a href="attendance.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-clipboard-check fa-2x mb-2"></i><br>
                                    <span>Take Attendance</span>
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="schedule.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>
                                    <span>View Schedule</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="reports.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                    <span>Reports</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profile.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-user-cog fa-2x mb-2"></i><br>
                                    <span>Profile</span>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Today's Attendance Overview -->
                        <?php if (!empty($today_attendance_summary)): ?>
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-muted mb-3"><i class="fas fa-chart-pie me-2"></i>Today's Overview</h6>
                            <?php 
                            $total_present = 0;
                            $total_absent = 0;
                            $total_students = 0;
                            
                            foreach ($today_attendance_summary as $summary) {
                                $total_present += $summary['present_count'];
                                $total_absent += $summary['absent_count'];
                                $total_students += $summary['total_students'];
                            }
                            
                            $overall_rate = $total_students > 0 ? round(($total_present / $total_students) * 100, 1) : 0;
                            ?>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-success">
                                        <h4 class="mb-1"><?php echo $total_present; ?></h4>
                                        <small>Present</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-danger">
                                        <h4 class="mb-1"><?php echo $total_absent; ?></h4>
                                        <small>Absent</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-primary">
                                        <h4 class="mb-1"><?php echo $overall_rate; ?>%</h4>
                                        <small>Rate</small>
                                    </div>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $overall_rate; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-dashboard">
                    <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-week me-2"></i>Upcoming Classes This Week</h5>
                        <span class="badge bg-light text-dark"><?php echo $upcoming_classes ? $upcoming_classes->num_rows : 0; ?> total</span>
                    </div>
                    <div class="card-body">
                        <?php if ($upcoming_classes && $upcoming_classes->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Subject</th>
                                            <th>Room</th>
                                            <th>Year Level</th>
                                            <th>Section</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($class['day']); ?></strong>
                                                    <?php if ($class['day'] === $today_day): ?>
                                                        <span class="badge bg-success ms-1">Today</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="time-badge">
                                                        <?php echo date("g:i A", strtotime($class['start_time'])) . ' - ' . date("g:i A", strtotime($class['end_time'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($class['room_name']); ?></td>                                       
                                                <td><?php echo isset($class['year_level']) ? htmlspecialchars($class['year_level']) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                                <td>
                                                    <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                       <i class="fas fa-eye me-1"></i> View Records
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-plus text-muted mb-3" style="font-size: 3rem;"></i>
                                <h5 class="text-muted">No Upcoming Classes</h5>
                                <p class="text-muted">No classes scheduled for the rest of the week.</p>
                                <a href="schedule.php" class="btn btn-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>View Full Schedule
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.main-content -->

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

        // Auto-refresh page every 5 minutes to prevent timeout
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes

        // Add smooth scrolling for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers for view attendance buttons
            const viewButtons = document.querySelectorAll('.view-attendance-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Optional: Add loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';
                    this.disabled = true;
                    
                    // Revert after 2 seconds if still on same page
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                });
            });
            
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.transition = 'background-color 0.2s ease';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        });
    </script>
</body>
</html>