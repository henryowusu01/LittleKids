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
    <th>Departure Time</th>
    <th>Arrival Time</th>
    <th>Status</th>
</tr>

<?php

$result = $conn->query("SELECT * FROM tblschedule");

while($row = $result->fetch_assoc()){
?>

<tr>
    <td><?php echo $row['scheduleID']; ?></td>
    <td><?php echo $row['departureTime']; ?></td>
    <td><?php echo $row['arrivalTime']; ?></td>
    <td><?php echo $row['status']; ?></td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>