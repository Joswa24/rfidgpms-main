<?php
include '../connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<?php
include 'header.php';
?>
<!-- In your dashboard.php, update the Today's Entrance Logs query -->
<?php
$results = mysqli_query($db, "
SELECT 
    COALESCE(p.photo, vl.photo) as photo,
    COALESCE(p.department, vl.department) as department,
    COALESCE(p.rfid_number, vl.rfid_number) as rfid_number,
    COALESCE(p.role, 'Visitor') as role,
    COALESCE(CONCAT(p.first_name, ' ', p.last_name), vl.name) AS full_name,
    COALESCE(rl.time_in, vl.time_in_am, vl.time_in_pm) as time_in,
    COALESCE(rl.time_out, vl.time_out_am, vl.time_out_pm) as time_out,
    COALESCE(rl.location, vl.location) as location,
    COALESCE(rl.date_logged, vl.date_logged) as date_logged
FROM room_logs rl
LEFT JOIN personell p ON rl.personnel_id = p.id
LEFT JOIN visitors vl ON rl.visitor_id = vl.id
WHERE COALESCE(rl.date_logged, vl.date_logged) = CURRENT_DATE()

UNION

SELECT 
    vl.photo,
    vl.department,
    vl.rfid_number,
    'Visitor' AS role,
    vl.name AS full_name,
    COALESCE(vl.time_in_am, vl.time_in_pm) as time_in,
    COALESCE(vl.time_out_am, vl.time_out_pm) as time_out,
    vl.location,
    vl.date_logged
FROM visitor_logs vl
WHERE vl.date_logged = CURRENT_DATE() AND 
      NOT EXISTS (SELECT 1 FROM room_logs rl WHERE rl.visitor_id = vl.id AND rl.date_logged = vl.date_logged)
      
ORDER BY 
    CASE 
        WHEN time_out IS NOT NULL THEN time_out 
        ELSE time_in 
    END DESC
");
?>
<head> 
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {packages: ['corechart']});
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        // Use the PHP generated data
        const weeklyData = <?php
            include '../connection.php';

            // Function to get count of logs for each day
            function getEntrantsCount($db, $tableName) {
                $data = array_fill(0, 7, 0); // Initialize array with 0 values for each day of the week
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

            // Fetch data from personell_logs and visitor_logs
            $personellData = getEntrantsCount($db, 'personell_logs');
            $visitorData = getEntrantsCount($db, 'visitor_logs');

            // Sum the entrants from both tables for each day
            $totalData = [];
            for ($i = 0; $i < 7; $i++) {
                $totalData[$i] = $personellData[$i] + $visitorData[$i];
            }

            // Close connection
            $db->close();

            echo json_encode($totalData);
        ?>;
        
        const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const dataArray = [['Day', 'Entrants']];
        for (let i = 0; i < weeklyData.length; i++) {
            dataArray.push([daysOfWeek[i], weeklyData[i]]);
        }
        const data = google.visualization.arrayToDataTable(dataArray);

        // Set Options
        const options = {
            title: 'Weekly Entrants',
            hAxis: {title: 'Day'},
            vAxis: {title: 'Number of Entrants'},
            legend: 'none'
        };

        // Draw
        const chart = new google.visualization.LineChart(document.getElementById('myChart1'));
        chart.draw(data, options);
    }
