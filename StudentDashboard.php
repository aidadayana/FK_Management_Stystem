<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

if($_SESSION['RoleID'] != 'R02') {
    header("Location: login.php");
    exit();
}

/* Example placeholders (replace with DB later) */
$totalClubs = 3;
$totalEvents = 5;
$attendancePoints = 80;
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
        Welcome, <?php echo $_SESSION['Name']; ?>
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

    <!-- OPTIONAL SECTION (LIKE ADMIN CHART STYLE) -->
    <div class="header-row">
        <h3>Student Overview</h3>
    </div>

    <div class="summary-grid">

        <div class="summary-card">
            <h3>✔</h3>
            <p>Profile Complete</p>
        </div>

        <div class="summary-card">
            <h3>✔</h3>
            <p>Club Participation</p>
        </div>

    </div>

</div>

<script>
function refreshData() {
    window.location.reload();
}
</script>

</body>
</html>