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
        <?php if(($_SESSION['RoleID'] ?? '') == 'R01') { ?>
            <li>
                <a href="AdminDashboard.php">
                    AdminDashboard
                </a>
            </li>
            <li>
                <a href="ClubDashboard.php">
                     Club Dashboard
                </a>
            </li>
            <li>
                <a href="UserManagement.php">
                    User Management
                </a>
            </li>
            <li>
                <a href="ClubList.php">
                    List of Club
                </a>
            </li>
            <li>
                <a href="events.php">Event Dashboard</a>
            </li>
            <li>
                <a href="ManageEvents.php">Manage Events</a>
            </li>
            <li>
                <a href="ClubReport.php">Club Reports</a>
            </li>
        <?php } ?>

        <!-- STUDENT -->
        <?php if(($_SESSION['RoleID'] ?? '') == 'R02') { ?>
            <li>
                <a href="StudentDashboard.php">
                    Dashboard
                </a>
            </li>
            <li>
                <a href="ClubList.php">
                    List of Club
                </a>
            </li>
            
            <li>
                <a href="StudentEvent.php">
                    Events
                </a>
            </li>
            <li>
                <a href="Waitlist.php">My Waitlist</a>
            </li>
            <li>
                <a href="MyRegistrations.php">My Registrations</a>
            </li>
        <?php } ?>

        <!-- COMMITTEE -->
        <?php if(($_SESSION['RoleID'] ?? '') == 'R03') { ?>
            <li>
                <a href="ClubDashboard.php">
                     Club Dashboard
                </a>
            </li>
            <li>
                <a href="ClubList.php">
                    List of Club
                </a>
            </li>
            <li>
                <a href="events.php">Event Dashboard</a>
            </li>
            <li>
                <a href="ManageEvents.php">Manage Events</a>
            </li>
            <li>
                <a href="CreateEvent.php">Create Event</a>
            </li>
        <?php } ?>

        <!-- EVERYONE -->

        <li>
            <a href="Profile.php">
                Profile
            </a>
        </li>
        <li>
            <a href="Logout.php"
               onclick="return confirm('Are you sure you want to logout?');">
                Logout
            </a>
        </li>
    </ul>
</div>