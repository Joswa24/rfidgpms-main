<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session at the very beginning
session_start();

// Set timezone and include connection
date_default_timezone_set('Asia/Manila');
include 'connection.php';

// Set MySQL timezone to match PHP
mysqli_query($db, "SET time_zone = '+08:00'");

// Check if user came from scanner
$from_scanner = isset($_GET['from_scanner']) ? true : false;

// Initialize variables
$attendance_saved = false;
$show_timeout_message = false;
$timeout_time = '';
$archive_message = '';
$first_student_section = null;
$first_student_year = null;

// Check if instructor is logged in
if (!isset($_SESSION['access']['instructor']['id'])) {
    $_SESSION['scanner_error'] = "Please log in as instructor first";
    header("Location: index.php");
    exit();
}

// Function to clear session data
function clearAttendanceSessionData() {
    unset(
        $_SESSION['instructor_session_id'], 
        $_SESSION['instructor_login_time'],
        $_SESSION['allowed_section'],
        $_SESSION['allowed_year'],
        $_SESSION['is_first_student'],
        $_SESSION['attendance_saved'],
        $_SESSION['timeout_time'],
        $_SESSION['archive_message'],
        $_SESSION['original_time_in']
    );
}

// Handle logout action
if (isset($_POST['logout_after_save'])) {
    // Store success messages temporarily
    $saved_messages = [
        'timeout_time' => $_SESSION['timeout_time'] ?? '',
        'archive_message' => $_SESSION['archive_message'] ?? ''
    ];
    
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Start new session and restore messages
    session_start();
    $_SESSION['attendance_success'] = true;
    $_SESSION['timeout_time'] = $saved_messages['timeout_time'];
    $_SESSION['archive_message'] = $saved_messages['archive_message'];
    
    header("Location: index.php");
    exit();
}

// Function to fetch actual student time in/out with proper timezone
function getStudentAttendanceTimes($db, $id_number) {
    $query = "SELECT 
                al.time_in,
                al.time_out,
                (SELECT COUNT(*) FROM attendance_logs 
                 WHERE student_id = s.id 
                 AND DATE(time_in) = CURDATE()) as scan_count,
                s.fullname
              FROM attendance_logs al
              JOIN students s ON al.student_id = s.id
              WHERE s.id_number = ? 
              AND DATE(al.time_in) = CURDATE()
              ORDER BY al.time_in DESC
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($attendance_data) {
            // Convert UTC times from database to Asia/Manila timezone
            $time_in = $attendance_data['time_in'] ? 
                convertUtcToManila($attendance_data['time_in']) : 
                null;
            
            $time_out = $attendance_data['time_out'] ? 
                convertUtcToManila($attendance_data['time_out']) : 
                null;
            
            return [
                'time_in' => $time_in,
                'time_out' => $time_out,
                'scan_count' => $attendance_data['scan_count'],
                'fullname' => $attendance_data['fullname'],
                'has_time_in' => !empty($attendance_data['time_in']),
                'has_time_out' => !empty($attendance_data['time_out'])
            ];
        }
    }
    
    return [
        'time_in' => null,
        'time_out' => null,
        'scan_count' => 0,
        'fullname' => '',
        'has_time_in' => false,
        'has_time_out' => false
    ];
}

// Function to convert UTC time from database to Asia/Manila time
function convertUtcToManila($utcDateTime) {
    if (!$utcDateTime) return null;
    
    try {
        // Create DateTime object from UTC time
        $utcTime = new DateTime($utcDateTime, new DateTimeZone('UTC'));
        // Convert to Asia/Manila timezone
        $utcTime->setTimezone(new DateTimeZone('Asia/Manila'));
        // Return formatted time
        return $utcTime->format('h:i A');
    } catch (Exception $e) {
        error_log("Time conversion error: " . $e->getMessage());
        // Fallback to direct formatting
        return date('h:i A', strtotime($utcDateTime . ' UTC')) ?: null;
    }
}

