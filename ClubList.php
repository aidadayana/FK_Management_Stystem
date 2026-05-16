<?php
require_once 'db.php';
session_start();

// ROLE CHECK
$userRole = $_SESSION['UserRole'] ?? '';
$currentUserID = $_SESSION['UserID'] ?? ''; 

$isAdmin = ($userRole === 'Admin');
$isCommitteeGlobal = ($userRole === 'Committee');
$isStudentGlobal = ($userRole === 'Student');

if (isset($_GET['id'])) {
    unset($_GET['id']);
}

// FILTER
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// DATABASE 
try {
    // Select c.*, the explicit user join token, and their structural title name for that club assignment row
    if ($isStudentGlobal || $isCommitteeGlobal) {
        $query = "SELECT c.*, m.MemberID, mr.MemberRoleName 
                  FROM club c 
                  LEFT JOIN membership m ON c.ClubID = m.ClubID AND m.UserID = '$currentUserID'
                  LEFT JOIN membership_role mr ON m.MemberRoleID = mr.MemberRoleID
                  WHERE c.ClubStatus='Active'";
    } else {
        $query = "SELECT c.*, m.MemberID, mr.MemberRoleName 
                  FROM club c 
                  LEFT JOIN membership m ON c.ClubID = m.ClubID AND m.UserID = '$currentUserID'
                  LEFT JOIN membership_role mr ON m.MemberRoleID = mr.MemberRoleID
                  WHERE 1=1";
    }

    // SEARCH 
    if (!empty($search)) {
        $safeSearch = mysqli_real_escape_string($conn, $search);
        $query .= " AND (c.ClubName LIKE '%$safeSearch%' OR c.ClubDesc LIKE '%$safeSearch%')";
    }

    // STATUS FILTER ONLY ADMIN & COMMITTEE
    if (!$isStudentGlobal && !empty($statusFilter) && $statusFilter !== 'All') {
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
            <p>
                Current Role:
                <strong>
                    <?php echo htmlspecialchars($userRole); ?>
                </strong>
            </p>

            <?php if ($isAdmin): ?>
                <a href="ClubAddEdit.php" class="btn btn-primary">
                    + Add Club
                </a>
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

            <?php if (!$isStudentGlobal): ?>
                <select name="status" class="status-select">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo ($statusFilter == 'Active') ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($statusFilter == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            <?php endif; ?>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?php echo ($isStudentGlobal) ? "Search" : "Filter Results"; ?>
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
                
                // Determine loop context profile role
                $loopClubRole = isset($club['MemberRoleName']) ? trim($club['MemberRoleName']) : '';
                
                $loopIsCommittee = ($userRole === 'Committee' || (!empty($loopClubRole) && !in_array(strtolower($loopClubRole), ['student', 'member'])));
                $loopIsStudent = ($userRole === 'Student' && !$loopIsCommittee && !$isAdmin);
                ?>

                <div class="club-card">
                    <div>
                        <?php if (!$isStudentGlobal): ?>
                            <span class="status-badge <?php echo $isClubActive ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo htmlspecialchars($club['ClubStatus']); ?>
                            </span>
                        <?php endif; ?>

                        <div class="club-header-simple">
                            <h3>
                                <?php echo htmlspecialchars($club['ClubName']); ?>
                            </h3>
                        </div>

                        <p class="advisor-text">
                            Advisor:
                            <strong>
                                <?php echo htmlspecialchars($club['ClubAdvisor'] ?? 'TBA'); ?>
                            </strong>
                        </p>

                        <p class="description">
                            <?php echo htmlspecialchars($club['ClubDesc']); ?>
                        </p>
                    </div>

                    <div class="action-footer">
                        <a href="ClubDetails.php?id=<?php echo urlencode($club['ClubID']); ?>" class="btn btn-primary">
                            View Details
                        </a>

                        <?php if ($loopIsStudent): ?>
                            <?php if ($club['MemberID']): ?>
                                <span class="btn btn-disabled" style="background: #ccc; cursor: not-allowed;">Already Joined</span>
                            <?php else: ?>
                                <form action="JoinClub.php" method="POST" style="display:inline;" 
                                    onsubmit="return confirm('Join <?php echo addslashes($club['ClubName']); ?>?')">
                                    <input type="hidden" name="ClubID" value="<?php echo $club['ClubID']; ?>">
                                    <button type="submit" class="btn btn-primary">Join Club</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>