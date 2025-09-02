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
$student_table = ($view == 'archived') ? 'archived_attendance_logs' : 'attendance_logs';
$instructor_table = ($view == 'archived') ? 'archived_instructor_logs' : 'instructor_logs';

// Date filter logic
if (isset($_GET['date']) && $_GET['date'] !== '') {
    $selected_date = $_GET['date'];
} else {
    $selected_date = date('Y-m-d');
}

// Add year, section, and instructor filter logic
$search_year = isset($_GET['search_year']) ? trim($_GET['search_year']) : '';
$search_section = isset($_GET['search_section']) ? trim($_GET['search_section']) : '';
$search_instructor = isset($_GET['search_instructor']) ? trim($_GET['search_instructor']) : '';
$log_type = isset($_GET['log_type']) ? $_GET['log_type'] : 'students';

// Get instructor name for the header
$instructor_header = '';
$instructor_query = "SELECT i.fullname 
                    FROM $student_table l
                    JOIN instructor i ON l.instructor_id = i.id
                    WHERE DATE(l.time_in) = ?
                    GROUP BY l.instructor_id
                    ORDER BY COUNT(*) DESC
                    LIMIT 1";
$instructor_stmt = $db->prepare($instructor_query);
if ($instructor_stmt) {
    $instructor_stmt->bind_param("s", $selected_date);
    $instructor_stmt->execute();
    $instructor_result = $instructor_stmt->get_result();
    if ($instructor_result->num_rows > 0) {
        $instructor_row = $instructor_result->fetch_assoc();
        $instructor_header = $instructor_row['fullname'];
    }
    $instructor_stmt->close();
}

