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
        /* Updated styles for barcode scanner interface */
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

        .scanned-label {
            font-weight: bold;
            color: #084298;
            margin-bottom: 5px;
        }

        .scanned-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #212529;
            margin: 10px 0;
            padding: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            word-break: break-all;
        }


.processing-text {
    color: #084298;
    font-style: italic;
    margin-top: 10px;
}
        .preview-1 {
            width: 140px!important;
            height: 130px!important;
            position: absolute;
            border: 1px solid gray;
            top: 15%;
            cursor: pointer;
        }
        
        .column {
            flex: 1;
            text-align: center;
        }
        
        .column.wide {
            flex: 2;
        }
        
        .text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        
        .text .row {
            line-height: 1.5;
            margin-bottom: 5px;
        }
        
        .detail {
            appearance: none;
            border: none;
            outline: none;
            border-bottom: .2em solid #084298;
            background: white;
            border-radius: .2em .2em 0 0;
            padding: .4em;
            margin: 13px 0px;
            height: 70px;
        }
        
        #reader {
            width: 50%;
            max-width: 250px;
            margin: 0 auto; /* Centered horizontally */
            border: 2px solid #084298;
            border-radius: 10px;
            overflow: hidden;
        }
        
        #result {
            text-align: center;
            font-size: 1.5rem;
            margin: 20px 0;
            color: #084298;
            font-weight: bold;
        }
        
        .scanner-container {
            position: relative;
            display: flex;
            justify-content: center; /* Center the scanner */
            align-items: center;
            flex-direction: column;
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
            height: 20px;
            
                
        }
        
        .manual-input-section h4 {
            color: #084298;
            margin-bottom: 10px;
            text-align: center;

        }
        
        .input-group {
            margin-bottom: 10px;
        }
        
        #manualIdInput {
            border: 2px solid #084298;
            height: 50px;
            font-size: 1.2rem;
            text-align: center;
        }
        
        #manualSubmitBtn {
            height: 50px;
            font-size: 1.1rem;
           
        }
        
        /* Confirmation modal styling */
        .confirmation-modal .modal-dialog {
            max-width: 500px;
        }
        
        .confirmation-modal .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .confirmation-modal .modal-header {
            background-color: #084298;
            color: white;
            border-bottom: none;
        }
        
        .confirmation-modal .modal-body {
            padding: 30px;
            text-align: center;
        }
        
        .confirmation-modal .student-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid #084298;
        }
        
        .confirmation-modal .student-info {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .confirmation-modal .attendance-status {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            border-radius: 10px;
        }
        
        .confirmation-modal .time-in {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .confirmation-modal .time-out {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .confirmation-modal .modal-footer {
            border-top: none;
            justify-content: center;
        }
        
        /* Add new styles for larger scanner */
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
        
        /* Adjust the layout for the columns */
        .scanner-column {
            flex: 1;
            padding: 15px;
        }
        
        .photo-column {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .large-photo {
            width: 100%;
            max-width: 300px;
            height: 300px;
            object-fit: cover;
            border: 2px solid #084298;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Scanner preview modal styles */
        .scanner-preview-modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 300px;
            max-width: 90%;
            text-align: center;
        }
        
        .scanner-preview-modal img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #084298;
            margin-bottom: 15px;
        }
        
        .scanner-preview-modal h5 {
            margin-bottom: 10px;
            color: #084298;
        }
        
        .scanner-preview-modal p {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .scanner-preview-modal .btn-group {
            margin-top: 15px;
        }
        
        .scanner-preview-modal .btn {
            padding: 5px 15px;
            margin: 0 5px;
        }
        
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }
        
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        .restriction-message {
            background-color: #f8d7da;
            color: #842029;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .modal-student-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #084298;
            margin: 0 auto 20px;
            display: block;
        }
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 
<div id="message"></div>

<img src="uploads/Head.png" style="width: 100%; height: 150px; margin-left: 10px; padding=10px; margin-top=20px;S">
        <!-- Confirmation Modal -->
        <div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Attendance Recorded</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <!-- ✅ Student Photo - Fixed -->
                        <img id="modalStudentPhoto" 
                            src="assets/img/2601828.png" 
                            alt="Student Photo" 
                            class="modal-student-photo">
                        
                        <h4 id="modalStudentName" class="mb-3"></h4>
                        
                        <div class="student-info mb-3">
                            <div class="mb-2"><strong>ID:</strong> <span id="modalStudentId"></span></div>
                            <div class="mb-2"><strong>Department:</strong> <span id="modalStudentDept"></span></div>
                            <div class="mb-2"><strong>Year & Section:</strong> <span id="modalStudentYearSection"></span></div>
                            <div><strong>Role:</strong> <span id="modalStudentRole"></span></div>
                        </div>
                        
                        <div class="attendance-status mb-3" id="modalAttendanceStatus">
                            <span id="modalTimeInOut"></span>
                        </div>
                        
                        <div class="time-display bg-light p-3 rounded">
                            <div id="modalTimeDisplay" class="fw-bold fs-5"></div>
                            <div id="modalDateDisplay" class="text-muted"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" style="background-color: #87abe0ff" onclick="closeAndRefresh()">OK</button>
                    </div>
                </div>
            </div>
        </div>



<!-- Scanner Preview Modal (shown within scanner frame) -->
<div id="ScannerPreviewModal" class="Scanner-preview-modal" style="display: none;">
    <img id="previewPhoto" src="uploads/temporary.png" alt="Student Photo" type="image/png">
    <h5 id="previewName"></h5>
    <p>ID: <span id="previewId"></span></p>
    <p>Department: <span id="previewDept"></span></p>
    <p>Section: <span id="previewSection"></span></p>
    <p>Year: <span id="previewYear"></span></p>
    
    <div class="btn-group">
        <button id="previewConfirmBtn" class="btn btn-success">Confirm</button>
        <button id="previewCancelBtn" class="btn btn-secondary">Cancel</button>
    </div>
</div>


<!-- Navigation Tabs -->
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
        <!-- Department/Location Info Display - Made more compact -->
        <div class="dept-location-info mb-2 py-1">
            <h3 class="mb-1" style="font-size: 1rem;">Department: <?php echo $department; ?></h3>
            <h3 class="mb-1" style="font-size: 1rem;">Room: <?php echo $location; ?></h3>
        </div>
        
        <!-- Compact Clock Display -->
        <center>
            <div id="clockdate" style="border: 1px solid #084298; background-color: #084298; height: 70px; margin-bottom: 10px;">
                <div class="clockdate-wrapper d-flex flex-column justify-content-center" style="height:100%;">
                    <div id="clock" style="font-weight: bold; color: #fff; font-size: 1.8rem; line-height: 1.2;"></div>
                    <div id="date" style="color: #fff; font-size: 0.8rem;"><span id="currentDate"></span></div>
                </div>
            </div>
        </center>
        
        <!-- Main Content Row - Adjusted heights -->
        <div class="row" style="height: calc(100% - 120px);">
            <!-- Scanner Column (70% width) -->
            <div class="col-md-8 h-100" style="padding-right: 5px;">
                <div class="alert alert-primary py-1 mb-2" role="alert" id="alert">
                    <div class="alert alert-primary py-1 mb-2" role="alert" id="alert">
                    <center><h3 id="in_out" class="mb-0" style="font-size: 1rem;">
                        <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Attendance
                    </h3></center>
                </div>
                </div>

                <!-- Scanner Container - Adjusted size -->
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
            
            <!-- Photo/Manual Input Column (30% width) -->
            <div class="col-md-4 h-100 d-flex flex-column" style="padding-left: 5px;">
                <!-- Student Photo - Made smaller -->
                <img id="pic" class="mb-2" alt=""; 
                     src="assets/img/section/type.jpg"
                     style="margin-top: .5px; width: 100%; height: 200px; object-fit: cover; border: 2px solid #084298; border-radius: 3px;">
                
                <!-- Manual Input Section - Made more compact -->
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

<script>
// Global variables
let lastScanTime = 0;
const scanCooldown = 1000;
let allowedSection = null;
let allowedYear = null;
let isFirstStudent = <?php echo $_SESSION['is_first_student'] ? 'true' : 'false'; ?>;
let scanTimeout = null;

// Initialize when page loads
function initScanner() {
    console.log("Initializing barcode scanner...");
    
    // Focus on manual input field (where barcode scanner will type)
    document.getElementById('manualIdInput').focus();
    
    // Set up event listener for barcode scanner input
    const manualInput = document.getElementById('manualIdInput');
    
    manualInput.addEventListener('input', function(e) {
        clearTimeout(scanTimeout);
        
        // Set a timeout to detect when scanning is complete
        // Barcode scanners typically type quickly and then send Enter
        scanTimeout = setTimeout(() => {
            const scannedValue = manualInput.value.trim();
            
            // Only process if there's a value and it looks like a barcode/ID
            if (scannedValue.length > 3 && !scannedValue.includes(' ')) {
                console.log("Detected barcode scan:", scannedValue);
                processBarcode(scannedValue);
            }
        }, 100); // Short timeout to catch rapid input
    });
    
    // Also listen for Enter key (many scanners send Enter after barcode)
    manualInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(scanTimeout);
            
            const scannedValue = manualInput.value.trim();
            if (scannedValue.length > 0) {
                console.log("Barcode scan with Enter:", scannedValue);
                processBarcode(scannedValue);
            }
        }
    });
}

