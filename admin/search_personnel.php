<?php
include '../connection.php';

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    $searchTerm = "%" . $query . "%";
    
    // Search in instructor_glogs table
    $sql1 = "SELECT DISTINCT instructor_id as id, name, 'instructor' as type 
             FROM instructor_glogs 
             WHERE name LIKE ?";
    
    // Search in personell_glogs table  
    $sql2 = "SELECT DISTINCT personell_id as id, name, 'personell' as type 
             FROM personell_glogs 
             WHERE name LIKE ?";
    
    $stmt1 = $db->prepare($sql1);
    $stmt1->bind_param("s", $searchTerm);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->bind_param("s", $searchTerm);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    $results = [];
    
    // Add instructors to results
    while ($row = $result1->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'fullname' => $row['name'],
            'type' => $row['type']
        ];
    }
    
    // Add personnel to results
    while ($row = $result2->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'fullname' => $row['name'],
            'type' => $row['type']
        ];
    }
    
    $stmt1->close();
    $stmt2->close();
    $db->close();
    
    header('Content-Type: application/json');
    echo json_encode($results);
} else {
    echo json_encode([]);
}
?>