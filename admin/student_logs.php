<?php
session_start();
include 'header.php';
include '../connection.php';

// Fetch data from the about table
$logo1 = $logo2 = $nameo = $address = '';
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
}

// Determine if we're viewing current or archived logs
$view = isset($_GET['view']) ? $_GET['view'] : 'current';
$instructor_table = ($view == 'archived') ? 'archived_instructor_logs' : 'instructor_logs';

// Date filter logic
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// Add instructor filter logic
$search_instructor = isset($_GET['search_instructor']) ? trim($_GET['search_instructor']) : '';

// Check for recently archived records
$recent_archives = false;
if ($view == 'archived') {
    $recent_query = "SELECT COUNT(*) as count FROM archived_instructor_logs 
                    WHERE DATE(time_in) = ?";
    $recent_stmt = $db->prepare($recent_query);
    if ($recent_stmt) {
        $recent_stmt->bind_param("s", $selected_date);
        $recent_stmt->execute();
        $recent_result = $recent_stmt->get_result();
        $recent_row = $recent_result->fetch_assoc();
        $recent_archives = ($recent_row['count'] > 0);
        $recent_stmt->close();
    }
}
?>
<style>
    .instructor-header {
        background-color: #f8f9fa;
        padding: 0.75rem 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }

    .instructor-info {
        display: flex;
        align-items: center;
    }

    .instructor-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-right: 0.25rem;
    }

    .detail-value {
        color: #212529;
    }

    .instructor-date-item,
    .instructor-submitter-item,
    .instructor-year-item,
    .instructor-section-item {
        display: flex;
        align-items: center;
    }

    @media (max-width: 576px) {
        .instructor-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .instructor-details {
            flex-direction: column;
            gap: 0.25rem;
        }
        .btn-outline-danger {
            align-self: flex-end;
            margin-top: 0.5rem;
        }
    }
</style>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid pt-4 px-4">
            <div class="col-sm-12 col-xl-12">
                <div class="bg-light rounded h-100 p-4">
                    <div class="row">
                        <div class="col-9">
                            <h6 class="mb-4">Instructor Attendance Logs</h6>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success mt-3">
                            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="get" class="row mb-3">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        <div class="col-lg-3">
                            <label>Date:</label>
                            <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-lg-3">
                            <label>Instructor:</label>
                            <input type="text" class="form-control" name="search_instructor" placeholder="Search instructor" value="<?php echo htmlspecialchars($search_instructor); ?>">
                        </div>
                        <div class="col-lg-3 mt-4">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                            <a href="student_logs.php" class="btn btn-warning"><i class="fa fa-sync"></i> Reset</a>
                        </div>
                    </form>
                    
                    <ul class="nav nav-pills mb-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($view == 'current') ? 'active' : ''; ?>" href="student_logs.php?view=current">Current Logs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($view == 'archived') ? 'active' : ''; ?>" href="student_logs.php?view=archived">Archived Logs</a>
                        </li>
                    </ul>
                    
                    <div class="table-responsive">
                        <?php if ($recent_archives): ?>
                            <div class="alert alert-success mb-3">
                                <i class="fa fa-check-circle"></i> Showing archived records for <?php echo date('F d, Y', strtotime($selected_date)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h6>
                            Instructor Attendance for: <span class="text-primary"><?php echo date('F d, Y', strtotime($selected_date)); ?></span>
                        </h6>
                        
                        <!-- Instructor Attendance Table -->
                        <table class="table table-striped">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Status</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build the query to fetch all necessary fields including department and location
                                $query = "SELECT l.*, i.fullname as instructor_name,
                                        CASE WHEN l.time_out IS NOT NULL THEN 'Saved' ELSE 'Pending' END as save_status
                                        FROM $instructor_table l
                                        JOIN instructor i ON l.instructor_id = i.id
                                        WHERE DATE(l.time_in) = ?";
                                $params = [$selected_date];
                                $types = "s";
                                
                                if ($search_instructor !== '') {
                                    $query .= " AND i.fullname LIKE ?";
                                    $params[] = "%$search_instructor%";
                                    $types .= "s";
                                }
                                
                                $query .= " ORDER BY l.time_in DESC";
                                
                                $stmt = $db->prepare($query);
                                if ($stmt === false) {
                                    die("Error preparing query: " . $db->error);
                                }
                                
                                if (!empty($params)) {
                                    $bind_result = $stmt->bind_param($types, ...$params);
                                    if ($bind_result === false) {
                                        die("Error binding parameters: " . $stmt->error);
                                    }
                                }
                                
                                $execute_result = $stmt->execute();
                                if ($execute_result === false) {
                                    die("Error executing query: " . $stmt->error);
                                }
                                
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['id_number']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['instructor_name']) . '</td>';
                                        echo '<td>' . ($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'N/A') . '</td>';
                                        echo '<td>' . ($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'N/A') . '</td>';
                                        echo '<td>' . ($row['save_status'] == 'Saved' ? '<span class="badge bg-success">Saved</span>' : '<span class="badge bg-warning">Pending</span>') . '</td>';
                                        
                                        // Enhanced Department display with fallback
                                        echo '<td>';
                                        if (!empty($row['department'])) {
                                            echo '<span class="badge bg-primary">' . htmlspecialchars($row['department']) . '</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        echo '</td>';
                                        
                                        // Enhanced Location display with fallback
                                        echo '<td>';
                                        if (!empty($row['location'])) {
                                            echo '<span class="badge bg-info text-dark">' . htmlspecialchars($row['location']) . '</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        echo '</td>';
                                        
                                        echo '<td>' . date('m/d/Y', strtotime($row['time_in'])) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="8" class="text-center py-4">';
                                    echo '<div class="text-muted">';
                                    echo '<i class="fa fa-search fa-2x mb-2"></i><br>';
                                    echo 'No instructor attendance records found for ' . date('F d, Y', strtotime($selected_date));
                                    echo '</div>';
                                    echo '</td></tr>';
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>
     <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff"><i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i></a>
</div>
<script src="lib/chart/chart.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="lib/tempusdominus/js/moment.min.js"></script>
<script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

<!-- Template Javascript -->
<script src="js/main.js"></script>
<?php mysqli_close($db); ?>