<?php
include '../connection.php';

// Create the instructor_holidays table if it doesn't exist
 $sql = "CREATE TABLE IF NOT EXISTS instructor_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT NOT NULL,
    month VARCHAR(20) NOT NULL,
    day INT NOT NULL,
    year INT NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES instructor(id) ON DELETE CASCADE
)";

if ($db->query($sql) === TRUE) {
    echo "Table instructor_holidays created successfully";
} else {
    echo "Error creating table: " . $db->error;
}

 $db->close();
?>