// Process barcode function
function processBarcode(barcode) {
    const now = Date.now();
    
    // Implement scan cooldown to prevent duplicate scans
    if (now - lastScanTime < scanCooldown) {
        console.log("Scan cooldown active - ignoring scan");
        return;
    }
    
    lastScanTime = now;
    
    console.log("Processing barcode:", barcode);
    
    // Show processing state
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border text-primary me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Processing: ${barcode}</span>
        </div>
    `;
    
    // Clear the input field
    document.getElementById('manualIdInput').value = '';
    
    // Disable input during processing
    const manualInput = document.getElementById('manualIdInput');
    const submitBtn = document.getElementById('manualSubmitBtn');
    
    manualInput.disabled = true;
    submitBtn.disabled = true;
    
    $.ajax({
        type: "POST",
        url: "process_barcode.php",
        data: { 
            barcode: barcode,
            current_department: "<?php echo $department; ?>",
            current_location: "<?php echo $location; ?>",
            is_first_student: isFirstStudent
        },
        success: function(response) {
            console.log("Raw server response:", response);
            
            let data;
            
            // Handle both JSON string and object responses
            if (typeof response === 'string') {
                try {
                    data = JSON.parse(response);
                    console.log("Parsed JSON data:", data);
                } catch (e) {
                    console.error("Failed to parse JSON:", e);
                    showErrorMessage("Invalid server response format");
                    reenableInput();
                    return;
                }
            } else {
                data = response;
                console.log("Response is already object:", data);
            }
            
            if (data.error) {
                showErrorMessage(data.error);
                speakErrorMessage(data.error);
                reenableInput();
                return;
            }

            // If first student, set allowed section/year
            if (isFirstStudent && data.section && data.year_level) {
                console.log("Setting first student restrictions:", data.section, data.year_level);
                allowedSection = data.section;
                allowedYear = data.year_level;
                isFirstStudent = false;
            }

            // Update the preview photo in the scanner column
            updatePreviewPhoto(data);
            
            // Show confirmation modal
            showConfirmationModal(data);
            
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            console.error("Response text:", xhr.responseText);
            
            let errorMessage = "Server connection error";
            if (xhr.responseText) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    errorMessage = errorResponse.error || errorMessage;
                } catch (e) {
                    errorMessage = "Server error: " + xhr.status;
                }
            }
            
            showErrorMessage(errorMessage);
            speakErrorMessage("Server error occurred");
            reenableInput();
        }
    });
}

// Re-enable input after processing
function reenableInput() {
    const manualInput = document.getElementById('manualIdInput');
    const submitBtn = document.getElementById('manualSubmitBtn');
    
    manualInput.disabled = false;
    submitBtn.disabled = false;
    manualInput.focus();
    
    // Clear result message after a delay
    setTimeout(() => {
        document.getElementById('result').innerHTML = '';
    }, 3000);
}

// Update preview photo in the scanner column
function updatePreviewPhoto(data) {
    const previewImg = document.getElementById('pic');
    if (data.photo && data.photo !== '') {
        previewImg.src = data.photo;
        console.log("Updated preview photo");
    } else {
        previewImg.src = "assets/img/2601828.png";
    }
}

// Show confirmation modal with complete student data
function showConfirmationModal(data) {
    console.log("Showing confirmation modal with data:", data);
    
    // Get current time and date
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Student details - with comprehensive null checks
    const fullName = data.full_name || 'Unknown Student';
    const idNumber = data.id_number || 'N/A';
    const department = data.department || 'N/A';
    const yearLevel = data.year_level || 'N/A';
    const section = data.section || 'N/A';
    const role = data.role || 'Student';
    const timeInOut = data.time_in_out || 'Attendance Recorded';
    const timeIn = data.time_in || '';
    const timeOut = data.time_out || '';
    const alertClass = data.alert_class || 'alert-success';
    const voice = data.voice || `Attendance recorded for ${fullName}`;

    console.log("Formatted data for modal:", {
        fullName, idNumber, department, yearLevel, section, role, timeInOut
    });

    // Update modal content
    document.getElementById('modalStudentName').textContent = fullName;
    document.getElementById('modalStudentId').textContent = idNumber;
    document.getElementById('modalStudentDept').textContent = department;
    document.getElementById('modalStudentYearSection').textContent = `${yearLevel} - ${section}`;
    document.getElementById('modalStudentRole').textContent = role;
    document.getElementById('modalTimeInOut').textContent = timeInOut;
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;

    // Student Photo
    const modalPhoto = document.getElementById('modalStudentPhoto');
    if (data.photo && data.photo.startsWith('data:image')) {
        modalPhoto.src = data.photo;
        console.log("Set modal photo to base64 image");
    } else {
        modalPhoto.src = "assets/img/2601828.png";
        console.log("Set modal photo to default image");
    }

    // Update status color and icon
    const statusElement = document.getElementById('modalAttendanceStatus');
    statusElement.className = 'attendance-status p-3 rounded';
    
    if (alertClass === 'alert-success') {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-sign-in-alt me-2"></i>
            ${timeInOut}
            ${timeIn ? `<br><small>Time In: ${timeIn}</small>` : ''}
        `;
    } else if (alertClass === 'alert-warning') {
        statusElement.classList.add('time-out');
        statusElement.innerHTML = `
            <i class="fas fa-sign-out-alt me-2"></i>
            ${timeInOut}
            ${timeIn ? `<br><small>Time In: ${timeIn}</small>` : ''}
            ${timeOut ? `<br><small>Time Out: ${timeOut}</small>` : ''}
        `;
    } else {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${timeInOut}
        `;
    }

    // Clear result message
    document.getElementById('result').innerHTML = '';
    
    // Show modal using Bootstrap 5
    const modalElement = document.getElementById('confirmationModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // Show the modal
    modal.show();
    console.log("Modal show() called");
    
    // Re-enable input
    reenableInput();
    
    // Play success sound
    playAlertSound();
    
    // Speak confirmation message
    speakErrorMessage(voice);
}

// Show error message
function showErrorMessage(message) {
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>${message}</div>
        </div>
    `;
    playAlertSound();
}

