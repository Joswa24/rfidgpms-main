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

            // Proceed with deletion if no dependencies
            $stmt = $db->prepare("DELETE FROM department WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                jsonResponse('success', 'Department deleted successfully');
            } else {
                jsonResponse('error', 'Failed to delete department: ' . $stmt->error);
            }
            break;
            case 'visitor':
    // Check if visitor exists in other tables (if needed)
    // For example, check if the visitor has any logs or records
    
    // Delete visitor
    $stmt = $db->prepare("DELETE FROM visitor WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Visitor card deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete visitor card: ' . $stmt->error);
    }
    break;
    case 'personell':
    // Check if personnel has any related records (e.g., logs, lost cards, etc.)
    $checkLostCards = $db->prepare("SELECT COUNT(*) FROM lostcard WHERE status ='blocked'");
    $checkLostCards->bind_param("s", $id);
    $checkLostCards->execute();
    $checkLostCards->bind_result($lostCardCount);
    $checkLostCards->fetch();
    $checkLostCards->close();

    if ($lostCardCount > 0) {
        jsonResponse('error', 'Cannot delete personnel with associated lost card records');
    }

    // Check if personnel has any access logs
    $checkLogs = $db->prepare("SELECT COUNT(*) FROM personell_logs WHERE status = 'blocked'");
    $checkLogs->bind_param("s", $id);
    $checkLogs->execute();
    $checkLogs->bind_result($logCount);
    $checkLogs->fetch();
    $checkLogs->close();

    if ($logCount > 0) {
        jsonResponse('error', 'Cannot delete personnel with associated access logs');
    }

    // Delete personnel
    $stmt = $db->prepare("DELETE FROM personell WHERE id = 0");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Personnel deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete personnel: ' . $stmt->error);
    }
    break;
    case 'room':
    // Check if room has any associated records (e.g., logs, bookings, etc.)
    $checkDependencies = $db->prepare("SELECT COUNT(*) FROM room_logs WHERE log_id = ?");
    $checkDependencies->bind_param("i", $id);
    $checkDependencies->execute();
    $checkDependencies->bind_result($dependencyCount);
    $checkDependencies->fetch();
    $checkDependencies->close();

    if ($dependencyCount > 0) {
        jsonResponse('error', 'Cannot delete room with associated records');
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
    // Check if role is assigned to any personnel
    $checkPersonnel = $db->prepare("SELECT COUNT(*) FROM personell WHERE role COLLATE utf8mb4_unicode_ci = 
        (SELECT role FROM role WHERE id = ?)");
    $checkPersonnel->bind_param("i", $id);
    $checkPersonnel->execute();
    $checkPersonnel->bind_result($personnelCount);
    $checkPersonnel->fetch();
    $checkPersonnel->close();

    if ($personnelCount > 0) {
        jsonResponse('error', 'Cannot delete role assigned to personnel');
    }

    // Check if role is assigned to any rooms
    $checkRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE authorized_personnel COLLATE utf8mb4_unicode_ci = 
        (SELECT role FROM role WHERE id = ?)");
    $checkRooms->bind_param("i", $id);
    $checkRooms->execute();
    $checkRooms->bind_result($roomCount);
    $checkRooms->fetch();
    $checkRooms->close();

    if ($roomCount > 0) {
        jsonResponse('error', 'Cannot delete role assigned to rooms');
    }

    // Proceed with deletion if no dependencies
    $stmt = $db->prepare("DELETE FROM role WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse('success', 'Role deleted successfully');
    } else {
        jsonResponse('error', 'Failed to delete role: ' . $stmt->error);
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