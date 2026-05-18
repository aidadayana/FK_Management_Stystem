<?php
session_start();
require_once 'db.php';

/* LOGIN CHECK */
if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

/* =========================
   FILTER LOGIC
========================= */

$roleFilter = "ALL";
$statusFilter = "ALL";

/* ROLE FILTER */
if(isset($_GET['role']) && $_GET['role'] != "")
{
    $roleFilter = $_GET['role'];
}

/* STATUS FILTER */
if(isset($_GET['status']) && $_GET['status'] != "")
{
    $statusFilter = $_GET['status'];
}

/* =========================
   QUERY BUILDING
========================= */

$sql = "
    SELECT UserID, Name, Email, RoleID, UserStatus
    FROM user
    WHERE 1=1
";

/* ROLE CONDITION */
if($roleFilter != "ALL")
{
    $sql .= " AND RoleID = '$roleFilter'";
}

/* STATUS CONDITION */
if($statusFilter != "ALL")
{
    $sql .= " AND UserStatus = '$statusFilter'";
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

        <a href="RegisterUser.php"
           class="btn btn-primary">

            + Add User

        </a>

    </div>

    <!-- SUBTITLE -->

    <p class="club-subtitle">

        Manage all registered users in the system

    </p>

    <!-- FILTER SECTION -->

    <div class="search-container">

        <form method="GET" class="filter-form">

            <!-- ROLE FILTER -->

            <select name="role"
                    class="status-select"
                    onchange="this.form.submit()">

                <option value="ALL"
                    <?php if($roleFilter=="ALL") echo "selected"; ?>>

                    All Roles

                </option>

                <option value="R01"
                    <?php if($roleFilter=="R01") echo "selected"; ?>>

                    Admin

                </option>

                <option value="R02"
                    <?php if($roleFilter=="R02") echo "selected"; ?>>

                    Student

                </option>

                <option value="R03"
                    <?php if($roleFilter=="R03") echo "selected"; ?>>

                    Committee

                </option>

            </select>

            <!-- STATUS FILTER -->

            <select name="status"
                    class="status-select"
                    onchange="this.form.submit()">

                <option value="ALL"
                    <?php if($statusFilter=="ALL") echo "selected"; ?>>

                    All Status

                </option>

                <option value="Active"
                    <?php if($statusFilter=="Active") echo "selected"; ?>>

                    Active

                </option>

                <option value="Inactive"
                    <?php if($statusFilter=="Inactive") echo "selected"; ?>>

                    Inactive

                </option>

            </select>

        </form>

    </div>

    <!-- TABLE -->

    <div class="info-table-container">

        <table class="management-table">

            <thead>

                <tr>

                    <th>User ID</th>

                    <th>Name</th>

                    <th>Email</th>

                    <th>Role</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>

            <tbody>

            <?php if(mysqli_num_rows($result) > 0) { ?>

                <?php while($row = mysqli_fetch_assoc($result)) { ?>

                    <tr>

                        <!-- USER ID -->

                        <td>

                            <?php echo $row['UserID']; ?>

                        </td>

                        <!-- NAME -->

                        <td>

                            <?php echo $row['Name']; ?>

                        </td>

                        <!-- EMAIL -->

                        <td>

                            <?php echo $row['Email']; ?>

                        </td>

                        <!-- ROLE -->

                        <td>

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

                        </td>

                        <!-- STATUS -->

                        <td>

                            <?php if($row['UserStatus'] == 'Active') { ?>

                                <span class="status-badge status-active">

                                    Active

                                </span>

                            <?php } else { ?>

                                <span class="status-badge status-inactive">

                                    Inactive

                                </span>

                            <?php } ?>

                        </td>

                        <!-- ACTION -->

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

                    <td colspan="6"
                        style="text-align:center; padding:20px;">

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