<?php
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

<?php include 'header.php'; ?>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded h-100 p-4">
                <div class="row mb-4">
                    <div class="col-9">
                        <h6 class="mb-4">Manage Room Schedules</h6>
                    </div>
                    <div class="col-3 text-end">
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            Add Room Schedule
                        </button>
                    </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered" id="myDataTable">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Room</th>
                                <th>Subject</th>
                                <th>Section</th>
                                <th>Year</th>
                                <th>Day</th>
                                <th>Instructor</th>
                                <th>Time Start</th>
                                <th>Time End</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $schedules->fetch_assoc()): ?>
                            <tr class="table-<?= $row['id'] ?>">
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td><?= htmlspecialchars($row['subject']) ?></td>
                                <td><?= htmlspecialchars($row['section']) ?></td>
                                <td><?= htmlspecialchars($row['year_level']) ?></td>
                                <td><?= htmlspecialchars($row['day']) ?></td>
                                <td><?= htmlspecialchars($row['instructor']) ?></td>
                                <td><?= date("g:i A", strtotime($row['start_time'])) ?></td>
                                <td><?= date("g:i A", strtotime($row['end_time'])) ?></td>
                                <td>
                                    <button data-id="<?= $row['id'] ?>" 
                                            class="btn btn-sm btn-warning btn-edit">
                                        Edit
                                    </button>
                                    <button data-id="<?= $row['id'] ?>" 
                                            class="btn btn-sm btn-danger btn-delete">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Schedule Modal -->
        <div class="modal fade" id="scheduleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Room Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="scheduleForm">
                        <div class="modal-body">
                            <div id="mgs-schedule"></div>
                           <div class="mb-3">
    <label class="form-label">Department</label>
    <select name="department" class="form-control" required>
        <option value="">Select Department</option>
        <?php
        $departments = $db->query("SELECT * FROM department ORDER BY department_name");
        while ($dept = $departments->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($dept['department_name']).'">'.htmlspecialchars($dept['department_name']).'</option>';
        }
        ?>
    </select>
</div>
                            
<div class="mb-3">
    <label class="form-label">Room Name</label>
    <select name="room_name" class="form-control" required>
        <option value="">Select Room</option>
        <?php
        $rooms = $db->query("SELECT * FROM rooms ORDER BY room");
        while ($room = $rooms->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($room['room']).'" data-department="'.htmlspecialchars($room['department']).'">'.htmlspecialchars($room['room']).'</option>';
        }
        ?>
    </select>
</div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <input type="text" name="section" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year Level</label>
                                <input type="text" name="year_level" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Day</label>
                                <select name="day" class="form-control" required>
                                    <option value="">Select Day</option>
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                    foreach ($days as $day) {
                                        echo "<option value='$day'>$day</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                           <div class="mb-3">
    <label class="form-label">Instructor</label>
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
</div>
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="btn-schedule" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Schedule Modal -->
        <div class="modal fade" id="editScheduleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Room Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editScheduleForm">
                        <div class="modal-body">
                            <div id="mgs-editschedule"></div>
                            <input type="hidden" name="edit" value="schedule">
                            <input type="hidden" name="id" id="edit_scheduleid">
                            <div class="mb-3">
    <label class="form-label">Department</label>
    <select name="department" id="edit_department" class="form-control" required>
        <option value="">Select Department</option>
        <?php
        $departments = $db->query("SELECT * FROM department ORDER BY department_name");
        while ($dept = $departments->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($dept['department_name']).'">'.htmlspecialchars($dept['department_name']).'</option>';
        }
        ?>
    </select>
</div>
                          
<div class="mb-3">
    <label class="form-label">Room Name</label>
    <select name="room_name" class="form-control" required>
        <option value="">Select Room</option>
        <?php
        $rooms = $db->query("SELECT * FROM rooms ORDER BY room");
        while ($room = $rooms->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($room['room']).'" data-department="'.htmlspecialchars($room['department']).'">'.htmlspecialchars($room['room']).'</option>';
        }
        ?>
    </select>
</div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" id="edit_subject" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Section</label>
                                <input type="text" name="section" id="edit_section" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Year Level</label>
                                <input type="text" name="year_level" id="edit_year_level" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Day</label>
                                <select name="day" id="edit_day" class="form-control" required>
                                    <option value="">Select Day</option>
                                    <?php
                                    foreach ($days as $day) {
                                        echo "<option value='$day'>$day</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                           <div class="mb-3">
    <label class="form-label">Instructor</label>
    <select name="instructor" id="edit_instructor" class="form-control" required>
        <option value="">Select Instructor</option>
        <?php
        $instructors = $db->query("SELECT * FROM instructor ORDER BY fullname");
        while ($instructor = $instructors->fetch_assoc()) {
            echo '<option value="'.htmlspecialchars($instructor['fullname']).'">'.htmlspecialchars($instructor['fullname']).'</option>';
        }
        ?>
    </select>
</div>
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="btn-editschedule" class="btn btn-warning">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'footer.php'; ?>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    
$(document).ready(function() {

    // Initialize DataTable
    $('#myDataTable').DataTable({ order: [[1, 'asc']] });
    // Add this script after your DataTable initialization
    
$(document).ready(function() {
    // Function to filter rooms based on selected department
    function filterRooms(selectedDepartment) {
        $('select[name="room_name"] option').each(function() {
            var $option = $(this);
            // Always show the "Select Room" option
            if ($option.val() === '') {
                $option.show();
                return;
            }
            // Show/hide based on department match
            if ($option.data('department') === selectedDepartment) {
                $option.show();
            } else {
                $option.hide();
                // If this option was selected but doesn't match, clear the selection
                if ($option.prop('selected')) {
                    $('select[name="room_name"]').val('');
                }
            }
        });
    }

    // Apply filtering when department changes in Add modal
    $('select[name="department"]').on('change', function() {
        filterRooms($(this).val());
    });

    // Apply filtering when department changes in Edit modal
    $(document).on('change', '#edit_department', function() {
        filterRooms($(this).val());
    });

    // Initialize rooms filter when edit modal opens
    $(document).on('click', '.btn-edit', function() {
        // Wait for modal to be shown and data loaded
        setTimeout(function() {
            var selectedDept = $('#edit_department').val();
            filterRooms(selectedDept);
        }, 100);
    });
});
// Function to filter rooms based on selected department
function filterRooms(selectedDepartment, $selectElement) {
    $selectElement.find('option').each(function() {
        var $option = $(this);
        // Always show the "Select Room" option
        if ($option.val() === '') {
            $option.prop('disabled', false);
            return;
        }
        // Show/hide based on department match
        if ($option.data('department') === selectedDepartment) {
            $option.prop('disabled', false);
        } else {
            $option.prop('disabled', true);
            // If this option was selected but doesn't match, clear the selection
            if ($option.prop('selected')) {
                $selectElement.val('').trigger('change');
            }
        }
    });
    
    // Refresh Select2 if it's being used
    if ($selectElement.hasClass('select2-hidden-accessible')) {
        $selectElement.trigger('change.select2');
    }
}

// Apply filtering when department changes in Add modal
$('select[name="department"]').on('change', function() {
    filterRooms($(this).val(), $('select[name="room_name"]'));
});

// Apply filtering when department changes in Edit modal
$(document).on('change', '#edit_department', function() {
    filterRooms($(this).val(), $('#edit_room_name'));
});
// Add this to your validation before saving/updating
function checkTimeConflict(room, day, start, end, excludeId = null) {
    // AJAX call to check if the time slot is available
    // Should return true if conflict exists
}
    // ==============
    // CREATE (ADD SCHEDULE)
    // ==============
    $('#btn-schedule').click(function() {
        var $btn = $(this);
        var formData = $('#scheduleForm').serialize();
        
        // Validate form
        var isValid = true;
        $('#scheduleForm input, #scheduleForm select').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            $('#mgs-schedule').html('<div class="alert alert-danger">Please fill all required fields</div>');
            return;
        }
        
        // Show loading state
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        $btn.prop('disabled', true);
        
        $.ajax({
            type: "POST",
            url: "transac.php?action=add_schedule",
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#scheduleForm')[0].reset();
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
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while processing your request'
                });
            },
            complete: function() {
                $btn.html('Save');
                $btn.prop('disabled', false);
            }
        });
    });
