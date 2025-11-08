<?php
// admin/index.php
include '../connection.php';
include '../security-headers.php';

// Set secure session cookie parameters BEFORE starting the session
 $secure = isset($_SERVER['HTTPS']);
 $httponly = true;
 $samesite = 'Strict';

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);
} else {
    session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], $secure, $httponly);
}

session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Additional security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("X-Permitted-Cross-Domain-Policies: none");
header("Cross-Origin-Embedder-Policy: require-corp");
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Initialize variables
 $maxAttempts = 3;
 $lockoutTime = 30;
 $error = '';
 $success = '';
 $twoFactorRequired = false;
 $redirectRequired = false;

// Initialize session variables
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['2fa_verified']) && $_SESSION['2fa_verified'] === true) {
    header('Location: dashboard.php');
    exit();
}

// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa'])) {
    error_log("2FA verification process started");
    
    // Combine the 6 input fields into one code
    $verificationCode = '';
    for ($i = 1; $i <= 6; $i++) {
        $fieldName = "code_$i";
        $verificationCode .= isset($_POST[$fieldName]) ? trim($_POST[$fieldName]) : '';
    }
    
    error_log("2FA Verification Attempt - Code length: " . strlen($verificationCode));
    
    if (empty($verificationCode) || strlen($verificationCode) !== 6) {
        $error = "Please enter the complete 6-digit verification code.";
        $twoFactorRequired = true;
    } elseif (!ctype_digit($verificationCode)) {
        $error = "Invalid verification code format. Please enter only numbers.";
        $twoFactorRequired = true;
    } else {
        try {
            // Check if session variables exist
            if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_username']) || !isset($_SESSION['temp_email'])) {
                $error = "Session expired. Please login again.";
                $twoFactorRequired = false;
                // Clear any existing session data
                unset($_SESSION['temp_user_id'], $_SESSION['temp_username'], $_SESSION['temp_email'], $_SESSION['password_verified']);
            } else {
                $userId = $_SESSION['temp_user_id'];
                $username = $_SESSION['temp_username'];
                $email = $_SESSION['temp_email'];
                
                // Debug logging
                error_log("Verifying 2FA for user ID: $userId");
                
                // Check if verification code is valid
                $stmt = $db->prepare("SELECT id, admin_id, verification_code, expires_at FROM admin_2fa_codes WHERE admin_id = ? AND verification_code = ? AND is_used = 0 AND expires_at > NOW()");
                $stmt->bind_param("is", $userId, $verificationCode);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $codeData = $result->fetch_assoc();
                    $codeId = $codeData['id'];
                    
                    // Mark code as used
                    $stmt = $db->prepare("UPDATE admin_2fa_codes SET is_used = 1, used_at = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $codeId);
                    
                    if ($stmt->execute()) {
                        // Log successful 2FA verification
                        logAccessAttempt($userId, $username, '2FA Verification', 'success');
                        
                        // Set a flag to redirect after output
                        $redirectRequired = true;
                        
                        // Set session variables for the redirect
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['logged_in'] = true;
                        $_SESSION['2fa_verified'] = true;
                        $_SESSION['login_time'] = time();
                        
                        // Clear temporary session variables
                        unset($_SESSION['temp_user_id']);
                        unset($_SESSION['temp_username']);
                        unset($_SESSION['temp_email']);
                        unset($_SESSION['password_verified']);
                        
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        // Set success message for dashboard
                        $_SESSION['success_message'] = "Login successful! Welcome, " . htmlspecialchars($username);
                        
                        error_log("2FA successful - Will redirect to dashboard for user: $username");
                    } else {
                        throw new Exception("Failed to mark 2FA code as used");
                    }
                    
                } else {
                    $error = "Invalid verification code. Please try again.";
                    $twoFactorRequired = true;
                    
                    // Log failed 2FA attempt
                    logAccessAttempt($userId, $username, 'Failed 2FA - Invalid Code', 'failed');
                }
            }
        } catch (Exception $e) {
            error_log("2FA verification error: " . $e->getMessage());
            $error = "Database error. Please try again.";
            $twoFactorRequired = true;
        }
    }
}

