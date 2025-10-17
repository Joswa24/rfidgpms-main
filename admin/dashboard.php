<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include connection and functions
include '../connection.php';
include 'functions.php';

// Get dashboard statistics
$stats = getDashboardStats($db);

// Get today's logs
$logsResult = getTodaysLogs($db);
?>
<!DOCTYPE html>
<html lang="en">
    <?php include 'header.php'; ?>
<head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {packages: ['corechart']});
        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {
            const weeklyData = <?php
                // Function to get count of logs for each day
                function getEntrantsCount($db, $tableName) {
                    $data = array_fill(0, 7, 0);
                    for ($i = 0; $i < 7; $i++) {
                        $date = date('Y-m-d', strtotime("last Monday +$i days"));
                        $sql = "SELECT COUNT(*) as count FROM $tableName WHERE date_logged = '$date'";
                        $result = $db->query($sql);
                        if ($result && $row = $result->fetch_assoc()) {
                            $data[$i] = $row['count'];
                        }
                    }
                    return $data;
                }

                $personellData = getEntrantsCount($db, 'personell_logs');
                $visitorData = getEntrantsCount($db, 'visitor_logs');

                $totalData = [];
                for ($i = 0; $i < 7; $i++) {
                    $totalData[$i] = $personellData[$i] + $visitorData[$i];
                }

                echo json_encode($totalData);
            ?>;
            
            const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const dataArray = [['Day', 'Entrants']];
            for (let i = 0; i < weeklyData.length; i++) {
                dataArray.push([daysOfWeek[i], weeklyData[i]]);
            }
            const data = google.visualization.arrayToDataTable(dataArray);

            const options = {
                title: 'Weekly Entrants',
                hAxis: {title: 'Day'},
                vAxis: {title: 'Number of Entrants'},
                legend: 'none',
                colors: ['#5c95e9'],
                backgroundColor: '#f8f9fc',
                chartArea: {width: '85%', height: '70%'}
            };

            const chart = new google.visualization.LineChart(document.getElementById('myChart1'));
            chart.draw(data, options);
        }
    </script>
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--dark-text);
            line-height: 1.6;
        }

        .content {
            background: transparent;
            padding: 20px;
        }

        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }

        /* Stats Cards */
        .stats-card {
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
        }

        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stats-content h6 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-text);
        }

        .stats-content p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
            font-weight: 500;
        }

        .stats-detail {
            font-size: 0.8rem;
            color: #495057;
            margin-top: 5px;
        }

        /* Hover Logs */
        .hover-logs {
            display: none;
            position: absolute;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 15px;
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            width: 280px;
            border: 1px solid var(--accent-color);
        }

        .hover-logs ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hover-logs li {
            padding: 8px 0;
            border-bottom: 1px solid var(--accent-color);
            display: flex;
            align-items: center;
        }

        .hover-logs li:last-child {
            border-bottom: none;
        }

        .hover-logs img {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            object-fit: cover;
            margin-right: 10px;
        }

        .hover-logs .user-info {
            flex: 1;
        }

        .hover-logs .user-info b {
            display: block;
            font-size: 0.9rem;
            color: var(--dark-text);
        }

        .hover-logs .user-info small {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            padding: 20px;
        }

        .table-container h2 {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .table-container h2 i {
            margin-right: 10px;
            color: var(--icon-color);
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: #e9ecef;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .chart-container h3 {
            color: var(--dark-text);
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Back to Top Button */
        .back-to-top {
            background: linear-gradient(135deg, var(--icon-color), #4361ee) !important;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(92, 149, 233, 0.3);
            transition: var(--transition);
        }

        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(92, 149, 233, 0.4);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }

        .section-header h2 {
            color: var(--dark-text);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .section-header h2 i {
            margin-right: 10px;
            color: var(--icon-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content {
                padding: 10px;
            }
            
            .stats-card {
                padding: 15px;
            }
            
            .stats-icon {
                font-size: 2rem;
            }
            
            .stats-content h6 {
                font-size: 1.5rem;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .hover-logs {
                width: 250px;
            }
        }

        @media (max-width: 576px) {
            .stats-card {
                margin-bottom: 15px;
            }
            
            .table thead th {
                padding: 10px 8px;
                font-size: 0.8rem;
            }
            
            .table tbody td {
                padding: 8px 6px;
                font-size: 0.85rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4a7fe0;
        }
    </style>
</head>
<body>
    <div class="container-fluid position-relative d-flex p-0" style="background: transparent;">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content" style="flex: 1;">
            <?php include 'navbar.php'; ?>

            <!-- Main Content Container -->
            <div class="main-container">
                <div class="container-fluid p-4">

                    <!-- Stats Cards -->
                    <div class="row g-4 mb-4">
                        <!-- Total Entrants Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card">
                                <div class="stats-icon text-info">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['total_entrants_today']; ?></h6>
                                    <p>Total Entrants Today</p>
                                </div>
                            </div>
                        </div>
                        <!-- Visitors Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card position-relative"
                                onmouseover="showVisitorLogs()" onmouseout="hideVisitorLogs()">
                                <div class="stats-icon text-primary">
                                    <i class="fa fa-user-plus"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['visitors_today']; ?></h6>
                                    <p>Visitors Today</p>
                                </div>
                                <div id="visitorLogs" class="hover-logs">
                                    <ul class="list-unstyled">
                                        <?php
                                        $visitorLogs = getHoverLogs($db, 'visitors');
                                        if (!empty($visitorLogs)) {
                                            foreach ($visitorLogs as $row) {
                                                echo '<li class="mb-2">';
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<img src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" alt="Visitor Photo">';
                                                echo '<div class="user-info">';
                                                echo '<b>' . sanitizeOutput($row["full_name"]) . '</b>';
                                                echo '<small>' . sanitizeOutput($row["department"]) . '</small>';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</li>';
                                            }
                                        } else {
                                            echo '<li><p class="text-center text-muted">No visitor logs found</p></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Blocked Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card position-relative"
                                onmouseover="showBlockLogs()" onmouseout="hideBlockLogs()">
                                <div class="stats-icon text-danger">
                                    <i class="fa fa-ban"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['blocked']; ?></h6>
                                    <p>Blocked Personnel</p>
                                </div>
                                <div id="blockLogs" class="hover-logs">
                                    <ul class="list-unstyled">
                                        <?php
                                        $blockedLogs = getHoverLogs($db, 'blocked');
                                        if (!empty($blockedLogs)) {
                                            foreach ($blockedLogs as $row) {
                                                echo '<li class="mb-2">';
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<img src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" alt="Blocked Photo">';
                                                echo '<div class="user-info">';
                                                echo '<b>' . sanitizeOutput($row["full_name"]) . '</b>';
                                                echo '<small>' . sanitizeOutput($row["role"]) . ' - ' . sanitizeOutput($row["department"]) . '</small>';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</li>';
                                            }
                                        } else {
                                            echo '<li><p class="text-center text-muted">No blocked personnel found</p></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mb-4">
                        <!-- Students Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card">
                                <div class="stats-icon text-success">
                                    <i class="fa fa-user-graduate"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['students_today']; ?></h6>
                                    <p>Students Present</p>
                                    <div class="stats-detail">
                                        Total: <?php echo $stats['total_students']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                    
                        <!-- Instructors Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card position-relative"
                                onmouseover="showInstructorLogs()" onmouseout="hideInstructorLogs()">
                                <div class="stats-icon text-warning">
                                    <i class="fa fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['instructors_today']; ?></h6>
                                    <p>Instructors Present</p>
                                    <div class="stats-detail">
                                        Total: <?php echo $stats['total_instructors']; ?>
                                    </div>
                                </div>
                                <div id="instructorLogs" class="hover-logs">
                                    <ul class="list-unstyled">
                                        <?php
                                        $instructorLogs = getHoverLogs($db, 'instructors');
                                        if (!empty($instructorLogs)) {
                                            foreach ($instructorLogs as $row) {
                                                echo '<li class="mb-2">';
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<div class="user-info">';
                                                echo '<b>' . sanitizeOutput($row["full_name"]) . '</b>';
                                                echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                                if ($row['status'] == 'Present') {
                                                    echo '<small>' . sanitizeOutput($row["department"]) . '</small>';
                                                }
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</li>';
                                            }
                                        } else {
                                            echo '<li><p class="text-center text-muted">No instructors found</p></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Card -->
                        <div class="col-sm-6 col-xl-4">
                            <div class="stats-card position-relative"
                                onmouseover="showStaffLogs()" onmouseout="hideStaffLogs()">
                                <div class="stats-icon text-secondary">
                                    <i class="fa fa-users-cog"></i>
                                </div>
                                <div class="stats-content">
                                    <h6><?php echo $stats['staff_today']; ?></h6>
                                    <p>Staff Present</p>
                                    <div class="stats-detail">
                                        Total: <?php echo $stats['total_staff']; ?>
                                    </div>
                                </div>
                                <div id="staffLogs" class="hover-logs">
                                    <ul class="list-unstyled">
                                        <?php
                                        $staffLogs = getHoverLogs($db, 'staff');
                                        if (!empty($staffLogs)) {
                                            foreach ($staffLogs as $row) {
                                                echo '<li class="mb-2">';
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<img src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" alt="Staff Photo">';
                                                echo '<div class="user-info">';
                                                echo '<b>' . sanitizeOutput($row["full_name"]) . '</b>';
                                                echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                                if ($row['status'] == 'Present') {
                                                    echo '<small>' . sanitizeOutput($row["role"]) . ' - ' . sanitizeOutput($row["department"]) . '</small>';
                                                }
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</li>';
                                            }
                                        } else {
                                            echo '<li><p class="text-center text-muted">No staff found</p></li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Entrance Logs -->
                    <div class="table-container">
                        <div class="section-header">
                            <h2><i class="fas fa-clock me-2"></i>Today's Entrance Logs</h2>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Location</th>
                                        <th scope="col">Entrance</th>
                                        <th scope="col">Exit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!$logsResult) {
                                        echo '<tr><td colspan="7" class="text-danger text-center">Error loading data: ' . mysqli_error($db) . '</td></tr>';
                                    } else {
                                        if (mysqli_num_rows($logsResult) > 0) {
                                            while ($row = mysqli_fetch_array($logsResult)) { 
                                                $timein = formatTime($row['time_in']);
                                                $timeout = formatTime($row['time_out']);
                                    ?>
                                                <tr>
                                                    <td><?php echo sanitizeOutput($row['full_name']); ?></td>
                                                    <td><?php echo sanitizeOutput($row['role']); ?></td>
                                                    <td><?php echo sanitizeOutput($row['location']); ?></td>
                                                    <td><?php echo $timein; ?></td>
                                                    <td><?php echo $timeout; ?></td>
                                                </tr>
                                    <?php 
                                            }
                                        } else {
                                            echo '<tr><td colspan="7" class="text-center text-muted">No entrance logs found for today.</td></tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Chart Section -->
                    <div class="chart-container mb-4">
                        <h3><i class="fas fa-chart-line me-2"></i>Weekly Entrants Overview</h3>
                        <div id="myChart1" style="height: 300px;"></div>
                    </div>
                </div>
            </div>
            
            <?php include 'footer.php'; ?>
        </div>
        <!-- Back to Top -->
        <a href="#" class="btn btn-lg back-to-top"><i class="fas fa-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <!-- Hover Functions -->
    <script>
    function showVisitorLogs() {
        document.getElementById('visitorLogs').style.display = 'block';
    }
    function hideVisitorLogs() {
        document.getElementById('visitorLogs').style.display = 'none';
    }
    function showBlockLogs() {
        document.getElementById('blockLogs').style.display = 'block';
    }
    function hideBlockLogs() {
        document.getElementById('blockLogs').style.display = 'none';
    }
    function showInstructorLogs() {
        document.getElementById('instructorLogs').style.display = 'block';
    }
    function hideInstructorLogs() {
        document.getElementById('instructorLogs').style.display = 'none';
    }
    function showStaffLogs() {
        document.getElementById('staffLogs').style.display = 'block';
    }
    function hideStaffLogs() {
        document.getElementById('staffLogs').style.display = 'none';
    }

    // Position hover logs dynamically
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.stats-card');
        cards.forEach(card => {
            card.addEventListener('mouseover', function(e) {
                const hoverLog = this.querySelector('.hover-logs');
                if (hoverLog) {
                    const rect = this.getBoundingClientRect();
                    hoverLog.style.top = (rect.bottom + 10) + 'px';
                    hoverLog.style.left = (rect.left) + 'px';
                }
            });
        });
    });
    </script>
</body>
</html>