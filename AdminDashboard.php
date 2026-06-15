<?php
session_start();

/* Prevent browser cache */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'db.php';

/* Login Check */
if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

/* Admin Only */
if($_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

/* ===========================
   SUMMARY CARDS
=========================== */

/* Total Students */
$resStudents = mysqli_query($conn,
"SELECT COUNT(*) AS count
FROM user
WHERE RoleID='R02'");
$totalStudents = mysqli_fetch_assoc($resStudents)['count'];

/* Total Active Clubs */
$resClubs = mysqli_query($conn,
"SELECT COUNT(*) AS count
FROM club
WHERE ClubStatus='Active'");
$totalClubs = mysqli_fetch_assoc($resClubs)['count'];

/* Total Events */
$resEvents = mysqli_query($conn,
"SELECT COUNT(*) AS count
FROM event");
$totalEvents = mysqli_fetch_assoc($resEvents)['count'];

/* Total Users */
$resUsers = mysqli_query($conn,
"SELECT COUNT(*) AS count
FROM user");
$totalUsers = mysqli_fetch_assoc($resUsers)['count'];


/* ===========================
   CHART 1
   USER ROLE DISTRIBUTION
=========================== */

$resRole = mysqli_query($conn,"
SELECT
CASE
    WHEN RoleID='R01' THEN 'Admin'
    WHEN RoleID='R02' THEN 'Student'
    WHEN RoleID='R03' THEN 'Committee'
END AS RoleName,
COUNT(*) AS Total
FROM user
GROUP BY RoleID
");

$roles = [];
$roleCounts = [];

while($row = mysqli_fetch_assoc($resRole))
{
    $roles[] = $row['RoleName'];
    $roleCounts[] = $row['Total'];
}


/* ===========================
   CHART 2
   CLUB VS EVENT
=========================== */

$activeClubs = $totalClubs;
$totalEventCount = $totalEvents;


/* ===========================
   CHART 3
   MEMBERSHIP BY CLUB
=========================== */

$resMembers = mysqli_query($conn,"
SELECT
c.ClubName,
COUNT(m.UserID) AS Members
FROM club c
LEFT JOIN membership m
ON c.ClubID = m.ClubID
GROUP BY c.ClubID
");

$clubNames = [];
$memberCounts = [];

while($row = mysqli_fetch_assoc($resMembers))
{
    $clubNames[] = $row['ClubName'];
    $memberCounts[] = $row['Members'];
}


/* ===========================
   CHART 4
   EVENTS BY CLUB
=========================== */

$resClubEvents = mysqli_query($conn,"
SELECT
c.ClubName,
COUNT(e.EventID) AS TotalEvents
FROM club c
LEFT JOIN event e
ON c.ClubID = e.ClubID
GROUP BY c.ClubID
");

$eventClubNames = [];
$eventCounts = [];

while($row = mysqli_fetch_assoc($resClubEvents))
{
    $eventClubNames[] = $row['ClubName'];
    $eventCounts[] = $row['TotalEvents'];
}
?>

<!DOCTYPE html>
<html>
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

        <button onclick="location.reload()" class="btn btn-primary">
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

    <!-- CHARTS -->
    <div class="header-row">
        <h3>System Overview</h3>
    </div>

    <div class="summary-grid">

        <div class="chart-container">
            <canvas id="roleChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="clubEventChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="membershipChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="eventChart"></canvas>
        </div>

    </div>

</div>

<script>

/* ===========================
   CHART 1
=========================== */

new Chart(
document.getElementById('roleChart'),
{
    type:'bar',
    data:{
        labels: <?php echo json_encode($roles); ?>,
        datasets:[{
            label:'Users',
            data: <?php echo json_encode($roleCounts); ?>,
            backgroundColor:'#800000'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            title:{
                display:true,
                text:'User Role Distribution'
            },
            legend:{
                display:false
            }
        }
    }
});


/* ===========================
   CHART 2
=========================== */

new Chart(
document.getElementById('clubEventChart'),
{
    type:'doughnut',
    data:{
        labels:['Active Clubs','Events'],
        datasets:[{
            data:[
                <?php echo $activeClubs; ?>,
                <?php echo $totalEventCount; ?>
            ],
            backgroundColor:[
                '#800000',
                '#D7B7A3'
            ]
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            title:{
                display:true,
                text:'Club vs Event Overview'
            }
        }
    }
});


/* ===========================
   CHART 3
=========================== */

new Chart(
document.getElementById('membershipChart'),
{
    type:'bar',
    data:{
        labels: <?php echo json_encode($clubNames); ?>,
        datasets:[{
            label:'Members',
            data: <?php echo json_encode($memberCounts); ?>,
            backgroundColor:'#8B1E3F'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            title:{
                display:true,
                text:'Membership Distribution by Club'
            }
        }
    }
});


/* ===========================
   CHART 4
=========================== */

new Chart(
document.getElementById('eventChart'),
{
    type:'bar',
    data:{
        labels: <?php echo json_encode($eventClubNames); ?>,
        datasets:[{
            label:'Events',
            data: <?php echo json_encode($eventCounts); ?>,
            backgroundColor:'#D7B7A3'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            title:{
                display:true,
                text:'Events Organized by Club'
            }
        }
    }
});

</script>

</body>
</html>