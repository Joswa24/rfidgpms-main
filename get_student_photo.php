<?php
include 'connection.php';

if(isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    $sql = "SELECT photo FROM students WHERE id_number = '$student_id'";
    $result = mysqli_query($db, $sql);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode(['photo' => $row['photo']]);
    } else {
        echo json_encode(['photo' => 'default.png']);
    }
} else {
    echo json_encode(['photo' => 'default.png']);
}
?>