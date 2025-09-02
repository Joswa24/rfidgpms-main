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
    
    <title>Barcode Scanner</title>
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
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 
<div id="message"></div>


<!-- Confirmation Modal -->
<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Recorded</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalStudentPhoto" class="student-photo" src="uploads/temporary.png" type="image/png" alt="Student Photo">
                <h4 id="modalStudentName"></h4>
                
                <div class="student-info">
                    <div>ID: <span id="modalStudentId"></span></div>
                    <div>Department: <span id="modalStudentDept"></span></div>
                    <div>Role: <span id="modalStudentRole"></span></div>
                </div>
                
                <div class="attendance-status" id="modalAttendanceStatus">
                    <span id="modalTimeInOut"></span>
                </div>
                
                <div class="time-display">
                    <div id="modalTimeDisplay"></div>
                    <div id="modalDateDisplay"></div>
                </div>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-primary"style="background-color: #87abe0ff" onclick="window.location.href='main1.php'">OK</button>
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

<nav class="navbar navbar-expand-lg navbar-light py-2" style="height: 1%; border-bottom: 1px solid #FBC257; margin-bottom: 1%; padding: 0px 50px 0px 50px; display: flex; justify-content: center; align-items: center;">
    <div style="text-align: left; margin-right: 10px;">
        <img src="<?php echo 'admin/uploads/'.$logo1; ?>" alt="Image 1" style="height: 100px;">
    </div>
    <div class="column wide" style="flex-grow: 2; text-align: center;">
        <h2><?php echo $nameo; ?></h2>
    </div>
    <div style="text-align: right; margin-left: 10px;">
        <img src="<?php echo 'admin/uploads/'.$logo2; ?>" alt="Image 2" style="height: 100px;">
    </div>
</nav>

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
                    <center><h3 id="in_out" class="mb-0" style="font-size: 1rem;">Scan Your ID Barcode</h3></center>
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
                <img id="pic" class="mb-2" alt="Student Photo" 
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
    $.ajax({
        type: "POST",
        url: "process_barcode.php",
        data: { 
            barcode: barcode,
            current_department: "<?php echo $department; ?>",
            current_location: "<?php echo $location; ?>",
            is_first_student: <?php echo $_SESSION['is_first_student'] ? 'true' : 'false'; ?>,
            allowed_section: "<?php echo $_SESSION['allowed_section'] ?? ''; ?>",
            allowed_year: "<?php echo $_SESSION['allowed_year'] ?? ''; ?>"
        },
        success: function(response) {
            const data = typeof response === 'string' ? JSON.parse(response) : response;

            if (data.error) {
                showErrorMessage(data.error);
                return;
            }

            // If first student, set allowed section/year
            if (<?php echo $_SESSION['is_first_student'] ? 'true' : 'false'; ?> && data.section && data.year_level) {
                // Store in session via AJAX
                $.post('set_session.php', {
                    allowed_section: data.section,
                    allowed_year: data.year_level,
                    is_first_student: false
                });
                
                // Update local variables
                allowedSection = data.section;
                allowedYear = data.year_level;
                isFirstStudent = false;
            }

            // Show confirmation modal
            showConfirmationModal(data);
        }
    });
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
        url: "student_logs.php",
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

// Show confirmation modal with complete student data
function showConfirmationModal(data) {
    // Get current time and date
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    // Update modal content with complete student data
    document.getElementById('modalStudentPhoto').src = 
        data.photo ? 'uploads' + data.photo : 
        'temporary.png';
        
    document.getElementById('modalStudentName').textContent = 
        data.full_name || 'Unknown Student';
        
    document.getElementById('modalStudentId').textContent = 
        data.id_number || 'N/A';
        
    document.getElementById('modalStudentDept').textContent = 
        "<?php echo $department; ?> " || 'N/A';
        
    document.getElementById('modalStudentRole').textContent = 
        data.role || 'N/A';
        
    document.getElementById('modalTimeInOut').textContent = 
        data.time_in_out || 'Attendance Recorded';
        
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;
    
    // Update status color
    const statusElement = document.getElementById('modalAttendanceStatus');
    statusElement.className = 'attendance-status';
    
    if (data.alert_class === 'alert-success') {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-sign-in-alt me-2"></i>
            ${data.time_in_out || 'Time In Recorded'}
        `;
    } else {
        statusElement.classList.add('time-out');
        statusElement.innerHTML = `
            <i class="fas fa-sign-out-alt me-2"></i>
            ${data.time_in_out || 'Time Out Recorded'}
        `;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Update UI with attendance data
function updateAttendanceUI(data) {
    // Update alert color and text
    const alertElement = document.getElementById('alert');
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-danger', 'alert-warning');
    alertElement.classList.add(data.alert_class || 'alert-primary');
    document.getElementById('in_out').textContent = data.time_in_out || 'Scan Your ID Barcode';
    
    // Update photo
    if (data.photo) {
        document.getElementById('pic').src = 'admin/uploads/' + data.photo;
    }
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

// Process manual input
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
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Processing...</span>
        </div>
    `;
    
    // Disable input during processing
    document.getElementById('manualIdInput').disabled = true;
    document.getElementById('manualSubmitBtn').disabled = true;
    
    // Process the attendance
    $.ajax({
        type: "POST",
        url: "process_barcode.php",
        data: { 
            barcode: idNumber,
            current_department: "<?php echo $department; ?>",
            current_location: "<?php echo $location; ?>",
            is_first_student: isFirstStudent
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.error) {
                    showErrorMessage(data.error);
                    speakErrorMessage(data.error);
                    return;
                }

                // Update UI immediately
                updateAttendanceUI(data);
                
                // Update student photo
                document.getElementById('pic').src = data.photo ? 'uploads' + data.photo : 'temporary.png';
                
                // Show confirmation modal
                showConfirmationModal(data);
                
                // If this is the first student, set allowed section/year
                if (isFirstStudent && data.section && data.year_level) {
                    allowedSection = data.section;
                    allowedYear = data.year_level;
                    isFirstStudent = false;
                }

            } catch (e) {
                console.error("Error processing response:", e, response);
                
                
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            const msg = "Connection error. Please try again.";
            showErrorMessage(msg);
            speakErrorMessage(msg);
        },
        complete: function() {
            // Re-enable input
            document.getElementById('manualIdInput').value = '';
            document.getElementById('manualIdInput').disabled = false;
            document.getElementById('manualSubmitBtn').disabled = false;
            document.getElementById('manualIdInput').focus();
        }
    });
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