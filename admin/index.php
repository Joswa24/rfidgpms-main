<?php
include '../connection.php';
session_start();

// Security headers
header("Content-Security-Policy: default-src 'self'");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Initialize variables for login attempts
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutes in seconds
$errorMessage = '';

// Initialize session variables if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Check if user is currently locked out
    if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
        $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
        $errorMessage = "Too many failed attempts. Please wait " . ceil($remainingTime / 60) . " minutes before trying again.";
    } else {
        // Reset attempts if lockout period has expired
        if ((time() - $_SESSION['lockout_time']) >= $lockoutTime) {
            $_SESSION['login_attempts'] = 0;
        }

        // Validate and sanitize inputs
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        
        // Check credentials in database
        $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['login_attempts'] = 0;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                // Password verification failed
                $errorMessage = "Invalid username or password";
                $_SESSION['login_attempts']++;
            }
        } else {
            // User not found
            $errorMessage = "Invalid username or password";
            $_SESSION['login_attempts']++;
        }
        
        // Check if account should be locked
        if ($_SESSION['login_attempts'] >= $maxAttempts) {
            $_SESSION['lockout_time'] = time();
            $errorMessage = "Too many failed attempts. Your account has been locked for 5 minutes.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <!-- Add SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .terms-link {
            padding-left: 65%;
            font-size: 12px;
            color: gray;
            text-decoration: none;
            cursor: pointer;
        }
        .terms-link:hover {
            text-decoration: underline;
            color: black;
        }
        #lockout-message {
            display: none;
            margin-top: 15px;
        }
        .login-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <div class="container-fluid">
        <div class="row h-100 align-items-center justify-content-center" style="min-height: 100vh;">
            <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <div class="bg-light rounded p-4 p-sm-5 my-4 mx-3 login-container">
                    <form id="logform" method="POST">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h3 class="text-warning">ADMIN</h3>
                            <h3>Sign In</h3>
                        </div>

                        <div class="form-floating mb-3">
                            <input id="uname" type="text" class="form-control" name="username" placeholder="Username" autocomplete="off" required>
                            <label for="uname">Username</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input id="password" type="password" class="form-control" name="password" placeholder="Password" autocomplete="off" required>
                            <label for="password">Password</label>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="form-check">
                                <input type="checkbox" id="remember" class="form-check-input" onclick="togglePasswordVisibility()">
                                <label class="form-check-label" for="remember">Show Password</label>
                            </div>
                            <a class="terms-link" href="forgot_password.php">Forgot Password?</a>
                        </div>
                        
                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                        <button type="submit" id="loginBtn" name="login" class="btn btn-warning py-3 w-100 mb-4">
                            <span id="loginText">Sign In</span>
                            <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>

                       
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include required libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle password visibility
    function togglePasswordVisibility() {
        const passwordField = document.getElementById("password");
        passwordField.type = passwordField.type === "password" ? "text" : "password";
    }

    // Handle form submission with AJAX and SweetAlerts
    document.getElementById('logform').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const loginBtn = document.getElementById('loginBtn');
        const loginText = document.getElementById('loginText');
        const loginSpinner = document.getElementById('loginSpinner');
        
        loginText.textContent = 'Authenticating...';
        loginSpinner.classList.remove('d-none');
        loginBtn.disabled = true;
        
        // Submit form via AJAX
        const formData = new FormData(this);
        
        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    timer: 2000,
                    timerProgressBar: true
                }).then(() => {
                    window.location.href = data.redirect;
                });
            } else if (data.status === 'verification') {
                window.location.href = data.redirect;
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                
                // Update lockout message if needed
                if (data.message.includes('locked')) {
                    startCountdown(<?php echo $lockoutTime; ?>);
                }
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'An unexpected error occurred. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        })
        .finally(() => {
            // Reset button state
            loginText.textContent = 'Sign In';
            loginSpinner.classList.add('d-none');
            loginBtn.disabled = false;
        });
    });

    // Countdown timer for lockout
    function startCountdown(duration) {
        const lockoutMessage = document.getElementById('lockout-message');
        const countdownElement = document.getElementById('countdown');
        const form = document.getElementById('logform');
        const inputs = form.querySelectorAll('input, button');
        
        lockoutMessage.style.display = 'block';
        let timer = duration;
        
        // Disable form elements
        inputs.forEach(input => {
            if (input.type !== 'hidden') {
                input.disabled = true;
            }
        });
        
        const interval = setInterval(() => {
            const minutes = Math.floor(timer / 60);
            let seconds = timer % 60;
            
            seconds = seconds < 10 ? '0' + seconds : seconds;
            countdownElement.textContent = `${minutes}:${seconds}`;
            
            if (--timer < 0) {
                clearInterval(interval);
                lockoutMessage.style.display = 'none';
                
                // Enable form elements
                inputs.forEach(input => {
                    input.disabled = false;
                });
            }
        }, 1000);
    }

    // Initialize lockout if needed
    <?php if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime): ?>
        const remainingTime = <?php echo $lockoutTime - (time() - $_SESSION['lockout_time']); ?>;
        startCountdown(remainingTime);
    <?php endif; ?>

    // Security: Disable right-click and developer tools
    document.addEventListener('contextmenu', (e) => e.preventDefault());
    
    document.onkeydown = function(e) {
        if (e.keyCode === 123 || // F12
            (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
            (e.ctrlKey && e.shiftKey && e.keyCode === 74) || // Ctrl+Shift+J
            (e.ctrlKey && e.shiftKey && e.keyCode === 67) || // Ctrl+Shift+C
            (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
            e.preventDefault();
            Swal.fire({
                title: 'Restricted Action',
                text: 'This action is not allowed.',
                icon: 'warning'
            });
        }
    };
</script>
</body>
</html>