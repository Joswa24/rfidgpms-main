<?php
// ✅ Session & Role Check - MUST BE AT THE VERY TOP
session_start();

// Check if essential session variables exist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || 
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' ||
    !isset($_SESSION['instructor_id']) || !isset($_SESSION['fullname']) || !isset($_SESSION['department'])) {
    header("Location: index.php");
    exit();
}

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
include 'header.php';   
include '../connection.php';

// ✅ Fetch Instructor Schedules
$today_classes = null;
$upcoming_classes = null;

// Today's classes
$today_day = date("l");
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor_id = ? AND day = ?
    ORDER BY start_time ASC
");
if ($stmt) {
    $stmt->bind_param("is", $_SESSION['instructor_id'], $today_day);
    $stmt->execute();
    $today_classes = $stmt->get_result();
    $stmt->close();
} else {
    die("Database error: " . $db->error);
}

// Upcoming classes (week overview)
$stmt = $db->prepare("
    SELECT subject, room_name, section, start_time, end_time, day, year_level
    FROM room_schedules
    WHERE instructor_id = ?
    ORDER BY FIELD(day,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
             start_time ASC
");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['instructor_id']);
    $stmt->execute();
    $upcoming_classes = $stmt->get_result();
    $stmt->close();
} else {
    die("Database error: " . $db->error);
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
        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        <div class="welcome-header">
            <h2><i class="fas fa-chalkboard-teacher me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h2>
            <p class="mb-0">ID: <?php echo htmlspecialchars($_SESSION['instructor_id']); ?> | Department: <?php echo htmlspecialchars($_SESSION['department']); ?></p>
            <p class="mb-0">Today is <?php echo date('l, F j, Y'); ?></p>
        </div>

        <div class="row">
            <!-- Today's Classes -->
            <div class="col-md-6">
                <div class="card card-dashboard h-100">
                    <div class="card-header bg-primary-custom">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Classes</h5>
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
                                    $status = ($current_time >= $class_start && $current_time <= $class_end) ? 
                                        '<span class="badge bg-success float-end">In Progress</span>' : 
                                        (($current_time < $class_start) ? 
                                        '<span class="badge bg-info float-end">Upcoming</span>' : 
                                        '<span class="badge bg-secondary float-end">Completed</span>');
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($class['subject']); ?> <?php echo $status; ?></h6>
                                        <p class="mb-1">Room: <?php echo htmlspecialchars($class['room_name']); ?></p>
                                        <small>
                                            Section: <?php echo htmlspecialchars($class['section']); ?> | 
                                            <?php echo $start_time . ' - ' . $end_time; ?>
                                        </small>
                                        <br>
                                        <a href="attendance.php?year=<?php echo urlencode($class['year_level']); ?>&section=<?php echo urlencode($class['section']); ?>&subject=<?php echo urlencode($class['subject']); ?>" 
                                           class="btn btn-sm btn-outline-primary mt-2">
                                           <i class="fas fa-eye me-1"></i> View Attendance
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No classes scheduled for today.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-6">
                <div class="card card-dashboard h-100">
                    <div class="card-header bg-primary-custom">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <a href="attendance.php" class="btn btn-outline-primary w-100 py-3"><i class="fas fa-clipboard-check fa-2x mb-2"></i><br>Take Attendance</a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="schedule.php" class="btn btn-outline-primary w-100 py-3"><i class="fas fa-calendar-alt fa-2x mb-2"></i><br>View Schedule</a>
                            </div>
                            <div class="col-6">
                                <a href="reports.php" class="btn btn-outline-primary w-100 py-3"><i class="fas fa-chart-bar fa-2x mb-2"></i><br>Reports</a>
                            </div>
                            <div class="col-6">
                                <a href="profile.php" class="btn btn-outline-primary w-100 py-3"><i class="fas fa-user-cog fa-2x mb-2"></i><br>Profile</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-dashboard">
                    <div class="card-header bg-primary-custom">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-week me-2"></i>Upcoming Classes This Week</h5>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($class = $upcoming_classes->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($class['day']); ?></td>
                                                <td><?php echo date("g:i A", strtotime($class['start_time'])) . ' - ' . date("g:i A", strtotime($class['end_time'])); ?></td>
                                                <td><?php echo htmlspecialchars($class['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($class['room_name']); ?></td>                                       
                                                <td><?php echo isset($class['year_level']) ? htmlspecialchars($class['year_level']) : '-'; ?></td>
                                                <td><?php echo htmlspecialchars($class['section']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No upcoming classes this week.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>