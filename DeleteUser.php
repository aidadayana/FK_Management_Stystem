<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01') {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: UserManagement.php");
    exit();
}

$userID = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM user WHERE UserID = ?");
$stmt->bind_param("s", $userID);

if($stmt->execute()) {
    header("Location: UserManagement.php");
    exit();
} else {
    echo "Error deleting user";
}
?>