<?php
session_start();
require_once 'db.php';

/* ADMIN ONLY */
if (!isset($_SESSION['RoleID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])){
    header("Location: ClubList.php");
    exit();
}

$resTotal = mysqli_query($conn,
"SELECT COUNT(*) total FROM club");

$totalClubs = mysqli_fetch_assoc($resTotal)['total'];

$resActive = mysqli_query($conn,
"SELECT COUNT(*) total
 FROM club
 WHERE ClubStatus='Active'");

$activeClubs = mysqli_fetch_assoc($resActive)['total'];

$resMembers = mysqli_query($conn,
"SELECT COUNT(*) total
 FROM membership");

$totalMembers = mysqli_fetch_assoc($resMembers)['total'];

/* REPORT 1: SINGLE TABLE */
$sqlClub = "
    SELECT ClubID, ClubName, ClubAdvisor, ClubStatus
    FROM club
    ORDER BY ClubName ASC
";

$resClub = mysqli_query($conn, $sqlClub);

/* REPORT 2: JOIN TABLE */
$sqlMember = "
    SELECT
        c.ClubName,
        u.Name,
        mr.MemberRoleName,
        m.JoinDate,
        m.MemberStatus
    FROM membership m
    INNER JOIN club c
        ON m.ClubID = c.ClubID
    INNER JOIN user u
        ON m.UserID = u.UserID
    INNER JOIN membership_role mr
        ON m.MemberRoleID = mr.MemberRoleID
    ORDER BY c.ClubName, u.Name
";

$resMember = mysqli_query($conn, $sqlMember);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Club Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <div class="header-row">
        <h1>Club Reports</h1>

        <button onclick="window.print()" class="btn btn-primary">
            Print Report
        </button>
    </div>

    <!-- REPORT 1 -->

    <div class="detail-section">
        <h2>Club Report</h2>

        <table class="modern-table">
            <thead>
                <tr>
                    <th>Club ID</th>
                    <th>Club Name</th>
                    <th>Advisor</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php while($club = mysqli_fetch_assoc($resClub)): ?>

                <tr>
                    <td><?= htmlspecialchars($club['ClubID']) ?></td>
                    <td><?= htmlspecialchars($club['ClubName']) ?></td>
                    <td><?= htmlspecialchars($club['ClubAdvisor']) ?></td>
                    <td><?= htmlspecialchars($club['ClubStatus']) ?></td>
                </tr>

            <?php endwhile; ?>

            </tbody>
        </table>
    </div>

    <br><br>

    <!-- REPORT 2 -->

    <div class="detail-section">
        <h2>Membership Report</h2>

        <table class="modern-table">

            <thead>
                <tr>
                    <th>Club</th>
                    <th>Student Name</th>
                    <th>Role</th>
                    <th>Join Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php while($member = mysqli_fetch_assoc($resMember)): ?>

                <tr>
                    <td><?= htmlspecialchars($member['ClubName']) ?></td>
                    <td><?= htmlspecialchars($member['Name']) ?></td>
                    <td><?= htmlspecialchars($member['MemberRoleName']) ?></td>
                    <td><?= htmlspecialchars($member['JoinDate']) ?></td>
                    <td><?= htmlspecialchars($member['MemberStatus']) ?></td>
                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>
    </div>


</div>

</body>
</html>