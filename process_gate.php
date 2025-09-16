<?php
include 'connection.php';
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("process_gate.php hit at " . date('Y-m-d H:i:s')); 

session_start();

// Get the ID number from the request
$id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
$department = isset($_POST['department']) ? $_POST['department'] : '';
$location = isset($_POST['location']) ? $_POST['location'] : '';

// Validate input
if (empty($id_number)) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

// Get current time and date
$current_time = date('H:i:s');
$current_date = date('Y-m-d');
$current_period = date('A'); // AM or PM

// Check if this is a student, instructor, personnel, or visitor
$person = null;
$person_type = '';

// Use prepared statements to prevent SQL injection
// First, check if it's a student
$sql = "SELECT * FROM students WHERE id_number = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $person = $result->fetch_assoc();
    $person_type = 'student';
} else {
    // Check if it's an instructor
    $sql = "SELECT * FROM instructor WHERE id_number = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $person = $result->fetch_assoc();
        $person_type = 'instructor';
    } else {
        // Check if it's personnel/staff
        $sql = "SELECT * FROM personell WHERE id_number = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $id_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $person = $result->fetch_assoc();
            $person_type = 'personell';
            
            // Check if personnel is blocked
            if (isset($person['status']) && $person['status'] == 'Block') {
                echo json_encode([
                    'error' => 'BLOCKED',
                    'time_in_out' => 'UNAUTHORIZED',
                    'full_name' => $person['first_name'] . ' ' . $person['last_name'],
                    'id_number' => $id_number
                ]);
                exit;
            }
        } else {
            // Check if it's a visitor
            $sql = "SELECT * FROM visitors WHERE id_number = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $person = $result->fetch_assoc();
                $person_type = 'visitor';
            } else {
                // Not found in any table - unauthorized
                echo json_encode([
                    'error' => 'NOT FOUND',
                    'time_in_out' => 'UNAUTHORIZED',
                    'full_name' => 'Unknown',
                    'id_number' => $id_number
                ]);
                exit;
            }
        }
    }
}

// Process the entry based on person type
switch ($person_type) {
    case 'student':
        processStudentEntry($person, $db, $department, $location, $current_date, $current_time, $current_period);
        break;
    case 'instructor':
        processInstructorEntry($person, $db, $department, $location, $current_date, $current_time, $current_period);
        break;
    case 'personell':
        processPersonellEntry($person, $db, $department, $location, $current_date, $current_time, $current_period);
        break;
    case 'visitor':
        processVisitorEntry($person, $db, $department, $location, $current_date, $current_time, $current_period);
        break;
    default:
        echo json_encode(['error' => 'Unknown person type']);
        exit;
}


// =============================================================
// STUDENT ENTRY
// =============================================================
function processStudentEntry($student, $db, $department, $location, $current_date, $current_time, $current_period) {
    $student_id = $student['id'];
    $id_number = $student['id_number'];
    $full_name = $student['first_name'] . ' ' . $student['last_name'];
    

    $sql = "SELECT * FROM student_glogs WHERE student_id = ? AND date_logged = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $student_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $time_in_out = 'UNAUTHORIZED';

    if ($result->num_rows > 0) {
        $log = $result->fetch_assoc();
        if ($current_period == 'AM') {
            if (empty($log['time_in_am'])) {
                $sql = "UPDATE student_glogs SET time_in_am = ?, dept = ?, location = ? WHERE student_id = ? AND date_logged = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssis", $current_time, $department, $location, $student_id, $current_date);
                if ($stmt->execute()) {
                    $time_in_out = 'TIME IN';
                }
            } elseif (empty($log['time_out_am'])) {
                $sql = "UPDATE student_glogs SET time_out_am = ?, dept = ?, location = ? WHERE student_id = ? AND date_logged = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssis", $current_time, $department, $location, $student_id, $current_date);
                if ($stmt->execute()) {
                    $time_in_out = 'TIME OUT';
                }
            }
        } else { // PM
            if (empty($log['time_in_pm'])) {
                $sql = "UPDATE student_glogs SET time_in_pm = ?, dept = ?, location = ? WHERE student_id = ? AND date_logged = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssis", $current_time, $department, $location, $student_id, $current_date);
                if ($stmt->execute()) {
                    $time_in_out = 'TIME IN';
                }
            } elseif (empty($log['time_out_pm'])) {
                $sql = "UPDATE student_glogs SET time_out_pm = ?, dept = ?, location = ? WHERE student_id = ? AND date_logged = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("sssis", $current_time, $department, $location, $student_id, $current_date);
                if ($stmt->execute()) {
                    $time_in_out = 'TIME OUT';
                }
            }
        }
    } else {
        $sql = "INSERT INTO student_glogs (student_id, date_logged, time_in_am, dept, location) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issss", $student_id, $current_date, $current_time, $department, $location);
        if ($stmt->execute()) {
            $time_in_out = 'TIME IN';
        }
    }

    echo json_encode([
        'id_number' => $id_number,
        'full_name' => $full_name,
        'first_name' => $student['first_name'],
        'role' => 'Student',
        'department' => $student['course'] ?? 'N/A',
        
        'time_in_out' => $time_in_out
    ]);
    exit;
}

