<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01') {
    header("Location: login.php");
    exit();
}

$sql = "SELECT UserID, Name, Email, RoleID FROM user";
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

    <div class="header-row">
        <h1>USER MANAGEMENT</h1>

        <a href="RegisterUser.php" class="btn btn-primary">
            + Add User
        </a>
    </div>

    <p class="club-subtitle">
        Manage all registered users in the system
    </p>

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

            </tbody>

        </table>

    </div>

</div>

</body>
</html>