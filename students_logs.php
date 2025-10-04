<?php
session_start();
$filterSection = $_SESSION['allowed_section'] ?? '';
$filterYear = $_SESSION['allowed_year'] ?? '';
include 'connection.php';

// Quick table structure verification
function verifyTableStructures($db) {
    $tables = ['attendance_logs', 'archived_attendance_logs', 'instructor_logs', 'archived_instructor_logs'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW CREATE TABLE $table");
        if ($result) {
            $row = $result->fetch_assoc();
            error_log("Table structure for $table: " . substr($row['Create Table'], 0, 200));
        }
    }
}

// Call this function to debug
verifyTableStructures($db);

// Function to get classmates by year and section
function getClassmatesByYearSection($db, $year, $section) {
    $classmates = array();
    
    $query = "SELECT s.id_number, s.fullname, s.section, s.year, d.department_name,
              (SELECT COUNT(*) FROM attendance_logs al 
               WHERE al.student_id = s.id AND DATE(al.time_in) = CURDATE()) as attendance_count
              FROM students s
              LEFT JOIN department d ON s.department_id = d.department_id
              WHERE s.section = ? AND s.year = ?
              ORDER BY s.fullname";
    
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ss", $section, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $classmates[] = $row;
        }
        
        $stmt->close();
    }
    
    return $classmates;
}

// NEW FUNCTION: Save classmates data to instructor attendance records
function saveClassmatesToInstructorAttendance($db, $classmates, $instructor_id, $year, $section, $subject = null) {
    $today = date('Y-m-d');
    
    foreach ($classmates as $student) {
        // Check if record already exists for today
        $check_query = "SELECT id FROM instructor_attendance_records 
                       WHERE instructor_id = ? 
                       AND student_id_number = ? 
                       AND date = ? 
                       AND year = ? 
                       AND section = ?";
        
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bind_param("issss", $instructor_id, $student['id_number'], $today, $year, $section);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            // Insert new record
            $insert_query = "INSERT INTO instructor_attendance_records 
                           (instructor_id, student_id_number, student_name, section, year, 
                            department, status, date, subject, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $insert_stmt = $db->prepare($insert_query);
            $status = ($student['attendance_count'] > 0) ? 'Present' : 'Absent';
            
            $insert_stmt->bind_param(
                "issssssss", 
                $instructor_id, 
                $student['id_number'],
                $student['fullname'],
                $section,
                $year,
                $student['department_name'],
                $status,
                $today,
                $subject
            );
            
            $insert_stmt->execute();
            $insert_stmt->close();
        } else {
            // Update existing record
            $update_query = "UPDATE instructor_attendance_records 
                           SET status = ?, updated_at = NOW() 
                           WHERE instructor_id = ? 
                           AND student_id_number = ? 
                           AND date = ? 
                           AND year = ? 
                           AND section = ?";
            
            $update_stmt = $db->prepare($update_query);
            $status = ($student['attendance_count'] > 0) ? 'Present' : 'Absent';
            
            $update_stmt->bind_param(
                "sissss", 
                $status,
                $instructor_id, 
                $student['id_number'],
                $today,
                $year,
                $section
            );
            
            $update_stmt->execute();
            $update_stmt->close();
        }
        
        $check_stmt->close();
    }
    
    return true;
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
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($classmates as $student) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['id_number']) . '</td>';
        echo '<td>' . htmlspecialchars($student['fullname']) . '</td>';
        echo '<td>' . htmlspecialchars($student['section']) . '</td>';
        echo '<td>' . htmlspecialchars($student['year']) . '</td>';
        echo '<td>' . htmlspecialchars($student['department_name']) . '</td>';
        echo '<td>' . ($student['attendance_count'] > 0 ? 
             '<span class="badge bg-success">Present</span>' : 
             '<span class="badge bg-danger">Absent</span>') . '</td>';
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

// Get the first student's section and year if available
$first_student_section = isset($_SESSION['first_student_section']) ? $_SESSION['first_student_section'] : null;
$first_student_year = isset($_SESSION['first_student_year']) ? $_SESSION['first_student_year'] : null;

