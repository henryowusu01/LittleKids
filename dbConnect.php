<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "lktransport";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $dbname
);

if($conn->connect_error){
    die("Connection failed");
}

?>