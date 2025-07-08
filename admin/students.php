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
                                            <th scope="col" style="text-align:left;">ID Number</th>
                                            <th scope="col" style="text-align:left;">Full Name</th>
                                            <th scope="col" style="text-align:left;">Section</th>
                                            <th scope="col" style="text-align:left;">Year</th>
                                            <th scope="col" style="text-align:left;">RFID UID</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php include '../connection.php'; ?>
                                        <?php $results = mysqli_query($db, "SELECT * FROM students"); ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { ?>
                                        <tr class="table-<?php echo $row['id'];?>">
                                            <td style="text-align:left;" class="id_number"><?php echo $row['id_number']; ?></td>
                                            <td style="text-align:left;" class="fullname"><?php echo $row['fullname']; ?></td>
                                            <td style="text-align:left;" class="section"><?php echo $row['section']; ?></td>
                                            <td style="text-align:left;" class="year"><?php echo $row['year']; ?></td>
                                            <td style="text-align:left;" class="rfid_uid"><?php echo $row['rfid_uid']; ?></td>
                                            <td width="14%">
                                                <center>
                                                    <button data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-outline-primary btn-sm btn-edit">
                                                        <i class="bi bi-plus-edit"></i> Edit 
                                                    </button>
                                                    <button data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-outline-danger btn-sm btn-del">
                                                        <i class="bi bi-plus-trash"></i> Delete 
                                                    </button>
                                                </center> 
                                            </td>
                                        </tr>
                                        <?php } ?>
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
                                        <input name="section" type="text" id="section" class="form-control" 
                                               autocomplete="off" required>
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
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="rfid_uid"><b>RFID UID: </b></label>
                                        <input name="rfid_uid" type="text" id="rfid_uid" class="form-control" 
                                               autocomplete="off">
                                        <span class="student-error" id="rfid_uid-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="resetForm()" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-student">Save</button>
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
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editstudent"></div>
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
                                        <input name="section" type="text" id="edit_section" class="form-control" 
                                               autocomplete="off" required>
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
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="edit_rfid_uid"><b>RFID UID: </b></label>
                                        <input name="rfid_uid" type="text" id="edit_rfid_uid" class="form-control" 
                                               autocomplete="off">
                                        <span class="student-error" id="edit_rfid_uid-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="id" id="edit_studentid">
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
        $('#myDataTable').DataTable({ order: [[0, 'desc']] });

        // Helper function to reset form
        function resetForm() {
            $('.student-error').text('');
            $('#studentForm')[0].reset();
        }

        // ==============
        // CREATE (ADD STUDENT)
        // ==============
        $('#btn-student').click(function() {
            // Get form values
            var id_number = $('#id_number').val().trim();
            var fullname = $('#fullname').val().trim();
            var section = $('#section').val().trim();
            var year = $('#year').val();
            var rfid_uid = $('#rfid_uid').val().trim();
            var $btn = $(this);
            
            // Reset previous errors
            $('.student-error').text('');
            
            // Validate inputs
            var isValid = true;
            if (!id_number) {
                $('#id_number-error').text('ID number is required');
                isValid = false;
            }
            if (!fullname) {
                $('#fullname-error').text('Full name is required');
                isValid = false;
            }
            if (!section) {
                $('#section-error').text('Section is required');
                isValid = false;
            }
            if (!year) {
                $('#year-error').text('Year is required');
                isValid = false;
            }
            
            if (!isValid) return;
            
            // Show loading state
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            $btn.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                type: "POST",
                url: "transac.php?action=add_student",
                data: { 
                    id_number: id_number,
                    fullname: fullname,
                    section: section,
                    year: year,
                    rfid_uid: rfid_uid
                },
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
                            // Reset form and close modal
                            resetForm();
                            $('#studentModal').modal('hide');
                            // Refresh table
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
                    // Reset button state
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
            $('#edit_id_number').val($row.find('.id_number').text());
            $('#edit_fullname').val($row.find('.fullname').text());
            $('#edit_section').val($row.find('.section').text());
            $('#edit_year').val($row.find('.year').text());
            $('#edit_rfid_uid').val($row.find('.rfid_uid').text());
            
            $('#editStudentModal').modal('show');
        });

        // ==========
        // UPDATE (Edit Student)
        // ==========
        $('#btn-editstudent').click(function() {
            // Get form values
            var id = $('#edit_studentid').val();
            var id_number = $('#edit_id_number').val().trim();
            var fullname = $('#edit_fullname').val().trim();
            var section = $('#edit_section').val().trim();
            var year = $('#edit_year').val();
            var rfid_uid = $('#edit_rfid_uid').val().trim();
            var $btn = $(this);
            
            // Reset previous errors
            $('.student-error').text('');
            
            // Validate inputs
            var isValid = true;
            if (!id_number) {
                $('#edit_id_number-error').text('ID number is required');
                isValid = false;
            }
            if (!fullname) {
                $('#edit_fullname-error').text('Full name is required');
                isValid = false;
            }
            if (!section) {
                $('#edit_section-error').text('Section is required');
                isValid = false;
            }
            if (!year) {
                $('#edit_year-error').text('Year is required');
                isValid = false;
            }
            
            if (!isValid) return;
            
            // Show loading state
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Updating...');
            $btn.prop('disabled', true);
            
            // Make AJAX request to edit1.php
            $.ajax({
                type: "POST",
                url: "edit1.php?edit=student&id=" + id,
                data: { 
                    id: id,
                    id_number: id_number,
                    fullname: fullname,
                    section: section,
                    year: year,
                    rfid_uid: rfid_uid
                },
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
                            // Update the table row without reloading
                            var $row = $('.table-' + id);
                            $row.find('.id_number').text(id_number);
                            $row.find('.fullname').text(fullname);
                            $row.find('.section').text(section);
                            $row.find('.year').text(year);
                            $row.find('.rfid_uid').text(rfid_uid);
                            
                            $('#editStudentModal').modal('hide');
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
                    // Reset button state
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
                        url: 'transac.php?action=delete_student',
                        data: { id: id },
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
                                    $row.remove();
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