<?php
session_start();
if (isset($_SESSION['reload_flag'])) {
    // Unset specific session variables
    unset($_SESSION['month']); 
    unset($_SESSION['name']);
    unset($_SESSION['id']);
} 

 $id=0;
include '../connection.php';
?>
<?php
include 'header.php';

// Check if there's a search query
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['query'])) {
    
    $query = trim($_POST['query']);  // Get the search query and remove leading/trailing spaces

    // SQL query to fetch instructors from instructor table
    $sql = "SELECT id, fullname 
            FROM instructor 
            WHERE fullname LIKE ?";

    // Prepare the SQL statement
    $stmt = $db->prepare($sql);

    // Use wildcard to match partial strings
    $searchTerm = "%" . $query . "%";  

    // Bind parameters for fullname search
    $stmt->bind_param("s", $searchTerm);  // 's' for string

    // Execute the query and get the result
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the results into an array
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }

    // Close the statement and the database connection
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'header.php'; ?>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
        <?php
        include 'navbar.php';
        ?>
 <style>
        .instructor-list {
            list-style-type: none;
            padding: 0;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .instructor-list li {
            padding: 8px;
            cursor: pointer;
        }
        .instructor-list li:hover {
            background-color: #f0f0f0;
        }
    </style>
    <style>
         #suggestions {
            position: absolute;
            z-index: 9999; /* Ensure it appears on top */
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 5px;
        }

        #suggestions div {
            padding: 10px;
            cursor: pointer;
            background-color: #f9f9f9;
        }

        #suggestions div:hover {
            background-color: #e0e0e0;
        }
    </style>
            <div class="container-fluid pt-4 px-4">
                <div class="col-sm-12 col-xl-12">
                    <div class="bg-light rounded h-100 p-4">
                        <div class="row">
                            <div class="col-9">
                                <h6 class="mb-4">Generate Instructor DTR</h6>
                            </div>
                        </div>
                        <br>
                        <form id="filterForm" method="POST" action="">
                        <div class="row">

              
                        <div class="col-lg-3">
            <label>Search Instructor:</label>
           
            <input type="text" name="pname" class="form-control" id="searchInput" autocomplete="off">
            <input hidden type="text" id="pername" name="pername" autocomplete="off">
            <input hidden type="text" id="perid" name="perid" autocomplete="off">
    <div id="suggestions"></div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const suggestionsDiv = document.getElementById('suggestions');

        // Event listener for input field
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim();
            
            // Clear suggestions if input is empty
            if (query.length === 0) {
                suggestionsDiv.innerHTML = '';
                return;
            }

            // Send request to the PHP script
            fetch(`search_personnel.php?query=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    suggestionsDiv.innerHTML = '';
                    if (data.error) {
                        suggestionsDiv.innerHTML = '<div>Error fetching data</div>';
                        console.error(data.error);
                    } else if (data.length > 0) {
                        data.forEach(instructor => {
                            const div = document.createElement('div');
                            div.textContent = instructor.fullname;
                            div.addEventListener('click', () => {
                                searchInput.value = instructor.fullname;
                                suggestionsDiv.innerHTML = '';
                                document.getElementById('pername').value = searchInput.value;
                                document.getElementById('perid').value = instructor.id;
                            });
                            suggestionsDiv.appendChild(div);
                        });
                    } else {
                        suggestionsDiv.innerHTML = '<div>No matches found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        });
    </script>
               
            
        </div>
        <div class="col-lg-3">
            <label>Month:</label>
            
            <select class="form-control" id="months" name="month">
            <option value="<?php echo date('F'); ?>" selected><?php echo date('F'); ?></option>
    <option value="January">January</option>
    <option value="February">February</option>
    <option value="March">March</option>
    <option value="April">April</option>
    <option value="May">May</option>
    <option value="June">June</option>
    <option value="July">July</option>
    <option value="August">August</option>
    <option value="September">September</option>
    <option value="October">October</option>
    <option value="November">November</option>
    <option value="December">December</option>
</select>
 
            
        </div>
        <div class="col-lg-3 mt-4">
            <label></label>
            <button type="submit" class="btn btn-primary" id="btn_search"><i class="fa fa-search"></i> Search</button>
          
        </div>
        <div class="col-lg-3 mt-4" style="text-align:right;">
                                <label></label>
                                <button onclick="printDiv('container')" type="button" class="btn btn-success" id="btn_print"><i class="fa fa-print"> Print</i></button> 
                               
                            </div></form>
        

                        </div>
                        <hr>
                        <div class="table-responsive">

    <style>
       
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 20px;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            text-decoration: underline;
        }
        .header h3 {
            margin: 5px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table th, .info-table td {
            border: none;
            padding: 5px;
        }
        .info-table th {
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
        }
        .footer p {
            font-size: 14px;
            text-align: justify;
        }
        .footer .in-charge {
            text-align: right;
            margin-top: 30px;
        }
        /* Add styles for highlighting incomplete records */
        .incomplete-day {
            background-color: #fff3cd !important;
        }
        .no-time-in {
            color: #dc3545;
            font-weight: bold;
        }
        .no-time-out {
            color: #ffc107;
            font-weight: bold;
        }
    
</style>
<?php

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the value from the hidden input field
    $name = $_POST['pername']; // Sanitize the input
    $month = $_POST['month'] ?? '';
    $id = $_POST['perid'];
    $_SESSION['id'] = $id;
    // Add additional processing logic here, such as database queries
    $_SESSION['name'] =$name;
    $_SESSION['month']=$month;

// Query to fetch fullname for the given instructor ID
 $instructor = [];
 $sql = "SELECT fullname
        FROM instructor 
        WHERE id = ?";

// Prepare and execute the query
 $stmt = $db->prepare($sql);
 $stmt->bind_param("i", $id);
 $stmt->execute();
 $result = $stmt->get_result();

// Fetch the instructor data
if ($row = $result->fetch_assoc()) {
    $instructor = $row; // Store fullname
}

// Close the statement
 $stmt->close();

// Check if instructor data is available
if (empty($instructor)) {
    echo "No instructor found for the given ID.";
    exit;
}

// Get current year and month number
 $currentYear = date('Y');
 $month1 = date('m', strtotime($month)); 

// Initialize the array to store the data for each day
 $daysData = [];

// ENHANCED: Updated SQL query to fetch all gate logs for the instructor
 $sql = "SELECT date, time_in, time_out, action, direction
        FROM gate_logs 
        WHERE MONTH(date) = ? AND YEAR(date) = ? 
        AND person_id = ? AND person_type = 'instructor'
        ORDER BY date, time_in";

// Prepare statement
 $stmt = $db->prepare($sql);

if (!$stmt) {
    die("Error preparing statement: " . $db->error);
}

// Bind parameters (current month, current year, and instructor ID)
 $stmt->bind_param("iii", $month1, $currentYear, $id);

// Execute the statement
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

// Get the result
 $result = $stmt->get_result();

// First, collect all logs for each day
 $dailyLogs = [];
while ($row = $result->fetch_assoc()) {
    $day = (int)date('d', strtotime($row['date']));
    if (!isset($dailyLogs[$day])) {
        $dailyLogs[$day] = [];
    }
    $dailyLogs[$day][] = $row;
}

// Process each day's logs to determine time in/out
foreach ($dailyLogs as $day => $logs) {
    // Initialize day data
    $daysData[$day] = [
        'time_in_am' => '',
        'time_out_am' => '',
        'time_in_pm' => '',
        'time_out_pm' => '',
        'has_in_am' => false,
        'has_out_am' => false,
        'has_in_pm' => false,
        'has_out_pm' => false
    ];
    
    // Process each log entry for the day
    foreach ($logs as $log) {
        $time_in = !empty($log['time_in']) && $log['time_in'] != '00:00:00' ? $log['time_in'] : null;
        $time_out = !empty($log['time_out']) && $log['time_out'] != '00:00:00' ? $log['time_out'] : null;
        $action = strtoupper($log['action'] ?? $log['direction'] ?? '');
        
        // Process time_in entries
        if ($time_in) {
            $hour = (int)date('H', strtotime($time_in));
            $time_12h = date('g:i A', strtotime($time_in));
            
            if ($hour < 12 && !$daysData[$day]['has_in_am']) {
                // AM time in
                $daysData[$day]['time_in_am'] = $time_12h;
                $daysData[$day]['has_in_am'] = true;
            } elseif ($hour >= 12 && !$daysData[$day]['has_in_pm']) {
                // PM time in
                $daysData[$day]['time_in_pm'] = $time_12h;
                $daysData[$day]['has_in_pm'] = true;
            }
        }
        
        // Process time_out entries
        if ($time_out) {
            $hour = (int)date('H', strtotime($time_out));
            $time_12h = date('g:i A', strtotime($time_out));
            
            if ($hour < 12 && !$daysData[$day]['has_out_am']) {
                // AM time out
                $daysData[$day]['time_out_am'] = $time_12h;
                $daysData[$day]['has_out_am'] = true;
            } elseif ($hour >= 12 && !$daysData[$day]['has_out_pm']) {
                // PM time out
                $daysData[$day]['time_out_pm'] = $time_12h;
                $daysData[$day]['has_out_pm'] = true;
            }
        }
    }
    
    // Auto-fill logic: If no time_out but there's a time_in, check for next day's time_in
    if (!$daysData[$day]['has_out_am'] && $daysData[$day]['has_in_am']) {
        // Check if next day has an AM time in (meaning they left after midnight)
        if (isset($dailyLogs[$day + 1])) {
            foreach ($dailyLogs[$day + 1] as $nextDayLog) {
                $next_time_in = !empty($nextDayLog['time_in']) && $nextDayLog['time_in'] != '00:00:00' ? $nextDayLog['time_in'] : null;
                if ($next_time_in) {
                    $next_hour = (int)date('H', strtotime($next_time_in));
                    if ($next_hour < 6) { // Before 6 AM is considered same day's night out
                        $daysData[$day]['time_out_pm'] = date('g:i A', strtotime($next_time_in));
                        $daysData[$day]['has_out_pm'] = true;
                        break;
                    }
                }
            }
        }
    }
    
    // If still no time_out in PM but has time_in_pm, assume 5:00 PM (standard office hours)
    if (!$daysData[$day]['has_out_pm'] && $daysData[$day]['has_in_pm']) {
        $daysData[$day]['time_out_pm'] = '5:00 PM';
        $daysData[$day]['has_out_pm'] = true;
    }
    
    // If still no time_out in AM but has time_in_am, assume 12:00 PM (lunch time)
    if (!$daysData[$day]['has_out_am'] && $daysData[$day]['has_in_am']) {
        $daysData[$day]['time_out_am'] = '12:00 PM';
        $daysData[$day]['has_out_am'] = true;
    }
}

// Close the statement
 $stmt->close();

// Close the database connection
 $db->close();
}
?>
<div class="container" id="container">
    <div class="header">
        <h5>Civil Service Form No. 48</h5>
        <h4>DAILY TIME RECORD</h4>
        <?php if (!empty($name)): ?>
            <h1><?php echo htmlspecialchars($name); ?></h1>
        <?php else: ?>
            <p>(Name)</p>
        <?php endif; ?>
    </div>

    <table class="info-table">
        <tr>
            <th>For the month of</th>
            <td><?php if (!empty($month)): ?>
            <?php echo htmlspecialchars($month); ?>
        <?php else: ?>
            <p>(Month)</p>
        <?php endif; ?></td>
            <td><?php echo $currentYear; ?></td>
            <td></td>
        </tr>
        <tr>
            <th>Official hours of arrival and departure:</th>
            <td>Regular Days: _______________</td>
            <td>Saturdays: _______________</td>
            <td></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Days</th>
                <th colspan="2">A.M.</th>
                <th colspan="2">P.M.</th>
                <th colspan="2">Undertime</th>
            </tr>
            <tr>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Departure</th>
                <th>Hours</th>
                <th>Minutes</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Loop through all the days of the month (1 to 31)
        for ($day = 1; $day <= 31; $day++) {
            // Check if time data exists for this day
            $timeData = isset($daysData[$day]) ? $daysData[$day] : [
                'time_in_am' => '',
                'time_out_am' => '',
                'time_in_pm' => '',
                'time_out_pm' => '',
                'has_in_am' => false,
                'has_out_am' => false,
                'has_in_pm' => false,
                'has_out_pm' => false
            ];
            
            // Determine if day is incomplete
            $isIncomplete = false;
            $rowClass = '';
            
            if ($timeData['has_in_am'] && !$timeData['has_out_am']) {
                $isIncomplete = true;
            }
            if ($timeData['has_in_pm'] && !$timeData['has_out_pm']) {
                $isIncomplete = true;
            }
            
            if ($isIncomplete) {
                $rowClass = 'class="incomplete-day"';
            }
        
            // Display the row for each day
            echo "<tr {$rowClass}>";
            echo "<td>" . $day . "</td>";
            
            // AM Arrival
            if ($timeData['time_in_am']) {
                echo "<td>" . htmlspecialchars($timeData['time_in_am']) . "</td>";
            } else {
                echo "<td class='no-time-in'>—</td>";
            }
            
            // AM Departure
            if ($timeData['time_out_am']) {
                echo "<td>" . htmlspecialchars($timeData['time_out_am']) . "</td>";
            } else {
                echo "<td class='no-time-out'>—</td>";
            }
            
            // PM Arrival
            if ($timeData['time_in_pm']) {
                echo "<td>" . htmlspecialchars($timeData['time_in_pm']) . "</td>";
            } else {
                echo "<td class='no-time-in'>—</td>";
            }
            
            // PM Departure
            if ($timeData['time_out_pm']) {
                echo "<td>" . htmlspecialchars($timeData['time_out_pm']) . "</td>";
            } else {
                echo "<td class='no-time-out'>—</td>";
            }
            
            echo "<td></td>"; // Placeholder for undertime
            echo "<td></td>"; // Placeholder for undertime
            echo "</tr>";
        }
        
        ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <td colspan="6"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>
            I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.
        </p>
        <div class="in-charge">
            <p>__________________________</p>
            <p>In-Charge</p>
        </div>
    </div>
    
    <!-- Legend for incomplete records -->
    <div class="mt-3" style="font-size: 0.85rem;">
        <p><strong>Legend:</strong></p>
        <p><span style="color: #dc3545;">—</span> No time recorded</p>
        <p><span style="background-color: #fff3cd; padding: 2px 5px;">Yellow row</span> Incomplete record (missing time out)</p>
    </div>
</div>

                        </div>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
            
        </div>
    
        <script type="text/javascript">
    $(document).ready(function() {
        $('#btn_print').on('click', function() {
            // Load print.php content into a hidden iframe
            var iframe = $('<iframe>', {
                id: 'printFrame',
                style: 'visibility:hidden; display:none'
            }).appendTo('body');

            // Set iframe source to print.php
            iframe.attr('src', 'dtr_print.php');

            // Wait for iframe to load
            iframe.on('load', function() {
                // Call print function of the iframe content
                this.contentWindow.print();

                // Remove the iframe after printing
                setTimeout(function() {
                    iframe.remove();
                }, 1000);
            });
        });
    });
</script>

        <a href="#" class="btn btn-lg btn-warning btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>
</html>