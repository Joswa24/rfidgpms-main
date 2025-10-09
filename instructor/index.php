<?php
// Start output buffering and session at the very top
ob_start();
session_start();

include '../connection.php';

// Initialize session variables for login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers - placed after session_start()
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Login configuration
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutes
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid request. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Check if user is currently locked out
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
            $errorMessage = "Too many failed attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
        } else {
            // Reset attempts if lockout period has expired
            if ((time() - $_SESSION['lockout_time']) >= $lockoutTime) {
                $_SESSION['login_attempts'] = 0;
            }

            // Validate inputs
            $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
            $password = trim($_POST['password']);

            if (!$db) {
                $errorMessage = "Database connection error. Please try again later.";
            } else {
                // FIXED QUERY: Join with instructor table to get fullname and department
                $stmt = $db->prepare("
                    SELECT ia.*, i.fullname, i.department_id, d.department_name 
                    FROM instructor_accounts ia 
                    INNER JOIN instructor i ON ia.instructor_id = i.id 
                    LEFT JOIN department d ON i.department_id = d.department_id 
                    WHERE ia.username = ?
                ");
                
                if ($stmt) {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();

                        // Verify password
                        if (password_verify($password, $user['password'])) {
                            // Successful login
                            $_SESSION['login_attempts'] = 0;

                            // Store session data
                            $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['instructor_id'] = (int)$user['instructor_id'];
                            $_SESSION['fullname'] = htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8');
                            $_SESSION['department'] = htmlspecialchars($user['department_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                            $_SESSION['role'] = 'instructor';
                            $_SESSION['logged_in'] = true;
                            $_SESSION['last_activity'] = time();
                            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                            // Update last login timestamp
                            $updateStmt = $db->prepare("UPDATE instructor_accounts SET last_login = NOW() WHERE instructor_id = ?");
                            $updateStmt->bind_param("i", $user['instructor_id']);
                            $updateStmt->execute();
                            $updateStmt->close();

                            session_regenerate_id(true);
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $errorMessage = "Invalid username or password";
                            $_SESSION['login_attempts']++;
                        }
                    } else {
                        $errorMessage = "Invalid username or password";
                        $_SESSION['login_attempts']++;
                    }
                    $stmt->close();
                } else {
                    $errorMessage = "Database error. Please try again later.";
                    error_log("Login prepare failed: " . $db->error);
                }
            }

            // Check if account should be locked
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['lockout_time'] = time();
                $errorMessage = "Too many failed attempts. Your account has been locked for 5 minutes.";
            }
        }

        // Regenerate CSRF token after processing
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <style>
        /* Your existing CSS styles remain the same */
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
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
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
        }
        
        .login-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .form-label i {
            margin-right: 8px;
            color: var(--accent-color);
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--accent-color);
        }
        
        .form-control {
            border: none;
            padding: 0.75rem 1rem;
            background-color: var(--light-bg);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background-color: white;
            box-shadow: none;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-text);
            cursor: pointer;
            z-index: 5;
            background: white;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .forgot-link {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .login-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        @media (max-width: 576px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-body {
                padding: 20px;
            }
            
            .login-header {
                padding: 20px;
            }
            
            .login-header h3 {
                font-size: 1.5rem;
            }
            .header-content {
            position: relative;
            z-index: 1;
            }

            .logo-title-wrapper {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }

            .header-logo {
                height: 1px;
                width: auto;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                border: 2px solid rgba(255, 255, 255, 0.5);
                background: rgba(255, 255, 255, 0.9);
                padding: 3px;
            }

            /* Ensure the logo displays even if there are path issues */
            .header-logo[src=""],
            .header-logo:not([src]) {
                display: none;
            }

            
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <!-- Logo and Title Wrapped Together -->
            <div class="header-content">
                <div class="logo-title-wrapper">
                    <img src="../uploads/it.png" alt="Institution Logo" class="header-logo" style="height: 120px; width: 150px;">
                    <h3><i class="fas fa-chalkboard-teacher me-2"></i>INSTRUCTOR LOGIN</h3>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="username" class="form-label"><i class="fas fa-user"></i>Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required autocomplete="off" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label"><i class="fas fa-lock"></i>Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                    </div>
                </div>

                <button type="submit" name="login" class="btn btn-login mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>

                <div class="login-footer">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    <div class="text-muted">© <?php echo date('Y'); ?></div>
                </div>
            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>