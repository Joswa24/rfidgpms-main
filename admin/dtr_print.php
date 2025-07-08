<?php
//session_start();

// Check if required session variables are set
if (!isset($_SESSION['id']) || !isset($_SESSION['name']) || !isset($_SESSION['month'])) {
    // Alternatively, check for GET parameters if passed that way
    if (!isset($_GET['id']) || !isset($_GET['name']) || !isset($_GET['month'])) {
        die("Error: Required data is missing. Please go back and try again.");
    } else {
        // If GET parameters are present, use them
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        $name = htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');
        $month = htmlspecialchars($_GET['month'], ENT_QUOTES, 'UTF-8');
    }
} else {
    // Use session variables
    $id = filter_var($_SESSION['id'], FILTER_VALIDATE_INT);
    $name = htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8');
    $month = htmlspecialchars($_SESSION['month'], ENT_QUOTES, 'UTF-8');
}

// Validate the ID
if (!$id) {
    die("Invalid personnel ID.");
}

// Include the database connection
include '../connection.php';

// Query to fetch personnel details
$personnel = [];
$sql = "SELECT first_name, last_name FROM personell WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $db->error);
}

$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $personnel = $row;
} else {
    die("No personnel found with ID: $id");
}
$stmt->close();

// Get current year and month number
$currentYear = date('Y');
$monthNum = date('m', strtotime($month));
if (!$monthNum) {
    die("Invalid month format. Please use a valid month name (e.g., 'January').");
}

// Initialize the array to store the data for each day
$daysData = [];

// SQL query to fetch all logs for the specified month and personnel ID
$sql = "SELECT date_logged, time_in_am, time_out_am, time_in_pm, time_out_pm 
        FROM personell_logs 
        WHERE MONTH(date_logged) = ? AND YEAR(date_logged) = ? AND personnel_id = ?
        ORDER BY date_logged";

$stmt = $db->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $db->error);
}

$stmt->bind_param("iii", $monthNum, $currentYear, $id);

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $day = (int)date('d', strtotime($row['date_logged']));
    
    // Format times properly
    $row['time_in_am'] = (!empty($row['time_in_am']) && $row['time_in_am'] != '?') ? date('h:i A', strtotime($row['time_in_am'])) : '';
    $row['time_out_am'] = (!empty($row['time_out_am']) && $row['time_out_am'] != '?') ? date('h:i A', strtotime($row['time_out_am'])) : '';
    $row['time_in_pm'] = (!empty($row['time_in_pm']) && $row['time_in_pm'] != '?') ? date('h:i A', strtotime($row['time_in_pm'])) : '';
    $row['time_out_pm'] = (!empty($row['time_out_pm']) && $row['time_out_pm'] != '?') ? date('h:i A', strtotime($row['time_out_pm'])) : '';
    
    $daysData[$day] = $row;
}

