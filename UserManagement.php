<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01') {
    header("Location: login.php");
    exit();
}

/* =========================
   FILTER LOGIC
========================= */
$filter = "ALL";

if(isset($_GET['role']) && $_GET['role'] != "ALL") {
    $filter = $_GET['role'];
}

/* =========================
   QUERY USERS
========================= */
if($filter == "ALL") {
    $sql = "SELECT UserID, Name, Email, RoleID FROM user";
} else {
    $sql = "SELECT UserID, Name, Email, RoleID FROM user WHERE RoleID = '$filter'";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- HEADER -->
    <div class="header-row">
        <h1>USER MANAGEMENT</h1>

        <a href="RegisterUser.php" class="btn btn-primary">
            + Add User
        </a>
    </div>

    <p class="club-subtitle">
        Manage all registered users in the system
    </p>

    <!-- FILTER -->
    <form method="GET" class="header-row" style="margin-top:10px;">

        <select name="role" class="btn btn-outline" onchange="this.form.submit()">

            <option value="ALL" <?php if($filter=="ALL") echo "selected"; ?>>
                All Roles
            </option>

            <option value="R01" <?php if($filter=="R01") echo "selected"; ?>>
                Admin
            </option>

            <option value="R02" <?php if($filter=="R02") echo "selected"; ?>>
                Student
            </option>

            <option value="R03" <?php if($filter=="R03") echo "selected"; ?>>
                Committee
            </option>

        </select>

    </form>

    <!-- TABLE -->
    <div class="info-table-container">

        <table class="management-table">

            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if(mysqli_num_rows($result) > 0) { ?>

                <?php while($row = mysqli_fetch_assoc($result)) { ?>

                    <tr>

                        <td><?php echo $row['UserID']; ?></td>
                        <td><?php echo $row['Name']; ?></td>
                        <td><?php echo $row['Email']; ?></td>

                        <td>
                            <?php
                                if($row['RoleID'] == 'R01') echo "Admin";
                                elseif($row['RoleID'] == 'R02') echo "Student";
                                else echo "Committee";
                            ?>
                        </td>

                        <td>

                            <a href="EditUser.php?id=<?php echo $row['UserID']; ?>"
                               class="btn btn-outline">
                                Edit
                            </a>

                            <a href="DeleteUser.php?id=<?php echo $row['UserID']; ?>"
                               class="btn btn-danger-text"
                               onclick="return confirm('Delete this user?')">
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="5" style="text-align:center; padding:20px;">
                        No Users Found
                    </td>
                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>