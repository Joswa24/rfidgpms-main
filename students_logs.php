<?php
// Include enhanced handler
include 'attendance_functions.php';
$enhancedHandler = new EnhancedAttendanceHandler($db);

// Get enhanced statistics
$enhanced_stats = [];
if ($first_student_section && $first_student_year && isset($_SESSION['access']['instructor']['id'])) {
    $enhanced_stats = $enhancedHandler->getEnhancedClassStats(
        $first_student_year, 
        $first_student_section, 
        $_SESSION['access']['instructor']['id']
    );
}

// Get enhanced attendance report
$enhanced_report = [];
if (isset($_SESSION['access']['instructor']['id'])) {
    $enhanced_report = $enhancedHandler->getEnhancedAttendanceReport($_SESSION['access']['instructor']['id']);
}
// Function to display classmates table
function displayClassmatesTable($classmates, $year, $section) {
    if (empty($classmates)) {
        echo '<div class="alert alert-info mt-4">No classmates found for ' . htmlspecialchars($year) . ' - ' . htmlspecialchars($section) . '</div>';
        return;
    }
    
    echo '<h5 class="mt-4">Class List (' . htmlspecialchars($year) . ' - ' . htmlspecialchars($section) . ')</h5>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead class="table-dark">';
    echo '<tr>';
    echo '<th>ID Number</th>';
    echo '<th>Name</th>';
    echo '<th>Section</th>';
    echo '<th>Year</th>';
    echo '<th>Department</th>';
    echo '<th>Status</th>';
    echo '<th>Last Scan Time</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($classmates as $student) {
        $status_badge = $student['attendance_count'] > 0 ? 
            '<span class="badge bg-success">Present</span>' : 
            '<span class="badge bg-danger">Absent</span>';
        
        $last_scan = $student['last_time_in'] ? 
            date('h:i A', strtotime($student['last_time_in'])) : 
            'Not scanned';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['id_number']) . '</td>';
        echo '<td>' . htmlspecialchars($student['fullname']) . '</td>';
        echo '<td>' . htmlspecialchars($student['section']) . '</td>';
        echo '<td>' . htmlspecialchars($student['year']) . '</td>';
        echo '<td>' . htmlspecialchars($student['department_name']) . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . $last_scan . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Function to get first student details
