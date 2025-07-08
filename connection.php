 <?php
date_default_timezone_set('Asia/Manila');
 $db = mysqli_connect('localhost','root', '', 'gpassdb') or
        die ('Unable to connect. Check your connection parameters.');
        mysqli_select_db($db, 'gpassdb' ) or die(mysqli_error($db));
?>
