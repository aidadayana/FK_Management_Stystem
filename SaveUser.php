<?php

session_start();

require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

$name = $_POST['name'];

$email = $_POST['email'];

$password = $_POST['password'];

$role = $_POST['role'];

/* AUTO GENERATE USER ID */

$query = "SELECT COUNT(*) AS total FROM user";

$result = mysqli_query($conn, $query);

$row = mysqli_fetch_assoc($result);

$number = $row['total'] + 1;

$userID = "U00" . $number;

/* INSERT USER */

$stmt = $conn->prepare("
    INSERT INTO user
    (UserID, Name, Email, Password, RoleID, UserStatus)
    VALUES (?, ?, ?, ?, ?, 'Active')
");

$stmt->bind_param(
    "sssss",
    $userID,
    $name,
    $email,
    $password,
    $role
);

if($stmt->execute())
{
    header("Location: UserManagement.php");
}
else
{
    echo "Error Registering User";
}
?>