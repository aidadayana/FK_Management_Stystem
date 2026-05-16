<?php

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

?>

<div class="sidebar">

    <h2>FK Management</h2>

    <ul class="nav-menu">

        <!-- ADMIN -->

        <?php if($_SESSION['RoleID'] == 'R01') { ?>

            <li>
                <a href="AdminDashboard.php">Dashboard</a>
            </li>

            <li>
                <a href="UserManagement.php">User Management</a>
            </li>

            <li>
                <a href="ClubList.php">Club</a>
            </li>

            <li>
                <a href="Reports.php">Reports</a>
            </li>
            
            <li>
                <a href="ClubList.php">Club</a>
            </li>

        <?php } ?>



        <!-- STUDENT -->

        <?php if($_SESSION['RoleID'] == 'R02') { ?>

            <li>
                <a href="StudentDashboard.php">Dashboard</a>
            </li>

            <li>
                <a href="JoinClub.php">Join Club</a>
            </li>

            <li>
                <a href="MyClub.php">My Club</a>
            </li>

            <li>
                <a href="StudentEvent.php">Events</a>
            </li>

        <?php } ?>



        <!-- COMMITTEE -->

        <?php if($_SESSION['RoleID'] == 'R03') { ?>

            <li>
                <a href="ClubDashboard.php">Dashboard</a>
            </li>

            <li>
                <a href="ClubList.php">Club</a>
            </li>

        <?php } ?>



        <!-- EVERYONE -->

        <li>
            <a href="Profile.php">Profile</a>
        </li>

        <li>
            <a href="Logout.php">Logout</a>
        </li>

    </ul>

</div>