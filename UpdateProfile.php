<?php

session_start();

require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$name = $_POST['name'];

$email = $_POST['email'];

$stmt = $conn->prepare("
    UPDATE user
    SET Name = ?, Email = ?
    WHERE UserID = ?
");

$stmt->bind_param(
    "sss",
    $name,
    $email,
    $userID
);

if($stmt->execute())
{
    $_SESSION['Name'] = $name;

    header("Location: Profile.php");
}
else
{
    echo "Update Failed";
}
?>