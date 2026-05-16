<?php
require_once 'db.php';
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

$userID = $_SESSION['UserID'];
$clubID = $_POST['ClubID'];
$today  = date('Y-m-d');

// STEP 1: Check if the record already exists
$checkQuery = "SELECT * FROM membership WHERE UserID = '$userID' AND ClubID = '$clubID'";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) > 0) {
    // Student is already a member
    echo "<script>alert('You are already a member of this club!'); window.location.href='ClubList.php';</script>";
} else {
    // STEP 2: Proceed with the join
    $insertQuery = "INSERT INTO membership (UserID, ClubID, MemberRoleID, JoinDate, MemberStatus) 
                    VALUES ('$userID', '$clubID', 'R006', '$today', 'Active')";
    
    if (mysqli_query($conn, $insertQuery)) {
        echo "<script>alert('Successfully joined the club!'); window.location.href='ClubList.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>