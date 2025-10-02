<?php
include '../connection.php';
session_start();

// Function to send JSON response
function jsonResponse($status, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Check if request is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Invalid request method');
}

if (!isset($_POST['type']) || !isset($_POST['id'])) {
    jsonResponse('error', 'Invalid request parameters');
}

$type = $_POST['type'];
$id = (int)$_POST['id'];

// Validate ID
if ($id <= 0) {
    jsonResponse('error', 'Invalid ID');
}

try {
    switch ($type) {
        case 'department':
            // First check if department has any personnel
            $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE department = 
                (SELECT department_name FROM department WHERE department_id = ?)");
            $checkPersonnel->bind_param("i", $id);
            $checkPersonnel->execute();
            $checkPersonnel->bind_result($personnelCount);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            if ($personnelCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned personnel');
            }

            // Check if department has any rooms
            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = 
                (SELECT department_name FROM department WHERE department_id = ?)");
            $checkRooms->bind_param("i", $id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned rooms');
            }

            // Proceed with deletion
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;

        case 'visitor':
            $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Visitor card deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete visitor card: ' . $stmt->error);
            }
            break;

        case 'personell':
            // Check for lost cards
            $checkLostCards = $db->prepare("SELECT COUNT(*) FROM lostcard WHERE personnel_id = ?");
            $checkLostCards->bind_param("i", $id);
            $checkLostCards->execute();
            $checkLostCards->bind_result($lostCardCount);
            $checkLostCards->fetch();
            $checkLostCards->close();

            if ($lostCardCount > 0) {
                jsonResponse('error', 'Cannot delete personnel with associated lost card records');
            }

            // Check for access logs
            $checkLogs = $db->prepare("SELECT COUNT(*) FROM personell_logs WHERE personnel_id = ?");
            $checkLogs->bind_param("i", $id);
            $checkLogs->execute();
            $checkLogs->bind_result($logCount);
            $checkLogs->fetch();
            $checkLogs->close();

            if ($logCount > 0) {
                jsonResponse('error', 'Cannot delete personnel with associated access logs');
            }

            // Delete personnel
            $stmt = $db->prepare("DELETE FROM personell WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Personnel deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
            }
            break;

      case 'room':
    // Check if room exists first
    $checkRoom = $db->prepare("SELECT id FROM rooms WHERE id = ?");
    $checkRoom->bind_param("i", $id);
    $checkRoom->execute();
    $checkRoom->store_result();
    
    if ($checkRoom->num_rows === 0) {
        jsonResponse('error', 'Room not found');
    }
    $checkRoom->close();

    // Check for room dependencies (scheduled classes)
    $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name COLLATE utf8mb4_unicode_ci = 
        (SELECT room COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)");
    $checkSchedules->bind_param("i", $id);
    $checkSchedules->execute();
    $checkSchedules->bind_result($scheduleCount);
    $checkSchedules->fetch();
    $checkSchedules->close();

    if ($scheduleCount > 0) {
        jsonResponse('error', 'Cannot delete room with scheduled classes');
    }

    // Delete room
    $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Room deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete room: ' . $stmt->error);
    }
    break;

        case 'role':
            // Check if role is assigned to personnel
            $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE role = 
                (SELECT role FROM role WHERE id = ?)");
            $checkPersonnel->bind_param("i", $id);
            $checkPersonnel->execute();
            $checkPersonnel->bind_result($personnelCount);
            $checkPersonnel->fetch();
            $checkPersonnel->close();

            if ($personnelCount > 0) {
                jsonResponse('error', 'Cannot delete role assigned to personnel');
            }

            // Check if role is assigned to rooms
            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms 
                WHERE FIND_IN_SET(
                    (SELECT role FROM role WHERE id = ?) COLLATE utf8mb4_unicode_ci, 
                    authorized_personnel COLLATE utf8mb4_unicode_ci
                ) > 0");
            $checkRooms->bind_param("i", $id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete role assigned to rooms');
            }

            // Proceed with deletion
            $stmt = $db->prepare("DELETE FROM role WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Role deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete role: ' . $stmt->error);
            }
            break;

        case 'delete_instructor':
            // Check if instructor exists
            $check = $db->prepare("SELECT id FROM instructor WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows === 0) {
                jsonResponse('error', 'Instructor not found');
            }
            $check->close();

            // Delete instructor
            $stmt = $db->prepare("DELETE FROM instructor WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Instructor deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete instructor: ' . $stmt->error);
            }
            break;

        case 'delete_student':
        // Check if student exists
        $check = $db->prepare("SELECT id FROM students WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            jsonResponse('error', 'Student not found');
        }
        $check->close();

        // Delete student
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            jsonResponse('success', 'Student deleted successfully');
        } else {
            jsonResponse('error', 'Failed to delete student');
        }
        break;
            case 'schedule':
    // First check if schedule exists
    $check = $db->prepare("SELECT id FROM room_schedules WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows === 0) {
        jsonResponse('error', 'Schedule not found');
    }
    $check->close();

    // Delete schedule
    $stmt = $db->prepare("DELETE FROM room_schedules WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Schedule deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete schedule: ' . $stmt->error);
    }
    break;

        default:
            jsonResponse('error', 'Invalid type specified');
    }
} catch (Exception $e) {
    jsonResponse('error', 'Database error: ' . $e->getMessage());
}

