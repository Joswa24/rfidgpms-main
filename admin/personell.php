
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

// Function to format ID with hyphen
function formatID($id) {
    if (strlen($id) == 8 && is_numeric($id)) {
        return substr($id, 0, 4) . '-' . substr($id, 4, 4);
    }
    return $id;
}

// Function to remove hyphen from ID
function cleanID($id) {
    return str_replace('-', '', $id);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>
<?php include '../connection.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Personnel</title>
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
        .upload-img-btn img {
            border-radius: 5px;
            border: 2px dashed #dee2e6;
        }
        .upload-img-btn:hover img {
            border-color: #4e73df;
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
        
        <!-- Content Start -->
        <div class="content">
            <?php include 'navbar.php'; ?>
            
            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Manage Personnel</h6>
                            </div>
                            <div class="col-3">
                                <button type="button" class="btn btn-outline-warning m-2" data-bs-toggle="modal" data-bs-target="#employeeModal">
                                    <i class="fas fa-plus-circle"></i> Add Personnel
                                </button>
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-border" id="myDataTable">
                                <thead>
                                    <tr>
                                        <th scope="col">Photo</th>
                                        <th scope="col">ID Number</th>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Department</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                        <th style="display: none;">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $results = mysqli_query($db, "SELECT * FROM personell ORDER BY date_added DESC"); ?>
                                    <?php while ($row = mysqli_fetch_array($results)) { ?>
                                    <tr class="table-<?php echo $row['id'];?>">
                                        <input class="id_number" type="hidden" value="<?php echo $row['id_no']; ?>" />
                                        <input class="rfid_raw" type="hidden" value="<?php echo $row['rfid_number']; ?>" />
                                        <input class="role" type="hidden" value="<?php echo $row['role']; ?>" />
                                        <input class="last_name" type="hidden" value="<?php echo $row['last_name']; ?>" />
                                        <input class="first_name" type="hidden" value="<?php echo $row['first_name']; ?>" />
                                        <input class="middle_name" type="hidden" value="<?php echo $row['middle_name']; ?>" />
                                        <input class="date_of_birth" type="hidden" value="<?php echo $row['date_of_birth']; ?>" />
                                        <input class="place_of_birth" type="hidden" value="<?php echo $row['place_of_birth']; ?>" />
                                        <input class="sex" type="hidden" value="<?php echo $row['sex']; ?>" />
                                        <input class="civil_status" type="hidden" value="<?php echo $row['civil_status']; ?>" />
                                        <input class="contact_number" type="hidden" value="<?php echo $row['contact_number']; ?>" />
                                        <input class="categ" type="hidden" value="<?php echo $row['category']; ?>" />
                                        <input class="email_address" type="hidden" value="<?php echo $row['email_address']; ?>" />
                                        <input class="status" type="hidden" value="<?php echo $row['status']; ?>" />
                                        <input class="department" type="hidden" value="<?php echo $row['department']; ?>" />
                                
                                        <td>
                                        <center>
                                        <img class="photo" src="uploads/<?php echo $row['photo']; ?>" width="50px" height="50px">
                                        </center>
                                        </td>
                                        <td class="rfid"><?php echo formatID($row['rfid_number']); ?></td>
                                        <td><?php echo $row['first_name'] .' '.$row['last_name']; ?></td>
                                        <td><?php echo $row['role']; ?></td>
                                        <td><?php echo $row['category']; ?></td>
                                        <td><?php echo $row['department']; ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Active') {
                                                echo '<span class="badge bg-success">Active</span>';
                                            } else {
                                                echo '<span class="badge bg-danger">Blocked</span>';
                                            } ?>
                                        </td>
                                        <td width="14%">
                                            <center>
                                                <button address="<?php echo $row['complete_address']; ?>" data-id="<?php echo $row['id'];?>" class="btn btn-outline-primary btn-sm btn-edit e_user_id">
                                                    <i class="fas fa-edit"></i> Edit 
                                                </button>
                                                <button user_name="<?php echo $row['first_name'] . ' ' . $row['last_name']; ?>" 
                                                        data-id="<?php echo $row['id']; ?>" 
                                                        class="btn btn-outline-danger btn-sm btn-del d_user_id">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </center>
                                        </td>
                                        <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Personnel Modal -->
            <form id="personellForm" role="form" method="post" action="" enctype="multipart/form-data">
                <div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">
                                    <i class="fas fa-plus-circle"></i> New Personnel
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="col-lg-11 mb-2 mt-1" id="mgs-emp" style="margin-left: 4%"></div>
                            <div class="modal-body">
                                <div class="row justify-content-md-center">
                                    <div id="msg-emp"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header" style="padding: 1%;color: black;font-size: 1.2rem">
                                            <i class="fas fa-user"></i> PERSONAL INFORMATION
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label for="photo" class="upload-img-btn" style="cursor: pointer;">
                                                        <img class="preview-1" src="../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg"
                                                            style="width: 140px!important; height: 130px!important; position: absolute; border: 1px solid gray; top: 25%;"
                                                            title="Upload Photo.." />
                                                    </label>
                                                    <input type="file" id="photo" name="photo" class="upload-field-1" 
                                                            style="opacity: 0; position: absolute; z-index: -1;" accept="image/*" required>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>ROLE:</label>
                                                    <select required class="form-control dept_ID" name="role" id="role" autocomplete="off" onchange="updateCategory()">
                                                        <?php
                                                            $sql = "SELECT * FROM role";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $role = $row['role'];
                                                                if ($role === 'Student') {
                                                                    echo "<option value='$role' selected>$role</option>";
                                                                } else {
                                                                    echo "<option value='$role'>$role</option>";
                                                                }
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="pob-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="lnamez">
                                                <div class="form-group">
                                                    <label>CATEGORY:</label>
                                                    <select required class="form-control" name="category" id="category" autocomplete="off">
                                                        <!-- Category options will be populated by JavaScript -->
                                                    </select>
                                                    <span class="id-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>LAST NAME:</label>
                                                    <input required type="text" class="form-control" name="last_name" id="last_name" autocomplete="off">
                                                    <span class="lname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>FIRST NAME:</label>
                                                    <input required type="text" class="form-control" name="first_name" id="first_name" autocomplete="off">
                                                    <span class="fname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>DATE OF BIRTH:</label>
                                                    <input required type="date" class="form-control" name="date_of_birth" id="date_of_birth" autocomplete="off">
                                                    <span class="dob-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>DEPARTMENT:</label>
                                                    <select required class="form-control" name="department" id="department" autocomplete="off">
                                                        <?php
                                                            $sql = "SELECT * FROM department";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $department_name = $row['department_name'];
                                                                echo "<option value='$department_name'>$department_name</option>";
                                                            }
                                                        ?>            
                                                    </select>
                                                    <span class="dprt-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>ID NUMBER:</label>
                                                    <input required type="text" class="form-control" name="rfid_number" id="rfid_number" minlength="9" maxlength="9" autocomplete="off" placeholder="0000-0000" pattern="[0-9]{4}-[0-9]{4}">
                                                    <span class="rfidno-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>STATUS:</label>
                                                    <input type="text" class="form-control" name="status" id="status" value="Active" autocomplete="off" readonly="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-emp" class="btn btn-outline-warning">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Edit Personnel Modal -->
            <div class="modal fade" id="editemployeeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-edit"></i> Edit Personnel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="col-lg-11 mb-2 mt-1" id="mgs-emp" style="margin-left: 4%"></div>
                        <form id="editPersonellForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body" id="editModal">
                                <div class="row justify-content-md-center">
                                    <div id="msg-emp" style=""></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header" style="padding: 1%;color: black;font-size: 1.2rem">
                                            <i class="fas fa-user"></i> PERSONAL INFORMATION
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label name="upload-label" class="upload-img-btn">
                                                        <input type="file" id="photo" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Foto.."/>
                                                        <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                                        <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>ROLE:</label>
                                                    <select class="form-control dept_ID" name="role" id="erole" autocomplete="off">
                                                        <option class="edit-role-val" value=""></option>
                                                        <?php
                                                            $sql = "SELECT * FROM role";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $role = $row['role'];
                                                                echo "<option value='$role'>$role</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="pob-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="lnamez">
                                                <div class="form-group">
                                                    <label>CATEGORY:</label>
                                                    <select class="form-control" name="category" id="ecategory" autocomplete="off">
                                                        <option class="edit-categ-val" value=""></option>
                                                    </select>
                                                    <span class="id-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>LAST NAME:</label>
                                                    <input type="text" class="form-control edit-lname" name="last_name" id="last_name" autocomplete="off">
                                                    <span class="lname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>FIRST NAME:</label>
                                                    <input type="text" class="form-control edit-fname" name="first_name" id="first_name" autocomplete="off">
                                                    <span class="fname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label>DATE OF BIRTH:</label>
                                                    <input type="date" class="form-control edit-dob" name="date_of_birth" id="date_of_birth" autocomplete="off">
                                                    <span class="dob-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>DEPARTMENT:</label>
                                                    <select class="form-control" name="e_department" id="e_department" autocomplete="off">
                                                        <option class="edit-department"></option>
                                                        <?php
                                                            $sql = "SELECT * FROM department";
                                                            $result = $db->query($sql);
                                                            while ($row = $result->fetch_assoc()) {
                                                                $department_name = $row['department_name'];
                                                                echo "<option value='$department_name'>$department_name</option>";
                                                            }
                                                        ?>            
                                                    </select>
                                                    <span class="dprt-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>ID NUMBER:</label>
                                                    <input required type="text" class="form-control edit-rfid" name="rfid_number" id="rfid_number1" minlength="9" maxlength="9" autocomplete="off" placeholder="0000-0000" pattern="[0-9]{4}-[0-9]{4}">
                                                    <span class="rfidno-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label>STATUS:</label>
                                                    <select class="form-control" name="status" id="status" autocomplete="off">
                                                        <option class="edit-status1"></option>
                                                        <option value="Active">Active</option>
                                                        <option value="Block">Block</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_employeeid" name="">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <input value="Update" name="update" type="submit" id="btn-editemp" class="btn btn-outline-warning"/>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Personnel Modal -->
            <div class="modal fade" id="delemployee-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-trash"></i> Delete Personnel
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" id="delete-form">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-delemp"></div>
                                <div class="col-lg-12 mb-1">
                                    <div class="form-group">
                                        <label for="inputTime"><b>Name:</b></label>
                                        <input type="text" id="delete_departmentname" class="form-control d-personell user_name" autocomplete="off" readonly="">
                                        <span class="deptname-error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="user_id" id="delete_employeeid">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">No</button>
                                <button type="button" class="btn btn-outline-primary remove_id" id="btn-delemp">Yes</button>
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
            order: [[8, 'desc']],
            stateSave: true
        });

        // Function to update category dropdown based on role selection
        function updateCategory() {
            var role = document.getElementById('role').value;
            var categorySelect = document.getElementById('category');
            
            // Clear the existing options
            categorySelect.innerHTML = '';

            if (role === 'Student') {
                // If the role is 'Student', show 'Student' only in category
                var option = document.createElement('option');
                option.value = 'Student';
                option.text = 'Student';
                categorySelect.appendChild(option);
            } else {
                // If the role is not 'Student', show 'Regular' and 'Contractual'
                var option1 = document.createElement('option');
                option1.value = 'Regular';
                option1.text = 'Regular';
                categorySelect.appendChild(option1);

                var option2 = document.createElement('option');
                option2.value = 'Contractual';
                option2.text = 'Contractual';
                categorySelect.appendChild(option2);
            }
        }

        // Initialize category dropdown on page load
        updateCategory();

        // Format ID number input to "0000-0000" pattern
        function formatIDNumber(input) {
            // Remove any non-digit characters
            let value = input.value.replace(/\D/g, '');
            
            // Add hyphen after 4 digits
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            
            // Update the input value
            input.value = value;
        }

        // Add event listeners for ID number formatting
        $('#rfid_number, #rfid_number1').on('input', function() {
            formatIDNumber(this);
        });

        // Handle form submission for adding personnel
        $('#personellForm').submit(function(e) {
            e.preventDefault();
            
            // Validate required fields
            const requiredFields = ['last_name', 'first_name', 'date_of_birth', 'rfid_number', 'role', 'category', 'department'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const fieldValue = $('#' + field).val();
                if (!fieldValue || fieldValue.trim() === '') {
                    isValid = false;
                    $('.' + field + '-error').text('This field is required').css('color', 'red');
                } else {
                    $('.' + field + '-error').text('');
                }
            });
            
            // Validate ID number format (0000-0000)
            const idNumber = $('#rfid_number').val();
            const idPattern = /^\d{4}-\d{4}$/;
            if (!idPattern.test(idNumber)) {
                isValid = false;
                $('.rfidno-error').text('ID Number must be in format: 0000-0000').css('color', 'red');
            } else {
                $('.rfidno-error').text('');
            }
            
            // Validate date of birth (minimum age 18)
            const dob = new Date($('#date_of_birth').val());
            const today = new Date();
            const minAgeDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            if (dob > minAgeDate) {
                isValid = false;
                $('.dob-error').text('Personnel must be at least 18 years old').css('color', 'red');
            } else {
                $('.dob-error').text('');
            }
            
            if (!isValid) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill all required fields correctly',
                    icon: 'error'
                });
                return;
            }

            // Remove hyphen from ID number before submitting
            var cleanIdNumber = idNumber.replace(/-/g, '');
            
            var formData = new FormData(this);
            formData.set('rfid_number', cleanIdNumber); // Use the clean ID without hyphen
            
            // Show loading indicator
            $('#btn-emp').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-emp').prop('disabled', true);
            
            $.ajax({
                url: 'transac.php?action=add_personnel',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-emp').html('Save');
                    $('#btn-emp').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            // Close modal and refresh page to show new record
                            $('#employeeModal').modal('hide');
                            location.reload();
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
                    $('#btn-emp').html('Save');
                    $('#btn-emp').prop('disabled', false);
                    
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred: ' + error,
                        icon: 'error'
                    });
                }
            });
        });

        // ID number duplicate check on blur
        $('#rfid_number').on('blur', function() {
            const idNumber = $(this).val();
            const idPattern = /^\d{4}-\d{4}$/;
            
            if (idPattern.test(idNumber)) {
                // Remove hyphen for database check
                const cleanIdNumber = idNumber.replace(/-/g, '');
                
                $.ajax({
                    url: 'check_rfid.php',
                    method: 'POST',
                    data: { rfid_number: cleanIdNumber },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.exists) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Duplicate ID Number',
                                text: 'This ID number already exists in the system.',
                            }).then(() => {
                                $('#rfid_number').val('').focus();
                            });
                        }
                    }
                });
            }
        });

        // Handle edit button click
        $(document).on('click', '.btn-edit', function() {
            var $id = $(this).data('id');
            
            // Retrieve data from the selected row
            var $getphoto = $('.table-' + $id + ' .photo').attr('src');
            var $getrfid = $('.table-' + $id + ' .rfid_raw').val(); // Get raw RFID without formatting
            var $getrole = $('.table-' + $id + ' .role').val();
            var $getcateg = $('.table-' + $id + ' .categ').val();
            var $getfname = $('.table-' + $id + ' .first_name').val();
            var $getlname = $('.table-' + $id + ' .last_name').val();
            var $getmname = $('.table-' + $id + ' .middle_name').val();
            var $getdob = $('.table-' + $id + ' .date_of_birth').val();
            var $getpob = $('.table-' + $id + ' .place_of_birth').val();
            var $getsex = $('.table-' + $id + ' .sex').val();
            var $getcivil = $('.table-' + $id + ' .civil_status').val();
            var $getcnumber = $('.table-' + $id + ' .contact_number').val();
            var $getemail = $('.table-' + $id + ' .email_address').val();
            var $getdepartment = $('.table-' + $id + ' .department').val();
            var $getstatus = $('.table-' + $id + ' .status').val();

            // Format the RFID for display in the edit form
            var formattedRfid = $getrfid;
            if (formattedRfid.length === 8 && /^\d+$/.test(formattedRfid)) {
                formattedRfid = formattedRfid.substring(0, 4) + '-' + formattedRfid.substring(4, 8);
            }

            // Update the modal fields with data
            $('.edit-photo').attr('src', $getphoto);
            $('.edit-rfid').val(formattedRfid); // Use formatted RFID
            $('.edit-id').val($id);
            $('#erole').val($getrole);
            $('#ecategory').val($getcateg);
            $('.edit-fname').val($getfname);
            $('.edit-lname').val($getlname);
            $('.capturedImage').val($getphoto);
            $('.edit-mname').val($getmname);
            $('.edit-dob').val($getdob);
            $('.edit-pob').val($getpob);
            $('.edit-sex').val($getsex);
            $('.edit-cnumber').val($getcnumber);
            $('.edit-status').val($getcivil);
            $('.edit-email').val($getemail);
            $('#e_department').val($getdepartment);
            $('#status').val($getstatus);

            // Update category dropdown based on role
            updateCategory1($getrole);

            // Show the modal
            $('#editemployeeModal').modal('show');
        });

        // Handle edit form submission
        $('#editPersonellForm').submit(function(e) {
            e.preventDefault();
            
            var userId = $('.edit-id').val();
            
            if (!userId) {
                Swal.fire({
                    title: 'Error!',
                    text: 'No user selected. Please select a user first.',
                    icon: 'error'
                });
                return;
            }

            // Remove hyphen from ID number before submitting
            var idNumber = $('#rfid_number1').val();
            var cleanIdNumber = idNumber.replace(/-/g, '');
            
            // Validate ID number format (8 digits)
            if (!/^\d{8}$/.test(cleanIdNumber)) {
                Swal.fire({
                    title: 'Error!',
                    text: 'ID Number must be 8 digits (format: 0000-0000)',
                    icon: 'error'
                });
                return;
            }

            var formData = new FormData(this);
            formData.set('rfid_number', cleanIdNumber); // Use the clean ID without hyphen
            formData.append('id', userId);

            // Show loading indicator
            $('#btn-editemp').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editemp').prop('disabled', true);
            
            $.ajax({
                url: 'edit1.php?edit=personell&id=' + userId,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editemp').html('Update');
                    $('#btn-editemp').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr) {
                    // Reset button state
                    $('#btn-editemp').html('Update');
                    $('#btn-editemp').prop('disabled', false);
                    
                    try {
                        // Try to parse the error response as JSON
                        var errorResponse = JSON.parse(xhr.responseText);
                        Swal.fire({
                            title: 'Error!',
                            text: errorResponse.message || 'An error occurred',
                            icon: 'error'
                        });
                    } catch (e) {
                        // If not JSON, show raw response
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred: ' + xhr.responseText,
                            icon: 'error'
                        });
                    }
                }
            });
        });

        

       // Handle delete button click