// Handle resend 2FA code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_2fa'])) {
    try {
        if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
            $error = "Session expired. Please login again.";
        } else {
            $userId = $_SESSION['temp_user_id'];
            $email = $_SESSION['temp_email'];
            
            // Generate and send new 2FA code
            $verificationCode = generate2FACode($userId, $email);
            
            if ($verificationCode) {
                $success = "A new verification code has been sent to your email.";
                $twoFactorRequired = true;
            } else {
                $error = "Failed to send verification code. Please try again.";
                $twoFactorRequired = true;
            }
        }
    } catch (Exception $e) {
        error_log("Error resending 2FA code: " . $e->getMessage());
        $error = "Error sending verification code. Please try again.";
        $twoFactorRequired = true;
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    error_log("Login form submitted");
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token invalid. Please refresh the page.";
    } else {
        // Check lockout
        if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime) {
            $remainingTime = $lockoutTime - (time() - $_SESSION['lockout_time']);
            $error = "Too many failed attempts. Please wait " . $remainingTime . " seconds before trying again.";
        } else {
            // Reset attempts if lockout expired
            if ((time() - $_SESSION['lockout_time']) >= $lockoutTime && $_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;
            }

            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            // Input validation
            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
            } elseif (strlen($username) > 50 || strlen($password) > 255) {
                $error = "Invalid input length.";
            } else {
                try {
                    $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
                    if (!$stmt) {
                        throw new Exception("Database error: " . $db->error);
                    }
                    
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password'])) {
                            // Log successful login
                            logAccessAttempt($user['id'], $user['username'], 'Login', 'success');
                            
                            // Reset login attempts
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['lockout_time'] = 0;
                            
                            // Store user info in session for 2FA verification
                            $_SESSION['temp_user_id'] = $user['id'];
                            $_SESSION['temp_username'] = $user['username'];
                            $_SESSION['temp_email'] = $user['email'];
                            $_SESSION['password_verified'] = true;
                            
                            error_log("Password verified for user: $username, generating 2FA code");
                            
                            // Generate and send 2FA code
                            $verificationCode = generate2FACode($user['id'], $user['email']);
                            
                            if ($verificationCode) {
                                $twoFactorRequired = true;
                                $success = "Verification code sent to your email.";
                                error_log("2FA code generated successfully, setting twoFactorRequired to true");
                            } else {
                                $error = "Failed to send verification code. Please try again.";
                                error_log("Failed to generate 2FA code");
                            }
                        } else {
                            // Log failed login attempt
                            logAccessAttempt(0, $username, 'Failed Login', 'failed');
                            
                            $_SESSION['login_attempts']++;
                            $attemptsLeft = $maxAttempts - $_SESSION['login_attempts'];
                            if ($attemptsLeft > 0) {
                                $error = "Invalid username or password. Attempts remaining: " . $attemptsLeft;
                            } else {
                                $_SESSION['lockout_time'] = time();
                                $error = "Too many failed attempts. Please wait 30 seconds before trying again.";
                            }
                        }
                    } else {
                        // Log failed login attempt
                        logAccessAttempt(0, $username, 'Failed Login', 'failed');
                        
                        $_SESSION['login_attempts']++;
                        $attemptsLeft = $maxAttempts - $_SESSION['login_attempts'];
                        if ($attemptsLeft > 0) {
                            $error = "Invalid username or password. Attempts remaining: " . $attemptsLeft;
                        } else {
                            $_SESSION['lockout_time'] = time();
                            $error = "Too many failed attempts. Please wait 30 seconds before trying again.";
                        }
                    }
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

// Function to log access attempts
function logAccessAttempt($userId, $username, $activity, $status) {
    global $db;
    
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $location = 'Unknown';
        
        // Get location information
        if (function_exists('file_get_contents') && !in_array($ipAddress, ['127.0.0.1', '::1'])) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $ipData = @file_get_contents("http://ip-api.com/json/{$ipAddress}", false, $context);
            if ($ipData) {
                $ipInfo = json_decode($ipData);
                if ($ipInfo && $ipInfo->status === 'success') {
                    $location = $ipInfo->city . ', ' . $ipInfo->regionName . ', ' . $ipInfo->country;
                }
            }
        }
        
        $stmt = $db->prepare("INSERT INTO admin_access_logs (admin_id, username, login_time, ip_address, user_agent, location, activity, status) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issssss", $userId, $username, $ipAddress, $userAgent, $location, $activity, $status);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Failed to log access attempt: " . $e->getMessage());
    }
}

