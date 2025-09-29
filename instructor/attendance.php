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
$filter_date    = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); // Default to today

$classmates = [];
$available_dates = [];
$available_classes = [];

// Get available dates for this instructor
$dates_query = "SELECT DISTINCT date FROM instructor_attendance_records 
                WHERE instructor_id = ? 
                ORDER BY date DESC";
$dates_stmt = $db->prepare($dates_query);
$dates_stmt->bind_param("i", $instructor_id);
$dates_stmt->execute();
$dates_result = $dates_stmt->get_result();

while ($date_row = $dates_result->fetch_assoc()) {
    $available_dates[] = $date_row['date'];
}
$dates_stmt->close();

// Get available classes for this instructor
$classes_query = "SELECT DISTINCT year, section, subject FROM instructor_attendance_records 
                  WHERE instructor_id = ? 
                  ORDER BY year, section";
$classes_stmt = $db->prepare($classes_query);
$classes_stmt->bind_param("i", $instructor_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

while ($class_row = $classes_result->fetch_assoc()) {
    $available_classes[] = $class_row;
}
$classes_stmt->close();

if ($filter_year && $filter_section) {
    // Fetch attendance from saved records
    $attendance_query = "
        SELECT student_id_number, student_name, section, year, department, status, date, subject
        FROM instructor_attendance_records 
        WHERE instructor_id = ? 
        AND year = ? 
        AND section = ?
    ";
    
    $params = [$instructor_id, $filter_year, $filter_section];
    $types = "iss";
    
    // Add date filter if specified
    if ($filter_date) {
        $attendance_query .= " AND date = ?";
        $params[] = $filter_date;
        $types .= "s";
    }
    
    // Add subject filter if specified
    if ($filter_subject) {
        $attendance_query .= " AND subject = ?";
        $params[] = $filter_subject;
        $types .= "s";
    }
    
    $attendance_query .= " ORDER BY student_name";
    
    $stmt = $db->prepare($attendance_query);
    $stmt->bind_param($types, ...$params);
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
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .present-badge {
            background-color: #28a745;
        }
        .absent-badge {
            background-color: #dc3545;
        }
        .date-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
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
        <!-- Filter Section -->
        <div class="filter-section">
            <h5><i class="fas fa-filter me-2"></i>Filter Attendance Records</h5>
            <form method="GET" action="attendance.php" class="row g-3">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <select class="form-select" id="date" name="date">
                        <option value="">All Dates</option>
                        <?php foreach ($available_dates as $date): ?>
                            <option value="<?php echo $date; ?>" <?php echo ($filter_date == $date) ? 'selected' : ''; ?>>
                                <?php echo date('M j, Y', strtotime($date)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">Select Year</option>
                        <?php foreach ($available_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['year']); ?>" 
                                <?php echo ($filter_year == $class['year']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="section" class="form-label">Section</label>
                    <select class="form-select" id="section" name="section">
                        <option value="">Select Section</option>
                        <?php foreach ($available_classes as $class): ?>
                            <?php if ($filter_year && $class['year'] == $filter_year): ?>
                                <option value="<?php echo htmlspecialchars($class['section']); ?>" 
                                    <?php echo ($filter_section == $class['section']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['section']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subject" class="form-label">Subject</label>
                    <select class="form-select" id="subject" name="subject">
                        <option value="">All Subjects</option>
                        <?php foreach ($available_classes as $class): ?>
                            <?php if ($filter_year && $filter_section && $class['year'] == $filter_year && $class['section'] == $filter_section && !empty($class['subject'])): ?>
                                <option value="<?php echo htmlspecialchars($class['subject']); ?>" 
                                    <?php echo ($filter_subject == $class['subject']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['subject']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Filter</button>
                    <a href="attendance.php" class="btn btn-secondary"><i class="fas fa-refresh me-2"></i>Reset</a>
                </div>
            </form>
        </div>

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
                        Attendance Records
                    <?php endif; ?>
                </h5>
                <a href="dashboard.php" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
            </div>
            <div class="card-body">
                <?php if ($filter_year && $filter_section): ?>
                    <?php if (!empty($classmates)): ?>
                        <!-- Statistics -->
                        <?php
                        $present_count = 0;
                        $absent_count = 0;
                        foreach ($classmates as $student) {
                            if ($student['status'] == 'Present') {
                                $present_count++;
                            } else {
                                $absent_count++;
                            }
                        }
                        $total_count = count($classmates);
                        $attendance_rate = $total_count > 0 ? round(($present_count / $total_count) * 100, 1) : 0;
                        ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stats-card bg-success text-white">
                                    <i class="fas fa-user-check fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $present_count; ?></div>
                                    <div>Present</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-danger text-white">
                                    <i class="fas fa-user-times fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $absent_count; ?></div>
                                    <div>Absent</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-info text-white">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $total_count; ?></div>
                                    <div>Total Students</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card bg-warning text-dark">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $attendance_rate; ?>%</div>
                                    <div>Attendance Rate</div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Table -->
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
                                        <th>Date</th>
                                        <th>Subject</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classmates as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['section']); ?></td>
                                            <td><?php echo htmlspecialchars($student['year']); ?></td>
                                            <td><?php echo htmlspecialchars($student['department']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $student['status'] == 'Present' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo htmlspecialchars($student['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($student['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($student['subject'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No attendance records found for the selected filters.</p>
                            <a href="attendance.php" class="btn btn-primary">View All Records</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Welcome to Attendance Records</h4>
                        <p class="text-muted">Please select filters above to view attendance records.</p>
                        <?php if (empty($available_classes)): ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                No attendance records found. Records will appear here after you save attendance from the scanner page.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dynamic dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const yearSelect = document.getElementById('year');
            const sectionSelect = document.getElementById('section');
            const subjectSelect = document.getElementById('subject');
            
            // Update sections when year changes
            yearSelect.addEventListener('change', function() {
                const selectedYear = this.value;
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                
                if (selectedYear) {
                    // This would typically be populated via AJAX, but for now we'll rely on page refresh
                    // You can implement AJAX here to dynamically load sections without page refresh
                }
            });
            
            // Update subjects when section changes
            sectionSelect.addEventListener('change', function() {
                const selectedYear = yearSelect.value;
                const selectedSection = this.value;
                subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                
                if (selectedYear && selectedSection) {
                    // This would typically be populated via AJAX
                }
            });
        });
    </script>
</body>
</html>