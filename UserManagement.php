<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

if($_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

/* GET USERS */
$sql = "SELECT UserID, Name, MatricNo, Email, RoleID FROM user";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>

    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- HEADER -->
    <div class="header-row">
        <h1>USER MANAGEMENT</h1>

        <a href="add_user.php" class="btn btn-primary">
            + Add User
        </a>
    </div>

    <p class="club-subtitle">
        Manage all registered users in the system
    </p>

    <!-- TABLE BOX -->
    <div class="info-table-container">

        <table class="management-table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Matric No</th>
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
                    <td><?php echo $row['MatricNo']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td>
                        <span class="status-badge status-active">
                            <?php echo $row['RoleID']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $row['UserID']; ?>" class="btn btn-outline">Edit</a>
                        <a href="delete_user.php?id=<?php echo $row['UserID']; ?>" class="btn btn-danger-text">Delete</a>
                    </td>
                </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>