// Function to generate and send 2FA code
function generate2FACode($userId, $email) {
    global $db;
    
    try {
        // Generate a 6-digit verification code
        $verificationCode = sprintf('%06d', mt_rand(0, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        error_log("Generating 2FA code for user ID: $userId, email: $email");
        
        // Delete any existing codes for this user
        $stmt = $db->prepare("DELETE FROM admin_2fa_codes WHERE admin_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare DELETE statement: " . $db->error);
        }
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete old 2FA codes: " . $stmt->error);
        }
        $stmt->close();
        
        // Insert new verification code
        $stmt = $db->prepare("INSERT INTO admin_2fa_codes (admin_id, verification_code, expires_at) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare INSERT statement: " . $db->error);
        }
        
        $stmt->bind_param("iss", $userId, $verificationCode, $expiresAt);
        
        if ($stmt->execute()) {
            error_log("2FA code generated successfully for user ID: $userId");
            $stmt->close();
            
            // Send the code via email
            if (send2FACodeEmail($email, $verificationCode)) {
                error_log("2FA code sent successfully to: $email");
                return $verificationCode;
            } else {
                error_log("Failed to send 2FA code via email to: $email");
                // Even if email fails, we still generated the code
                return $verificationCode;
            }
        } else {
            throw new Exception("Failed to insert 2FA code: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error generating 2FA code: " . $e->getMessage());
        return false;
    }
}

// Function to send 2FA code via email
function send2FACodeEmail($email, $verificationCode) {
    try {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: $email");
            return false;
        }

        // Load PHPMailer with correct relative paths
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuapastorpide10@gmail.com';
        $mail->Password = 'ydadaipirzhyhzae';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        
        // Enable verbose debugging for troubleshooting
        $mail->SMTPDebug = 0; // Set to 2 for detailed debugging
        
        // SSL context options for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Character set
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom('joshuapastorpide10@gmail.com', 'RFID GPMS Admin');
        $mail->addAddress($email);
        $mail->addReplyTo('joshuapastorpide10@gmail.com', 'RFID GPMS Admin');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Two-Factor Authentication Code - RFID GPMS';
        
        // Remove X-Mailer header for security
        $mail->XMailer = ' ';
        
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    background-color: #f4f4f4; 
                    line-height: 1.6;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white; 
                    border-radius: 10px; 
                    overflow: hidden; 
                    box-shadow: 0 0 10px rgba(0,0,0,0.1); 
                }
                .header { 
                    background: #4e73df; 
                    color: white; 
                    padding: 20px; 
                    text-align: center; 
                }
                .content { 
                    padding: 30px; 
                }
                .code { 
                    background: #e1e7f0; 
                    padding: 15px; 
                    text-align: center; 
                    font-size: 24px; 
                    font-weight: bold; 
                    letter-spacing: 5px; 
                    margin: 20px 0; 
                    border-radius: 5px; 
                    font-family: monospace; 
                    color: #2c3e50;
                }
                .footer { 
                    padding: 20px; 
                    text-align: center; 
                    color: #6c757d; 
                    font-size: 12px; 
                    background: #f8f9fa; 
                }
                .warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 10px;
                    margin: 15px 0;
                    color: #856404;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RFID GPMS Admin Portal</h2>
                </div>
                <div class='content'>
                    <h3>Two-Factor Authentication Required</h3>
                    <p>Hello,</p>
                    <p>Your verification code for the RFID GPMS Admin Portal is:</p>
                    <div class='code'>$verificationCode</div>
                    <div class='warning'>
                        <strong>Security Notice:</strong> This code will expire in 10 minutes. Do not share this code with anyone.
                    </div>
                    <p>If you did not request this code, please ignore this email or contact system administrator.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " RFID GPMS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "RFID GPMS - Two-Factor Authentication\n\nYour verification code is: $verificationCode\n\nThis code will expire in 10 minutes.\n\nDo not share this code with anyone.\n\nIf you did not request this code, please ignore this email.";
        
        // Add small delay to avoid rate limiting
        usleep(500000);
        
        if ($mail->send()) {
            error_log("SUCCESS: 2FA code sent to: $email");
            return true;
        } else {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
        
    } catch (Exception $e) {
        error_log("EXCEPTION in send2FACodeEmail: " . $e->getMessage());
        return false;
    }
}

