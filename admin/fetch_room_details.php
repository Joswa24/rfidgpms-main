<?php
include '../connection.php';

if (!isset($_GET['room_name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Room name not provided']);
    exit;
}

$roomName = $_GET['room_name'];
$stmt = $db->prepare("SELECT * FROM rooms WHERE room = ?");
$stmt->bind_param("s", $roomName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'data' => [
            'department' => $room['department'],
            'password' => $room['password'],
            'location' => $room['room']
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Room not found']);
}
?>