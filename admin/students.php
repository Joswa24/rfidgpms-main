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

function getStudentPhoto($photo) {
    $basePath = '../uploads/students/';
    $defaultPhoto = '../assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
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
        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        .upload-img-btn {
            cursor: pointer;
            display: block;
            position: relative;
        }
        .preview-1 {
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .preview-1:hover {
            opacity: 0.8;
        }
        .file-uploader {
            position: relative;
            margin-bottom: 15px;
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
                                <h6 class="mb-4">Manage Students</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#studentModal">
                                    <i class="fas fa-plus-circle"></i> Add Student
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Photo</th>
                                        <th scope="col">ID Number</th>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Year</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $results = mysqli_query($db, "SELECT s.*, d.department_name 
                                                                FROM students s 
                                                                LEFT JOIN department d 
                                                                ON s.department_id = d.department_id 
                                                                ORDER BY s.id DESC"); 
                                    
                                    if ($results === false) {
                                        die("Query failed: " . mysqli_error($db));
                                    }
                                    ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { 
                                        // Use the getStudentPhoto function to get the correct photo path
                                        $photoPath = getStudentPhoto($row['photo']);
                                    ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-student-id="<?php echo $row['id'];?>">
                                        <input class="department_id" type="hidden" value="<?php echo $row['department_id']; ?>" />
                                        <input class="id_number" type="hidden" value="<?php echo $row['id_number']; ?>" />
                                        <input class="fullname" type="hidden" value="<?php echo $row['fullname']; ?>" />
                                        <input class="section" type="hidden" value="<?php echo $row['section']; ?>" />
                                        <input class="year" type="hidden" value="<?php echo $row['year']; ?>" />
                                        <?php if (isset($row['date_added'])): ?>
                                        <input class="date_added" type="hidden" value="<?php echo $row['date_added']; ?>" />
                                        <?php endif; ?>

                                        <td>
                                            <center>
                                                <img class="photo student-photo" src="<?php echo $photoPath; ?>" 
                                                     onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                            </center>
                                        </td>
                                        <td class="student_id"><?php echo $row['id_number']; ?></td>
                                        <td><?php echo $row['fullname']; ?></td>
                                        <td><?php echo $row['department_name']; ?></td>
                                        <td><?php echo $row['year']; ?></td>
                                        <td><?php echo $row['section']; ?></td>
                                        <td width="14%">
                                            <center>
                                                <button data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-outline-primary btn-sm btn-edit e_student_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button student_name="<?php echo $row['fullname']; ?>" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_student_id">
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

            <!-- Add Student Modal -->
            <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="studentForm" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-student"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-student"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>STUDENT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label for="photo" class="upload-img-btn" style="cursor: pointer;">
                                                        <img class="preview-1" src="../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg"
                                                            style="width: 140px!important; height: 130px!important; position: absolute; border: 1px solid gray; top: 25%;"
                                                            title="Upload Photo.." />
                                                    </label>
                                                    <input type="file" id="photo" name="photo" class="upload-field-1" 
                                                            style="opacity: 0; position: absolute; z-index: -1;" accept="image/*" required>
                                                    <span class="error-message" id="photo-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select required class="form-control dept_ID" name="department_id" id="department_id" autocomplete="off">
                                                        <option value="">Select Department</option>
                                                        <?php
                                                            $sql = "SELECT * FROM department ORDER BY department_name";
                                                            $result = $db->query($sql);
                                                            while ($dept = $result->fetch_assoc()) {
                                                                echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="department_id-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label><b>ID Number:</b></label>
                                                    <input required type="text" class="form-control" name="id_number" id="id_number" autocomplete="off">
                                                    <span class="error-message" id="id_number-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Full Name:</b></label>
                                                    <input required type="text" class="form-control" name="fullname" id="fullname" autocomplete="off">
                                                    <span class="error-message" id="fullname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year:</b></label>
                                                    <select required class="form-control" name="year" id="year" autocomplete="off">
                                                        <option value="">Select Year</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="year-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <select required class="form-control" name="section" id="section" autocomplete="off">
                                                        <option value="">Select Section</option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="error-message" id="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-student" class="btn btn-outline-warning">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editstudentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editStudentForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editstudent"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editstudent"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>STUDENT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label name="upload-label" class="upload-img-btn">
                                                        <input type="file" id="editPhoto" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Photo.."/>
                                                        <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                                        <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select class="form-control dept_ID" name="department_id" id="edepartment_id" autocomplete="off">
                                                        <option class="edit-dept-val" value=""></option>
                                                        <?php
                                                            $sql = "SELECT * FROM department ORDER BY department_name";
                                                            $result = $db->query($sql);
                                                            while ($dept = $result->fetch_assoc()) {
                                                                echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="edepartment_id-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label><b>ID Number:</b></label>
                                                    <input required type="text" class="form-control edit-idnumber" name="id_number" id="eid_number" autocomplete="off">
                                                    <span class="error-message" id="eid_number-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Full Name:</b></label>
                                                    <input type="text" class="form-control edit-fullname" name="fullname" id="efullname" autocomplete="off">
                                                    <span class="error-message" id="efullname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year:</b></label>
                                                    <select class="form-control" name="year" id="eyear" autocomplete="off">
                                                        <option class="edit-year-val" value=""></option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="eyear-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <select class="form-control" name="section" id="esection" autocomplete="off">
                                                        <option class="edit-section-val" value=""></option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="error-message" id="esection-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_studentid" name="student_id" class="edit-id">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editstudent" class="btn btn-outline-primary">Update</button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
    // Initialize DataTable
    var dataTable = $('#myDataTable').DataTable({
        order: [[7, 'desc']],
        stateSave: true
    });

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#studentForm')[0].reset();
        $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
    }

    // ==========
    // READ (EDIT STUDENT) - FIXED
    // ==========
    $(document).on('click', '.e_student_id', function() {
        const id = $(this).data('id');
        
        // Retrieve data from the selected row
        const $row = $(this).closest('tr');
        const $getphoto = $row.find('.photo').attr('src');
        const $getidnumber = $row.find('.student_id').text();
        const $getdept = $row.find('.department_id').val();
        const $getfullname = $row.find('.fullname').val();
        const $getyear = $row.find('.year').val();
        const $getsection = $row.find('.section').val();

        console.log('Editing student:', id, $getidnumber, $getfullname); // Debug log

        // Populate edit form
        $('#edit_studentid').val(id);
        $('.edit-photo').attr('src', $getphoto);
        $('#eid_number').val($getidnumber);
        $('#edepartment_id').val($getdept);
        $('#efullname').val($getfullname);
        $('#eyear').val($getyear);
        $('#esection').val($getsection);
        $('.capturedImage').val($getphoto);
        
        // Clear any previous error messages
        $('.error-message').text('');
        
        // Show modal
        $('#editstudentModal').modal('show');
    });

    // ==============
    // CREATE (ADD STUDENT)
    // ==============
    $('#studentForm').submit(function(e) {
        e.preventDefault();
        
        $('.error-message').text('');
        const department_id = $('#department_id').val();
        const id_number = $('#id_number').val().trim();
        const fullname = $('#fullname').val().trim();
        const year = $('#year').val();
        const section = $('#section').val();
        const photo = $('#photo')[0].files[0];
        let isValid = true;

        // Validation
        if (!department_id) { 
            $('#department_id-error').text('Department is required'); 
            isValid = false; 
        }
        if (!id_number) { 
            $('#id_number-error').text('ID Number is required'); 
            isValid = false; 
        }
        if (!fullname) { 
            $('#fullname-error').text('Full name is required'); 
            isValid = false; 
        }
        if (!year) { 
            $('#year-error').text('Year is required'); 
            isValid = false; 
        }
        if (!section) { 
            $('#section-error').text('Section is required'); 
            isValid = false; 
        }
        if (!photo) { 
            $('#photo-error').text('Photo is required'); 
            isValid = false; 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-student').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-student').prop('disabled', true);

        var formData = new FormData(this);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_student",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-student').html('Save');
                $('#btn-student').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: true
                    }).then(() => {
                        $('#studentModal').modal('hide');
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
                $('#btn-student').html('Save');
                $('#btn-student').prop('disabled', false);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred: ' + error
                });
            }
        });
    });

    // ==========
    // UPDATE STUDENT
    // ==========
    $('#editStudentForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_studentid').val();
        const department_id = $('#edepartment_id').val();
        const id_number = $('#eid_number').val().trim();
        const fullname = $('#efullname').val().trim();
        const year = $('#eyear').val();
        const section = $('#esection').val();

        // Validation
        let isValid = true;
        if (!department_id) { 
            $('#edepartment_id-error').text('Department is required'); 
            isValid = false; 
        } else { 
            $('#edepartment_id-error').text(''); 
        }
        if (!id_number) { 
            $('#eid_number-error').text('ID Number is required'); 
            isValid = false; 
        } else { 
            $('#eid_number-error').text(''); 
        }
        if (!fullname) { 
            $('#efullname-error').text('Full name is required'); 
            isValid = false; 
        } else { 
            $('#efullname-error').text(''); 
        }
        if (!year) { 
            $('#eyear-error').text('Year is required'); 
            isValid = false; 
        } else { 
            $('#eyear-error').text(''); 
        }
        if (!section) { 
            $('#esection-error').text('Section is required'); 
            isValid = false; 
        } else { 
            $('#esection-error').text(''); 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-editstudent').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editstudent').prop('disabled', true);

        var formData = new FormData(this);
        formData.append('id', id);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_student",
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editstudent').html('Update');
                $('#btn-editstudent').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: true
                    }).then(() => {
                        $('#editstudentModal').modal('hide');
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
                $('#btn-editstudent').html('Update');
                $('#btn-editstudent').prop('disabled', false);
                
                try {
                    // Try to parse the error response as JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    Swal.fire({
                        title: 'Error!',
                        text: errorResponse.message || 'An error occurred',
                        icon: 'error'
                    });
                } catch (e) {
                    // If not JSON, show raw response
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred: ' + xhr.responseText,
                        icon: 'error'
                    });
                }
            }
        });
    });

    // Handle delete button click
    $(document).on('click', '.d_student_id', function() {
        var studentId = $(this).data('id');
        var studentName = $(this).attr('student_name');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete student: " + studentName,
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
                    url: "transac.php?action=delete_student",
                    type: 'POST',
                    data: { 
                        id: studentId 
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
    $('#studentModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
        $('.error-message').text('');
    });

    $('#editstudentModal').on('hidden.bs.modal', function () {
        $('.error-message').text('');
    });

    // Image preview functionality for both forms
    $(document).on('change', '[class^=upload-field-]', function() {
        readURL(this);
    });

    // Click handler for edit photo upload
    $(document).on('click', '.edit-photo', function() {
        $('#editPhoto').click();
    });

    function readURL(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
            const maxSize = 2 * 1024 * 1024; // 2MB

            // Validate file format
            if (!validFormats.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Format',
                    text: 'Only JPG and PNG formats are allowed.',
                });
                input.value = ''; // Reset the input
                return;
            }

            // Validate file size
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Maximum file size is 2MB.',
                });
                input.value = ''; // Reset the input
                return;
            }

            // Preview the image
            var reader = new FileReader();
            reader.onload = function (e) {
                // Find the closest preview image
                $(input).closest('.file-uploader').find('.preview-1').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    }
});
    </script>
</body>
</html>