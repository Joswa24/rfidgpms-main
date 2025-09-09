<?php
include '../connection.php';


//Check if user is logged in as instructor
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
        .sidebar {
            background-color: var(--primary-color);
            min-height: 100vh;
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
        }
        .card-dashboard {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-0">
                <!-- Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['fullname']; ?>
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

                <!-- Page Content -->
                <div class="container-fluid p-4">
                    <div class="welcome-header">
                        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Welcome, <?php echo $_SESSION['fullname']; ?>!</h2>
                        <p class="mb-0">ID: <?php echo $_SESSION['id_number']; ?> | Department: <?php echo $_SESSION['department']; ?></p>
                    </div>

                    <div class="row">
                        <!-- Today's Classes Card -->
                        <div class="col-md-6 mb-4">
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
                                            ?>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo $class['subject_name']; ?></h6>
                                                        <small><?php echo $start_time . ' - ' . $end_time; ?></small>
                                                    </div>
                                                    <p class="mb-1">Room: <?php echo $class['room_name']; ?></p>
                                                    <small>Section: <?php echo $class['section']; ?></small>
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
                        <div class="col-md-6 mb-4">
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>