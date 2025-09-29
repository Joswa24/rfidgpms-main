<?php
session_start();
include '../connection.php';

header('Content-Type: application/json');

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    
    // SQL query to fetch instructors from instructor table
    $sql = "SELECT id, fullname 
            FROM instructor 
            WHERE fullname LIKE ? 
            LIMIT 10"; // Limit results to 10
    
    $stmt = $db->prepare($sql);
    $searchTerm = "%" . $query . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $instructors = [];
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }
    
    $stmt->close();
    $db->close();
    
    echo json_encode($instructors);
} else {
    echo json_encode(['error' => 'No query provided']);
}
?>