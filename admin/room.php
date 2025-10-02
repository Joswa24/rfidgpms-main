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
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<?php include '../connection.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms</title>
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
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
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
                                <h6 class="mb-4">Manage Rooms</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#roomModal">
                                    <i class="fas fa-plus-circle"></i> Add Room
                                </button>
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
                                    $results = mysqli_query($db, "SELECT * FROM rooms ORDER BY id"); 
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
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button authrole="<?php echo $row['authorized_personnel'];?>" 
                                                        descr="<?php echo $row['descr'];?>" 
                                                        pass="<?php echo $row['password'];?>" 
                                                        room="<?php echo $row['room'];?>" 
                                                        department="<?php echo $row['department'];?>"  
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_room_id">
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

            <!-- Add Room Modal -->
            <div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Room
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="roomForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-room"></div>
                                <div class="col-lg-12 mb-3">
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

                                <div class="col-lg-12 mb-3">
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
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Room Name:</b></label>
                                        <input name="roomname" type="text" id="roomname" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Description:</b></label>
                                        <input name="roomdesc" type="text" id="roomdesc" class="form-control" autocomplete="off">
                                        <span class="error-message" id="roomdesc-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group password-field">
                                        <label for="inputTime"><b>Password:</b></label>
                                        <input name="roompass" type="password" id="roompass" class="form-control" autocomplete="off">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('roompass')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <span class="error-message" id="roompass-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-outline-warning" id="btn-room">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Room Modal -->
            <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Room
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editRoomForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editroom"></div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Department: </b></label>
                                        <select class="form-control" name="eroomdpt" id="eroomdpt" autocomplete="off">
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

                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Authorized Role: </b></label>
                                        <select class="form-control" name="eroomrole" id="eroomrole" autocomplete="off">
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
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Room Name:</b></label>
                                        <input name="eroomname" type="text" id="eroomname" class="form-control" autocomplete="off">
                                        <span class="error-message" id="eroomname-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Description:</b></label>
                                        <input name="eroomdesc" type="text" id="eroomdesc" class="form-control" autocomplete="off">
                                        <span class="error-message" id="eroomdesc-error"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group password-field">
                                        <label for="inputTime"><b>Password:</b></label>
                                        <input name="eroompass" type="password" id="eroompass" class="form-control" autocomplete="off">
                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('eroompass')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <span class="error-message" id="eroompass-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="room_id" id="edit_roomid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-outline-primary" id="btn-editroom">Update</button>
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
        order: [[0, 'desc']],
        stateSave: true
    });

    // Password visibility toggle function
    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const toggle = input.parentNode.querySelector('.password-toggle i');
        
        if (input.type === 'password') {
            input.type = 'text';
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        }
    }

    // Reset form function
    function resetForm() {
        $('.error-message').text('');
        $('#roomForm')[0].reset();
        // Reset password visibility
        const eyeIcons = document.querySelectorAll('.password-toggle i');
        eyeIcons.forEach(icon => {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        });
    }

    // ==============
    // CREATE (ADD ROOM)
    // ==============
    $('#roomForm').submit(function(e) {
        e.preventDefault();
        
        $('.error-message').text('');
        const roomdpt = $('#roomdpt').val();
        const roomrole = $('#roomrole').val();
        const roomname = $('#roomname').val().trim();
        const roomdesc = $('#roomdesc').val().trim();
        const roompass = $('#roompass').val().trim();
        let isValid = true;

        // Validation
        if (!roomname) { 
            $('#roomname-error').text('Room name is required'); 
            isValid = false; 
        }
        if (!roomdesc) { 
            $('#roomdesc-error').text('Description is required'); 
            isValid = false; 
        }
        if (!roompass) { 
            $('#roompass-error').text('Password is required'); 
            isValid = false; 
        } else if (roompass.length < 6) { 
            $('#roompass-error').text('Password must be at least 6 characters'); 
            isValid = false; 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-room').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        $('#btn-room').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=add_room",
            data: { roomdpt, roomrole, roomname, roomdesc, roompass },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-room').html('Save');
                $('#btn-room').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#roomModal').modal('hide');
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
                $('#btn-room').html('Save');
                $('#btn-room').prop('disabled', false);
                
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
    // READ (EDIT ROOM)
    // ==========
    $(document).on('click', '.e_room_id', function() {
        const id = $(this).data('id');
        const department = $(this).attr('department');
        const role = $(this).attr('authrole');
        const room = $(this).attr('room');
        const descr = $(this).attr('descr');
        const pass = $(this).attr('pass');

        // Populate edit form
        $('#edit_roomid').val(id);
        $('#eroomdpt').val(department);
        $('#eroomrole').val(role);
        $('#eroomname').val(room);
        $('#eroomdesc').val(descr);
        $('#eroompass').val(pass);
        
        // Reset password visibility
        const eyeIcon = document.querySelector('#editRoomModal .password-toggle i');
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
        $('#eroompass').attr('type', 'password');
        
        // Show modal
        $('#editRoomModal').modal('show');
    });

    // ==========
    // UPDATE ROOM
    // ==========
    $('#editRoomForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_roomid').val();
        const roomdpt = $('#eroomdpt').val();
        const roomrole = $('#eroomrole').val();
        const roomname = $('#eroomname').val().trim();
        const roomdesc = $('#eroomdesc').val().trim();
        const roompass = $('#eroompass').val().trim();

        // Validation
        let isValid = true;
        if (!roomname) { 
            $('#eroomname-error').text('Room name is required'); 
            isValid = false; 
        } else { 
            $('#eroomname-error').text(''); 
        }
        if (!roomdesc) { 
            $('#eroomdesc-error').text('Description is required'); 
            isValid = false; 
        } else { 
            $('#eroomdesc-error').text(''); 
        }
        if (!roompass) { 
            $('#eroompass-error').text('Password is required'); 
            isValid = false; 
        } else if (roompass.length < 6) { 
            $('#eroompass-error').text('Password must be at least 6 characters'); 
            isValid = false; 
        } else { 
            $('#eroompass-error').text(''); 
        }
        
        if (!isValid) return;

        // Show loading state
        $('#btn-editroom').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#btn-editroom').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: "transac.php?action=update_room",
            data: { 
                id: id,
                roomdpt: roomdpt,
                roomrole: roomrole,
                roomname: roomname,
                roomdesc: roomdesc,
                roompass: roompass
            },
            dataType: 'json',
            success: function(response) {
                // Reset button state
                $('#btn-editroom').html('Update');
                $('#btn-editroom').prop('disabled', false);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#editRoomModal').modal('hide');
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
                $('#btn-editroom').html('Update');
                $('#btn-editroom').prop('disabled', false);
                
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
    // DELETE ROOM
    // ==========
    $(document).on('click', '.d_room_id', function() {
        const $button = $(this);
        const id = $button.data('id');
        const roomName = $button.attr('room');
        
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
                // Show loading state
                $button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                $button.prop('disabled', true);
                
                $.ajax({
                    type: 'POST',
                    url: 'transac.php?action=delete_room',
                    data: { id: id },
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
                                title: 'Error!',
                                text: response.message || 'Failed to delete room'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the room'
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
    $('#roomModal').on('hidden.bs.modal', function() {
        resetForm();
    });
    
    $('#editRoomModal').on('hidden.bs.modal', function() {
        $('.error-message').text('');
    });
});
    </script>
</body>
</html>