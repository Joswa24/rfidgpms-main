<?php
date_default_timezone_set('Asia/Manila');

$db = mysqli_connect('127.0.0.1','u802714156_rfidgpmsPass','1rfidUser2025','u802714156_rfidgpms') or
        die ('Unable to connect. Check your connection parameters.');

// After connecting to database in connection.php
mysqli_query($db, "SET time_zone = '+08:00'");
?>