$stmt->close();
$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Time Record - <?php echo $name; ?> - <?php echo $month; ?> <?php echo $currentYear; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            text-decoration: underline;
            margin: 10px 0;
        }

        .header h4, .header h5 {
            margin: 5px 0;
        }

        .header h5 {
            font-size: 10px;
            text-align: left;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table th, .info-table td {
            border: none;
            padding: 5px;
            text-align: left;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
        }

        .footer p {
            text-align: justify;
            margin: 5px 0;
        }

        .in-charge {
            text-align: right;
            margin-top: 30px;
        }

        /* Print-specific styles */
        @media print {
            body {
                font-size: 10px;
            }

            .container {
                padding: 10px;
            }

            th, td {
                padding: 3px;
                font-size: 9px;
            }

            .header h1 {
                font-size: 16px;
            }

            @page {
                size: A4;
                margin: 10mm;
            }

            .page-break {
                page-break-after: always;
            }
        }

        /* Two-column layout for printing */
        .table-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }

        .table-column {
            flex: 1;
            min-width: 48%;
        }

        @media print {
            .table-wrapper {
                display: block;
            }
            
            .table-column {
                page-break-after: always;
                margin-bottom: 20mm;
            }
            
            .table-column:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="table-wrapper">
        <!-- Original Copy -->
        <div class="table-column">
            <div class="container">
                <div class="header">
                    <h5>Civil Service Form No. 48</h5>
                    <h4>DAILY TIME RECORD</h4>
                    <h1><?php echo $name; ?></h1>
                </div>

                <table class="info-table">
                    <tr>
                        <th style="width: 30%">For the month of</th>
                        <td style="width: 40%"><?php echo $month; ?></td>
                        <td style="width: 30%"><?php echo $currentYear; ?></td>
                    </tr>
                    <tr>
                        <th>Official hours for:</th>
                        <td>Regular Days: 8:00 AM - 5:00 PM</td>
                        <td>Saturdays: 8:00 AM - 12:00 PM</td>
                    </tr>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 10%">Day</th>
                            <th colspan="2" style="width: 30%">A.M.</th>
                            <th colspan="2" style="width: 30%">P.M.</th>
                            <th colspan="2" style="width: 30%">Undertime</th>
                        </tr>
                        <tr>
                            <th style="width: 15%">Arrival</th>
                            <th style="width: 15%">Departure</th>
                            <th style="width: 15%">Arrival</th>
                            <th style="width: 15%">Departure</th>
                            <th style="width: 15%">Hours</th>
                            <th style="width: 15%">Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthNum, $currentYear);
                        for ($day = 1; $day <= $daysInMonth; $day++): 
                            $date = "$currentYear-$monthNum-$day";
                            $dayOfWeek = date('N', strtotime($date));
                            $isSaturday = ($dayOfWeek == 6);
                            $isSunday = ($dayOfWeek == 7);
                        ?>
                            <tr>
                                <td><?php echo $day; ?></td>
                                <td><?php echo isset($daysData[$day]['time_in_am']) ? $daysData[$day]['time_in_am'] : ''; ?></td>
                                <td><?php echo isset($daysData[$day]['time_out_am']) ? $daysData[$day]['time_out_am'] : ''; ?></td>
                                <td><?php 
                                    if ($isSaturday || $isSunday) {
                                        echo ''; // No PM for Saturdays and Sundays
                                    } else {
                                        echo isset($daysData[$day]['time_in_pm']) ? $daysData[$day]['time_in_pm'] : '';
                                    }
                                ?></td>
                                <td><?php 
                                    if ($isSaturday || $isSunday) {
                                        echo ''; // No PM for Saturdays and Sundays
                                    } else {
                                        echo isset($daysData[$day]['time_out_pm']) ? $daysData[$day]['time_out_pm'] : '';
                                    }
                                ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor; ?>
                        <!-- Fill remaining rows if month has less than 31 days -->
                        <?php if ($daysInMonth < 31): ?>
                            <?php for ($day = $daysInMonth + 1; $day <= 31; $day++): ?>
                                <tr>
                                    <td><?php echo $day; ?></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="footer">
                    <p>I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.</p>
                    <div class="in-charge">
                        <p>__________________________</p>
                        <p><strong>In-Charge</strong></p>
                    </div>
                    <p style="text-align: center; margin-top: 20px;">(Original Copy)</p>
                </div>
            </div>
        </div>

        <!-- Duplicate Copy -->
        <div class="table-column">
            <div class="container">
                <div class="header">
                    <h5>Civil Service Form No. 48</h5>
                    <h4>DAILY TIME RECORD</h4>
                    <h1><?php echo $name; ?></h1>
                </div>

                <table class="info-table">
                    <tr>
                        <th style="width: 30%">For the month of</th>
                        <td style="width: 40%"><?php echo $month; ?></td>
                        <td style="width: 30%"><?php echo $currentYear; ?></td>
                    </tr>
                    <tr>
                        <th>Official hours for:</th>
                        <td>Regular Days: 8:00 AM - 5:00 PM</td>
                        <td>Saturdays: 8:00 AM - 12:00 PM</td>
                    </tr>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" style="width: 10%">Day</th>
                            <th colspan="2" style="width: 30%">A.M.</th>
                            <th colspan="2" style="width: 30%">P.M.</th>
                            <th colspan="2" style="width: 30%">Undertime</th>
                        </tr>
                        <tr>
                            <th style="width: 15%">Arrival</th>
                            <th style="width: 15%">Departure</th>
                            <th style="width: 15%">Arrival</th>
                            <th style="width: 15%">Departure</th>
                            <th style="width: 15%">Hours</th>
                            <th style="width: 15%">Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        for ($day = 1; $day <= $daysInMonth; $day++): 
                            $date = "$currentYear-$monthNum-$day";
                            $dayOfWeek = date('N', strtotime($date));
                            $isSaturday = ($dayOfWeek == 6);
                            $isSunday = ($dayOfWeek == 7);
                        ?>
                            <tr>
                                <td><?php echo $day; ?></td>
                                <td><?php echo isset($daysData[$day]['time_in_am']) ? $daysData[$day]['time_in_am'] : ''; ?></td>
                                <td><?php echo isset($daysData[$day]['time_out_am']) ? $daysData[$day]['time_out_am'] : ''; ?></td>
                                <td><?php 
                                    if ($isSaturday || $isSunday) {
                                        echo ''; // No PM for Saturdays and Sundays
                                    } else {
                                        echo isset($daysData[$day]['time_in_pm']) ? $daysData[$day]['time_in_pm'] : '';
                                    }
                                ?></td>
                                <td><?php 
                                    if ($isSaturday || $isSunday) {
                                        echo ''; // No PM for Saturdays and Sundays
                                    } else {
                                        echo isset($daysData[$day]['time_out_pm']) ? $daysData[$day]['time_out_pm'] : '';
                                    }
                                ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor; ?>
                        <!-- Fill remaining rows if month has less than 31 days -->
                        <?php if ($daysInMonth < 31): ?>
                            <?php for ($day = $daysInMonth + 1; $day <= 31; $day++): ?>
                                <tr>
                                    <td><?php echo $day; ?></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
                
                <div class="footer">
                    <p>I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from the office.</p>
                    <div class="in-charge">
                        <p>__________________________</p>
                        <p><strong>In-Charge</strong></p>
                    </div>
                    <p style="text-align: center; margin-top: 20px;">(Duplicate Copy)</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>