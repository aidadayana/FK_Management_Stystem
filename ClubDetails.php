<?php
require_once 'db.php';
session_start();

/*Role*/
$userRole = $_SESSION['UserRole'] ?? '';

$isAdmin = ($userRole === 'Admin');
$isCommittee = ($userRole === 'Committee');
$isStudent = ($userRole === 'Student');

/*Club Details*/
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $clubID = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM club WHERE ClubID = ?");
    $stmt->execute([$clubID]);
    $club = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$club) {
        header("Location: ClubList.php");
        exit();
    }
} else {
    header("Location: ClubList.php");
    exit();
}

/* Students only can see ACTIVE clubs */
if ($isStudent && $club['ClubStatus'] !== 'Active') {
    die("This club is currently inactive.");
}

try {

    $stmtCommittee = $pdo->prepare("
        SELECT 
            m.MemberStatus,
            mr.MemberRoleName,
            u.Name
        FROM membership m
        INNER JOIN membership_role mr
            ON m.MemberRoleID = mr.MemberRoleID
        INNER JOIN user u
            ON m.UserID = u.UserID
        WHERE m.ClubID = ?
        AND m.MemberStatus = 'Active'
    ");
    $stmtCommittee->execute([$clubID]);
    $committeeMembers = $stmtCommittee->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $committeeMembers = [];
}

try {
    $stmtUp = $pdo->prepare("
        SELECT *
        FROM event
        WHERE ClubID = ?
        AND EventDate >= CURDATE()
        ORDER BY EventDate ASC
    ");

    $stmtUp->execute([$clubID]);
    $upcomingEvents = $stmtUp->fetchAll(PDO::FETCH_ASSOC);
    $stmtPast = $pdo->prepare("
        SELECT *
        FROM event
        WHERE ClubID = ?
        AND EventDate < CURDATE()
        ORDER BY EventDate DESC
        LIMIT 5
    ");

    $stmtPast->execute([$clubID]);
    $pastEvents = $stmtPast->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $upcomingEvents = [];
    $pastEvents = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo htmlspecialchars($club['ClubName']); ?> | Club Details
    </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include('Navigation.php'); ?>
<div class="main-content">
    <div class="header-row">
        <h1>Club Details</h1>
        <a href="ClubList.php" class="btn btn-outline">
            Back to Club List
        </a>
    </div>

    <!-- CLUB PROFILE -->
    <div class="profile-main-box">
        <div class="profile-header-bg"></div>
            <div class="profile-content">
                <div class="title-status">
                    <h2>
                        <?php echo htmlspecialchars($club['ClubName']); ?>
                    </h2>
                    
                    <?php if (!$isStudent): ?>
                        <span class="status-pill <?php echo strtolower($club['ClubStatus']); ?>">
                            <?php echo htmlspecialchars($club['ClubStatus']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="header-divider"></div>
                <p class="advisor-name">
                    <strong>Advisor:</strong>
                    <?php echo htmlspecialchars($club['ClubAdvisor']); ?>
                </p>
            </div>

            <div class="about-container">
                <h3 class="section-underline-title">
                    About the Club
                </h3>
                <p class="description-text">
                    <?php echo nl2br(htmlspecialchars($club['ClubDesc'])); ?>
                </p>
            </div>
        </div>

    <div class="profile-details-grid">
        <div class="detail-section full-width">
            <h3 class="section-underline-title">
                Club Committee
            </h3>
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
                            <td colspan="3">
                                No committee members found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($committeeMembers as $m): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($m['Name']); ?>
                                </td>
                                <td>
                                    <span class="pos-badge">
                                        <?php echo htmlspecialchars($m['MemberRoleName']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($m['MemberStatus']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- EVENTS -->
        <div class="detail-section full-width">
            <h3 class="section-underline-title">
                Club Events
            </h3>
            <div class="event-tabs-container">
                <div class="event-column">
                    <h4 class="event-type-title">
                        Upcoming
                    </h4>

                    <?php if (empty($upcomingEvents)): ?>
                        <p class="no-data">
                            No scheduled events.
                        </p>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="event-item upcoming">
                                <div class="event-date-badge">
                                    <span class="day">
                                        <?php echo date('d', strtotime($ev['EventDate'])); ?>
                                    </span>
                                    <span class="month">
                                        <?php echo date('M', strtotime($ev['EventDate'])); ?>
                                    </span>
                                </div>

                                <div class="event-info">
                                    <h5>
                                        <?php echo htmlspecialchars($ev['Title']); ?>
                                    </h5>
                                    <p>
                                        📍 <?php echo htmlspecialchars($ev['Venue']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- PAST -->
                <div class="event-column">
                    <h4 class="event-type-title">
                        Past
                    </h4>
                    <?php if (empty($pastEvents)): ?>
                        <p class="no-data">
                            No past records.
                        </p>
                    <?php else: ?>
                        <?php foreach ($pastEvents as $ev): ?>
                            <div class="event-item past">
                                <div class="event-info">
                                    <h5>
                                        <?php echo htmlspecialchars($ev['Title']); ?>
                                    </h5>
                                    <p class="past-date">
                                        <?php echo date('d M Y', strtotime($ev['EventDate'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="footer-action-row">
            <!-- STUDENT -->
            <?php if ($isStudent): ?>
                <form action="JoinClub.php" method="POST">
                    <input 
                        type="hidden"
                        name="ClubID"
                        value="<?php echo $club['ClubID']; ?>"
                    >
                    <button type="submit" class="btn btn-join">
                        Join Club
                    </button>
                </form>
            <?php endif; ?>

            <!-- ADMIN + COMMITTEE -->
            <div class="admin-actions">
                <!-- ADMIN ONLY -->
                <?php if ($isAdmin): ?>
                    <a href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-manage">
                        Manage Committee
                    </a>

                    <a href="ClubAddEdit.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-edit">
                        Edit Details
                    </a>
                    <a href="ClubDelete.php?id=<?php echo $club['ClubID']; ?>"
                       class="btn btn-delete"
                       onclick="return confirm('WARNING: Are you sure you want to delete this club?')">
                        Delete Club
                    </a>
                <?php endif; ?>

                <!-- COMMITTEE ONLY -->
                <?php if ($isCommittee): ?>
                    <a href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-manage">
                        Manage Events
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>