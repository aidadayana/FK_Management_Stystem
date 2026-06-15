<?php

session_start();

require_once 'db.php';

/* CHECK IF USER LOGGED IN */

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

/* ONLY ADMIN CAN ACCESS */

if (!isset($_SESSION['RoleID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    echo "<script>
        alert('Access denied. Admin or Committee only.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

/* GET TOTAL CLUBS */

$resTotal = mysqli_query($conn, "SELECT COUNT(*) as count FROM club");
$totalClubs = mysqli_fetch_assoc($resTotal)['count'];

/* GET ACTIVE CLUBS */
$resActive = mysqli_query($conn, "SELECT COUNT(*) as count FROM club WHERE ClubStatus = 'Active'");
$activeClubs = mysqli_fetch_assoc($resActive)['count'];


/* GET TOTAL STUDENTS (Standard Students R02 + Committee Students R03) */
$resStudents = mysqli_query($conn, "SELECT COUNT(DISTINCT UserID) as count FROM user WHERE RoleID IN ('R02', 'R03')");
$totalStudents = mysqli_fetch_assoc($resStudents)['count'];

/* TOTAL MEMBERSHIPS */
$resMemberships = mysqli_query($conn,
    "SELECT COUNT(*) as count FROM membership");
$totalMemberships = mysqli_fetch_assoc($resMemberships)['count'];

/* MOST POPULAR CLUB */
$resPopular = mysqli_query($conn,
    "SELECT c.ClubName, COUNT(m.UserID) as total
     FROM club c
     LEFT JOIN membership m ON c.ClubID = m.ClubID
     GROUP BY c.ClubID
     ORDER BY total DESC
     LIMIT 1");

$popularClub = mysqli_fetch_assoc($resPopular);
$mostPopularClub = $popularClub ? $popularClub['ClubName'] : "N/A";

/* STUDENT BAR CHART */
$memberStatsQuery = "
    SELECT c.ClubName, COUNT(m.UserID) as member_count 
    FROM club c 
    LEFT JOIN membership m ON c.ClubID = m.ClubID 
    GROUP BY c.ClubID
";

/* MEMBERSHIP GROWTH TREND */
$trendQuery = "
    SELECT DATE_FORMAT(JoinDate, '%b %Y') AS month,
           COUNT(*) AS total
    FROM membership
    GROUP BY YEAR(JoinDate), MONTH(JoinDate)
    ORDER BY YEAR(JoinDate), MONTH(JoinDate)
";

$resTrend = mysqli_query($conn, $trendQuery);

$months = [];
$growthCounts = [];

while($row = mysqli_fetch_assoc($resTrend))
{
    $months[] = $row['month'];
    $growthCounts[] = $row['total'];
}

$resStats = mysqli_query($conn, $memberStatsQuery);

$clubNames = [];
$counts = [];

while ($row = mysqli_fetch_assoc($resStats))
{
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
            <a href="ClubReport.php" class="btn btn-primary">
                View Reports
            </a>

            <button onclick="window.print()" class="btn btn-primary">
                Print Report
            </button>

            <a href="ExportClubReport.php" class="btn btn-primary">
                Export CSV
            </a>
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

            <div class="summary-card">
                <h3><?php echo $mostPopularClub; ?></h3>
                <p>Most Popular Club</p>
            </div>

            <div class="summary-card">
                <h3><?php echo $totalMemberships; ?></h3>
                <p>Total Memberships</p>
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

        <div class="chart-container line-box">
            <canvas id="membershipTrendChart"></canvas>
        </div>

    <script>
        // 1. DATA REFRESH FUNCTION
        function refreshData() {
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

        // MEMBERSHIP GROWTH LINE CHART

            const ctxLine = document.getElementById('membershipTrendChart').getContext('2d');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Membership Growth',
                        data: <?php echo json_encode($growthCounts); ?>,
                        borderColor: MAROON,
                        backgroundColor: 'rgba(128,0,0,0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Membership Growth by Month'
                        }
                    }
                }
            });
    </script>
</body>
</html>