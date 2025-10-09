<?php
session_start();
// Include connection and functions
include '../connection.php';
include 'functions.php';

// Validate session
validateSession();

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
    </style>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <!-- Sidebar Start -->
        <?php include 'sidebar.php'; ?>
        <!-- Sidebar End -->
        
        <!-- Backdrop for Mobile Sidebar -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>

            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Manage Department</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#departmentModal">
                                    <i class="fas fa-plus-circle"></i> Add Department
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Department Name</th>
                                        <th scope="col">Department Description</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $results = mysqli_query($db, "SELECT * FROM department"); ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['department_id'];?>">
                                        <td><?php echo $row['department_name']; ?></td>
                                        <td><?php echo $row['department_desc']; ?></td>
                                        <td width="14%">
                                            <center>
                                                <button department_name="<?php echo $row['department_name'];?>" 
                                                        department_desc="<?php echo $row['department_desc'];?>" 
                                                        data-id="<?php echo $row['department_id'];?>" 
                                                        class="btn btn-outline-primary btn-sm btn-edit e_department_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button department_name="<?php echo $row['department_name'];?>" 
                                                        department_desc="<?php echo $row['department_desc'];?>"  
                                                        data-id="<?php echo $row['department_id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_department_id">
                                                    <i class="fas fa-trash"></i> Delete 
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

                <!-- Add Department Modal -->
                <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">
                                    <i class="fas fa-plus-circle"></i> New Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="col-lg-11 mb-2 mt-1" id="mgs-dept" style="margin-left: 4%"></div>
                            <form id="departmentForm">
                                <div class="modal-body">
                                    <div class="col-lg-12 mb-1">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Name:</b></label>
                                            <input name="department_name" type="text" id="department_name" class="form-control" autocomplete="off">
                                            <span class="deptname-error" id="deptname-error" style="color:red;font-size:10px;"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Description: </b></label>
                                            <textarea name="department_desc" type="text" id="department_description" class="form-control" autocomplete="off"></textarea>
                                            <span class="deptname-desc" id="deptname-desc" style="color:red;font-size:10px;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-outline-warning" id="btn-department">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Edit Department Modal -->
                <div class="modal fade" id="editdepartment-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit"></i> Edit Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editdept"></div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department Name:</b></label>
                                        <input name="department_name" type="text" id="edit_departmentname" class="form-control edit-name" autocomplete="off">
                                        <span class="deptname-error" id="edeptname-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department Description: </b></label>
                                        <textarea name="department_desc" type="text" id="edit_departmentdescription" class="form-control edit-desc" autocomplete="off"></textarea>
                                        <span class="deptname-error" id="edeptname-desc" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="" id="edit_departmentid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-outline-primary" id="btn-editdepartment">Update</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Department Modal -->
                <div class="modal fade" id="deldepartment-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-trash"></i> Delete Department
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" id="delete-form">
                                <div class="modal-body">
                                    <div class="col-lg-12 mt-1" id="mgs-deldept"></div>
                                    <div class="col-lg-12 mb-1">
                                        <div class="form-group">
                                            <label for="inputTime"><b>Department Name:</b></label>
                                            <input type="text" id="delete_departmentname" class="form-control d-dpt" autocomplete="off" readonly="">
                                            <span class="deptname-error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="" id="delete_departmentid">
                                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">No</button>
                                    <button type="button" class="btn btn-outline-primary remove_id" id="btn-deldepartment">Yes</button>
                                </div>
                            </form>
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
            order: [[0, 'desc']],
            stateSave: true
        });

        // Helper function to validate inputs
        function validateInput(input, errorId, message) {
            if (input.value === '') {
                document.getElementById(errorId).innerHTML = message;
                input.focus();
                return false;
            } else {
                document.getElementById(errorId).innerHTML = '';
                return true;
            }
        }

        // Helper function to reset form
        function resetForm() {
            document.getElementById('deptname-error').innerHTML = '';
            document.getElementById('deptname-desc').innerHTML = '';
            document.getElementById('departmentForm').reset();
        }

        // ==============
        // CREATE (ADD)
        // ==============
        $('#departmentForm').submit(function(e) {
            e.preventDefault();
            
            var inputField = document.getElementById('department_name');
            var inputField1 = document.getElementById('department_description');

            // Validate inputs
            if (!validateInput(inputField, 'deptname-error', 'Department name is required') || 
                !validateInput(inputField1, 'deptname-desc', 'Description is required')) {
                return;
            }

            var dptname = $('#department_name').val();
            var dptdesc = $('#department_description').val();
            
            // Show loading state
            $('#btn-department').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-department').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_department",
                data: { dptname: dptname, dptdesc: dptdesc },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-department').html('Save');
                    $('#btn-department').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            $('#departmentModal').modal('hide');
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
                    $('#btn-department').html('Save');
                    $('#btn-department').prop('disabled', false);
                    
                    console.log('XHR Response:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while processing your request'
                    });
                }
            });
        });

        // ==========
        // READ (EDIT)
        // ==========
        $(document).on('click', '.e_department_id', function() {
            var id = $(this).data('id');
            var name = $(this).attr('department_name');
            var desc = $(this).attr('department_desc');
            
            $('#edit_departmentname').val(name);
            $('#edit_departmentdescription').val(desc);
            $('#edit_departmentid').val(id);
            $('#editdepartment-modal').modal('show');
        });

        // ==========
        // UPDATE
        // ==========
        $('#btn-editdepartment').click(function(e) {
            e.preventDefault();
            var inputField = document.getElementById('edit_departmentname');
            var inputField1 = document.getElementById('edit_departmentdescription');

            // Validate inputs
            if (!validateInput(inputField, 'edeptname-error', 'Department name is required') || 
                !validateInput(inputField1, 'edeptname-desc', 'Description is required')) {
                return;
            }

            var id = $('#edit_departmentid').val();
            var dptname = $('#edit_departmentname').val();
            var dptdesc = $('#edit_departmentdescription').val();
            
            // Show loading state
            $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $(this).prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_department",
                data: { id: id, dptname: dptname, dptdesc: dptdesc },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editdepartment').html('Update');
                    $('#btn-editdepartment').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            $('#editdepartment-modal').modal('hide');
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
                    $('#btn-editdepartment').html('Update');
                    $('#btn-editdepartment').prop('disabled', false);
                    
                    console.log('XHR Response:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while processing your request'
                    });
                }
            });
        });

        // ==========
        // DELETE
        // ==========
        $(document).on('click', '.d_department_id', function() {
            var id = $(this).data('id');
            var name = $(this).attr('department_name');
            
            $('#delete_departmentname').val(name);
            $('#delete_departmentid').val(id);
            $('#deldepartment-modal').modal('show');
        });

        // Handle the actual deletion when "Yes" is clicked in the modal
        $(document).on('click', '#btn-deldepartment', function() {
            var id = $('#delete_departmentid').val();
            
            // Show loading indicator
            $('#btn-deldepartment').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
            $('#btn-deldepartment').prop('disabled', true);
            
            $.ajax({
                type: 'POST',
                url: 'transac.php?action=delete_department',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-deldepartment').html('Yes');
                    $('#btn-deldepartment').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        // Close the modal
                        $('#deldepartment-modal').modal('hide');
                        
                        // Remove the row from the table
                        dataTable.row($('.table-' + id)).remove().draw();
                        
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
                    $('#btn-deldepartment').html('Yes');
                    $('#btn-deldepartment').prop('disabled', false);
                    
                    console.log('XHR Response:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred: ' + error,
                        icon: 'error'
                    });
                }
            });
        });

        // Reset modal when closed
        $('#departmentModal').on('hidden.bs.modal', function () {
            resetForm();
        });
    });
    </script>
</body>
</html>