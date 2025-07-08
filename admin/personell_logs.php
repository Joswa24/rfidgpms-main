<?php
// Start session at the very beginning
session_start();

// Initialize database connection
include '../connection.php';

// Check if database connection was successful
if (!$db) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize filtered data array
$filtered_data = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize filter variables
    $date1 = isset($_POST['date1']) ? $_POST['date1'] : '';
    $date2 = isset($_POST['date2']) ? $_POST['date2'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    
    // Validate dates - either both empty or both provided
    if (($date1 && !$date2) || (!$date1 && $date2)) {
        echo '<script>alert("Please enter both dates or leave both blank.");</script>';
    } else {
        // Build query with proper filtering
        $sql = "SELECT p.first_name, p.last_name, p.department, p.role, p.photo, 
                       rl.location, rl.time_in, rl.time_out, rl.date_logged 
                FROM personell AS p
                JOIN room_logs AS rl ON p.id = rl.personnel_id";
        
        $where = [];
        $params = [];
        $types = '';
        
        // Add date filter if both dates provided
        if ($date1 && $date2) {
            $where[] = "rl.date_logged BETWEEN ? AND ?";
            $params[] = date('Y-m-d', strtotime($date1));
            $params[] = date('Y-m-d', strtotime($date2));
            $types .= 'ss';
        }
        
        // Add other filters
        if ($location) {
            $where[] = "rl.location = ?";
            $params[] = $location;
            $types .= 's';
        }
        
        if ($role) {
            $where[] = "p.role = ?";
            $params[] = $role;
            $types .= 's';
        }
        
        if ($department) {
            $where[] = "p.department = ?";
            $params[] = $department;
            $types .= 's';
        }
        
        // Combine WHERE clauses
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY rl.date_logged DESC";
        
        // Prepare and execute query
        if ($stmt = $db->prepare($sql)) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $filtered_data = $result->fetch_all(MYSQLI_ASSOC);
            $_SESSION['filtered_data'] = $filtered_data;
            $stmt->close();
        }
    }
} else {
    // Default query when no filters applied
    $sql = "SELECT p.first_name, p.last_name, p.department, p.role, p.photo, 
                   rl.location, rl.time_in, rl.time_out, rl.date_logged 
            FROM personell AS p
            JOIN room_logs AS rl ON p.id = rl.personnel_id 
            ORDER BY rl.date_logged DESC";
    
    $result = mysqli_query($db, $sql);
    if ($result) {
        $filtered_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $_SESSION['filtered_data'] = $filtered_data;
    } else {
        die("Query failed: " . mysqli_error($db));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Personnel Logs</h6>
                            </div>
                        </div>
                        <br>
                        <form id="filterForm" method="POST">
                            <div class="row">
                                <div class="col-lg-3">
                                    <label>Date:</label>
                                    <input type="text" class="form-control" name="date1" placeholder="Start" id="date1" autocomplete="off" />
                                </div>
                                <div class="col-lg-3">
                                    <label>To:</label>
                                    <input type="text" class="form-control" name="date2" placeholder="End" id="date2" autocomplete="off" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2">
                                    <label>Department:</label>
                                    <select class="form-control" name="department" id="department" autocomplete="off">
                                        <option value="">Select</option>
                                        <?php
                                        $dept_result = $db->query("SELECT * FROM department");
                                        while ($row = $dept_result->fetch_assoc()) {
                                            echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <label>Location:</label>
                                    <select class="form-control mb-4" name="location" id="location" autocomplete="off">
                                        <option value="">Select</option>
                                        <option value="Gate">Gate</option>
                                        <?php
                                        $room_result = $db->query("SELECT * FROM rooms");
                                        while ($row = $room_result->fetch_assoc()) {
                                            echo "<option value='{$row['room']}'>{$row['room']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-lg-2">
                                    <label>Role:</label>
                                    <select class="form-control dept_ID" name="role" id="role" autocomplete="off">
                                        <option value="">Select</option>
                                        <?php
                                        $role_result = $db->query("SELECT * FROM role");
                                        while ($row = $role_result->fetch_assoc()) {
                                            echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-lg-3 mt-4">
                                    <label></label>
                                    <button type="submit" class="btn btn-primary" id="btn_search"><i class="fa fa-search"></i> Filter</button>
                                    <button type="button" id="reset" class="btn btn-warning"><i class="fa fa-sync"></i> Reset</button>
                                </div>
                                <div class="col-lg-3 mt-4" style="text-align:right;">
                                    <label></label>
                                    <button type="button" class="btn btn-success" id="btn_print"><i class="fa fa-print"> Print</i></button> 
                                </div>
                            </div>
                        </form>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Full Name</th>
                                        <th>Department</th>
                                        <th>Location</th>
                                        <th>Role</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Log Date</th>
                                    </tr>
                                </thead>
                                <tbody id="load_data">
                                    <?php
                                    if (!empty($filtered_data)) {
                                        foreach ($filtered_data as $row) {
                                            echo '<tr>';
                                            echo '<td><center><img src="uploads/' . $row['photo'] . '" width="50px" height="50px"></center></td>';
                                            echo '<td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>';
                                            echo '<td>' . $row['department'] . '</td>';
                                            echo '<td>' . $row['location'] . '</td>';
                                            echo '<td>' . $row['role'] . '</td>';
                                            echo '<td>' . date("h:i A", strtotime($row['time_in'])) . '</td>';
                                            
                                            if ($row['time_out'] === '?' || $row['time_out'] === '' || is_null($row['time_out'])) {
                                                echo '<td>' . $row['time_out'] . '</td>';
                                            } else {
                                                echo '<td>' . date("h:i A", strtotime($row['time_out'])) . '</td>';
                                            }
                                            
                                            echo '<td>' . $row['date_logged'] . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="8">No records found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        // Initialize datepickers
        $('#date1, #date2').datepicker();
        
        // Handle search button click
        $('#btn_search').on('click', function() {
            $date1 = $('#date1').val();
            $date2 = $('#date2').val();
            
            if (($date1 && !$date2) || (!$date1 && $date2)) {
                alert("Please enter both dates or leave both blank.");
                return false;
            }
            
            $('#load_data').empty();
            $loader = $('<tr><td colspan="8"><center>Searching....</center></td></tr>');
            $loader.appendTo('#load_data');
            
            setTimeout(function() {
                $loader.remove();
                $('#filterForm').submit();
            }, 1000);
        });

        // Handle reset button click
        $('#reset').on('click', function() {
            location.reload();
        });

        // Handle print button click
        $('#btn_print').on('click', function() {
            var iframe = $('<iframe>', {
                id: 'printFrame',
                style: 'visibility:hidden; display:none'
            }).appendTo('body');

            iframe.attr('src', 'print.php');

            iframe.on('load', function() {
                this.contentWindow.print();
                setTimeout(function() {
                    iframe.remove();
                }, 1000);
            });
        });
    });
    </script>
</body>
</html>