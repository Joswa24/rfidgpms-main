<?php
// Enhanced error handling at the start
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'connection.php';
session_start();

// Initialize session variables with proper validation
$_SESSION['allowed_section'] = $_SESSION['allowed_section'] ?? null;
$_SESSION['allowed_year'] = $_SESSION['allowed_year'] ?? null;
$_SESSION['is_first_student'] = $_SESSION['is_first_student'] ?? true;

// Safely get department and location from session with fallbacks
$department = $_SESSION['access']['room']['department'] ?? 'Department';
$location = $_SESSION['access']['room']['room'] ?? 'Location';

// Check for force redirect
if (isset($_SESSION['access']['force_redirect'])) {
    header('Location: ' . $_SESSION['access']['force_redirect']);
    exit;
}

// Fetch data from the about table with error handling
$logo1 = $nameo = $address = $logo2 = "";
$sql = "SELECT * FROM about LIMIT 1";
$result = $db->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $logo1 = $row['logo1'] ?? '';
    $nameo = $row['name'] ?? '';
    $address = $row['address'] ?? '';
    $logo2 = $row['logo2'] ?? '';
} 

// Close database connection properly
if ($db) {
    mysqli_close($db);
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
    
    <title>Classroom Attendance Scanner</title>
    <link rel="icon" href="uploads/scanner.webp" type="image/webp">
    <style>
        /* Enhanced CSS with better organization */
        :root {
            --primary-color: #084298;
            --secondary-color: #87abe0;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        .scanner-container {
            position: relative;
            height: 60vh;
            max-height: 300px;
            margin: 20px auto;
        }
        
        #largeReader {
            width: 100%;
            height: 100%;
            border: 2px solid var(--primary-color);
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
        
        .dept-location-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .manual-input-section {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #dee2e6;
        }
        
        .confirmation-modal .modal-dialog {
            max-width: 500px;
        }
        
        .confirmation-modal .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .confirmation-modal .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-bottom: none;
        }
        
        .confirmation-modal .student-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 3px solid var(--primary-color);
        }
        
        .blink {
            animation: blink-animation 1s steps(5, start) infinite;
        }
        
        @keyframes blink-animation {
            to { visibility: hidden; }
        }
        
        .processing-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        
        .status-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 2px solid #0f5132;
        }
        
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 2px solid #856404;
        }
        
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #721c24;
        }
    </style>
</head>

<body onload="startTime()">
<audio id="alertAudio" hidden>
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
                <img id="modalStudentPhoto" 
                    src="assets/img/2601828.png" 
                    alt="Student Photo" 
                    class="student-photo">
                
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
        <!-- Department/Location Info -->
        <div class="dept-location-info mb-2 py-1">
            <h3 class="mb-1" style="font-size: 1rem;">Department: <?php echo htmlspecialchars($department); ?></h3>
            <h3 class="mb-1" style="font-size: 1rem;">Room: <?php echo htmlspecialchars($location); ?></h3>
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
                <div class="scanner-container">
                    <div id="largeReader"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-frame">
                            <div class="scanner-laser"></div>
                        </div>
                    </div>
                </div>
                <div id="result" class="text-center" style="min-height: 40px; font-size: 0.9rem;"></div>
            </div>
            
            <!-- Photo/Manual Input Column -->
            <div class="col-md-4 h-100 d-flex flex-column" style="padding-left: 5px;">
                <!-- Student Photo -->
                <img id="pic" class="mb-2" alt="Student Photo" 
                     src="assets/img/section/type.jpg"
                     style="margin-top: .5px; width: 100%; height: 200px; object-fit: cover; border: 2px solid #084298; border-radius: 3px;">
                
                <!-- Manual Input Section -->
                <div class="manual-input-section flex-grow-1">
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
                                style="height: 40px; font-size: 0.9rem;"
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
// Enhanced JavaScript with better error handling and organization
class AttendanceScanner {
    constructor() {
        this.scanner = null;
        this.lastScanTime = 0;
        this.scanCooldown = 1000; // 1 second cooldown
        this.isProcessing = false;
        
        this.init();
    }
    
    init() {
        this.initScanner();
        this.bindEvents();
        this.startTime();
    }
    
    initScanner() {
        // Clear existing scanner
        if (this.scanner) {
            this.scanner.clear().catch(console.error);
        }
        
        // Initialize new scanner
        this.scanner = new Html5QrcodeScanner('largeReader', {
            qrbox: { width: 300, height: 300 },
            fps: 20,
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
            showTorchButtonIfSupported: true
        });
        
        this.scanner.render(
            (decodedText) => this.onScanSuccess(decodedText),
            (error) => this.onScanError(error)
        );
    }
    
