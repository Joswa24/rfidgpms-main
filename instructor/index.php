<?php
// At the VERY TOP - before any output
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
if (ob_get_level() == 0) {
    ob_start();
}

// Session configuration
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Simple validation
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $errorMessage = "Please enter both username and password";
    } else {
        include '../connection.php';
        
        if ($db) {
            $stmt = $db->prepare("SELECT * FROM instructor_accounts WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($password, $user['password'])) {
                        // Successful login - set minimal session data
                        $_SESSION['logged_in'] = true;
                        $_SESSION['role'] = 'instructor';
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['instructor_id'] = $user['instructor_id'];
                        $_SESSION['fullname'] = $user['fullname'];
                        
                        // Clear output buffer and redirect
                        ob_end_clean();
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $errorMessage = "Invalid username or password";
                    }
                } else {
                    $errorMessage = "Invalid username or password";
                }
                $stmt->close();
            } else {
                $errorMessage = "Database error";
            }
        } else {
            $errorMessage = "Database connection failed";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Login - RFID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3><i class="fas fa-chalkboard-teacher me-2"></i>INSTRUCTOR LOGIN</h3>
            <p>RFID Attendance System V2.0</p>
        </div>
        <div class="login-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" name="login" class="btn btn-login mb-3">Login</button>
            </form>
        </div>
    </div>
</body>
</html>