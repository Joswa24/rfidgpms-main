<!DOCTYPE html>
<html lang="en">
<?php
include 'header.php';
include '../connection.php';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
                                <h6 class="mb-4">Manage Roles</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#roleModal">
                                    <i class="fas fa-plus-circle"></i> Add Role
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Role</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $results = mysqli_query($db, "SELECT * FROM role"); ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['id'];?>" data-role-id="<?php echo $row['id'];?>">
                                        <td><?php echo $row['role']; ?></td>
                                        <td width="14%">
                                            <center>
                                                <button role="<?php echo $row['role'];?>" 
                                                        data-id="<?php echo $row['id'];?>" 
                                                        class="btn btn-outline-primary btn-sm btn-edit e_role_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button role="<?php echo $row['role'];?>"  
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_role_id">
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
            </div>

            <!-- Add Role Modal -->
            <div class="modal fade" id="roleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Role
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="roleForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-role"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Role:</b></label>
                                        <input name="role" type="text" id="role" class="form-control" autocomplete="off">
                                        <span class="error-message" id="role-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-outline-warning" id="btn-role">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Role Modal -->
            <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Role
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editRoleForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editrole"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Role:</b></label>
                                        <input name="erole" type="text" id="erole" class="form-control" autocomplete="off">
                                        <span class="error-message" id="erole-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="role_id" id="edit_roleid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-outline-primary" id="btn-editrole">Update</button>
                            </div>
                        </form>
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
    $(document).ready(function() {
    // Initialize DataTable
    var dataTable = $('#myDataTable').DataTable({
        order: [[0, 'desc']],
        stateSave: true
    });

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#roleForm')[0].reset();
    }

    // Validation function
    function validateRole(role) {
        let isValid = true;
        const errors = {};

        if (!role) { 
            errors.role = 'Role name is required';
            isValid = false; 
        } else if (role.length > 100) {
            errors.role = 'Role name must be less than 100 characters';
            isValid = false;
        }

        return { isValid, errors };
    }

    // Display validation errors
    function displayErrors(errors, prefix = '') {
        $('.error-message').text('');
        Object.keys(errors).forEach(key => {
            $(`#${prefix}${key}-error`).text(errors[key]);
        });
    }

    // ==============
    // CREATE (ADD ROLE)
    // ==============
    $('#roleForm').submit(function(e) {
        e.preventDefault();
        
        const role = $('#role').val().trim();

        // Validation
        const validation = validateRole(role);
        if (!validation.isValid) {
            displayErrors(validation.errors);
            return;
        }

        // Show loading state
        $('#btn-role').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-role').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_role",
            data: { role: role },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-role').html('Save');
                $('#btn-role').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#roleModal').modal('hide');
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
                $('#btn-role').html('Save');
                $('#btn-role').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                // Try to parse error response
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // If not JSON, use default message
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
    // READ (EDIT ROLE)
    // ==========
    $(document).on('click', '.e_role_id', function() {
        const id = $(this).data('id');
        const role = $(this).attr('role');

        // Populate edit form
        $('#edit_roleid').val(id);
        $('#erole').val(role);
        
        // Clear previous errors
        $('.error-message').text('');
        
        // Show modal
        $('#editRoleModal').modal('show');
    });

    // ==========
    // UPDATE ROLE
    // ==========
    $('#editRoleForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_roleid').val();
        const role = $('#erole').val().trim();

        // Validation
        const validation = validateRole(role);
        if (!validation.isValid) {
            displayErrors(validation.errors, 'e');
            return;
        }

        // Show loading state
        $('#btn-editrole').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editrole').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_role",
            data: { 
                id: id,
                role: role
            },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editrole').html('Update');
                $('#btn-editrole').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editRoleModal').modal('hide');
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
                $('#btn-editrole').html('Update');
                $('#btn-editrole').prop('disabled', false);
                
                console.log('XHR Response:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                
                // Try to parse error response
                let errorMessage = 'An error occurred while processing your request';
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                } catch (e) {
                    // If not JSON, use default message
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
    // DELETE ROLE
    // ==========
    // ==========
// DELETE ROLE
// ==========
$(document).on('click', '.d_role_id', function() {
    const $button = $(this);
    const id = $button.data('id');
    const roleName = $button.attr('role');
    
    Swal.fire({
        title: 'Delete Role?',
        text: `Are you sure you want to delete "${roleName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            $button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $button.prop('disabled', true);
            
            $.ajax({
                type: 'POST',
                url: 'transac.php?action=delete_role',
                data: { 
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Remove row from DataTable
                        dataTable.row($button.closest('tr')).remove().draw();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cannot Delete Role',
                            text: response.message || 'Failed to delete role',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('XHR Response:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    let errorMessage = 'An error occurred while deleting the role';
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        // If not JSON, show the raw response
                        errorMessage = xhr.responseText || errorMessage;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: `<div style="text-align: left;">
                               <strong>Error Details:</strong><br>
                               <small>${errorMessage}</small>
                               </div>`,
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    // Restore button state
                    $button.html('<i class="fas fa-trash"></i> Delete');
                    $button.prop('disabled', false);
                }
            });
        }
    });
});

    // Reset modal when closed
    $('#roleModal').on('hidden.bs.modal', function() {
        resetForm();
    });
    
    $('#editRoleModal').on('hidden.bs.modal', function() {
        $('.error-message').text('');
    });
});
    </script>
</body>
</html>