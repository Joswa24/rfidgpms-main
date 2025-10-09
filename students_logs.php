<?php
// Include the enhanced attendance handler
include 'attendance_functions.php';
$attendanceHandler = new AttendanceHandler($db);

// Get comprehensive attendance data
$attendance_data = $attendanceHandler->getStudentAttendance([
    'instructor_id' => $_SESSION['access']['instructor']['id'] ?? null,
    'section' => $first_student_section,
    'year' => $first_student_year,
    'date' => date('Y-m-d')
]);

// Get class summary
$class_summary = $attendanceHandler->getClassSummary(
    $_SESSION['access']['instructor']['id'] ?? null
);

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

// Get additional statistics for enhanced display
function getEnhancedClassStats($db, $year, $section, $instructor_id) {
    $stats = [];
    
    // Get present students count with time details
    $present_query = "SELECT 
                        COUNT(DISTINCT al.student_id) as present_count,
                        MIN(TIME(al.time_in)) as earliest_time,
                        MAX(TIME(al.time_in)) as latest_time,
                        AVG(TIME_TO_SEC(TIME(al.time_in))) as avg_time_sec
                     FROM attendance_logs al
                     JOIN students s ON al.student_id = s.id
                     WHERE s.section = ? 
                     AND s.year = ?
                     AND DATE(al.time_in) = CURDATE()
                     AND al.instructor_id = ?";
    
    $stmt = $db->prepare($present_query);
    if ($stmt) {
        $stmt->bind_param("ssi", $section, $year, $instructor_id);
        $stmt->execute();
        $stats['present'] = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Get total students in class
    $total_query = "SELECT COUNT(*) as total_count 
                   FROM students 
                   WHERE section = ? AND year = ?";
    
    $stmt = $db->prepare($total_query);
    if ($stmt) {
        $stmt->bind_param("ss", $section, $year);
        $stmt->execute();
        $stats['total'] = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Calculate absent count
    $stats['absent_count'] = $stats['total']['total_count'] - $stats['present']['present_count'];
    
    // Calculate attendance rate
    if ($stats['total']['total_count'] > 0) {
        $stats['attendance_rate'] = round(($stats['present']['present_count'] / $stats['total']['total_count']) * 100, 2);
    } else {
        $stats['attendance_rate'] = 0;
    }
    
    return $stats;
}

// Get enhanced stats if we have section and year
$enhanced_stats = [];
if ($first_student_section && $first_student_year && isset($_SESSION['access']['instructor']['id'])) {
    $enhanced_stats = getEnhancedClassStats($db, $first_student_year, $first_student_section, $_SESSION['access']['instructor']['id']);
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
                    <h4 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Class Attendance Summary</h4>
                    <div class="row g-3">
                        <!-- Total Students Card -->
                        <div class="col-md-3 col-6">
                            <div class="card stats-card border-primary">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-number text-primary">
                                        <?php echo $enhanced_stats['total']['total_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Total Students</div>
                                    <div class="stats-detail">
                                        <?php echo htmlspecialchars($first_student_year . ' - ' . $first_student_section); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Present Students Card -->
                        <div class="col-md-3 col-6">
                            <div class="card stats-card border-success">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-success">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stats-number text-success">
                                        <?php echo $enhanced_stats['present']['present_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Present Today</div>
                                    <div class="stats-detail">
                                        <?php 
                                        $present_rate = $enhanced_stats['total']['total_count'] > 0 ? 
                                            round(($enhanced_stats['present']['present_count'] / $enhanced_stats['total']['total_count']) * 100, 1) : 0;
                                        echo $present_rate . '% of class';
                                        ?>
                                    </div>
                                    <div class="progress attendance-progress">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo $present_rate; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Absent Students Card -->
                        <div class="col-md-3 col-6">
                            <div class="card stats-card border-danger">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-danger">
                                        <i class="fas fa-user-times"></i>
                                    </div>
                                    <div class="stats-number text-danger">
                                        <?php echo $enhanced_stats['absent_count'] ?? 0; ?>
                                    </div>
                                    <div class="stats-label">Absent Today</div>
                                    <div class="stats-detail">
                                        <?php 
                                        $absent_rate = $enhanced_stats['total']['total_count'] > 0 ? 
                                            round(($enhanced_stats['absent_count'] / $enhanced_stats['total']['total_count']) * 100, 1) : 0;
                                        echo $absent_rate . '% of class';
                                        ?>
                                    </div>
                                    <div class="progress attendance-progress">
                                        <div class="progress-bar bg-danger" 
                                             style="width: <?php echo $absent_rate; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Time Statistics Card -->
                        <div class="col-md-3 col-6">
                            <div class="card stats-card time-stats">
                                <div class="card-body text-center">
                                    <div class="stats-icon text-white">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-number text-white">
                                        <?php 
                                        if (!empty($enhanced_stats['present']['earliest_time'])) {
                                            echo date('g:i A', strtotime($enhanced_stats['present']['earliest_time']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </div>
                                    <div class="stats-label">First Scan</div>
                                    <div class="stats-detail">
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
                    
                    <!-- Additional Statistics Row -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4 border-end">
                                            <small class="text-muted">Attendance Rate</small>
                                            <h5 class="mb-0 <?php echo ($enhanced_stats['attendance_rate'] >= 80) ? 'text-success' : (($enhanced_stats['attendance_rate'] >= 60) ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $enhanced_stats['attendance_rate'] ?? 0; ?>%
                                            </h5>
                                        </div>
                                        <div class="col-md-4 border-end">
                                            <small class="text-muted">Class Session</small>
                                            <h5 class="mb-0 text-info">
                                                <?php 
                                                if (!empty($_SESSION['access']['subject']['time'])) {
                                                    echo htmlspecialchars($_SESSION['access']['subject']['time']);
                                                } else {
                                                    echo 'Not Set';
                                                }
                                                ?>
                                            </h5>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Current Time</small>
                                            <h5 class="mb-0 text-primary" id="currentTime">
                                                <?php echo date('g:i A'); ?>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif ($first_student_section && $first_student_year): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Waiting for student scans to display attendance statistics.
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
</script>
</body>
</html>
<?php 
if (isset($db)) {
    mysqli_close($db);
}