// ==========
// READ (EDIT) - GET request to fetch schedule data
// ==========
$(document).on('click', '.btn-edit', function() {
    var id = $(this).data('id');
    var $btn = $(this);
    
    // Show loading state
    $btn.html('<span class="spinner-border spinner-border-sm"></span>');
    $btn.prop('disabled', true);
    
    // First fetch the schedule data
    $.ajax({
        type: "GET",
        url: "edit1.php",
        data: {
            edit: 'schedule',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Populate form fields
                $('#edit_scheduleid').val(response.data.id);
                $('#edit_department').val(response.data.department);
                $('#edit_room_name').val(response.data.room_name);
                $('#edit_subject').val(response.data.subject);
                $('#edit_section').val(response.data.section);
                $('#edit_year_level').val(response.data.year_level);
                $('#edit_day').val(response.data.day);
                $('#edit_instructor').val(response.data.instructor);
                $('#edit_start_time').val(response.data.start_time);
                $('#edit_end_time').val(response.data.end_time);
                
                // Filter rooms based on selected department
                filterRooms(response.data.department, $('#edit_room_name'));
                
                // Show modal
                $('#editScheduleModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Failed to load schedule data'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while loading the schedule: ' + 
                      (xhr.responseJSON?.message || xhr.statusText)
            });
        },
        complete: function() {
            $btn.html('Edit');
            $btn.prop('disabled', false);
        }
    });
});

// ==========
// UPDATE - POST request to update schedule
// ==========
$('#btn-editschedule').click(function() {
    var $btn = $(this);
    var formData = $('#editScheduleForm').serialize() + '&edit=schedule';
    
    // Validate form
    var isValid = true;
    $('#editScheduleForm input, #editScheduleForm select').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    if (!isValid) {
        $('#mgs-editschedule').html('<div class="alert alert-danger">Please fill all required fields</div>');
        return;
    }
    
    // Validate time
    var startTime = $('#edit_start_time').val();
    var endTime = $('#edit_end_time').val();
    if (startTime >= endTime) {
        $('#edit_start_time, #edit_end_time').addClass('is-invalid');
        $('#mgs-editschedule').html('<div class="alert alert-danger">End time must be after start time</div>');
        return;
    }
    
    // Show loading state
    $btn.html('<span class="spinner-border spinner-border-sm"></span> Updating...');
    $btn.prop('disabled', true);
    
    $.ajax({
        type: "POST",
        url: "edit1.php",
        data: formData,
        dataType: 'json',
        success: function(response) {
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