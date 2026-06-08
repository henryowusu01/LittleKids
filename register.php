<?php
include 'dbConnect.php';

$message = "";

if(isset($_POST['register'])){

    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = "SELECT * FROM tblguardians WHERE email='$email'";
    $result = $conn->query($check);

    if($result->num_rows > 0){

        $message = "Email already registered!";

    }else{

        $sql = "INSERT INTO tblguardians
                (fullName,email,phoneNum,address,password)
                VALUES
                ('$fullName','$email','$phone','$address','$password')";

        if($conn->query($sql)){

            header("Location: LKLogin.php");
            exit();

        }else{

            $message = "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-container">

    <h2>Guardian Registration</h2>

    <form method="POST">

        <input type="text"
               name="fullName"
               placeholder="Full Name"
               required>

        <input type="email"
               name="email"
               placeholder="Email"
               required>

        <input type="text"
               name="phone"
               placeholder="Phone Number"
               required>

        <input type="text"
               name="address"
               placeholder="Address"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               required>

        <button type="submit" name="register">
            Register
        </button>

    </form>

    <p><?php echo $message; ?></p>

    <p>
        Already have an account?
        <a href="LKLogin.php">Login</a>
    </p>

</div>

</body>
</html>