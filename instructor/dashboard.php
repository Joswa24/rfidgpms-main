<?php
include '../connection.php';


// Check if user is logged in as instructor
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'instructor') {
    header("Location: index.php");
    exit();
}

// Get instructor details
$instructor_id = $_SESSION['instructor_id'];
$stmt = $db->prepare("SELECT i.*, d.department_name 
                     FROM instructor i 
                     LEFT JOIN department d ON i.department_id = d.department_id 
                     WHERE i.id = ?");
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();

// Get today's classes for this instructor
$today = date('Y-m-d');
$classes_query = $db->prepare("
    SELECT rs.*, s.subject_name, r.room_name 
    FROM room_schedule rs 
    INNER JOIN subjects s ON rs.subject_id = s.id 
    INNER JOIN room r ON rs.room_id = r.room_id 
    WHERE rs.instructor_id = ? AND rs.schedule_date = ?
    ORDER BY rs.start_time
");
$classes_query->bind_param("is", $instructor_id, $today);
$classes_query->execute();
$today_classes = $classes_query->get_result();

// Get upcoming classes (next 7 days)
$nextWeek = date('Y-m-d', strtotime('+7 days'));
$upcoming_query = $db->prepare("
    SELECT rs.*, s.subject_name, r.room_name 
    FROM room_schedule rs 
    INNER JOIN subjects s ON rs.subject_id = s.id 
    INNER JOIN room r ON rs.room_id = r.room_id 
    WHERE rs.instructor_id = ? AND rs.schedule_date BETWEEN ? AND ?
    ORDER BY rs.schedule_date, rs.start_time
    LIMIT 5
");
$upcoming_query->bind_param("iss", $instructor_id, $today, $nextWeek);
$upcoming_query->execute();
$upcoming_classes = $upcoming_query->get_result();
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
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item w-100">
                    <a href="attendance.php" class="nav-link">
                        <i class="fas fa-clipboard-check me-2"></i> Attendance
                    </a>
                </li>
                <li class="nav-item w-100">
                    <a href="schedule.php" class="nav-link">
                        <i class="fas fa-calendar-alt me-2"></i> Schedule
                    </a>
                </li>
                <li class="nav-item w-100">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item w-100">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
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
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['fullname'], ENT_QUOTES, 'UTF-8'); ?>
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
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link text-white active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link text-white">
                        <i class="fas fa-clipboard-check me-2"></i> Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedule.php" class="nav-link text-white">
                        <i class="fas fa-calendar-alt me-2"></i> Schedule
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link text-white">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-white">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Page Content -->
    <div class="main-content">
        <div class="welcome-header">
            <h2><i class="fas fa-chalkboard-teacher me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
            <p class="mb-0">ID: <?php echo htmlspecialchars($_SESSION['id_number'], ENT_QUOTES, 'UTF-8'); ?> | Department: <?php echo htmlspecialchars($_SESSION['department'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p class="mb-0">Today is <?php echo date('l, F j, Y'); ?></p>
        </div>

        <div class="row">
            <!-- Today's Classes Card -->
            <div class="col-md-6">
                <div class="card card-dashboard h-100">
                    <div class="card-header bg-primary-custom">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Classes</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($today_classes->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($class = $today_classes->fetch_assoc()): 
                                    $start_time = date("g:i A", strtotime($class['start_time']));
                                    $end_time = date("g:i A", strtotime($class['end_time']));
                                    $current_time = time();
                                    $class_start = strtotime($class['start_time']);
                                    $class_end = strtotime($class['end_time']);
                                    
                                    // Determine if class is in session
                                    $status = '';
                                    if ($current_time >= $class_start && $current_time <= $class_end) {
                                        $status = '<span class="badge bg-success float-end">In Progress</span>';
                                    } elseif ($current_time < $class_start) {
                                        $status = '<span class="badge bg-info float-end">Upcoming</span>';
                                    } else {
                                        $status = '<span class="badge bg-secondary float-end">Completed</span>';
                                    }
                                ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($class['subject_name'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <small><?php echo $start_time . ' - ' . $end_time; ?></small>
                                        </div>
                                        <p class="mb-1">Room: <?php echo htmlspecialchars($class['room_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <small>Section: <?php echo htmlspecialchars($class['section'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php echo $status; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No classes scheduled for today.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
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
                                    Take Attendance
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="schedule.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>
                                    View Schedule
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="reports.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                    Reports
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profile.php" class="btn btn-outline-primary w-100 py-3">
                                    <i class="fas fa-user-cog fa-2x mb-2"></i><br>
                                    Profile
                                </a>
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
                        <?php if ($upcoming_classes->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Subject</th>
                                            <th>Room</th>
                                            <th>Section</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($class = $upcoming_classes->fetch_assoc()): 
                                            $start_time = date("g:i A", strtotime($class['start_time']));
                                            $end_time = date("g:i A", strtotime($class['end_time']));
                                            $class_date = date("M j, Y", strtotime($class['schedule_date']));
                                        ?>
                                            <tr>
                                                <td><?php echo $class_date; ?></td>
                                                <td><?php echo $start_time . ' - ' . $end_time; ?></td>
                                                <td><?php echo htmlspecialchars($class['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($class['room_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($class['section'], ENT_QUOTES, 'UTF-8'); ?></td>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>