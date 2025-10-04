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

    .location-badge {
        background-color: #e3f2fd;
        color: #1976d2;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }

    .department-badge {
        background-color: #f3e5f5;
        color: #7b1fa2;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
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
                        <div class="col-lg-2">
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
                        <div class="col-lg-2">
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
                        <div class="col-lg-2 mt-4">
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
                            <?php if ($view == 'archived'): ?>
                                <span class="badge bg-secondary ms-2">Archived</span>
                            <?php endif; ?>
                        </h6>
                        
                        <!-- Instructor Attendance Table -->
                        <table class="table table-striped">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Department</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build the main query with all filters
                                $query = "SELECT l.*, i.fullname as instructor_name,
                                         CASE 
                                            WHEN l.time_out IS NOT NULL THEN 'Saved' 
                                            ELSE 'Pending' 
                                         END as save_status,
                                         TIMEDIFF(l.time_out, l.time_in) as duration
                                         FROM $instructor_table l
                                         JOIN instructor i ON l.instructor_id = i.id
                                         WHERE DATE(l.time_in) = ?";
                                
                                $params = [$selected_date];
                                $types = "s";
                                
                                // Add instructor name filter
                                if ($search_instructor !== '') {
                                    $query .= " AND i.fullname LIKE ?";
                                    $params[] = "%$search_instructor%";
                                    $types .= "s";
                                }
                                
                                // Add department filter
                                if (isset($_GET['search_department']) && $_GET['search_department'] !== '') {
                                    $query .= " AND l.department = ?";
                                    $params[] = $_GET['search_department'];
                                    $types .= "s";
                                }
                                
                                // Add location filter
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
                                        echo '<td>'.htmlspecialchars($row['id_number']).'</td>';
                                        echo '<td>'.htmlspecialchars($row['instructor_name']).'</td>';
                                        echo '<td>'.($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
                                        echo '<td>'.($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
                                        
                                        // Display duration
                                        echo '<td>';
                                        if ($row['time_out'] && $row['duration']) {
                                            // Format duration to show hours and minutes only
                                            $duration_parts = explode(':', $row['duration']);
                                            if (count($duration_parts) >= 2) {
                                                echo $duration_parts[0] . 'h ' . $duration_parts[1] . 'm';
                                            } else {
                                                echo $row['duration'];
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        echo '</td>';
                                        
                                        echo '<td>';
                                        if ($row['save_status'] == 'Saved') {
                                            echo '<span class="badge bg-success">Saved</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        }
                                        echo '</td>';
                                        
                                        // Display department with badge
                                        echo '<td>';
                                        if (!empty($row['department'])) {
                                            echo '<span class="department-badge">'.htmlspecialchars($row['department']).'</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        echo '</td>';
                                        
                                        // Display location with badge
                                        echo '<td>';
                                        if (!empty($row['location'])) {
                                            echo '<span class="location-badge">'.htmlspecialchars($row['location']).'</span>';
                                        } else {
                                            echo '<span class="text-muted">N/A</span>';
                                        }
                                        echo '</td>';
                                        
                                        echo '<td>'.date('m/d/Y', strtotime($row['time_in'])).'</td>';
                                        
                                        // Actions column
                                        echo '<td>';
                                        echo '<div class="btn-group btn-group-sm">';
                                        echo '<button class="btn btn-info btn-sm view-details" data-bs-toggle="tooltip" title="View Details" data-id="'.$row['id'].'">';
                                        echo '<i class="fa fa-eye"></i>';
                                        echo '</button>';
                                        if ($view == 'current' && empty($row['time_out'])) {
                                            echo '<button class="btn btn-warning btn-sm force-timeout" data-bs-toggle="tooltip" title="Force Time Out" data-id="'.$row['id'].'">';
                                            echo '<i class="fa fa-clock"></i>';
                                            echo '</button>';
                                        }
                                        echo '</div>';
                                        echo '</td>';
                                        
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="10" class="text-center py-4">';
                                    echo '<div class="text-muted">';
                                    echo '<i class="fa fa-search fa-2x mb-3"></i><br>';
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

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Instructor Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Details will be loaded here via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // View details functionality
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const recordId = this.getAttribute('data-id');
            const viewType = '<?php echo $view; ?>';
            
            fetch('get_instructor_details.php?id=' + recordId + '&view=' + viewType)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('detailsContent').innerHTML = data;
                    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading details');
                });
        });
    });

    // Force time out functionality
    document.querySelectorAll('.force-timeout').forEach(button => {
        button.addEventListener('click', function() {
            const recordId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to force time out for this instructor?')) {
                fetch('force_timeout.php?id=' + recordId, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Time out recorded successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error recording time out');
                });
            }
        });
    });
});
</script>
<?php mysqli_close($db); ?>