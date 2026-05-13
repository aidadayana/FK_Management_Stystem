<?php
session_start();
require_once 'db.php';

/*Role Check*/
if (!isset($_SESSION['UserRole']) || $_SESSION['UserRole'] !== 'Admin') {
    // If NOT an admin, redirect back to the club list quietly.
    // This is how you avoid that white page with text.
    header("Location: ClubList.php");
    exit();
}

$clubID = $_GET['id'] ?? null;

if ($clubID) {
    try {
        $pdo->beginTransaction();

        //Delete related data 
        $pdo->prepare("DELETE FROM membership WHERE ClubID = ?")->execute([$clubID]);
        $pdo->prepare("DELETE FROM event WHERE ClubID = ?")->execute([$clubID]);
        //Delete the Club itself
        $pdo->prepare("DELETE FROM club WHERE ClubID = ?")->execute([$clubID]);
        $pdo->commit();
        
        // 6. Seamless Automatic Redirect
        header("Location: ClubList.php?msg=deleted");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // Go back with an error code if something fails
        header("Location: ClubList.php?error=db");
        exit();
    }
} else {
    // No ID found? Just go back.
    header("Location: ClubList.php");
    exit();
}