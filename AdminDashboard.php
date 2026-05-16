<?php
session_start();
require_once 'db.php';

/* LOGIN CHECK */
if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

/* ROLE CHECK (ADMIN ONLY) */
if($_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE QUERIES
========================= */

/* TOTAL STUDENTS (R02) */
$resStudents = mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE RoleID = 'R02'");
$totalStudents = mysqli_fetch_assoc($resStudents)['count'];

/* TOTAL CLUBS */
$resClubs = mysqli_query($conn, "SELECT COUNT(*) as count FROM club");
$totalClubs = mysqli_fetch_assoc($resClubs)['count'];

/* TOTAL EVENTS */
$resEvents = mysqli_query($conn, "SELECT COUNT(*) as count FROM event");
$totalEvents = mysqli_fetch_assoc($resEvents)['count'];

/* NEW USERS (LAST 7 DAYS - simple version) */
$resNew = mysqli_query($conn, "SELECT COUNT(*) as count FROM user");
$newUsers = mysqli_fetch_assoc($resNew)['count'];

/* ROLE DISTRIBUTION */
$resRole = mysqli_query($conn, "SELECT RoleID, COUNT(*) as count FROM user GROUP BY RoleID");

$roles = [];
$roleCounts = [];

while($row = mysqli_fetch_assoc($resRole))
{
    $roles[] = $row['RoleID'];
    $roleCounts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- HEADER -->
    <div class="header-row">
        <h1>ADMIN DASHBOARD</h1>

        <button onclick="refreshData()" class="btn btn-primary">
            🔄 Refresh Data
        </button>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">

        <div class="summary-card">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Total Students</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $totalClubs; ?></h3>
            <p>Total Clubs</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $totalEvents; ?></h3>
            <p>Total Events</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $newUsers; ?></h3>
            <p>Total Users</p>
        </div>

    </div>

    <!-- CHART SECTION -->
    <div class="header-row">
        <h3>System Overview</h3>
    </div>

    <div class="summary-grid">

        <div class="chart-container">
            <canvas id="roleChart"></canvas>
        </div>

    </div>

</div>

<script>
function refreshData()
{
    window.location.reload();
}

/* ROLE CHART */
const ctx = document.getElementById('roleChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($roles); ?>,
        datasets: [{
            label: 'Users by Role',
            data: <?php echo json_encode($roleCounts); ?>,
            backgroundColor: '#800000'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'User Role Distribution'
            },
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

</body>
</html>