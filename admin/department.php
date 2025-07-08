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
                                    <h6 class="mb-4">Manage Department</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#departmentModal">Add Department</button>
                                </div>
                            </div>
                            <hr></hr>
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
                                        <?php include '../connection.php'; ?>
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
                                                        <i class="bi bi-plus-edit"></i> Edit 
                                                    </button>
                                                    <button id="deldpt" 
                                                            department_name="<?php echo $row['department_name'];?>" 
                                                            department_desc="<?php echo $row['department_desc'];?>"  
                                                            data-id="<?php echo $row['department_id']; ?>" 
                                                            class="btn btn-outline-danger btn-sm btn-del d_department_id">
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

            <!-- Add Department Modal -->
            <div class="modal fade" id="departmentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-plus-circle"></i> New Department</h5>
                            <button type="button" onclick="resetForm()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="myForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-dept"></div>
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
                                <button type="button" onclick="resetForm()" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-department">Save</button>
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
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Department</h5>
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
                            <h5 class="modal-title"><i class="bi bi-trash"></i> Delete Department</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
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

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
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
    <script src="js/main.js"></script>

    <!-- ====================== -->
    <!-- CRUD FUNCTIONALITY JS -->
    <!-- ====================== -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#myDataTable').DataTable({ order: [[0, 'desc']] });

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
        window.resetForm = function() {
            document.getElementById('deptname-error').innerHTML = '';
            document.getElementById('deptname-desc').innerHTML = '';
            document.getElementById('myForm').reset();
        }

        // ==============
        // CREATE (ADD)
        // ==============
        $('#btn-department').click(function() {
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
            $(this).html('<span class="spinner-border spinner-border-sm"></span>');
            $(this).prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_department",
                data: { dptname: dptname, dptdesc: dptdesc },
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
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                    $('#btn-department').html('Save');
                    $('#btn-department').prop('disabled', false);
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'An error occurred while processing your request', 'error');
                    $('#btn-department').html('Save');
                    $('#btn-department').prop('disabled', false);
                }
            });
        });

        // ==========
        // READ (EDIT)
        // ==========
        $('.e_department_id').click(function() {
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
            $(this).html('<span class="spinner-border spinner-border-sm"></span>');
            $(this).prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "edit1.php?edit=department&id=" + id,
                data: { dptname: dptname, dptdesc: dptdesc },
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
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                    $('#btn-editdepartment').html('Update');
                    $('#btn-editdepartment').prop('disabled', false);
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'An error occurred while processing your request', 'error');
                    $('#btn-editdepartment').html('Update');
                    $('#btn-editdepartment').prop('disabled', false);
                }
            });
        });

        // ==========
        // DELETE
        // ==========
        $('.d_department_id').click(function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).attr('department_name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete department: ${name}`,
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
                        data: { type: 'department', id: id },
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