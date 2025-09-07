<?php
include 'connection.php';
session_start();

// Set session variables for gate access
$_SESSION['department'] = 'Main';
$_SESSION['location'] = 'Gate';
$_SESSION['descr'] = 'Gate';

$logo1 = "";
$nameo = "";
$address = "";
$logo2 = "";

// Fetch data from the about table
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'];
    $nameo = $row['name'];
    $address = $row['address'];
    $logo2 = $row['logo2'];
} 

// Get current period
$current_period = date('A');

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
    
    <title>RFIDGACPMS</title>
    <link rel="icon" href="admin/uploads/logo.png" type="image/png">
    <style>
        /* Gate-specific styles */
        .gate-header {
            background: linear-gradient(135deg, #084298 0%, #052c65 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 3px solid #FBC257;
        }
        
        .gate-title {
            font-size: 2.5rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin: 0;
        }
        
        .gate-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin: 5px 0 0 0;
        }
        
        .gate-scanner-container {
            background-color: #f8f9fa;
            border: 3px solid #084298;
            border-radius: 15px;
            padding: 20px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .scanner-display {
            position: relative;
            width: 100%;
            height: 300px;
            background-color: #000;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px auto;
        }
        
        .scanner-laser {
            position: absolute;
            width: 100%;
            height: 3px;
            background: #FBC257;
            top: 0;
            animation: scan 2s infinite;
            box-shadow: 0 0 20px #FBC257;
            z-index: 10;
        }
        
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        
        .scanner-overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 1.5rem;
            text-align: center;
            z-index: 5;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
        }
        
        .gate-status-panel {
            background-color: white;
            border: 2px solid #084298;
            border-radius: 10px;
            padding: 15px;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .status-indicator {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .status-timein {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .status-timeout {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .status-unauthorized {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        
        .personnel-details {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-label {
            font-weight: bold;
            color: #084298;
        }
        
        .detail-value {
            color: #495057;
        }
        
        .gate-clock {
            background-color: #084298;
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin: 20px auto;
            max-width: 400px;
        }
        
        .clock-time {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .clock-date {
            font-size: 1.2rem;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .manual-input-section {
            background-color: white;
            border: 2px solid #084298;
            border-radius: 10px;
            padding: 15px;
            margin: 20px auto;
            max-width: 500px;
        }
        
        .input-title {
            color: #084298;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }
        
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .gate-title {
                font-size: 2rem;
            }
            
            .scanner-display {
                height: 250px;
            }
            
            .clock-time {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body onload="startTime()">
<audio id="myAudio" hidden>
    <source src="admin/audio/alert.mp3" type="audio/mpeg">
</audio> 

<div class="gate-header">
    <div class="container text-center">
        <h1 class="gate-title"><?php echo $nameo; ?></h1>
        <p class="gate-subtitle">Main Gate </p>
    </div>
</div>

<div class="container">
    <!-- Clock Display -->
    <div class="gate-clock">
        <div id="clock" class="clock-time"></div>
        <div id="date" class="clock-date"><span id="currentDate"></span></div>
    </div>
    
    <!-- Scanner Area -->
    <div class="gate-scanner-container text-center">
        <h3 class="mb-3"><i class="fas fa-qrcode me-2"></i>ID Scanner</h3>
        
        <div class="scanner-display">
            <div class="scanner-laser"></div>
            <div class="scanner-overlay-text">
                <i class="fas fa-camera fa-2x mb-2"></i><br>
                Position ID Card in Front of Scanner
            </div>
            <div id="largeReader" style="width: 100%; height: 100%;"></div>
        </div>
        
        <div id="result" class="mt-3"></div>
    </div>
    
    <!-- Status Panel -->
    <div class="gate-status-panel">
        <div id="alert" class="status-indicator status-timein">
            <i class="fas fa-id-card me-2"></i>
            <span id="in_out">Scan Your ID Card</span>
        </div>
        
        <div class="personnel-details">
            <div class="text-center mb-3">
                <img id="pic" class="rounded-circle" alt="Personnel Photo" 
                     src="assets/img/section/istockphoto-1184670010-612x612.jpg"
                     style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #084298;">
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span id="entrant_name" class="detail-value">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">ID Number:</span>
                <span id="display_id" class="detail-value">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Department:</span>
                <span id="department" class="detail-value">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Role:</span>
                <span id="role" class="detail-value">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Time In:</span>
                <span id="time_in" class="detail-value">-</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Time Out:</span>
                <span id="time_out" class="detail-value">-</span>
            </div>
        </div>
    </div>
    
    <!-- Manual Input Section -->
    <div class="manual-input-section">
        <h4 class="input-title"><i class="fas fa-keyboard me-2"></i>Manual Entry</h4>
        
        <div class="input-group mb-3">
            <input type="text" class="form-control form-control-lg" 
                   id="manualIdInput" placeholder="Enter ID Number (0000-0000)"
                   style="text-align: center;">
            <button class="btn btn-primary btn-lg" 
                    id="manualSubmitBtn" 
                    style="background-color: #084298; border-color: #084298;"
                    onclick="processManualInput()">
                <i class="fas fa-paper-plane me-1"></i> Submit
            </button>
        </div>
        
        <div class="text-center">
            <small class="text-muted">For personnel who forgot their ID card</small>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Attendance Recorded</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalStudentPhoto" class="rounded-circle mb-3" 
                     src="assets/img/section/istockphoto-1184670010-612x612.jpg" 
                     alt="Student Photo" style="width: 100px; height: 100px; object-fit: cover;">
                
                <h4 id="modalStudentName" class="mb-2"></h4>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <strong>ID:</strong><br>
                        <span id="modalStudentId"></span>
                    </div>
                    <div class="col-6">
                        <strong>Role:</strong><br>
                        <span id="modalStudentRole"></span>
                    </div>
                </div>
                
                <div class="alert" id="modalAttendanceStatus">
                    <strong id="modalTimeInOut"></strong>
                </div>
                
                <div class="mt-3">
                    <div id="modalTimeDisplay" class="fw-bold"></div>
                    <div id="modalDateDisplay" class="text-muted"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global variables
let scanner = null;
let lastScanTime = 0;
const scanCooldown = 1000; // 1 second cooldown between scans

// Initialize scanner
function initScanner() {
    if (scanner) {
        scanner.clear().catch(error => {
            console.log("Scanner already cleared or not initialized");
        });
    }
    
    scanner = new Html5QrcodeScanner("largeReader", {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
    });
    
    scanner.render(onScanSuccess, onScanError);
}

// Scanner success callback
function onScanSuccess(decodedText) {
    const now = Date.now();
    if (now - lastScanTime < scanCooldown) return;
    
    lastScanTime = now;
    
    document.getElementById('result').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin me-2"></i>
            Processing: ${decodedText}
        </div>
    `;
    
    processBarcode(decodedText);
}

// Scanner error callback
function onScanError(error) {
    // Ignore common non-error messages
    if (!error.includes('NotFoundException') && !error.includes('No MultiFormat Readers')) {
        console.log('Scanner message:', error);
    }
}

// Process scanned barcode
function processBarcode(barcode) {
    $.ajax({
        type: "POST",
        url: "process_gate.php", // Changed to gate-specific processor
        data: { 
            rfid_number: barcode,
            department: "<?php echo $_SESSION['department']; ?>",
            location: "<?php echo $_SESSION['location']; ?>"
        },
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.error) {
                    showErrorMessage(data.error);
                    return;
                }
                
                // Update UI with the response data
                updateGateUI(data);
                
                // Show confirmation modal
                showConfirmationModal(data);
                
            } catch (e) {
                console.error("Error processing response:", e, response);
                showErrorMessage("Error processing ID card");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
            showErrorMessage("Connection error. Please try again.");
        }
    });
}

// Update gate UI with personnel data
function updateGateUI(data) {
    // Update status indicator
    const statusElement = document.getElementById('alert');
    statusElement.className = 'status-indicator';
    
    if (data.time_in_out === 'TIME IN') {
        statusElement.classList.add('status-timein');
        statusElement.innerHTML = `<i class="fas fa-sign-in-alt me-2"></i> TIME IN RECORDED`;
    } else if (data.time_in_out === 'TIME OUT') {
        statusElement.classList.add('status-timeout');
        statusElement.innerHTML = `<i class="fas fa-sign-out-alt me-2"></i> TIME OUT RECORDED`;
    } else if (data.time_in_out === 'UNAUTHORIZED') {
        statusElement.classList.add('status-unauthorized');
        statusElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i> UNAUTHORIZED`;
    } else {
        statusElement.classList.add('status-unauthorized');
        statusElement.innerHTML = `<i class="fas fa-id-card me-2"></i> ${data.time_in_out || 'SCAN YOUR ID CARD'}`;
    }
    
    // Update personnel details
    if (data.photo) {
        document.getElementById('pic').src = 'admin/uploads/' + data.photo;
    }
    
    document.getElementById('entrant_name').textContent = data.full_name || '-';
    document.getElementById('display_id').textContent = data.id_number || '-';
    document.getElementById('department').textContent = data.department || '-';
    document.getElementById('role').textContent = data.role || '-';
    document.getElementById('time_in').textContent = data.time_in || '-';
    document.getElementById('time_out').textContent = data.time_out || '-';
    
    // Speak the status
    if (data.time_in_out === 'TIME IN') {
        speakMessage(`Welcome ${data.first_name || ''}`);
    } else if (data.time_in_out === 'TIME OUT') {
        speakMessage(`Goodbye ${data.first_name || ''}`);
    } else if (data.time_in_out === 'UNAUTHORIZED') {
        speakMessage("Unauthorized access");
    }
}

// Show confirmation modal
function showConfirmationModal(data) {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    const dateString = now.toLocaleDateString();
    
    // Update modal content
    if (data.photo) {
        document.getElementById('modalStudentPhoto').src = 'admin/uploads/' + data.photo;
    }
    
    document.getElementById('modalStudentName').textContent = data.full_name || 'Unknown';
    document.getElementById('modalStudentId').textContent = data.id_number || 'N/A';
    document.getElementById('modalStudentRole').textContent = data.role || 'N/A';
    document.getElementById('modalTimeInOut').textContent = data.time_in_out || 'Attendance Recorded';
    document.getElementById('modalTimeDisplay').textContent = timeString;
    document.getElementById('modalDateDisplay').textContent = dateString;
    
    // Update modal status color
    const statusElement = document.getElementById('modalAttendanceStatus');
    statusElement.className = 'alert';
    
    if (data.time_in_out === 'TIME IN') {
        statusElement.classList.add('alert-success');
    } else if (data.time_in_out === 'TIME OUT') {
        statusElement.classList.add('alert-danger');
    } else {
        statusElement.classList.add('alert-warning');
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
}

// Show error message
function showErrorMessage(message) {
    document.getElementById('result').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
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

// Speak message
function speakMessage(message) {
    if ('speechSynthesis' in window) {
        const speech = new SpeechSynthesisUtterance(message);
        window.speechSynthesis.speak(speech);
    }
}

// Manual input processing
function processManualInput() {
    const idNumber = document.getElementById('manualIdInput').value.trim();
    
    if (!idNumber) {
        showErrorMessage("Please enter ID number");
        return;
    }
    
    // Show processing state
    document.getElementById('result').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin me-2"></i>
            Processing manual entry...
        </div>
    `;
    
    // Process the manual entry
    processBarcode(idNumber);
    
    // Clear input field
    document.getElementById('manualIdInput').value = '';
}

// Time functions
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

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initScanner();
    
    // Enable Enter key in manual input
    document.getElementById('manualIdInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            processManualInput();
        }
    });
    
    // Focus on manual input
    document.getElementById('manualIdInput').focus();
});
</script>
</body>
</html>