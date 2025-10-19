<?php
session_start();
// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Include connection
include '../connection.php';

// Check if connection is successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Define essential functions
if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatTime')) {
    function formatTime($time) {
        if (empty($time) || $time == '00:00:00' || $time == '?' || $time == '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            return date('h:i A', strtotime($time));
        } catch (Exception $e) {
            return '-';
        }
    }
}

// Get dashboard statistics
function getDashboardStats($db) {
    $stats = [
        'total_entrants_today' => 0,
        'visitors_today' => 0,
        'students_today' => 0,
        'instructors_today' => 0,
        'staff_today' => 0,
        'blocked' => 0,
        'total_students' => 0,
        'total_instructors' => 0,
        'total_staff' => 0
    ];

    try {
        // Total entrants today
        $query = "SELECT COUNT(*) as total FROM gate_logs WHERE DATE(created_at) = CURDATE()";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_entrants_today'] = $row['total'] ?? 0;
        }

        // Visitors today
        $query = "SELECT COUNT(*) as total FROM visitor_logs WHERE DATE(time_in) = CURDATE()";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['visitors_today'] = $row['total'] ?? 0;
        }

        // Students present today
        $query = "SELECT COUNT(DISTINCT student_id) as total FROM students_glogs WHERE date_logged = CURDATE() AND (time_out IS NULL OR time_out = '00:00:00')";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['students_today'] = $row['total'] ?? 0;
        }

        // Total students
        $query = "SELECT COUNT(*) as total FROM students";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_students'] = $row['total'] ?? 0;
        }

        // Instructors present today
        $query = "SELECT COUNT(DISTINCT instructor_id) as total FROM instructor_glogs WHERE date_logged = CURDATE() AND (time_out IS NULL OR time_out = '00:00:00')";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['instructors_today'] = $row['total'] ?? 0;
        }

        // Total instructors
        $query = "SELECT COUNT(*) as total FROM instructor";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_instructors'] = $row['total'] ?? 0;
        }

        // Staff present today
        $query = "SELECT COUNT(DISTINCT personell_id) as total FROM personell_glogs WHERE date_logged = CURDATE() AND (time_out IS NULL OR time_out = '00:00:00')";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['staff_today'] = $row['total'] ?? 0;
        }

        // Total staff
        $query = "SELECT COUNT(*) as total FROM personell WHERE status != 'Block'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['total_staff'] = $row['total'] ?? 0;
        }

        // Blocked personnel
        $query = "SELECT COUNT(*) as total FROM personell WHERE status = 'Block'";
        $result = $db->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['blocked'] = $row['total'] ?? 0;
        }

    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
    }

    return $stats;
}

// Get today's logs
function getTodaysLogs($db) {
    $query = "SELECT 
                gl.name as full_name,
                gl.person_type as role,
                gl.location,
                gl.time_in,
                gl.time_out,
                gl.created_at
              FROM gate_logs gl
              WHERE DATE(gl.created_at) = CURDATE()
              ORDER BY gl.created_at DESC
              LIMIT 50";
    
    try {
        return $db->query($query);
    } catch (Exception $e) {
        error_log("Today's logs error: " . $e->getMessage());
        return false;
    }
}

// Get weekly entrants data for line chart
function getWeeklyEntrants($db) {
    $weeklyData = [];
    
    try {
        // Get last 7 days including today
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime($date));
            
            $query = "SELECT COUNT(*) as total FROM gate_logs WHERE DATE(created_at) = '$date'";
            $result = $db->query($query);
            
            if ($result) {
                $row = $result->fetch_assoc();
                $weeklyData[] = [
                    'day' => $dayName,
                    'date' => $date,
                    'total' => $row['total'] ?? 0
                ];
            } else {
                $weeklyData[] = [
                    'day' => $dayName,
                    'date' => $date,
                    'total' => 0
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Weekly entrants error: " . $e->getMessage());
    }
    
    return $weeklyData;
}

// Get entrants distribution for pie chart
function getEntrantsDistribution($db) {
    $distribution = [];
    
    try {
        // Get counts for each person type
        $query = "SELECT 
                    person_type,
                    COUNT(*) as total
                  FROM gate_logs 
                  WHERE DATE(created_at) = CURDATE()
                  GROUP BY person_type";
        
        $result = $db->query($query);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $distribution[] = [
                    'type' => ucfirst($row['person_type']),
                    'total' => $row['total'] ?? 0
                ];
            }
        }
        
        // If no data for today, get from all time for demo
        if (empty($distribution)) {
            $query = "SELECT 
                        person_type,
                        COUNT(*) as total
                      FROM gate_logs 
                      GROUP BY person_type
                      LIMIT 10";
            
            $result = $db->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $distribution[] = [
                        'type' => ucfirst($row['person_type']),
                        'total' => $row['total'] ?? 0
                    ];
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Entrants distribution error: " . $e->getMessage());
    }
    
    return $distribution;
}

