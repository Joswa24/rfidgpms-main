<?php
include 'connection.php';
session_start();
// In your login process or at the top of main1.php
$_SESSION['instructor_id'] = 1; // Replace with actual instructor ID from your login system
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
        .person-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #084298;
    margin: 0 auto;
}

.access-status .time-in {
    background-color: #d1e7dd;
    color: #0f5132;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: bold;
}

.access-status .time-out {
    background-color: #f8d7da;
    color: #842029;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: bold;
}

.access-status .access-denied {
    background-color: #f8d7da;
    color: #842029;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: bold;
}
.student-info .badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
    margin-top: 0.25rem;
    display: inline-block;
    min-width: 80px;
}

.student-info .row {
    margin-bottom: 0.5rem;
}

.student-info strong {
    font-size: 0.85rem;
    color: #6c757d;
}

.access-status .time-in {
    background-color: #d1e7dd;
    color: #0f5132;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 1.1rem;
}

.access-status .time-out {
    background-color: #fff3cd;
    color: #856404;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 1.1rem;
}

.access-status .access-denied {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 1.1rem;
}

.time-display {
    border: 2px solid #e9ecef;
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
<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>Attendance Recorded
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <!-- Student Photo -->
                <div class="mb-3">
                    <img id="modalPersonPhoto"
                         src="uploads/students/default.png"
                         alt="Student Photo"
                         class="person-photo"
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #084298;">
                </div>

                <h4 id="modalPersonName" class="mb-3">Student Name</h4>
                
                <div class="student-info mb-3">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <strong>ID Number:</strong><br>
                            <span id="modalPersonId" class="badge bg-secondary">N/A</span>
                        </div>
                        <div class="col-6 mb-2">
                            <strong>Department:</strong><br>
                            <span id="modalPersonDept" class="badge bg-info">N/A</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2">
                            <strong>Year Level:</strong><br>
                            <span id="modalPersonYear" class="badge bg-warning">N/A</span>
                        </div>
                        <div class="col-6 mb-2">
                            <strong>Section:</strong><br>
                            <span id="modalPersonSection" class="badge bg-success">N/A</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <strong>Role:</strong><br>
                            <span id="modalPersonRole" class="badge bg-primary">Student</span>
                        </div>
                    </div>
                </div>
                
                <div class="access-status mb-3" id="modalAccessStatus">
                    <span id="modalAccessType" class="badge bg-success">Attendance Recorded</span>
                </div>
                
                <div class="time-display bg-light p-3 rounded">
                    <div id="modalTimeDisplay" class="fw-bold fs-5 text-primary"></div>
                    <div id="modalDateDisplay" class="text-muted small"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModalAndContinue()">
                    <i class="fas fa-qrcode me-2"></i>Continue Scanning
                </button>
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
            <div id="clockdate" style="border: 1px solid #08a4298; background-color: #084298; height: 70px; margin-bottom: 10px;">
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
let scanner = null;
let lastScanTime = 0;
const scanCooldown = 1000;

// CORRECTED: Student photo mapping with proper paths
const studentPhotos = {
    "2024-0380": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1570": "uploads/students/c9c9ed00-ab5c-4c3e-b197-56559ab7ca61.jpg", 
    "2024-0117": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1697": "uploads/students/68b75972d9975_5555-7777.jpg"
};

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeScanner();
    setupEventListeners();
    startTime();
});

// CORRECTED: Update photo function
function updatePhoto(data) {
    const photoElement = document.getElementById('pic');
    if (!photoElement) return;
    
    let photoPath = "uploads/students/default.png";
    
    if (data.photo) {
        // If photo path is provided in response, use it directly
        photoPath = data.photo;
    } else if (studentPhotos[data.id_number]) {
        // Fallback to predefined photos
        photoPath = studentPhotos[data.id_number];
    }
    
    // Add cache busting and ensure correct path
    photoElement.src = photoPath + "?t=" + new Date().getTime();
}


// CORRECTED: Update modal photo function
function updateModalPhoto(data) {
    const modalPhoto = document.getElementById("modalPersonPhoto");
    if (!modalPhoto) {
        console.error('Modal photo element not found');
        return;
    }
    
    let photoPath = "uploads/students/default.png";
    
    if (data.photo) {
        // Use the photo path from the server response
        photoPath = data.photo;
    } else if (studentPhotos[data.id_number]) {
        // Fallback to predefined photos
        photoPath = studentPhotos[data.id_number];
    }
    
    // Add cache busting to prevent cached images
    modalPhoto.src = photoPath + "?t=" + new Date().getTime();
    console.log('Setting modal photo to:', modalPhoto.src);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeScanner();
    setupEventListeners();
    startTime();
});

