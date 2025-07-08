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
                                    <h6 class="mb-4">Manage Visitor Cards</h6>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#visitorModal">Add Visitor Card</button>
                                </div>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="text-align:left;">RFID Number</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php include '../connection.php'; ?>
                                        <?php $results = mysqli_query($db, "SELECT * FROM visitor"); ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { ?>
                                        <tr class="table-<?php echo $row['id'];?>">
                                            <td style="text-align:left;" class="rfid_number"><?php echo $row['rfid_number']; ?></td>
                                            <td width="14%">
                                                <center>
                                                    <button rfid="<?php echo $row['rfid_number'];?>" 
                                                            data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-outline-primary btn-sm btn-edit e_visitor_id">
                                                        <i class="bi bi-plus-edit"></i> Edit 
                                                    </button>
                                                    <button rfid="<?php echo $row['rfid_number'];?>" 
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-outline-danger btn-sm btn-del d_visitor_id">
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

            <!-- Add Visitor Modal -->
            <div class="modal fade" id="visitorModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-plus-circle"></i> New Visitor Card</h5>
                            <button type="button" onclick="resetForm()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="visitorForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-visitor"></div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>RFID Number: </b></label>
                                        <input name="rfid_number" type="text" id="rfid_number" class="form-control" 
                                               autocomplete="off" minlength="10" maxlength="10" 
                                               title="Enter exactly 10 digits" required>
                                        <span class="visitor-error" id="visitor-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="resetForm()" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-warning" id="btn-visitor">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Visitor Modal -->
            <div class="modal fade" id="editVisitorModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Visitor Card</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editVisitorForm">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-editvisitor"></div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="inputTime"><b>RFID Number: </b></label>
                                        <input minlength="10" maxlength="10" name="rfid_number" type="text" 
                                               id="erfid_number" class="form-control e-rfid" autocomplete="off">
                                        <span class="evisitor-error" id="evisitor-error" style="color:red;font-size:10px;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="id" id="edit_visitorid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-outline-primary" id="btn-editvisitor">Update</button>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Visitor CRUD JavaScript -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#myDataTable').DataTable({ order: [[0, 'desc']] });

        // Restrict RFID input to numbers only
        document.getElementById('erfid_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        document.getElementById('rfid_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Helper function to reset form
        function resetForm() {
            document.getElementById('visitor-error').innerHTML = '';
            document.getElementById('evisitor-error').innerHTML = '';
            document.getElementById('visitorForm').reset();
        }

       
       // ==============
// CREATE (ADD VISITOR CARD)
// ==============
$('#btn-visitor').click(function() {
    // Get and trim input value
    var rfid_number = $('#rfid_number').val().trim();
    var $btn = $(this);
    var $errorField = $('#visitor-error');
    
    // Reset previous errors
    $errorField.text('');
    
    // Validate input
    if (!rfid_number) {
        $errorField.text('RFID number is required');
        $('#rfid_number').focus();
        return;
    }
    
    if (rfid_number.length !== 10 || !/^\d+$/.test(rfid_number)) {
        $errorField.text('RFID must be exactly 10 digits');
        $('#rfid_number').focus();
        return;
    }
    
    // Show loading state
    $btn.html('<span class="spinner-border spinner-border-sm"></span> Saving...');
    $btn.prop('disabled', true);
    
    // Make AJAX request
    $.ajax({
        type: "POST",
        url: "transac.php?action=add_visitor",
        data: { rfid_number: rfid_number },
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
                    $('#visitorForm')[0].reset();
                    $('#visitorModal').modal('hide');
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
$(document).on('click', '.e_visitor_id', function() {
    var id = $(this).data('id');
    var rfid = $(this).attr('rfid');
    
    $('#erfid_number').val(rfid);
    $('#edit_visitorid').val(id);
    $('#editVisitorModal').modal('show');
});

// ==========
// UPDATE
// ==========
$('#btn-editvisitor').click(function() {
    var inputField = document.getElementById('erfid_number');
    
    // Validate input
    if (inputField.value === '') {
        document.getElementById('evisitor-error').innerHTML = 'RFID number is required';
        inputField.focus();
        return;
    } else if (inputField.value.length !== 10) {
        document.getElementById('evisitor-error').innerHTML = 'RFID must be exactly 10 digits';
        inputField.focus();
        return;
    } else {
        document.getElementById('evisitor-error').innerHTML = '';
    }

    var id = $('#edit_visitorid').val();
    var rfid_number = $('#erfid_number').val();
    
    // Show loading state
    $(this).html('<span class="spinner-border spinner-border-sm"></span>');
    $(this).prop('disabled', true);

    $.ajax({
        type: "POST",
        url: "edit1.php?edit=visitor&id=" + id,
        data: { rfid_number: rfid_number },
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
                    $('.table-' + id + ' .rfid_number').text(rfid_number);
                    $('.table-' + id + ' .btn-edit').attr('rfid', rfid_number);
                    $('#editVisitorModal').modal('hide');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message
                });
            }
            $('#btn-editvisitor').html('Update');
            $('#btn-editvisitor').prop('disabled', false);
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while processing your request'
            });
            $('#btn-editvisitor').html('Update');
            $('#btn-editvisitor').prop('disabled', false);
        }
    });
});

        // ==========
        // DELETE
        // ==========
        $(document).on('click', '.d_visitor_id', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var rfid = $(this).attr('rfid');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete visitor card: ${rfid}`,
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
                        data: {
                            type: 'visitor',
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