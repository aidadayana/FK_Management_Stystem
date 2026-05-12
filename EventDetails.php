<?php
// EventDetails.php - View Single Event + Register / Cancel
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: Login.php");
    exit();
}

$userID   = $_SESSION['UserID'];
$eventID  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventID === 0) {
    header("Location: EventList.php");
    exit();
}

// Fetch event
$sql = "
    SELECT e.*, c.ClubName, c.AdvisorName,
           COUNT(CASE WHEN er.RegistrationStatus = 'Confirmed' THEN 1 END) AS RegisteredCount
    FROM event e
    LEFT JOIN club c ON e.ClubID = c.ClubID
    LEFT JOIN event_registration er ON e.EventID = er.EventID
    WHERE e.EventID = $eventID
    GROUP BY e.EventID
";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    header("Location: EventList.php");
    exit();
}
$ev = $res->fetch_assoc();

// Check if user is already registered
$chk = $conn->query("SELECT * FROM event_registration WHERE UserID='$userID' AND EventID=$eventID AND RegistrationStatus='Confirmed'");
$alreadyRegistered = ($chk && $chk->num_rows > 0);

// Check waitlist
$wChk = $conn->query("SELECT * FROM waitlist WHERE UserID='$userID' AND EventID=$eventID AND WaitlistStatus='Waiting'");
$onWaitlist = ($wChk && $wChk->num_rows > 0);

$isFull = $ev['RegisteredCount'] >= $ev['MaxParticipants'];
$isPast = strtotime($ev['EventDate']) < strtotime(date('Y-m-d'));
$pct    = $ev['MaxParticipants'] > 0 ? ($ev['RegisteredCount'] / $ev['MaxParticipants']) * 100 : 0;

$successMsg = $errorMsg = '';
if (isset($_SESSION['event_msg'])) {
    $successMsg = $_SESSION['event_msg'];
    unset($_SESSION['event_msg']);
}
if (isset($_SESSION['event_err'])) {
    $errorMsg = $_SESSION['event_err'];
    unset($_SESSION['event_err']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($ev['Title']) ?> | FK Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="container">

    <a href="EventList.php">← Back to Events</a>

    <?php if ($successMsg): ?>
        <div class="success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="event-wrapper">

        <!-- Event Details -->
        <div class="event-card">
            <div class="event-header">
                <h2><?= htmlspecialchars($ev['Title']) ?></h2>
                <p>By: <?= htmlspecialchars($ev['ClubName']) ?></p>
            </div>
            
            <div class="event-details">
                <p>📝 <?= nl2br(htmlspecialchars($ev['Description'])) ?></p>
                <p>📅 <?= date('l, d F Y', strtotime($ev['EventDate'])) ?></p>
                <p>🕐 <?= date('h:i A', strtotime($ev['EventTime'])) ?></p>
                <p>📍 <?= htmlspecialchars($ev['Venue']) ?></p>
                <p>👨‍🏫 Advisor: <?= htmlspecialchars($ev['AdvisorName'] ?? '-') ?></p>
            </div>
        </div>

        <!-- Registration Panel -->
        <div class="register-panel">
            <h3>Registration</h3>
            
            <p>Seats: <?= $ev['RegisteredCount'] ?> / <?= $ev['MaxParticipants'] ?></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= min($pct,100) ?>%"></div>
            </div>

            <?php if ($alreadyRegistered): ?>
                <div class="info">✔ You are registered.</div>
                <form method="POST" action="CancelRegistration.php" onsubmit="return confirm('Cancel registration?')">
                    <input type="hidden" name="EventID" value="<?= $eventID ?>">
                    <button type="submit" class="btn cancel">Cancel Registration</button>
                </form>

            <?php elseif ($onWaitlist): ?>
                <div class="info">⏳ You are on the waitlist.</div>
                <button class="btn disabled" disabled>On Waitlist</button>

            <?php elseif ($isPast): ?>
                <button class="btn disabled" disabled>Event Ended</button>

            <?php elseif ($isFull): ?>
                <div class="error">Event is fully booked.</div>
                <form method="POST" action="RegisterEvent.php">
                    <input type="hidden" name="EventID" value="<?= $eventID ?>">
                    <input type="hidden" name="action" value="waitlist">
                    <button type="submit" class="btn waitlist">Join Waitlist</button>
                </form>

            <?php else: ?>
                <form method="POST" action="RegisterEvent.php">
                    <input type="hidden" name="EventID" value="<?= $eventID ?>">
                    <input type="hidden" name="action" value="register">
                    <button type="submit" class="btn register">Register Now</button>
                </form>
                <p class="small"><?= $ev['MaxParticipants'] - $ev['RegisteredCount'] ?> seats left</p>
            <?php endif; ?>
        </div>

    </div>

</div>
</body>
</html>