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

include '../connection.php';

function getStudentPhoto($photo) {
    $basePath = '../uploads/students/';
    $defaultPhoto = '../assets/img/2601828.png';

    // If no photo or file does not exist → return default
    if (empty($photo) || !file_exists($basePath . $photo)) {
        return $defaultPhoto;
    }

    return $basePath . $photo;
}

// Get statistics for summary cards
$totalStudents = mysqli_num_rows(mysqli_query($db, "SELECT id FROM students"));
$firstYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '1st Year'"))['count'];
$secondYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '2nd Year'"))['count'];
$thirdYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '3rd Year'"))['count'];
$fourthYearCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM students WHERE year = '4th Year'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .bg-light {
            background-color: #f8f9fa !important;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
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
        .student-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        .upload-img-btn {
            cursor: pointer;
            display: block;
            position: relative;
        }
        .preview-1 {
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .preview-1:hover {
            opacity: 0.8;
        }
        .file-uploader {
            position: relative;
            margin-bottom: 15px;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
        }
        .group-header {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .group-header:hover {
            background-color: #0b5ed7 !important;
        }
        .view-toggle-btn.active {
            background-color: #4e73df;
            color: white;
        }
        .student-count-badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
        }
        @media (max-width: 768px) {
            .filter-section .col-md-3,
            .filter-section .col-md-2 {
                margin-bottom: 1rem;
            }
            .btn-group .btn {
                margin-bottom: 0.5rem;
            }
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
                        <div class="row mb-4">
                            <div class="col-6">
                                <h4 class="mb-0">Manage Students</h4>
                                <p class="text-muted">View and manage all student records</p>
                            </div>
                            <div class="col-6 text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-warning m-1" data-bs-toggle="modal" data-bs-target="#studentModal">
                                        <i class="fas fa-plus-circle"></i> Add Student
                                    </button>
                                    <button type="button" class="btn btn-outline-success m-1" id="exportBtn">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button type="button" class="btn btn-outline-info m-1" id="printBtn">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <div class="card bg-primary text-white summary-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fs-6 fw-bold">Total Students</div>
                                                <div class="fs-4 fw-bold"><?php echo $totalStudents; ?></div>
                                            </div>
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <div class="card bg-success text-white summary-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fs-6 fw-bold">1st Year</div>
                                                <div class="fs-4 fw-bold"><?php echo $firstYearCount; ?></div>
                                            </div>
                                            <i class="fas fa-graduation-cap fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <div class="card bg-info text-white summary-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fs-6 fw-bold">2nd Year</div>
                                                <div class="fs-4 fw-bold"><?php echo $secondYearCount; ?></div>
                                            </div>
                                            <i class="fas fa-graduation-cap fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <div class="card bg-warning text-dark summary-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fs-6 fw-bold">3rd Year</div>
                                                <div class="fs-4 fw-bold"><?php echo $thirdYearCount; ?></div>
                                            </div>
                                            <i class="fas fa-graduation-cap fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <div class="card bg-danger text-white summary-card mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fs-6 fw-bold">4th Year</div>
                                                <div class="fs-4 fw-bold"><?php echo $fourthYearCount; ?></div>
                                            </div>
                                            <i class="fas fa-graduation-cap fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- View Toggle Buttons -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary view-toggle-btn active" data-view="table">
                                        <i class="fas fa-table"></i> Table View
                                    </button>
                                    <button type="button" class="btn btn-outline-primary view-toggle-btn" data-view="grouped">
                                        <i class="fas fa-layer-group"></i> Grouped View
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-2">
                                    <label><strong>Filter by Year:</strong></label>
                                    <select class="form-control" id="filterYear">
                                        <option value="">All Years</option>
                                        <option value="1st Year">1st Year</option>
                                        <option value="2nd Year">2nd Year</option>
                                        <option value="3rd Year">3rd Year</option>
                                        <option value="4th Year">4th Year</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label><strong>Filter by Section:</strong></label>
                                    <select class="form-control" id="filterSection">
                                        <option value="">All Sections</option>
                                        <option value="West">West</option>
                                        <option value="North">North</option>
                                        <option value="East">East</option>
                                        <option value="South">South</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label><strong>Search:</strong></label>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or ID...">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-outline-secondary w-100" id="resetFilters">
                                        <i class="fas fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Table View -->
                        <div class="view-content" id="tableView">
                            <div class="table-responsive">
                                <table class="table table-border" id="myDataTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Photo</th>
                                            <th scope="col">ID Number</th>
                                            <th scope="col">Full Name</th>
                                            <th scope="col">Department</th>
                                            <th scope="col">Year</th>
                                            <th scope="col">Section</th>
                                            <th scope="col">Action</th>
                                            <th style="display: none;">Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $results = mysqli_query($db, "SELECT s.*, d.department_name 
                                                                    FROM students s 
                                                                    LEFT JOIN department d 
                                                                    ON s.department_id = d.department_id 
                                                                    ORDER BY s.year, s.section, s.fullname"); 
                                        
                                        if ($results === false) {
                                            die("Query failed: " . mysqli_error($db));
                                        }
                                        ?>
                                        <?php while ($row = mysqli_fetch_array($results)) { 
                                            $photoPath = getStudentPhoto($row['photo']);
                                        ?>
                                        <tr class="table-<?php echo $row['id'];?>" data-student-id="<?php echo $row['id'];?>">
                                            <input class="department_id" type="hidden" value="<?php echo $row['department_id']; ?>" />
                                            <input class="id_number" type="hidden" value="<?php echo $row['id_number']; ?>" />
                                            <input class="fullname" type="hidden" value="<?php echo $row['fullname']; ?>" />
                                            <input class="section" type="hidden" value="<?php echo $row['section']; ?>" />
                                            <input class="year" type="hidden" value="<?php echo $row['year']; ?>" />
                                            <?php if (isset($row['date_added'])): ?>
                                            <input class="date_added" type="hidden" value="<?php echo $row['date_added']; ?>" />
                                            <?php endif; ?>

                                            <td>
                                                <center>
                                                    <img class="photo student-photo" src="<?php echo $photoPath; ?>" 
                                                         onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                                </center>
                                            </td>
                                            <td class="student_id"><?php echo $row['id_number']; ?></td>
                                            <td><?php echo $row['fullname']; ?></td>
                                            <td><?php echo $row['department_name']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $row['year']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $row['section']; ?></span></td>
                                            <td width="14%">
                                                <center>
                                                    <button data-id="<?php echo $row['id'];?>" 
                                                            class="btn btn-outline-primary btn-sm btn-edit e_student_id">
                                                        <i class="fas fa-edit"></i> Edit 
                                                    </button>
                                                    <button student_name="<?php echo $row['fullname']; ?>" 
                                                            data-id="<?php echo $row['id']; ?>" 
                                                            class="btn btn-outline-danger btn-sm btn-del d_student_id">
                                                        <i class="fas fa-trash"></i> Delete 
                                                    </button>
                                                </center>
                                            </td>
                                            <?php if (isset($row['date_added'])): ?>
                                            <td style="display:none;" class="hidden-date"><?php echo $row['date_added']; ?></td>
                                            <?php else: ?>
                                            <td style="display:none;" class="hidden-date"><?php echo date('Y-m-d H:i:s'); ?></td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Grouped View -->
                        <div class="view-content" id="groupedView" style="display: none;">
                            <?php
                            // Group students by year and section
                            $groupedQuery = mysqli_query($db, 
                                "SELECT s.*, d.department_name 
                                 FROM students s 
                                 LEFT JOIN department d ON s.department_id = d.department_id 
                                 ORDER BY s.year, s.section, s.fullname");
                            
                            $groupedStudents = [];
                            while ($row = mysqli_fetch_array($groupedQuery)) {
                                $groupKey = $row['year'] . '_' . $row['section'];
                                $groupedStudents[$groupKey][] = $row;
                            }
                            
                            foreach ($groupedStudents as $groupKey => $students) {
                                list($year, $section) = explode('_', $groupKey);
                                $studentCount = count($students);
                            ?>
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center group-header" 
                                     data-bs-toggle="collapse" data-bs-target="#collapse<?php echo str_replace(' ', '', $year.$section); ?>">
                                    <h6 class="mb-0">
                                        <i class="fas fa-users me-2"></i>
                                        <?php echo $year ?> - <?php echo $section ?> Section
                                        <span class="badge bg-light text-dark ms-2 student-count-badge"><?php echo $studentCount ?> students</span>
                                    </h6>
                                    <div>
                                        <button class="btn btn-sm btn-outline-light expand-all me-1" title="Expand/Collapse">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="collapse show" id="collapse<?php echo str_replace(' ', '', $year.$section); ?>">
                                    <div class="card-body p-0">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Photo</th>
                                                    <th>ID Number</th>
                                                    <th>Full Name</th>
                                                    <th>Department</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student) { 
                                                    $photoPath = getStudentPhoto($student['photo']);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <center>
                                                            <img class="student-photo" src="<?php echo $photoPath; ?>" 
                                                                 onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                                        </center>
                                                    </td>
                                                    <td><?php echo $student['id_number']; ?></td>
                                                    <td><?php echo $student['fullname']; ?></td>
                                                    <td><?php echo $student['department_name']; ?></td>
                                                    <td width="14%">
                                                        <center>
                                                            <button data-id="<?php echo $student['id'];?>" 
                                                                    class="btn btn-outline-primary btn-sm btn-edit e_student_id">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button student_name="<?php echo $student['fullname']; ?>" 
                                                                    data-id="<?php echo $student['id']; ?>" 
                                                                    class="btn btn-outline-danger btn-sm btn-del d_student_id">
                                                                <i class="fas fa-trash"></i>
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
                            <?php } ?>
                        </div>

                        <!-- Card View -->
                        <div class="view-content" id="cardView" style="display: none;">
                            <div class="row">
                                <?php
                                $cardQuery = mysqli_query($db, 
                                    "SELECT s.*, d.department_name 
                                     FROM students s 
                                     LEFT JOIN department d ON s.department_id = d.department_id 
                                     ORDER BY s.year, s.fullname");
                                
                                while ($student = mysqli_fetch_array($cardQuery)) {
                                    $photoPath = getStudentPhoto($student['photo']);
                                ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 mb-4 student-card" 
                                     data-department="<?php echo $student['department_id']; ?>"
                                     data-year="<?php echo $student['year']; ?>"
                                     data-section="<?php echo $student['section']; ?>">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <img src="<?php echo $photoPath; ?>" 
                                                 class="student-photo mb-3" 
                                                 style="width: 80px; height: 80px;"
                                                 onerror="this.onerror=null; this.src='../assets/img/default-avatar.png';">
                                            <h6 class="card-title"><?php echo $student['fullname']; ?></h6>
                                            <p class="card-text">
                                                <small class="text-muted">ID: <?php echo $student['id_number']; ?></small><br>
                                                <span class="badge bg-primary"><?php echo $student['year']; ?></span>
                                                <span class="badge bg-info"><?php echo $student['section']; ?></span><br>
                                                <small><?php echo $student['department_name']; ?></small>
                                            </p>
                                        </div>
                                        <div class="card-footer text-center">
                                            <button data-id="<?php echo $student['id'];?>" 
                                                    class="btn btn-outline-primary btn-sm btn-edit e_student_id">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button student_name="<?php echo $student['fullname']; ?>" 
                                                    data-id="<?php echo $student['id']; ?>" 
                                                    class="btn btn-outline-danger btn-sm btn-del d_student_id">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Student Modal -->
            <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">
                                <i class="fas fa-plus-circle"></i> New Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="studentForm" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="col-lg-12 mt-1" id="mgs-student"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-student"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>STUDENT INFORMATION</strong>
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
                                                    <span class="error-message" id="photo-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select required class="form-control dept_ID" name="department_id" id="department_id" autocomplete="off">
                                                        <option value="">Select Department</option>
                                                        <?php
                                                            $sql = "SELECT * FROM department ORDER BY department_name";
                                                            $result = $db->query($sql);
                                                            while ($dept = $result->fetch_assoc()) {
                                                                echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="department_id-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label><b>ID Number:</b></label>
                                                    <input required type="text" class="form-control" name="id_number" id="id_number" autocomplete="off">
                                                    <span class="error-message" id="id_number-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Full Name:</b></label>
                                                    <input required type="text" class="form-control" name="fullname" id="fullname" autocomplete="off">
                                                    <span class="error-message" id="fullname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year:</b></label>
                                                    <select required class="form-control" name="year" id="year" autocomplete="off">
                                                        <option value="">Select Year</option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="year-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <select required class="form-control" name="section" id="section" autocomplete="off">
                                                        <option value="">Select Section</option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="error-message" id="section-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-student" class="btn btn-outline-warning">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Student Modal -->
            <div class="modal fade" id="editstudentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-edit"></i> Edit Student
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editStudentForm" class="edit-form" role="form" method="post" action="" enctype="multipart/form-data">
                            <div class="modal-body" id="editModal">
                                <div class="col-lg-12 mt-1" id="mgs-editstudent"></div>
                                <div class="row justify-content-md-center">
                                    <div id="msg-editstudent"></div>
                                    <div class="col-sm-12 col-md-12 col-lg-10">
                                        <div class="section-header p-2 mb-3 rounded">
                                            <strong>STUDENT INFORMATION</strong>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-3 col-md-6 col-sm-12" id="up_img">
                                                <div class="file-uploader">
                                                    <label name="upload-label" class="upload-img-btn">
                                                        <input type="file" id="editPhoto" name="photo" class="upload-field-1" style="display:none;" accept="image/*" title="Upload Photo.."/>
                                                        <input type="hidden" id="capturedImage" name="capturedImage" class="capturedImage">
                                                        <img class="preview-1 edit-photo" src="" style="width: 140px!important;height: 130px!important;position: absolute;border: 1px solid gray;top: 25%" title="Upload Photo.." />
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <label><b>Department:</b></label>
                                                    <select class="form-control dept_ID" name="department_id" id="edepartment_id" autocomplete="off">
                                                        <option class="edit-dept-val" value=""></option>
                                                        <?php
                                                            $sql = "SELECT * FROM department ORDER BY department_name";
                                                            $result = $db->query($sql);
                                                            while ($dept = $result->fetch_assoc()) {
                                                                echo "<option value='{$dept['department_id']}'>{$dept['department_name']}</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                    <span class="error-message" id="edepartment_id-error"></span>
                                                </div>
                                            </div>

                                            <div class="col-lg-5 col-md-6 col-sm-12" id="idnumberz">
                                                <div class="form-group">
                                                    <label><b>ID Number:</b></label>
                                                    <input required type="text" class="form-control edit-idnumber" name="id_number" id="eid_number" autocomplete="off">
                                                    <span class="error-message" id="eid_number-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3 mt-1">
                                            <div class="col-lg-3 col-md-6 col-sm-12">
                                                <div class="form-group">
                                                    <!-- empty -->
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Full Name:</b></label>
                                                    <input type="text" class="form-control edit-fullname" name="fullname" id="efullname" autocomplete="off">
                                                    <span class="error-message" id="efullname-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Year:</b></label>
                                                    <select class="form-control" name="year" id="eyear" autocomplete="off">
                                                        <option class="edit-year-val" value=""></option>
                                                        <option value="1st Year">1st Year</option>
                                                        <option value="2nd Year">2nd Year</option>
                                                        <option value="3rd Year">3rd Year</option>
                                                        <option value="4th Year">4th Year</option>
                                                    </select>
                                                    <span class="error-message" id="eyear-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-6 col-sm-12 mt-1">
                                                <div class="form-group">
                                                    <label><b>Section:</b></label>
                                                    <select class="form-control" name="section" id="esection" autocomplete="off">
                                                        <option class="edit-section-val" value=""></option>
                                                        <option value="West">West</option>
                                                        <option value="North">North</option>
                                                        <option value="East">East</option>
                                                        <option value="South">South</option>
                                                    </select>
                                                    <span class="error-message" id="esection-error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="edit_studentid" name="student_id" class="edit-id">
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                <button type="submit" id="btn-editstudent" class="btn btn-outline-primary">Update</button>
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
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
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
        // Initialize DataTable with export buttons
        var dataTable = $('#myDataTable').DataTable({
            order: [[7, 'desc']],
            stateSave: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            responsive: true
        });

        // View Toggle Functionality
        $('.view-toggle-btn').on('click', function() {
            const viewType = $(this).data('view');
            
            // Update active button
            $('.view-toggle-btn').removeClass('active');
            $(this).addClass('active');
            
            // Show selected view, hide others
            $('.view-content').hide();
            $(`#${viewType}View`).show();
            
            // Reinitialize DataTable when switching back to table view
            if (viewType === 'table') {
                dataTable.columns.adjust().responsive.recalc();
            }
        });

        // Filter Functionality
        function initializeFilters() {
            const filterDepartment = $('#filterDepartment');
            const filterYear = $('#filterYear');
            const filterSection = $('#filterSection');
            const searchInput = $('#searchInput');
            
            function applyFilters() {
                const departmentVal = filterDepartment.val();
                const yearVal = filterYear.val();
                const sectionVal = filterSection.val();
                const searchVal = searchInput.val().toLowerCase();
                
                // Table view filtering
                dataTable.column(1).search(searchVal).draw();
                dataTable.column(3).search(departmentVal).draw();
                dataTable.column(4).search(yearVal).draw();
                dataTable.column(5).search(sectionVal).draw();
                
                // Card view filtering
                $('.student-card').each(function() {
                    const $card = $(this);
                    const cardDept = $card.data('department').toString();
                    const cardYear = $card.data('year');
                    const cardSection = $card.data('section');
                    const cardText = $card.text().toLowerCase();
                    
                    const deptMatch = !departmentVal || cardDept === departmentVal;
                    const yearMatch = !yearVal || cardYear === yearVal;
                    const sectionMatch = !sectionVal || cardSection === sectionVal;
                    const searchMatch = !searchVal || cardText.includes(searchVal);
                    
                    if (deptMatch && yearMatch && sectionMatch && searchMatch) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
                
                // Grouped view filtering (simplified - could be enhanced)
                $('.card.mb-3').each(function() {
                    const $group = $(this);
                    const groupHeader = $group.find('.card-header').text().toLowerCase();
                    const hasVisibleRows = $group.find('tbody tr:visible').length > 0;
                    
                    if (hasVisibleRows || groupHeader.includes(searchVal)) {
                        $group.show();
                    } else {
                        $group.hide();
                    }
                });
            }
            
            // Event listeners for filters
            filterDepartment.on('change', applyFilters);
            filterYear.on('change', applyFilters);
            filterSection.on('change', applyFilters);
            searchInput.on('keyup', applyFilters);
            
            // Reset filters
            $('#resetFilters').on('click', function() {
                filterDepartment.val('');
                filterYear.val('');
                filterSection.val('');
                searchInput.val('');
                applyFilters();
            });
        }

        // Initialize filters
        initializeFilters();

        // Export functionality
        $('#exportBtn').on('click', function() {
            dataTable.button('.buttons-excel').trigger();
        });

        // Print functionality
        $('#printBtn').on('click', function() {
            dataTable.button('.buttons-print').trigger();
        });

        // Expand/Collapse all groups
        $(document).on('click', '.expand-all', function(e) {
            e.stopPropagation();
            const $header = $(this).closest('.card-header');
            const $collapse = $($header.data('bs-target'));
            $collapse.collapse('toggle');
        });

        // Reset form function
        function resetForm() {
            $('.error-message').text('');
            $('#studentForm')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
        }

        // ==========
        // READ (EDIT STUDENT)
        // ==========
        $(document).on('click', '.e_student_id', function() {
            const id = $(this).data('id');
            
            // Retrieve data from the selected row
            const $row = $(this).closest('tr');
            const $getphoto = $row.find('.photo').attr('src');
            const $getidnumber = $row.find('.student_id').text();
            const $getdept = $row.find('.department_id').val();
            const $getfullname = $row.find('.fullname').val();
            const $getyear = $row.find('.year').val();
            const $getsection = $row.find('.section').val();

            console.log('Editing student:', id, $getidnumber, $getfullname);

            // Populate edit form
            $('#edit_studentid').val(id);
            $('.edit-photo').attr('src', $getphoto);
            $('#eid_number').val($getidnumber);
            $('#edepartment_id').val($getdept);
            $('#efullname').val($getfullname);
            $('#eyear').val($getyear);
            $('#esection').val($getsection);
            $('.capturedImage').val($getphoto);
            
            // Clear any previous error messages
            $('.error-message').text('');
            
            // Show modal
            $('#editstudentModal').modal('show');
        });

        // ==============
        // CREATE (ADD STUDENT)
        // ==============
        $('#studentForm').submit(function(e) {
            e.preventDefault();
            
            $('.error-message').text('');
            const department_id = $('#department_id').val();
            const id_number = $('#id_number').val().trim();
            const fullname = $('#fullname').val().trim();
            const year = $('#year').val();
            const section = $('#section').val();
            const photo = $('#photo')[0].files[0];
            let isValid = true;

            // Validation
            if (!department_id) { 
                $('#department_id-error').text('Department is required'); 
                isValid = false; 
            }
            if (!id_number) { 
                $('#id_number-error').text('ID Number is required'); 
                isValid = false; 
            }
            if (!fullname) { 
                $('#fullname-error').text('Full name is required'); 
                isValid = false; 
            }
            if (!year) { 
                $('#year-error').text('Year is required'); 
                isValid = false; 
            }
            if (!section) { 
                $('#section-error').text('Section is required'); 
                isValid = false; 
            }
            if (!photo) { 
                $('#photo-error').text('Photo is required'); 
                isValid = false; 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-student').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
            $('#btn-student').prop('disabled', true);

            var formData = new FormData(this);

            $.ajax({
                type: "POST",
                url: "transac.php?action=add_student",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#studentModal').modal('hide');
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
                    $('#btn-student').html('Save');
                    $('#btn-student').prop('disabled', false);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred: ' + error
                    });
                }
            });
        });

        // ==========
        // UPDATE STUDENT
        // ==========
        $('#editStudentForm').submit(function(e) {
            e.preventDefault();
            
            const id = $('#edit_studentid').val();
            const department_id = $('#edepartment_id').val();
            const id_number = $('#eid_number').val().trim();
            const fullname = $('#efullname').val().trim();
            const year = $('#eyear').val();
            const section = $('#esection').val();

            // Validation
            let isValid = true;
            if (!department_id) { 
                $('#edepartment_id-error').text('Department is required'); 
                isValid = false; 
            } else { 
                $('#edepartment_id-error').text(''); 
            }
            if (!id_number) { 
                $('#eid_number-error').text('ID Number is required'); 
                isValid = false; 
            } else { 
                $('#eid_number-error').text(''); 
            }
            if (!fullname) { 
                $('#efullname-error').text('Full name is required'); 
                isValid = false; 
            } else { 
                $('#efullname-error').text(''); 
            }
            if (!year) { 
                $('#eyear-error').text('Year is required'); 
                isValid = false; 
            } else { 
                $('#eyear-error').text(''); 
            }
            if (!section) { 
                $('#esection-error').text('Section is required'); 
                isValid = false; 
            } else { 
                $('#esection-error').text(''); 
            }
            
            if (!isValid) return;

            // Show loading state
            $('#btn-editstudent').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
            $('#btn-editstudent').prop('disabled', true);

            var formData = new FormData(this);
            formData.append('id', id);

            $.ajax({
                type: "POST",
                url: "transac.php?action=update_student",
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    // Reset button state
                    $('#btn-editstudent').html('Update');
                    $('#btn-editstudent').prop('disabled', false);
                    
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            $('#editstudentModal').modal('hide');
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
                    $('#btn-editstudent').html('Update');
                    $('#btn-editstudent').prop('disabled', false);
                    
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
        $(document).on('click', '.d_student_id', function() {
            var studentId = $(this).data('id');
            var studentName = $(this).attr('student_name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete student: " + studentName,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "transac.php?action=delete_student",
                        type: 'POST',
                        data: { 
                            id: studentId 
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
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
                        error: function(xhr, status, error) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred: ' + error,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Reset modal when closed
        $('#studentModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('.preview-1').attr('src', '../assets/img/pngtree-vector-add-user-icon-png-image_780447.jpg');
            $('.error-message').text('');
        });

        $('#editstudentModal').on('hidden.bs.modal', function () {
            $('.error-message').text('');
        });

        // Image preview functionality for both forms
        $(document).on('change', '[class^=upload-field-]', function() {
            readURL(this);
        });

        // Click handler for edit photo upload
        $(document).on('click', '.edit-photo', function() {
            $('#editPhoto').click();
        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const validFormats = ['image/jpeg', 'image/png', 'image/jpg'];
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
                    // Find the closest preview image
                    $(input).closest('.file-uploader').find('.preview-1').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        }
    });
    </script>
</body>
</html>