// Enhanced function to get classmates with proper timezone conversion
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
                 AND DATE(al.time_in) = CURDATE()) as attendance_count,
                (SELECT time_in FROM attendance_logs al 
                 WHERE al.student_id = s.id 
                 AND DATE(al.time_in) = CURDATE()
                 ORDER BY al.time_in ASC LIMIT 1) as time_in,
                (SELECT time_out FROM attendance_logs al 
                 WHERE al.student_id = s.id 
                 AND DATE(al.time_in) = CURDATE()
                 ORDER BY al.time_in DESC LIMIT 1) as time_out
              FROM students s
              LEFT JOIN department d ON s.department_id = d.department_id
              WHERE s.section = ? AND s.year = ?
              ORDER BY s.fullname";
    
    $stmt = $db->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("ss", $section, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $classmates = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Process times with proper timezone conversion
        foreach ($classmates as &$student) {
            // Format Time In - convert from UTC to Manila time
            $student['formatted_time_in'] = '-';
            if ($student['time_in']) {
                $student['formatted_time_in'] = convertUtcToManila($student['time_in']);
            }
            
            // Format Time Out - convert from UTC to Manila time
            $student['formatted_time_out'] = '-';
            if ($student['time_out']) {
                $student['formatted_time_out'] = convertUtcToManila($student['time_out']);
            }
            
            // Determine attendance status
            $student['attendance_status'] = $student['attendance_count'] > 0 ? 'Present' : 'Absent';
        }
        
        return $classmates;
    }
    
    return [];
}

// Function to display classmates table with consistent time formatting
function displayClassmatesTable($classmates, $year, $section) {
    if (empty($classmates)) {
        echo '<div class="alert alert-info mt-4">No classmates found for ' . htmlspecialchars($year) . ' - ' . htmlspecialchars($section) . '</div>';
        return;
    }
    
    echo '<h5 class="mt-4">Class Attendance List (' . htmlspecialchars($year) . ' - ' . htmlspecialchars($section) . ')</h5>';
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
    echo '<th>Time In</th>';
    echo '<th>Time Out</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($classmates as $student) {
        $status_badge = $student['attendance_count'] > 0 ? 
            '<span class="badge bg-success">Present</span>' : 
            '<span class="badge bg-danger">Absent</span>';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($student['id_number']) . '</td>';
        echo '<td>' . htmlspecialchars($student['fullname']) . '</td>';
        echo '<td>' . htmlspecialchars($student['section']) . '</td>';
        echo '<td>' . htmlspecialchars($student['year']) . '</td>';
        echo '<td>' . htmlspecialchars($student['department_name']) . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td class="time-in-cell">' . $student['formatted_time_in'] . '</td>';
        echo '<td class="time-out-cell">' . $student['formatted_time_out'] . '</td>';
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

// Function to get attendance statistics
// Function to get attendance statistics
function getAttendanceStats($db, $year, $section) {
    // If we don't have valid section/year, return empty stats
    if ($year === 'N/A' || $section === 'N/A') {
        return ['total_students' => 0, 'present_count' => 0, 'absent_count' => 0, 'attendance_rate' => 0, 'absent_rate' => 0];
    }
    
    $query = "SELECT 
                COUNT(*) as total_students,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM attendance_logs al 
                    WHERE al.student_id = s.id 
                    AND DATE(al.time_in) = CURDATE()
                ) THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN NOT EXISTS (
                    SELECT 1 FROM attendance_logs al 
                    WHERE al.student_id = s.id 
                    AND DATE(al.time_in) = CURDATE()
                ) THEN 1 ELSE 0 END) as absent_count
              FROM students s
              WHERE s.section = ? AND s.year = ?";
    
    $stmt = $db->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ss", $section, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        // Ensure we have valid array
        if (!$stats) {
            return ['total_students' => 0, 'present_count' => 0, 'absent_count' => 0, 'attendance_rate' => 0, 'absent_rate' => 0];
        }
        
        // Calculate percentages
        if ($stats['total_students'] > 0) {
            $stats['attendance_rate'] = round(($stats['present_count'] / $stats['total_students']) * 100, 1);
            $stats['absent_rate'] = round(($stats['absent_count'] / $stats['total_students']) * 100, 1);
        } else {
            $stats['attendance_rate'] = 0;
            $stats['absent_rate'] = 0;
        }
        
        return $stats;
    }
    
    return ['total_students' => 0, 'present_count' => 0, 'absent_count' => 0, 'attendance_rate' => 0, 'absent_rate' => 0];
}

