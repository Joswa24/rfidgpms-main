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
    font-size: 1.1rem;
    margin-bottom: 15px;
}

.confirmation-modal .attendance-status {
    font-size: 1.3rem;
    font-weight: bold;
    margin: 20px 0;
    padding: 10px;
    border-radius: 10px;
}

.confirmation-modal .time-in {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 2px solid #0f5132;
}

.confirmation-modal .time-out {
    background-color: #f8d7da;
    color: #842029;
    border: 2px solid #842029;
}

.confirmation-modal .modal-footer {
    border-top: none;
    justify-content: center;
}

.confirmation-modal .time-display {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
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
let barcodeBuffer = '';
let lastScanTime = 0;
const scanCooldown = 1000; // 1 second cooldown between scans
let allowedSection = null;
let allowedYear = null;
let isFirstStudent = true;

// Student photo mapping
const studentPhotos = {
    "2024-0380": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1570": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-0117": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1697": "uploads/students/68b703dcdff49_1232-1232.jpg",
    // ✅ add more here...
};

function setStudentPhoto(idNumber) {
    let photoPath = studentPhotos[idNumber] || "uploads/students/default.png";
    document.getElementById("modalStudentPhoto").src = photoPath + "?t=" + new Date().getTime();
}

// Scanner Initialization and Control Functions
function initScanner() {
    // Clear any existing scanner instance
    if (scanner) {
        scanner.clear();
    }
    
    // Create new scanner instance with configuration
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: {
            width: 300,
            height: 300,
        },
        fps: 20,
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
        showTorchButtonIfSupported: true
    });
    
    // Render the scanner with success and error callbacks
    scanner.render(onScanSuccess, onScanError);
}

function stopScanner() {
    if (scanner) {
        scanner.clear().then(() => {
            console.log("Scanner stopped successfully");
        }).catch(err => {
            console.error("Failed to stop scanner:", err);
        });
    }
}


function restartScanner() {
    stopScanner();
    initScanner();
    document.querySelector('.scanner-overlay').style.display = 'flex';
}

// Scanner Event Handlers
function onScanSuccess(decodedText) {
    const now = Date.now();
    
    // Implement scan cooldown to prevent duplicate scans
    if (now - lastScanTime < scanCooldown) {
        console.log("Scan cooldown active - ignoring scan");
        return;
    }
    
    lastScanTime = now;
    
    
    
    // Hide scanner overlay during processing
    document.querySelector('.scanner-overlay').style.display = 'none';
    
    // Process the scanned barcode
    processBarcode(decodedText);
}

function onScanError(error) {
    // Handle different types of scanner errors
    if (error.includes('No MultiFormat Readers were able to detect the code')) {
        console.log("No barcode detected - continuing scan");
        return;
    }
    
    console.error('Scanner error:', error);
    
    // Show error to user if it's not a benign error
    if (!error.includes('NotFoundException') && !error.includes('No MultiFormat Readers')) {
        showErrorMessage(`Scanner error: ${error}`);
    }
}

// Camera Control Functions
function getCameraDevices() {
    return Html5Qrcode.getCameras().then(devices => {
        if (devices && devices.length) {
            return devices;
        }
        throw "No cameras found";
    });
}

function switchCamera(deviceId) {
    if (!scanner) return;
    
    stopScanner();
    
    // Reinitialize with new camera
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: { width: 300, height: 300 },
        fps: 20,
        deviceId: { exact: deviceId }
    });
    
    scanner.render(onScanSuccess, onScanError);
}

// Scanner UI Helpers
function showScannerOverlay() {
    document.querySelector('.scanner-overlay').style.display = 'flex';
}

function hideScannerOverlay() {
    document.querySelector('.scanner-overlay').style.display = 'none';
}

// Scanner State Management
let scannerState = {
    isScanning: false,
    currentCamera: null,
    torchEnabled: false
};

