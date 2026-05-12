<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
include 'db.php';

$eventID = $_GET['id'];
$userID = $_SESSION['user_id'];

// Check if already registered
$check = mysqli_query($conn, "SELECT * FROM event_registration 
                               WHERE UserID='$userID' AND EventID='$eventID'");
if (mysqli_num_rows($check) > 0) {
    echo "<p>You are already registered!</p>";
    exit();
}

// Check capacity
$eventQuery = mysqli_query($conn, "SELECT * FROM event WHERE EventID='$eventID'");
$event = mysqli_fetch_assoc($eventQuery);

$countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM event_registration 
                                    WHERE EventID='$eventID' AND RegistrationStatus='confirmed'");
$count = mysqli_fetch_assoc($countQuery)['total'];

if ($count >= $event['MaxParticipants']) {
    echo "<p>Sorry, this event is full. <a href='Waitlist.php?id=$eventID'>Join waitlist</a></p>";
    exit();
}

// Register the student
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = date('Y-m-d');
    mysqli_query($conn, "INSERT INTO event_registration 
                          (UserID, EventID, RegistrationDate, RegistrationStatus) 
                          VALUES ('$userID', '$eventID', '$date', 'confirmed')");
    echo "<p>Registration successful!</p>";
    header("Location: EventList.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Register for Event</title><link rel="stylesheet" href="style.css"></head>
<body>
<?php include 'Navigation.php'; ?>
<div class="container">
    <h2>Register: <?= htmlspecialchars($event['Title']) ?></h2>
    <p>Date: <?= $event['EventDate'] ?> at <?= $event['EventTime'] ?></p>
    <p>Venue: <?= htmlspecialchars($event['Venue']) ?></p>
    <p>Spots remaining: <?= $event['MaxParticipants'] - $count ?></p>
    
    <form method="POST">
        <button type="submit" class="btn">Confirm Registration</button>
        <a href="EventList.php" class="btn-outline">Cancel</a>
    </form>
</div>
</body>
</html>