function setupEventListeners() {
    // Manual input event listeners
    const manualInput = document.getElementById('manualIdInput');
    const manualSubmitBtn = document.getElementById('manualSubmitBtn');
    
    if (manualInput) {
        manualInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                processManualInput();
            }
        });
    }
    
    if (manualSubmitBtn) {
        manualSubmitBtn.addEventListener('click', processManualInput);
    }
    
    // Focus on manual input field
    if (manualInput) {
        manualInput.focus();
    }
}

// Scanner Functions
function initializeScanner() {
    // Clear existing scanner
    if (scanner) {
        scanner.clear().catch(console.error);
    }
    
    // Check camera permissions first
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            // Initialize scanner with configuration
            scanner = new Html5QrcodeScanner('largeReader', { 
                qrbox: { width: 250, height: 250 },
                fps: 10,
                rememberLastUsedCamera: true,
                supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
            });
            
            scanner.render(onScanSuccess, onScanError);
        })
        .catch(err => {
            console.error("Camera permission denied:", err);
            showErrorMessage("Tap Your ID Card to the Scanner or Use Manual Input");
        });
}

function onScanSuccess(decodedText) {
    const now = Date.now();
    
    // Implement scan cooldown
    if (now - lastScanTime < scanCooldown) {
        console.log("Scan cooldown active - ignoring scan");
        return;
    }
    
    lastScanTime = now;
    
    // Show processing message
    updateResultMessage(`Processing: ${decodedText}`, 'info');
    
    // Hide scanner overlay during processing
    hideScannerOverlay();
    
    // Process the scanned barcode
    processBarcode(decodedText);
}

function onScanError(error) {
    // Ignore common scanner errors
    if (error.includes('NotFoundException') || error.includes('No MultiFormat Readers')) {
        return;
    }
    console.error('Scanner error:', error);
}

