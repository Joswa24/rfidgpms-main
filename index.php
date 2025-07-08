<?php
session_start();

include 'connection.php';

// =====================================================================
// MAINTENANCE TASKS - Improved with prepared statements
// =====================================================================
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Update personell_logs with parameterized queries
$sql = "SELECT id, time_in_am, time_in_pm, time_out_am, time_out_pm 
        FROM personell_logs 
        WHERE DATE(date_logged) = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("s", $yesterday);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $updates = [];
    $params = [];
    $types = '';
    
    if (empty($row['time_in_am'])) {
        $updates[] = "time_in_am = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_in_pm'])) {
        $updates[] = "time_in_pm = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_out_am'])) {
        $updates[] = "time_out_am = ?";
        $params[] = '?';
        $types .= 's';
    }
    if (empty($row['time_out_pm'])) {
        $updates[] = "time_out_pm = ?";
        $params[] = '?';
        $types .= 's';
    }
    
    if (!empty($updates)) {
        $updateSql = "UPDATE personell_logs SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($updateSql);
        $params[] = $row['id'];
        $types .= 'i';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
}
/*
// =====================================================================
// SECURITY - Enhanced CSRF Protection
// =====================================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}
*/
// =====================================================================
// HELPER FUNCTION - Improved Sanitization
// =====================================================================
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// =====================================================================
// LOGIN PROCESSING - Enhanced with Rate Limiting
// =====================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
     /*
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    
    // Check if token is expired (15 minutes)
    if (time() - $_SESSION['csrf_token_time'] > 900) {
        die("CSRF token expired. Please refresh the page.");
    }
*/
    $department = sanitizeInput($_POST['roomdpt'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $password = $_POST['Ppassword'] ?? ''; // Don't sanitize passwords
    $rfid_number = sanitizeInput($_POST['Prfid_number'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($department)) $errors[] = "Department is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($rfid_number)) $errors[] = "RFID number is required";
    
    if (!empty($errors)) {
        http_response_code(400);
        die(implode("<br>", $errors));
    }

    // Verify RFID number against instructor table with rate limiting
    $stmt = $db->prepare("SELECT * FROM instructor WHERE rfid_number = ?");
    $stmt->bind_param("s", $rfid_number);
    $stmt->execute();
    $instructorResult = $stmt->get_result();

    if ($instructorResult->num_rows === 0) {
        sleep(2); // Slow down brute force attempts
        die("Invalid RFID number. Instructor not found.");
    }

    $instructor = $instructorResult->fetch_assoc();

    // Verify room credentials
    $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ?");
    $stmt->bind_param("ss", $department, $location);
    $stmt->execute();
    $roomResult = $stmt->get_result();

    if ($roomResult->num_rows === 0) {
        sleep(2);
        die("Room not found.");
    }

    $room = $roomResult->fetch_assoc();

     $stmt = $db->prepare("SELECT * FROM rooms WHERE password=?");
    $stmt->bind_param("s", $password);
    $stmt->execute();
    $password = $stmt->get_result();

    if ($password->num_rows === 0) {
        sleep(2);
        die("Invalid Password.");
    }
    // Verify password with timing-safe 
    

    // Login successful - set session data
    $_SESSION['access'] = [
        'instructor' => [
            'id' => $instructor['id'],
            'fullname' => $instructor['fullname'],
            'rfid_number' => $instructor['rfid_number']
        ],
        'room' => [
            'id' => $room['id'],
            'department' => $room['department'],
            'room' => $room['room'],
            'desc' => $room['desc'],
            'descr' => $room['descr'],
            'authorized_personnel' => $room['authorized_personnel']
        ],
        'last_activity' => time()
    ];

    // Regenerate session ID to prevent fixation
    //session_regenerate_id(true);
    
    // Respond based on location
   echo json_encode([
    'status' => 'success',
    'redirect' => ($department === 'Main' && $location === 'Gate') ? 'main.php' : 'main1.php'
]);
exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>RFIDGPMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="RFID Gate and Personnel Management System">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:;">
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" as="style">
    <link rel="preload" href="admin/css/bootstrap.min.css" as="style">
    <link rel="preload" href="admin/css/style.css" as="style">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
    <link rel="stylesheet" href="admin/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin/css/style.css">
    
    <style>
        @media (max-width: 1200px) {
            #lnamez { margin-top: 30%; display: block; }
            #up_img { position: relative; margin-top: 4%; display: block; }
        }
        
        .terms-link {
            padding-left: 55%;
            font-size: 12px;
            color: gray;
            text-decoration: none;
            cursor: pointer;
        }
        
        .terms-link:hover {
            text-decoration: underline;
            color: black;
        }
        
        .form-control:focus {
            border-color: #084298;
            box-shadow: 0 0 0 0.25rem rgba(8, 66, 152, 0.25);
        }
        
        #rfid-input {
            letter-spacing: 0.5rem;
            font-family: monospace;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4 p-sm-5">
                    <form id="logform" method="POST" novalidate>
                        
                        
                        <div id="alert-container" class="alert alert-danger d-none" role="alert"></div>
                        
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h3 class="text-primary mb-0">GACPMS</h3>
                            <h5 class="text-muted mb-0">Location</h5>
                        </div>
                        
                        <div class="mb-3">
                            <label for="roomdpt" class="form-label">Department</label>
                            <select class="form-select" name="roomdpt" id="roomdpt" required>
                                <option value="Main" selected>Main</option>
                                <?php
                                $sql = "SELECT department_name FROM department WHERE department_name != 'Main'";
                                $result = $db->query($sql);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <option value="<?= htmlspecialchars($row['department_name']) ?>">
                                    <?= htmlspecialchars($row['department_name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-select" name="location" id="location" required>
                                <option value="Gate" selected>Gate</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="Ppassword" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="rfid-input" class="form-label">RFID Card</label>
                            <input type="text" class="form-control" id="rfid-input" name="Prfid_number" 
                                   placeholder="Tap RFID card" required autofocus>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        
                        <div class="text-end">
                            <a href="terms.php" class="terms-link">Terms and Conditions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="admin/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Password visibility toggle
        $('#togglePassword').click(function() {
            const icon = $(this).find('i');
            const passwordField = $('#password');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Fetch rooms when department changes
        function fetchRooms() {
            const department = $('#roomdpt').val();
            if (department === "Main") {
                $('#location').html('<option value="Gate" selected>Gate</option>');
                return;
            }
            
            $.get('get_rooms.php', { department: department })
                .done(function(data) {
                    $('#location').html(data);
                })
                .fail(function() {
                    $('#location').html('<option value="">Error loading rooms</option>');
                });
        }
        
        $('#roomdpt').change(fetchRooms);
        
        // Form submission with better error handling
        $('#logform').submit(function(e) {
            e.preventDefault();
            const $form = $(this);
            const $alert = $('#alert-container');
            
            $alert.addClass('d-none');
            
            $.ajax({
    url: '',
    type: 'POST',
    data: $form.serialize(),
    dataType: 'json', 
                beforeSend: function() {
                    $form.find('button[type="submit"]').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                },
                complete: function() {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Login');
                },
                // Replace your current AJAX success handler with:
success: function(response) {
    try {
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        
        if (data.status === 'success') {
            window.location.href = data.redirect;
        } else {
            $alert.removeClass('d-none').text(data.message || 'Login failed');
            $('#rfid-input').focus();
        }
    } catch (e) {
        $alert.removeClass('d-none').text('Invalid server response');
        $('#rfid-input').focus();
    }
},
                error: function(xhr) {
                    const errorMsg = xhr.status === 0 ? 'Network error. Please check your connection.' : 
                                    xhr.responseText || 'An error occurred. Please try again.';
                    $alert.removeClass('d-none').text(errorMsg);
                }
            });
        });
        
        // Auto-focus on RFID input
        $('#rfid-input').focus();
        
        // Prevent form submission on Enter key in RFID field
        $('#rfid-input').keypress(function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#logform').submit();
            }
        });
    });
    </script>
</body>
</html>