<?php
// forgot_password.php
session_start();
include '../connection.php';
include '../security-headers.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Generate CSRF token
if (empty($_SESSION['forgot_csrf_token'])) {
    $_SESSION['forgot_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['forgot_csrf_token']) {
        $error = "Security token invalid. Please refresh the page.";
    } else {
        $email = trim($_POST['email']);
        $captcha = trim($_POST['captcha']);
        
        // Validate inputs
        if (empty($email) || empty($captcha)) {
            $error = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($email) > 255) {
            $error = "Email address is too long.";
        } else {
            // Verify CAPTCHA
            if (!isset($_SESSION['captcha']) || strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
                $error = "Invalid CAPTCHA code. Please try again.";
            } else {
                // Check if email exists in database
                try {
                    $stmt = $db->prepare("SELECT id, username FROM user WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        // Generate verification token
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        
                        // Store token in database
                        $stmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user['id'], $token, $expires);
                        
                        if ($stmt->execute()) {
                            // Send verification email
                            if (sendPasswordResetEmail($email, $token, $user['username'])) {
                                $_SESSION['reset_email'] = $email;
                                $_SESSION['reset_token_sent'] = true;
                                header('Location: reset_password.php');
                                exit();
                            } else {
                                $error = "Failed to send verification email. Please try again.";
                            }
                        } else {
                            $error = "Database error. Please try again.";
                        }
                    } else {
                        $error = "No account found with that email address.";
                    }
                } catch (Exception $e) {
                    error_log("Forgot password error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

// Function to send password reset email
function sendPasswordResetEmail($email, $token, $username) {
    // SMTP Configuration (Update these with your Gmail credentials)
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_username = 'joshuapastorpide10@gmail.com'; // Your Gmail address
    $smtp_password = 'bmnvognbjqcpxcyf'; // Your Gmail app password
    $from_email = 'joshuapastorpide10@gmail.com';
    $from_name = 'RFID GPMS Admin';
    
    
    // Create reset link
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $domain = $_SERVER['HTTP_HOST'];
    $reset_link = "$protocol://$domain/admin/reset_password.php?token=$token";
    
    // Email content
    $subject = "Password Reset Request - RFID GPMS Admin";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4e73df; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fc; }
            .button { background: #4e73df; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
            .footer { padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>RFID GPMS Admin Portal</h2>
            </div>
            <div class='content'>
                <h3>Hello $username,</h3>
                <p>You have requested to reset your password for the RFID GPMS Admin Portal.</p>
                <p>Click the button below to reset your password. This link will expire in 1 hour.</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$reset_link' class='button'>Reset Your Password</a>
                </p>
                <p>If the button doesn't work, copy and paste this link in your browser:</p>
                <p><code>$reset_link</code></p>
                <p>If you didn't request this password reset, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from_name <$from_email>" . "\r\n";
    $headers .= "Reply-To: $from_email" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // For better reliability, use PHPMailer (recommended)
    // But for quick testing, use the basic mail() function:
    return mail($email, $subject, $message, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RFID GPMS Admin</title>
    
    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:;">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    
    <style>
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
        
        .reset-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .reset-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
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
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .captcha-container {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 8px;
        }
        
        .captcha-image {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h3><i class="fas fa-key me-2"></i>RESET PASSWORD</h3>
            <p class="mb-0">Enter your email to reset your password</p>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['forgot_csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fas fa-envelope"></i>Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your registered email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <div class="captcha-container">
                    <label class="form-label mb-3"><i class="fas fa-shield-alt"></i>Security Verification</label>
                    <img src="captcha.php?<?php echo time(); ?>" alt="CAPTCHA" class="captcha-image" id="captchaImage">
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="refreshCaptcha()">
                        <i class="fas fa-redo"></i> Refresh CAPTCHA
                    </button>
                    <input type="text" class="form-control mt-3" name="captcha" placeholder="Enter CAPTCHA code" required maxlength="6" 
                        value="<?php echo isset($_POST['captcha']) ? htmlspecialchars($_POST['captcha']) : ''; ?>">
                    
                    <!-- Add this for better user experience -->
                    <small class="text-muted">Enter the code shown in the image above (case insensitive)</small>
                </div>

                <button type="submit" class="btn btn-reset mb-3">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>

            <div class="back-link">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captchaImage');
            // Add timestamp to prevent caching
            captchaImage.src = 'captcha.php?' + new Date().getTime();
            
            // Clear the CAPTCHA input field
            document.querySelector('input[name="captcha"]').value = '';
            
            // Show loading state
            captchaImage.style.opacity = '0.5';
            setTimeout(() => {
                captchaImage.style.opacity = '1';
            }, 300);
        }

        document.getElementById('resetForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            btn.disabled = true;
        });

    </script>
</body>
</html>