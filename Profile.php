<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="styleAdmin.css">
</head>

<body>

<?php include('AdminNavigation.php'); ?>


<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div class="sidebar-logo">
            <img src="images/logo.png">
            <h2>FK Management</h2>
        </div>

        <ul class="menu">
            <li><a href="admin_dashboard.html">Dashboard</a></li>
            <li><a href="user_management.html">User Management</a></li>
            <li><a href="#" class="active">Profile</a></li>
            <li><a href="login.html">Logout</a></li>
        </ul>

    </div>

    <!-- CONTENT -->
    <div class="dashboard-content">

        <div class="dashboard-header">
            <h1>Admin Profile</h1>
            <p>View and update your profile information</p>
        </div>

        <!-- PROFILE CARD -->
        <div class="profile-card">

            <img src="images/profile.png" class="profile-img">

            <h2>Administrator</h2>
            <p>Email: admin@fkm.com</p>
            <p>Role: System Admin</p>

            <button class="btn">Edit Profile</button>

        </div>

    </div>

</div>

</body>
</html>