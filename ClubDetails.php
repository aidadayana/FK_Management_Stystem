<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>CLUB DETAILS</title>
</head>
<body>
    <?php include('Navigation.php'); ?>

    <div class="main-content">
        <div class="header-row">
            <h1>Club Details</h1>
            <a href="ClubListPage.php" class="btn btn-back">Back to List</a>
        </div>

        <div class="content-box">
            <div class="club-profile-header">
                <div class="logo-box">Logo</div>
                <div class="club-title">
                    <h2>CyberSecurity Nexus</h2>
                    <p>Advisor: Dr. Noorlin Mohd Ali</p>
                </div>
            </div>
            <div class="description-section">
                <h4>Description:</h4>
                <p>Focuses on network security, ethical hacking, and cybersecurity awareness within the Faculty of Computing.</p>
            </div>
        </div>

        <div class="content-box">
            <h3>Club Committee</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student ID</th>
                        <th>Position</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Aina Nasuha</td>
                        <td>CD23008</td>
                        <td>President</td>
                    </tr>
                    <tr>
                        <td>Nur Aida Dayana</td>
                        <td>CD23012</td>
                        <td>Secretary</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="content-box">
            <h3>Upcoming Events</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Cyber Drill 2026</td>
                        <td>15 May 2026</td>
                        <td>Lab 3, FK</td>
                    </tr>
                    <tr>
                        <td>Linux Workshop</td>
                        <td>02 June 2026</td>
                        <td>Main Hall</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <button class="btn btn-primary">Join Club</button>
            <div class="admin-group">
                <a href="CommManage.php" class="btn btn-outline">Manage Committee</a>
                <a href="AddEditPage.php" class="btn btn-outline">Edit Club</a>
                <button class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</body>
</html>