// Check for recently archived records
$recent_archives = false;
if ($view == 'archived') {
    $recent_query = "SELECT COUNT(*) as count FROM archived_attendance_logs 
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
                    <h6 class="mb-4">Attendance Logs</h6>
                </div>
            </div>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success mt-3">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            
            
            <form method="get" class="row mb-3">
                <!-- Rest of your filter form remains the same -->
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <div class="col-lg-2">
                    <label>Date:</label>
                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($selected_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-lg-2">
                    <label>Year:</label>
                    <input type="text" class="form-control" name="search_year" placeholder="e.g. 1st" value="<?php echo htmlspecialchars($search_year); ?>">
                </div>
                <div class="col-lg-2">
                    <label>Section:</label>
                    <input type="text" class="form-control" name="search_section" placeholder="e.g. A" value="<?php echo htmlspecialchars($search_section); ?>">
                </div>
                <div class="col-lg-2">
                    <label>Instructor:</label>
                    <input type="text" class="form-control" name="search_instructor" placeholder="Search instructor" value="<?php echo htmlspecialchars($search_instructor); ?>">
                </div>
                <div class="col-lg-2">
                    <label>Log Type:</label>
                    <select class="form-select" name="log_type">
                        <option value="students" <?php echo ($log_type == 'students') ? 'selected' : ''; ?>>Students</option>
                        <option value="instructors" <?php echo ($log_type == 'instructors') ? 'selected' : ''; ?>>Instructors</option>
                    </select>
                </div>
                <div class="col-lg-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="student_logs.php" class="btn btn-warning"><i class="fa fa-sync"></i> Reset</a>
                </div>
            </form>
                    <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($view == 'current') ? 'active' : ''; ?>" href="student_logs.php?view=current&log_type=<?php echo $log_type; ?>">Current Logs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($view == 'archived') ? 'active' : ''; ?>" href="student_logs.php?view=archived&log_type=<?php echo $log_type; ?>">Archived Logs</a>
                </li>
            </ul>
                    <div class="table-responsive">
                       
                        
                        <?php if ($recent_archives): ?>
                            <div class="alert alert-success mb-3">
                                <i class="fa fa-check-circle"></i> Showing archived records for <?php echo date('F d, Y', strtotime($selected_date)); ?>
                            </div>
                        <?php endif; ?>
                        
                       <h6>
    Attendance for: <span class="text-primary"><?php echo date('F d, Y', strtotime($selected_date)); ?></span>
    <?php if (!empty($instructor_header) && $log_type == 'students'): ?>
        | Instructor: <span class="text-success"><?php echo htmlspecialchars($instructor_header); ?></span>
        
        <?php 
        // Get subject and time information for the instructor
        $subject_info = $db->query("SELECT s.name as subject_name, s.time as subject_time 
                                  FROM $student_table l
                                  JOIN subjects s ON l.subject_id = s.id
                                  JOIN instructor i ON l.instructor_id = i.id
                                  WHERE DATE(l.time_in) = '$selected_date' 
                                  AND i.fullname = '".$db->real_escape_string($instructor_header)."'
                                  LIMIT 1");
        
        if ($subject_info && $subject_info->num_rows > 0) {
            $subject_row = $subject_info->fetch_assoc();
            if (!empty($subject_row['subject_name'])) {
                echo '| Subject: <span class="text-info">'.htmlspecialchars($subject_row['subject_name']).'</span>';
            }
            if (!empty($subject_row['subject_time'])) {
                echo '| Time: <span class="text-warning">'.htmlspecialchars($subject_row['subject_time']).'</span>';
            }
        }
        ?>
    <?php endif; ?>
</h6>
                        
                        <?php if ($log_type == 'students'): ?>
                        <!-- Student Attendance Table -->
                        <table class="table table-striped">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>ID Number</th>
                                    <th>Name</th>
                                    <th>Section</th>
                                    <th>Year</th>
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
                                $query = "SELECT l.*, s.fullname as student_name, s.section, s.year, 
                                          i.fullname as instructor_name, i.id as instructor_id,
                                          CASE WHEN l.time_out IS NOT NULL THEN 'Saved' ELSE 'Pending' END as save_status
                                          FROM $student_table l
                                          JOIN students s ON l.student_id = s.id
                                          LEFT JOIN instructor i ON l.instructor_id = i.id
                                          WHERE DATE(l.time_in) = ?";
                                $params = [$selected_date];
                                $types = "s";
                                
                                if ($search_year !== '') {
                                    $query .= " AND s.year LIKE ?";
                                    $params[] = "%$search_year%";
                                    $types .= "s";
                                }
                                if ($search_section !== '') {
                                    $query .= " AND s.section LIKE ?";
                                    $params[] = "%$search_section%";
                                    $types .= "s";
                                }
                                if ($search_instructor !== '') {
                                    $query .= " AND i.fullname LIKE ?";
                                    $params[] = "%$search_instructor%";
                                    $types .= "s";
                                }
                                
                                $query .= " ORDER BY s.year ASC, s.section ASC, l.time_in DESC";
                                
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

                                $lastYear = $lastSection = null;
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        if ($lastYear !== $row['year'] || $lastSection !== $row['section']) {
                                            $headerText = 'Year: ' . htmlspecialchars($row['year']) . ' | Section: ' . htmlspecialchars($row['section']);
                                            if (!empty($row['instructor_name'])) {
                                                $headerText .= ' | Instructor: ' . htmlspecialchars($row['instructor_name']);
                                            }
                                            echo '<tr class="table-primary"><td colspan="11">';
echo '<div class="instructor-info d-flex justify-content-between align-items-center w-100">';
echo '<div class="instructor-details d-flex flex-wrap align-items-center gap-3">';

// Year and Section
echo '<div class="instructor-year-item">';
echo '<span class="detail-label">Year:</span>';
echo '<span class="detail-value">' . htmlspecialchars($row['year']) . '</span>';
echo '</div>';

echo '<div class="instructor-section-item">';
echo '<span class="detail-label">Section:</span>';
echo '<span class="detail-value">' . htmlspecialchars($row['section']) . '</span>';
echo '</div>';

// Instructor info (if available)
if (!empty($row['instructor_name'])) {
    echo '<div class="instructor-name-item">';
    echo '<span class="detail-label">Instructor:</span>';
    echo '<span class="detail-value">' . htmlspecialchars($row['instructor_name']) . '</span>';
    echo '</div>';
}

// Subject info (if available)
if (!empty($row['subject_name'])) {
    echo '<div class="instructor-subject-item">';
    echo '<span class="detail-label">Subject:</span>';
    echo '<span class="detail-value">' . htmlspecialchars($row['subject_name']) . '</span>';
    echo '</div>';
}

// Time info (if available)
if (!empty($row['subject_time'])) {
    echo '<div class="instructor-time-item">';
    echo '<span class="detail-label">Time:</span>';
    echo '<span class="detail-value">' . htmlspecialchars($row['subject_time']) . '</span>';
    echo '</div>';
}

echo '</div>'; // Close instructor-details
echo '</div>'; // Close instructor-info
echo '</td></tr>';

$lastYear = $row['year'];
$lastSection = $row['section'];
                                        }
                                        echo '<tr>';
                                        echo '<td>'.$row['id_number'].'</td>';
                                        echo '<td>'.$row['student_name'].'</td>';
                                        echo '<td>'.$row['section'].'</td>';
                                        echo '<td>'.$row['year'].'</td>';
                                        echo '<td>'.($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
                                        echo '<td>'.($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
                                        echo '<td>'.($row['save_status'] == 'Saved' ? '<span class="badge bg-success">Saved</span>' : '<span class="badge bg-warning">Pending</span>').'</td>';
                                        echo '<td>'.$row['department'].'</td>';
                                        echo '<td>'.$row['location'].'</td>';
                                        echo '<td>'.date('m/d/Y', strtotime($row['time_in'])).'</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="11" class="text-center">No student attendance records found for this date</td></tr>';
                                }
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                        
                        <?php else: ?>
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
                                     // Display the student row
        echo '<tr>';
        echo '<td>'.$row['id_number'].'</td>';
        echo '<td>'.$row['student_name'].'</td>';
        echo '<td>'.$row['section'].'</td>';
        echo '<td>'.$row['year'].'</td>';
        echo '<td>'.($row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
        echo '<td>'.($row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
        echo '<td>'.($row['save_status'] == 'Saved' ? '<span class="badge bg-success">Saved</span>' : '<span class="badge bg-warning">Pending</span>').'</td>';
        echo '<td>'.$row['department'].'</td>';
        echo '<td>'.$row['location'].'</td>';
        echo '<td>'.date('m/d/Y', strtotime($row['time_in'])).'</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="11" class="text-center">No student attendance records found for this date</td></tr>';
}
                                $stmt->close();
                                ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    </div>
</div>
<?php mysqli_close($db); ?>