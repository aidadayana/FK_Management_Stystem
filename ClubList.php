<?php
require_once 'db.php';
session_start();

// ROLE CHECK
$userRole = $_SESSION['UserRole'] ?? '';

$isAdmin = ($userRole === 'Admin');
$isCommittee = ($userRole === 'Committee');
$isStudent = ($userRole === 'Student');

if (isset($_GET['id'])) {
    unset($_GET['id']);
}

// FILTER
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// DATABASE 
try {
    /*STUDENT:active club
    ADMIN / COMMITTEE: active inactive club*/
    if ($isStudent || $isCommittee) {
        $query = "SELECT * FROM club WHERE ClubStatus='Active'";
    } else {
        $query = "SELECT * FROM club WHERE 1=1";
    }

    // SEARCH
    if (!empty($search)) {
        $safeSearch = mysqli_real_escape_string($conn, $search);
        $query .= " AND (ClubName LIKE '%$safeSearch%' OR ClubDesc LIKE '%$safeSearch%')";
    }

    // STATUS FILTER ONLY ADMIN & COMMITEE
    if (!$isStudent && !empty($statusFilter) && $statusFilter !== 'All') {
        $safeStatus = mysqli_real_escape_string($conn, $statusFilter);
        $query .= " AND ClubStatus = '$safeStatus'";
    }

    $query .= " ORDER BY ClubName ASC";

    // EXECUTE USING MYSQLI
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

            <!-- ADMIN ONLY -->
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

            <?php if (!$isStudent): ?>
                <select name="status" class="status-select">
                    <option value="">
                        All Status
                    </option>
                    <option
                        value="Active"
                        <?php echo ($statusFilter == 'Active') ? 'selected' : ''; ?>
                    >
                        Active
                    </option>
                    <option
                        value="Inactive"
                        <?php echo ($statusFilter == 'Inactive') ? 'selected' : ''; ?>
                    >
                        Inactive
                    </option>
                </select>

            <?php endif; ?>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?php
                    if ($isStudent) {
                        echo "Search";
                    } else {
                        echo "Filter Results";
                    }
                    ?>
                </button>
                <a href="ClubList.php" class="btn btn-outline">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="club-grid">
        <?php if (empty($clubs)): ?>
            <p>No clubs found in the database.</p>
        <?php else: ?>
            <?php foreach($clubs as $club): ?>
                <?php
                $isClubActive =
                    (strcasecmp($club['ClubStatus'], 'Active') == 0);
                ?>

                <div class="club-card">
                    <div>
                        <?php if (!$isStudent): ?>
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
                                <?php
                                echo htmlspecialchars(
                                    $club['ClubAdvisor'] ?? 'TBA'
                                );
                                ?>
                            </strong>
                        </p>

                        <p class="description">
                            <?php
                            echo htmlspecialchars($club['ClubDesc']);
                            ?>
                        </p>
                    </div>

                    <div class="action-footer">
                        <a
                            href="ClubDetails.php?id=<?php echo urlencode($club['ClubID']); ?>"
                            class="btn btn-primary"
                        >
                            View Details
                        </a>

                        <?php if ($isAdmin): ?>
                            <a
                                href="ClubAddEdit.php?id=<?php echo $club['ClubID']; ?>"
                                class="btn btn-edit"
                            >
                                Edit
                            </a>
                            <a
                                href="ClubDelete.php?id=<?php echo $club['ClubID']; ?>"
                                class="btn btn-delete"
                                onclick="return confirm('Delete this club?')"
                            >
                                Delete
                            </a>
                        <?php endif; ?>

                        <?php if ($isCommittee): ?>
                            <a
                                href="ClubCommManage.php?id=<?php echo $club['ClubID']; ?>"
                                class="btn btn-manage"
                            >
                                Manage
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>