// Get first student details to determine class
$first_student = getFirstStudentDetails($db);
if ($first_student) {
    $first_student_section = $first_student['section'];
    $first_student_year = $first_student['year'];
}

// Handle Save Attendance action
// Handle Save Attendance action
if (isset($_POST['save_attendance']) && isset($_POST['id_number'])) {
    $instructor_id = $_SESSION['access']['instructor']['id'] ?? null;
    
    if (!$instructor_id) {
        $_SESSION['scanner_error'] = "Instructor not logged in";
        header("Location: students_logs.php");
        exit();
    }

    // Verify ID matches logged-in instructor
    $instructor_id_number = $_SESSION['access']['instructor']['id_number'] ?? '';
    if ($_POST['id_number'] != $instructor_id_number) {
        $_SESSION['scanner_error'] = "ID verification failed. Expected: $instructor_id_number, Got: " . $_POST['id_number'];
        header("Location: students_logs.php");
        exit();
    }

    try {
        $db->begin_transaction();

        // Get session information
        $original_time_in = $_SESSION['instructor_login_time'] ?? date('Y-m-d H:i:s');
        $time_in_formatted = date('H:i:s', strtotime($original_time_in));
        $time_out_formatted = date('H:i:s');
        $current_date = date('Y-m-d');

        // Get room location from session
        $room_location = $_SESSION['access']['room']['room'] ?? 'Classroom';
        
        // Get attendance stats - ensure we have valid values
        $stats = getAttendanceStats($db, $first_student_year ?? 'N/A', $first_student_section ?? 'N/A');
        
        // 1. Save to instructor_attendance_summary (UPDATED WITH ROOM)
        $summary_sql = "INSERT INTO instructor_attendance_summary 
            (instructor_id, instructor_name, subject_name, year_level, section, room,
            total_students, present_count, absent_count, attendance_rate, 
            session_date, time_in, time_out) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $summary_stmt = $db->prepare($summary_sql);
        
        // Ensure we have non-null values for binding
        $instructor_name = $_SESSION['access']['instructor']['fullname'] ?? 'Unknown Instructor';
        $subject_name = $_SESSION['access']['subject']['name'] ?? 'General Subject';
        $year_level = $first_student_year ?? 'N/A';
        $section = $first_student_section ?? 'N/A';
        $total_students = $stats['total_students'] ?? 0;
        $present_count = $stats['present_count'] ?? 0;
        $absent_count = $stats['absent_count'] ?? 0;
        $attendance_rate = $stats['attendance_rate'] ?? 0;
        
        $summary_stmt->bind_param(
            "isssssiiidsss",
            $instructor_id,
            $instructor_name,
            $subject_name,
            $year_level,
            $section,
            $room_location, // ADDED ROOM PARAMETER
            $total_students,
            $present_count,
            $absent_count,
            $attendance_rate,
            $current_date,
            $time_in_formatted,
            $time_out_formatted
        );
        
        if (!$summary_stmt->execute()) {
            throw new Exception("Failed to save attendance summary: " . $summary_stmt->error);
        }
        $summary_stmt->close();

        // 2. Archive PRESENT students (those who scanned) - UPDATED WITH ROOM
        $present_archive_sql = "INSERT INTO archived_attendance_logs 
            (student_id, id_number, fullname, department, location, time_in, time_out, 
            status, instructor_id, instructor_name, session_date, year_level, section, subject_name, room)
            SELECT 
                al.student_id,
                s.id_number,
                s.fullname,
                CONCAT(s.section, ' - ', s.year, ' Year'),
                ?,
                al.time_in,
                al.time_out,
                'Present',
                ?,
                ?,
                ?,
                s.year,
                s.section,
                ?,
                ?
            FROM attendance_logs al
            JOIN students s ON al.student_id = s.id
            WHERE DATE(al.time_in) = ?";
        
        $present_stmt = $db->prepare($present_archive_sql);
        $location_name = $_SESSION['access']['subject']['name'] ?? 'Classroom';
        $present_stmt->bind_param(
            "sisssss",
            $location_name,
            $instructor_id,
            $instructor_name,
            $current_date,
            $subject_name,
            $room_location, // ADDED ROOM PARAMETER
            $current_date
        );
        
        if (!$present_stmt->execute()) {
            throw new Exception("Failed to archive present students: " . $present_stmt->error);
        }
        $present_stmt->close();

        // 3. Archive ABSENT students (those who didn't scan) - UPDATED WITH ROOM
        if ($first_student_section && $first_student_year) {
            $absent_archive_sql = "INSERT INTO archived_attendance_logs 
                (student_id, id_number, fullname, department, location, time_in, time_out, 
                status, instructor_id, instructor_name, session_date, year_level, section, subject_name, room)
                SELECT 
                    s.id,
                    s.id_number,
                    s.fullname,
                    CONCAT(s.section, ' - ', s.year, ' Year'),
                    ?,
                    NULL,
                    NULL,
                    'Absent',
                    ?,
                    ?,
                    ?,
                    s.year,
                    s.section,
                    ?,
                    ?
                FROM students s
                WHERE s.section = ? AND s.year = ?
                AND s.id NOT IN (
                    SELECT student_id FROM attendance_logs WHERE DATE(time_in) = ?
                )";
            
            $absent_stmt = $db->prepare($absent_archive_sql);
            $absent_stmt->bind_param(
                "sisssssss",
                $location_name,
                $instructor_id,
                $instructor_name,
                $current_date,
                $subject_name,
                $room_location, // ADDED ROOM PARAMETER
                $first_student_section,
                $first_student_year,
                $current_date
            );
            
            if (!$absent_stmt->execute()) {
                throw new Exception("Failed to archive absent students: " . $absent_stmt->error);
            }
            $absent_stmt->close();
        } else {
            error_log("⚠️ Cannot archive absent students: No section/year data available");
        }

        // 4. Clear current logs
        $delete_stmt = $db->prepare("DELETE FROM attendance_logs WHERE DATE(time_in) = ?");
        $delete_stmt->bind_param("s", $current_date);
        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to clear logs: " . $delete_stmt->error);
        }
        $delete_stmt->close();

        $db->commit();

        // Success handling
        $_SESSION['timeout_time'] = date('h:i A');
        $_SESSION['original_time_in'] = date('h:i A', strtotime($original_time_in));
        $_SESSION['attendance_saved'] = true;
        $_SESSION['archive_message'] = "Attendance saved successfully! Present: {$present_count}, Absent: {$absent_count}, Room: {$room_location}";
        
        // Clear session data
        clearAttendanceSessionData();
        
        header("Location: students_logs.php");
        exit();

    } catch (Exception $e) {
        $db->rollback();
        error_log("Attendance save error: " . $e->getMessage());
        $_SESSION['scanner_error'] = "Error saving attendance: " . $e->getMessage();
        header("Location: students_logs.php");
        exit();
    }
}
// Check if attendance was just saved

    if (isset($_SESSION['attendance_saved']) && $_SESSION['attendance_saved']) {
        $attendance_saved = true;
        $show_timeout_message = true;
        $timeout_time = $_SESSION['timeout_time'] ?? '';
        $archive_message = $_SESSION['archive_message'] ?? '';
        $original_time_in = $_SESSION['original_time_in'] ?? '';
        
        // Clear the session variables
        unset(
            $_SESSION['attendance_saved'], 
            $_SESSION['timeout_time'], 
            $_SESSION['archive_message'],
            $_SESSION['first_student_section'],
            $_SESSION['first_student_year'],
            $_SESSION['original_time_in']
        );
    }

