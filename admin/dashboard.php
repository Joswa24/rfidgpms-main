<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include connection and functions
include '../connection.php';
include 'functions.php';

// Validate session
validateSession();

// Get dashboard statistics
$stats = getDashboardStats($db);

// Get today's logs
$logsResult = getTodaysLogs($db);

// Get gate entrance statistics specifically for the dashboard
function getGateEntranceStats($db) {
    $stats = [
        'total_gate_entries' => 0,
        'gate_entries_today' => 0,
        'gate_exits_today' => 0,
        'current_inside' => 0,
        'gate_visitors_today' => 0
    ];
    
    // Total gate entries (all time)
    $sql = "SELECT COUNT(*) as total FROM gate_logs";
    $result = $db->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_gate_entries'] = $row['total'];
    }
    
    // Today's entries and exits
    $today = date('Y-m-d');
    $sql = "SELECT 
        SUM(CASE WHEN time_in = 'TIME IN' THEN 1 ELSE 0 END) as entries,
        SUM(CASE WHEN time_out = 'TIME OUT' THEN 1 ELSE 0 END) as exits,
        -- SUM(CASE WHEN role = 'Visitor' AND DATE(timestamp) = '$today' THEN 1 ELSE 0 END) as visitors
    FROM gate_logs 
    WHERE DATE(timestamp) = '$today'";
    
    $result = $db->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['gate_entries_today'] = $row['entries'] ?? 0;
        $stats['gate_exits_today'] = $row['exits'] ?? 0;
        $stats['gate_visitors_today'] = $row['visitors'] ?? 0;
    }
    
    // Calculate current people inside (entries - exits)
    $stats['current_inside'] = $stats['gate_entries_today'] - $stats['gate_exits_today'];
    
    return $stats;
}

// Get recent gate activities
function getRecentGateActivities($db, $limit = 10) {
    $activities = [];
    $sql = "SELECT gl.*, 
                   COALESCE(s.photo, v.photo, p.photo) as photo,
                   COALESCE(s.full_name, v.full_name, p.full_name) as full_name,
                   COALESCE(s.department, v.department, p.department) as department,
                   gl.role
            FROM gate_logs gl
            LEFT JOIN students s ON gl.id_number = s.id_number AND gl.role = 'Student'
            LEFT JOIN visitors v ON gl.id_number = v.id_number AND gl.role = 'Visitor'
            LEFT JOIN personell p ON gl.id_number = p.id_number AND gl.role IN ('Instructor', 'Staff')
            ORDER BY gl.timestamp DESC 
            LIMIT $limit";
    
    $result = $db->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    return $activities;
}

$gateStats = getGateEntranceStats($db);
$recentGateActivities = getRecentGateActivities($db, 5);
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
                // Function to get count of gate logs for each day
                function getGateEntrantsCount($db) {
                    $data = array_fill(0, 7, 0);
                    for ($i = 0; $i < 7; $i++) {
                        $date = date('Y-m-d', strtotime("last Monday +$i days"));
                        $sql = "SELECT COUNT(*) as count FROM gate_logs WHERE DATE(timestamp) = '$date'";
                        $result = $db->query($sql);
                        if ($result && $row = $result->fetch_assoc()) {
                            $data[$i] = $row['count'];
                        }
                    }
                    return $data;
                }

                $gateData = getGateEntrantsCount($db);
                echo json_encode($gateData);
            ?>;
            
            const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const dataArray = [['Day', 'Gate Entries']];
            for (let i = 0; i < weeklyData.length; i++) {
                dataArray.push([daysOfWeek[i], weeklyData[i]]);
            }
            const data = google.visualization.arrayToDataTable(dataArray);

            const options = {
                title: 'Weekly Gate Entries',
                hAxis: {title: 'Day'},
                vAxis: {title: 'Number of Entries'},
                legend: 'none',
                colors: ['#084298']
            };

            const chart = new google.visualization.LineChart(document.getElementById('gateChart'));
            chart.draw(data, options);
        }

        // Draw pie chart for today's gate activities
        google.charts.setOnLoadCallback(drawPieChart);
        function drawPieChart() {
            const data = google.visualization.arrayToDataTable([
                ['Activity', 'Count'],
                ['Entries', <?php echo $gateStats['gate_entries_today']; ?>],
                ['Exits', <?php echo $gateStats['gate_exits_today']; ?>],
                ['Visitors', <?php echo $gateStats['gate_visitors_today']; ?>]
            ]);

            const options = {
                title: "Today's Gate Activities",
                pieHole: 0.4,
                colors: ['#28a745', '#dc3545', '#ffc107']
            };

            const chart = new google.visualization.PieChart(document.getElementById('pieChart'));
            chart.draw(data, options);
        }
    </script>