function hideScannerOverlay() {
    const overlay = document.querySelector('.scanner-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showScannerOverlay() {
    const overlay = document.querySelector('.scanner-overlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

    // Update classroom UI for attendance records
    function updateClassroomUI(data) {
        const alertElement = document.getElementById('alert');
        if (!alertElement) return;
        
        // Reset classes
        alertElement.classList.remove('alert-primary', 'alert-success', 'alert-warning', 'alert-danger', 'alert-info');
        
        // Set appropriate alert class based on attendance status
        if (data.attendance_status === 'PRESENT RECORDED') {
            alertElement.classList.add('alert-success');
            document.getElementById('in_out').innerHTML = '<i class="fas fa-check-circle me-2"></i>ATTENDANCE RECORDED - PRESENT';
        } else if (data.attendance_status === 'ALREADY RECORDED') {
            alertElement.classList.add('alert-warning');
            document.getElementById('in_out').innerHTML = '<i class="fas fa-info-circle me-2"></i>ATTENDANCE ALREADY RECORDED';
        } else if (data.error) {
            alertElement.classList.add('alert-danger');
            document.getElementById('in_out').innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${data.error}`;
        } else {
            alertElement.classList.add('alert-primary');
            document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Student ID for Attendance';
        }
        
        // Update student photo
        updatePhoto(data);
    }
        
        // Classroom Attendance Processing with Instructor Records
        function processBarcode(barcode) {
            console.log('Processing student ID:', barcode);
            
            // Show processing message
            updateResultMessage(`Processing Student ID: ${barcode}`, 'info');
            
            $.ajax({
                type: "POST",
                url: "process_barcode.php",
                data: { 
                    barcode: barcode,
                    department: "<?php echo $department; ?>",
                    location: "<?php echo $location; ?>"
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Server response:', response);
                    
                    if (response.error) {
                        showErrorMessage(response.error);
                        return;
                    }
                    
                    // Update UI first
                    updateClassroomUI(response);
                    
                    // Then show confirmation modal
                    showConfirmationModal(response);
                    
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            showErrorMessage(response.error);
                        } else {
                            updateClassroomUI(response);
                            showConfirmationModal(response);
                        }
                    } catch (e) {
                        showErrorMessage('System error, but attendance may have been recorded');
                        
                        const fallbackData = {
                            full_name: 'Student',
                            id_number: barcode,
                            department: 'N/A',
                            photo: 'uploads/students/default.png',
                            attendance_status: 'RECORDED',
                            role: 'Student'
                        };
                        updateClassroomUI(fallbackData);
                        showConfirmationModal(fallbackData);
                    }
                }
            });
        }

// Update gate UI
function updateGateUI(data) {
    const alertElement = document.getElementById('alert');
    if (!alertElement) return;
    
    // Reset classes
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-warning', 'alert-danger', 'alert-info');
    
    // Set appropriate alert class based on response
    if (data.time_in_out === 'Time In Recorded' || data.time_in_out === 'TIME IN') {
        alertElement.classList.add('alert-success');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>ENTRY GRANTED - TIME IN RECORDED';
    } else if (data.time_in_out === 'Time Out Recorded' || data.time_in_out === 'TIME OUT') {
        alertElement.classList.add('alert-warning');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>EXIT RECORDED - TIME OUT RECORDED';
    } else if (data.error) {
        alertElement.classList.add('alert-danger');
        document.getElementById('in_out').innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${data.error}`;
    } else {
        alertElement.classList.add('alert-primary');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
    }
    
    // Update photo
    updatePhoto(data);
}

function updatePhoto(data) {
    const photoElement = document.getElementById('pic');
    if (!photoElement) return;
    
    let photoPath = "uploads/students/default.png";
    
    if (data.photo) {
        if (data.photo.startsWith('data:image')) {
            photoPath = data.photo;
        } else {
            photoPath = data.photo;
        }
    } else if (personPhotos[data.id_number]) {
        photoPath = personPhotos[data.id_number];
    }
    
    photoElement.src = photoPath + "?t=" + new Date().getTime();
}

    
    // CORRECTED: Show confirmation modal - ROBUST VERSION
    // CORRECTED: Show confirmation modal - COMPLETE STUDENT DATA VERSION
function showConfirmationModal(data) {
    console.log('Showing confirmation modal with student data:', data);
    
    // Get current time and date
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    });
    const dateString = now.toLocaleDateString([], { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });

    // Safe data access with fallbacks - using all student fields from students.php
    const safeData = {
        full_name: data.full_name || 'Unknown Student',
        id_number: data.id_number || 'N/A',
        role: data.role || 'Student',
        department: data.department || 'N/A',
        year_level: data.year_level || data.year || 'N/A',
        section: data.section || 'N/A',
        photo: data.photo || 'uploads/students/default.png',
        attendance_status: data.attendance_status || 'RECORDED',
        time_in_out: data.time_in_out || 'Attendance Recorded'
    };

    // Update modal content with complete student data
    setElementText('modalPersonName', safeData.full_name);
    setElementText('modalPersonId', safeData.id_number);
    setElementText('modalPersonRole', safeData.role);
    setElementText('modalPersonDept', safeData.department);
    setElementText('modalPersonYear', safeData.year_level);
    setElementText('modalPersonSection', safeData.section);
    setElementText('modalTimeDisplay', timeString);
    setElementText('modalDateDisplay', dateString);

    // Set student photo with cache busting
    updateModalPhoto(safeData);

    // Update attendance status
    updateModalAccessStatus(safeData);

    // Show the modal using Bootstrap
    showBootstrapModal();
    
    // Speak confirmation message
    speakConfirmationMessage(safeData);
}



// Update modal access status for classroom attendance
function updateModalAccessStatus(data) {
    const statusElement = document.getElementById('modalAccessStatus');
    if (!statusElement) {
        console.error('Modal access status element not found');
        return;
    }
    
    // Reset classes
    statusElement.className = 'access-status';
    
    // Add appropriate styling based on attendance status
    if (data.attendance_status === 'PRESENT RECORDED' || data.time_in_out === 'Time In Recorded') {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ATTENDANCE RECORDED - PRESENT
        `;
    } else if (data.attendance_status === 'ALREADY RECORDED' || data.time_in_out === 'Time Out Recorded') {
        statusElement.classList.add('time-out');
        statusElement.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            ALREADY MARKED PRESENT
        `;
    } else if (data.error) {
        statusElement.classList.add('access-denied');
        statusElement.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${data.error}
        `;
    } else {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ATTENDANCE RECORDED
        `;
    }
}

// Helper function to safely set element text
function setElementText(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text || 'N/A';
    } else {
        console.warn('Element not found:', elementId);
    }
}

