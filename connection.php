


<?php


$servername = "127.0.0.1";
$username = "1rfidUser2025";
$password = "u8027114156_rfidgpmsPass";
$dbname = "u8027114156_rfidgpms";


// Create connection
$db = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>



