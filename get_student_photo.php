<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Function to get student photo path (same as in students.php but adjusted for scanner context)
function getStudentPhoto($photo) {
    $basePath = 'uploads/students/';
    $defaultPhoto = 'assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

try {
    // Fetch all students with their photos
    $query = "SELECT id_number, photo FROM students WHERE photo IS NOT NULL AND photo != ''";
    $result = mysqli_query($db, $query);
    
    $photos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $photos[$row['id_number']] = getStudentPhoto($row['photo']);
    }
    
    echo json_encode([
        'success' => true,
        'photos' => $photos,
        'count' => count($photos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'photos' => []
    ]);
}

exit;
?>