// Get data
$stats = getDashboardStats($db);
$logsResult = getTodaysLogs($db);
$weeklyData = getWeeklyEntrants($db);
$entrantsDistribution = getEntrantsDistribution($db);
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RFIDGPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --success-color: #1cc88a;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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

        .alert {
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d1edff;
            color: #0c5460;
            border-left: 4px solid #117a8b;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .back-to-top {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)) !important;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
        }

        h6.mb-4 {
            color: var(--dark-text);
            font-weight: 700;
            font-size: 1.25rem;
        }

        hr {
            opacity: 0.1;
            margin: 1.5rem 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(92, 149, 233, 0.05);
            transform: translateY(-1px);
            transition: var(--transition);
        }

        /* Chart container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 20px;
            height: 400px;
        }

        .chart-title {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Hover logs */
        .hover-logs {
            display: none;
            position: absolute;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            padding: 15px;
            z-index: 1050;
            max-height: 280px;
            overflow-y: auto;
            width: 320px;
            border: 1px solid var(--accent-color);
        }

        .hover-logs ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hover-logs li {
            padding: 10px 0;
            border-bottom: 1px solid var(--accent-color);
        }

        .hover-logs li:last-child {
            border-bottom: none;
        }

        .hover-logs img {
            border-radius: 50%;
            width: 35px;
            height: 35px;
            object-fit: cover;
            margin-right: 10px;
        }

        /* Chart responsiveness */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h6>
                            </div>
                        </div>
                        <hr>

                        <!-- Statistics Cards -->
                        <div class="row g-4 mb-4">
                            <!-- Total Entrants -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-info">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['total_entrants_today']); ?></h3>
                                        <p>Total Entrants Today</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Visitors -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-primary position-relative"
                                    onmouseover="showVisitorLogs()" onmouseout="hideVisitorLogs()">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['visitors_today']); ?></h3>
                                        <p>Visitors Today</p>
                                    </div>
                                    <div id="visitorLogs" class="hover-logs">
                                        <h6 class="mb-3"><i class="fas fa-users me-2"></i>Today's Visitors</h6>
                                        <ul class="list-unstyled">
                                            <?php
                                            $visitorQuery = "SELECT full_name, contact_number as department FROM visitor_logs WHERE DATE(time_in) = CURDATE() ORDER BY time_in DESC LIMIT 10";
                                            $visitorResult = $db->query($visitorQuery);
                                            if ($visitorResult && $visitorResult->num_rows > 0) {
                                                while ($row = $visitorResult->fetch_assoc()) {
                                                    echo '<li class="mb-2">';
                                                    echo '<div class="d-flex align-items-center">';
                                                    echo '<img src="../admin/uploads/students/default.png" alt="Visitor Photo">';
                                                    echo '<div>';
                                                    echo '<b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                                    echo '<small class="text-muted">' . sanitizeOutput($row["department"]) . '</small>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }
                                            } else {
                                                echo '<li><div class="text-center text-muted py-3"><i class="fas fa-user-slash fa-2x mb-2"></i><br>No visitors today</div></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Students -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-success">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['students_today']); ?></h3>
                                        <p>Students Present</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_students']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Blocked -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-danger position-relative"
                                    onmouseover="showBlockLogs()" onmouseout="hideBlockLogs()">
                                    <div class="stats-icon">
                                        <i class="fas fa-ban"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['blocked']); ?></h3>
                                        <p>Blocked Personnel</p>
                                    </div>
                                    <div id="blockLogs" class="hover-logs">
                                        <h6 class="mb-3"><i class="fas fa-ban me-2"></i>Blocked Personnel</h6>
                                        <ul class="list-unstyled">
                                            <?php
                                            $blockedQuery = "SELECT CONCAT(first_name, ' ', last_name) as full_name, department, 'Staff' as role FROM personell WHERE status = 'Block' LIMIT 10";
                                            $blockedResult = $db->query($blockedQuery);
                                            if ($blockedResult && $blockedResult->num_rows > 0) {
                                                while ($row = $blockedResult->fetch_assoc()) {
                                                    echo '<li class="mb-2">';
                                                    echo '<div class="d-flex align-items-center">';
                                                    echo '<img src="../admin/uploads/personell/default.png" alt="Blocked Photo">';
                                                    echo '<div>';
                                                    echo '<b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                                    echo '<small class="text-muted">' . sanitizeOutput($row["role"]) . ' - ' . sanitizeOutput($row["department"]) . '</small>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }
                                            } else {
                                                echo '<li><div class="text-center text-muted py-3"><i class="fas fa-check-circle fa-2x mb-2"></i><br>No blocked personnel</div></li>';
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Instructors -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-warning">
                                    <div class="stats-icon">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['instructors_today']); ?></h3>
                                        <p>Instructors Present</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_instructors']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff -->
                            <div class="col-sm-6 col-md-4 col-xl-2">
                                <div class="stats-card text-secondary">
                                    <div class="stats-icon">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div class="stats-content">
                                        <h3><?php echo sanitizeOutput($stats['staff_today']); ?></h3>
                                        <p>Staff Present</p>
                                        <div class="stats-detail">
                                            Total: <?php echo sanitizeOutput($stats['total_staff']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Section -->
                        <div class="row g-4 mb-4">
                            <!-- Weekly Entrants Line Chart -->
                            <div class="col-lg-8">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-line me-2"></i>Weekly Entrants Trend</h5>
                                    <div id="weeklyEntrantsChart" style="height: 100%;"></div>
                                </div>
                            </div>

                            <!-- Entrants Distribution Pie Chart -->
                            <div class="col-lg-4">
                                <div class="chart-container">
                                    <h5 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Entrants Distribution</h5>
                                    <div id="entrantsDistributionChart" style="height: 100%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Entrance Logs -->
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Today's Entrance Logs</h5>
                                        <hr>
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="logsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Full Name</th>
                                                        <th>Role</th>
                                                        <th>Location</th>
                                                        <th>Entrance</th>
                                                        <th>Exit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if (!$logsResult) {
                                                        echo '<tr><td colspan="5" class="text-danger text-center py-4"><i class="fas fa-exclamation-triangle me-2"></i>Error loading entrance logs</td></tr>';
                                                    } else {
                                                        if ($logsResult->num_rows > 0) {
                                                            while ($row = $logsResult->fetch_assoc()) { 
                                                                $timein = formatTime($row['time_in']);
                                                                $timeout = formatTime($row['time_out']);
                                                    ?>
                                                                <tr>
                                                                    <td><?php echo sanitizeOutput($row['full_name']); ?></td>
                                                                    <td><span class="badge bg-primary"><?php echo sanitizeOutput($row['role']); ?></span></td>
                                                                    <td><?php echo sanitizeOutput($row['location']); ?></td>
                                                                    <td><span class="badge bg-success"><?php echo $timein; ?></span></td>
                                                                    <td><span class="badge bg-secondary"><?php echo $timeout; ?></span></td>
                                                                </tr>
                                                    <?php 
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No entrance logs found for today.</td></tr>';
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="fas fa-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script type="text/javascript">
        // Load Google Charts
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawWeeklyEntrantsChart();
            drawEntrantsDistributionChart();
        }

            function drawWeeklyEntrantsChart() {
            // Weekly entrants data from PHP
            const weeklyData = <?php echo json_encode($weeklyData); ?>;
            
            const data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Entrants');
            
            weeklyData.forEach(day => {
                data.addRow([day.day, parseInt(day.total)]);
            });

            const options = {
                title: '',
                curveType: 'function',
                legend: { position: 'none' },
                colors: ['#5c95e9'],
                backgroundColor: 'transparent',
                chartArea: {width: '85%', height: '75%', top: 20, bottom: 60},
                hAxis: {
                    textStyle: {color: '#5a5c69', fontSize: 12},
                    gridlines: { color: 'transparent' },
                    baselineColor: '#5a5c69',
                    showTextEvery: 1,
                    slantedText: false
                },
                vAxis: {
                    title: 'Number of Entrants',
                    titleTextStyle: {color: '#5a5c69', bold: true, fontSize: 12},
                    minValue: 0,
                    gridlines: { 
                        color: '#f0f0f0',
                        count: 5
                    },
                    baseline: 0,
                    baselineColor: '#5a5c69',
                    format: '0',
                    viewWindow: {
                        min: 0
                    },
                    textStyle: {color: '#5a5c69', fontSize: 11}
                },
                titleTextStyle: {
                    color: '#5a5c69',
                    fontSize: 16,
                    bold: true
                },
                lineWidth: 3,
                pointSize: 6,
                animation: {
                    startup: true,
                    duration: 1000,
                    easing: 'out'
                }
            };

            const chart = new google.visualization.LineChart(document.getElementById('weeklyEntrantsChart'));
            chart.draw(data, options);
        }

        function drawEntrantsDistributionChart() {
        // Entrants distribution data from PHP
        const distributionData = <?php echo json_encode($entrantsDistribution); ?>;
        
        const data = new google.visualization.DataTable();
        data.addColumn('string', 'Person Type');
        data.addColumn('number', 'Count');
        
        distributionData.forEach(item => {
            data.addRow([item.type, parseInt(item.total)]);
        });

        const options = {
            title: '',
            pieHole: 0,
            backgroundColor: 'transparent',
            chartArea: {
                width: '95%', 
                height: '85%', 
                top: 20, 
                left: 10,
                right: 10,
                bottom: 20
            },
            legend: {
                position: 'none' // Remove the legend completely
            },
            pieSliceText: 'label', // Show labels (Instructor, Students, etc.) in slices
            tooltip: {
                text: 'percentage',
                showColorCode: true
            },
            slices: {
                0: { color: '#5c95e9' },
                1: { color: '#4e73df' },
                2: { color: '#1cc88a' },
                3: { color: '#36b9cc' },
                4: { color: '#f6c23e' },
                5: { color: '#e74a3b' }
            },
            titleTextStyle: {
                color: '#5a5c69',
                fontSize: 16,
                bold: true
            },
            pieSliceTextStyle: {
                color: 'white',
                fontSize: 12,
                bold: true,
                fontName: 'Arial'
            },
            pieSliceBorderColor: 'transparent', // Remove border lines
            pieSliceBorderWidth: 0, // Remove border width
            is3D: false,
            pieStartAngle: 0,
            sliceVisibilityThreshold: 0 // Show all slices even if very small
        };

        // Format the data to show labels with percentages
        const formatter = new google.visualization.NumberFormat({
            pattern: '#,##0'
        });
        
        formatter.format(data, 1);

        const chart = new google.visualization.PieChart(document.getElementById('entrantsDistributionChart'));
        chart.draw(data, options);
    }
        // Redraw charts on window resize
        window.addEventListener('resize', function() {
            drawCharts();
        });
    </script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable for logs
        $('#logsTable').DataTable({
            order: [[3, 'desc']],
            pageLength: 10,
            responsive: true
        });

        // Auto-refresh logs every 30 seconds
        setInterval(function() {
            $.ajax({
                url: 'refresh_logs.php',
                type: 'GET',
                success: function(data) {
                    // Update the logs table if needed
                    console.log('Logs refreshed');
                }
            });
        }, 30000);
    });

    // Hover log functions
    function showVisitorLogs() {
        const hoverLog = document.getElementById('visitorLogs');
        const card = hoverLog.parentElement;
        const rect = card.getBoundingClientRect();
        
        hoverLog.style.top = (rect.bottom + 10) + 'px';
        hoverLog.style.left = rect.left + 'px';
        hoverLog.style.display = 'block';
    }

    function hideVisitorLogs() {
        setTimeout(() => {
            document.getElementById('visitorLogs').style.display = 'none';
        }, 300);
    }

    function showBlockLogs() {
        const hoverLog = document.getElementById('blockLogs');
        const card = hoverLog.parentElement;
        const rect = card.getBoundingClientRect();
        
        hoverLog.style.top = (rect.bottom + 10) + 'px';
        hoverLog.style.left = rect.left + 'px';
        hoverLog.style.display = 'block';
    }

    function hideBlockLogs() {
        setTimeout(() => {
            document.getElementById('blockLogs').style.display = 'none';
        }, 300);
    }

    // Close hover logs when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.stats-card')) {
            document.querySelectorAll('.hover-logs').forEach(log => {
                log.style.display = 'none';
            });
        }
    });

</script>
</body>
</html>