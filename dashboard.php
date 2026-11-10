<?php

session_start();

if(!isset($_SESSION['guardianID'])){

    header("Location: LKLogin.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

<header class="dashboard-header-bar">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['fullName'], ENT_QUOTES, 'UTF-8') ?></h1>
</header>

    <div class="card-container">

        <div class="card">
            <h3> 
                 <a href="routes.php">Routes</a>
        </h3>
             <p>View transport routes</p>
              
        </div>

        <div class="card">
            <h3> 
                <a href="transport.php">Schedules</a>
            </h3>
            <p>View transport schedules</p>
        </div>

        <div class="card">
            <h3><a href="childreninfo.php">Children</a></h3>
            <p>Manage child information</p>
        </div>

        <div class="card">
            <h3><a href="incidents.php">Incidents</a></h3>
            <p>View transport incidents</p>
        </div>

    </div>

    <br>

    <center>
        <a href="logout.php" class="btn">Logout</a>
    </center>

</div>

<div class="logout-confirmation" id="logout-confirmation" hidden>
    <div class="logout-confirmation__dialog" role="dialog" aria-modal="true" aria-labelledby="logout-confirmation-title">
        <h2 id="logout-confirmation-title">Are you sure you want to logout?</h2>
        <div class="logout-confirmation__actions">
            <button type="button" class="logout-confirmation__yes" id="logout-confirmation-yes">Yes</button>
            <button type="button" class="logout-confirmation__no" id="logout-confirmation-no">No</button>
        </div>
    </div>
</div>

<script src="Script.js"></script>
</body>
</html>