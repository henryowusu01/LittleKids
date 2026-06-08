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

<header>
    <h1>
        Welcome,
        <?php echo $_SESSION['fullName']; ?>
    </h1>
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
            <h3>Children</h3>
            <p>Manage child information</p>
        </div>

        <div class="card">
            <h3>Incidents</h3>
            <p>View transport incidents</p>
        </div>

    </div>

    <br>

    <center>
        <a href="logout.php" class="btn">Logout</a>
    </center>

</div>

</body>
</html>