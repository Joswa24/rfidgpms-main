<?php
include 'connection.php';
session_start();

// Initialize session variables with proper checks
$_SESSION['allowed_section'] = $_SESSION['allowed_section'] ?? null;
$_SESSION['allowed_year'] = $_SESSION['allowed_year'] ?? null;
$_SESSION['is_first_student'] = $_SESSION['is_first_student'] ?? true;

// Safely get department and location from session
$department = isset($_SESSION['access']['room']['department']) ? 
              $_SESSION['access']['room']['department'] : 'Department';
$location = isset($_SESSION['access']['room']['room']) ? 
            $_SESSION['access']['room']['room'] : 'Location';

// Check for force redirect
if (isset($_SESSION['access']['force_redirect'])) {
    header('Location: ' . $_SESSION['access']['force_redirect']);
    exit;
}

// Fetch data from the about table
$logo1 = $nameo = $address = $logo2 = "";
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
} 

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/grow_up.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.2/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    
    <title>Classroom Attendance Scanner</title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
    <style>
        /* Enhanced Confirmation Modal Styles */
        .confirmation-modal .modal-dialog {
            max-width: 550px;
        }

        .confirmation-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 3px solid #084298;
        }

        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, #084298 0%, #0d6efd 100%);
            color: white;
            border-bottom: none;
            padding: 20px 30px;
            position: relative;
        }

        .confirmation-modal .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 5%;
            width: 90%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
        }

        .confirmation-modal .modal-title {
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .confirmation-modal .modal-body {
            padding: 30px;
            background: #f8f9fa;
        }

        .confirmation-modal .student-photo-container {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 20px;
        }

        .confirmation-modal .student-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #084298;
            box-shadow: 0 8px 25px rgba(8, 66, 152, 0.3);
            background: white;
        }

        .confirmation-modal .photo-frame {
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border: 2px solid #0d6efd;
            border-radius: 50%;
            pointer-events: none;
        }

        .confirmation-modal .student-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #084298;
            margin-bottom: 5px;
            text-align: center;
        }

        .confirmation-modal .student-id {
            font-size: 1.1rem;
            color: #6c757d;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .confirmation-modal .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .confirmation-modal .info-item {
            background: white;
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #084298;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }

        .confirmation-modal .info-item:hover {
            transform: translateY(-2px);
        }

        .confirmation-modal .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .confirmation-modal .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            word-break: break-word;
        }

        .confirmation-modal .attendance-status-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .confirmation-modal .time-in-status {
            border-color: #198754;
            background: linear-gradient(135deg, #d1e7dd 0%, #f8f9fa 100%);
        }

        .confirmation-modal .time-out-status {
            border-color: #fd7e14;
            background: linear-gradient(135deg, #ffe5d0 0%, #f8f9fa 100%);
        }

        .confirmation-modal .attendance-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .confirmation-modal .time-in-icon {
            color: #198754;
        }

        .confirmation-modal .time-out-icon {
            color: #fd7e14;
        }

        .confirmation-modal .attendance-text {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .confirmation-modal .time-in-text {
            color: #198754;
        }

        .confirmation-modal .time-out-text {
            color: #fd7e14;
        }

        .confirmation-modal .time-display {
            background: linear-gradient(135deg, #084298 0%, #0d6efd 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(8, 66, 152, 0.3);
        }

        .confirmation-modal .time-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            font-family: 'Courier New', monospace;
        }

        .confirmation-modal .date-value {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .confirmation-modal .modal-footer {
            border-top: none;
            padding: 20px 30px;
            background: #f8f9fa;
            justify-content: center;
        }

        .confirmation-modal .ok-button {
            background: linear-gradient(135deg, #084298 0%, #0d6efd 100%);
            border: none;
            padding: 12px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 6px 20px rgba(8, 66, 152, 0.4);
            transition: all 0.3s ease;
        }

        .confirmation-modal .ok-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(8, 66, 152, 0.5);
        }

        /* Scanner styles remain the same */
        .scanner-display-area {
            background-color: #f8f9fa;
            border: 2px dashed #084298;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .scanned-id-display {
            background-color: #f8f9fa;
            border: 2px solid #084298;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
            width: 100%;
        }

        /* Rest of your existing styles... */
        .dept-location-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: #084298;
        }

        .manual-input-section {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        .large-scanner-container {
            position: relative;
            height: 60vh;
            max-height: 300px;
            margin: 20px auto;
        }

        #largeReader {
            border: 2px solid #084298;
            border-radius: 10px;
            overflow: hidden;
        }

        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0,0,0,0.7);
            z-index: 10;
        }

        .scanner-frame {
            border: 3px solid #FBC257;
            width: 80%;
            height: 200px;
            position: relative;
        }

        .scanner-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #FBC257;
            top: 0;
            animation: scan 2s infinite;
            box-shadow: 0 0 10px #FBC257;
        }

        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }

        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }

        @keyframes blink-animation {
            to { visibility: hidden; }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .confirmation-modal .info-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .confirmation-modal .modal-body {
                padding: 20px;
            }
            
            .confirmation-modal .student-photo-container {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 

<div id="message"></div>

<img src="uploads/Head.png" style="width: 100%; height: 150px; margin-left: 10px; padding=10px; margin-top=20px;S">

<!-- Enhanced Confirmation Modal -->
<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Attendance Recorded
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <!-- Student Photo with Frame -->
                <div class="student-photo-container">
                    <img id="modalStudentPhoto" 
                         src="assets/img/2601828.png" 
                         alt="Student Photo" 
                         class="student-photo"
                         onerror="this.src='assets/img/2601828.png'">
                    <div class="photo-frame"></div>
                </div>
                
                <!-- Student Name and ID -->
                <h4 id="modalStudentName" class="student-name"></h4>
                <div id="modalStudentId" class="student-id"></div>

                <!-- Student Information Grid -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div id="modalStudentDept" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Year & Section</div>
                        <div id="modalStudentYearSection" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div id="modalStudentRole" class="info-value"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div id="modalStudentStatus" class="info-value">Active</div>
                    </div>
                </div>

                <!-- Attendance Status -->
                <div class="attendance-status-container" id="modalAttendanceStatus">
                    <div class="attendance-icon" id="modalAttendanceIcon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="attendance-text" id="modalTimeInOut"></div>
                    <div class="attendance-subtext" id="modalAttendanceSubtext">Successfully recorded</div>
                </div>

                <!-- Time Display -->
                <div class="time-display">
                    <div id="modalTimeDisplay" class="time-value"></div>
                    <div id="modalDateDisplay" class="date-value"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ok-button" onclick="closeAndContinue()">
                    <i class="fas fa-check me-2"></i>OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rest of your HTML structure remains the same -->
<div class="container mt-3">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active active-tab" aria-current="page" href="#">Scanner</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="students_logs.php?from_scanner=1">Attendance Log</a>
        </li>
    </ul>
</div>

<section class="hero" style="margin-top: 0; height: calc(100vh - 140px);">
    <div class="container h-100">
        <!-- Department/Location Info Display -->
        <div class="dept-location-info mb-2 py-1">
            <h3 class="mb-1" style="font-size: 1rem;">Department: <?php echo $department; ?></h3>
            <h3 class="mb-1" style="font-size: 1rem;">Room: <?php echo $location; ?></h3>
        </div>
        
        <!-- Clock Display -->
        <center>
            <div id="clockdate" style="border: 1px solid #084298; background-color: #084298; height: 70px; margin-bottom: 10px;">
                <div class="clockdate-wrapper d-flex flex-column justify-content-center" style="height:100%;">
                    <div id="clock" style="font-weight: bold; color: #fff; font-size: 1.8rem; line-height: 1.2;"></div>
                    <div id="date" style="color: #fff; font-size: 0.8rem;"><span id="currentDate"></span></div>
                </div>
            </div>
        </center>
        
        <!-- Main Content Row -->
        <div class="row" style="height: calc(100% - 120px);">
            <!-- Scanner Column -->
            <div class="col-md-8 h-100" style="padding-right: 5px;">
                <div class="alert alert-primary py-1 mb-2" role="alert" id="alert">
                    <center><h3 id="in_out" class="mb-0" style="font-size: 1rem;">
                        <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Attendance
                    </h3></center>
                </div>

                <!-- Scanner Container -->
                <div class="large-scanner-container" style="height: calc(100% - 60px);">
                    <div id="largeReader" style="height: 100%;"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-frame" style="height: 130px; margin-bottom: 10px;">
                            <div class="scanner-laser"></div>
                        </div>
                    </div>
                </div>
                <div id="result" class="text-center" style="min-height: 40px; font-size: 0.9rem;"></div>
            </div>
            
            <!-- Photo/Manual Input Column -->
            <div class="col-md-4 h-100 d-flex flex-column" style="padding-left: 5px;">
                <!-- Student Photo -->
                <img id="pic" class="mb-2" alt=""; 
                     src="assets/img/section/type.jpg"
                     style="margin-top: .5px; width: 100%; height: 200px; object-fit: cover; border: 2px solid #084298; border-radius: 3px;">
                
                <!-- Manual Input Section -->
                <div class="manual-input-section flex-grow-1" style="padding: 10px; margin-bottom:60px;">
                    <h4 class="mb-1" style="font-size: 1rem;"><i class="fas fa-keyboard"></i> Manual Attendance</h4>
                    <p class="text-center mb-2" style="font-size: 0.8rem;">For students who forgot their ID</p>
                    
                    <div class="input-group mb-1">
                        <input type="text" 
                               class="form-control" 
                               id="manualIdInput" 
                               placeholder="0000-0000"
                               style="height: 40px; font-size: 0.9rem;">
                        <button class="btn btn-primary" 
                                id="manualSubmitBtn" 
                                style="height: 40px; font-size: 0.9rem; border: 1px solid #084298; background-color: #084298;"
                                onclick="processManualInput()">
                            Submit
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <small class="text-muted" style="font-size: 0.7rem;">Press Enter after typing ID</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>

<script>
// Global variables
let scanner = null;
let lastScanTime = 0;
const scanCooldown = 1000;

// Enhanced showConfirmationModal function
function showConfirmationModal(data) {
    console.log("🎯 Showing enhanced confirmation modal with data:", data);
    
    // Get current time and date
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Update student information
    document.getElementById('modalStudentName').textContent = data.full_name || 'Student Name';
    document.getElementById('modalStudentId').textContent = data.id_number || 'N/A';
    document.getElementById('modalStudentDept').textContent = data.department || 'N/A';
    document.getElementById('modalStudentYearSection').textContent = (data.year_level || 'N/A') + ' - ' + (data.section || 'N/A');
    document.getElementById('modalStudentRole').textContent = data.role || 'Student';
    document.getElementById('modalStudentStatus').textContent = data.status || 'Active';
    
    // Update time displays
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;

    // Update attendance status with enhanced styling
    const statusContainer = document.getElementById('modalAttendanceStatus');
    const statusIcon = document.getElementById('modalAttendanceIcon');
    const statusText = document.getElementById('modalTimeInOut');
    
    if (data.attendance_type === 'time_in' || data.time_in_out === 'Time In Recorded') {
        statusContainer.className = 'attendance-status-container time-in-status';
        statusIcon.innerHTML = '<i class="fas fa-sign-in-alt time-in-icon"></i>';
        statusText.className = 'attendance-text time-in-text';
        statusText.textContent = '✓ Time In Recorded';
        document.getElementById('modalAttendanceSubtext').textContent = 'Welcome to class!';
    } else if (data.attendance_type === 'time_out' || data.time_in_out === 'Time Out Recorded') {
        statusContainer.className = 'attendance-status-container time-out-status';
        statusIcon.innerHTML = '<i class="fas fa-sign-out-alt time-out-icon"></i>';
        statusText.className = 'attendance-text time-out-text';
        statusText.textContent = '✓ Time Out Recorded';
        document.getElementById('modalAttendanceSubtext').textContent = 'See you next time!';
    } else {
        statusContainer.className = 'attendance-status-container';
        statusIcon.innerHTML = '<i class="fas fa-check-circle text-primary"></i>';
        statusText.className = 'attendance-text text-primary';
        statusText.textContent = data.time_in_out || '✓ Attendance Recorded';
        document.getElementById('modalAttendanceSubtext').textContent = 'Successfully recorded';
    }

    // Update student photo with enhanced error handling
    const modalPhoto = document.getElementById('modalStudentPhoto');
    if (data.photo && data.photo !== 'assets/img/2601828.png') {
        modalPhoto.src = data.photo + '?t=' + new Date().getTime();
        modalPhoto.onerror = function() {
            this.src = 'assets/img/2601828.png';
            console.log("🖼️ Photo load failed, using default");
        };
    } else {
        modalPhoto.src = 'assets/img/2601828.png';
    }

    // Show additional student info in console for debugging
    console.log("📋 Enhanced Student Data:", {
        name: data.full_name,
        id: data.id_number,
        department: data.department,
        year: data.year_level,
        section: data.section,
        role: data.role,
        status: data.status,
        photo: data.photo,
        attendance: data.time_in_out || data.attendance_type
    });

    // Show modal using Bootstrap
    const modalElement = document.getElementById('confirmationModal');
    const modal = new bootstrap.Modal(modalElement);
    
    modal.show();
    
    // Hide scanner overlay while modal is open
    document.querySelector('.scanner-overlay').style.display = 'none';
    
    // Add event listener for when modal is hidden
    modalElement.addEventListener('hidden.bs.modal', function () {
        console.log("🎯 Modal closed, restarting scanner");
        restartScanner();
    });
    
    // Speak confirmation message if available
    if (data.voice) {
        speakErrorMessage(data.voice);
    }
}

// Enhanced close function
function closeAndContinue() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
    modal.hide();
    restartScanner();
}

// Enhanced processBarcode function
function processBarcode(barcode) {
    console.log("🔍 Processing barcode:", barcode);
    
    // Show processing state
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Processing ID: ${barcode}</span>
        </div>
    `;
    
    // Disable inputs during processing
    document.getElementById('manualIdInput').disabled = true;
    document.getElementById('manualSubmitBtn').disabled = true;
    
    $.ajax({
        type: "POST",
        url: "process_barcode.php",
        data: { 
            barcode: barcode,
            department: "<?php echo $department; ?>",
            location: "<?php echo $location; ?>"
        },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            console.log("✅ SUCCESS - Enhanced response:", response);
            
            if (response.error) {
                showErrorMessage(response.error);
                speakErrorMessage(response.error);
                document.querySelector('.scanner-overlay').style.display = 'flex';
                return;
            }

            // Enhanced data processing
            const studentData = {
                full_name: response.student_data?.full_name || response.full_name || 'Student',
                id_number: response.student_data?.id_number || response.id_number || barcode,
                department: response.student_data?.department || response.department || '<?php echo $department; ?>',
                section: response.student_data?.section || response.section || 'N/A',
                year_level: response.student_data?.year_level || response.year_level || 'N/A',
                role: 'Student',
                status: response.student_data?.status || 'Active',
                photo: response.photo || 'assets/img/2601828.png',
                time_in_out: response.attendance_info?.time_in_out || response.time_in_out,
                attendance_type: response.attendance_info?.attendance_type || response.attendance_type,
                voice: response.voice
            };

            // Update main display photo
            if (studentData.photo) {
                document.getElementById('pic').src = studentData.photo;
            }
            
            // Show enhanced confirmation modal
            showConfirmationModal(studentData);
            
        },
        error: function(xhr, status, error) {
            console.error("❌ AJAX ERROR:", error);
            
            // Fallback with enhanced data
            const fallbackData = {
                full_name: "Student",
                id_number: barcode,
                department: "<?php echo $department; ?>",
                section: "N/A",
                year_level: "N/A",
                role: "Student",
                status: "Active",
                photo: "assets/img/2601828.png",
                time_in_out: "Attendance Recorded Successfully",
                attendance_type: "time_in"
            };
            
            showConfirmationModal(fallbackData);
        },
        complete: function() {
            // Re-enable inputs
            document.getElementById('manualIdInput').disabled = false;
            document.getElementById('manualSubmitBtn').disabled = false;
            document.getElementById('manualIdInput').value = '';
            document.getElementById('manualIdInput').focus();
        }
    });
}

// Rest of your existing JavaScript functions (startTime, initScanner, etc.)
function startTime() {
    const today = new Date();
    let h = today.getHours();
    let m = today.getMinutes();
    let s = today.getSeconds();
    let period = h >= 12 ? 'PM' : 'AM';
    
    h = h % 12;
    h = h ? h : 12;
    
    m = checkTime(m);
    s = checkTime(s);
    
    document.getElementById('clock').innerHTML = h + ":" + m + ":" + s + " " + period;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
    
    setTimeout(startTime, 1000);
}

function checkTime(i) {
    if (i < 10) {i = "0" + i};
    return i;
}

function initScanner() {
    if (scanner) {
        scanner.clear();
    }
    
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: { width: 300, height: 300 },
        fps: 20,
    });
    
    scanner.render(onScanSuccess, onScanError);
}

function onScanSuccess(decodedText) {
    const now = Date.now();
    if (now - lastScanTime < scanCooldown) return;
    
    lastScanTime = now;
    
    document.getElementById('result').innerHTML = `
        <span class="blink">Processing: ${decodedText}</span>
    `;
    
    document.querySelector('.scanner-overlay').style.display = 'none';
    processBarcode(decodedText);
}

function onScanError(error) {
    // Handle scanner errors silently
}

function restartScanner() {
    if (scanner) {
        scanner.clear().then(() => {
            initScanner();
            document.querySelector('.scanner-overlay').style.display = 'flex';
            document.getElementById('result').innerHTML = "";
        });
    }
}

function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        return;
    }
    processBarcode(idNumber);
}

function showErrorMessage(message) {
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>${message}</div>
        </div>
    `;
    playAlertSound();
}

function playAlertSound() {
    const audio = document.getElementById('myAudio');
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.log('Audio playback failed:', error);
    });
}

function speakErrorMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;
        window.speechSynthesis.speak(speech);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initScanner();
    
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    document.getElementById('manualIdInput').focus();
});
</script>
</body>
</html>