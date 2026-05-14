<?php
session_start(); 
require_once 'db.php';

//get Total Clubs
$resTotal = mysqli_query($conn, "SELECT COUNT(*) as count FROM club");
$totalClubs = mysqli_fetch_assoc($resTotal)['count'];

//get Active Clubs
$resActive = mysqli_query($conn, "SELECT COUNT(*) as count FROM club WHERE ClubStatus = 'Active'");
$activeClubs = mysqli_fetch_assoc($resActive)['count'];

//get Total Students
$resStudents = mysqli_query($conn, "SELECT COUNT(DISTINCT UserID) as count FROM user WHERE RoleID = 'Student'");
$totalStudents = mysqli_fetch_assoc($resStudents)['count'];

//student Bar Chart
$memberStatsQuery = "
    SELECT c.ClubName, COUNT(m.UserID) as member_count 
    FROM club c 
    LEFT JOIN membership m ON c.ClubID = m.ClubID 
    GROUP BY c.ClubID
";
$resStats = mysqli_query($conn, $memberStatsQuery);

$clubNames = [];
$counts = [];

while ($row = mysqli_fetch_assoc($resStats)) {
    $clubNames[] = $row['ClubName'];
    $counts[] = $row['member_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard | Admin</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include('Navigation.php'); ?>

    <div class="main-content">
        <div class="header-row">
            <h1>CLUB DASHBOARD</h1>
            <button onclick="refreshData()" class="btn btn-primary">
                <span class="refresh-icon">🔄</span> Refresh Data
            </button>
        </div>
        
        <div class="summary-grid">
            <div class="summary-card">
                <h3><?php echo $totalClubs; ?></h3>
                <p>Total Clubs</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $activeClubs; ?></h3>
                <p>Active Clubs</p>
            </div>
            <div class="summary-card">
                <h3><?php echo $totalStudents; ?></h3>
                <p>Total Students</p>
            </div>
        </div>

        <div class="header-row">
            <h3>Visual Statistics</h3>
        </div>
        
        <div class="summary-grid">
            <div class="chart-container pie-box">
                <canvas id="statusPieChart"></canvas>
            </div>
            <div class="chart-container bar-box">
                <canvas id="memberBarChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // 1. DATA REFRESH FUNCTION
        function refreshData() {
            const btn = document.querySelector('.btn-refresh');
            btn.innerHTML = "Updating...";
            window.location.reload();
        }

        // 2. CHART COLORS (Grabbing from CSS Variables if defined, else fallback)
        const MAROON = '#800000';
        const LIGHT_GRAY = '#dddddd';

        // 3. PIE CHART INITIALIZATION
        const ctxPie = document.getElementById('statusPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [<?php echo $activeClubs; ?>, <?php echo ($totalClubs - $activeClubs); ?>],
                    backgroundColor: [MAROON, LIGHT_GRAY]
                }]
            },
            options: { 
                maintainAspectRatio: false,
                plugins: { 
                    title: { display: true, text: 'Club Activity Status' },
                    legend: { position: 'bottom' }
                } 
            }
        });

        // 4. BAR CHART INITIALIZATION
        const ctxBar = document.getElementById('memberBarChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($clubNames); ?>,
                datasets: [{
                    label: 'Number of Members',
                    data: <?php echo json_encode($counts); ?>,
                    backgroundColor: MAROON
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    } 
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Members per Club' }
                }
            }
        });
    </script>
</body>
</html>