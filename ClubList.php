<?php
require_once 'db.php';
session_start();

$currentUserID = $_SESSION['UserID'] ?? '';

// 1. DYNAMICALLY RESOLVE ROLE FROM DATABASE
$isAdmin = false;
$isStudent = false;
$isCommittee = false;

if (!empty($currentUserID)) {
    $sqlUserRole = "SELECT RoleID FROM user WHERE UserID = '$currentUserID'";
    $resUserRole = mysqli_query($conn, $sqlUserRole);
    if ($userRow = mysqli_fetch_assoc($resUserRole)) {
        $sysRole = $userRow['RoleID'];
        $isAdmin     = ($sysRole === 'R01');
        $isStudent   = ($sysRole === 'R02');
        $isCommittee = ($sysRole === 'R03');
    }
}

// Get filter inputs
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// 2. DATABASE QUERY BASE
try {
    if ($isAdmin) {
        $query = "SELECT c.*, m.MemberID, m.MemberStatus 
                  FROM club c 
                  LEFT JOIN membership m ON c.ClubID = m.ClubID AND m.UserID = '$currentUserID'
                  WHERE 1=1";
    } else {
        $query = "SELECT c.*, m.MemberID, m.MemberStatus 
                  FROM club c 
                  LEFT JOIN membership m ON c.ClubID = m.ClubID AND m.UserID = '$currentUserID'
                  WHERE c.ClubStatus='Active'";
    }

    // SEARCH FILTERS
    if (!empty($search)) {
        $safeSearch = mysqli_real_escape_string($conn, $search);
        $query .= " AND (c.ClubName LIKE '%$safeSearch%' OR c.ClubDesc LIKE '%$safeSearch%')";
    }

    if ($isAdmin && !empty($statusFilter) && $statusFilter !== 'All') {
        $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
        $query .= " AND c.ClubStatus = '$safeStatus'";
    }

    $query .= " ORDER BY c.ClubName ASC";

    $result = mysqli_query($conn, $query);
    $clubs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $clubs[] = $row;
        }
    }
} 
catch (Exception $e) {
    $clubs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Directory | Smart Campus</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include('Navigation.php'); ?>

<div class="main-content">
    <div class="header-row">
        <h1>Clubs of Faculty Computing</h1>
        <div style="display:flex; align-items:center; gap:15px;">
            <?php if ($isAdmin): ?>
                <a href="ClubAddEdit.php" class="btn btn-primary">+ Add Club</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-container">
        <form action="ClubList.php" method="GET" class="filter-form">
            <input
                type="text"
                name="search"
                placeholder="Search for a club..."
                class="search-input"
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <?php if ($isAdmin): ?>
                <select name="status" class="status-select">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo ($statusFilter == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($statusFilter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            <?php endif; ?>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo ($isAdmin) ? "Filter Results" : "Search"; ?>
                </button>
                <a href="ClubList.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <div class="club-grid">
        <?php if (empty($clubs)): ?>
            <p>No clubs found in the database.</p>
        <?php else: ?>
            <?php foreach($clubs as $club): ?>
                <?php 
                $isClubActive = (strcasecmp($club['ClubStatus'], 'Active') == 0); 
                $hasJoined = (!empty($club['MemberID']) && $club['MemberStatus'] === 'Active');
                ?>

                <div class="club-card">
                    <div>
                        <?php if ($isAdmin): ?>
                            <span class="status-badge <?php echo $isClubActive ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo htmlspecialchars($club['ClubStatus']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="club-header-simple">
                            <h3><?php echo htmlspecialchars($club['ClubName']); ?></h3>
                        </div>

                        <p class="advisor-text">
                            Advisor: <strong><?php echo htmlspecialchars($club['ClubAdvisor'] ?? 'TBA'); ?></strong>
                        </p>

                        <p class="description">
                            <?php echo htmlspecialchars($club['ClubDesc']); ?>
                        </p>
                    </div>

                    <div class="action-footer">
                        <a href="ClubDetails.php?id=<?php echo urlencode($club['ClubID']); ?>" class="btn btn-primary">
                            View Details
                        </a>

                        <?php if ($isStudent): ?>
                            <?php if ($hasJoined): ?>
                                <button class="btn" style="background-color: #6c757d; cursor: not-allowed;" disabled>Joined</button>
                            <?php else: ?>
                                <form action="JoinClub.php" method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Are you sure you want to join <?php echo addslashes($club['ClubName']); ?>?')">
                                    <input type="hidden" name="ClubID" value="<?php echo $club['ClubID']; ?>">
                                    <button type="submit" class="btn btn-primary">Join Club</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($isCommittee && $hasJoined): ?>
                            <a href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-manage" style="background-color: #ffc107; color: #000;">
                                Manage
                            </a>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a href="ClubAddEdit.php?id=<?php echo $club['ClubID']; ?>" class="btn btn-edit">Edit</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>