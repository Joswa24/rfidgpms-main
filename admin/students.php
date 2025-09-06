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
                                    Add Student
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
    <tr class="table-<?php echo $row['id'];?>">
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
                <img class="photo" src="<?php echo $photoPath; ?>" width="50px" height="50px" 
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
                <button data-id="<?php echo $row['id'];?>" class="btn btn-outline-primary btn-sm btn-edit e_student_id">
                    <i class="bi bi-plus-edit"></i> Edit 
                </button>
                <button student_name="<?php echo $row['fullname']; ?>" 
                        data-id="<?php echo $row['id']; ?>" 
                        class="btn btn-outline-danger btn-sm btn-del d_student_id">
                    <i class="bi bi-plus-trash"></i> Delete
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
            <form id="studentForm" role="form" method="post" action="" enctype="multipart/form-data">
                <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">
                                    <i class="bi bi-plus-circle"></i> New Student
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="col-lg-11 mb-2 mt-1" id="mgs-student" style="margin-left: 4%"></div>
                            <div class="modal-body">
                                <div class="row justify-content-md-center">
                                    <div id="msg-student"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="" style="border: 1PX solid #b3f0fc;padding: 1%;background-color: #f7cfa1;color: black;font-size: 1.2rem">STUDENT INFORMATION</div>
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
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>DEPARTMENT:</label>
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
                                                    <span class="dept-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label>ID NUMBER:</label>
                                                    <input required type="text" class="form-control" name="id_number" id="id_number" autocomplete="off">
                                                    <span class="id-error"></span>
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
                                                    <label>FULL NAME:</label>
                                                    <input required type="text" class="form-control" name="fullname" id="fullname" autocomplete="off">
                                                    <span class="name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>YEAR:</label>
                                                    <select required class="form-control" name="year" id="year" autocomplete="off">
                                                        <option value="">Select Year</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="year-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>SECTION:</label>
                                                    <select required class="form-control" name="section" id="section" autocomplete="off">
                                                        <option value="">Select Section</option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="section-error"></span>
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
                        </div>
                    </div>
                </div>
            </form>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editstudentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="bi bi-pencil"></i> Edit Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="col-lg-11 mb-2 mt-1" id="mgs-student" style="margin-left: 4%"></div>
                        <form id="editStudentForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body" id="editModal">
                                <div class="row justify-content-md-center">
                                    <div id="msg-student" style=""></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="" style="border: 1PX solid #b3f0fc;padding: 1%;background-color: #f7cfa1;color: black;font-size: 1.2rem">STUDENT INFORMATION</div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label name="upload-label" class="upload-img-btn">
                                                        <input type="file" id="photo" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Foto.."/>
                                                        <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                                        <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>DEPARTMENT:</label>
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
                                                    <span class="dept-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label>ID NUMBER:</label>
                                                    <input required type="text" class="form-control edit-idnumber" name="id_number" id="id_number1" autocomplete="off">
                                                    <span class="id-error"></span>
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
                                                    <label>FULL NAME:</label>
                                                    <input type="text" class="form-control edit-fullname" name="fullname" id="fullname" autocomplete="off">
                                                    <span class="name-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>YEAR:</label>
                                                    <select class="form-control" name="year" id="eyear" autocomplete="off">
                                                        <option class="edit-year-val" value=""></option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="year-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>SECTION:</label>
                                                    <select class="form-control" name="section" id="esection" autocomplete="off">
                                                        <option class="edit-section-val" value=""></option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_studentid" name="">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <input value="Update" name="update" type="submit" id="btn-editstudent" class="btn btn-outline-warning"/>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Student Modal -->
            <div class="modal fade" id="delstudent-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-trash"></i> Delete Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="delete-form">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-delstudent"></div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Name:</b></label>
                                        <input type="text" id="delete_studentname" class="form-control d-student student_name" autocomplete="off" readonly="">
                                        <span class="studentname-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="student_id" id="delete_studentid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">No</button>
                                <button type="button" class="btn btn-outline-primary remove_id" id="btn-delstudent">Yes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script>
      var currentDeleteStudentId = null;
      var currentDeleteStudentName = null;

      // Handle delete button click
      $(document).on('click', '.d_student_id', function() {
          currentDeleteStudentId = $(this).data('id');
          currentDeleteStudentName = $(this).attr('student_name');
          
          $('#delete_studentid').val(currentDeleteStudentId);
          $('#delete_studentname').val(currentDeleteStudentName);
          
          $('#delstudent-modal').modal('show');
      });

      // Handle delete confirmation
      $('#btn-delstudent').on('click', function() {
          if (currentDeleteStudentId) {
              $.ajax({
                  url: 'transac.php?action=delete_student',
                  type: 'POST',
                  data: { student_id: currentDeleteStudentId },
                  success: function(response) {
                      var res = JSON.parse(response);
                      if (res.status === 'success') {
                          Swal.fire({
                              title: 'Success!',
                              text: res.message,
                              icon: 'success'
                          }).then(() => {
                              location.reload();
                          });
                      } else {
                          Swal.fire({
                              title: 'Error!',
                              text: res.message,
                              icon: 'error'
                          });
                      }
                  },
                  error: function(xhr, status, error) {
                      Swal.fire({
                          title: 'Error!',
                          text: 'An error occurred while deleting the student.',
                          icon: 'error'
                      });
                  }
              });
          }
      });
    $(document).ready(function() {
        // Initialize DataTable
        var dataTable = $('#myDataTable').DataTable({
            order: [[7, 'desc']],
            stateSave: true
        });

        // Handle form submission for adding student
        $('#studentForm').submit(function(e) {
            e.preventDefault();
            
            // Validate required fields
            const requiredFields = ['department_id', 'id_number', 'fullname', 'year', 'section'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const fieldValue = $('#' + field).val();
                if (!fieldValue || fieldValue.trim() === '') {
                    isValid = false;
                    $('.' + field + '-error').text('This field is required').css('color', 'red');
                } else {
                    $('.' + field + '-error').text('');
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill all required fields correctly',
                    icon: 'error'
                });
                return;
            }

            var formData = new FormData(this);
            
            // Show loading indicator
            $('#btn-student').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-student').prop('disabled', true);
            
            $.ajax({
                url: 'transac.php?action=add_student',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        // Store success message in session
                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: { 
                                message: response.message,
                                type: 'success'
                            },
                            success: function() {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success'
                                }).then(() => {
                                    // Close modal and refresh page to show new record
                                    $('#studentModal').modal('hide');
                                    location.reload();
                                });
                            }
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
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred: ' + error,
                        icon: 'error'
                    });
                }
            });
        });

        // ID number duplicate check on blur
        $('#id_number').on('blur', function() {
            const idNumber = $(this).val();
            
            if (idNumber) {
                $.ajax({
                    url: 'check_id.php',
                    method: 'POST',
                    data: { id_number: idNumber },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.exists) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Duplicate ID Number',
                                text: 'This ID number already exists in the system.',
                            }).then(() => {
                                $('#id_number').val('').focus();
                            });
                        }
                    }
                });
            }
        });

        // Handle edit button click
        $(document).on('click', '.btn-edit', function() {
            var $id = $(this).data('id');
            
            // Retrieve data from the selected row
            var $getphoto = $('.table-' + $id + ' .photo').attr('src');
            var $getidnumber = $('.table-' + $id + ' .student_id').html();
            var $getdept = $('.table-' + $id + ' .department_id').val();
            var $getfullname = $('.table-' + $id + ' .fullname').val();
            var $getyear = $('.table-' + $id + ' .year').val();
            var $getsection = $('.table-' + $id + ' .section').val();

            // Update the modal fields with data
            $('.edit-photo').attr('src', $getphoto);
            $('.edit-idnumber').val($getidnumber);
            $('.edit-id').val($id);
            $('#edepartment_id').val($getdept);
            $('.edit-fullname').val($getfullname);
            $('#eyear').val($getyear);
            $('#esection').val($getsection);
            $('.capturedImage').val($getphoto);

            // Show the modal
            $('#editstudentModal').modal('show');
        });

        // Handle edit form submission
        var currentEditStudentId = null;

        $(document).on('click', '.btn-edit', function() {
            currentEditStudentId = $(this).data('id');
            
            // Retrieve data from the selected row
            var $getphoto = $('.table-' + currentEditStudentId + ' .photo').attr('src');
            var $getidnumber = $('.table-' + currentEditStudentId + ' .student_id').html();
            // ... rest of your existing code ...

            // Add the ID to the hidden input field
            $('.edit-id').val(currentEditStudentId);
            
            // Show the modal
            $('#editstudentModal').modal('show');
        });

        $('#editStudentForm').submit(function(e) {
            e.preventDefault();
            
            // Use the stored user ID or get it from the hidden field
            var studentId = currentEditStudentId || $('.edit-id').val();
            
            if (!studentId) {
                Swal.fire({
                    title: 'Error!',
                    text: 'No student selected. Please select a student first.',
                    icon: 'error'
                });
                return;
            }

            var formData = new FormData(this);
            formData.append('id', studentId);

            // Show loading indicator
            $('#btn-editstudent').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editstudent').prop('disabled', true);
            
            $.ajax({
                url: 'edit1.php?edit=student&id=' + studentId,
                type: 'POST',
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
                            title: 'Success!',
                            text: response.message,
                            icon: 'success'
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
                error: function(xhr) {
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
        $(document).on('click', '.btn-del', function() {
            var studentId = $(this).data('id');
            var studentName = $(this).attr('student_name');
            
            // Store these for use in the AJAX call
            currentDeleteStudentId = studentId;
            currentDeleteStudentName = studentName;
            
            // Show confirmation dialog
            $('#delete_studentname').val(studentName);
            $('#delete_studentid').val(studentId);
            $('#delstudent-modal').modal('show');
        });

        // Handle the actual deletion when "Yes" is clicked in the modal
        $(document).on('click', '#btn-delstudent', function() {
            var studentId = $('#delete_studentid').val();
            var studentName = $('#delete_studentname').val();
            
            // Show loading indicator
            $('#btn-delstudent').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
            $('#btn-delstudent').prop('disabled', true);
            
            $.ajax({
                url: 'del.php',
                type: 'POST',
                data: { 
                    type: 'student',
                    id: studentId 
                },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-delstudent').html('Yes');
                    $('#btn-delstudent').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        // Close the modal
                        $('#delstudent-modal').modal('hide');
                        
                        // Remove the row from the table
                        dataTable.row($('.table-' + studentId)).remove().draw();
                        
                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            timer: 3000,
                            showConfirmButton: false
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
                    $('#btn-delstudent').html('Yes');
                    $('#btn-delstudent').prop('disabled', false);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred: ' + error,
                        icon: 'error'
                    });
                }
            });
        });

        // Reset modal when closed
        $('#studentModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
        });

        // Image preview functionality
        $("[class^=upload-field-]").change(function () {
            readURL(this);
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validFormats = ['image/jpeg', 'image/png'];
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
                    var num = $(input).attr('class').split('-')[2];
                    $('.file-uploader .preview-' + num).attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });
    </script>
</body>
</html>