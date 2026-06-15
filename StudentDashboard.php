<?php
session_start();
require_once 'db.php';

/* CHECK LOGIN */
if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R02') {
    header("Location: login.php");
    exit();
}

$currentUserID = $_SESSION['UserID'];

/* =========================
   DASHBOARD STATS (PLACEHOLDER)
   ========================= */
$totalClubs = 0;
$totalEvents = 0;
$attendancePoints = 0;

/* GET JOINED CLUBS */
$query = "
    SELECT c.*, m.JoinDate
    FROM membership m
    INNER JOIN club c ON m.ClubID = c.ClubID
    WHERE m.UserID = ?
    AND m.MemberStatus = 'Active'
    ORDER BY c.ClubName ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentUserID);
$stmt->execute();
$result = $stmt->get_result();

$clubs = [];

while($row = $result->fetch_assoc()) {
    $clubs[] = $row;
}

$totalClubs = count($clubs);

/* TOTAL EVENTS (OPTIONAL - adjust if you have event registration table) */
$eventQuery = "
    SELECT COUNT(*) AS total
    FROM event_registration
    WHERE UserID = ?
";

$stmt2 = $conn->prepare($eventQuery);
$stmt2->bind_param("s", $currentUserID);
$stmt2->execute();
$result2 = $stmt2->get_result();
$totalEvents = $result2->fetch_assoc()['total'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- HEADER -->
    <div class="header-row">
        <h1>STUDENT DASHBOARD</h1>

        <button onclick="refreshData()" class="btn btn-primary">
            🔄 Refresh Data
        </button>
    </div>

    <p class="club-subtitle">
        Welcome, <?php echo htmlspecialchars($_SESSION['Name']); ?>
    </p>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">

        <div class="summary-card">
            <h3><?php echo $totalClubs; ?></h3>
            <p>Joined Clubs</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $totalEvents; ?></h3>
            <p>Registered Events</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $attendancePoints; ?></h3>
            <p>Attendance Points</p>
        </div>

        <div class="summary-card">
            <h3>Active</h3>
            <p>Status</p>
        </div>

    </div>

    <!-- =========================
         MY CLUBS SECTION
         ========================= -->
    <div class="header-row">
        <h2>MY CLUBS</h2>
    </div>

    <div class="club-grid">

        <?php if(empty($clubs)): ?>

            <p>You have not joined any clubs yet.</p>

        <?php else: ?>

            <?php foreach($clubs as $club): ?>

                <div class="club-card">

                    <div>

                        <div class="club-header-simple">
                            <h3><?php echo htmlspecialchars($club['ClubName']); ?></h3>
                        </div>

                        <p class="advisor-text">
                            Advisor:
                            <strong>
                                <?php echo htmlspecialchars($club['ClubAdvisor'] ?? 'TBA'); ?>
                            </strong>
                        </p>

                        <p class="member-date">
                            Joined On:
                            <strong>
                                <?php echo date('d M Y', strtotime($club['JoinDate'])); ?>
                            </strong>
                        </p>

                        <p class="description">
                            <?php echo htmlspecialchars($club['ClubDesc']); ?>
                        </p>

                    </div>

                    <div class="action-footer">

                        <a href="ClubDetails.php?id=<?php echo urlencode($club['ClubID']); ?>"
                           class="btn btn-primary">
                            View Details
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<script>
function refreshData() {
    window.location.reload();
}
</script>

</body>
</html>