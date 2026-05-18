<?php
// 1. Session MUST be started first before any other logic
session_start();
require_once 'db.php';

// 2. DEFENSIVE CHECK: Is the user logged in at all?
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

// 3. DEFENSIVE CHECK: Is the logged-in user an Admin (R01)? 
if (!isset($_SESSION['RoleID']) || $_SESSION['RoleID'] !== 'R01') {
    // If a regular student or committee member tries to access this page, boot them out
    header("Location: ClubList.php?msg=Unauthorized");
    exit();
}

// 4. Get ID from URL and sanitize it for MySQLi to prevent SQL injection
$clubID = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

if ($clubID) {
    // Start Database Transaction to guarantee data integrity
    mysqli_begin_transaction($conn);

    try {
        /* CRITICAL STEP: 
          If your database layout has an event attendance/registration table, 
          uncomment the line below to clear them first to prevent Foreign Key errors!
        */
        // mysqli_query($conn, "DELETE FROM event_attendance WHERE EventID IN (SELECT EventID FROM event WHERE ClubID = '$clubID')");

        // Step A: Delete all scheduled events assigned to this club first
        $sqlDeleteEvents = "DELETE FROM event WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteEvents);

        // Step B: Delete all student club memberships linked to this club
        $sqlDeleteMembers = "DELETE FROM membership WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteMembers);

        // Step C: Finally, drop the main club record itself cleanly
        $sqlDeleteClub = "DELETE FROM club WHERE ClubID = '$clubID'";
        mysqli_query($conn, $sqlDeleteClub);

        // Commit all changes since no SQL blocks failed
        mysqli_commit($conn);

        // Set the Toast notification message variable for your dashboard banner notice
        $_SESSION['flash_msg'] = "Club deleted permanently from database";
        header("Location: ClubList.php");
        exit();

    } catch (Exception $e) {
        // If anything trips an execution error, rollback completely so the DB doesn't corrupt
        mysqli_rollback($conn);
        header("Location: ClubList.php?error=db");
        exit();
    }
} else {
    // No ID parameter found in the URL link? Redirect safely back to the directory grid list.
    header("Location: ClubList.php");
    exit();
}
?>