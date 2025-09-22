<?php
include 'header.php';   
include '../connection.php';
session_start();

// ✅ Session & Role Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'instructor') {
    header("Location: index.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

// ✅ Capture filter params
$filter_year    = isset($_GET['year']) ? $_GET['year'] : null;
$filter_section = isset($_GET['section']) ? $_GET['section'] : null;
$filter_subject = isset($_GET['subject']) ? $_GET['subject'] : null;

$classmates = [];

if ($filter_year && $filter_section) {
    // Fetch attendance for the selected class
    $attendance_query = "
        SELECT s.id_number, s.fullname, s.section, s.year, d.department_name,
               (SELECT COUNT(*) FROM attendance_logs al 
                WHERE al.student_id = s.id 
                  AND DATE(al.time_in) = CURDATE()
                  AND al.instructor_id = ?) as attendance_count
        FROM students s
        LEFT JOIN department d ON s.department_id = d.department_id
        WHERE s.section = ? AND s.year = ?
        ORDER BY s.fullname
    ";

    $stmt = $db->prepare($attendance_query);
    $stmt->bind_param("iss", $instructor_id, $filter_section, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $classmates[] = $row;
    }
    $stmt->close();
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
    <div class="card card-dashboard">
        <div class="card-header bg-primary-custom d-flex justify-content-between">
            <h5 class="card-title mb-0">
                <i class="fas fa-clipboard-check me-2"></i>
                <?php if ($filter_year && $filter_section): ?>
                    Attendance - <?php echo htmlspecialchars($filter_year . " - " . $filter_section); ?>
                    <?php if ($filter_subject): ?> (<?php echo htmlspecialchars($filter_subject); ?>)<?php endif; ?>
                <?php else: ?>
                    Attendance
                <?php endif; ?>
            </h5>
            <a href="dashboard.php" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
        <div class="card-body">
            <?php if ($filter_year && $filter_section): ?>
                <?php if (!empty($classmates)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Section</th>
                                    <th>Year</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classmates as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($student['section']); ?></td>
                                        <td><?php echo htmlspecialchars($student['year']); ?></td>
                                        <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                                        <td>
                                            <?php echo ($student['attendance_count'] > 0) 
                                                ? '<span class="badge bg-success">Present</span>' 
                                                : '<span class="badge bg-danger">Absent</span>'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No attendance records found for this class today.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Please select a class from your dashboard to view attendance.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