$db->close();
?>
<?php
include('../connection.php');
date_default_timezone_set('Asia/Manila');
session_start();

// Function to send JSON response
function jsonResponse($status, $message, $data = []) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to validate and sanitize input
function sanitizeInput($db, $input) {
    return mysqli_real_escape_string($db, trim($input));
}

// Check if this is an AJAX request for department operations
$isDepartmentAjax = isset($_GET['action']) && in_array($_GET['action'], ['add_department', 'update_department', 'delete_department']);

if ($isDepartmentAjax) {
    // For AJAX requests, don't include any other files that might output HTML
    switch ($_GET['action']) {
        case 'add_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if (strlen($department_name) > 100) {
                jsonResponse('error', 'Department name must be less than 100 characters');
            }

            if (strlen($department_desc) > 255) {
                jsonResponse('error', 'Description must be less than 255 characters');
            }

            // Check if department exists
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ?");
            $check->bind_param("s", $department_name);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department already exists');
            }

            // Insert new department
            $stmt = $db->prepare("INSERT INTO department (department_name, department_desc) VALUES (?, ?)");
            $stmt->bind_param("ss", $department_name, $department_desc);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department added successfully');
            } else {
                jsonResponse('error', 'Failed to add department: ' . $db->error);
            }
            break;

        case 'update_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }
            if (!isset($_POST['dptname']) || empty(trim($_POST['dptname']))) {
                jsonResponse('error', 'Department name is required');
            }

            $department_id = intval($_POST['id']);
            $department_name = sanitizeInput($db, trim($_POST['dptname']));
            $department_desc = isset($_POST['dptdesc']) ? sanitizeInput($db, trim($_POST['dptdesc'])) : '';

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            // Check if department exists (excluding current one)
            $check = $db->prepare("SELECT COUNT(*) FROM department WHERE department_name = ? AND department_id != ?");
            $check->bind_param("si", $department_name, $department_id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                jsonResponse('error', 'Department name already exists');
            }

            // Update department
            $stmt = $db->prepare("UPDATE department SET department_name = ?, department_desc = ? WHERE department_id = ?");
            $stmt->bind_param("ssi", $department_name, $department_desc, $department_id);

            if ($stmt->execute()) {
                jsonResponse('success', 'Department updated successfully');
            } else {
                jsonResponse('error', 'Failed to update department: ' . $db->error);
            }
            break;

        case 'delete_department':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse('error', 'Invalid request method');
            }

            if (!isset($_POST['id']) || empty($_POST['id'])) {
                jsonResponse('error', 'Department ID is required');
            }

            $department_id = intval($_POST['id']);

            if ($department_id <= 0) {
                jsonResponse('error', 'Invalid department ID');
            }

            $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE department = (SELECT department_name FROM department WHERE department_id = ?)");
            $checkRooms->bind_param("i", $department_id);
            $checkRooms->execute();
            $checkRooms->bind_result($roomCount);
            $checkRooms->fetch();
            $checkRooms->close();

            if ($roomCount > 0) {
                jsonResponse('error', 'Cannot delete department with assigned rooms');
            }

            // Delete department
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $department_id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;

        default:
            jsonResponse('error', 'Invalid action');
    }
} else {
    // Handle other actions (your existing code)
    if (!isset($_GET['action'])) {
        jsonResponse('error', 'No action specified');
    }
// Add these cases to your existing transac.php switch statement

case 'add_room':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
        }
    }

    // Sanitize inputs
    $department = sanitizeInput($db, $_POST['roomdpt']);
    $role = sanitizeInput($db, $_POST['roomrole']);
    $room = sanitizeInput($db, $_POST['roomname']);
    $descr = sanitizeInput($db, $_POST['roomdesc']);
    $password = sanitizeInput($db, $_POST['roompass']);

    // Validate lengths
    if (strlen($room) > 100) {
        jsonResponse('error', 'Room name must be less than 100 characters');
    }

    if (strlen($descr) > 255) {
        jsonResponse('error', 'Description must be less than 255 characters');
    }

    if (strlen($password) < 6) {
        jsonResponse('error', 'Password must be at least 6 characters');
    }

    // Check if room exists in department
    $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ?");
    $check->bind_param("ss", $room, $department);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        jsonResponse('error', 'Room already exists in this department');
    }
    $check->close();

    // Insert room
    $stmt = $db->prepare("INSERT INTO rooms (room, authorized_personnel, department, password, descr) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $room, $role, $department, $password, $descr);

    if ($stmt->execute()) {
        jsonResponse('success', 'Room added successfully');
    } else {
        jsonResponse('error', 'Failed to add room: ' . $db->error);
    }
    break;

case 'update_room':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['id'])) {
        jsonResponse('error', 'Room ID is required');
    }

    $required = ['roomdpt', 'roomrole', 'roomname', 'roomdesc', 'roompass'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            jsonResponse('error', "Missing required field: " . str_replace('room', '', $field));
        }
    }

    // Sanitize inputs
    $id = intval($_POST['id']);
    $department = sanitizeInput($db, $_POST['roomdpt']);
    $role = sanitizeInput($db, $_POST['roomrole']);
    $room = sanitizeInput($db, $_POST['roomname']);
    $descr = sanitizeInput($db, $_POST['roomdesc']);
    $password = sanitizeInput($db, $_POST['roompass']);

    // Validate ID
    if ($id <= 0) {
        jsonResponse('error', 'Invalid room ID');
    }

    // Validate lengths
    if (strlen($room) > 100) {
        jsonResponse('error', 'Room name must be less than 100 characters');
    }

    if (strlen($descr) > 255) {
        jsonResponse('error', 'Description must be less than 255 characters');
    }

    if (strlen($password) < 6) {
        jsonResponse('error', 'Password must be at least 6 characters');
    }

    // Check if room exists in department (excluding current room)
    $check = $db->prepare("SELECT id FROM rooms WHERE room = ? AND department = ? AND id != ?");
    $check->bind_param("ssi", $room, $department, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        jsonResponse('error', 'Room already exists in this department');
    }
    $check->close();

    // Update room
    $stmt = $db->prepare("UPDATE rooms SET room = ?, authorized_personnel = ?, department = ?, password = ?, descr = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $room, $role, $department, $password, $descr, $id);

    if ($stmt->execute()) {
        jsonResponse('success', 'Room updated successfully');
    } else {
        jsonResponse('error', 'Failed to update room: ' . $db->error);
    }
    break;

case 'delete_room':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse('error', 'Invalid request method');
    }

    // Validate required field
    if (empty($_POST['id'])) {
        jsonResponse('error', 'Room ID is required');
    }

    // Sanitize input
    $id = intval($_POST['id']);

    if ($id <= 0) {
        jsonResponse('error', 'Invalid room ID');
    }

    // Check if room exists first
    $checkRoom = $db->prepare("SELECT id FROM rooms WHERE id = ?");
    $checkRoom->bind_param("i", $id);
    $checkRoom->execute();
    $checkRoom->store_result();
    
    if ($checkRoom->num_rows === 0) {
        jsonResponse('error', 'Room not found');
    }
    $checkRoom->close();

    // Check for room dependencies (scheduled classes)
    $checkSchedules = $db->prepare("SELECT COUNT(*) FROM room_schedules WHERE room_name COLLATE utf8mb4_unicode_ci = (SELECT room COLLATE utf8mb4_unicode_ci FROM rooms WHERE id = ?)");
    $checkSchedules->bind_param("i", $id);
    $checkSchedules->execute();
    $checkSchedules->bind_result($scheduleCount);
    $checkSchedules->fetch();
    $checkSchedules->close();

    if ($scheduleCount > 0) {
        jsonResponse('error', 'Cannot delete room with scheduled classes');
    }

    // Delete room
    $stmt = $db->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Room deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete room: ' . $stmt->error);
    }
    break;
    
}

$db->close();
?>