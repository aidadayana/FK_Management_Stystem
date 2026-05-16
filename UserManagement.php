<?php

session_start();

require_once 'db.php';

/* CHECK LOGIN */

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

/* ADMIN ONLY */

if($_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

/* GET USERS */

$sql = "
    SELECT UserID, Name, Email, RoleID
    FROM user
";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>User Management</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- PAGE HEADER -->

    <div class="header-row">

        <h1>USER MANAGEMENT</h1>

        <a href="RegisterUser.php"
           class="btn btn-primary">

            + Add User

        </a>

    </div>

    <p class="club-subtitle">

        Manage all registered users in the system

    </p>

    <!-- TABLE CONTAINER -->

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

            <?php

            if(mysqli_num_rows($result) > 0)
            {
                while($row = mysqli_fetch_assoc($result))
                {
            ?>

                <tr>

                    <td>
                        <?php echo $row['UserID']; ?>
                    </td>

                    <td>
                        <?php echo $row['Name']; ?>
                    </td>

                    <td>
                        <?php echo $row['Email']; ?>
                    </td>

                    <td>

                        <span class="status-badge status-active">

                            <?php

                            if($row['RoleID'] == 'R01')
                            {
                                echo "Admin";
                            }
                            elseif($row['RoleID'] == 'R02')
                            {
                                echo "Student";
                            }
                            else
                            {
                                echo "Committee";
                            }

                            ?>

                        </span>

                    </td>

                    <td>

                        <a href="edit_user.php?id=<?php echo $row['UserID']; ?>"
                           class="btn btn-outline">

                            Edit

                        </a>

                        <a href="delete_user.php?id=<?php echo $row['UserID']; ?>"
                           class="btn btn-danger-text"
                           onclick="return confirm('Delete this user?')">

                            Delete

                        </a>

                    </td>

                </tr>

            <?php
                }
            }
            else
            {
            ?>

                <tr>

                    <td colspan="5"
                        style="text-align:center; padding:20px;">

                        No Users Found

                    </td>

                </tr>

            <?php
            }
            ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>