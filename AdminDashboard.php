<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

if($_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
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
            <p>Upcoming Events</p>
        </div>

        <div class="summary-card">
            <h3><?php echo $newUsers; ?></h3>
            <p>New Users (7 Days)</p>
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
function refreshData() {
    window.location.reload();
}

/* BAR CHART */
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