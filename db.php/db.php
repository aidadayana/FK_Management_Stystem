<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "fk_management";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Database Connected Successfully";

?>