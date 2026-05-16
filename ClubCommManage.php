<?php
require_once 'db.php';
session_start();

$clubID = $_GET['id'] ?? '';
if (!$clubID) { 
    header("Location: ClubList.php"); 
    exit(); 
}

$safeClubID = mysqli_real_escape_string($conn, $clubID);

// Fetch club overview details
$sqlClub = "SELECT * FROM club WHERE ClubID = '$safeClubID'";
$resClub = mysqli_query($conn, $sqlClub);
$club = mysqli_fetch_assoc($resClub);

if (!$club) { 
    die("Club not found."); 
}

// ====================================================
// 1. HANDLE ASSIGN MEMBER SUBMISSION (ON-PAGE)
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'assign') {
    $inputUserID = mysqli_real_escape_string($conn, $_POST['UserID']);
    $chosenRoleID = mysqli_real_escape_string($conn, $_POST['MemberRoleID']);

    if (!empty($inputUserID) && !empty($chosenRoleID)) {
        // Since they already have a membership row from joining, we UPDATE their role status
        $updateMemberSql = "UPDATE membership 
                            SET MemberRoleID = '$chosenRoleID', MemberStatus = 'Active' 
                            WHERE UserID = '$inputUserID' AND ClubID = '$safeClubID'";
        
        if (mysqli_query($conn, $updateMemberSql)) {
            header("Location: ClubCommManage.php?id=" . $safeClubID . "&msg=Committee+member+assigned+successfully");
            exit();
        } else {
            echo "<script>alert('Error updating member role: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Please select both a student and a position.');</script>";
    }
}

// ====================================================
// 2. HANDLE INLINE ROLE UPDATE DROPDOWN SUBMISSION
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_role') {
    $memberID = mysqli_real_escape_string($conn, $_POST['MemberID']);
    $newRoleID = mysqli_real_escape_string($conn, $_POST['new_role_id']);

    $updateSql = "UPDATE membership SET MemberRoleID = '$newRoleID' WHERE MemberID = '$memberID' AND ClubID = '$safeClubID'";
    if (mysqli_query($conn, $updateSql)) {
        header("Location: ClubCommManage.php?id=" . $safeClubID . "&msg=Position+updated+successfully");
        exit();
    }
}

// Fetch general reference roles listings
$resRoles = mysqli_query($conn, "SELECT * FROM membership_role ORDER BY MemberRoleID ASC");
$roles = [];
while ($row = mysqli_fetch_assoc($resRoles)) {
    $roles[] = $row;
}

// (Assuming standard club members have a default/blank role, or we filter out common members)
$sqlComm = "SELECT m.*, r.MemberRoleName, u.Name 
            FROM membership m 
            JOIN membership_role r ON m.MemberRoleID = r.MemberRoleID 
            JOIN user u ON m.UserID = u.UserID 
            WHERE m.ClubID = '$safeClubID' AND r.MemberRoleName != 'General Member'
            ORDER BY r.MemberRoleID ASC";

$resComm = mysqli_query($conn, $sqlComm);
$members = [];
while ($row = mysqli_fetch_assoc($resComm)) {
    $members[] = $row;
}

// ====================================================
// NEW EXTRACTION: FETCH STUDENTS WHO JOINED THIS CLUB
// ====================================================
$sqlJoinedStudents = "SELECT m.UserID, u.Name 
                      FROM membership m
                      JOIN user u ON m.UserID = u.UserID
                      JOIN membership_role r ON m.MemberRoleID = r.MemberRoleID
                      WHERE m.ClubID = '$safeClubID' 
                      AND (r.MemberRoleName = 'General Member' OR m.MemberRoleID = '' OR m.MemberRoleID IS NULL)";