function showBootstrapModal() {
    const modalElement = document.getElementById('confirmationModal');
    if (!modalElement) {
        console.error('Confirmation modal element not found');
        showErrorMessage('Modal element not found');
        return;
    }
    
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        console.error('Bootstrap not loaded');
        showErrorMessage('Bootstrap not loaded');
        return;
    }
    
    try {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        console.log('Modal shown successfully');
    } catch (error) {
        console.error('Error showing modal:', error);
        showErrorMessage('Error showing confirmation: ' + error.message);
    }
}

function closeModalAndContinue() {
    const modalElement = document.getElementById('confirmationModal');
    if (!modalElement) return;
    
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
    
    // Restart scanner after modal closes
    restartScanner();
}

function restartScanner() {
    // Clear result message
    clearResultMessage();
    
    // Show scanner overlay
    showScannerOverlay();
    
    // Re-focus on manual input
    const manualInput = document.getElementById('manualIdInput');
    if (manualInput) {
        manualInput.focus();
    }
}

    function speakConfirmationMessage(data) {
        let message = '';
        
        if (data.attendance_status === 'PRESENT RECORDED') {
            message = `Attendance recorded for ${data.full_name || 'student'}. Marked present.`;
        } else if (data.attendance_status === 'ALREADY RECORDED') {
            message = `Attendance already recorded for ${data.full_name || 'student'}`;
        } else if (data.error) {
            message = data.error;
        } else {
            message = "Attendance recorded";
        }
        
        speakMessage(message);
    }

// Manual Input Processing
function processManualInput() {
    const manualInput = document.getElementById('manualIdInput');
    const idNumber = manualInput ? manualInput.value.trim() : '';
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        return;
    }
    
    // Show processing state
    updateResultMessage(`Processing manual input: ${idNumber}`, 'info');
    
    // Disable inputs during processing
    setManualInputsDisabled(true);
    
    // Process the ID
    processBarcode(idNumber);
    
    // Re-enable inputs after a delay
    setTimeout(() => {
        setManualInputsDisabled(false);
        if (manualInput) {
            manualInput.value = '';
            manualInput.focus();
        }
    }, 2000);
}

function setManualInputsDisabled(disabled) {
    const manualInput = document.getElementById('manualIdInput');
    const manualSubmitBtn = document.getElementById('manualSubmitBtn');
    
    if (manualInput) manualInput.disabled = disabled;
    if (manualSubmitBtn) manualSubmitBtn.disabled = disabled;
}

// UI Helper Functions
function updateResultMessage(message, type = 'info') {
    const resultElement = document.getElementById('result');
    if (!resultElement) return;
    
    const alertClass = type === 'error' ? 'alert-danger' : 'alert-info';
    const iconClass = type === 'error' ? 'fa-exclamation-triangle' : 'fa-spinner fa-spin';
    
    resultElement.innerHTML = `
        <div class="alert ${alertClass} d-flex align-items-center">
            <i class="fas ${iconClass} me-2"></i>
            <div>${message}</div>
        </div>
    `;
}

function clearResultMessage() {
    const resultElement = document.getElementById('result');
    if (resultElement) {
        resultElement.innerHTML = '';
    }
}

function showErrorMessage(message) {
    updateResultMessage(message, 'error');
    playAlertSound();
    speakMessage(message);
    
    // Auto-clear error after 3 seconds and restart scanner
    setTimeout(() => {
        clearResultMessage();
        showScannerOverlay();
    }, 3000);
}

function playAlertSound() {
    const audio = document.getElementById('myAudio');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(error => {
            console.log('Audio playback failed:', error);
        });
    }
}

function speakMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 3;
        speech.rate = .9;
        speech.pitch = 1.1;
        
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const voice = voices.find(v => v.lang.includes('en')) || voices[0];
            speech.voice = voice;
        }
        
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
    
    m = formatTimeUnit(m);
    s = formatTimeUnit(s);
    
    // Update clock display
    const clockElement = document.getElementById('clock');
    if (clockElement) {
        clockElement.innerHTML = `${h}:${m}:${s} ${period}`;
    }
    
    // Update date display
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        dateElement.innerHTML = today.toLocaleDateString('en-US', options);
    }
    
    setTimeout(startTime, 1000);
}

function formatTimeUnit(unit) {
    return unit < 10 ? "0" + unit : unit;
}

// Page Visibility Handling
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, stop scanner to conserve resources
        if (scanner) {
            scanner.clear().catch(console.error);
        }
    } else {
        // Page is visible again, restart scanner
        initializeScanner();
    }
});

// Clean up when leaving page
window.addEventListener('beforeunload', function() {
    if (scanner) {
        scanner.clear().catch(console.error);
    }
});
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>
</body>
</html> 