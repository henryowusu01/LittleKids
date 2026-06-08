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
    <title>Routes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard-header">
    <h2> Routes</h2>
    <a href="dashboard.php" class="btn"> Back </a>
</div>

<div class="table-container">

<table>

<tr>
    <th>Route ID</th>
    <th>Route Name</th>
    <th>Pickup Point</th>
    <th>Drop Off Point</th>
    <th>Status</th>
</tr>

<?php

$result = $conn->query("SELECT * FROM tblroutes");

while($row = $result->fetch_assoc()){
?>

<tr>
    <td><?php echo $row['routeID']; ?></td>
    <td><?php echo $row['routeName']; ?></td>
    <td><?php echo $row['pickup']; ?></td>
    <td><?php echo $row['dropoff']; ?></td>
    <td><?php echo $row['routeStatus']; ?></td>
</tr>

<?php } ?>

</table>

</div>

</body>
</html>