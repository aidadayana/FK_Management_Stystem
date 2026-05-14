<?php
require_once 'db.php';
session_start();

$clubID = $_GET['id'] ?? '';
if (!$clubID) { 
    header("Location: ClubList.php"); 
    exit(); 
}

$safeClubID = mysqli_real_escape_string($conn, $clubID);
$sqlClub = "SELECT * FROM club WHERE ClubID = '$safeClubID'";
$resClub = mysqli_query($conn, $sqlClub);
$club = mysqli_fetch_assoc($resClub);

if (!$club) { 
    die("Club not found."); 
}

//get roles
$resRoles = mysqli_query($conn, "SELECT * FROM membership_role ORDER BY MemberRoleID ASC");
$roles = [];
while ($row = mysqli_fetch_assoc($resRoles)) {
    $roles[] = $row;
}

//get committee
$sqlComm = "SELECT m.*, r.MemberRoleName, u.Name 
            FROM membership m 
            JOIN membership_role r ON m.MemberRoleID = r.MemberRoleID 
            JOIN user u ON m.UserID = u.UserID 
            WHERE m.ClubID = '$safeClubID'
            ORDER BY r.MemberRoleID ASC";

$resComm = mysqli_query($conn, $sqlComm);
$members = [];
while ($row = mysqli_fetch_assoc($resComm)) {
    $members[] = $row;
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
            <a href="ClubList.php?id=<?php echo $clubID; ?>" class="btn btn-outline">Back to Club List</a>
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
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <div class="management-split">
            <div class="committee-list-section">
                <div class="info-table-container">
                    <h3>Committee Personnel</h3>
                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>Student Info</th>
                                <th>Position</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td>
                                    <span class="student-id-badge"><?php echo htmlspecialchars($m['UserID']); ?></span>
                                    <span class="student-name"><?php echo htmlspecialchars($m['Name']); ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="UpdateRole.php">
                                        <input type="hidden" name="MemberID" value="<?php echo $m['MemberID']; ?>">
                                        <input type="hidden" name="ClubID" value="<?php echo $clubID; ?>">
                                        <select name="new_role_id" onchange="this.form.submit()" class="table-select">
                                            <?php foreach($roles as $role): ?>
                                                <option value="<?php echo $role['MemberRoleID']; ?>" 
                                                    <?php echo ($m['MemberRoleID'] == $role['MemberRoleID']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($role['MemberRoleName']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <a href="DeleteMember.php?id=<?php echo $m['MemberID']; ?>&club_id=<?php echo $clubID; ?>" 
                                       class="btn-danger-text" onclick="return confirm('Remove <?php echo addslashes($m['Name']); ?>?')">Remove</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="assign-section">
                <h3>Assign Committee</h3>
                <form method="POST" action="AssignMember.php">
                    <input type="hidden" name="ClubID" value="<?php echo $clubID; ?>">
                    <div class="form-group">
                        <label>Student User ID</label>
                        <input type="text" name="UserID" placeholder="e.g. CD23001" required>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <select name="MemberRoleID" required>
                            <option value="">-- Select --</option>
                            <?php foreach($roles as $role): ?>
                                <option value="<?php echo $role['MemberRoleID']; ?>"><?php echo $role['MemberRoleName']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Assign Member</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>