$(document).on('click', '.btn-del', function() {
    var userId = $(this).data('id');
    var userName = $(this).attr('user_name');
    
    // Store these for use in the AJAX call
    currentDeleteUserId = userId;
    currentDeleteUserName = userName;
    
    // Show confirmation dialog
    $('#delete_departmentname').val(userName);
    $('#delete_employeeid').val(userId);
    $('#delemployee-modal').modal('show');
});

// Handle the actual deletion when "Yes" is clicked in the modal
$(document).on('click', '#btn-delemp', function() {
    var userId = $('#delete_employeeid').val();
    var userName = $('#delete_departmentname').val();
    
    // Show loading indicator
    $('#btn-delemp').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');
    $('#btn-delemp').prop('disabled', true);
    
    $.ajax({
        url: 'del.php',
        type: 'POST',
        data: { 
            type: 'personell',
            id: userId 
        },
        dataType: 'json',
        success: function(response) {
            // Reset button state
            $('#btn-delemp').html('Yes');
            $('#btn-delemp').prop('disabled', false);
            
            if (response.status === 'success') {
                // Close the modal
                $('#delemployee-modal').modal('hide');
                
                // Remove the row from the table
                dataTable.row($('.table-' + userId)).remove().draw();
                
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
            $('#btn-delemp').html('Yes');
            $('#btn-delemp').prop('disabled', false);
            
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred: ' + error,
                icon: 'error'
            });
        }
    });
});

        // Reset modal when closed
        $('#employeeModal').on('hidden.bs.modal', function () {
            document.getElementById('role').value = 'Student';
            updateCategory();
            $(this).find('form')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
        });

        // Image preview functionality
        $("[class^=upload-field-]").change(function () {
            readURL(this);
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validFormats = ['image/jpeg', 'image/png'];
                const maxSize = 2 * 1024 * 1024; // 2MB

                // Validate file format
                if (!validFormats.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Format',
                        text: 'Only JPG and PNG formats are allowed.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Validate file size
                if (file.size > maxSize) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size is 2MB.',
                    });
                    input.value = ''; // Reset the input
                    return;
                }

                // Preview the image
                var reader = new FileReader();
                reader.onload = function (e) {
                    var num = $(input).attr('class').split('-')[2];
                    $('.file-uploader .preview-' + num).attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });

    // Function to update category dropdown for edit modal
    function updateCategory1(role) {
        const categoryDropdown = document.getElementById('ecategory');
        // Clear existing options
        categoryDropdown.innerHTML = '';

        if (role === 'Student') {
            const studentOption = new Option('Student', 'Student');
            categoryDropdown.add(studentOption);
        } else {
            const regularOption = new Option('Regular', 'Regular');
            const contractualOption = new Option('Contractual', 'Contractual');
            categoryDropdown.add(regularOption);
            categoryDropdown.add(contractualOption);
        }
    }

    </script>
</body>
</html>