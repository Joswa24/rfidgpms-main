<?php
include '../connection.php';

// Check if query parameter is set
if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    
    // Prepare SQL statement to search for instructors
    $sql = "SELECT id, fullname 
            FROM instructor 
            WHERE fullname LIKE ? 
            ORDER BY fullname 
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $searchTerm = "%" . $query . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $instructors = [];
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($instructors);
    
    $stmt->close();
} else {
    // Return error if no query provided
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No query provided']);
}

 $db->close();
?>