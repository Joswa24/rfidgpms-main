<?php 
session_start();
// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

include '../connection.php';

// --- CREATE TABLE COLUMNS IF NOT EXISTS (optional, for first-time setup) ---
// $db->query("ALTER TABLE room_schedules 
//     ADD COLUMN IF NOT EXISTS department VARCHAR(255) NOT NULL AFTER year_level,
//     ADD COLUMN IF NOT EXISTS instructor VARCHAR(255) NOT NULL AFTER department");

// --- FETCH FOR EDIT ---
$edit_mode = false;
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM room_schedules WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $edit_data = $edit_result->fetch_assoc();
}

// --- GET ALL SCHEDULES ---
$schedules = $db->query("SELECT * FROM room_schedules ORDER BY room_name, day, start_time");
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Room Schedules</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .bg-light {
            background-color: #f8f9fa !important;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .table th {
            background-color: #4e73df;
            color: white;
        }
        .badge {
            font-size: 0.85em;
        }
        .section-header {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .btn-del {
            transition: all 0.3s ease;
        }
        .btn-del:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }
        .swal2-popup {
            font-family: inherit;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .schedule-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
            background-color: #4e73df;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        .time-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Manage Room Schedules</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                    <i class="fas fa-plus-circle"></i> Add Schedule
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Icon</th>
                                        <th scope="col">Room</th>
                                        <th scope="col">Subject</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Year</th>
                                        <th scope="col">Day</th>
                                        <th scope="col">Instructor</th>
                                        <th scope="col">Time</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $schedules->fetch_assoc()): ?>
                                    <tr class="table-<?= $row['id'] ?>" data-schedule-id="<?= $row['id'] ?>">
                                        <input class="department" type="hidden" value="<?= htmlspecialchars($row['department']) ?>" />
                                        <input class="room_name" type="hidden" value="<?= htmlspecialchars($row['room_name']) ?>" />
                                        <input class="subject" type="hidden" value="<?= htmlspecialchars($row['subject']) ?>" />
                                        <input class="section" type="hidden" value="<?= htmlspecialchars($row['section']) ?>" />
                                        <input class="year_level" type="hidden" value="<?= htmlspecialchars($row['year_level']) ?>" />
                                        <input class="day" type="hidden" value="<?= htmlspecialchars($row['day']) ?>" />
                                        <input class="instructor" type="hidden" value="<?= htmlspecialchars($row['instructor']) ?>" />
                                        <input class="start_time" type="hidden" value="<?= htmlspecialchars($row['start_time']) ?>" />
                                        <input class="end_time" type="hidden" value="<?= htmlspecialchars($row['end_time']) ?>" />
                                        
                                        <td>
                                            <center>
                                                <div class="schedule-icon">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            </center>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['room_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($row['department']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['subject']) ?></td>
                                        <td><?= htmlspecialchars($row['section']) ?></td>
                                        <td><?= htmlspecialchars($row['year_level']) ?></td>
                                        <td><?= htmlspecialchars($row['day']) ?></td>
                                        <td><?= htmlspecialchars($row['instructor']) ?></td>
                                        <td>
                                            <span class="time-badge">
                                                <?= date("g:i A", strtotime($row['start_time'])) ?> - 
                                                <?= date("g:i A", strtotime($row['end_time'])) ?>
                                            </span>
                                        </td>
                                        <td width="14%">
                                            <center>
                                                <button data-id="<?= $row['id'] ?>" 
                                                        class="btn btn-outline-primary btn-sm btn-edit e_schedule_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button schedule_name="<?= htmlspecialchars($row['room_name'] . ' - ' . $row['subject']) ?>" 
                                                        data-id="<?= $row['id'] ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_schedule_id">
                                                    <i class="fas fa-trash"></i> Delete 
                                                </button>
                                            </center>
                                        </td>
                                        <td style="display:none;" class="hidden-date">
                                            <?= isset($row['date_added']) ? $row['date_added'] : date('Y-m-d H:i:s') ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Schedule Modal -->
            <div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Room Schedule
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="scheduleForm" role="form" method="post" action="">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-schedule"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-schedule"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SCHEDULE INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="schedule-icon mx-auto mb-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            </div>

                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select name="department" class="form-control" required>
                                                        <option value="">Select Department</option>
                                                        <?php
                                                        $departments = $db->query("SELECT * FROM department ORDER BY department_name");
                                                        while ($dept = $departments->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($dept['department_name']).'">'.htmlspecialchars($dept['department_name']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="department-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Room Name:</b></label>
                                                    <select name="room_name" class="form-control" required>
                                                        <option value="">Select Room</option>
                                                        <?php
                                                        $rooms = $db->query("SELECT * FROM rooms ORDER BY room");
                                                        while ($room = $rooms->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($room['room']).'" data-department="'.htmlspecialchars($room['department']).'">'.htmlspecialchars($room['room']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="room_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year Level:</b></label>
                                                    <select name="year_level" class="form-control" required>
                                                        <option value="">Select Year Level</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="year_level-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Subject:</b></label>
                                                    <select name="subject" id="subject" class="form-control" required>
                                                        <option value="">Select Subject</option>
                                                        <?php
                                                        $subjects = $db->query("SELECT * FROM subjects ORDER BY subject_name");
                                                        while ($subject = $subjects->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($subject['subject_name']).'" 
                                                                  data-year-level="'.htmlspecialchars($subject['year_level']).'">'
                                                                  .htmlspecialchars($subject['subject_code']).' - '.htmlspecialchars($subject['subject_name'])
                                                                  .'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="subject-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <input type="text" name="section" class="form-control" required>
                                                    <span class="error-message" id="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Day:</b></label>
                                                    <select name="day" class="form-control" required>
                                                        <option value="">Select Day</option>
                                                        <?php
                                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                        foreach ($days as $day) {
                                                            echo "<option value='$day'>$day</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="day-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-8 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Instructor:</b></label>
                                                    <select name="instructor" id="instructor" class="form-control" required>
                                                        <option value="">Select Instructor</option>
                                                        <?php
                                                        $instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
                                                        while ($instructor = $instructors->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($instructor['fullname']).'" 
                                                                  data-rfid="'.htmlspecialchars($instructor['rfid_number']).'">'
                                                                  .htmlspecialchars($instructor['fullname']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="instructor-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Start Time:</b></label>
                                                    <input type="time" name="start_time" class="form-control" required>
                                                    <span class="error-message" id="start_time-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>End Time:</b></label>
                                                    <input type="time" name="end_time" class="form-control" required>
                                                    <span class="error-message" id="end_time-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-schedule" class="btn btn-outline-warning">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Schedule Modal -->
            <div class="modal fade" id="editscheduleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Room Schedule
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editScheduleForm" class="edit-form" role="form" method="post" action="">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editschedule"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editschedule"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SCHEDULE INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="schedule-icon mx-auto mb-3">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>
                                            </div>
                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select name="department" id="edepartment" class="form-control" required>
                                                        <option value="">Select Department</option>
                                                        <?php
                                                        $departments = $db->query("SELECT * FROM department ORDER BY department_name");
                                                        while ($dept = $departments->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($dept['department_name']).'">'.htmlspecialchars($dept['department_name']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="edepartment-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Room Name:</b></label>
                                                    <select name="room_name" id="eroom_name" class="form-control" required>
                                                        <option value="">Select Room</option>
                                                        <?php
                                                        $rooms = $db->query("SELECT * FROM rooms ORDER BY room");
                                                        while ($room = $rooms->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($room['room']).'" data-department="'.htmlspecialchars($room['department']).'">'.htmlspecialchars($room['room']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="eroom_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year Level:</b></label>
                                                    <select name="year_level" id="eyear_level" class="form-control" required>
                                                        <option value="">Select Year Level</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="eyear_level-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Subject:</b></label>
                                                    <select name="subject" id="esubject" class="form-control" required>
                                                        <option value="">Select Subject</option>
                                                        <?php
                                                        $subjects = $db->query("SELECT * FROM subjects ORDER BY subject_name");
                                                        while ($subject = $subjects->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($subject['subject_name']).'" 
                                                                  data-year-level="'.htmlspecialchars($subject['year_level']).'">'
                                                                  .htmlspecialchars($subject['subject_code']).' - '.htmlspecialchars($subject['subject_name'])
                                                                  .'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="esubject-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <input type="text" name="section" id="esection" class="form-control" required>
                                                    <span class="error-message" id="esection-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Day:</b></label>
                                                    <select name="day" id="eday" class="form-control" required>
                                                        <option value="">Select Day</option>
                                                        <?php
                                                        foreach ($days as $day) {
                                                            echo "<option value='$day'>$day</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="eday-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-8 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Instructor:</b></label>
                                                    <select name="instructor" id="einstructor" class="form-control" required>
                                                        <option value="">Select Instructor</option>
                                                        <?php
                                                        $instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
                                                        while ($instructor = $instructors->fetch_assoc()) {
                                                            echo '<option value="'.htmlspecialchars($instructor['fullname']).'">'.htmlspecialchars($instructor['fullname']).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="einstructor-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Start Time:</b></label>
                                                    <input type="time" name="start_time" id="estart_time" class="form-control" required>
                                                    <span class="error-message" id="estart_time-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>End Time:</b></label>
                                                    <input type="time" name="end_time" id="eend_time" class="form-control" required>
                                                    <span class="error-message" id="eend_time-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_scheduleid" name="schedule_id" class="edit-id">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editschedule" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="delschedule-modal" tabindex="-1" aria-labelledby="delscheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="delscheduleModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete schedule: <strong id="delete_schedulename"></strong>?</p>
                            <input type="hidden" id="delete_scheduleid">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="btn-delschedule">Yes, Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#myDataTable').DataTable({
            order: [[9, 'desc']],
            stateSave: true
        });

        // Reset form function
        function resetForm() {
            $('.error-message').text('');
            $('#scheduleForm')[0].reset();
        }

        // ==============
        // CREATE (ADD SCHEDULE)
        // ==============
        $('#scheduleForm').submit(function(e) {
            e.preventDefault();
            
            $('.error-message').text('');
            const department = $('select[name="department"]').val();
            const room_name = $('select[name="room_name"]').val();
            const year_level = $('select[name="year_level"]').val();
            const subject = $('#subject').val();
            const section = $('input[name="section"]').val().trim();
            const day = $('select[name="day"]').val();
            const instructor = $('#instructor').val();
            const start_time = $('input[name="start_time"]').val();
            const end_time = $('input[name="end_time"]').val();
            
            let isValid = true;

            // Validation
            if (!department) { 
                $('#department-error').text('Department is required'); 
                isValid = false; 
            }
            if (!room_name) { 
                $('#room_name-error').text('Room name is required'); 
                isValid = false; 
            }
            if (!year_level) { 
                $('#year_level-error').text('Year level is required'); 
                isValid = false; 
            }
            if (!subject) { 
                $('#subject-error').text('Subject is required'); 
                isValid = false; 
            }
            if (!section) { 
                $('#section-error').text('Section is required'); 
                isValid = false; 
            }
            if (!day) { 
                $('#day-error').text('Day is required'); 
                isValid = false; 
            }
            if (!instructor) { 
                $('#instructor-error').text('Instructor is required'); 
                isValid = false; 
            }
            if (!start_time) { 
                $('#start_time-error').text('Start time is required'); 
                isValid = false; 
            }
            if (!end_time) { 
                $('#end_time-error').text('End time is required'); 
                isValid = false; 
            }
            if (start_time && end_time && start_time >= end_time) {
                $('#start_time-error, #end_time-error').text('End time must be after start time'); 
                isValid = false; 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-schedule').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-schedule').prop('disabled', true);

            var formData = new FormData(this);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_schedule",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-schedule').html('Save');
                    $('#btn-schedule').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#scheduleModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button state
                    $('#btn-schedule').html('Save');
                    $('#btn-schedule').prop('disabled', false);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred: ' + error
                    });
                }
            });
        });

        // ==========
        // READ (EDIT SCHEDULE)
        // ==========
        $(document).on('click', '.e_schedule_id', function() {
            const id = $(this).data('id');
            
            // Retrieve data from the selected row
            const $getdepartment = $('.table-' + id + ' .department').val();
            const $getroomname = $('.table-' + id + ' .room_name').val();
            const $getyearlevel = $('.table-' + id + ' .year_level').val();
            const $getsubject = $('.table-' + id + ' .subject').val();
            const $getsection = $('.table-' + id + ' .section').val();
            const $getday = $('.table-' + id + ' .day').val();
            const $getinstructor = $('.table-' + id + ' .instructor').val();
            const $getstarttime = $('.table-' + id + ' .start_time').val();
            const $getendtime = $('.table-' + id + ' .end_time').val();

            // Populate edit form
            $('#edit_scheduleid').val(id);
            $('#edepartment').val($getdepartment);
            $('#eroom_name').val($getroomname);
            $('#eyear_level').val($getyearlevel);
            $('#esubject').val($getsubject);
            $('#esection').val($getsection);
            $('#eday').val($getday);
            $('#einstructor').val($getinstructor);
            $('#estart_time').val($getstarttime);
            $('#eend_time').val($getendtime);
            
            // Show modal
            $('#editscheduleModal').modal('show');
        });

        // ==========
        // UPDATE SCHEDULE
        // ==========
        $('#editScheduleForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#edit_scheduleid').val();
            const department = $('#edepartment').val();
            const room_name = $('#eroom_name').val();
            const year_level = $('#eyear_level').val();
            const subject = $('#esubject').val();
            const section = $('#esection').val().trim();
            const day = $('#eday').val();
            const instructor = $('#einstructor').val();
            const start_time = $('#estart_time').val();
            const end_time = $('#eend_time').val();

            // Validation
            let isValid = true;
            if (!department) { 
                $('#edepartment-error').text('Department is required'); 
                isValid = false; 
            } else { 
                $('#edepartment-error').text(''); 
            }
            if (!room_name) { 
                $('#eroom_name-error').text('Room name is required'); 
                isValid = false; 
            } else { 
                $('#eroom_name-error').text(''); 
            }
            if (!year_level) { 
                $('#eyear_level-error').text('Year level is required'); 
                isValid = false; 
            } else { 
                $('#eyear_level-error').text(''); 
            }
            if (!subject) { 
                $('#esubject-error').text('Subject is required'); 
                isValid = false; 
            } else { 
                $('#esubject-error').text(''); 
            }
            if (!section) { 
                $('#esection-error').text('Section is required'); 
                isValid = false; 
            } else { 
                $('#esection-error').text(''); 
            }
            if (!day) { 
                $('#eday-error').text('Day is required'); 
                isValid = false; 
            } else { 
                $('#eday-error').text(''); 
            }
            if (!instructor) { 
                $('#einstructor-error').text('Instructor is required'); 
                isValid = false; 
            } else { 
                $('#einstructor-error').text(''); 
            }
            if (!start_time) { 
                $('#estart_time-error').text('Start time is required'); 
                isValid = false; 
            } else { 
                $('#estart_time-error').text(''); 
            }
            if (!end_time) { 
                $('#eend_time-error').text('End time is required'); 
                isValid = false; 
            } else { 
                $('#eend_time-error').text(''); 
            }
            if (start_time && end_time && start_time >= end_time) {
                $('#estart_time-error, #eend_time-error').text('End time must be after start time'); 
                isValid = false; 
            } else {
                $('#estart_time-error, #eend_time-error').text(''); 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-editschedule').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editschedule').prop('disabled', true);

            var formData = new FormData(this);
            formData.append('id', id);

            $.ajax({
                type: "POST",
                url: "edit1.php?edit=schedule&id=" + id,
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editschedule').html('Update');
                    $('#btn-editschedule').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    $('#editScheduleModal').modal('hide');
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Update failed'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while processing your request: ' + 
                      (xhr.responseJSON?.message || xhr.statusText)
            });
        },
        complete: function() {
            $btn.html('Update');
            $btn.prop('disabled', false);
        }
    });
});
    // ==========
    // DELETE
    // ==========
    $(document).on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var $btn = $(this);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                $btn.html('<span class="spinner-border spinner-border-sm"></span>');
                $btn.prop('disabled', true);
                
                $.ajax({
                    type: 'POST',
                    url: 'del.php',
                    data: {
                        type: 'schedule',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                            $btn.html('Delete');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'An error occurred while processing your request', 'error');
                        $btn.html('Delete');
                        $btn.prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>