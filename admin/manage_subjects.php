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
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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
        .subject-icon {
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
                                <h6 class="mb-4">Manage Subjects</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#subjectModal">
                                    <i class="fas fa-plus-circle"></i> Add Subject
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Icon</th>
                                        <th scope="col">Subject Code</th>
                                        <th scope="col">Subject Name</th>
                                        <th scope="col">Year Level</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $results = mysqli_query($db, "SELECT * FROM subjects ORDER BY id DESC"); 
                                    
                                    if ($results === false) {
                                        die("Query failed: " . mysqli_error($db));
                                    }
                                    ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-subject-id="<?php echo $row['id'];?>">
                                        <input class="subject_code" type="hidden" value="fas fa-book<?php echo $row['subject_code']; ?>" />
                                        <input class="subject_name" type="hidden" value="<?php echo $row['subject_name']; ?>" />
                                        <input class="year_level" type="hidden" value="<?php echo $row['year_level']; ?>" />
                                        <?php if (isset($row['date_added'])): ?>
                                        <input class="date_added" type="hidden" value="<?php echo $row['date_added']; ?>" />
                                        <?php endif; ?>

                                        <td>
                                            <center>
                                                <div class="subject-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </center>
                                        </td>
                                        <td class="subject_code_display"><?php echo $row['subject_code']; ?></td>
                                        <td><?php echo $row['subject_name']; ?></td>
                                        <td><?php echo $row['year_level']; ?></td>
                                        <td width="14%">
                                            <center>
                                                <button data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-outline-primary btn-sm btn-edit e_subject_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button subject_name="<?php echo $row['subject_name']; ?>" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_subject_id">
                                                    <i class="fas fa-trash"></i> Delete 
                                                </button>
                                            </center>
                                        </td>
                                        <?php if (isset($row['date_added'])): ?>
                                        <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                        <?php else: ?>
                                        <td style="display:none;" class="hidden-date"><?php echo date('Y-m-d H:i:s'); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Subject Modal -->
            <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Subject
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="subjectForm" role="form" method="post" action="">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-subject"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-subject"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SUBJECT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="subject-icon mx-auto mb-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </div>

                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Subject Code:</b></label>
                                                    <input required type="text" class="form-control" name="subject_code" id="subject_code" autocomplete="off">
                                                    <span class="error-message" id="subject_code-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Subject Name:</b></label>
                                                    <input required type="text" class="form-control" name="subject_name" id="subject_name" autocomplete="off">
                                                    <span class="error-message" id="subject_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year Level:</b></label>
                                                    <select required class="form-control" name="year_level" id="year_level" autocomplete="off">
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
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-subject" class="btn btn-outline-warning">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Subject Modal -->
            <div class="modal fade" id="editsubjectModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Subject
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editSubjectForm" class="edit-form" role="form" method="post" action="">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editsubject"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editsubject"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>SUBJECT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="subject-icon mx-auto mb-3">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                            </div>
                                            <div class="col-lg-9 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Subject Code:</b></label>
                                                    <input required type="text" class="form-control edit-subjectcode" name="subject_code" id="esubject_code" autocomplete="off">
                                                    <span class="error-message" id="esubject_code-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Subject Name:</b></label>
                                                    <input type="text" class="form-control edit-subjectname" name="subject_name" id="esubject_name" autocomplete="off">
                                                    <span class="error-message" id="esubject_name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year Level:</b></label>
                                                    <select class="form-control" name="year_level" id="eyear_level" autocomplete="off">
                                                        <option class="edit-yearlevel-val" value=""></option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                        <option value="5th Year">5th Year</option>
                                                    </select>
                                                    <span class="error-message" id="eyear_level-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_subjectid" name="subject_id" class="edit-id">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editsubject" class="btn btn-outline-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="delsubject-modal" tabindex="-1" aria-labelledby="delsubjectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="delsubjectModalLabel">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete subject: <strong id="delete_subjectname"></strong>?</p>
                            <input type="hidden" id="delete_subjectid">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="btn-delsubject">Yes, Delete</button>
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
        order: [[5, 'desc']],
        stateSave: true
    });

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#subjectForm')[0].reset();
    }

    // ==========
    // READ (EDIT SUBJECT) 
    // ==========
    $(document).on('click', '.e_subject_id', function() {
        const id = $(this).data('id');
        
        // Retrieve data from the selected row
        const $row = $(this).closest('tr');
        const $getsubjectcode = $row.find('.subject_code_display').text();
        const $getsubjectname = $row.find('.subject_name').val();
        const $getyearlevel = $row.find('.year_level').val();

        console.log('Editing subject:', id, $getsubjectcode, $getsubjectname);

        // Populate edit form
        $('#edit_subjectid').val(id);
        $('#esubject_code').val($getsubjectcode);
        $('#esubject_name').val($getsubjectname);
        $('#eyear_level').val($getyearlevel);
        
        // Clear any previous error messages
        $('.error-message').text('');
        
        // Show modal
        $('#editsubjectModal').modal('show');
    });

    // ==============
    // CREATE (ADD SUBJECT)
    // ==============
    $('#subjectForm').submit(function(e) {
        e.preventDefault();
        
        $('.error-message').text('');
        const subject_code = $('#subject_code').val().trim();
        const subject_name = $('#subject_name').val().trim();
        const year_level = $('#year_level').val();
        let isValid = true;

        // Validation
        if (!subject_code) { 
            $('#subject_code-error').text('Subject code is required'); 
            isValid = false; 
        }
        if (!subject_name) { 
            $('#subject_name-error').text('Subject name is required'); 
            isValid = false; 
        }
        if (!year_level) { 
            $('#year_level-error').text('Year level is required'); 
            isValid = false; 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-subject').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-subject').prop('disabled', true);

        // Use regular form data
        var formData = {
            subject_code: subject_code,
            subject_name: subject_name,
            year_level: year_level
        };

        console.log('Sending data:', formData);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_subject",
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-subject').html('Save');
                $('#btn-subject').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: true
                    }).then(() => {
                        $('#subjectModal').modal('hide');
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
                $('#btn-subject').html('Save');
                $('#btn-subject').prop('disabled', false);
                
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
    // UPDATE SUBJECT
    // ==========
    $('#editSubjectForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_subjectid').val();
        const subject_code = $('#esubject_code').val().trim();
        const subject_name = $('#esubject_name').val().trim();
        const year_level = $('#eyear_level').val();

        // Validation
        let isValid = true;
        if (!subject_code) { 
            $('#esubject_code-error').text('Subject code is required'); 
            isValid = false; 
        } else { 
            $('#esubject_code-error').text(''); 
        }
        if (!subject_name) { 
            $('#esubject_name-error').text('Subject name is required'); 
            isValid = false; 
        } else { 
            $('#esubject_name-error').text(''); 
        }
        if (!year_level) { 
            $('#eyear_level-error').text('Year level is required'); 
            isValid = false; 
        } else { 
            $('#eyear_level-error').text(''); 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-editsubject').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editsubject').prop('disabled', true);

        // Use regular form data
        var formData = {
            id: id,
            subject_code: subject_code,
            subject_name: subject_name,
            year_level: year_level
        };

        console.log('Sending update data:', formData);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_subject",
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editsubject').html('Update');
                $('#btn-editsubject').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: true
                    }).then(() => {
                        $('#editsubjectModal').modal('hide');
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
                $('#btn-editsubject').html('Update');
                $('#btn-editsubject').prop('disabled', false);
                
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
    $(document).on('click', '.d_subject_id', function() {
        var subjectId = $(this).data('id');
        var subjectName = $(this).attr('subject_name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete subject: " + subjectName,
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
                    url: 'transac.php?action=delete_subject',
                    type: 'POST',
                    data: { 
                        id: subjectId 
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

    // Reset modal when closed
    $('#subjectModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('.error-message').text('');
    });

    $('#editsubjectModal').on('hidden.bs.modal', function () {
        $('.error-message').text('');
    });

    // ============================
    // DROPDOWN FILTERING FUNCTIONS
    // ============================

    // Store all rooms and subjects for filtering
    let allRooms = [];
    let allSubjects = [];

    // Fetch all rooms and subjects on page load
    function initializeDropdownData() {
        // Get all rooms
        $('select[name="room_name"] option').each(function() {
            if ($(this).val()) {
                allRooms.push({
                    value: $(this).val(),
                    text: $(this).text(),
                    department: $(this).data('department')
                });
            }
        });

        // Get all subjects
        $('#subject option').each(function() {
            if ($(this).val()) {
                allSubjects.push({
                    value: $(this).val(),
                    text: $(this).text(),
                    yearLevel: $(this).data('year-level')
                });
            }
        });

        console.log('Initialized dropdown data:', {
            rooms: allRooms,
            subjects: allSubjects
        });
    }

    // Filter rooms based on selected department
    function filterRoomsByDepartment(department) {
        const $roomSelect = $('select[name="room_name"]');
        const currentValue = $roomSelect.val();
        
        $roomSelect.empty();
        $roomSelect.append('<option value="">Select Room</option>');
        
        if (department) {
            const filteredRooms = allRooms.filter(room => 
                room.department === department
            );
            
            filteredRooms.forEach(room => {
                $roomSelect.append(
                    $('<option></option>')
                        .attr('value', room.value)
                        .text(room.text)
                        .attr('data-department', room.department)
                );
            });
            
            // Restore previous selection if it exists in filtered list
            if (currentValue && filteredRooms.some(room => room.value === currentValue)) {
                $roomSelect.val(currentValue);
            }
        } else {
            // Show all rooms if no department selected
            allRooms.forEach(room => {
                $roomSelect.append(
                    $('<option></option>')
                        .attr('value', room.value)
                        .text(room.text)
                        .attr('data-department', room.department)
                );
            });
        }
    }

    // Filter subjects based on selected year level
    function filterSubjectsByYearLevel(yearLevel) {
        const $subjectSelect = $('#subject');
        const currentValue = $subjectSelect.val();
        
        $subjectSelect.empty();
        $subjectSelect.append('<option value="">Select Subject</option>');
        
        if (yearLevel) {
            const filteredSubjects = allSubjects.filter(subject => 
                subject.yearLevel === yearLevel
            );
            
            filteredSubjects.forEach(subject => {
                $subjectSelect.append(
                    $('<option></option>')
                        .attr('value', subject.value)
                        .text(subject.text)
                        .attr('data-year-level', subject.yearLevel)
                );
            });
            
            // Restore previous selection if it exists in filtered list
            if (currentValue && filteredSubjects.some(subject => subject.value === currentValue)) {
                $subjectSelect.val(currentValue);
            }
        } else {
            // Show all subjects if no year level selected
            allSubjects.forEach(subject => {
                $subjectSelect.append(
                    $('<option></option>')
                        .attr('value', subject.value)
                        .text(subject.text)
                        .attr('data-year-level', subject.yearLevel)
                );
            });
        }
    }

    // Department change handler - filter rooms
    $('select[name="department"]').change(function() {
        const selectedDepartment = $(this).val();
        console.log('Department changed to:', selectedDepartment);
        filterRoomsByDepartment(selectedDepartment);
    });

    // Year level change handler - filter subjects
    $('select[name="year_level"]').change(function() {
        const selectedYearLevel = $(this).val();
        console.log('Year level changed to:', selectedYearLevel);
        filterSubjectsByYearLevel(selectedYearLevel);
    });

    // Room selection handler to auto-fill department (for consistency)
    $('select[name="room_name"]').change(function() {
        const selectedOption = $(this).find('option:selected');
        const department = selectedOption.data('department');
        if (department && department !== $('select[name="department"]').val()) {
            $('select[name="department"]').val(department);
            // Re-filter rooms to ensure consistency
            filterRoomsByDepartment(department);
        }
    });

    // Initialize dropdown data when page loads
    $(document).ready(function() {
        initializeDropdownData();
        
        // Apply initial filters based on any pre-selected values
        const initialDepartment = $('select[name="department"]').val();
        const initialYearLevel = $('select[name="year_level"]').val();
        
        if (initialDepartment) {
            filterRoomsByDepartment(initialDepartment);
        }
        
        if (initialYearLevel) {
            filterSubjectsByYearLevel(initialYearLevel);
        }
    });

    // Reset filters when modal is closed
    $('#scheduleModal').on('hidden.bs.modal', function () {
        // Reset to show all options
        filterRoomsByDepartment('');
        filterSubjectsByYearLevel('');
    });
});
</script>
</body>
</html>