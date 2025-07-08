<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <!-- Add SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Additional CSS for better button styling */
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
        /* Your existing styles */
        .terms-link {
            padding-left: 65%;
            font-size: 12px;
            color: gray;
            text-decoration: none;
            cursor: pointer;
        }
        .terms-link:hover {
            text-decoration: underline;
            color: black;
        }
        #lockout-message {
            display: none;
            margin-top: 15px;
        }
        .login-container {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <div class="col-sm-12 col-xl-12">
                        <div class="bg-light rounded h-100 p-4">
                            <div class="row">
                                <div class="col-9">
                                    <h6 class="mb-4">Manage Rooms</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#roomModal">Add Room</button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Department</th>
                                            <th scope="col">Authorized Role</th>
                                            <th scope="col">Room</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Password</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        include '../connection.php';  
                                        $results = mysqli_query($db, "SELECT * FROM rooms order by id"); 
                                        while ($row = mysqli_fetch_array($results)) { 
                                        ?>
                                        <tr class="table-<?php echo $row['id'];?>" data-room-id="<?php echo $row['id'];?>">
                                            <td class="department"><?php echo $row['department']; ?></td>
                                            <td><?php echo $row['authorized_personnel']; ?></td>
                                            <td><?php echo $row['room']; ?></td>
                                            <td><?php echo $row['descr']; ?></td>
                                            <td><?php echo substr($row['password'], 0, 10) . '...'; ?></td>
                                            <td width="14%">
                                                <center>
                                                    <button authrole="<?php echo $row['authorized_personnel'];?>" 
                                                            descr="<?php echo $row['descr'];?>" 
                                                            pass="<?php echo $row['password'];?>" 
                                                            room="<?php echo $row['room'];?>" 
                                                            department="<?php echo $row['department'];?>" 
                                                            data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-outline-primary btn-sm btn-edit e_room_id">
                                                        <i class="bi bi-plus-edit"></i> Edit 
                                                    </button>
                                                    <button authrole="<?php echo $row['authorized_personnel'];?>" 
                                                            descr="<?php echo $row['descr'];?>" 
                                                            pass="<?php echo $row['password'];?>" 
                                                            room="<?php echo $row['room'];?>" 
                                                            department="<?php echo $row['department'];?>"  
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-outline-danger btn-sm btn-del d_room_id">
                                                        <i class="bi bi-plus-trash"></i> Delete 
                                                    </button>
                                                    <input type="hidden" id="dpt" value="<?php echo $row['department'];?>"/>
                                                    <input type="hidden" id="role" value="<?php echo $row['authorized_personnel'];?>"/>
                                                    <input type="hidden" id="desc" value="<?php echo $row['descr'];?>"/>
                                                    <input type="hidden" id="pass" value="<?php echo $row['password'];?>"/>
                                                    <input type="hidden" id="name" value="<?php echo $row['room'];?>"/>
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

            <!-- Add Room Modal -->
            <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-plus-circle"></i> New Room</h5>
                            <button type="button" onclick="resetForm()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="myForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-dept"></div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department: </b></label>
                                        <select class="form-control" name="roomdpt" id="roomdpt" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM department";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Authorized Role: </b></label>
                                        <select class="form-control" name="roomrole" id="roomrole" autocomplete="off">
                                            <?php
                                            $sql = "SELECT * FROM role";
                                            $result = $db->query($sql);
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Room:</b></label>
                                        <input name="roomname" type="text" id="roomname" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Description:</b></label>
                                        <input name="roomdesc" type="text" id="roomdesc" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomdesc-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Password:</b></label>
                                        <input name="roompass" type="password" id="roompass" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roompass-error"></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" id="remember" onclick="togglePasswordVisibility('roompass')" class="form-check-input">
                                        <label class="form-check-label" for="remember">Show Password</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="resetForm()" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-room">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editdepartment-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Room</h5>
                            <button onclick="resetForm()" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="col-lg-12 mt-1" id="mgs-editdept"></div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputTime"><b>Department: </b></label>
                                    <select class="form-control" name="eroomdpt" id="eroomdpt" autocomplete="off">
                                        <option class="edit-department"></option>
                                        <?php
                                        $sql = "SELECT * FROM department";
                                        $result = $db->query($sql);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['department_name']}'>{$row['department_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputTime"><b>Authorized Role: </b></label>
                                    <select class="form-control" name="eroomrole" id="eroomrole" autocomplete="off">
                                        <option class="edit-role"></option>
                                        <?php
                                        $sql = "SELECT * FROM role";
                                        $result = $db->query($sql);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['role']}'>{$row['role']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-12 mb-1">
                                <div class="form-group">
                                    <label for="inputTime"><b>Room:</b></label>
                                    <input name="eroomname" type="text" id="eroomname" class="form-control edit-name" autocomplete="off">
                                    <span class="error-message" id="eroomname-error"></span>
                                </div>
                            </div>
                            <div class="col-lg-12 mb-1">
                                <div class="form-group">
                                    <label for="inputTime"><b>Description:</b></label>
                                    <input name="eroomdesc" type="text" id="eroomdesc" class="form-control edit-desc" autocomplete="off">
                                    <span class="error-message" id="eroomdesc-error"></span>
                                </div>
                            </div>
                            <div class="col-lg-12 mb-1">
                                <div class="form-group">
                                    <label for="inputTime"><b>Password:</b></label>
                                    <input name="eroompass" type="password" id="eroompass" class="form-control edit-pass" autocomplete="off">
                                    <span class="error-message" id="eroompass-error"></span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="form-check">
                                    <input type="checkbox" id="remember" onclick="togglePasswordVisibility('eroompass')" class="form-check-input">
                                    <label class="form-check-label" for="remember">Show Password</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" name="" id="edit_departmentid">
                            <button onclick="resetForm()" type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-outline-primary" id="btn-editdepartment">Update</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'footer.php'; ?>
        </div>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
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

    <!-- Custom JavaScript -->
   <script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#myDataTable').DataTable({ order: [[0, 'desc']] });

        // Helper: Reset form
        window.resetForm = function() {
            $('.error-message').text('');
            $('#myForm')[0].reset();
        }

        // ==============
        // CREATE (ADD)
        // ==============
        $('#btn-room').click(function() {
            $('.error-message').text('');
            var roomdpt = $('#roomdpt').val();
            var roomrole = $('#roomrole').val();
            var roomname = $('#roomname').val().trim();
            var roomdesc = $('#roomdesc').val().trim();
            var roompass = $('#roompass').val().trim();
            var isValid = true;

            if (!roomname) { $('#roomname-error').text('Room name is required'); isValid = false; }
            if (!roomdesc) { $('#roomdesc-error').text('Description is required'); isValid = false; }
            if (!roompass) { $('#roompass-error').text('Password is required'); isValid = false; }
            else if (roompass.length < 6) { $('#roompass-error').text('Password must be at least 6 characters'); isValid = false; }
            if (!isValid) return;

            var $btn = $(this), originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_room",
                data: { roomdpt, roomrole, roomname, roomdesc, roompass },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Success', text: response.message, showConfirmButton: true })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, showConfirmButton: true });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred: ' + error, showConfirmButton: true });
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // ==========
        // READ (EDIT)
        // ==========
        $('.e_room_id').click(function() {
            var id = $(this).attr('data-id');
            var department = $(this).attr('department');
            var role = $(this).attr('authrole');
            var room = $(this).attr('room');
            var descr = $(this).attr('descr');
            var pass = $(this).attr('pass');

            $('#editdepartment-modal').modal('show');
            $('#edit_departmentid').val(id);
            $('#eroomdpt').val(department);
            $('#eroomrole').val(role);
            $('#eroomname').val(room);
            $('#eroomdesc').val(descr);
            $('#eroompass').val(pass);
        });

        // ==========
        // UPDATE
        // ==========
        $('#btn-editdepartment').click(function(e) {
            e.preventDefault();
            var id = $('#edit_departmentid').val();
            var roomdpt = $('#eroomdpt').val();
            var roomrole = $('#eroomrole').val();
            var roomname = $('#eroomname').val().trim();
            var roomdesc = $('#eroomdesc').val().trim();
            var roompass = $('#eroompass').val().trim();

            // Validate
            var isValid = true;
            if (!roomname) { $('#eroomname-error').text('This field is required.'); isValid = false; }
            else { $('#eroomname-error').text(''); }
            if (!roomdesc) { $('#eroomdesc-error').text('This field is required.'); isValid = false; }
            else { $('#eroomdesc-error').text(''); }
            if (!roompass) { $('#eroompass-error').text('This field is required.'); isValid = false; }
            else { $('#eroompass-error').text(''); }
            if (!isValid) return;

            var $btn = $(this), originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');

            $.ajax({
                type: "POST",
                url: "edit1.php?edit=room&id=" + id,
                data: { roomdpt, roomrole, roomname, roomdesc, roompass },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Successfully Updated.', showConfirmButton: true })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: response.message, showConfirmButton: true });
                    }
                },
                error: function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing your request', showConfirmButton: true });
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

       // ==========
// DELETE ROOM - WORKING VERSION
// ==========
$(document).on('click', '.btn-del', function() {
    const $button = $(this);
    const id = $button.data('id');
    const roomName = $button.attr('room');
    const $row = $button.closest('tr');
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Delete Room?',
        text: `Are you sure you want to delete "${roomName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state immediately
            $button.html('<span class="spinner-border spinner-border-sm"></span>');
            $button.prop('disabled', true);
            
            // Make AJAX request
            $.ajax({
                type: 'POST',
                url: 'del.php',
                data: { 
                    type: 'room', 
                    id: id 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Remove row from DataTable
                        $('#myDataTable').DataTable().row($row).remove().draw();
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to delete room'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting the room'
                    });
                },
                complete: function() {
                    // Restore button state
                    $button.html('<i class="bi bi-plus-trash"></i> Delete');
                    $button.prop('disabled', false);
                }
            });
        }
    });
});
    });
    // Add this to your JavaScript section
function fetchInstructorRFIDs() {
    $.ajax({
        url: 'get_rfids.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Populate your RFID input field with autocomplete
                $("#rfid_number").autocomplete({
                    source: response.data,
                    minLength: 3
                });
            }
        }
    });
}

// Call this when the page loads
$(document).ready(function() {
    fetchInstructorRFIDs();
});

</script>
</body>
</html>