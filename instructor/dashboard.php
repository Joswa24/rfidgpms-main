<?php
// At the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (ob_get_level() == 0) {
    ob_start();
}

session_start();

// Debug: Check what's in session
echo "<!-- Session data: " . json_encode($_SESSION) . " -->";

// Simple access check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Clear buffer and redirect
    ob_end_clean();
    header("Location: index.php");
    exit();
}

// If we get here, user is logged in
ob_end_clean(); // Clear any buffered output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>SUCCESS! You are logged in.</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>!</p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>