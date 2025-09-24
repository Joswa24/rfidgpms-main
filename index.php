<?php
// Start output buffering with maximum level
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Add error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
 
// Clear output buffer
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
    $department = sanitizeInput($_POST['roomdpt'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $password = $_POST['Ppassword'] ?? ''; // Don't sanitize passwords
    $id_number = sanitizeInput($_POST['Pid_number'] ?? '');
    $selected_subject = sanitizeInput($_POST['selected_subject'] ?? '');
    $selected_room = sanitizeInput($_POST['selected_room'] ?? '');

    // Validate inputs
    $errors = [];
    if (empty($department)) $errors[] = "Department is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($id_number)) $errors[] = "ID number is required";
    
    if (!empty($errors)) {
        http_response_code(400);
        die(implode("<br>", $errors));
    }

    // Check if this is a gate access request (Main department + Gate location)
    if ($department === 'Main' && $location === 'Gate') {
        // Remove hyphen from ID for database search
        $clean_id = str_replace('-', '', $id_number);
        
        // Use the correct column name: id_no instead of id_number
        $stmt = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND department = 'Main'");
        if (!$stmt) {
            error_log("Prepare failed: " . $db->error);
            die("Database error. Please check server logs.");
        }
        
        $stmt->bind_param("s", $clean_id);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            die("Database query failed.");
        }
        
        $securityResult = $stmt->get_result();

        if ($securityResult->num_rows === 0) {
            sleep(2);
            
            // Try rfid_number as fallback (also without hyphen)
            $stmt2 = $db->prepare("SELECT * FROM personell WHERE id_number = ? AND role = 'Security Personnel'");
            if ($stmt2) {
                $stmt2->bind_param("s", $clean_id);
                $stmt2->execute();
                $securityResult = $stmt2->get_result();
            }
        }

        if ($securityResult->num_rows === 0) {
            sleep(2);
            
            // Debug: Check what IDs actually exist
            $debugStmt = $db->prepare("SELECT id_number, first_name, last_name, role FROM personell WHERE (role LIKE '%Security Personnel%' OR role LIKE '%Guard%')");
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            
            $availablePersonnel = [];
            while ($row = $debugResult->fetch_assoc()) {
                $availablePersonnel[] = " RFID:{$row['id_number']}, Name:{$row['first_name']} {$row['last_name']}";
            }
            
            die("Unauthorized access. Security personnel not found with ID: $id_number " );
        }

        $securityGuard = $securityResult->fetch_assoc();
        
        // Check if they have security role
        $role = strtolower($securityGuard['role'] ?? '');
        $isSecurity = stripos($role, 'security') !== false || stripos($role, 'guard') !== false;
        
        if (!$isSecurity) {
            sleep(2);
            die("Unauthorized access. User found but not security personnel. Role: " . ($securityGuard['role'] ?? 'Unknown'));
        }

        // Verify room credentials for gate
        $stmt = $db->prepare("SELECT * FROM rooms WHERE department = ? AND room = ?");
        $stmt->bind_param("ss", $department, $location);
        $stmt->execute();
        $roomResult = $stmt->get_result();

        if ($roomResult->num_rows === 0) {
            sleep(2);
            die("Gate access not configured.");
        }

        $room = $roomResult->fetch_assoc();

        // Verify gate password
        $stmt = $db->prepare("SELECT * FROM rooms WHERE password=? AND department='Main' AND room='Gate'");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        $passwordResult = $stmt->get_result();

        if ($passwordResult->num_rows === 0) {
            sleep(2);
            die("Invalid Gate Password.");
        }

        // Gate login successful - set session data
        $_SESSION['access'] = [
            'security' => [
                'id' => $securityGuard['id'],
                'fullname' => $securityGuard['first_name'] . ' ' . $securityGuard['last_name'],
                'id_number' => $securityGuard['id_number'],
                'role' => $securityGuard['role']
            ],
            'gate' => [
                'department' => 'Main',
                'location' => 'Gate'
            ],
            'last_activity' => time()
        ];

        // Clear any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set proper headers
        header('Content-Type: application/json');

        // Return JSON response for gate access - REDIRECT TO MAIN.PHP
        echo json_encode([
            'status' => 'success',
            'redirect' => 'main.php', // Security personnel go to main.php
            'message' => 'Gate access granted'
        ]);
        exit;
    }

    // Regular instructor login process (for non-gate access)
    // Verify ID number against instructor table with rate limiting
    $stmt = $db->prepare("SELECT * FROM instructor WHERE id_number = ?");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $instructorResult = $stmt->get_result();

    if ($instructorResult->num_rows === 0) {
        sleep(2); // Slow down brute force attempts
        die("Invalid ID number. Instructor not found.");
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

    // Login successful - set session data
    $_SESSION['access'] = [
        'instructor' => [
            'id' => $instructor['id'],
            'fullname' => $instructor['fullname'],
            'id_number' => $instructor['id_number']
        ],
        'room' => [
            'id' => $room['id'],
            'department' => $room['department'],
            'room' => $room['room'],
            'desc' => $room['desc'],
            'descr' => $room['descr'],
            'authorized_personnel' => $room['authorized_personnel']
        ],
        'subject' => [
            'name' => $selected_subject,
            'room' => $selected_room,
            'time' => $_POST['selected_time'] // Add this line
        ],
        'last_activity' => time()
    ];

    // Clear output buffer before JSON response
    if ($password->num_rows === 0) {
        sleep(2);
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => 'Invalid Password.']));
    }

    // Clear any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set proper headers
    header('Content-Type: application/json');

    // Return JSON response
    echo json_encode([
        'status' => 'success',
        'redirect' => 'main1.php'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GACPMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Gate and Personnel Management System">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://fonts.googleapis.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:;">
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
    <link rel="stylesheet" href="admin/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin/css/style.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .mb-3, .mb-4 {
            transition: all 0.3s ease;
        }
        
        /* Security guard specific styles */
        .gate-access-info {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
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
                            <label for="id-input" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="id-input" name="Pid_number" 
                                   placeholder="0000-0000" required
                                   pattern="[0-9]{4}-[0-9]{4}" 
                                   title="Please enter ID in format: 0000-0000">
                            <div class="form-text">Scan your ID barcode or type manually (format: 0000-0000)</div>
                        </div>
                        
                        <!-- Gate access information -->
                        <div id="gateAccessInfo" class="gate-access-info d-none">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Gate Access Mode:</strong> Security personnel only
                        </div>
                        
                        <!-- Hidden fields for selected subject -->
                        <input type="hidden" name="selected_subject" id="selected_subject" value="">
                        <input type="hidden" name="selected_room" id="selected_room" value="">
                        <input type="hidden" name="selected_time" id="selected_time" value="">
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" id="loginButton">Login</button>
                        
                        <div class="text-end">
                            <a href="terms.php" class="terms-link">Terms and Conditions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Subject Selection Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Select Your Subject for <span id="modalRoomName"></span></h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        Please check the subject you're currently teaching in this room and click "Confirm Selection".
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="subjectTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">Select</th>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="subjectList">
                                <!-- Subjects will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelSubject">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubject" disabled>
                        <span class="spinner-border spinner-border-sm d-none" id="confirmSpinner" role="status" aria-hidden="true"></span>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="admin/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
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

        // ID Number Input Handling with Formatting
        const idInput = $('#id-input');

        idInput.on('input', function(e) {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            $(this).val(value);
        });
        
        // Show/hide gate access info based on department selection
        $('#roomdpt, #location').change(function() {
            const department = $('#roomdpt').val();
            const location = $('#location').val();
            
            if (department === 'Main' && location === 'Gate') {
                $('#gateAccessInfo').removeClass('d-none');
            } else {
                $('#gateAccessInfo').addClass('d-none');
            }
        });

        // Initial check
        $('#roomdpt').trigger('change');

        // Form submission handler
        $('#logform').on('submit', function(e) {
            e.preventDefault();
            
            const idNumber = $('#id-input').val();
            const password = $('#password').val();
            const department = $('#roomdpt').val();
            const selectedRoom = $('#location').val();
            
            // Validate ID format
            if (!/^\d{4}-\d{4}$/.test(idNumber)) {
                showAlert('Please enter a valid ID number (format: 0000-0000)');
                idInput.focus();
                return;
            }
            
            if (!password) {
                showAlert('Please enter your password');
                $('#password').focus();
                return;
            }
            
            // For Main department + Gate location, proceed directly to gate access
            if (department === 'Main' && selectedRoom === 'Gate') {
                submitLoginForm();
            } 
            // If we have a selected subject, proceed
            else if ($('#selected_subject').val()) {
                submitLoginForm();
            }
            // Otherwise show subject selection
            else {
                showSubjectSelectionModal();
            }
        });

        // Show subject selection modal
        function showSubjectSelectionModal() {
            const idNumber = $('#id-input').val();
            const selectedRoom = $('#location').val();
            
            if (!idNumber || !selectedRoom) {
                showAlert('Please select a location first');
                return;
            }
            
            $('#subjectList').html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            
            const subjectModal = new bootstrap.Modal(document.getElementById('subjectModal'));
            subjectModal.show();
            
            $('#modalRoomName').text(selectedRoom);
            loadInstructorSubjects(idNumber, selectedRoom);
        }

        // Load subjects for instructor
        function loadInstructorSubjects(idNumber, selectedRoom) {
            $.ajax({
                url: 'get_instructor_subjects.php',
                type: 'GET',
                data: { 
                    id_number: idNumber.replace(/-/g, ''),
                    room_name: selectedRoom
                },
                dataType: 'json',
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.status === 'success' && data.data && data.data.length > 0) {
                            let html = '';
                            data.data.forEach(schedule => {
                                const now = new Date();
                                const currentTimeMinutes = now.getHours() * 60 + now.getMinutes();

                                // Parse subject start time into minutes
                                let startMinutes = null;
                                if (schedule.start_time) {
                                    const [hour, minute, second] = schedule.start_time.split(':');
                                    startMinutes = parseInt(hour, 10) * 60 + parseInt(minute, 10);
                                }

                                const isEnabled = startMinutes !== null && startMinutes >= currentTimeMinutes;

                                const startTimeFormatted = schedule.start_time ? 
                                    new Date(`1970-01-01T${schedule.start_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                                    'N/A';
                                const endTimeFormatted = schedule.end_time ? 
                                    new Date(`1970-01-01T${schedule.end_time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 
                                    'N/A';

                                html += `
                                    <tr class="modal-subject-row ${!isEnabled ? 'table-secondary' : ''}">
                                        <td>
                                            <input type="checkbox" class="form-check-input subject-checkbox"
                                                data-subject="${schedule.subject || ''}"
                                                data-room="${schedule.room_name || ''}"
                                                ${!isEnabled ? 'disabled' : ''}>
                                        </td>
                                        <td>${schedule.subject || 'N/A'}</td>
                                        <td>${schedule.section || 'N/A'}</td>
                                        <td>${schedule.day || 'N/A'}</td>
                                        <td>${startTimeFormatted} - ${endTimeFormatted}</td>
                                    </tr>`;
                            });

                            $('#subjectList').html(html);
                        } else {
                            $('#subjectList').html(`<tr><td colspan="5" class="text-center">No scheduled subjects found</td></tr>`);
                        }
                    } catch (e) {
                        console.error('Error parsing subjects:', e, response);
                        $('#subjectList').html('<tr><td colspan="5" class="text-center text-danger">Error loading subjects</td></tr>');
                    }
                },
                error: function(xhr) {
                    $('#subjectList').html('<tr><td colspan="5" class="text-center text-danger">Error loading subjects</td></tr>');
                }
            });
        }

        // Handle subject selection (instructors only)
        $(document).on('change', '.subject-checkbox', function() {
            // Uncheck all others (single select)
            $('.subject-checkbox').not(this).prop('checked', false);
            
            // Store selected subject/room/time in hidden inputs
            if ($(this).is(':checked')) {
                $('#selected_subject').val($(this).data('subject'));
                $('#selected_room').val($(this).data('room'));
                $('#confirmSubject').prop('disabled', false);
            } else {
                $('#selected_subject').val('');
                $('#selected_room').val('');
                $('#confirmSubject').prop('disabled', true);
            }
        });

        // Confirm subject selection
        $('#confirmSubject').click(function() {
            const selectedRow = $('.subject-checkbox:checked').closest('tr');
            const subject = $('#selected_subject').val();
            const room = $('#selected_room').val();
            
            if (!subject || !room) {
                showAlert('Please select a subject first.');
                return;
            }
            
            // Grab time text
            $('#selected_time').val(selectedRow.find('td:last').text());
            
            // Close modal
            $('#subjectModal').modal('hide');
            
            // Submit login form
            submitLoginForm();
        });

        // Submit login form to server
        function submitLoginForm() {
            const formData = $('#logform').serialize();
            
            Swal.fire({
                title: 'Logging in...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '', // same PHP page
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: response.message || 'Redirecting...',
                            timer: 1500,
                            showConfirmButton: false,
                            willClose: () => {
                                window.location.href = response.redirect;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message || 'Invalid credentials'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    
                    // Try to parse the response as text first to see what's coming back
                    let errorMessage = 'Login request failed. Please try again.';
                    
                    try {
                        // If it's JSON, parse it
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        // If it's not JSON, show the raw response for debugging
                        errorMessage = xhr.responseText || errorMessage;
                        if (errorMessage.length > 100) {
                            errorMessage = errorMessage.substring(0, 100) + '...';
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                    
                    console.error('Login error:', xhr.responseText);
                }
            });
        }

        // Utility alert (used earlier)
        function showAlert(message) {
            $('#alert-container').removeClass('d-none').text(message);
        }

        // Fetch rooms when department changes
        $('#roomdpt').change(function() {
            const department = $(this).val();
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
        });

        // Initial focus
        setTimeout(function() {
            idInput.focus();
        }, 300);
    });
    </script>
</body>
</html>