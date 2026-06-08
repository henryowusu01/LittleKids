<?php

include 'dbConnect.php';

if(isset($_POST['register'])){

    $name = $_POST['fullname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $password =
    password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );

    $sql =
    "INSERT INTO tblguardians
    (phoneNum,fullName,email,password,address)

    VALUES

    ('$phone','$name','$email','$password','$address')";

    $conn->query($sql);
}
?>

<form method="POST">

<input type="text" name="fullname" placeholder="Full Name">

<input type="text" name="phone" placeholder="Phone Number">

<input type="email" name="email" placeholder="Email">

<input type="text" name="address" placeholder="Address">

<input type="password" name="password" placeholder="Password">

<button name="register">
Register
</button>

</form>