</head>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <!-- Gate Statistics Cards -->
            <div class="container-fluid pt-4 px-4">
                <div class="row g-4 mb-4">
                    <!-- Gate Entries Today -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showGateEntries()" onmouseout="hideGateEntries()">
                            <i class="fa fa-sign-in-alt fa-3x text-success"></i>
                            <div class="ms-3">
                                <p class="mb-2">Gate Entries Today</p>
                                <h6 class="mb-0"><?php echo $gateStats['gate_entries_today']; ?></h6>
                            </div>
                        </div>
                    </div>

                    <!-- Gate Exits Today -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showGateExits()" onmouseout="hideGateExits()">
                            <i class="fa fa-sign-out-alt fa-3x text-warning"></i>
                            <div class="ms-3">
                                <p class="mb-2">Gate Exits Today</p>
                                <h6 class="mb-0"><?php echo $gateStats['gate_exits_today']; ?></h6>
                            </div>
                        </div>
                    </div>

                    <!-- Currently Inside -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                            <i class="fa fa-users fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Currently Inside</p>
                                <h6 class="mb-0"><?php echo $gateStats['current_inside']; ?></h6>
                            </div>
                        </div>
                    </div>

                    <!-- Total Gate Entries -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4">
                            <i class="fa fa-door-open fa-3x text-info"></i>
                            <div class="ms-3">
                                <p class="mb-2">Total Gate Entries</p>
                                <h6 class="mb-0"><?php echo $gateStats['total_gate_entries']; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <!-- Weekly Gate Entries Chart -->
                    <div class="col-sm-12 col-xl-8">
                        <div class="bg-light rounded h-100 p-4">
                            <div id="gateChart" style="width: 100%; height: 300px;"></div>
                        </div>
                    </div>
                    
                    <!-- Today's Activities Pie Chart -->
                    <div class="col-sm-12 col-xl-4">
                        <div class="bg-light rounded h-100 p-4">
                            <div id="pieChart" style="width: 100%; height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Gate Activities -->
                <div class="row g-4">
                    <div class="col-12">
                        <div class="bg-light rounded h-100 p-4">
                            <h4><i class="fa fa-history me-2"></i>Recent Gate Activities</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Photo</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Activity</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentGateActivities)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No recent gate activities found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentGateActivities as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($activity['photo'])): ?>
                                                            <img src="../admin/uploads/<?php echo sanitizeOutput($activity['photo']); ?>" 
                                                                 width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div style="width:40px;height:40px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo sanitizeOutput($activity['full_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo sanitizeOutput($activity['role']); ?></td>
                                                    <td><?php echo sanitizeOutput($activity['department'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $activity['time_in'] == 'TIME IN' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $activity['time_in']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatTime($activity['timestamp']); ?></td>
                                                    <td>
                                                        <?php if ($activity['time_in'] == 'TIME IN'): ?>
                                                            <span class="text-success"><i class="fa fa-check-circle"></i> Entered</span>
                                                        <?php else: ?>
                                                            <span class="text-warning"><i class="fa fa-sign-out-alt"></i> Exited</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Detailed Entrance Logs -->
                <div class="bg-light rounded h-100 p-4 mt-4">
                    <h4><i class="bi bi-clock"></i> Today's Detailed Gate Logs</h4>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="gateLogsTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Photo</th>
                                    <th>Full Name</th>
                                    <th>ID Number</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Activity</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!$logsResult) {
                                    echo '<tr><td colspan="10" class="text-danger">Error loading data: ' . mysqli_error($db) . '</td></tr>';
                                } else {
                                    if (mysqli_num_rows($logsResult) > 0) {
                                        while ($row = mysqli_fetch_array($logsResult)) { 
                                            $timein = formatTime($row['time_in']);
                                            $timeout = formatTime($row['time_out']);
                                ?>
                                            <tr>
                                                <td>
                                                    <center>
                                                        <?php if (!empty($row['photo'])): ?>
                                                            <img src="../admin/uploads/<?php echo sanitizeOutput($row['photo']); ?>" 
                                                                 width="50px" height="50px" style="border-radius: 50%; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div style="width:50px;height:50px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </center>
                                                </td>
                                                <td><?php echo sanitizeOutput($row['full_name']); ?></td>
                                                <td><?php echo sanitizeOutput($row['id_number'] ?? 'N/A'); ?></td>
                                                <td><?php echo sanitizeOutput($row['role']); ?></td>
                                                <td><?php echo sanitizeOutput($row['department']); ?></td>
                                                <td><?php echo sanitizeOutput($row['location']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $row['time_in'] == 'TIME IN' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo $row['time_in']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $timein; ?></td>
                                                <td><?php echo $timeout; ?></td>
                                                <td>
                                                    <?php if ($row['time_in'] == 'TIME IN' && empty($row['time_out'])): ?>
                                                        <span class="text-success"><i class="fa fa-building"></i> Inside</span>
                                                    <?php elseif (!empty($row['time_out'])): ?>
                                                        <span class="text-secondary"><i class="fa fa-home"></i> Left</span>
                                                    <?php else: ?>
                                                        <span class="text-info"><i class="fa fa-clock"></i> Processing</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="10" class="text-center">No gate logs found for today.</td></tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php include 'footer.php'; ?>
        </div>
        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff">
            <i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i>
        </a>
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

    <!-- DataTables for better table functionality -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#gateLogsTable').DataTable({
            "order": [[7, "desc"]], // Sort by time in descending order
            "pageLength": 10,
            "responsive": true
        });
    });

    // Auto-refresh the page every 30 seconds to get latest data
    setTimeout(function() {
        location.reload();
    }, 30000); // 30 seconds

    // Hover functions for gate statistics
    function showGateEntries() {
        // You can implement hover details for gate entries here
        console.log('Showing gate entries details');
    }
    
    function hideGateEntries() {
        console.log('Hiding gate entries details');
    }
    
    function showGateExits() {
        console.log('Showing gate exits details');
    }
    
    function hideGateExits() {
        console.log('Hiding gate exits details');
    }
    </script>
</body>
</html>