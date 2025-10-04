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

// DEBUG: Check if archived table has department and location data
if ($view == 'archived') {
    $debug_query = "SELECT 
                   COUNT(*) as total,
                   SUM(CASE WHEN department IS NOT NULL AND department != '' THEN 1 ELSE 0 END) as has_department,
                   SUM(CASE WHEN location IS NOT NULL AND location != '' THEN 1 ELSE 0 END) as has_location
                   FROM archived_instructor_logs 
                   WHERE DATE(time_in) = ?";
    $debug_stmt = $db->prepare($debug_query);
    if ($debug_stmt) {
        $debug_stmt->bind_param("s", $selected_date);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        $debug_data = $debug_result->fetch_assoc();
        error_log("Archived data check - Total: " . $debug_data['total'] . 
                 ", Has Department: " . $debug_data['has_department'] . 
                 ", Has Location: " . $debug_data['has_location']);
        $debug_stmt->close();
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

    .department-badge {
        background-color: #e3f2fd;
        color: #1976d2;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        border: 1px solid #bbdefb;
    }

    .location-badge {
        background-color: #f3e5f5;
        color: #7b1fa2;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        border: 1px solid #e1bee7;
    }

    .table-responsive {
        max-height: 70vh;
        overflow-y: auto;
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
                        <div class="col-lg-3">
                            <label>Department:</label>
                            <select class="form-control" name="search_department">
                                <option value="">All Departments</option>
                                <?php
                                $dept_query = "SELECT DISTINCT department FROM $instructor_table WHERE department IS NOT NULL AND department != '' ORDER BY department";
                                $dept_result = $db->query($dept_query);
                                if ($dept_result && $dept_result->num_rows > 0) {
                                    while ($dept_row = $dept_result->fetch_assoc()) {
                                        $selected = (isset($_GET['search_department']) && $_GET['search_department'] == $dept_row['department']) ? 'selected' : '';
                                        echo '<option value="'.htmlspecialchars($dept_row['department']).'" '.$selected.'>'.htmlspecialchars($dept_row['department']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label>Location:</label>
                            <select class="form-control" name="search_location">
                                <option value="">All Locations</option>
                                <?php
                                $loc_query = "SELECT DISTINCT location FROM $instructor_table WHERE location IS NOT NULL AND location != '' ORDER BY location";
                                $loc_result = $db->query($loc_query);
                                if ($loc_result && $loc_result->num_rows > 0) {
                                    while ($loc_row = $loc_result->fetch_assoc()) {
                                        $selected = (isset($_GET['search_location']) && $_GET['search_location'] == $loc_row['location']) ? 'selected' : '';
                                        echo '<option value="'.htmlspecialchars($loc_row['location']).'" '.$selected.'>'.htmlspecialchars($loc_row['location']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-12 mt-3">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                            <a href="student_logs.php?view=<?php echo $view; ?>" class="btn btn-warning"><i class="fa fa-sync"></i> Reset</a>
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
                            <?php if ($view == 'archived'): ?>
                                <span class="badge bg-secondary ms-2">Archived</span>
                            <?php endif; ?>
                        </h6>
                        
                        <!-- Instructor Attendance Table -->
                        <table class="table table-striped table-hover">
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
                                // Build query with all filters
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
                                
                                if (isset($_GET['search_department']) && $_GET['search_department'] !== '') {
                                    $query .= " AND l.department = ?";
                                    $params[] = $_GET['search_department'];
                                    $types .= "s";
                                }
                                
                                if (isset($_GET['search_location']) && $_GET['search_location'] !== '') {
                                    $query .= " AND l.location = ?";
                                    $params[] = $_GET['search_location'];
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
                                        
                                        // Display Department
                                        echo '<td>';
                                        if (!empty($row['department'])) {
                                            echo '<span class="department-badge">' . htmlspecialchars($row['department']) . '</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        echo '</td>';
                                        
                                        // Display Location
                                        echo '<td>';
                                        if (!empty($row['location'])) {
                                            echo '<span class="location-badge">' . htmlspecialchars($row['location']) . '</span>';
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
                                    echo 'No instructor attendance records found for the selected criteria.';
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
</div>

<?php mysqli_close($db); ?>