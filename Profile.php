<?php

session_start();

require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

/* GET USER DATA */

$sql = "
    SELECT Name, Email, RoleID
    FROM user
    WHERE UserID = '$userID'
";

$result = mysqli_query($conn, $sql);

$user = mysqli_fetch_assoc($result);

if(!$user)
{
    echo "User not found";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>My Profile</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <!-- HEADER -->

    <div class="header-row">

        <h1>MY PROFILE</h1>

    </div>

    <p class="club-subtitle">

        View and manage your personal account information

    </p>

    <!-- PROFILE CARD -->

    <div class="profile-main-box">

        <div class="profile-header-bg"></div>

        <div class="profile-content">

            <div class="profile-info-main">

                <div class="title-status">

                    <h2>

                        <?php echo $user['Name']; ?>

                    </h2>

                    <span class="status-pill active">

                        <?php echo $user['RoleID']; ?>

                    </span>

                </div>

                <div class="header-divider"></div>

                <p class="advisor-name">

                    Email:
                    <?php echo $user['Email']; ?>

                </p>

                <p class="advisor-name">

                    User ID:
                    <?php echo $userID; ?>

                </p>

            </div>

        </div>

        <!-- FOOTER ACTION -->

        <div class="profile-footer">

            <a href="EditProfile.php"
               class="btn btn-primary">

                Edit Profile

            </a>


        </div>

    </div>

</div>

</body>
</html>