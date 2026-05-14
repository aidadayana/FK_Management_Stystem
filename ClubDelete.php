<?php
// CRITICAL FIX: Session must be started FIRST.
session_start();
require_once 'db.php';

/* Role Check */
// Only Admins are allowed to perform the delete action.
if (!isset($_SESSION['UserRole']) || $_SESSION['UserRole'] !== 'Admin') {
    // Redirect quietly to avoid "Unauthorized access" text pages.
    header("Location: ClubList.php");
    exit();
}

// Get ID from URL and sanitize it for MySQLi to prevent SQL injection.
$clubID = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if ($clubID) {
    // Start Transaction in MySQLi to ensure data integrity.
    mysqli_begin_transaction($conn);

    try {
        // Delete all memberships
        $sqlDeleteMembers = "DELETE FROM membership WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteMembers);

        // Delete all events 
        $sqlDeleteEvents = "DELETE FROM event WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteEvents);

        //delete club
        $sqlDeleteClub = "DELETE FROM club WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteClub);

        mysqli_commit($conn);

        header("Location: ClubList.php?msg=deleted");
        exit();

    } catch (Exception $e) {
        // If any step fails, undo all deletions.
        mysqli_rollback($conn);
        header("Location: ClubList.php?error=db");
        exit();
    }
} else {
    // No ID found in the URL? Redirect back to the list.
    header("Location: ClubList.php");
    exit();
}
?>