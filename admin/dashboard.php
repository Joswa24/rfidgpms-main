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
                legend: 'none'
            };

            const chart = new google.visualization.LineChart(document.getElementById('myChart1'));
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

            <!-- Stats Cards -->
            <div class="container-fluid pt-4 px-4">
                <!-- Blocked and Visitors Cards in One Row -->
                <div class="row g-4 mb-4">
                    <!-- Visitors Card -->
                    <div class="col-sm-6 col-xl-6">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showVisitorLogs()" onmouseout="hideVisitorLogs()">
                            <i class="fa fa-user-plus fa-3x text-secondary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Visitors Today</p>
                                <h6 class="mb-0"><?php echo $stats['visitors_today']; ?></h6>
                            </div>
                        </div>
                        <div id="visitorLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $visitorLogs = getHoverLogs($db, 'visitors');
                                if (!empty($visitorLogs)) {
                                    foreach ($visitorLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b><br><small>' . sanitizeOutput($row["department"]) . '</small></span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No visitor logs found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Blocked Card -->
                    <div class="col-sm-6 col-xl-6">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showBlockLogs()" onmouseout="hideBlockLogs()">
                            <i class="fa fa-ban fa-3x text-danger"></i>
                            <div class="ms-3">
                                <p class="mb-2">Blocked Personnel</p>
                                <h6 class="mb-0"><?php echo $stats['blocked']; ?></h6>
                            </div>
                        </div>
                        <div id="blockLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $blockedLogs = getHoverLogs($db, 'blocked');
                                if (!empty($blockedLogs)) {
                                    foreach ($blockedLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b><br><small>' . sanitizeOutput($row["role"]) . ' - ' . sanitizeOutput($row["department"]) . '</small></span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No blocked personnel found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Four Main Cards -->
                <div class="row g-4">
                    <!-- Total Entrants Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showEntrantsLogs()" onmouseout="hideEntrantsLogs()">
                            <i class="fa fa-users fa-3x text-primary"></i>
                            <div class="ms-3">
                                <p class="mb-2">Total Entrants Today</p>
                                <h6 class="mb-0"><?php echo $stats['total_entrants_today']; ?></h6>
                            </div>
                        </div>
                        <div id="entrantsLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $entrantLogs = getHoverLogs($db, 'entrants');
                                if (!empty($entrantLogs)) {
                                    foreach ($entrantLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b> (' . sanitizeOutput($row["role"]) . ')</span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No logs found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Students Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showStudentLogs()" onmouseout="hideStudentLogs()">
                            <i class="fa fa-user-graduate fa-3x text-success"></i>
                            <div class="ms-3">
                                <p class="mb-2">Students</p>
                                <h6 class="mb-0"><?php echo $stats['students_today']; ?> <small class="text-muted">/ <?php echo $stats['total_students']; ?></small></h6>
                            </div>
                        </div>
                        <div id="studentLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $studentLogs = getHoverLogs($db, 'students');
                                if (!empty($studentLogs)) {
                                    foreach ($studentLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . sanitizeOutput($row["department"]) . '</small>';
                                        }
                                        echo '</span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No students found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Instructors Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showInstructorLogs()" onmouseout="hideInstructorLogs()">
                            <i class="fa fa-chalkboard-teacher fa-3x text-info"></i>
                            <div class="ms-3">
                                <p class="mb-2">Instructors</p>
                                <h6 class="mb-0"><?php echo $stats['instructors_today']; ?> <small class="text-muted">/ <?php echo $stats['total_instructors']; ?></small></h6>
                            </div>
                        </div>
                        <div id="instructorLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $instructorLogs = getHoverLogs($db, 'instructors');
                                if (!empty($instructorLogs)) {
                                    foreach ($instructorLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . sanitizeOutput($row["department"]) . '</small>';
                                        }
                                        echo '</span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No instructors found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Staff Card -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="bg-light rounded d-flex align-items-center justify-content-between p-4"
                        onmouseover="showStaffLogs()" onmouseout="hideStaffLogs()">
                            <i class="fa fa-users-cog fa-3x text-warning"></i>
                            <div class="ms-3">
                                <p class="mb-2">Staff</p>
                                <h6 class="mb-0"><?php echo $stats['staff_today']; ?> <small class="text-muted">/ <?php echo $stats['total_staff']; ?></small></h6>
                            </div>
                        </div>
                        <div id="staffLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $staffLogs = getHoverLogs($db, 'staff');
                                if (!empty($staffLogs)) {
                                    foreach ($staffLogs as $row) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . sanitizeOutput($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . sanitizeOutput($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . sanitizeOutput($row["role"]) . ' - ' . sanitizeOutput($row["department"]) . '</small>';
                                        }
                                        echo '</span>';
                                        echo '</li>';
                                    }
                                } else {
                                    echo '<li><p class="text-center">No staff found</p></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Charts Section (keep your existing chart code) -->
                <!-- ... your existing chart code ... -->

                <!-- Today's Entrance Logs -->
                <div class="bg-light rounded h-100 p-4 mt-4">
                    <br>
                    <h2><i class="bi bi-clock"></i> Entrance for today</h2>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-border" id="myDataTable">
                            <thead>
                                <tr>
                                    <th scope="col">Photo</th>
                                    <th scope="col">Full Name</th>
                                    <th scope="col">Department</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Time In</th>
                                    <th scope="col">Time Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!$logsResult) {
                                    echo '<tr><td colspan="7" class="text-danger">Error loading data: ' . mysqli_error($db) . '</td></tr>';
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
                                                            <img src="../admin/uploads/<?php echo sanitizeOutput($row['photo']); ?>" width="50px" height="50px" style="border-radius: 50%;">
                                                        <?php else: ?>
                                                            <div style="width:50px;height:50px;border-radius:50%;background:#ccc;display:flex;align-items:center;justify-content:center;">
                                                                <i class="fa fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </center>
                                                </td>
                                                <td><?php echo sanitizeOutput($row['full_name']); ?></td>
                                                <td><?php echo sanitizeOutput($row['department']); ?></td>
                                                <td><?php echo sanitizeOutput($row['role']); ?></td>
                                                <td><?php echo sanitizeOutput($row['location']); ?></td>
                                                <td><?php echo $timein; ?></td>
                                                <td><?php echo $timeout; ?></td>
                                            </tr>
                                <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center">No entrance logs found for today.</td></tr>';
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
        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff"><i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i></a>
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
    function showEntrantsLogs() {
        document.getElementById('entrantsLogs').style.display = 'block';
    }
    function hideEntrantsLogs() {
        document.getElementById('entrantsLogs').style.display = 'none';
    }
    function showStudentLogs() {
        document.getElementById('studentLogs').style.display = 'block';
    }
    function hideStudentLogs() {
        document.getElementById('studentLogs').style.display = 'none';
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
    </script>
</body>
</html>