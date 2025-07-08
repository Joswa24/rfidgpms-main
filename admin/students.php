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
                    <div class="col-sm-12 col-xl-12">
                        <div class="bg-light rounded h-100 p-4">
                            <div class="row">
                                <div class="col-9">
                                    <h6 class="mb-4">Manage Students</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#studentModal">Add Student</button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
    <tr>
        <th scope="col" style="text-align:left;">Department</th>
        <th scope="col" style="text-align:left;">ID Number</th>
        <th scope="col" style="text-align:left;">Full Name</th>
        <th scope="col" style="text-align:left;">Section</th>
        <th scope="col" style="text-align:left;">Year</th>
        <th scope="col">Action</th>
    </tr>
</thead>
                                    <!-- Replace your current table body section with this organized version -->
<tbody>
    <?php 
    include '../connection.php';
    
    // First get all distinct sections
    $sections = mysqli_query($db, "SELECT DISTINCT section FROM students ORDER BY section");
    
    while ($section_row = mysqli_fetch_array($sections)) {
        $current_section = $section_row['section'];
        
        // Then get all years for this section
        $years = mysqli_query($db, "SELECT DISTINCT year FROM students WHERE section = '$current_section' ORDER BY year");
        
        while ($year_row = mysqli_fetch_array($years)) {
            $current_year = $year_row['year'];
            
            // Display section and year header
            echo '<tr class="table-secondary">';
            echo '<td colspan="6" style="font-weight:bold; text-align:center;">';
            echo $current_section . ' - ' . $current_year;
            echo '</td>';
            echo '</tr>';
            
            // Get students for this section and year
            $students = mysqli_query($db, "SELECT s.*, d.department_name 
                                         FROM students s 
                                         LEFT JOIN department d ON s.department_id = d.department_id 
                                         WHERE s.section = '$current_section' AND s.year = '$current_year' 
                                         ORDER BY s.fullname");
            
            while ($row = mysqli_fetch_array($students)) {
                echo '<tr data-id="'.$row['id'].'" data-department-id="'.$row['department_id'].'">';
                echo '<td style="text-align:left;" class="department">'.$row['department_name'].'</td>';
                echo '<td style="text-align:left;" class="id_number">'.$row['id_number'].'</td>';
                echo '<td style="text-align:left;" class="fullname">'.$row['fullname'].'</td>';
                echo '<td style="text-align:left;" class="section">'.$row['section'].'</td>';
                echo '<td style="text-align:left;" class="year">'.$row['year'].'</td>';
                echo '<td width="14%">';
                echo '<center>';
                echo '<button data-id="'.$row['id'].'" class="btn btn-outline-primary btn-sm btn-edit">';
                echo '<i class="bi bi-plus-edit"></i> Edit';
                echo '</button> ';
                echo '<button data-id="'.$row['id'].'" class="btn btn-outline-danger btn-sm btn-del">';
                echo '<i class="bi bi-plus-trash"></i> Delete';
                echo '</button>';
                echo '</center>';
                echo '</td>';
                echo '</tr>';
            }
        }
    }
    ?>
</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Student Modal -->
            <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="studentModalLabel"><i class="bi bi-plus-circle"></i> New Student</h5>
                            <button type="button" onclick="resetForm()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="studentForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-student"></div>
                                <div class="col-lg-12 mb-3">
                                 <div class="form-group">
                                 <label for="department"><b>Department: </b></label>
       
                                    <select name="department" id="department" class="form-control" required>
                                     <option value="">Select Department</option>
                                          <?php 
                                     $dept_query = mysqli_query($db, "SELECT * FROM department ORDER BY department_name");
                                                 while ($dept = mysqli_fetch_array($dept_query)) { ?>
                          <option value="<?php echo $dept['department_id']; ?>">
                        <?php echo $dept['department_name']; ?>
                      </option>
                     <?php } ?>
                                   </select>
                      <span class="student-error" id="department-error" style="color:red;font-size:10px;"></span>
                    </div>
                    </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="id_number"><b>ID Number: </b></label>
                                        <input name="id_number" type="text" id="id_number" class="form-control" 
                                               autocomplete="off" required>
                                        <span class="student-error" id="id_number-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="fullname"><b>Full Name: </b></label>
                                        <input name="fullname" type="text" id="fullname" class="form-control" 
                                               autocomplete="off" required>
                                        <span class="student-error" id="fullname-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="section"><b>Section: </b></label>
                                        <select name="section" id="section" class="form-control" required>
                                            <option value="">Select Section</option>
                                            <option value="West">West</option>
                                            <option value="North">North</option>
                                            <option value="East">East</option>
                                        </select>
                                        <span class="student-error" id="section-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="year"><b>Year: </b></label>
                                        <select name="year" id="year" class="form-control" required>
                                            <option value="">Select Year</option>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                        <span class="student-error" id="year-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
    <button type="button" onclick="resetForm()" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-outline-warning" id="btn-student">Save</button>
</div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Student</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editStudentForm">
                            <input type="hidden" name="id" id="edit_studentid">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editstudent"></div>
                                <!-- Add this to your Edit Student Modal, before the ID Number field -->
<div class="col-lg-12 mb-3">
    <div class="form-group">
        <label for="edit_department"><b>Department: </b></label>
        <select name="department" id="edit_department" class="form-control" required>
            <option value="">Select Department</option>
            <?php 
            mysqli_data_seek($dept_query, 0); // Reset pointer
            while ($dept = mysqli_fetch_array($dept_query)) { ?>
                <option value="<?php echo $dept['department_id']; ?>">
                    <?php echo $dept['department_name']; ?>
                </option>
            <?php } ?>
        </select>
        <span class="student-error" id="edit_department-error" style="color:red;font-size:10px;"></span>
    </div>
</div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="edit_id_number"><b>ID Number: </b></label>
                                        <input name="id_number" type="text" id="edit_id_number" class="form-control" 
                                               autocomplete="off" required>
                                        <span class="student-error" id="edit_id_number-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="edit_fullname"><b>Full Name: </b></label>
                                        <input name="fullname" type="text" id="edit_fullname" class="form-control" 
                                               autocomplete="off" required>
                                        <span class="student-error" id="edit_fullname-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="edit_section"><b>Section: </b></label>
                                        <select name="section" id="edit_section" class="form-control" required>
                                            <option value="">Select Section</option>
                                            <option value="West">West</option>
                                            <option value="North">North</option>
                                            <option value="East">East</option>
                                        </select>
                                        <span class="student-error" id="edit_section-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="edit_year"><b>Year: </b></label>
                                        <select name="year" id="edit_year" class="form-control" required>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>
                                        <span class="student-error" id="edit_year-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-primary" id="btn-editstudent">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <!-- Student CRUD JavaScript -->
    <script>
    $(document).ready(function() {
    // Initialize DataTable
    $('#myDataTable').DataTable({ 
        order: [[0, 'desc']],
        responsive: true
    });

    // Helper function to reset form
    function resetForm() {
        $('.student-error').text('');
        $('.is-invalid').removeClass('is-invalid');
        $('#studentForm')[0].reset();
    }

    // Debug modal events
    $('#studentModal').on('show.bs.modal', function() {
        console.log('Modal opening, resetting form');
        resetForm();
    });

    // Enhanced save button handler
    $(document).on('click', '#btn-student', function(e) {
        e.preventDefault();
        console.log('Save button clicked');
        
        // Get form values - use department_id instead of department
        var formData = {
            department_id: $('#department').val(),  // Changed from 'department' to 'department_id'
            id_number: $('#id_number').val().trim(),
            fullname: $('#fullname').val().trim(),
            section: $('#section').val(),
            year: $('#year').val()
        };
        
        console.log('Form data:', formData);

        // Reset previous errors
        $('.student-error').text('');
        $('.is-invalid').removeClass('is-invalid');

        // Validate inputs
        var isValid = true;
        if (!formData.department_id) {
            $('#department').addClass('is-invalid');
            $('#department-error').text('Department is required');
            isValid = false;
        }
        if (!formData.id_number) {
            $('#id_number').addClass('is-invalid');
            $('#id_number-error').text('ID number is required');
            isValid = false;
        }
        if (!formData.fullname) {
            $('#fullname').addClass('is-invalid');
            $('#fullname-error').text('Full name is required');
            isValid = false;
        }
        if (!formData.section) {
            $('#section').addClass('is-invalid');
            $('#section-error').text('Section is required');
            isValid = false;
        }
        if (!formData.year) {
            $('#year').addClass('is-invalid');
            $('#year-error').text('Year is required');
            isValid = false;
        }

        if (!isValid) {
            console.log('Validation failed');
            return false;
        }

        var $btn = $(this);
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
        $btn.prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_student",
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#studentModal').modal('hide');
                        location.reload();
                    });
                } else {
                    if (response.message.includes('already exists')) {
                        $('#id_number').addClass('is-invalid');
                        $('#id_number-error').text(response.message);
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr);
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMessage = response.message;
                } catch (e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            },
            complete: function() {
                $btn.html('Save');
                $btn.prop('disabled', false);
            }
        });
    });

    // ==========
    // READ (EDIT)
    // ==========
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var $row = $(this).closest('tr');

        // Populate modal with student data
        $('#edit_studentid').val(id);
        $('#edit_department').val($row.data('department-id'));
        $('#edit_id_number').val($row.find('.id_number').text());
        $('#edit_fullname').val($row.find('.fullname').text());
        $('#edit_section').val($row.find('.section').text());
        $('#edit_year').val($row.find('.year').text());
        
        $('#editStudentModal').modal('show');
    });

    // ==========
    // UPDATE (Edit Student)
    // ==========
    $('#btn-editstudent').click(function() {
        var $form = $('#editStudentForm');
        var $btn = $(this);

        // Reset previous errors
        $('.student-error').text('');

        // Validate inputs
        var isValid = true;
        if (!$('#edit_department').val()) {
            $('#edit_department-error').text('Department is required');
            isValid = false;
        }
        if (!$('#edit_id_number').val().trim()) {
            $('#edit_id_number-error').text('ID number is required');
            isValid = false;
        }
        if (!$('#edit_fullname').val().trim()) {
            $('#edit_fullname-error').text('Full name is required');
            isValid = false;
        }
        if (!$('#edit_section').val()) {
            $('#edit_section-error').text('Section is required');
            isValid = false;
        }
        if (!$('#edit_year').val()) {
            $('#edit_year-error').text('Year is required');
            isValid = false;
        }

        if (!isValid) return;

        // Show loading state
        $btn.html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        $btn.prop('disabled', true);

        // Make AJAX request
        $.ajax({
            type: "POST",
            url: "edit1.php?edit=student",
            data: $form.serialize(),
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
                        // Close modal and reload table
                        $('#editStudentModal').modal('hide');
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
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
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
    $(document).on('click', '.btn-del', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        var fullname = $row.find('.fullname').text();

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete student: ${fullname}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                var $btn = $(this);
                $btn.html('<span class="spinner-border spinner-border-sm"></span>');
                $btn.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: 'del.php',
                    data: { type: 'student', id: id },
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
                                $row.fadeOut(400, function() {
                                    $(this).remove();
                                    $('#myDataTable').DataTable().draw(false);
                                });
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                            $btn.html('<i class="bi bi-plus-trash"></i> Delete');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'An error occurred while processing your request', 'error');
                        $btn.html('<i class="bi bi-plus-trash"></i> Delete');
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