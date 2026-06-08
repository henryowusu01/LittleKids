<?php

session_start();
include 'dbConnect.php';

$message = "";

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM tblguardians
            WHERE email='$email'";

    $result = $conn->query($sql);

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){

            $_SESSION['guardianID'] = $row['guardianID'];
            $_SESSION['fullName'] = $row['fullName'];

            header("Location: dashboard.php");
            exit();

        }else{

            $message = "Incorrect Password!";
        }

    }else{

        $message = "Account not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-container">

    <h2>Login</h2>

    <form method="POST">

        <input type="email"
               name="email"
               placeholder="Email"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               required>

        <button type="submit" name="login">
            Login
        </button>

    </form>

    <p><?php echo $message; ?></p>

    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>