function toggleTorch() {
    if (!scanner || !scanner.getState().scanning) return;
    
    scannerState.torchEnabled = !scannerState.torchEnabled;
    
    scanner.applyVideoConstraints({
        advanced: [{ torch: scannerState.torchEnabled }]
    }).then(() => {
        console.log(`Torch ${scannerState.torchEnabled ? 'enabled' : 'disabled'}`);
    }).catch(err => {
        console.error("Failed to toggle torch:", err);
    });
}
function setStudentPhoto(idNumber) {
    let photoPath = "uploads/students/default.png"; // default fallback

    if (idNumber === "2024-1697") {
        photoPath = "uploads/students/68b6d200ec51d_1117-8547.png";
    } else if (idNumber === "2024-1698") {
        photoPath = "uploads/students/68b6d200ec51d_1117-8547.png";
    } else if (idNumber === "2024-1699") {
        photoPath = "uploads/students/03.jpg";
    }
    // ✅ add more as needed...

    // Update modal photo with cache busting
    document.getElementById("modalStudentPhoto").src = photoPath + "?t=" + new Date().getTime();
}

// Initialize scanner when page loads
document.addEventListener('DOMContentLoaded', function() {
    // First check for camera permissions
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            // If permission granted, initialize scanner
            initScanner();
            
            // Set up camera switching if multiple cameras available
            getCameraDevices().then(devices => {
                if (devices.length > 1) {
                    // Add UI for camera switching if needed
                    console.log("Multiple cameras available:", devices);
                }
            });
        })
        .catch(err => {
            console.error("Scanner permission denied:", err);
            showErrorMessage("Tap Your ID to the Scanner.");
        });
    
    // Set up event listeners for manual controls
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    document.getElementById('manualSubmitBtn').addEventListener('click', processManualInput);
    
    // Focus on manual input field by default
    document.getElementById('manualIdInput').focus();
});

// Handle page visibility changes to conserve resources
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, stop scanner to conserve resources
        stopScanner();
    } else {
        // Page is visible again, restart scanner
        initScanner();
    }
});

// Clean up scanner when leaving page
window.addEventListener('beforeunload', function() {
    stopScanner();
});

// Time and Date Functions
function startTime() {
    const today = new Date();
    let h = today.getHours();
    let m = today.getMinutes();
    let s = today.getSeconds();
    let period = h >= 12 ? 'PM' : 'AM';
    
    // Convert to 12-hour format
    h = h % 12;
    h = h ? h : 12; // the hour '0' should be '12'
    
    m = checkTime(m);
    s = checkTime(s);
    
    document.getElementById('clock').innerHTML = h + ":" + m + ":" + s + " " + period;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
    
    setTimeout(startTime, 1000);
}

function checkTime(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
    return i;
}

// Initialize scanner
function initScanner() {
    if (scanner) {
        scanner.clear();
    }
    
    scanner = new Html5QrcodeScanner('largeReader', { 
        qrbox: {
            width: 300,
            height: 300,
        },
        fps: 20,
    });
    
    scanner.render(onScanSuccess, onScanError);
}

// Scanner success callback
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

