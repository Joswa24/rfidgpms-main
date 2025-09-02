
// 
 // $mysql_hostname = "127.0.0.1";
// $mysql_user = "1rfidUser2025";
// $mysql_password = "u8027114156_rfidgpmsPass";
// $mysql_database = "u8027114156_rfidgpms";
// $bd = mysqli_connect($mysql_hostname, $mysql_user, $mysql_password, $mysql_database) or die("Could not connect database"); 


// $mysql_hostname = "localhost";
// $mysql_user = "root";
// $mysql_password = "";
// $mysql_database = "";
// $bd = mysqli_connect($mysql_hostname, $mysql_user, $mysql_password, $mysql_database) or die("Could not connect database");

    //
//date_default_timezone_set('Asia/Manila');
//
 // $db = mysqli_connect('127.0.0.1','u8027114156_rfidgpmsPass','1rfidUser2025',  'u8027114156_rfidgpmsPass') or

       // die ('Unable to connect. Check your connection parameters.');
       // mysqli_select_db($db, 'u8027114156_rfidgpms' ) or die(mysqli_error($db));

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



