<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
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
            <li><a href="#" class="active">User Management</a></li>
            <li><a href="profile.html">Profile</a></li>
            <li><a href="login.html">Logout</a></li>
        </ul>

    </div>

    <!-- CONTENT -->
    <div class="dashboard-content">

        <div class="dashboard-header">
            <h1>User Management</h1>
            <p>Manage all registered users in the system</p>
        </div>

        <!-- TABLE SECTION -->
        <div class="table-section">

            <table class="user-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Matric No</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>

                <tr>
                    <td>1</td>
                    <td>Aina Nasuha</td>
                    <td>AA12345</td>
                    <td>aina@email.com</td>
                    <td>Student</td>
                    <td><button class="btn">Edit</button></td>
                </tr>

                <tr>
                    <td>2</td>
                    <td>John Doe</td>
                    <td>BB54321</td>
                    <td>john@email.com</td>
                    <td>Club Admin</td>
                    <td><button class="btn">Edit</button></td>
                </tr>

            </table>

        </div>

    </div>

</div>

</body>
</html>