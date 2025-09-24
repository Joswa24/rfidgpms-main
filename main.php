<?php
session_start();

// Check if user is logged in as security personnel
if (!isset($_SESSION['access']) || !isset($_SESSION['access']['security'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Set session variables for gate access
$_SESSION['department'] = 'Main';
$_SESSION['location'] = 'Gate';
$_SESSION['descr'] = 'Gate';

// Safely get department and location from session
$department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Main';
$location = isset($_SESSION['location']) ? $_SESSION['location'] : 'Gate';

$logo1 = $nameo = $address = $logo2 = "";

// Fetch data from the about table
if (isset($db)) {
    $sql = "SELECT * FROM about LIMIT 1";
    $result = $db->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $logo1 = $row['logo1'];
        $nameo = $row['name'];
        $address = $row['address'];
        $logo2 = $row['logo2'];
    }
    
}
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
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
    
    <title>Gate Entrance Scanner</title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
    <style>
        /* Main gate scanner styles */
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

        .dept-location-info {
            background: linear-gradient(135deg, #084298 0%, #052c65 100%);
            color: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .dept-location-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
        }
        
        .dept-location-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .preview-1 {
            width: 140px!important;
            height: 130px!important;
            position: absolute;
            border: 1px solid gray;
            top: 15%;
            cursor: pointer;
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
            margin: 0 auto;
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
            justify-content: center;
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
        
        .manual-input-section {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dee2e6;
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
            background-color: #084298;
            border-color: #084298;
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
        
        .confirmation-modal .person-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid #084298;
        }
        
        .confirmation-modal .person-info {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .confirmation-modal .access-status {
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
        
        .confirmation-modal .access-denied {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .confirmation-modal .modal-footer {
            border-top: none;
            justify-content: center;
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
        
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }
        
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        /* Clock styling */
        #clockdate {
            border: 1px solid #084298;
            background-color: #084298;
            height: 70px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .clockdate-wrapper {
            height: 100%;
        }
        
        #clock {
            font-weight: bold;
            color: #fff;
            font-size: 1.8rem;
            line-height: 1.2;
        }
        
        #date {
            color: #fff;
            font-size: 0.8rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .large-scanner-container {
                height: 40vh;
                max-height: 250px;
            }
            
            #clock {
                font-size: 1.5rem;
            }
            
            .dept-location-info h3 {
                font-size: 1.1rem;
            }
            
            .confirmation-modal .person-photo {
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

<img src="uploads/Head.png" style="width: 100%; height: 150px; margin-left: 10px; padding=10px; margin-top=20px;">

<!-- Confirmation Modal -->
<div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-door-open me-2"></i>Gate Access Recorded
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <!-- Person Photo -->
                <div class="mb-3">
                    <img id="modalPersonPhoto"
                         src="uploads/students/default.png"
                         alt="Person Photo"
                         class="person-photo">
                </div>

                <h4 id="modalPersonName"></h4>
                
                <div class="person-info">
                    <div>ID: <span id="modalPersonId"></span></div>
                    <div>Role: <span id="modalPersonRole"></span></div>
                    <div>Department: <span id="modalPersonDept"></span></div>
                </div>
                
                <div class="access-status" id="modalAccessStatus">
                    <span id="modalAccessType"></span>
                </div>
                
                <div class="time-display">
                    <div id="modalTimeDisplay"></div>
                    <div id="modalDateDisplay"></div>
                </div>
            </div>
            <div class="modal-footer">
                 <button type="button" class="btn btn-primary" style="background-color: #084298" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="container mt-3">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active active-tab" aria-current="page" href="#">Gate Scanner</a>
        </li>
       <li class="nav-item">
           <a class="nav-link" href="gate_logs.php">Gate Access Log</a>
       </li>
    </ul>
</div>

<section class="hero" style="margin-top: 0; height: calc(100vh - 140px);">
    <div class="container h-100">
        <!-- Gate Info Display -->
        <div class="dept-location-info mb-2">
            <h3><i class="fas fa-door-open me-2"></i>MAIN GATE ACCESS PORTAL</h3>
            <p>Students, Faculty, Staff & Visitors Entry/Exit System</p>
        </div>
        
        <!-- Clock Display -->
        <center>
            <div id="clockdate">
                <div class="clockdate-wrapper d-flex flex-column justify-content-center" style="height:100%;">
                    <div id="clock"></div>
                    <div id="date"><span id="currentDate"></span></div>
                </div>
            </div>
        </center>
        
        <!-- Main Content Row -->
        <div class="row" style="height: calc(100% - 120px);">
            <!-- Scanner Column (70% width) -->
            <div class="col-md-8 h-100" style="padding-right: 5px;">
                <div class="alert alert-primary py-1 mb-2" role="alert" id="alert">
                    <center><h3 id="in_out" class="mb-0" style="font-size: 1rem;">
                        <i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Logs
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
            
            <!-- Photo/Manual Input Column (30% width) -->
            <div class="col-md-4 h-100 d-flex flex-column" style="padding-left: 5px;">
                <!-- Person Photo -->
                <img id="pic" class="mb-2" alt="Person Photo"; 
                     src="assets/img/section/type.jpg"
                     style="margin-top: .5px; width: 100%; height: 200px; object-fit: cover; border: 2px solid #084298; border-radius: 3px;">
                
                <!-- Manual Input Section -->
                <div class="manual-input-section flex-grow-1" style="padding: 10px; margin-bottom:60px;">
                    <h4 class="mb-1" style="font-size: 1rem;"><i class="fas fa-keyboard"></i> Manual Entry</h4>
                    <p class="text-center mb-2" style="font-size: 0.8rem;">For visitors or forgot ID</p>
                    
                    <div class="input-group mb-1">
                        <input type="text" 
                               class="form-control" 
                               id="manualIdInput" 
                               placeholder="Enter ID Number"
                               style="height: 40px; font-size: 0.9rem;">
                        <button class="btn btn-primary" 
                                id="manualSubmitBtn" 
                                style="height: 40px; font-size: 0.9rem; background-color: #084298;"
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
let barcodeBuffer = '';
let lastScanTime = 0;
const scanCooldown = 1000; // 1 second cooldown between scans

// Person photo mapping
const personPhotos = {
    "2024-0380": "uploads/students/68b703dcdff49_1232-1232.jpg",
    "2024-1570": "uploads/students/c9c9ed00-ab5c-4c3e-b197-56559ab7ca61.jpg",
    "2024-1697": "uploads/students/68b75972d9975_5555-7777.jpg",
    // Add more mappings as needed
};

// Initialize scanner
function initScanner() {
    if (scanner) {
        scanner.clear().catch(error => {
            console.log("Scanner already cleared or not initialized");
        });
    }
    
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
    
    scanner.render(onScanSuccess, onScanError);
}

// Scanner success callback
function onScanSuccess(decodedText) {
    const now = Date.now();
    
    if (now - lastScanTime < scanCooldown) {
        console.log("Scan cooldown active - ignoring scan");
        return;
    }
    
    lastScanTime = now;
    
    document.getElementById('result').innerHTML = `
        <span class="blink">Processing: ${decodedText}</span>
    `;
    
    document.querySelector('.scanner-overlay').style.display = 'none';
    processBarcode(decodedText);
}

// Scanner error callback
function onScanError(error) {
    // Handle different types of scanner errors
    if (error.includes('No MultiFormat Readers were able to detect the code')) {
        console.log("No barcode detected - continuing scan");
        return;
    }
    
    console.error('Scanner error:', error);
}


// Process scanned barcode
function processBarcode(barcode) {
    $.ajax({
        type: "POST",
        url: "process_gate.php",
        data: { 
            barcode: barcode,
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

                // Update UI with gate access data
                updateGateUI(data);
                
                // Show confirmation modal
                showConfirmationModal(data);
            } catch (e) {
                console.error("Error parsing response:", e, response);
                showErrorMessage("Server response error");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            showErrorMessage("Connection error. Please try again.");
        },
        complete: function() {
            // Re-enable scanner after processing
            document.querySelector('.scanner-overlay').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('result').innerHTML = "";
            }, 3000);
        }
    });
}

// Update gate UI with access data
function updateGateUI(data) {
    const alertElement = document.getElementById('alert');
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-danger', 'alert-warning');
    
    if (data.time_in_out === 'TIME IN') {
        alertElement.classList.add('alert-success');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>ENTRY GRANTED';
    } else if (data.time_in_out === 'TIME OUT') {
        alertElement.classList.add('alert-warning');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>EXIT RECORDED';
    } else if (data.time_in_out === 'UNAUTHORIZED') {
        alertElement.classList.add('alert-danger');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ACCESS DENIED';
    } else if (data.time_in_out === 'COMPLETED') {
        alertElement.classList.add('alert-info');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-check-circle me-2"></i>ALREADY COMPLETED';
    } else {
        alertElement.classList.add('alert-primary');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
    }
    
    // Update photo
    if (data.photo) {
        document.getElementById('pic').src = data.photo;
    } else {
        document.getElementById('pic').src = "uploads/students/default.png";
    }
}

// Update gate UI with access data
function updateGateUI(data) {
    const alertElement = document.getElementById('alert');
    alertElement.classList.remove('alert-primary', 'alert-success', 'alert-danger', 'alert-warning');
    
    if (data.time_in_out === 'TIME IN') {
        alertElement.classList.add('alert-success');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>ENTRY GRANTED';
    } else if (data.time_in_out === 'TIME OUT') {
        alertElement.classList.add('alert-warning');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-sign-out-alt me-2"></i>EXIT RECORDED';
    } else if (data.time_in_out === 'UNAUTHORIZED') {
        alertElement.classList.add('alert-danger');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ACCESS DENIED';
    } else {
        alertElement.classList.add('alert-primary');
        document.getElementById('in_out').innerHTML = '<i class="fas fa-id-card me-2"></i>Scan Your ID Card for Gate Access';
    }
    
    // Update photo
    if (data.photo) {
        document.getElementById('pic').src = data.photo;
    } else if (personPhotos[data.id_number]) {
        document.getElementById('pic').src = personPhotos[data.id_number] + "?t=" + new Date().getTime();
    } else {
        document.getElementById('pic').src = "uploads/students/default.png";
    }
}

// Show confirmation modal
function showConfirmationModal(data) {
    const now = new Date();
    const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateString = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    // Update modal content
    document.getElementById('modalPersonName').textContent = data.full_name || 'Unknown';
    document.getElementById('modalPersonId').textContent = data.id_number || 'N/A';
    document.getElementById('modalPersonRole').textContent = data.role || 'N/A';
    document.getElementById('modalPersonDept').textContent = data.department || 'N/A';
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;

    // Set photo
    let photoPath = "uploads/students/default.png";
    if (data.photo) {
        photoPath = data.photo;
    } else if (personPhotos[data.id_number]) {
        photoPath = personPhotos[data.id_number];
    }
    document.getElementById("modalPersonPhoto").src = photoPath + "?t=" + new Date().getTime();

    // Update access status
    const statusElement = document.getElementById('modalAccessStatus');
    statusElement.className = 'access-status';
    
    if (data.time_in_out === 'TIME IN') {
        statusElement.classList.add('time-in');
        statusElement.innerHTML = `
            <i class="fas fa-sign-in-alt me-2"></i>
            ENTRY GRANTED
        `;
        speakMessage(`Welcome ${data.first_name || data.full_name || ''}`);
    } else if (data.time_in_out === 'TIME OUT') {
        statusElement.classList.add('time-out');
        statusElement.innerHTML = `
            <i class="fas fa-sign-out-alt me-2"></i>
            EXIT RECORDED
        `;
        speakMessage(`Goodbye ${data.first_name || data.full_name || ''}`);
    } else {
        statusElement.classList.add('access-denied');
        statusElement.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ACCESS DENIED
        `;
        speakMessage("Access denied");
    }

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
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
    speakMessage(message);
}

// Play alert sound
function playAlertSound() {
    const audio = document.getElementById('myAudio');
    audio.currentTime = 0;
    audio.play().catch(error => {
        console.log('Audio playback failed:', error);
    });
}

// Speak message
function speakMessage(message) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        
        const speech = new SpeechSynthesisUtterance();
        speech.text = message;
        speech.volume = 1;
        speech.rate = 1;
        speech.pitch = 1.1;
        
        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            const voice = voices.find(v => v.lang.includes('en')) || voices[0];
            speech.voice = voice;
        }
        
        window.speechSynthesis.speak(speech);
    }
}

// Manual input processing
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        speakMessage("Please enter ID number");
        return;
    }
    
    document.getElementById('result').innerHTML = `
        <div class="d-flex justify-content-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2">Processing...</span>
        </div>
    `;
    
    document.getElementById('manualIdInput').disabled = true;
    document.getElementById('manualSubmitBtn').disabled = true;
    
    processBarcode(idNumber);
    
    // Clear and re-enable input
    setTimeout(() => {
        document.getElementById('manualIdInput').value = '';
        document.getElementById('manualIdInput').disabled = false;
        document.getElementById('manualSubmitBtn').disabled = false;
        document.getElementById('manualIdInput').focus();
    }, 2000);
}

// Time and Date Functions
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

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize speech synthesis
    if ('speechSynthesis' in window) {
        let voices = window.speechSynthesis.getVoices();
        if (voices.length === 0) {
            window.speechSynthesis.onvoiceschanged = function() {
                voices = window.speechSynthesis.getVoices();
            };
        }
    }
    
    // Check for camera permissions and initialize scanner
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(() => {
            initScanner();
        })
        .catch(err => {
            console.error("Scanner permission denied:", err);
            showErrorMessage("Tap Your ID to the Scanner");
        });
    
    // Enable Enter key submission for manual input
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    // Focus on input field
    document.getElementById('manualIdInput').focus();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (scanner) scanner.clear().catch(() => {});
    } else {
        initScanner();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (scanner) scanner.clear().catch(() => {});
});
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>