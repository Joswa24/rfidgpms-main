<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'instructor';
header("Location: dashboard.php");
exit();