// AJAX handler for real-time time display
if (isset($_GET['ajax']) && isset($_GET['id_number'])) {
    $attendance_data = getStudentAttendanceTimes($db, $_GET['id_number']);
    header('Content-Type: application/json');
    echo json_encode($attendance_data);
    exit();
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
    <title>Attendance Log - Class Checker</title>
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #f3f5fcff;
            --icon-color: #5c95e9ff;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e4652aff;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Header - Fixed height and fully visible */
        .header-container {
            background: transparent;
            padding: 0;
            margin: 0;
            height: 120px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-image {
            max-width: 100%;
            max-height: 150%;
            object-fit: contain;
            display: block;
        }

        /* Main Container - Allow scrolling */
        .main-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin: 10px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* Navigation Tabs */
        .modern-tabs {
            background: var(--accent-color);
            border-radius: 8px;
            padding: 4px;
            margin: 10px;
            flex-shrink: 0;
        }

        .modern-tabs .nav-link {
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            color: var(--dark-text);
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .modern-tabs .nav-link.active {
            background: var(--secondary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        /* Content Area - Allow scrolling */
        .content-area {
            flex: 1;
            display: flex;
            padding: 0 10px 10px 10px;
            gap: 10px;
            overflow: hidden;
            min-height: 0;
        }

        /* Card Styles */
        .stats-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            overflow: hidden;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
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
            border-radius: 4px;
            overflow: hidden;
        }

        /* Instructor Header */
        .instructor-header {
            background: var(--light-bg);
            border-radius: var(--border-radius);
            padding: 15px;
            margin: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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
            color: var(--dark-text);
            margin-right: 5px;
        }

        .detail-value {
            color: var(--icon-color);
            font-weight: 500;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(92, 149, 233, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(92, 149, 233, 0.4);
        }

        .btn-outline-danger {
            border-color: var(--danger-color);
            color: var(--danger-color);
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Table Styles */
        .table-container {
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border: none;
            padding: 12px 15px;
            font-weight: 600;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: var(--accent-color);
        }

        .table tbody td {
            padding: 12px 15px;
            border-color: #e9ecef;
            vertical-align: middle;
        }

        /* Badge Styles */
        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #4cc9f0, #4361ee) !important;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #e74a3b, #d62828) !important;
        }

        /* Alert Styles */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 15px 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .alert-info {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .alert-success {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }

        .alert-warning {
            background: linear-gradient(135deg, #f6c23e, #f4a261);
            color: white;
        }

        /* Classmates Section */
        .classmates-section {
            margin: 10px;
            padding: 20px;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        /* Timeout Display */
        .timeout-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--icon-color);
            text-align: center;
            margin: 20px 0;
        }

        .archived-message {
            margin: 10px;
            padding: 30px;
            background: var(--light-bg);
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--accent-color);
        }

        .logout-after-save {
            margin-top: 20px;
            text-align: center;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--icon-color), #4361ee);
            color: white;
            border-bottom: none;
            padding: 15px 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: none;
            padding: 15px 20px;
            justify-content: center;
        }

        /* Additional styles for time display */
        .time-display {
            padding: 10px;
            background: var(--light-bg);
            border-radius: 8px;
            margin: 5px 0;
        }

        .time-display small {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .time-display .fw-bold {
            font-size: 1.1rem;
        }

        /* Highlight recent scans */
        tr:hover {
            background-color: rgba(92, 149, 233, 0.05) !important;
        }

        /* Time cells styling */
        .time-in-cell, .time-out-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .time-in-cell {
            color: #198754;
        }

        .time-out-cell {
            color: #dc3545;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .header-container {
                height: 100px;
            }
            
            .content-area {
                flex-direction: column;
                padding: 0 8px 8px 8px;
                gap: 8px;
            }
            
            .instructor-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .instructor-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                height: 80px;
            }
            
            .main-container {
                margin: 8px;
            }
            
            .modern-tabs {
                margin: 8px;
            }
            
            .modern-tabs .nav-link {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .stats-number {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                font-size: 2rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .header-container {
                height: 70px;
            }
            
            .main-container {
                margin: 5px;
            }
            
            .modern-tabs {
                margin: 5px;
            }
            
            .content-area {
                padding: 0 5px 5px 5px;
                gap: 5px;
            }
            
            .classmates-section {
                padding: 15px;
            }
            
            .instructor-header {
                padding: 10px;
            }
        }

        /* Custom scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--icon-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4a7fe0;
        }
    </style>
</head>
<body>
<!-- Header - Fixed height, fully visible -->
<div class="header-container">
    <img src="uploads/Head-removebg-preview.png" alt="Header" class="header-image">
</div>

<!-- Main Container - Scroll Design -->
<div class="main-container">
    <!-- Navigation Tabs -->
    <div class="modern-tabs">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link" href="main1.php">
                    <i class="fas fa-qrcode me-2"></i>Scanner
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="#">
                    <i class="fas fa-history me-2"></i>Attendance Log
                </a>
            </li>
        </ul>
    </div>

    <!-- Content Area - Allow scrolling -->
    <div class="content-area">
        <div class="scanner-section" style="flex: 1;">
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
                    <?php if ($attendance_saved): ?>
                        <div class="archived-message">
                            <h4>Attendance Records Archived</h4>
                            <p><?php echo htmlspecialchars($archive_message); ?></p>
                            <div class="session-timeline text-center mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="time-display">
                                            <small class="text-muted">Time In</small>
                                            <div class="fw-bold text-primary">
                                                <?php 
                                                if (!empty($original_time_in)) {
                                                    echo htmlspecialchars($original_time_in);
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="time-display">
                                            <small class="text-muted">Time Out</small>
                                            <div class="fw-bold text-primary">
                                                <?php 
                                                if (!empty($timeout_time)) {
                                                    echo htmlspecialchars($timeout_time);
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-success"><i class="fas fa-check-circle me-2"></i>Classmates data has been saved to your instructor panel.</p>
                            
                            <!-- Logout Button for Another Class -->
                            <div class="logout-after-save">
                                <form method="post" class="d-inline">
                                    <button type="submit" name="logout_after_save" class="btn btn-success btn-lg">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout & Start Another Class
                                    </button>
                                </form>
                                <p class="text-muted mt-2">Click above to log out and start attendance for another class</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Enhanced Statistics Section -->
                        <?php if ($first_student_section && $first_student_year): 
                            $stats = getAttendanceStats($db, $first_student_year, $first_student_section);
                        ?>
                        <div class="class-summary mb-4">
                            <h4 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Class Attendance Dashboard</h4>
                            
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
                                                <?php echo $stats['attendance_rate']; ?>%
                                            </div>
                                            <div class="stats-label">Attendance Rate</div>
                                            <div class="progress attendance-progress">
                                                <div class="progress-bar bg-primary" 
                                                    style="width: <?php echo $stats['attendance_rate']; ?>%">
                                                </div>
                                            </div>
                                            <div class="stats-detail small mt-2">
                                                <?php echo $stats['present_count']; ?> of <?php echo $stats['total_students']; ?> students
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Present Students -->
                                <div class="col-xl-3 col-md-6">
                                    <div class="card stats-card border-success">
                                        <div class="card-body text-center">
                                            <div class="stats-icon text-success">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div class="stats-number text-success">
                                                <?php echo $stats['present_count']; ?>
                                            </div>
                                            <div class="stats-label">Present</div>
                                            <div class="stats-detail small">
                                                Students who scanned today
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Absent Students -->
                                <div class="col-xl-3 col-md-6">
                                    <div class="card stats-card border-danger">
                                        <div class="card-body text-center">
                                            <div class="stats-icon text-danger">
                                                <i class="fas fa-user-times"></i>
                                            </div>
                                            <div class="stats-number text-danger">
                                                <?php echo $stats['absent_count']; ?>
                                            </div>
                                            <div class="stats-label">Absent</div>
                                            <div class="stats-detail small">
                                                <?php echo $stats['absent_rate']; ?>% of class
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Class Size -->
                                <div class="col-xl-3 col-md-6">
                                    <div class="card stats-card border-info">
                                        <div class="card-body text-center">
                                            <div class="stats-icon text-info">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="stats-number text-info">
                                                <?php echo $stats['total_students']; ?>
                                            </div>
                                            <div class="stats-label">Class Size</div>
                                            <div class="stats-detail small">
                                                Total enrolled students
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($from_scanner): ?>
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
                                        <span class="detail-value">
                                            <?php 
                                            if (isset($_SESSION['access']['instructor']['fullname'])) {
                                                echo htmlspecialchars($_SESSION['access']['instructor']['fullname']);
                                            } else {
                                                echo 'Not logged in';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['access']['subject']['name'])): ?>
                                    <div>
                                        <span class="detail-label">Subject:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($_SESSION['access']['subject']['name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['access']['subject']['time'])): ?>
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
                                
                                <?php if (isset($_SESSION['access']['instructor']['id'])): ?>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#idModal">
                                        <i class="fas fa-save me-1"></i> Save Today's Attendance
                                    </button>
                                    <a href="logout.php" class="btn btn-outline-danger">
                                        <i class="bx bx-power-off me-1"></i> Logout
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Classmates Section - ALWAYS VISIBLE FOR ATTENDANCE CHECKING -->
                        <?php if ($first_student_section && $first_student_year): ?>
                            <div class="classmates-section">
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
                                No class data available. Class attendance list will appear when the first student scans their ID.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Verification Modal -->
            <?php if (isset($_SESSION['access']['instructor']['id'])): ?>
            <div class="modal fade" id="idModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Instructor Verification</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <h5>Verifying: <?php echo htmlspecialchars($_SESSION['access']['instructor']['fullname'] ?? 'Instructor'); ?></h5>
                                <p class="text-muted">Your ID: <?php echo htmlspecialchars($_SESSION['access']['instructor']['id_number'] ?? 'N/A'); ?></p>
                                <p class="text-muted">Scan your ID barcode or enter manually</p>
                            </div>
                            <form id="verifyForm" method="post">
                                <div class="mb-3">
                                    <label for="idInput" class="form-label">ID Number</label>
                                    <input type="text" class="form-control" id="idInput" name="id_number" 
                                        placeholder="<?php echo htmlspecialchars($_SESSION['access']['instructor']['id_number'] ?? 'Scan your ID'); ?>" 
                                        required autofocus
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
            <?php endif; ?>
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
        const logoutBtn = document.querySelector('.btn-outline-danger');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to log out? This will clear today\'s attendance records.')) {
                    e.preventDefault();
                }
            });
        }

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

        <?php if ($show_timeout_message && $attendance_saved): ?>
        // Show success message if attendance was saved
        Swal.fire({
            icon: 'success',
            title: 'Attendance Saved Successfully!',
            html: `<div class="text-center">
                      <div class="mb-3">
                          <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                      </div>
                      <h5 class="mb-3">Your attendance session has been completed</h5>
                      
                      <div class="session-timeline mb-4">
                          <div class="row justify-content-center">
                              <div class="col-md-5">
                                  <div class="time-display bg-light p-3 rounded">
                                      <small class="text-muted d-block">Time In</small>
                                      <div class="fw-bold text-primary fs-5">
                                          <?php echo !empty($original_time_in) ? htmlspecialchars($original_time_in) : 'N/A'; ?>
                                      </div>
                                  </div>
                              </div>
                              <div class="col-md-2 d-flex align-items-center justify-content-center">
                                  <i class="fas fa-arrow-right text-muted"></i>
                              </div>
                              <div class="col-md-5">
                                  <div class="time-display bg-light p-3 rounded">
                                      <small class="text-muted d-block">Time Out</small>
                                      <div class="fw-bold text-primary fs-5">
                                          <?php echo !empty($timeout_time) ? htmlspecialchars($timeout_time) : 'N/A'; ?>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                      
                      <div class="alert alert-success bg-success text-white border-0">
                          <i class="fas fa-users me-2"></i>
                          <?php echo htmlspecialchars($archive_message); ?>
                      </div>
                      
                      <p class="text-muted">
                          <i class="fas fa-info-circle me-2"></i>
                          Class attendance data has been archived to your instructor panel.
                      </p>
                   </div>`,
            confirmButtonText: 'Continue',
            confirmButtonColor: '#3085d6',
            allowOutsideClick: false,
            backdrop: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Optional: You can add any cleanup or redirect here if needed
            }
        });
        <?php endif; ?>

        // Auto-refresh the page every 30 seconds to update attendance status
        // Only refresh if attendance is not saved (when showing active data)
        <?php if (!$attendance_saved): ?>
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        <?php endif; ?>
    });
</script>
</body>
</html>
<?php 
if (isset($db)) {
    mysqli_close($db);
}