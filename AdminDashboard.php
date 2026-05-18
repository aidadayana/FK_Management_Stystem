<?php
session_start();
require_once 'db.php';

/* LOGIN CHECK */
if(!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

/* ROLE CHECK (ADMIN ONLY) */
if($_SESSION['RoleID'] != 'R01') {
    header("Location: login.php");
    exit();
}

/* TOTAL STUDENTS */
$resStudents = mysqli_query($conn, "SELECT COUNT(*) as count FROM user WHERE RoleID = 'R02'");
$totalStudents = mysqli_fetch_assoc($resStudents)['count'];

/* TOTAL ACTIVE CLUBS */
$resClubs = mysqli_query($conn, "SELECT COUNT(*) as count FROM club WHERE ClubStatus = 'Active'");
$totalClubs = mysqli_fetch_assoc($resClubs)['count'];

/* TOTAL EVENTS */
$resEvents = mysqli_query($conn, "SELECT COUNT(*) as count FROM event");
$totalEvents = mysqli_fetch_assoc($resEvents)['count'];

/* TOTAL USERS */
$resUsers = mysqli_query($conn, "SELECT COUNT(*) as count FROM user");
$totalUsers = mysqli_fetch_assoc($resUsers)['count'];

/* ROLE DISTRIBUTION */
$resRole = mysqli_query($conn, "SELECT RoleID, COUNT(*) as count FROM user GROUP BY RoleID");

$roles = [];
$roleCounts = [];

while($row = mysqli_fetch_assoc($resRole)) {
    $roles[] = $row['RoleID'];
    $roleCounts[] = $row['count'];
}

/* NEW CHART DATA */
$resActiveClubs = mysqli_query($conn, "SELECT COUNT(*) as count FROM club WHERE ClubStatus = 'Active'");
$activeClubs = mysqli_fetch_assoc($resActiveClubs)['count'];

$resEventCount = mysqli_query($conn, "SELECT COUNT(*) as count FROM event");
$totalEventCount = mysqli_fetch_assoc($resEventCount)['count'];
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
            <p>Total Active Clubs</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $totalEvents; ?></h3>
            <p>Total Events</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>

    </div>

    <!-- CHART SECTION -->
    <div class="header-row">
        <h3>System Overview</h3>
    </div>

    <div class="summary-grid">

        <!-- ROLE CHART -->
        <div class="chart-container">
            <canvas id="roleChart"></canvas>
        </div>

        <!-- CLUB VS EVENT CHART -->
        <div class="chart-container">
            <canvas id="clubEventChart"></canvas>
        </div>

    </div>

</div>

<script>
function refreshData() {
    window.location.reload();
}

/* ROLE BAR CHART */
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

/* CLUB VS EVENT DOUGHNUT CHART */
const ctx2 = document.getElementById('clubEventChart').getContext('2d');

new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Active Clubs', 'Events'],
        datasets: [{
            data: [
                <?php echo $activeClubs; ?>,
                <?php echo $totalEventCount; ?>
            ],
            backgroundColor: ['#800000', '#D7B7A3']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Club vs Event Overview'
            }
        }
    }
});
</script>

</body>
</html>