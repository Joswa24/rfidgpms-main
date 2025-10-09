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

// --- GET FILTER OPTIONS ---
$departments = $db->query("SELECT DISTINCT department FROM room_schedules ORDER BY department");
$rooms = $db->query("SELECT DISTINCT room_name FROM room_schedules ORDER BY room_name");
$subjects = $db->query("SELECT DISTINCT subject FROM room_schedules ORDER BY subject");
$year_levels = $db->query("SELECT DISTINCT year_level FROM room_schedules ORDER BY year_level");
$days = $db->query("SELECT DISTINCT day FROM room_schedules ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
$instructors = $db->query("SELECT DISTINCT instructor FROM room_schedules ORDER BY instructor");

// --- GET ALL OPTIONS FOR ADD MODAL ---
$all_departments = $db->query("SELECT * FROM department WHERE department_name != 'Main' ORDER BY department_name");
$all_rooms = $db->query("SELECT * FROM rooms ORDER BY room");
$all_subjects = $db->query("SELECT * FROM subjects ORDER BY subject_name");
$all_instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
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
        .filter-section {
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .filter-btn {
            margin-top: 32px;
        }
        .filter-badge {
            background-color: #4e73df;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .option-disabled {
            color: #6c757d;
            font-style: italic;
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
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                    <label><b>Department:</b></label>
                                        <select class="form-control filter-select" id="filter_department" name="filter_department">
                                            <option value="">All Departments</option>
                                            <?php 
                                            // Reset the pointer to the beginning of the result set
                                            $all_departments->data_seek(0);
                                            while ($dept = $all_departments->fetch_assoc()): 
                                                // Skip the "Main" department
                                                if ($dept['department_name'] !== 'Main'): 
                                            ?>
                                                <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                                    <?= htmlspecialchars($dept['department_name']) ?>
                                                </option>
                                            <?php 
                                                endif;
                                            endwhile; 
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Room:</b></label>
                                        <select class="form-control filter-select" id="filter_room" name="filter_room">
                                            <option value="">All Rooms</option>
                                            <?php while ($room = $rooms->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($room['room_name']) ?>">
                                                    <?= htmlspecialchars($room['room_name']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Subject:</b></label>
                                        <select class="form-control filter-select" id="filter_subject" name="filter_subject">
                                            <option value="">All Subjects</option>
                                            <?php while ($subject = $subjects->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($subject['subject']) ?>">
                                                    <?= htmlspecialchars($subject['subject']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Year Level:</b></label>
                                        <select class="form-control filter-select" id="filter_year_level" name="filter_year_level">
                                            <option value="">All Year Levels</option>
                                            <?php while ($year = $year_levels->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($year['year_level']) ?>">
                                                    <?= htmlspecialchars($year['year_level']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Day:</b></label>
                                        <select class="form-control filter-select" id="filter_day" name="filter_day">
                                            <option value="">All Days</option>
                                            <?php while ($day = $days->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($day['day']) ?>">
                                                    <?= htmlspecialchars($day['day']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Instructor:</b></label>
                                        <select class="form-control filter-select" id="filter_instructor" name="filter_instructor">
                                            <option value="">All Instructors</option>
                                            <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($instructor['instructor']) ?>">
                                                    <?= htmlspecialchars($instructor['instructor']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><b>Time Range:</b></label>
                                        <select class="form-control filter-select" id="filter_time" name="filter_time">
                                            <option value="">All Times</option>
                                            <option value="morning">Morning (6AM-12PM)</option>
                                            <option value="afternoon">Afternoon (12PM-6PM)</option>
                                            <option value="evening">Evening (6PM-10PM)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group filter-btn">
                                        <button type="button" class="btn btn-outline-secondary w-100" id="resetFilters">
                                            <i class="fas fa-refresh"></i> Reset Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div id="activeFilters" class="d-flex flex-wrap gap-2">
                                        <!-- Active filters will appear here -->
                                    </div>
                                </div>
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
                                                    <select name="department" id="add_department" class="form-control" required>
                                                        <option value="">Select Department</option>
                                                        <?php 
                                                        $all_departments->data_seek(0);
                                                        while ($dept = $all_departments->fetch_assoc()):
                                                        if ($dept['department_name'] !== 'Main'): 
                                                        endif;?>
                                                            <option value="<?= htmlspecialchars($dept['department_name']) ?>">
                                                                <?= htmlspecialchars($dept['department_name']) ?>
                                                            </option>
                                                        <?php
                                                    endwhile; ?>
                                                    </select>
                                                    <span class="error-message" id="department-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Room Name:</b></label>
                                                    <select name="room_name" id="add_room_name" class="form-control" required>
                                                        <option value="">Select Room</option>
                                                        <!-- Rooms will be populated dynamically based on department -->
                                                    </select>
                                                    <span class="error-message" id="room_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year Level:</b></label>
                                                    <select name="year_level" id="add_year_level" class="form-control" required>
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
                                                    <select name="subject" id="add_subject" class="form-control" required>
                                                        <option value="">Select Subject</option>
                                                        <!-- Subjects will be populated dynamically based on year level -->
                                                    </select>
                                                    <span class="error-message" id="subject-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <input type="text" name="section" id="add_section" class="form-control" required placeholder="Input Section..">
                                                    <span class="error-message" id="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Day:</b></label>
                                                    <select name="day" id="add_day" class="form-control" required>
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
                                                    <select name="instructor" id="add_instructor" class="form-control" required>
                                                        <option value="">Select Instructor</option>
                                                        <!-- Instructors will be populated from database -->
                                                        <?php 
                                                        $instructors_modal = $db->query("SELECT * FROM instructor ORDER BY fullname");
                                                        while ($instructor = $instructors_modal->fetch_assoc()): ?>
                                                            <option value="<?= htmlspecialchars($instructor['fullname']) ?>" 
                                                                    data-rfid="<?= htmlspecialchars($instructor['rfid_number']) ?>">
                                                                <?= htmlspecialchars($instructor['fullname']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <span class="error-message" id="instructor-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Start Time:</b></label>
                                                    <input type="time" name="start_time" id="add_start_time" class="form-control" required>
                                                    <span class="error-message" id="start_time-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>End Time:</b></label>
                                                    <input type="time" name="end_time" id="add_end_time" class="form-control" required>
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
         <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top" style="background-color: #87abe0ff"><i class="bi bi-arrow-up" style="background-color: #87abe0ff"></i></a>
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
                stateSave: true,
                language: {
                    search: "Search all columns:"
                }
            });

            // Store room and subject data for filtering
            let roomData = <?php
                $rooms_data = $db->query("SELECT * FROM rooms ORDER BY room");
                $rooms_array = [];
                while ($room = $rooms_data->fetch_assoc()) {
                    $rooms_array[] = $room;
                }
                echo json_encode($rooms_array);
            ?>;

            let subjectData = <?php
                $subjects_data = $db->query("SELECT * FROM subjects ORDER BY subject_name");
                $subjects_array = [];
                while ($subject = $subjects_data->fetch_assoc()) {
                    $subjects_array[] = $subject;
                }
                echo json_encode($subjects_array);
            ?>;

            // Active filters object
            let activeFilters = {};

            // Function to update active filters display
            function updateActiveFilters() {
                $('#activeFilters').empty();
                let filterCount = 0;
                
                for (const [key, value] of Object.entries(activeFilters)) {
                    if (value) {
                        filterCount++;
                        let filterName = key.replace('filter_', '').replace('_', ' ');
                        filterName = filterName.charAt(0).toUpperCase() + filterName.slice(1);
                        
                        $('#activeFilters').append(`
                            <span class="filter-badge">
                                ${filterName}: ${value}
                                <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.7rem;" data-filter="${key}"></button>
                            </span>
                        `);
                    }
                }
                
                // Update table info with filter count
                if (filterCount > 0) {
                    $('.dataTables_info').append(` <span class="text-primary">(${filterCount} active filter(s))</span>`);
                }
            }

            // Function to apply all filters
            function applyFilters() {
                dataTable.columns().search('').draw();
                
                // Apply department filter
                if (activeFilters.filter_department) {
                    dataTable.column(1).search(activeFilters.filter_department, true, false);
                }
                
                // Apply room filter
                if (activeFilters.filter_room) {
                    dataTable.column(1).search(activeFilters.filter_room, true, false);
                }
                
                // Apply subject filter
                if (activeFilters.filter_subject) {
                    dataTable.column(2).search(activeFilters.filter_subject, true, false);
                }
                
                // Apply year level filter
                if (activeFilters.filter_year_level) {
                    dataTable.column(4).search(activeFilters.filter_year_level, true, false);
                }
                
                // Apply day filter
                if (activeFilters.filter_day) {
                    dataTable.column(5).search(activeFilters.filter_day, true, false);
                }
                
                // Apply instructor filter
                if (activeFilters.filter_instructor) {
                    dataTable.column(6).search(activeFilters.filter_instructor, true, false);
                }
                
                // Apply time range filter
                if (activeFilters.filter_time) {
                    dataTable.column(7).every(function() {
                        var column = this;
                        var searchTerm = activeFilters.filter_time;
                        
                        column.nodes().each(function(node) {
                            var cellText = $(node).text().toLowerCase();
                            var showRow = false;
                            
                            switch(searchTerm) {
                                case 'morning':
                                    showRow = cellText.includes('am') && !cellText.includes('pm');
                                    break;
                                case 'afternoon':
                                    showRow = cellText.includes('pm') && (
                                        cellText.includes('12:') || cellText.includes('1:') || 
                                        cellText.includes('2:') || cellText.includes('3:') || 
                                        cellText.includes('4:') || cellText.includes('5:')
                                    );
                                    break;
                                case 'evening':
                                    showRow = cellText.includes('pm') && (
                                        cellText.includes('6:') || cellText.includes('7:') || 
                                        cellText.includes('8:') || cellText.includes('9:') || 
                                        cellText.includes('10:')
                                    );
                                    break;
                            }
                            
                            if (!showRow) {
                                $(node).parent().hide();
                            } else {
                                $(node).parent().show();
                            }
                        });
                    });
                }
                
                dataTable.draw();
                updateActiveFilters();
            }

            // Filter change handler
            $('.filter-select').change(function() {
                const filterName = $(this).attr('id');
                const filterValue = $(this).val();
                
                activeFilters[filterName] = filterValue;
                applyFilters();
            });

            // Remove individual filter
            $(document).on('click', '.filter-badge .btn-close', function() {
                const filterName = $(this).data('filter');
                activeFilters[filterName] = '';
                $('#' + filterName).val('');
                applyFilters();
            });

            // Reset all filters
            $('#resetFilters').click(function() {
                $('.filter-select').val('');
                activeFilters = {};
                applyFilters();
                $('#activeFilters').empty();
            });

            // =============================================
            // ADD MODAL FILTERING FUNCTIONALITY
            // =============================================

            // Function to filter rooms based on selected department
            function filterRoomsByDepartment(department) {
                const roomSelect = $('#add_room_name');
                roomSelect.empty().append('<option value="">Select Room</option>');
                
                if (department) {
                    const filteredRooms = roomData.filter(room => room.department === department);
                    
                    if (filteredRooms.length === 0) {
                        roomSelect.append('<option value="" disabled class="option-disabled">No rooms available for this department</option>');
                    } else {
                        filteredRooms.forEach(room => {
                            roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                        });
                    }
                } else {
                    // Show all rooms if no department selected
                    roomData.forEach(room => {
                        roomSelect.append(`<option value="${room.room}">${room.room}</option>`);
                    });
                }
            }

            // Function to filter subjects based on selected year level
            function filterSubjectsByYearLevel(yearLevel) {
                const subjectSelect = $('#add_subject');
                subjectSelect.empty().append('<option value="">Select Subject</option>');
                
                if (yearLevel) {
                    const filteredSubjects = subjectData.filter(subject => subject.year_level === yearLevel);
                    
                    if (filteredSubjects.length === 0) {
                        subjectSelect.append('<option value="" disabled class="option-disabled">No subjects available for this year level</option>');
                    } else {
                        filteredSubjects.forEach(subject => {
                            subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                        });
                    }
                } else {
                    // Show all subjects if no year level selected
                    subjectData.forEach(subject => {
                        subjectSelect.append(`<option value="${subject.subject_name}">${subject.subject_code} - ${subject.subject_name}</option>`);
                    });
                }
            }

            // Department change handler for ADD modal
            $('#add_department').change(function() {
                const selectedDepartment = $(this).val();
                filterRoomsByDepartment(selectedDepartment);
                
                // Auto-select room if only one option available
                const roomSelect = $('#add_room_name');
                if (roomSelect.find('option').length === 2 && !roomSelect.find('option:first').hasClass('option-disabled')) {
                    roomSelect.val(roomSelect.find('option:last').val());
                }
            });

            // Year level change handler for ADD modal
            $('#add_year_level').change(function() {
                const selectedYearLevel = $(this).val();
                filterSubjectsByYearLevel(selectedYearLevel);
                
                // Auto-select subject if only one option available
                const subjectSelect = $('#add_subject');
                if (subjectSelect.find('option').length === 2 && !subjectSelect.find('option:first').hasClass('option-disabled')) {
                    subjectSelect.val(subjectSelect.find('option:last').val());
                }
            });

            // Room selection handler to auto-fill department in ADD modal
            $('#add_room_name').change(function() {
                const selectedRoom = $(this).val();
                if (selectedRoom) {
                    const room = roomData.find(r => r.room === selectedRoom);
                    if (room) {
                        $('#add_department').val(room.department);
                    }
                }
            });

            // Subject selection handler to auto-fill year level in ADD modal
            $('#add_subject').change(function() {
                const selectedSubject = $(this).val();
                if (selectedSubject) {
                    const subject = subjectData.find(s => s.subject_name === selectedSubject);
                    if (subject) {
                        $('#add_year_level').val(subject.year_level);
                    }
                }
            });

            // Initialize ADD modal dropdowns when modal is shown
            $('#scheduleModal').on('show.bs.modal', function () {
                // Populate rooms with all options initially
                filterRoomsByDepartment('');
                // Populate subjects with all options initially
                filterSubjectsByYearLevel('');
            });

            // Reset ADD modal when closed
            $('#scheduleModal').on('hidden.bs.modal', function () {
                $(this).find('form')[0].reset();
                $('.error-message').text('');
                // Reset dropdowns to show all options
                filterRoomsByDepartment('');
                filterSubjectsByYearLevel('');
            });

            // ==========
            // READ (EDIT SCHEDULE)
            // ==========
            $(document).on('click', '.e_schedule_id', function() {
                const id = $(this).data('id');
                
                // Retrieve data from the selected row
                const $row = $(this).closest('tr');
                const $getdepartment = $row.find('.department').val();
                const $getroomname = $row.find('.room_name').val();
                const $getyearlevel = $row.find('.year_level').val();
                const $getsubject = $row.find('.subject').val();
                const $getsection = $row.find('.section').val();
                const $getday = $row.find('.day').val();
                const $getinstructor = $row.find('.instructor').val();
                const $getstarttime = $row.find('.start_time').val();
                const $getendtime = $row.find('.end_time').val();

                console.log('Editing schedule:', id, $getroomname, $getsubject);

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
                
                // Clear any previous error messages
                $('.error-message').text('');
                
                // Show modal
                $('#editscheduleModal').modal('show');
            });

            // ==============
            // CREATE (ADD SCHEDULE)
            // ==============
            $('#scheduleForm').submit(function(e) {
                e.preventDefault();
                
                $('.error-message').text('');
                const department = $('#add_department').val();
                const room_name = $('#add_room_name').val();
                const year_level = $('#add_year_level').val();
                const subject = $('#add_subject').val();
                const section = $('#add_section').val().trim();
                const day = $('#add_day').val();
                const instructor = $('#add_instructor').val();
                const start_time = $('#add_start_time').val();
                const end_time = $('#add_end_time').val();
                
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

                var formData = {
                    department: department,
                    room_name: room_name,
                    year_level: year_level,
                    subject: subject,
                    section: section,
                    day: day,
                    instructor: instructor,
                    start_time: start_time,
                    end_time: end_time
                };

                console.log('Sending data for ADD:', formData);

                $.ajax({
                    type: "POST",
                    url: "transac.php?action=add_schedule",
                    data: formData,
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
                        
                        console.error('AJAX Error Details:');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        let errorMessage = 'An error occurred';
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMessage = errorResponse.message || errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText || error;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    }
                });
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

                var formData = {
                    id: id,
                    department: department,
                    room_name: room_name,
                    year_level: year_level,
                    subject: subject,
                    section: section,
                    day: day,
                    instructor: instructor,
                    start_time: start_time,
                    end_time: end_time
                };

                console.log('Sending data for UPDATE:', formData);

                $.ajax({
                    type: "POST",
                    url: "transac.php?action=update_schedule",
                    data: formData,
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
                                showConfirmButton: true
                            }).then(() => {
                                $('#editscheduleModal').modal('hide');
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset button state
                        $('#btn-editschedule').html('Update');
                        $('#btn-editschedule').prop('disabled', false);
                        
                        console.error('AJAX Update Error Details:');
                        console.error('Status:', status);
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            Swal.fire({
                                title: 'Error!',
                                text: errorResponse.message || 'An error occurred',
                                icon: 'error'
                            });
                        } catch (e) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred: ' + (xhr.responseText || error),
                                icon: 'error'
                            });
                        }
                    }
                });
            });

            // Handle delete button click
            $(document).on('click', '.d_schedule_id', function() {
                var scheduleId = $(this).data('id');
                var scheduleName = $(this).attr('schedule_name');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to delete schedule: " + scheduleName,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: 'transac.php?action=delete_schedule',
                            type: 'POST',
                            data: { 
                                id: scheduleId 
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message,
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred: ' + error,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            });

            // Reset edit modal when closed
            $('#editscheduleModal').on('hidden.bs.modal', function () {
                $('.error-message').text('');
            });
        });
    </script>
</body>
</html>