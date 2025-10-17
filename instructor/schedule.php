<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ✅ Session & Role Check - MUST BE AT THE VERY TOP
session_start();

// Debug session data
error_log("=== SCHEDULE ACCESS ===");
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

error_log("SESSION VALIDATION PASSED - Loading schedule");

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

// ✅ Fetch Instructor Information
$instructor_info = null;
$instructor_id = $_SESSION['instructor_id'];

// Get instructor details
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

// ✅ Fetch Instructor Schedules
// ✅ Fetch Instructor Schedules
$schedules = [];
$filtered_schedules = [];

// Get instructor's fullname
$instructor_name = $_SESSION['fullname'];

// Get all schedules for the instructor (using name instead of ID)
$stmt = $db->prepare("
    SELECT rs.*, d.department_name 
    FROM room_schedules rs
    LEFT JOIN department d ON rs.department = d.department_name
    WHERE rs.instructor = ?
    ORDER BY FIELD(rs.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
             rs.start_time ASC
");

if ($stmt) {
    $stmt->bind_param("s", $instructor_name);
    $stmt->execute();
    $schedules = $stmt->get_result();
    $stmt->close();
}

// Apply filters if any
$filter_day = $_GET['day'] ?? '';
$filter_room = $_GET['room'] ?? '';
$filter_subject = $_GET['subject'] ?? '';

if ($filter_day || $filter_room || $filter_subject) {
    $filter_conditions = [];
    $params = [];
    $types = '';
    
    if ($filter_day) {
        $filter_conditions[] = "rs.day = ?";
        $params[] = $filter_day;
        $types .= 's';
    }
    
    if ($filter_room) {
        $filter_conditions[] = "rs.room_name = ?";
        $params[] = $filter_room;
        $types .= 's';
    }
    
    if ($filter_subject) {
        $filter_conditions[] = "rs.subject = ?";
        $params[] = $filter_subject;
        $types .= 's';
    }
    
    $where_clause = "rs.instructor = ?";
    $params = array_merge([$instructor_name], $params);
    $types = 's' . $types;
    
    if (!empty($filter_conditions)) {
        $where_clause .= " AND " . implode(" AND ", $filter_conditions);
    }
    
    $stmt = $db->prepare("
        SELECT rs.*, d.department_name 
        FROM room_schedules rs
        LEFT JOIN department d ON rs.department = d.department_name
        WHERE $where_clause
        ORDER BY FIELD(rs.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
                 rs.start_time ASC
    ");
    
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $filtered_schedules = $stmt->get_result();
        $stmt->close();
    }
} else {
    $filtered_schedules = $schedules;
}

// Update the filter option queries too:
$days = $db->query("SELECT DISTINCT day FROM room_schedules WHERE instructor = '$instructor_name' ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$rooms = $db->query("SELECT DISTINCT room_name FROM room_schedules WHERE instructor = '$instructor_name' ORDER BY room_name");
$subjects = $db->query("SELECT DISTINCT subject FROM room_schedules WHERE instructor = '$instructor_name' ORDER BY subject");
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
    <title>My Schedule - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        
        .filter-section {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .schedule-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin: 0 auto;
        }
        
        .time-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .current-day {
            background-color: #d4edda !important;
            border-left: 4px solid #28a745;
        }
        
        .day-highlight {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 12px;
        }
        
        .table td {
            padding: 12px;
            vertical-align: middle;
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
        
        .filter-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-left: 5px;
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
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                </li>
                <li class="nav-item w-100">
                    <a href="attendance.php" class="nav-link"><i class="fas fa-clipboard-check me-2"></i> Attendance</a>
                </li>
                <li class="nav-item w-100">
                    <a href="schedule.php" class="nav-link active"><i class="fas fa-calendar-alt me-2"></i> Schedule</a>
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
                <li class="nav-item"><a href="dashboard.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="attendance.php" class="nav-link text-white"><i class="fas fa-clipboard-check me-2"></i> Attendance</a></li>
                <li class="nav-item"><a href="schedule.php" class="nav-link text-white active"><i class="fas fa-calendar-alt me-2"></i> Schedule</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link text-white"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-content">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="instructor-avatar">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h2 class="mb-2">My Teaching Schedule</h2>
                        <div class="mb-3">
                            <span class="info-badge">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                            </span>
                            <span class="info-badge">
                                <i class="fas fa-building me-1"></i>
                                Department: <?php echo htmlspecialchars($_SESSION['department']); ?>
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

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><b><i class="fas fa-calendar-day me-2"></i>Filter by Day:</b></label>
                            <select class="form-control" name="day" id="filter_day">
                                <option value="">All Days</option>
                                <?php while ($day = $days->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($day['day']) ?>" <?= ($filter_day == $day['day']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($day['day']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><b><i class="fas fa-door-open me-2"></i>Filter by Room:</b></label>
                            <select class="form-control" name="room" id="filter_room">
                                <option value="">All Rooms</option>
                                <?php 
                                $rooms->data_seek(0);
                                while ($room = $rooms->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($room['room_name']) ?>" <?= ($filter_room == $room['room_name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($room['room_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label"><b><i class="fas fa-book me-2"></i>Filter by Subject:</b></label>
                            <select class="form-control" name="subject" id="filter_subject">
                                <option value="">All Subjects</option>
                                <?php 
                                $subjects->data_seek(0);
                                while ($subject = $subjects->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($subject['subject']) ?>" <?= ($filter_subject == $subject['subject']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 d-flex justify-content-between align-items-center">
                        <div id="activeFilters" class="d-flex flex-wrap gap-2">
                            <?php if ($filter_day): ?>
                                <span class="filter-badge">
                                    Day: <?= htmlspecialchars($filter_day) ?>
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" onclick="removeFilter('day')"></button>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_room): ?>
                                <span class="filter-badge">
                                    Room: <?= htmlspecialchars($filter_room) ?>
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" onclick="removeFilter('room')"></button>
                                </span>
                            <?php endif; ?>
                            <?php if ($filter_subject): ?>
                                <span class="filter-badge">
                                    Subject: <?= htmlspecialchars($filter_subject) ?>
                                    <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" onclick="removeFilter('subject')"></button>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="schedule.php" class="btn btn-outline-secondary">
                                <i class="fas fa-refresh me-2"></i>Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Schedule Table -->
        <div class="card card-dashboard">
            <div class="card-header bg-primary-custom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Teaching Schedule
                    <span class="badge bg-light text-dark ms-2">
                        <?php echo $filtered_schedules ? $filtered_schedules->num_rows : 0; ?> classes
                    </span>
                </h5>
                <button type="button" class="btn btn-light btn-sm" onclick="printSchedule()">
                    <i class="fas fa-print me-2"></i>Print Schedule
                </button>
            </div>
            <div class="card-body">
                <?php if ($filtered_schedules && $filtered_schedules->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Room</th>
                                    <th>Year Level</th>
                                    <th>Section</th>
                                    <th>Department</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $today = date('l');
                                while ($schedule = $filtered_schedules->fetch_assoc()): 
                                    $isToday = ($schedule['day'] == $today) ? 'current-day' : '';
                                ?>
                                    <tr class="<?= $isToday ?>">
                                        <td>
                                            <div class="schedule-icon">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                        </td>
                                        <td class="day-highlight">
                                            <strong><?= htmlspecialchars($schedule['day']) ?></strong>
                                            <?php if ($isToday): ?>
                                                <br><span class="badge bg-success">Today</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="time-badge">
                                                <?= date("g:i A", strtotime($schedule['start_time'])) ?> - 
                                                <?= date("g:i A", strtotime($schedule['end_time'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($schedule['subject']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['room_name']) ?></td>
                                        <td><?= htmlspecialchars($schedule['year_level']) ?></td>
                                        <td><?= htmlspecialchars($schedule['section']) ?></td>
                                        <td><?= htmlspecialchars($schedule['department_name'] ?? $schedule['department']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 4rem;"></i>
                        <h4 class="text-muted">No Classes Found</h4>
                        <p class="text-muted">
                            <?php if ($filter_day || $filter_room || $filter_subject): ?>
                                No classes match your current filters. Try adjusting your filter criteria.
                            <?php else: ?>
                                You don't have any classes scheduled yet.
                            <?php endif; ?>
                        </p>
                        <?php if ($filter_day || $filter_room || $filter_subject): ?>
                            <a href="schedule.php" class="btn btn-primary">
                                <i class="fas fa-refresh me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Initialize DataTable
        document.addEventListener('DOMContentLoaded', function() {
            $('#scheduleTable').DataTable({
                order: [[1, 'asc'], [2, 'asc']], // Sort by day then time
                pageLength: 25,
                language: {
                    search: "Search schedules:",
                    lengthMenu: "Show _MENU_ classes per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ classes",
                    infoEmpty: "No classes to show",
                    infoFiltered: "(filtered from _MAX_ total classes)"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        });

        // Update current time
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

        // Remove filter function
        function removeFilter(filterType) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(filterType);
            window.location.href = 'schedule.php?' + urlParams.toString();
        }

        // Print schedule function
        function printSchedule() {
            const printWindow = window.open('', '_blank');
            const today = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Teaching Schedule - <?php echo $_SESSION['fullname']; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                        .instructor-info { margin-bottom: 15px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                        th { background-color: #87abe0; color: white; }
                        .today { background-color: #e7f3ff; }
                        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #666; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Teaching Schedule</h2>
                        <div class="instructor-info">
                            <h3><?php echo $_SESSION['fullname']; ?></h3>
                            <p>Department: <?php echo $_SESSION['department']; ?></p>
                            <p>Generated on: ${today}</p>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Year Level</th>
                                <th>Section</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
            `);
            
            <?php 
            $filtered_schedules->data_seek(0);
            while ($schedule = $filtered_schedules->fetch_assoc()): 
            ?>
            printWindow.document.write(`
                <tr <?php echo ($schedule['day'] == date('l')) ? 'class="today"' : ''; ?>>
                    <td><?= htmlspecialchars($schedule['day']) ?></td>
                    <td><?= date("g:i A", strtotime($schedule['start_time'])) ?> - <?= date("g:i A", strtotime($schedule['end_time'])) ?></td>
                    <td><?= htmlspecialchars($schedule['subject']) ?></td>
                    <td><?= htmlspecialchars($schedule['room_name']) ?></td>
                    <td><?= htmlspecialchars($schedule['year_level']) ?></td>
                    <td><?= htmlspecialchars($schedule['section']) ?></td>
                    <td><?= htmlspecialchars($schedule['department_name'] ?? $schedule['department']) ?></td>
                </tr>
            `);
            <?php endwhile; ?>
            
            printWindow.document.write(`
                        </tbody>
                    </table>
                    <div class="footer">
                        <p>This schedule is automatically generated from the RFID System.</p>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Auto-refresh page every 5 minutes to prevent timeout
        setTimeout(function() {
            window.location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>