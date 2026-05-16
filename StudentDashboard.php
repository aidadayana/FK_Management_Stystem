<?php

session_start();

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

if($_SESSION['RoleID'] != 'R02')
{
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Student Dashboard</title>

<link rel="stylesheet" href="styleAdmin.css">

</head>

<body>

<div class="dashboard-container">

    <div class="sidebar">

        <div class="sidebar-logo">

            <img src="images/logo.png">

            <h2>FK Management</h2>

        </div>

        <ul class="menu">

            <li><a href="StudentDashboard.php" class="active">Dashboard</a></li>

            <li><a href="#">My Clubs</a></li>

            <li><a href="#">Events</a></li>

            <li><a href="#">Profile</a></li>

            <li><a href="logout.php">Logout</a></li>

        </ul>

    </div>

    <div class="dashboard-content">

        <div class="dashboard-header">

            <h1>Student Dashboard</h1>

            <p>
                Welcome,
                <?php echo $_SESSION['Name']; ?>
            </p>

        </div>

        <div class="card-container">

            <div class="dashboard-card">

                <h3>Joined Clubs</h3>

                <p>3</p>

            </div>

            <div class="dashboard-card">

                <h3>Registered Events</h3>

                <p>5</p>

            </div>

            <div class="dashboard-card">

                <h3>Attendance Points</h3>

                <p>80</p>

            </div>

        </div>

    </div>

</div>

</body>

</html>