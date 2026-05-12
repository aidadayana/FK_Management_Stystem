<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include 'db.php'; // connects to your database

$query = "SELECT e.*, c.ClubName FROM event e 
          JOIN club c ON e.ClubID = c.ClubID 
          WHERE e.EventStatus = 'active' 
          ORDER BY e.EventDate ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'Navigation.php'; ?>

<div class="container">
    <h2>Upcoming Events</h2>
    
    <div class="event-grid">
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div class="event-card">
            <h3><?= htmlspecialchars($row['Title']) ?></h3>
            <p><strong>Date:</strong> <?= $row['EventDate'] ?></p>
            <p><strong>Venue:</strong> <?= htmlspecialchars($row['Venue']) ?></p>
            <p><strong>Club:</strong> <?= htmlspecialchars($row['ClubName']) ?></p>
            
            <?php
            // Check how many people already registered
            $eventID = $row['EventID'];
            $countQuery = "SELECT COUNT(*) as total FROM event_registration 
                           WHERE EventID = $eventID AND RegistrationStatus = 'confirmed'";
            $countResult = mysqli_query($conn, $countQuery);
            $count = mysqli_fetch_assoc($countResult)['total'];
            $spotsLeft = $row['MaxParticipants'] - $count;
            ?>
            
            <?php if ($spotsLeft > 0) { ?>
                <p style="color:green">Spots left: <?= $spotsLeft ?></p>
                <a href="RegisterEvent.php?id=<?= $eventID ?>" class="btn">Register</a>
            <?php } else { ?>
                <p style="color:red">Event is FULL</p>
                <a href="Waitlist.php?id=<?= $eventID ?>" class="btn-outline">Join Waitlist</a>
            <?php } ?>
        </div>
    <?php } ?>
    </div>
</div>
</body>
</html>