$resJoined = mysqli_query($conn, $sqlJoinedStudents);
$joinedStudents = [];
if ($resJoined) {
    while ($row = mysqli_fetch_assoc($resJoined)) {
        $joinedStudents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage | <?php echo htmlspecialchars($club['ClubName']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include('Navigation.php'); ?>

    <div class="main-content">
        <div class="header-row">
            <h1>Committee Management</h1>
            <a href="ClubDetails.php?id=<?php echo $clubID; ?>" class="btn btn-outline">Back to Details</a>
        </div>

        <div class="profile-main-box">
            <div class="profile-header-bg"></div>
            <div class="profile-content">
                <div class="title-status">
                    <h2><?php echo htmlspecialchars($club['ClubName']); ?></h2>
                </div>
                <div class="header-divider"></div>
                <p class="advisor-name">
                    <strong>Advisor:</strong> <?php echo htmlspecialchars($club['ClubAdvisor']); ?>
                </p>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="management-split">
            <div class="committee-list-section">
                <div class="info-table-container">
                    <h3>Committee Personnel</h3>
                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>Student Info</th>
                                <th>Joined Date</th>
                                <th>Position</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($members)): ?>
                                <tr>
                                    <td colspan="4">No structural personnel assigned yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($members as $m): ?>
                                    <tr>
                                        <td>
                                            <span class="student-id-badge" style="font-weight: bold; color: #5c1d35; font-size: 1.1rem; margin-right: 8px;">
                                                <?php echo htmlspecialchars($m['UserID']); ?>
                                            </span>
                                            <span class="student-name" >
                                                <?php echo htmlspecialchars($m['Name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="joined-date-text" >
                                                <?php echo (!empty($m['JoinDate']) && $m['JoinDate'] !== '0000-00-00') ? date('d M Y', strtotime($m['JoinDate'])) : 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="ClubCommManage.php?id=<?php echo $clubID; ?>" class="inline-role-form" style="display: inline-flex; align-items: center; gap: 6px;">
                                                <input type="hidden" name="action_type" value="update_role">
                                                <input type="hidden" name="MemberID" value="<?php echo $m['MemberID']; ?>">
                                                
                                                <select name="new_role_id" class="table-select">
                                                    <?php foreach($roles as $role): ?>
                                                        <option value="<?php echo $role['MemberRoleID']; ?>" 
                                                            <?php echo ($m['MemberRoleID'] == $role['MemberRoleID']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($role['MemberRoleName']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                
                                                <button type="submit" style="background: none; border: none; padding: 4px; cursor: pointer; display: inline-flex; align-items: center;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </button>
                                            </form>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="DeleteMember.php?id=<?php echo $m['MemberID']; ?>&club_id=<?php echo $clubID; ?>" 
                                            onclick="return confirm('Remove <?php echo addslashes($m['Name']); ?>?')" 
                                            style="display: inline-flex; align-items: center; padding: 4px; text-decoration: none; float: right;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="assign-section">
                <h3>Assign Committee</h3>
                <form method="POST" action="ClubCommManage.php?id=<?php echo $clubID; ?>">
                    <input type="hidden" name="action_type" value="assign">
                    
                    <div class="form-group">
                        <label>Select Registered Student</label>
                        <select name="UserID" required class="form-control">
                            <option value="">-- Choose Registered Student --</option>
                            <?php if(empty($joinedStudents)): ?>
                                <option value="" disabled>No new registered students available</option>
                            <?php else: ?>
                                <?php foreach($joinedStudents as $student): ?>
                                    <option value="<?php echo $student['UserID']; ?>">
                                        <?php echo htmlspecialchars($student['UserID'] . " - " . $student['Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Assigned Position</label>
                        <select name="MemberRoleID" required class="form-control">
                            <option value="">-- Select Position --</option>
                            <?php foreach($roles as $role): ?>
                                <?php if($role['MemberRoleName'] !== 'General Member'): ?>
                                    <option value="<?php echo $role['MemberRoleID']; ?>">
                                        <?php echo htmlspecialchars($role['MemberRoleName']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:20px;">Assign Member</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>