   <?php
date_default_timezone_set('Asia/Manila');

 $db = mysqli_connect('127.0.0.1','u8027114156_rfidgpmsPass','1rfidUser2025',  'u8027114156_rfidgpms') or

        die ('Unable to connect. Check your connection parameters.');
        mysqli_select_db($db, 'u8027114156_rfidgpms' ) or die(mysqli_error($db));
?>