// =============================================================
// INSTRUCTOR ENTRY
// =============================================================
function processInstructorEntry($instructor, $db, $department, $location, $current_date, $current_time, $current_period) {
    $instructor_id = $instructor['id'];
    $id_number = $instructor['id_number'];
    $full_name = $instructor['first_name'] . ' ' . $instructor['last_name'];
   

    $sql = "SELECT * FROM instructor_glogs WHERE instructor_id = ? AND date_logged = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $instructor_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $time_in_out = 'UNAUTHORIZED';

    if ($result->num_rows > 0) {
        $log = $result->fetch_assoc();
        if (empty($log['time_in'])) {
            $sql = "UPDATE instructor_glogs SET time_in = ?, dept = ?, location = ? WHERE instructor_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssis", $current_time, $department, $location, $instructor_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME IN';
            }
        } elseif (empty($log['time_out'])) {
            $sql = "UPDATE instructor_glogs SET time_out = ?, dept = ?, location = ? WHERE instructor_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssis", $current_time, $department, $location, $instructor_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME OUT';
            }
        }
    } else {
        $sql = "INSERT INTO instructor_glogs (instructor_id, date_logged, time_in, dept, location) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issss", $instructor_id, $current_date, $current_time, $department, $location);
        if ($stmt->execute()) {
            $time_in_out = 'TIME IN';
        }
    }

    echo json_encode([
        'id_number' => $id_number,
        'full_name' => $full_name,
        'first_name' => $instructor['first_name'],
        'role' => 'Instructor',
        'department' => $instructor['department'] ?? 'N/A',
        'photo' => $photo,
        'time_in_out' => $time_in_out
    ]);
    exit;
}

// =============================================================
// PERSONNEL ENTRY
// =============================================================
function processPersonellEntry($personnel, $db, $department, $location, $current_date, $current_time, $current_period) {
    $personnel_id = $personnel['id'];
    $id_number = $personnel['id_number'];
    $full_name = $personnel['first_name'] . ' ' . $personnel['last_name'];
   

    $sql = "SELECT * FROM personell_glogs WHERE personell_id = ? AND date_logged = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $personnel_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $time_in_out = 'UNAUTHORIZED';

    if ($result->num_rows > 0) {
        $log = $result->fetch_assoc();
        if (empty($log['time_in'])) {
            $sql = "UPDATE personell_glogs SET time_in = ?, dept = ?, location = ? WHERE personell_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssis", $current_time, $department, $location, $personnel_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME IN';
            }
        } elseif (empty($log['time_out'])) {
            $sql = "UPDATE personell_glogs SET time_out = ?, dept = ?, location = ? WHERE personell_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("sssis", $current_time, $department, $location, $personnel_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME OUT';
            }
        }
    } else {
        $sql = "INSERT INTO personell_glogs (personell_id, date_logged, time_in, dept, location) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("issss", $personnel_id, $current_date, $current_time, $department, $location);
        if ($stmt->execute()) {
            $time_in_out = 'TIME IN';
        }
    }

    echo json_encode([
        'id_number' => $id_number,
        'full_name' => $full_name,
        'first_name' => $personnel['first_name'],
        'role' => 'Personnel',
        'department' => $personnel['department'] ?? 'N/A',
        
        'time_in_out' => $time_in_out
    ]);
    exit;
}

// =============================================================
// VISITOR ENTRY
// =============================================================
function processVisitorEntry($visitor, $db, $department, $location, $current_date, $current_time, $current_period) {
    $visitor_id = $visitor['id'];
    $full_name = $visitor['full_name'];
    

    $sql = "SELECT * FROM visitor_glogs WHERE visitor_id = ? AND date_logged = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $visitor_id, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $time_in_out = 'UNAUTHORIZED';

    if ($result->num_rows > 0) {
        $log = $result->fetch_assoc();
        if (empty($log['time_in'])) {
            $sql = "UPDATE visitor_glogs SET time_in = ?, location = ? WHERE visitor_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssis", $current_time, $location, $visitor_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME IN';
            }
        } elseif (empty($log['time_out'])) {
            $sql = "UPDATE visitor_glogs SET time_out = ?, location = ? WHERE visitor_id = ? AND date_logged = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssis", $current_time, $location, $visitor_id, $current_date);
            if ($stmt->execute()) {
                $time_in_out = 'TIME OUT';
            }
        }
    } else {
        $sql = "INSERT INTO visitor_glogs (visitor_id, date_logged, time_in, location) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isss", $visitor_id, $current_date, $current_time, $location);
        if ($stmt->execute()) {
            $time_in_out = 'TIME IN';
        }
    }

    echo json_encode([
        'id_number' => $visitor['id_number'] ?? 'N/A',
        'full_name' => $full_name,
        'first_name' => explode(' ', $full_name)[0],
        'role' => 'Visitor',
        'department' => 'N/A',
        
        'time_in_out' => $time_in_out
    ]);
    exit;
}

mysqli_close($db);