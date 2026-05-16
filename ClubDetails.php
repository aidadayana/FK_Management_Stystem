<?php
require_once 'db.php';
session_start();

$currentUserID = $_SESSION['UserID'] ?? '';

/* 1. Club Details Logic FIRST (Defines $clubID safely) */
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $clubID = mysqli_real_escape_string($conn, $_GET['id']);
    $sqlClub = "SELECT * FROM club WHERE ClubID = '$clubID'";
    $resClub = mysqli_query($conn, $sqlClub);
    $club = mysqli_fetch_assoc($resClub);

    if (!$club) { 
        header("Location: ClubList.php"); 
        exit(); 
    }
} else {
    header("Location: ClubList.php");
    exit();
}

/* 2. SYSTEM ROLE & MEMBERSHIP LOOKUP 
      Since $_SESSION['UserRole'] is NOT SET, we look up the user's base system role 
      and their club-specific membership status directly from the tables.
*/
$isAdmin = false;
$isStudent = false;
$isCommittee = false;

$isAlreadyMember = false;
$userClubRole = '';

if (!empty($currentUserID)) {
    // A. Fetch system role from user table
    $sqlUserRole = "SELECT RoleID FROM user WHERE UserID = '$currentUserID'";
    $resUserRole = mysqli_query($conn, $sqlUserRole);
    if ($userRow = mysqli_fetch_assoc($resUserRole)) {
        $sysRole = $userRow['RoleID'];
        $isAdmin     = ($sysRole === 'R01');
        $isStudent   = ($sysRole === 'R02');
        $isCommittee = ($sysRole === 'R03');
    }

    // B. Fetch membership role inside THIS specific club
    $sqlCheck = "SELECT r.MemberRoleName 
                 FROM membership m 
                 JOIN membership_role r ON m.MemberRoleID = r.MemberRoleID 
                 WHERE m.UserID = '$currentUserID' AND m.ClubID = '$clubID' AND m.MemberStatus = 'Active'";
    $resCheck = mysqli_query($conn, $sqlCheck);
    if ($rowM = mysqli_fetch_assoc($resCheck)) {
        $isAlreadyMember = true;
        $userClubRole = $rowM['MemberRoleName'];
    }
}

/* Students only can see ACTIVE clubs */
if ($isStudent && $club['ClubStatus'] !== 'Active') {
    die("This club is currently inactive.");
}

/* Committee Members Table Fetch */
try {
    $sqlComm = "SELECT m.MemberStatus, mr.MemberRoleName, u.Name
                FROM membership m
                INNER JOIN membership_role mr ON m.MemberRoleID = mr.MemberRoleID
                INNER JOIN user u ON m.UserID = u.UserID
                WHERE m.ClubID = '$clubID' AND m.MemberStatus = 'Active'";
    
    $resComm = mysqli_query($conn, $sqlComm);
    $committeeMembers = [];
    if ($resComm) {
        while ($row = mysqli_fetch_assoc($resComm)) {
            $committeeMembers[] = $row;
        }
    }
} catch (Exception $e) {
    $committeeMembers = [];
}

/* Events Fetch */
try {
    // Upcoming Events
    $sqlUp = "SELECT * FROM event WHERE ClubID = '$clubID' AND EventDate >= CURDATE() ORDER BY EventDate ASC";
    $resUp = mysqli_query($conn, $sqlUp);
    $upcomingEvents = [];
    while ($row = mysqli_fetch_assoc($resUp)) {
        $upcomingEvents[] = $row;
    }

    // Past Events
    $sqlPast = "SELECT * FROM event WHERE ClubID = '$clubID' AND EventDate < CURDATE() ORDER BY EventDate DESC LIMIT 5";
    $resPast = mysqli_query($conn, $sqlPast);
    $pastEvents = [];
    while ($row = mysqli_fetch_assoc($resPast)) {
        $pastEvents[] = $row;
    }

} catch (Exception $e) {
    $upcomingEvents = [];
    $pastEvents = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($club['ClubName']); ?> | Club Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include('Navigation.php'); ?>
<div class="main-content">
    <div class="header-row">
        <h1>Club Details</h1>
        <a href="ClubList.php" class="btn btn-outline">Back to Club List</a>
    </div>

    <div class="profile-main-box">
        <div class="profile-header-bg"></div>
        <div class="profile-content">
            <div class="title-status">
                <h2><?php echo htmlspecialchars($club['ClubName']); ?></h2>
                <?php if (!$isStudent): ?>
                    <span class="status-pill <?php echo strtolower($club['ClubStatus']); ?>">
                        <?php echo htmlspecialchars($club['ClubStatus']); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="header-divider"></div>
            <p class="advisor-name">
                <strong>Advisor:</strong> <?php echo htmlspecialchars($club['ClubAdvisor']); ?>
            </p>
        </div>

        <div class="about-container">
            <h3 class="section-underline-title">About the Club</h3>
            <p class="description-text">
                <?php echo nl2br(htmlspecialchars($club['ClubDesc'])); ?>
            </p>
        </div>
    </div>

    <div class="profile-details-grid">
        <div class="detail-section full-width">
            <h3 class="section-underline-title">Club Committee</h3>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($committeeMembers)): ?>
                        <tr>
                            <td colspan="3">No committee members found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($committeeMembers as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['Name']); ?></td>
                                <td>
                                    <span class="pos-badge"><?php echo htmlspecialchars($m['MemberRoleName']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($m['MemberStatus']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="detail-section full-width">
            <h3 class="section-underline-title">Club Events</h3>
            <div class="event-tabs-container">
                <div class="event-column">
                    <h4 class="event-type-title">Upcoming</h4>
                    <?php if (empty($upcomingEvents)): ?>
                        <p class="no-data">No scheduled events.</p>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="event-item upcoming">
                                <div class="event-date-badge">
                                    <span class="day"><?php echo date('d', strtotime($ev['EventDate'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($ev['EventDate'])); ?></span>
                                </div>
                                <div class="event-info">
                                    <h5><?php echo htmlspecialchars($ev['Title']); ?></h5>
                                    <p>📍 <?php echo htmlspecialchars($ev['Venue']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="event-column">
                    <h4 class="event-type-title">Past</h4>
                    <?php if (empty($pastEvents)): ?>
                        <p class="no-data">No past records.</p>
                    <?php else: ?>
                        <?php foreach ($pastEvents as $ev): ?>
                            <div class="event-item past">
                                <div class="event-info">
                                    <h5><?php echo htmlspecialchars($ev['Title']); ?></h5>
                                    <p class="past-date"><?php echo date('d M Y', strtotime($ev['EventDate'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-action-row">
            <div class="admin-actions">
                <?php if ($isAdmin): ?>
                    <a href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-manage">Manage Committee</a>
                    <a href="ClubAddEdit.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-edit">Edit Details</a>
                    <a href="ClubDelete.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-delete" onclick="return confirm('WARNING: Are you sure you want to delete this club?')">Delete Club</a>
                <?php endif; ?>

                <?php if ($isCommittee && $isAlreadyMember): ?>
                    <a href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-manage">Manage Events</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>