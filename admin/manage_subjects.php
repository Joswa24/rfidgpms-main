<?php include '../connection.php'?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->

        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="col-sm-12 col-xl-12">
                        <div class="bg-light rounded h-100 p-4">
                            <div class="row">
                                <div class="col-9">
                                    <h6 class="mb-4">Manage Subjects</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#subjectModal">Add Subject</button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="subjectTable">
                                   <thead>
                                        <tr>
                                            <th scope="col" style="text-align:left;">Subject Code</th>
                                            <th scope="col" style="text-align:left;">Subject Name</th>
                                            <th scope="col" style="text-align:left;">Year Level</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $results = mysqli_query($db, "SELECT * FROM subjects ORDER BY subject_code"); 
                                        while ($row = mysqli_fetch_array($results)) { ?>
                                        <tr data-id="<?php echo $row['id']; ?>">
                                            <td style="text-align:left;" class="subject_code"><?php echo $row['subject_code']; ?></td>
                                            <td style="text-align:left;" class="subject_name"><?php echo $row['subject_name']; ?></td>
                                            <td style="text-align:left;" class="year_level"><?php echo $row['year_level']; ?></td>
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

            <!-- Add Subject Modal -->
            <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="subjectModalLabel"><i class="bi bi-plus-circle"></i> New Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="subjectForm">
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" id="form-error"></div>
                                
                                <div class="mb-3">
                                    <label for="subject_code" class="form-label"><b>Subject Code: </b></label>
                                    <input name="subject_code" type="text" id="subject_code" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="subject_code-error"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject_name" class="form-label"><b>Subject Name: </b></label>
                                    <input name="subject_name" type="text" id="subject_name" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="subject_name-error"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="year_level" class="form-label"><b>Year Level: </b></label>
                                    <select name="year_level" id="year_level" class="form-control" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                        <option value="5th Year">5th Year</option>
                                    </select>
                                    <div class="error-message" id="year_level-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-subject">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Subject Modal -->
            <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editSubjectForm">
                            <input type="hidden" name="id" id="edit_subjectid">
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" id="edit-form-error"></div>
                                
                                <div class="mb-3">
                                    <label for="edit_subject_code" class="form-label"><b>Subject Code: </b></label>
                                    <input name="subject_code" type="text" id="edit_subject_code" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="edit_subject_code-error"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_subject_name" class="form-label"><b>Subject Name: </b></label>
                                    <input name="subject_name" type="text" id="edit_subject_name" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="edit_subject_name-error"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_year_level" class="form-label"><b>Year Level: </b></label>
                                    <select name="year_level" id="edit_year_level" class="form-control" required>
                                        <option value="">Select Year Level</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                        <option value="5th Year">5th Year</option>
                                    </select>
                                    <div class="error-message" id="edit_year_level-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-primary" id="btn-editsubject">Update</button>
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

    <!-- Subject CRUD JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#subjectTable').DataTable({ 
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: 3 } // Disable sorting on action column
            ]
        });
        
        // Helper function to reset form
        function resetForm() {
            $('.error-message').text('');
            $('.alert').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
            $('#subjectForm')[0].reset();
        }

        // Reset form when modal closes
        $('#subjectModal').on('hidden.bs.modal', resetForm);

        // ==============
        // CREATE (ADD SUBJECT)
        // ==============
        $(document).on('click', '#btn-subject', function() {
            const $btn = $(this);
            const subject_code = $('#subject_code').val().trim();
            const subject_name = $('#subject_name').val().trim();
            const year_level = $('#year_level').val();

            // Reset previous errors
            $('.error-message').text('');
            $('.form-control').removeClass('is-invalid');
            $('#form-error').addClass('d-none');
            
            // Validate inputs
            let isValid = true;
            
            if (!subject_code) {
                $('#subject_code').addClass('is-invalid');
                $('#subject_code-error').text('Subject code is required');
                isValid = false;
            }
            
            if (!subject_name) {
                $('#subject_name').addClass('is-invalid');
                $('#subject_name-error').text('Subject name is required');
                isValid = false;
            }
            
            if (!year_level) {
                $('#year_level').addClass('is-invalid');
                $('#year_level-error').text('Year level is required');
                isValid = false;
            }
            
            if (!isValid) {
                $('#form-error').removeClass('d-none').text('Please fix the errors in the form');
                return;
            }
            
            // Show loading state
            const originalBtnText = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
            $btn.prop('disabled', true);
            
            // Make AJAX request to transac.php
            $.ajax({
                type: "POST",
                url: "transac.php?action=add_subject",
                data: { 
                    subject_code: subject_code,
                    subject_name: subject_name,
                    year_level: year_level
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
                            $('#subjectModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        // Show specific error messages
                        if (response.message.includes('code')) {
                            $('#subject_code').addClass('is-invalid');
                            $('#subject_code-error').text(response.message);
                        } else {
                            $('#form-error').removeClass('d-none').text(response.message);
                        }
                    }
                },
                error: function(xhr) {
                    $('#form-error').removeClass('d-none').text('An error occurred: ' + xhr.responseText);
                },
                complete: function() {
                    $btn.html(originalBtnText);
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

            // Populate modal with subject data
            $('#edit_subjectid').val(id);
            $('#edit_subject_code').val($row.find('.subject_code').text());
            $('#edit_subject_name').val($row.find('.subject_name').text());
            $('#edit_year_level').val($row.find('.year_level').text());

            $('#editSubjectModal').modal('show');
        });

        // ==========
        // UPDATE (Edit Subject)
        // ==========
        $(document).on('click', '#btn-editsubject', function() {
            var $btn = $(this);
            var id = $('#edit_subjectid').val();
            var subject_code = $('#edit_subject_code').val().trim();
            var subject_name = $('#edit_subject_name').val().trim();
            var year_level = $('#edit_year_level').val();
            
            // Reset previous errors
            $('.error-message').text('');
            $('.form-control').removeClass('is-invalid');
            $('#edit-form-error').addClass('d-none').text('');
            
            // Validate inputs
            let isValid = true;
            
            if (!subject_code) {
                $('#edit_subject_code').addClass('is-invalid');
                $('#edit_subject_code-error').text('Subject code is required');
                isValid = false;
            }
            
            if (!subject_name) {
                $('#edit_subject_name').addClass('is-invalid');
                $('#edit_subject_name-error').text('Subject name is required');
                isValid = false;
            }
            
            if (!year_level) {
                $('#edit_year_level').addClass('is-invalid');
                $('#edit_year_level-error').text('Year level is required');
                isValid = false;
            }
            
            if (!isValid) {
                $('#edit-form-error').removeClass('d-none').text('Please fix the errors in the form');
                return;
            }
            
            // Show loading state
            const originalBtnText = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm"></span> Updating...');
            $btn.prop('disabled', true);
            
            // Make AJAX request to edit1.php
            $.ajax({
                type: "POST",
                url: "edit1.php?edit=subject",
                data: { 
                    id: id,
                    subject_code: subject_code,
                    subject_name: subject_name,
                    year_level: year_level
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
                            $('#editSubjectModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        if (response.message.includes('code')) {
                            $('#edit_subject_code').addClass('is-invalid');
                            $('#edit_subject_code-error').text(response.message);
                        } else {
                            $('#edit-form-error').removeClass('d-none').text(response.message);
                        }
                    }
                },
                error: function(xhr) {
                    $('#edit-form-error').removeClass('d-none').text('An error occurred: ' + xhr.responseText);
                },
                complete: function() {
                    $btn.html(originalBtnText);
                    $btn.prop('disabled', false);
                }
            });
        });

        // ==========
        // DELETE (Using del.php)
        // ==========
        $(document).on('click', '.btn-del', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var $row = $(this).closest('tr');
            var subject_name = $row.find('.subject_name').text();
            var $btn = $(this);
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete subject: ${subject_name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    const originalBtnText = $btn.html();
                    $btn.html('<span class="spinner-border spinner-border-sm"></span>');
                    $btn.prop('disabled', true);
                    
                    // Make AJAX request to del.php
                    $.ajax({
                        type: 'POST',
                        url: 'del.php',
                        data: { 
                            type: 'delete_subject', 
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
                                    // Remove row from DataTable
                                    var table = $('#subjectTable').DataTable();
                                    table.row($row).remove().draw();
                                });
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error!', 'An error occurred: ' + xhr.responseText, 'error');
                        },
                        complete: function() {
                            $btn.html(originalBtnText);
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