// If not in session, try to get from database
if (!$first_student_section || !$first_student_year) {
    $firstStudent = getFirstStudentDetails($db);
    if ($firstStudent) {
        $first_student_year = $firstStudent['year'];
        $first_student_section = $firstStudent['section'];
        
        // Store in session for persistence
        $_SESSION['first_student_section'] = $first_student_section;
        $_SESSION['first_student_year'] = $first_student_year;
    }
}

// Handle clear filter request
if (isset($_GET['clear_filter'])) {
    unset($_SESSION['first_student_section']);
    unset($_SESSION['first_student_year']);
    header("Location: students_logs.php");
    exit();
}

$logo1 = "";
$nameo = "";
$address = "";
$logo2 = "";
$department = isset($_SESSION['access']['room']['department']) ? $_SESSION['access']['room']['department'] : 'Department';
$location = isset($_SESSION['access']['room']['room']) ? $_SESSION['access']['room']['room'] : 'Location';

// Fetch data from the about table
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
}

// Record instructor attendance automatically when they access this page
if (isset($_SESSION['access']['instructor']['id'])) {
    $instructor_id = $_SESSION['access']['instructor']['id'];
    $id_number = $_SESSION['access']['instructor']['id_number'];
    $today = date('Y-m-d');
    
    // Check if already logged in today
    $check_sql = "SELECT id FROM instructor_logs WHERE instructor_id = ? AND DATE(time_in) = ?";
    $check_stmt = $db->prepare($check_sql);
    
    if ($check_stmt === false) {
        die("Error preparing check statement: " . $db->error);
    }
    
    $check_stmt->bind_param("is", $instructor_id, $today);
    if (!$check_stmt->execute()) {
        die("Error executing check statement: " . $check_stmt->error);
    }
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Record time_in
        $insert_sql = "INSERT INTO instructor_logs 
                      (instructor_id, id_number, time_in, department, location) 
                      VALUES (?, ?, NOW(), ?, ?)";
        $insert_stmt = $db->prepare($insert_sql);
        
        if ($insert_stmt === false) {
            die("Error preparing insert statement: " . $db->error);
        }
        
        $insert_stmt->bind_param(
            "isss",
            $instructor_id, 
            $id_number,
            $department,
            $location
        );
        
        if (!$insert_stmt->execute()) {
            die("Error executing insert statement: " . $insert_stmt->error);
        }
    }
}

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

        // NEW: Save classmates data before archiving
        if ($first_student_section && $first_student_year) {
            $classmates = getClassmatesByYearSection($db, $first_student_year, $first_student_section);
            $subject = $_SESSION['access']['subject']['name'] ?? null;
            saveClassmatesToInstructorAttendance($db, $classmates, $instructor_id, $first_student_year, $first_student_section, $subject);
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

        // DEBUG: Check current data before archiving
        error_log("=== DEBUG: Checking data before archiving ===");
        
        // Check student records that will be archived
        $student_check = $db->query("SELECT COUNT(*) as total, 
                                    SUM(CASE WHEN instructor_id IS NULL THEN 1 ELSE 0 END) as missing_instructor 
                                    FROM attendance_logs 
                                    WHERE DATE(time_in) = CURDATE()");
        if ($student_check) {
            $student_stats = $student_check->fetch_assoc();
            error_log("Student records to archive: " . $student_stats['total'] . ", Missing instructor_id: " . $student_stats['missing_instructor']);
        }

        // Check instructor records that will be archived
        $instructor_check = $db->query("SELECT COUNT(*) as total, instructor_id 
                                       FROM instructor_logs 
                                       WHERE DATE(time_in) = CURDATE()");
        if ($instructor_check) {
            $instructor_stats = $instructor_check->fetch_assoc();
            error_log("Instructor records to archive: " . $instructor_stats['total'] . ", Instructor ID: " . $instructor_stats['instructor_id']);
        }

        // 3. Archive student logs - ENSURING INSTRUCTOR_ID IS PRESERVED
        $db->query("CREATE TABLE IF NOT EXISTS archived_attendance_logs LIKE attendance_logs");
        
        // Verify table structures match
        $check_tables = $db->query("
            SELECT 
                (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'attendance_logs') as source_cols,
                (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'archived_attendance_logs') as archive_cols
        ");
        if ($check_tables) {
            $table_stats = $check_tables->fetch_assoc();
            error_log("Table columns - Source: " . $table_stats['source_cols'] . ", Archive: " . $table_stats['archive_cols']);
            
            // If column counts don't match, drop and recreate archive table
            if ($table_stats['source_cols'] != $table_stats['archive_cols']) {
                error_log("Table structures don't match - recreating archive table");
                $db->query("DROP TABLE IF EXISTS archived_attendance_logs");
                $db->query("CREATE TABLE archived_attendance_logs LIKE attendance_logs");
            }
        }

        // Archive with explicit verification
        $archive_result = $db->query("INSERT INTO archived_attendance_logs 
                                    SELECT * FROM attendance_logs 
                                    WHERE DATE(time_in) = CURDATE()");
        
        if (!$archive_result) {
            error_log("Archive error: " . $db->error);
            throw new Exception("Error archiving student data: " . $db->error);
        }
        
        $archived_count = $db->affected_rows;
        error_log("Successfully archived " . $archived_count . " student records");

        // Verify archived data has instructor_id
        $verify_archive = $db->query("SELECT COUNT(*) as total, 
                                     SUM(CASE WHEN instructor_id IS NULL THEN 1 ELSE 0 END) as missing_instructor 
                                     FROM archived_attendance_logs 
                                     WHERE DATE(time_in) = CURDATE()");
        if ($verify_archive) {
            $verify_stats = $verify_archive->fetch_assoc();
            error_log("Archived verification - Total: " . $verify_stats['total'] . ", Missing instructor_id: " . $verify_stats['missing_instructor']);
        }

        // 4. Archive instructor logs
        $db->query("CREATE TABLE IF NOT EXISTS archived_instructor_logs LIKE instructor_logs");
        
        $instructor_archive_result = $db->query("INSERT INTO archived_instructor_logs 
                                               SELECT * FROM instructor_logs 
                                               WHERE DATE(time_in) = CURDATE()");
        
        if (!$instructor_archive_result) {
            throw new Exception("Error archiving instructor data: " . $db->error);
        }
        
        $instructor_archived_count = $db->affected_rows;
        error_log("Successfully archived " . $instructor_archived_count . " instructor records");

        // 5. Clear current logs ONLY after successful archiving and verification
        $delete_students = $db->query("DELETE FROM attendance_logs WHERE DATE(time_in) = CURDATE()");
        if (!$delete_students) {
            throw new Exception("Error clearing student data: " . $db->error);
        }
        
        $delete_instructors = $db->query("DELETE FROM instructor_logs WHERE DATE(time_in) = CURDATE()");
        if (!$delete_instructors) {
            throw new Exception("Error clearing instructor data: " . $db->error);
        }

        error_log("Successfully cleared original records");

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

        error_log("=== ARCHIVE PROCESS COMPLETED SUCCESSFULLY ===");

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

// Check if we're coming from a save action
$show_timeout_message = isset($_SESSION['timeout_time']);
$attendance_saved = isset($_SESSION['attendance_saved']) ? $_SESSION['attendance_saved'] : false;
$archive_message = isset($_SESSION['archive_message']) ? $_SESSION['archive_message'] : '';

if ($show_timeout_message) {
    $timeout_time = $_SESSION['timeout_time'];
    unset($_SESSION['timeout_time']);
    unset($_SESSION['attendance_saved']);
    unset($_SESSION['archive_message']);
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
                <h5 class="mt-3">Attendance Records</h5>
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
                                        '<span class="badge bg-warning">Present</span>').'</td>';
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