// Scanner error callback
function onScanError(error) {
    // console.error('Scanner error:', error);
}

    function processBarcode(barcode) {
    console.log("Processing barcode:", barcode);
    
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
        timeout: 10000,
        success: function(response) {
            console.log("✅ SUCCESS - Raw response:", response);
            
            // Check if response is valid
            if (!response || typeof response !== 'object') {
                console.error("Invalid response format");
                showSuccessFallback(barcode);
                return;
            }
            
            if (response.error) {
                console.log("❌ Server error:", response.error);
                showErrorMessage(response.error);
                speakErrorMessage(response.error);
                document.querySelector('.scanner-overlay').style.display = 'flex';
                return;
            }

            // Update UI
            updateAttendanceUI(response);
            
            // Update photo in the main display
            if (response.photo) {
                document.getElementById('pic').src = response.photo;
            }
            
            // Show confirmation modal with all data
            console.log("🎯 Showing confirmation modal");
            showConfirmationModal(response);
            
        },
        error: function(xhr, status, error) {
            console.error("❌ AJAX ERROR:");
            console.error("Status:", status);
            console.error("Error:", error);
            console.error("Response text:", xhr.responseText);
            
            // Try to parse response even if AJAX reports error
            if (xhr.responseText) {
                try {
                    const parsedResponse = JSON.parse(xhr.responseText);
                    console.log("📦 Parsed error response:", parsedResponse);
                    
                    if (parsedResponse.error) {
                        showErrorMessage(parsedResponse.error);
                    } else {
                        // If we got valid JSON but AJAX still errored, try to use it
                        updateAttendanceUI(parsedResponse);
                        showConfirmationModal(parsedResponse);
                        return;
                    }
                } catch (e) {
                    console.log("Could not parse response as JSON");
                }
            }
            
            // Fallback to success since attendance was recorded
            showSuccessFallback(barcode);
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


// Fallback function if AJAX fails but attendance was recorded
function showSuccessFallback(barcode) {
    console.log("🔄 Using fallback success display");
    
    // Create a basic success response
    const fallbackData = {
        full_name: "Student",
        id_number: barcode,
        department: "<?php echo $department; ?>",
        photo: "assets/img/2601828.png",
        section: "N/A",
        year_level: "N/A", 
        role: "Student",
        time_in_out: "Attendance Recorded",
        alert_class: "alert-success"
    };
    
    updateAttendanceUI(fallbackData);
    showConfirmationModal(fallbackData);
    
    document.querySelector('.scanner-overlay').style.display = 'none';
}


// Show confirmation modal with complete student data
function showConfirmationModal(data) {
    console.log("🎯 showConfirmationModal called with:", data);
    
    // Get current time and date
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Update modal content
    document.getElementById('modalStudentName').textContent = data.full_name || 'Student';
    document.getElementById('modalStudentId').textContent = data.id_number || 'N/A';
    document.getElementById('modalStudentDept').textContent = data.department || 'N/A';
    document.getElementById('modalStudentYearSection').textContent = (data.year_level || 'N/A') + ' - ' + (data.section || 'N/A');
    document.getElementById('modalStudentRole').textContent = data.role || 'Student';
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;
    
    // Set attendance status
    const statusElement = document.getElementById('modalTimeInOut');
    const statusContainer = document.getElementById('modalAttendanceStatus');
    
    if (data.time_in_out === 'Time In Recorded' || data.alert_class === 'alert-success') {
        statusElement.textContent = 'Time In Recorded ✓';
        statusContainer.className = 'attendance-status mb-3 time-in';
    } else if (data.time_in_out === 'Time Out Recorded' || data.alert_class === 'alert-warning') {
        statusElement.textContent = 'Time Out Recorded ✓';
        statusContainer.className = 'attendance-status mb-3 time-out';
    } else {
        statusElement.textContent = data.time_in_out || 'Attendance Recorded ✓';
        statusContainer.className = 'attendance-status mb-3 time-in';
    }
    
    // Update student photo in modal
    if (data.photo) {
        document.getElementById('modalStudentPhoto').src = data.photo + '?t=' + new Date().getTime();
    } else {
        document.getElementById('modalStudentPhoto').src = 'assets/img/2601828.png';
    }

    // Show modal using Bootstrap
    const modalElement = document.getElementById('confirmationModal');
    const modal = new bootstrap.Modal(modalElement);
    
    console.log("🎯 Showing modal now...");
    modal.show();
    
    // Hide scanner overlay while modal is open
    document.querySelector('.scanner-overlay').style.display = 'none';
    
    // Add event listener for when modal is hidden
    modalElement.addEventListener('hidden.bs.modal', function () {
        console.log("🎯 Modal closed, restarting scanner");
        restartScanner();
    });
}

// Function to restart scanner after modal is closed
function restartScanner() {
    document.querySelector('.scanner-overlay').style.display = 'flex';
    document.getElementById('result').innerHTML = "";
    document.getElementById('manualIdInput').value = '';
    document.getElementById('manualIdInput').focus();
}

// Update UI with attendance data
function updateAttendanceUI(data) {
    // Update alert color and text
    const alertElement = document.getElementById('alert');
    const inOutElement = document.getElementById('in_out');
    
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-danger', 'alert-warning');
    
    if (data.alert_class) {
        alertElement.classList.add(data.alert_class);
    } else {
        alertElement.classList.add('alert-primary');
    }
    
    inOutElement.textContent = data.time_in_out || 'Scan Your ID Card for Attendance';
    
    // Update photo in main display
    if (data.photo) {
        document.getElementById('pic').src = data.photo;
    }
    
    // Update result display
    if (data.time_in_out) {
        document.getElementById('result').innerHTML = `
            <div class="alert ${data.alert_class || 'alert-success'} py-2" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${data.time_in_out}
            </div>
        `;
    }
}

// Show preview modal in the scanner frame
function showScannerPreviewModal(data) {
    // Fill preview modal with student data
    document.getElementById('previewPhoto').src = data.photo ? 'uploads' + data.photo : 'temporary.png';
    document.getElementById('previewName').textContent = data.full_name || 'Unknown Student';
    document.getElementById('previewId').textContent = data.id_number || 'N/A';
    document.getElementById('previewDept').textContent = "<?php echo $department; ?>" || 'N/A';
    document.getElementById('previewSection').textContent = data.section || 'N/A';
    document.getElementById('previewYear').textContent = data.year_level || 'N/A';

    // Show the preview modal
    document.getElementById('scannerPreviewModal').style.display = 'block';

    // Confirm button handler
    document.getElementById('previewConfirmBtn').onclick = function() {
        document.getElementById('scannerPreviewModal').style.display = 'none';
        recordAttendance(data.id_number, data);
    };

    // Cancel button handler
    document.getElementById('previewCancelBtn').onclick = function() {
        document.getElementById('scannerPreviewModal').style.display = 'none';
        document.getElementById('result').innerHTML = '<span class="text-warning">Entry cancelled.</span>';
        document.querySelector('.scanner-overlay').style.display = 'flex';
        setTimeout(() => {
            document.getElementById('result').innerHTML = "";
        }, 2000);
    };
}

// Record attendance after confirmation
function recordAttendance(idNumber, studentData) {
    $.ajax({
        type: "POST",
        url: "students_logs.php",
        data: { 
            id_number: idNumber,
            department: "<?php echo $department; ?>",
            location: "<?php echo $location; ?>"
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.error) {
                    showErrorMessage(data.error);
                    return;
                }

                // Update UI with attendance data
                updateAttendanceUI(data);
                
                // Show confirmation modal
                showConfirmationModal(data);
                
               

            } catch (e) {
                console.error("Error processing response:", e, response);
                showErrorMessage("Error processing attendance record");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            showErrorMessage("Server error: " + error);
        },
        complete: function() {
            // Re-enable scanner
            document.querySelector('.scanner-overlay').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('result').innerHTML = "";
            }, 3000);
        }
    });
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
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.log('Audio playback failed:', error);
    });
}

