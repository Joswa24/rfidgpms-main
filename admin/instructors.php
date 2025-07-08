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
                                    <h6 class="mb-4">Manage Instructors</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#instructorModal">Add Instructor</button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="instructorTable">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="text-align:left;">Full Name</th>
                                            <th scope="col" style="text-align:left;">RFID Number</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php include '../connection.php'; ?>
                                        <?php $results = mysqli_query($db, "SELECT * FROM instructor"); ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { ?>
                                        <tr data-id="<?php echo $row['id']; ?>">
                                            <td style="text-align:left;" class="fullname"><?php echo $row['fullname']; ?></td>
                                            <td style="text-align:left;" class="rfid_number"><?php echo $row['rfid_number']; ?></td>
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

            <!-- Add Instructor Modal -->
            <div class="modal fade" id="instructorModal" tabindex="-1" aria-labelledby="instructorModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="instructorModalLabel"><i class="bi bi-plus-circle"></i> New Instructor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="instructorForm">
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" id="form-error"></div>
                                <div class="mb-3">
                                    <label for="fullname" class="form-label"><b>Full Name: </b></label>
                                    <input name="fullname" type="text" id="fullname" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="fullname-error"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="rfid_number" class="form-label"><b>RFID Number: </b></label>
                                    <input name="rfid_number" type="text" id="rfid_number" class="form-control" autocomplete="off">
                                    <div class="error-message" id="rfid_number-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-instructor">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Instructor Modal -->
            <div class="modal fade" id="editInstructorModal" tabindex="-1" aria-labelledby="editInstructorModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Instructor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editInstructorForm">
                            <input type="hidden" name="id" id="edit_instructorid">
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" id="edit-form-error"></div>
                                <div class="mb-3">
                                    <label for="edit_fullname" class="form-label"><b>Full Name: </b></label>
                                    <input name="fullname" type="text" id="edit_fullname" class="form-control" autocomplete="off" required>
                                    <div class="error-message" id="edit_fullname-error"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_rfid_number" class="form-label"><b>RFID Number: </b></label>
                                    <input name="rfid_number" type="text" id="edit_rfid_number" class="form-control" autocomplete="off">
                                    <div class="error-message" id="edit_rfid_number-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-primary" id="btn-editinstructor">Update</button>
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

    <!-- Instructor CRUD JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#instructorTable').DataTable({ 
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: 2 } // Disable sorting on action column
            ]
        });
        
        // Helper function to reset form
        function resetForm() {
            $('.error-message').text('');
            $('.alert').addClass('d-none').text('');
            $('.form-control').removeClass('is-invalid');
            $('#instructorForm')[0].reset();
        }

        // Reset form when modal closes
        $('#instructorModal').on('hidden.bs.modal', resetForm);

        // ==============
        // CREATE (ADD INSTRUCTOR)
        // ==============
        $(document).on('click', '#btn-instructor', function() {
            const fullname = $('#fullname').val().trim();
            const rfid_number = $('#rfid_number').val().trim();
            const $btn = $(this);
            
            // Reset previous errors
            $('.error-message').text('');
            $('.form-control').removeClass('is-invalid');
            $('#form-error').addClass('d-none');
            
            // Validate inputs
            let isValid = true;
            
            if (!fullname) {
                $('#fullname').addClass('is-invalid');
                $('#fullname-error').text('Full name is required');
                isValid = false;
            }
            
            if (rfid_number && !/^[0-9A-F]{8,14}$/i.test(rfid_number)) {
                $('#rfid_number').addClass('is-invalid');
                $('#rfid_number-error').text('Invalid RFID format. Use 8-14 hex characters');
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
                url: "transac.php?action=add_instructor",
                data: { 
                    fullname: fullname,
                    rfid_number: rfid_number
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
                            resetForm();
                            $('#instructorModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        // Show specific error messages
                        if (response.message.includes('RFID')) {
                            $('#rfid_number').addClass('is-invalid');
                            $('#rfid_number-error').text(response.message);
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

            // Populate modal with instructor data
            $('#edit_instructorid').val(id);
            $('#edit_fullname').val($row.find('.fullname').text());
            $('#edit_rfid_number').val($row.find('.rfid_number').text());

            $('#editInstructorModal').modal('show');
        });

        // ==========
// UPDATE (Edit Instructor)
// ==========
$(document).on('click', '#btn-editinstructor', function() {
    var $btn = $(this);
    var id = $('#edit_instructorid').val();
    var fullname = $('#edit_fullname').val().trim();
    var rfid_number = $('#edit_rfid_number').val().trim();
    
    // Reset previous errors
    $('.error-message').text('');
    $('.form-control').removeClass('is-invalid');
    $('#edit-form-error').addClass('d-none').text('');
    
    // Validate inputs
    let isValid = true;
    if (!fullname) {
        $('#edit_fullname').addClass('is-invalid');
        $('#edit_fullname-error').text('Full name is required');
        isValid = false;
    }
    
    if (rfid_number && !/^[0-9A-F]{8,14}$/i.test(rfid_number)) {
        $('#edit_rfid_number').addClass('is-invalid');
        $('#edit_rfid_number-error').text('Invalid RFID format. Use 8-14 hex characters');
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
        url: "edit1.php?edit=instructor",
        data: { 
            id: id,
            fullname: fullname,
            rfid_number: rfid_number
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
                    $('#editInstructorModal').modal('hide');
                    location.reload();
                });
            } else {
                if (response.message.includes('RFID')) {
                    $('#edit_rfid_number').addClass('is-invalid');
                    $('#edit_rfid_number-error').text(response.message);
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
    var fullname = $row.find('.fullname').text();
    var $btn = $(this);
    
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete instructor: ${fullname}`,
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
                    type: 'delete_instructor', 
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
                            var table = $('#instructorTable').DataTable();
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