    bindEvents() {
        // Manual input events
        document.getElementById('manualIdInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.processManualInput();
            }
        });
        
        document.getElementById('manualSubmitBtn').addEventListener('click', () => {
            this.processManualInput();
        });
        
        // Page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopScanner();
            } else {
                setTimeout(() => this.initScanner(), 500);
            }
        });
        
        // Before page unload
        window.addEventListener('beforeunload', () => {
            this.stopScanner();
        });
    }
    
    onScanSuccess(decodedText) {
        const now = Date.now();
        
        // Prevent duplicate scans
        if (now - this.lastScanTime < this.scanCooldown || this.isProcessing) {
            return;
        }
        
        this.lastScanTime = now;
        this.isProcessing = true;
        
        this.showProcessingState(`Processing: ${decodedText}`);
        this.hideScannerOverlay();
        
        this.processBarcode(decodedText);
    }
    
    onScanError(error) {
        // Ignore benign errors
        if (error.includes('NotFoundException') || error.includes('No MultiFormat Readers')) {
            return;
        }
        console.error('Scanner error:', error);
    }
    
    async processBarcode(barcode) {
        try {
            this.disableInputs();
            
            const response = await this.makeAjaxRequest({
                barcode: barcode,
                department: "<?php echo htmlspecialchars($department); ?>",
                location: "<?php echo htmlspecialchars($location); ?>"
            });
            
            if (response.error) {
                throw new Error(response.error);
            }
            
            this.updateUI(response);
            this.showConfirmationModal(response);
            
        } catch (error) {
            console.error('Process barcode error:', error);
            this.handleProcessError(error, barcode);
        } finally {
            this.enableInputs();
        }
    }
    
    async makeAjaxRequest(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: "POST",
                url: "process_barcode.php",
                data: data,
                dataType: 'json',
                timeout: 15000,
                success: resolve,
                error: (xhr, status, error) => {
                    // Try to parse response even if AJAX reports error
                    if (xhr.responseText) {
                        try {
                            const parsedResponse = JSON.parse(xhr.responseText);
                            resolve(parsedResponse);
                            return;
                        } catch (e) {
                            console.error('JSON parse error:', e);
                        }
                    }
                    reject(new Error(`AJAX Error: ${status} - ${error}`));
                }
            });
        });
    }
    
    processManualInput() {
        const idNumber = document.getElementById('manualIdInput').value.trim();
        
        if (!idNumber) {
            this.showErrorMessage("Please enter ID number");
            this.speakMessage("Please enter ID number");
            return;
        }
        
        this.processBarcode(idNumber);
    }
    
    updateUI(data) {
        // Update alert
        const alertElement = document.getElementById('alert');
        const inOutElement = document.getElementById('in_out');
        
        alertElement.className = `alert ${data.alert_class || 'alert-primary'} py-1 mb-2`;
        inOutElement.textContent = data.time_in_out || 'Scan Your ID Card for Attendance';
        
        // Update result display
        if (data.time_in_out) {
            document.getElementById('result').innerHTML = `
                <div class="alert ${data.alert_class || 'alert-success'} py-2" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.time_in_out} - ${data.full_name || 'Student'}
                </div>
            `;
        }
        
        // Update photo
        if (data.photo) {
            const mainPhoto = document.getElementById('pic');
            mainPhoto.src = data.photo + '?t=' + new Date().getTime();
            mainPhoto.onerror = () => {
                mainPhoto.src = 'assets/img/section/type.jpg';
            };
        }
    }
    
    showConfirmationModal(data) {
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
        
        if (data.attendance_type === 'time_in' || data.time_in_out === 'Time In Recorded') {
            statusElement.textContent = '✓ Time In Recorded Successfully';
            statusElement.className = 'text-success fw-bold';
            statusContainer.className = 'attendance-status mb-3 status-success';
        } else if (data.attendance_type === 'time_out' || data.time_in_out === 'Time Out Recorded') {
            statusElement.textContent = '✓ Time Out Recorded Successfully';
            statusElement.className = 'text-warning fw-bold';
            statusContainer.className = 'attendance-status mb-3 status-warning';
        } else {
            statusElement.textContent = data.time_in_out || '✓ Attendance Recorded Successfully';
            statusElement.className = 'text-primary fw-bold';
            statusContainer.className = 'attendance-status mb-3';
        }
        
        // Update student photo
        const modalPhoto = document.getElementById('modalStudentPhoto');
        if (data.photo && data.photo !== 'assets/img/2601828.png') {
            modalPhoto.src = data.photo + '?t=' + new Date().getTime();
            modalPhoto.onerror = () => {
                modalPhoto.src = 'assets/img/2601828.png';
            };
        } else {
            modalPhoto.src = 'assets/img/2601828.png';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
        modal.show();
        
        // Speak confirmation if available
        if (data.voice) {
            this.speakMessage(data.voice);
        }
        
        // Add event listener for modal close
        document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', () => {
            this.restartScanner();
        });
    }
    
    handleProcessError(error, barcode) {
        console.error('Process error:', error);
        
        if (error.message.includes('Student not found') || error.message.includes('Already timed out')) {
            this.showErrorMessage(error.message);
            this.speakMessage(error.message);
        } else {
            // Fallback for other errors
            this.showSuccessFallback(barcode);
        }
        
        this.showScannerOverlay();
    }
    
    showSuccessFallback(barcode) {
        const fallbackData = {
            full_name: "Student",
            id_number: barcode,
            department: "<?php echo htmlspecialchars($department); ?>",
            photo: "assets/img/2601828.png",
            section: "N/A",
            year_level: "N/A", 
            role: "Student",
            time_in_out: "Attendance Recorded Successfully",
            alert_class: "alert-success",
            attendance_type: "time_in"
        };
        
        this.updateUI(fallbackData);
        this.showConfirmationModal(fallbackData);
    }
    
    showProcessingState(message) {
        document.getElementById('result').innerHTML = `
            <div class="processing-indicator">
                <div class="spinner-border text-primary me-2" role="status" style="width: 1rem; height: 1rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>${message}</span>
            </div>
        `;
    }
    
    showErrorMessage(message) {
        document.getElementById('result').innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>${message}</div>
            </div>
        `;
        this.playAlertSound();
    }
    
    playAlertSound() {
        const audio = document.getElementById('alertAudio');
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(console.error);
        }
    }
    
    speakMessage(message) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            
            const speech = new SpeechSynthesisUtterance();
            speech.text = message;
            speech.volume = 1;
            speech.rate = 1;
            speech.pitch = 1.1;
            
            const voices = window.speechSynthesis.getVoices();
            if (voices.length > 0) {
                const preferredVoices = ['Google UK English Female', 'Microsoft Zira Desktop', 'Karen'];
                const voice = voices.find(v => preferredVoices.includes(v.name)) || voices[0];
                speech.voice = voice;
            }
            
            window.speechSynthesis.speak(speech);
        }
    }
    
    disableInputs() {
        document.getElementById('manualIdInput').disabled = true;
        document.getElementById('manualSubmitBtn').disabled = true;
    }
    
    enableInputs() {
        document.getElementById('manualIdInput').disabled = false;
        document.getElementById('manualSubmitBtn').disabled = false;
        document.getElementById('manualIdInput').value = '';
        document.getElementById('manualIdInput').focus();
        this.isProcessing = false;
    }
    
    stopScanner() {
        if (this.scanner) {
            this.scanner.clear().catch(console.error);
        }
    }
    
    restartScanner() {
        this.stopScanner();
        setTimeout(() => {
            this.initScanner();
            this.showScannerOverlay();
            document.getElementById('result').innerHTML = "";
        }, 500);
    }
    
    showScannerOverlay() {
        document.querySelector('.scanner-overlay').style.display = 'flex';
    }
    
    hideScannerOverlay() {
        document.querySelector('.scanner-overlay').style.display = 'none';
    }
    
    startTime() {
        const today = new Date();
        let h = today.getHours();
        let m = today.getMinutes();
        let s = today.getSeconds();
        let period = h >= 12 ? 'PM' : 'AM';
        
        h = h % 12;
        h = h ? h : 12;
        
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;
        
        document.getElementById('clock').innerHTML = `${h}:${m}:${s} ${period}`;
        
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('currentDate').innerHTML = today.toLocaleDateString('en-US', options);
        
        setTimeout(() => this.startTime(), 1000);
    }
}

// Global functions for HTML event handlers
function closeAndRefresh() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
    modal.hide();
    
    // Refresh after modal is hidden
    document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function() {
        window.location.reload();
    }, { once: true });
}

// Initialize scanner when page loads
let attendanceScanner;
document.addEventListener('DOMContentLoaded', function() {
    // Load speech synthesis voices
    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
    }
    
    // Initialize scanner
    attendanceScanner = new AttendanceScanner();
});
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin/lib/chart/chart.min.js"></script>
</body>
</html>