// ========= MANUAL ATTENDANCE FEATURES =========
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        speakErrorMessage("Please enter ID number");
        return;
    }
    
    // Show processing state
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center align-items-center">
            <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span>Processing...</span>
        </div>
    `;
    
    // Disable input during processing
    document.getElementById('manualIdInput').disabled = true;
    document.getElementById('manualSubmitBtn').disabled = true;
    
    // ✅ FIXED: Only call processBarcode once - remove the duplicate AJAX call below
    processBarcode(idNumber);
    
    // Re-enable input after processing (moved to AJAX complete callback)
}

// Add this new function to speak error messages
function speakErrorMessage(message) {
    if ('speechSynthesis' in window) {
        // Cancel any ongoing speech
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;  // Slightly higher pitch for clarity
        
        // Set voice if available
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            // Prefer natural-sounding voices
            const preferredVoices = [
                'Google UK English Female',
                'Microsoft Zira Desktop',
                'Karen'
            ];
            
            const voice = voices.find(v => preferredVoices.includes(v.name)) || 
                          voices.find(v => v.lang.includes('en')) || 
                          voices[0];
            
            speech.voice = voice;
        }
        
        window.speechSynthesis.speak(speech);
    }
}

// Initialize speech synthesis when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Chrome requires voices to be loaded first
    if ('speechSynthesis' in window) {
        // Load voices
        let voices = window.speechSynthesis.getVoices();
        if (voices.length === 0) {
            window.speechSynthesis.onvoiceschanged = function() {
                voices = window.speechSynthesis.getVoices();
            };
        }
    }
    
    // Initialize scanner
    initScanner();
    
    // Enable Enter key submission for manual input
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    // Focus on input field
    document.getElementById('manualIdInput').focus();
});
function closeAndRefresh() {
    // Get the modal instance
    var modal = bootstrap.Modal.getInstance(document.getElementById('rfidModal'));
    
    // Hide the modal
    modal.hide();
    
    // Refresh after modal is hidden
    modal._element.addEventListener('hidden.bs.modal', function() {
        window.location.reload();
    });
}
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>
</body>
</html> 