function getFirstStudentDetails($db) {
    $query = "SELECT s.year, s.section 
              FROM attendance_logs al
              JOIN students s ON al.student_id = s.id
              WHERE DATE(al.time_in) = CURDATE()
              ORDER BY al.time_in ASC
              LIMIT 1";
    
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Enhanced function to get classmates with better performance
function getClassmatesByYearSection($db, $year, $section) {
    $query = "SELECT 
                s.id_number, 
                s.fullname, 
                s.section, 
                s.year, 
                d.department_name,
                s.photo,
                (SELECT COUNT(*) FROM attendance_logs al 
                 WHERE al.student_id = s.id 
                 AND DATE(al.time_in) = CURDATE()
                 AND al.instructor_id = ?) as attendance_count,
                (SELECT time_in FROM attendance_logs al 
                 WHERE al.student_id = s.id 
                 AND DATE(al.time_in) = CURDATE()
                 AND al.instructor_id = ?
                 ORDER BY al.time_in DESC LIMIT 1) as last_time_in
              FROM students s
              LEFT JOIN department d ON s.department_id = d.department_id
              WHERE s.section = ? AND s.year = ?
              ORDER BY s.fullname";
    
    $stmt = $db->prepare($query);
    $instructor_id = $_SESSION['access']['instructor']['id'] ?? null;
    
    if ($stmt) {
        $stmt->bind_param("iiss", $instructor_id, $instructor_id, $section, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $classmates = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $classmates;
    }
    
    return [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/grow_up.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Attendance Log</title>
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    <style>
        /* Enhanced Statistics Styles */
        .stats-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            margin-bottom: 0;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .stats-card .card-body {
            padding: 1.25rem;
        }

        .stats-icon {
            font-size: 2.2rem;
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1;
        }

        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stats-detail {
            font-size: 0.8rem;
            color: #495057;
            margin-top: 8px;
        }

        .attendance-progress {
            height: 6px;
            margin-top: 12px;
            border-radius: 3px;
            background-color: #e9ecef;
        }

        .attendance-progress .progress-bar {
            border-radius: 3px;
        }

        /* Color coding for different status levels */
        .text-success { color: #28a745 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-info { color: #17a2b8 !important; }
        .text-primary { color: #007bff !important; }

        .bg-success { background-color: #28a745 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        .bg-info { background-color: #17a2b8 !important; }
        .bg-primary { background-color: #007bff !important; }

        /* Enhanced table styles */
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                font-size: 1.8rem;
            }
            
            .stats-card .card-body {
                padding: 1rem;
            }
            
            .instructor-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
        }
        .active-tab {
            font-weight: bold;
            border-bottom: 3px solid #084298;
        }
        .nav-tabs .nav-link {
            color: #084298;
        }
        .nav-tabs .nav-link.active {
            color: #084298;
            font-weight: bold;
        }
        .action-buttons {
            margin-bottom: 20px;
        }
        .instructor-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .instructor-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .instructor-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-right: 5px;
        }
        .detail-value {
            color: #212529;
        }
        .table-header-row {
            position: sticky;
            top: 0;
            background: white;
            z-index: 99;
        }
        .timeout-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: #084298;
            text-align: center;
            margin: 20px 0;
        }
        .archived-message {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .badge {
            font-size: 0.85em;
        }
        .btn-primary {
            background-color: #87abe0;
            border-color: #87abe0;
        }
        .btn-primary:hover {
            background-color: #6c96d4;
            border-color: #6c96d4;
        }
        .classmates-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        /* Enhanced Statistics Styles */
        .stats-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card .card-body {
            padding: 1.5rem;
        }
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stats-detail {
            font-size: 0.8rem;
            color: #495057;
            margin-top: 10px;
        }
        .attendance-progress {
            height: 8px;
            margin-top: 10px;
        }
        .time-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .time-stats .stats-detail {
            color: rgba(255,255,255,0.8);
        }
        
        @media (max-width: 768px) {
            .instructor-info {
                flex-direction: column;
                align-items: flex-start;
            }
            .instructor-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .table-container {
                overflow-x: auto;
            }
            table {
                font-size: 0.9rem;
            }
            .stats-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<img src="uploads/Head.png" style="width: 100%; height: 150px; margin-left: 10px; padding=10px; margin-top=20px;S">

<div class="container mt-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" href="main1.php">Scanner</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="#">Attendance Log</a>
        </li>
    </ul>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success mt-3">
            <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['scanner_error'])): ?>
        <div class="alert alert-warning mt-3">
            <?php echo htmlspecialchars($_SESSION['scanner_error']); unset($_SESSION['scanner_error']); ?>
        </div>
    <?php endif; ?>

    <div class="tab-content mt-3">
        <!-- Student Attendance Tab -->
        <div class="tab-pane fade show active" id="pills-students">
            <div class="action-buttons">
                <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#idModal">
                    <i class="fas fa-save me-1"></i> Save Today's Attendance
                </button>
            </div>

            <?php if ($attendance_saved): ?>
                <div class="archived-message">
                    <h4>Attendance Records Archived</h4>
                    <p><?php echo htmlspecialchars($archive_message); ?></p>
                    <p>Your time-out was recorded at <strong><?php echo htmlspecialchars($timeout_time); ?></strong></p>
                    <p class="text-success"><i class="fas fa-check-circle me-2"></i>Classmates data has been saved to your instructor panel.</p>
                </div>
            <?php else: ?>
                <!-- Enhanced Statistics Section -->
                <?php if (!empty($enhanced_stats) && $first_student_section && $first_student_year): ?>
                <div class="class-summary mb-4">
                    <h4 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Class Analytics Dashboard</h4>
                    
                    <!-- Main Statistics Cards -->
                    <div class="row g-3 mb-4">
                        <!-- Overall Attendance -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-primary">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-primary">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                    <div class="stats-number text-primary">
                                        <?php echo $enhanced_stats['attendance_rate'] ?? 0; ?>%
                                    </div>
                                    <div class="stats-label">Attendance Rate</div>
                                    <div class="progress attendance-progress">
                                        <div class="progress-bar bg-primary" 
                                            style="width: <?php echo $enhanced_stats['attendance_rate'] ?? 0; ?>%">
                                        </div>
                                    </div>
                                    <div class="stats-detail small mt-2">
                                        <?php echo $enhanced_stats['present']['present_count'] ?? 0; ?> of <?php echo $enhanced_stats['total']['total_count'] ?? 0; ?> students
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Present Students -->
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stats-card border-success">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stats-number text-success">
                                        <?php echo $enhanced_stats['present']['present_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Present</div>
                                    <div class="stats-detail small">
                                        <?php echo $enhanced_stats['on_time_count'] ?? 0; ?> on time
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Late Students -->
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stats-card border-warning">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-number text-warning">
                                        <?php echo $enhanced_stats['late_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Late</div>
                                    <div class="stats-detail small">
                                        Punctuality: <?php echo $enhanced_stats['punctuality_rate'] ?? 0; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Absent Students -->
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="card stats-card border-danger">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-danger">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <div class="stats-number text-danger">
                                        <?php echo $enhanced_stats['absent_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Absent</div>
                                    <div class="stats-detail small">
                                        <?php 
                                        $absent_rate = $enhanced_stats['total']['total_count'] > 0 ? 
                                            round(($enhanced_stats['absent_count'] / $enhanced_stats['total']['total_count']) * 100, 1) : 0;
                                        echo $absent_rate . '% of class';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Time Statistics -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card border-info">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-info">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stats-number text-info" style="font-size: 1.5rem;">
                                        <?php 
                                        if (!empty($enhanced_stats['present']['earliest_time'])) {
                                            echo date('g:i A', strtotime($enhanced_stats['present']['earliest_time']));
                                        } else {
                                            echo '--:--';
                                        }
                                        ?>
                                    </div>
                                    <div class="stats-label">First Scan</div>
                                    <div class="stats-detail small">
                                        <?php 
                                        if (!empty($enhanced_stats['present']['latest_time'])) {
                                            echo 'Last: ' . date('g:i A', strtotime($enhanced_stats['present']['latest_time']));
                                        } else {
                                            echo 'No scans yet';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Session Information Row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body py-3">
                                    <div class="row text-center">
                                        <div class="col-md-3 border-end">
                                            <small class="text-muted d-block">Class Size</small>
                                            <strong class="text-dark"><?php echo $enhanced_stats['total']['total_count'] ?? 0; ?> Students</strong>
                                        </div>
                                        <div class="col-md-3 border-end">
                                            <small class="text-muted d-block">Current Session</small>
                                            <strong class="text-info">
                                                <?php 
                                                $room = $_SESSION['access']['room']['room'] ?? '';
                                                echo (stripos($room, 'lab') !== false) ? 'Laboratory' : 'Lecture';
                                                ?>
                                            </strong>
                                        </div>
                                        <div class="col-md-3 border-end">
                                            <small class="text-muted d-block">Academic Period</small>
                                            <strong class="text-success">
                                                <?php 
                                                $month = date('n');
                                                if ($month >= 1 && $month <= 5) echo '2nd Semester';
                                                elseif ($month >= 8 && $month <= 12) echo '1st Semester';
                                                else echo 'Summer';
                                                ?> • <?php echo date('Y'); ?>
                                            </strong>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block">Last Updated</small>
                                            <strong class="text-primary" id="currentTime">
                                                <?php echo date('g:i A'); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($first_student_section && $first_student_year): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-chart-bar me-2"></i>
                    Analytics dashboard will appear when students start scanning their IDs.
                </div>
                <?php endif; ?>

                <div class="instructor-header">
                    <div class="instructor-info">
                        <div class="instructor-details">
                            <div>
                                <span class="detail-label">Instructor:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['instructor']['fullname'] ?? 'N/A'); ?></span>
                            </div>
                            
                            <?php if (!empty($_SESSION['access']['subject']['name'])): ?>
                            <div>
                                <span class="detail-label">Subject:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['subject']['name']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_SESSION['access']['subject']['time'])): ?>
                            <div>
                                <span class="detail-label">Time:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['subject']['time']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($first_student_section && $first_student_year): ?>
                            <div>
                                <span class="detail-label">Class:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($first_student_year . ' - ' . $first_student_section); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="logout.php" class="btn btn-sm btn-outline-danger">
                            <i class="bx bx-power-off me-1"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Attendance Records -->
                <h5 class="mt-3"><i class="fas fa-list me-2"></i>Attendance Records</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Year</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Use prepared statements to prevent SQL injection
                            $attendance_query = "SELECT l.*, s.fullname, s.section, s.year 
                                               FROM attendance_logs l
                                               JOIN students s ON l.student_id = s.id";
                            
                            if ($first_student_section && $first_student_year) {
                                $attendance_query .= " WHERE s.section = ? AND s.year = ?";
                                $stmt = $db->prepare($attendance_query);
                                $stmt->bind_param("ss", $first_student_section, $first_student_year);
                                $stmt->execute();
                                $attendance_result = $stmt->get_result();
                            } else {
                                $attendance_result = $db->query($attendance_query);
                            }
                            
                            if ($attendance_result && $attendance_result->num_rows > 0) {
                                while ($row = $attendance_result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>'.htmlspecialchars($row['id_number']).'</td>';
                                    echo '<td>'.htmlspecialchars($row['fullname']).'</td>';
                                    echo '<td>'.htmlspecialchars($row['section']).'</td>';
                                    echo '<td>'.htmlspecialchars($row['year']).'</td>';
                                    echo '<td>'.($row['time_in'] ? date('m/d/Y h:i A', strtotime($row['time_in'])) : 'N/A').'</td>';
                                    echo '<td>'.($row['time_out'] ? date('m/d/Y h:i A', strtotime($row['time_out'])) : 'N/A').'</td>';
                                    echo '<td>'.(!empty($row['status']) ? 
                                        '<span class="badge bg-success">Saved</span>' : 
                                        '<span class="badge bg-success">Present</span>').'</td>';
                                    echo '</tr>';
                                }
                                
                                if (isset($stmt)) {
                                    $stmt->close();
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center py-4">No attendance records found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Classmates Section -->
                <?php if ($first_student_section && $first_student_year): ?>
                    <div class="classmates-section">
                        <?php if (!empty($_SESSION['access']['subject']['name'])): ?>
                            <div>
                                <span class="detail-label">Subject:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['subject']['name']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($_SESSION['access']['subject']['time'])): ?>
                            <div>
                                <span class="detail-label">Time:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['subject']['time']); ?></span>
                            </div>
                            <?php endif; ?>
                        <?php
                        // Get classmates
                        $classmates = getClassmatesByYearSection($db, $first_student_year, $first_student_section);
                        // Display classmates table
                        displayClassmatesTable($classmates, $first_student_year, $first_student_section);
                        ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        No class filter applied. Classmates will be displayed when the first student scans their ID.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Verification Modal -->
    <div class="modal fade" id="idModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Instructor Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <h5>Verifying: <?php echo htmlspecialchars($_SESSION['access']['instructor']['fullname'] ?? 'Instructor'); ?></h5>
                        <p class="text-muted">Scan your ID barcode or enter manually</p>
                    </div>
                    <form id="verifyForm" method="post">
                        <div class="mb-3">
                            <label for="idInput" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="idInput" name="id_number" 
                                placeholder="Scan your ID barcode" required autofocus
                                data-scanner-input="true">
                            <div class="form-text">Position cursor in field and scan your ID</div>
                            <input type="hidden" name="save_attendance" value="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="verifyForm" class="btn btn-primary">Verify</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update current time every minute
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Initial update
        updateCurrentTime();
        // Update every minute
        setInterval(updateCurrentTime, 60000);

        // Handle logout confirmation
        document.querySelector('.btn-outline-danger').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to log out? This will clear today\'s attendance records.')) {
                e.preventDefault();
            }
        });

        // Scanner functionality for the modal
        const idModal = document.getElementById('idModal');
        const idInput = document.getElementById('idInput');
        
        if (idModal && idInput) {
            let scanBuffer = '';
            let scanTimer;
            
            idModal.addEventListener('shown.bs.modal', function() {
                idInput.focus();
                scanBuffer = '';
                clearTimeout(scanTimer);
            });
            
            function formatIdNumber(id) {
                const cleaned = id.replace(/\D/g, '');
                if (cleaned.length >= 8) {
                    return cleaned.substring(0, 4) + '-' + cleaned.substring(4, 8);
                }
                return cleaned;
            }
            
            idModal.addEventListener('keypress', function(e) {
                if (document.activeElement === idInput) {
                    clearTimeout(scanTimer);
                    scanBuffer += e.key;
                    
                    scanTimer = setTimeout(function() {
                        if (scanBuffer.length >= 8) {
                            const formatted = formatIdNumber(scanBuffer);
                            idInput.value = formatted;
                            confirmAttendanceSave();
                        }
                        scanBuffer = '';
                    }, 100);
                }
            });
            
            function confirmAttendanceSave() {
                Swal.fire({
                    title: 'Confirm Save Attendance',
                    text: 'This will record your time-out and save classmates data to your instructor panel. Continue?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, save it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('verifyForm').submit();
                    } else {
                        idInput.value = '';
                        idInput.focus();
                    }
                });
            }

            const verifyForm = document.getElementById('verifyForm');
            if (verifyForm) {
                verifyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    confirmAttendanceSave();
                });
            }
        }

        <?php if ($show_timeout_message): ?>
            // Show success message if attendance was saved
            Swal.fire({
                icon: 'success',
                title: 'Attendance Saved',
                html: `<div class="text-center">
                          <h5>Your time-out has been recorded</h5>
                          <div class="timeout-display"><?php echo $timeout_time; ?></div>
                          <p><?php echo $archive_message; ?></p>
                          <p class="text-success"><i class="fas fa-check-circle me-2"></i>Classmates data has been saved to your instructor panel.</p>
                       </div>`,
                confirmButtonText: 'OK',
                allowOutsideClick: false
            });
        <?php endif; ?>
    });
    // Handle Save Attendance action - FIXED ARCHIVING PROCESS
        if (isset($_POST['save_attendance']) && isset($_POST['id_number'])) {
            $instructor_id = $_SESSION['access']['instructor']['id'];
            $currentDate = date('Y-m-d');
            
            // Verify ID matches logged-in instructor
            if ($_POST['id_number'] != $_SESSION['access']['instructor']['id_number']) {
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode(['success' => false, 'message' => 'ID verification failed']);
                    exit();
                }
                $_SESSION['scanner_error'] = "ID verification failed";
                header("Location: students_logs.php");
                exit();
            }

            try {
                $db->begin_transaction();

                // NEW: Save classmates data using enhanced handler
                if ($first_student_section && $first_student_year) {
                    $classmates = getClassmatesByYearSection($db, $first_student_year, $first_student_section);
                    $enhancedHandler->saveEnhancedAttendance($db, $classmates, $instructor_id, $first_student_year, $first_student_section);
                }

                // 1. Record time-out for instructor
                $update_instructor = $db->prepare("UPDATE instructor_logs 
                                                SET time_out = NOW(), 
                                                    status = 'saved' 
                                                WHERE instructor_id = ? 
                                                AND DATE(time_in) = ? 
                                                AND time_out IS NULL");
                if (!$update_instructor) {
                    throw new Exception("Error preparing instructor update: " . $db->error);
                }
                $update_instructor->bind_param("is", $instructor_id, $currentDate);
                if (!$update_instructor->execute()) {
                    throw new Exception("Error executing instructor update: " . $update_instructor->error);
                }

                // 2. Mark all student records as saved
                $update_students = $db->prepare("UPDATE attendance_logs 
                                            SET status = 'saved'
                                            WHERE instructor_id = ?
                                            AND DATE(time_in) = ?");
                if (!$update_students) {
                    throw new Exception("Error preparing student update: " . $db->error);
                }
                $update_students->bind_param("is", $instructor_id, $currentDate);
                if (!$update_students->execute()) {
                    throw new Exception("Error executing student update: " . $update_students->error);
                }

                // 3. Archive student logs
                $db->query("CREATE TABLE IF NOT EXISTS archived_attendance_logs LIKE attendance_logs");
                $archive_result = $db->query("INSERT INTO archived_attendance_logs 
                                            SELECT * FROM attendance_logs 
                                            WHERE DATE(time_in) = CURDATE()");
                
                if (!$archive_result) {
                    throw new Exception("Error archiving student data: " . $db->error);
                }

                // 4. Archive instructor logs
                $db->query("CREATE TABLE IF NOT EXISTS archived_instructor_logs LIKE instructor_logs");
                $instructor_archive_result = $db->query("INSERT INTO archived_instructor_logs 
                                                    SELECT * FROM instructor_logs 
                                                    WHERE DATE(time_in) = CURDATE()");
                
                if (!$instructor_archive_result) {
                    throw new Exception("Error archiving instructor data: " . $db->error);
                }

                // 5. Clear current logs ONLY after successful archiving
                $delete_students = $db->query("DELETE FROM attendance_logs WHERE DATE(time_in) = CURDATE()");
                if (!$delete_students) {
                    throw new Exception("Error clearing student data: " . $db->error);
                }
                
                $delete_instructors = $db->query("DELETE FROM instructor_logs WHERE DATE(time_in) = CURDATE()");
                if (!$delete_instructors) {
                    throw new Exception("Error clearing instructor data: " . $db->error);
                }

                // 6. Get the exact time-out time
                $time_query = "SELECT time_out FROM archived_instructor_logs 
                            WHERE instructor_id = ? 
                            AND DATE(time_in) = ? 
                            ORDER BY time_out DESC LIMIT 1";
                $time_stmt = $db->prepare($time_query);
                if (!$time_stmt) {
                    throw new Exception("Error preparing time query: " . $db->error);
                }
                $time_stmt->bind_param("is", $instructor_id, $currentDate);
                if (!$time_stmt->execute()) {
                    throw new Exception("Error executing time query: " . $time_stmt->error);
                }
                $time_result = $time_stmt->get_result();
                $time_row = $time_result->fetch_assoc();
                $exact_time_out = $time_row['time_out'] ?? date('Y-m-d H:i:s');

                $db->commit();

                // Return success response
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode([
                        'success' => true,
                        'timeout_time' => date('h:i A', strtotime($exact_time_out)),
                        'message' => 'Attendance saved and archived successfully'
                    ]);
                    exit();
                }
                
                $_SESSION['timeout_time'] = date('h:i A', strtotime($exact_time_out));
                $_SESSION['attendance_saved'] = true;
                $_SESSION['archive_message'] = 'Attendance saved and archived successfully';
                header("Location: students_logs.php");
                exit();

            } catch (Exception $e) {
                $db->rollback();
                error_log("Attendance save error: " . $e->getMessage());
                
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error saving attendance: ' . $e->getMessage()
                    ]);
                    exit();
                }
                
                $_SESSION['scanner_error'] = "Error saving attendance: " . $e->getMessage();
                header("Location: students_logs.php");
                exit();
            }
        }
</script>
</body>
</html>
<?php 
if (isset($db)) {
    mysqli_close($db);
}