</script>
</head>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php
        include 'sidebar.php';
        ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
        <?php
        include 'navbar.php';
        ?>

            <!-- Stats Cards -->
            <?php
            include '../connection.php';
            $today = date('Y-m-d');

            function getCount($db, $query) {
                $result = $db->query($query);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    return $row["count"];
                }
                return 0;
            }

            // Get counts for each category
            $total_entrants_today = getCount($db, "
                SELECT COUNT(*) AS count FROM (
                    SELECT id FROM personell_logs WHERE date_logged = '$today'
                    UNION ALL
                    SELECT id FROM visitor_logs WHERE date_logged = '$today'
                ) AS combined_logs
            ");

            // Count all students (not just those who entered today)
            $total_students = getCount($db, "SELECT COUNT(*) AS count FROM personell WHERE role = 'Student' AND status != 'Block'");
            
            // Count students who entered today
            $students_today = getCount($db, "
                SELECT COUNT(*) AS count FROM personell_logs pl
                JOIN personell p ON pl.personnel_id = p.id
                WHERE pl.date_logged = '$today' AND p.role = 'Student'
            ");

            // Count all instructors
            $total_instructors = getCount($db, "SELECT COUNT(*) AS count FROM personell WHERE role = 'Instructor' AND status != 'Block'");
            
            // Count instructors who entered today
            $instructors_today = getCount($db, "
                SELECT COUNT(*) AS count FROM personell_logs pl
                JOIN personell p ON pl.personnel_id = p.id
                WHERE pl.date_logged = '$today' AND p.role = 'Instructor'
            ");

            // Count all staff
            $total_staff = getCount($db, "SELECT COUNT(*) AS count FROM personell WHERE role IN ('Staff', 'Security Personnel', 'Administrator') AND status != 'Block'");
            
            // Count staff who entered today
            $staff_today = getCount($db, "
                SELECT COUNT(*) AS count FROM personell_logs pl
                JOIN personell p ON pl.personnel_id = p.id
                WHERE pl.date_logged = '$today' AND p.role IN ('Staff', 'Security Personnel', 'Administrator')
            ");

            $visitors_today = getCount($db, "SELECT COUNT(*) AS count FROM visitor_logs WHERE date_logged = '$today'");
            $blocked = getCount($db, "SELECT COUNT(*) AS count FROM personell WHERE status = 'Block'");
            ?>

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
                                <h6 class="mb-0"><?php echo $visitors_today; ?></h6>
                            </div>
                        </div>
                        <div id="visitorLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $sql = "
                                SELECT 
                                    vl.photo,
                                    vl.name AS full_name,
                                    vl.department,
                                    vl.time_in_am,
                                    vl.time_in_pm
                                FROM visitor_logs vl
                                WHERE vl.date_logged = CURRENT_DATE()
                                ORDER BY 
                                    COALESCE(vl.time_in_pm, vl.time_in_am) DESC
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b><br><small>' . htmlspecialchars($row["department"]) . '</small></span>';
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
                                <h6 class="mb-0"><?php echo $blocked; ?></h6>
                            </div>
                        </div>
                        <div id="blockLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $sql = "
                                SELECT 
                                    photo,
                                    CONCAT(first_name, ' ', last_name) AS full_name,
                                    role,
                                    department
                                FROM personell
                                WHERE status = 'Block'
                                ORDER BY first_name, last_name
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b><br><small>' . htmlspecialchars($row["role"]) . ' - ' . htmlspecialchars($row["department"]) . '</small></span>';
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
                                <h6 class="mb-0"><?php echo $total_entrants_today; ?></h6>
                            </div>
                        </div>
                        <div id="entrantsLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $currentDate = date('Y-m-d');
                                $sql = "
                                SELECT 
                                    p.photo,
                                    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                                    p.role,
                                    pl.time_in_am,
                                    pl.time_in_pm
                                FROM personell p
                                JOIN personell_logs pl ON pl.personnel_id = p.id
                                WHERE pl.date_logged = CURRENT_DATE()
                                
                                UNION ALL
                                
                                SELECT 
                                    vl.photo,
                                    vl.name AS full_name,
                                    'Visitor' AS role,
                                    vl.time_in_am,
                                    vl.time_in_pm
                                FROM visitor_logs vl
                                WHERE vl.date_logged = CURRENT_DATE()
                                
                                ORDER BY 
                                    COALESCE(time_in_pm, time_in_am) DESC
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="text-muted ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b> (' . htmlspecialchars($row["role"]) . ')</span>';
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
                                <h6 class="mb-0"><?php echo $students_today; ?> <small class="text-muted">/ <?php echo $total_students; ?></small></h6>
                            </div>
                        </div>
                        <div id="studentLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $sql = "
                                SELECT 
                                    p.photo,
                                    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                                    p.department,
                                    pl.time_in_am,
                                    pl.time_in_pm,
                                    CASE 
                                        WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                                        ELSE 'Absent'
                                    END as status
                                FROM personell p
                                LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
                                WHERE p.role = 'Student' AND p.status != 'Block'
                                ORDER BY 
                                    p.first_name, p.last_name
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . htmlspecialchars($row["department"]) . '</small>';
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
                                <h6 class="mb-0"><?php echo $instructors_today; ?> <small class="text-muted">/ <?php echo $total_instructors; ?></small></h6>
                            </div>
                        </div>
                        <div id="instructorLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $sql = "
                                SELECT 
                                    p.photo,
                                    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                                    p.department,
                                    pl.time_in_am,
                                    pl.time_in_pm,
                                    CASE 
                                        WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                                        ELSE 'Absent'
                                    END as status
                                FROM personell p
                                LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
                                WHERE p.role = 'Instructor' AND p.status != 'Block'
                                ORDER BY 
                                    p.first_name, p.last_name
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . htmlspecialchars($row["department"]) . '</small>';
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
                                <h6 class="mb-0"><?php echo $staff_today; ?> <small class="text-muted">/ <?php echo $total_staff; ?></small></h6>
                            </div>
                        </div>
                        <div id="staffLogs" class="stranger-logs" style="display:none; position: absolute;background: white; border: 1px solid #ccc; padding: 10px;border-radius: 5px; z-index: 100;box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-height: 300px;">
                            <ul class="list-unstyled">
                                <?php
                                $sql = "
                                SELECT 
                                    p.photo,
                                    CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                                    p.department,
                                    p.role,
                                    pl.time_in_am,
                                    pl.time_in_pm,
                                    CASE 
                                        WHEN pl.date_logged = CURRENT_DATE() THEN 'Present'
                                        ELSE 'Absent'
                                    END as status
                                FROM personell p
                                LEFT JOIN personell_logs pl ON pl.personnel_id = p.id AND pl.date_logged = CURRENT_DATE()
                                WHERE p.role IN ('Staff', 'Security Personnel', 'Administrator') AND p.status != 'Block'
                                ORDER BY 
                                    p.first_name, p.last_name
                                LIMIT 10;
                                ";
                                
                                $result = $db->query($sql);
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<li class="mb-2 d-flex align-items-center">';
                                        echo '<span><img style="border-radius:50%;" src="../admin/uploads/' . htmlspecialchars($row["photo"]) . '" width="20px" height="20px"/></span>';
                                        echo '<span class="ms-3"><b>' . htmlspecialchars($row["full_name"]) . '</b><br>';
                                        echo '<small class="' . ($row['status'] == 'Present' ? 'text-success' : 'text-danger') . '">' . $row['status'] . '</small>';
                                        if ($row['status'] == 'Present') {
                                            echo '<br><small>' . htmlspecialchars($row["role"]) . ' - ' . htmlspecialchars($row["department"]) . '</small>';
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

                <!-- Charts Section -->
                <br>
                <div style="margin:0;padding:0;">
                    <div class="row">
                        <!-- Entrants Status Chart -->
                        <div style="padding:20px; margin:10px;width:47%;" class="bg-light rounded">
                            <div id="myChart" style="width:100%; height:300px;"></div>
                            <script>
                            google.charts.load('current', {packages:['corechart']});
                            google.charts.setOnLoadCallback(drawChart2);

                            function drawChart2() {
                                fetch('status.php')
                                    .then(response => response.json())
                                    .then(data => {
                                        const chartData = google.visualization.arrayToDataTable([
                                            ['Status', 'Percentage'],
                                            ['Arrived', data.arrived],
                                            ['Not Arrived', data.not_arrived]
                                        ]);

                                        const options = {
                                            title: 'Entrants Status',
                                            pieSliceText: 'percentage',
                                            slices: {0: { offset: 0.1 }},
                                        };

                                        const chart = new google.visualization.PieChart(document.getElementById('myChart'));
                                        chart.draw(chartData, options);
                                    })
                                    .catch(error => console.error('Error fetching data:', error));
                            }
                            </script>
                        </div>

                        <!-- Department Chart -->
                        <div style="padding:20px; margin:10px;width:47%;" class="bg-light rounded">
                            <div id="myChart2" style="width:100%; height:300px;"></div>
                            <script>
                            google.charts.load('current', {'packages':['corechart']});
                            google.charts.setOnLoadCallback(drawChart);

                            function drawChart() {
                                <?php
                                // Fetch department data with counts
                                $sql = "
                                SELECT 
                                    d.department_name, 
                                    COUNT(DISTINCT p.id) AS personnel_count, 
                                    COUNT(DISTINCT r.id) AS room_count
                                FROM 
                                    department d
                                LEFT JOIN 
                                    personell p ON d.department_name = p.department
                                LEFT JOIN 
                                    rooms r ON d.department_name = r.department
                                GROUP BY 
                                    d.department_id, d.department_name
                                ORDER BY 
                                    d.department_name;
                                ";

                                $result = $db->query($sql);
                                $data = [];

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $data[] = [
                                            'department' => $row['department_name'],
                                            'personnel' => (int)$row['personnel_count'],
                                            'rooms' => (int)$row['room_count']
                                        ];
                                    }
                                }
                                ?>

                                const data = google.visualization.arrayToDataTable([
                                    ['Department', 'Personnel', 'Rooms'],
                                    <?php
                                    foreach ($data as $row) {
                                        echo "['" . $row['department'] . "',  " . $row['personnel'] . ", " . $row['rooms'] . "],";
                                    }
                                    ?>
                                ]);

                                const options = {
                                    title: 'Departments: Personnel and Rooms',
                                    chartArea: {width: '50%'},
                                    hAxis: {title: 'Count'},
                                    vAxis: {title: 'Departments'}
                                };

                                const chart = new google.visualization.BarChart(document.getElementById('myChart2'));
                                chart.draw(data, options);
                            }
                            </script>
                        </div>
                    </div>
                    
                    <!-- Weekly Entrants Chart -->
                    <div class="row">
                        <div style="padding:20px; margin:10px; width:100%;" class="bg-light rounded">
                            <div id="myChart1" style="width:100%; height:300px;"></div>
                        </div>
                    </div>

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
                                    $results = mysqli_query($db, "
                                    SELECT 
                                        p.photo,
                                        p.department,
                                        p.rfid_number,
                                        p.role,
                                        CONCAT(p.first_name, ' ', p.last_name) AS full_name,
                                        rl.time_in,
                                        rl.time_out,
                                        rl.location,
                                        rl.date_logged
                                    FROM room_logs rl
                                    JOIN personell p ON rl.personnel_id = p.id
                                    WHERE rl.date_logged = CURRENT_DATE()
                                    
                                    UNION
                                    
                                    SELECT 
                                        vl.photo,
                                        vl.department,
                                        vl.rfid_number,
                                        'Visitor' AS role,
                                        vl.name AS full_name,
                                        vl.time_in,
                                        vl.time_out,
                                        vl.location,
                                        vl.date_logged
                                    FROM visitor_logs vl
                                    WHERE vl.date_logged = CURRENT_DATE()
                                    
                                    ORDER BY 
                                        CASE 
                                            WHEN time_out IS NOT NULL THEN time_out 
                                            ELSE time_in 
                                        END DESC
                                    ");
                                    
                                    while ($row = mysqli_fetch_array($results)) { 
                                        $timein = $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-';
                                        $timeout = $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-';
                                    ?>
                                        <tr>
                                            <td>
                                                <center><img src="../admin/uploads/<?php echo $row['photo']; ?>" width="50px" height="50px"></center>
                                            </td>
                                            <td><?php echo $row['full_name']; ?></td>
                                            <td><?php echo $row['department']; ?></td>
                                            <td><?php echo $row['role']; ?></td>
                                            <td><?php echo $row['location']; ?></td>
                                            <td><?php echo $timein; ?></td>
                                            <td><?php echo $timeout; ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            include 'footer.php';
            ?>
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