// Play alert sound
function playAlertSound() {
    const audio = document.getElementById('myAudio');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(error => {
            console.log('Audio playback failed:', error);
        });
    }
}

// ========= MANUAL ATTENDANCE FEATURES =========
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        speakErrorMessage("Please enter ID number");
        return;
    }
    
    processBarcode(idNumber);
}

// Text-to-speech function
function speakErrorMessage(message) {
    if ('speechSynthesis' in window) {
        // Cancel any ongoing speech
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 0.8;
        speech.pitch = 1;
        
        window.speechSynthesis.speak(speech);
    }
}

// Time and Date Functions
function startTime() {
    const today = new Date();
    let h = today.getHours();
    let m = today.getMinutes();
    let s = today.getSeconds();
    let period = h >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
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

// Close modal and restart scanner
function closeAndRefresh() {
    console.log("Closing modal and focusing input");
    const modalElement = document.getElementById('confirmationModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
    
    // Focus back on input field
    setTimeout(() => {
        document.getElementById('manualIdInput').focus();
    }, 500);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded, initializing barcode scanner...");
    
    // Initialize scanner
    initScanner();
    
    // Set up event listeners for manual submit button
    document.getElementById('manualSubmitBtn').addEventListener('click', processManualInput);
    
    // Also listen for Enter key in manual input
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
});
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>
</body>
</html> 