// Check if user is currently locked out
 $isLockedOut = ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['lockout_time']) < $lockoutTime);
 $remainingLockoutTime = $isLockedOut ? ($lockoutTime - (time() - $_SESSION['lockout_time'])) : 0;

// Debug logging
error_log("Final state - twoFactorRequired: " . ($twoFactorRequired ? 'true' : 'false'));
error_log("Final state - error: " . $error);
error_log("Final state - success: " . $success);
error_log("Final state - redirectRequired: " . ($redirectRequired ? 'true' : 'false'));

// If redirect is required, do it now before any output
if ($redirectRequired) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RFID System</title>
    
    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="description" content="Gate and Personnel Management System">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
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
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .form-check-input:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .system-info {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e3e6f0;
            font-size: 0.85rem;
            color: var(--dark-text);
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning-color);
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
        
        .lockout-message {
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
            display: none;
        }
        
        .lockout-message.show {
            display: block;
        }
        
        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--danger-color);
            margin: 10px 0;
        }
        
        .attempts-warning {
            font-size: 0.9rem;
            color: var(--warning-color);
            font-weight: 600;
            margin-top: 10px;
        }
        
        .forgot-password-section {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e3e6f0;
        }
        
        .forgot-password-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .forgot-password-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
            transform: translateX(5px);
        }
        
        .forgot-password-link i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .forgot-password-link:hover i {
            transform: translateX(-3px);
        }
        
        /* 2FA Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .verification-code-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .verification-code-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 8px;
            border: 2px solid var(--light-bg);
            background-color: var(--light-bg);
            transition: all 0.3s ease;
        }
        
        .verification-code-input:focus {
            border-color: var(--accent-color);
            background-color: white;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            outline: none;
        }
        
        .verification-code-input.error {
            border-color: var(--danger-color);
            background-color: #fff5f5;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .btn-verify {
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
        
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .btn-resend {
            background: transparent;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-resend:hover:not(:disabled) {
            background-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .info-box {
            background-color: var(--light-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-box i {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .validation-message {
            font-size: 0.85rem;
            margin-top: 5px;
            color: var(--danger-color);
            display: none;
        }
        
        .validation-message.show {
            display: block;
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
            
            .logo-title-wrapper {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
            }

            .header-logo {
                height: 35px;
                width: auto;
                border-radius: 6px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
                border: 2px solid rgba(255, 255, 255, 0.4);
                background: rgba(255, 255, 255, 0.9);
                padding: 2px;
            }

            .verification-code-input {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="header-content">
                <div class="logo-title-wrapper">
                    <img src="../uploads/it.png" alt="Institution Logo" class="header-logo" style="height: 120px; width: 150px;">
                    <h3><i class="fas fa-user-shield me-2"></i>ADMIN LOGIN</h3>
                </div>
            </div>
        </div>
        
        <div class="login-body">
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Lockout Warning -->
            <div class="alert alert-warning <?php echo $isLockedOut ? '' : 'd-none'; ?>" id="lockoutAlert">
                <i class="fas fa-clock me-2"></i>
                <strong>Account Temporarily Locked</strong>
                <div class="countdown-timer" id="countdown">
                    <?php echo $remainingLockoutTime; ?> seconds
                </div>
                <div class="attempts-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Too many failed login attempts. Please wait until the timer expires.
                </div>
            </div>

            <form method="POST" id="loginForm" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login" value="1">

                <div class="form-group">
                    <label for="username" class="form-label"><i class="fas fa-user"></i>Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                            placeholder="Enter your username" required autocomplete="username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label"><i class="fas fa-lock"></i>Password</label>
                    <div class="input-group password-field">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                            placeholder="Enter your password" required
                            autocomplete="current-password"
                            <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <!-- Attempts Counter -->
                <div class="attempts-counter mb-3 text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Attempts: <span id="attemptsCount"><?php echo $_SESSION['login_attempts']; ?></span>/<?php echo $maxAttempts; ?>
                    </small>
                </div>

                <button type="submit" class="btn btn-login mb-3" id="loginBtn" <?php echo $isLockedOut ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt me-2"></i>
                    <span id="loginText"><?php echo $isLockedOut ? 'Account Locked' : 'Sign In'; ?></span>
                    <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                </button>
            </form>

            <!-- Forgot Password Section -->
            <div class="forgot-password-section">
                <a href="forgot_password.php" class="forgot-password-link">
                    <i class="fas fa-key"></i>
                    Forgot Password?
                </a>
            </div>
        </div>
    </div>

    <!-- 2FA Verification Modal -->
    <div class="modal fade" id="twoFactorModal" tabindex="-1" aria-labelledby="twoFactorModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="twoFactorModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Two-Factor Authentication
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Error Message -->
                    <div class="alert alert-danger <?php echo !empty($error) && $twoFactorRequired ? '' : 'd-none'; ?>" id="modalError">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="modalErrorText"><?php echo !empty($error) ? htmlspecialchars($error) : ''; ?></span>
                    </div>
                    
                    <!-- Success Message -->
                    <div class="alert alert-success <?php echo !empty($success) && $twoFactorRequired ? '' : 'd-none'; ?>" id="modalSuccess">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="modalSuccessText"><?php echo !empty($success) ? htmlspecialchars($success) : ''; ?></span>
                    </div>

                    <!-- Info Box -->
                    <div class="info-box">
                        <i class="fas fa-envelope"></i>
                        <p>A verification code has been sent to your email address: <strong><?php echo isset($_SESSION['temp_email']) ? htmlspecialchars($_SESSION['temp_email']) : ''; ?></strong></p>
                        <p class="mb-0">The code will expire in 10 minutes.</p>
                    </div>

                    <form method="POST" id="twoFactorForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="verification_code" class="form-label"><i class="fas fa-key"></i>Verification Code</label>
                            <div class="verification-code-container">
                                <input type="text" class="form-control verification-code-input" id="code_1" name="code_1" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                                <input type="text" class="form-control verification-code-input" id="code_2" name="code_2" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                                <input type="text" class="form-control verification-code-input" id="code_3" name="code_3" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                                <input type="text" class="form-control verification-code-input" id="code_4" name="code_4" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                                <input type="text" class="form-control verification-code-input" id="code_5" name="code_5" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                                <input type="text" class="form-control verification-code-input" id="code_6" name="code_6" 
                                    maxlength="1" pattern="[0-9]" autocomplete="one-time-code" required>
                            </div>
                            <div class="validation-message" id="codeValidationMessage">
                                Please enter a complete 6-digit verification code.
                            </div>
                        </div>

                        <button type="submit" name="verify_2fa" class="btn btn-verify mb-3" id="verifyBtn">
                            <i class="fas fa-check-circle me-2"></i>
                            <span id="verifyText">Verify Code</span>
                            <span id="verifySpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                        </button>
                    </form>

                    <!-- Resend Code Form -->
                    <form method="POST" id="resendForm" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="resend_2fa" class="btn btn-resend" id="resendBtn">
                            <i class="fas fa-redo me-2"></i>
                            <span id="resendText">Resend Code</span>
                            <span id="resendSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const eyeIcon = document.querySelector('.password-toggle i');
            
            if (passwordField.disabled) return;
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            
            if (isLockedOut) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            
            loginText.textContent = 'Authenticating...';
            loginSpinner.classList.remove('d-none');
            loginBtn.disabled = true;
        });

        // 2FA verification handling
        function setup2FAVerification() {
            const codeInputs = document.querySelectorAll('.verification-code-input');
            const twoFactorForm = document.getElementById('twoFactorForm');
            const validationMessage = document.getElementById('codeValidationMessage');
            
            // Auto-advance to next input
            codeInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    hideModalAlerts();
                    validationMessage.classList.remove('show');
                    this.classList.remove('error');
                    
                    if (this.value.length === 1 && index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                });
                
                input.addEventListener('keydown', function(e) {
                    // Allow only numbers and control keys
                    if (!/^\d$/.test(e.key) && 
                        !['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
                        e.preventDefault();
                    }
                    
                    // Handle backspace
                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        codeInputs[index - 1].focus();
                    }
                });
            });
            
            // Form submission - Let the form submit normally without preventing default
            twoFactorForm.addEventListener('submit', function(e) {
                // Check if all fields are filled
                const allFilled = Array.from(codeInputs).every(input => input.value.length === 1);
                
                if (!allFilled) {
                    e.preventDefault(); // Only prevent default if validation fails
                    validationMessage.classList.add('show');
                    codeInputs.forEach(input => {
                        if (input.value.length === 0) {
                            input.classList.add('error');
                        }
                    });
                    return;
                }
                
                // Show loading state
                const verifyBtn = document.getElementById('verifyBtn');
                const verifyText = document.getElementById('verifyText');
                const verifySpinner = document.getElementById('verifySpinner');
                
                verifyText.textContent = 'Verifying...';
                verifySpinner.classList.remove('d-none');
                verifyBtn.disabled = true;
                
                // Let the form submit normally
                // No need to call this.submit() since we're not preventing default
            });
        }

        // Resend Code Form submission
        document.getElementById('resendForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const resendBtn = document.getElementById('resendBtn');
            const resendText = document.getElementById('resendText');
            const resendSpinner = document.getElementById('resendSpinner');
            
            resendText.textContent = 'Sending...';
            resendSpinner.classList.remove('d-none');
            resendBtn.disabled = true;
            
            // Submit the form
            setTimeout(() => {
                this.submit();
            }, 500);
        });

        // Show/hide modal alerts
        function showModalError(message) {
            const modalError = document.getElementById('modalError');
            const modalErrorText = document.getElementById('modalErrorText');
            
            modalErrorText.textContent = message;
            modalError.classList.remove('d-none');
        }

        function showModalSuccess(message) {
            const modalSuccess = document.getElementById('modalSuccess');
            const modalSuccessText = document.getElementById('modalSuccessText');
            
            modalSuccessText.textContent = message;
            modalSuccess.classList.remove('d-none');
        }

        function hideModalAlerts() {
            document.getElementById('modalError').classList.add('d-none');
            document.getElementById('modalSuccess').classList.add('d-none');
        }

        // Countdown timer for lockout
        function startCountdown(duration) {
            const countdownElement = document.getElementById('countdown');
            const loginForm = document.getElementById('loginForm');
            const inputs = loginForm.querySelectorAll('input, button');
            const lockoutAlert = document.getElementById('lockoutAlert');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            
            let timer = duration;
            
            const interval = setInterval(() => {
                countdownElement.textContent = timer + ' seconds';
                
                if (--timer < 0) {
                    clearInterval(interval);
                    lockoutAlert.classList.add('d-none');
                    
                    // Enable form elements
                    inputs.forEach(input => {
                        if (input.type !== 'hidden') {
                            input.disabled = false;
                        }
                    });
                    
                    loginBtn.disabled = false;
                    loginText.textContent = 'Sign In';
                    
                    // Reset attempts counter display
                    document.getElementById('attemptsCount').textContent = '0';
                }
            }, 1000);
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setup2FAVerification();
            
            // Initialize lockout if needed
            <?php if ($isLockedOut): ?>
                const remainingTime = <?php echo $remainingLockoutTime; ?>;
                startCountdown(remainingTime);
            <?php endif; ?>

            // Show 2FA modal if required
            <?php if ($twoFactorRequired): ?>
                console.log("JavaScript: Showing 2FA modal - twoFactorRequired is true");
                const twoFactorModal = new bootstrap.Modal(document.getElementById('twoFactorModal'));
                twoFactorModal.show();
                
                // Auto-focus on first verification code input
                setTimeout(() => {
                    const firstInput = document.getElementById('code_1');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 500);
            <?php else: ?>
                console.log("JavaScript: twoFactorRequired is false - modal will not show");
            <?php endif; ?>

            // Auto-focus on username field if not locked out and not showing 2FA
            const isLockedOut = <?php echo $isLockedOut ? 'true' : 'false'; ?>;
            const show2FA = <?php echo $twoFactorRequired ? 'true' : 'false'; ?>;
            
            if (!isLockedOut && !show2FA) {
                document.getElementById('username').focus();
            }
        });
    </script>
</body>
</html>.