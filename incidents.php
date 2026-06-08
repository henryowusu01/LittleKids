<?php

include 'dbConnect.php';

$result =
$conn->query(
"SELECT * FROM tblincidents"
);

while($row =
$result->fetch_assoc()){

echo "<h3>" .
$row['emergAlert'] .
"</h3>";

echo "<p>" .
$row['inDesc'] .
"</p>";

}
?>