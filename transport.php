<?php
session_start();
include 'dbConnect.php';

if(!isset($_SESSION['guardianID'])){
    header("Location: LKLogin.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transport Schedule</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard-header">
    <h2>Transport Schedule</h2>
    <a href="dashboard.php" class="btn">Back</a>
</div>

<div class="table-container">

<table>

<tr>
    <th>Schedule ID</th>
    <th>Route</th>
    <th>Service Days / Date</th>
    <th>Departure Time</th>
    <th>Arrival Time</th>
    <th>Status</th>
</tr>

<?php

$result = $conn->query(
    "SELECT s.scheduleID, s.transpDate, s.serviceDays, s.departureTime,
            s.arrivalTime, s.status, r.routeName
     FROM tblschedule AS s
     LEFT JOIN tblroutes AS r ON r.routeID = s.routeID
     ORDER BY s.scheduleID"
);

while($row = $result->fetch_assoc()){
?>

<tr>
    <td>
        <?php echo htmlspecialchars($row['scheduleID'], ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['routeName'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['serviceDays'] ?: ($row['transpDate'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['departureTime'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['arrivalTime'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
    </td>
    
</tr>

<?php } ?>

</table>

</div>

</body>
</html>