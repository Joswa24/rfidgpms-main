
   <?php
date_default_timezone_set('Asia/Manila');

 $db = mysqli_connect('sql210.infinityfree.com','if0_39846067','','if0_39846067_gpassdb') or

        die ('Unable to connect. Check your connection parameters.');
        mysqli_select_db($db, 'if0_39846067_gpassdb' ) or die(mysqli_error($db));
?>
