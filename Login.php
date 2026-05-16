<?php

session_start();

include("db.php");

if(isset($_POST['login']))
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM user
              WHERE Email='$email'
              AND Password='$password'";

    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['UserID'] = $row['UserID'];
        $_SESSION['Name'] = $row['Name'];
        $_SESSION['RoleID'] = $row['RoleID'];

        if($row['RoleID'] == 'R01')
        {
            header("Location: AdminDashboard.php");
            exit();
        }
        elseif($row['RoleID'] == 'R02')
        {
            header("Location: StudentDashboard.php");
            exit();
        }
        elseif($row['RoleID'] == 'R03')
        {
            header("Location: ClubDashboard.php");
            exit();
        }
    }
    else
    {
        echo "<script>alert('Invalid Email or Password');</script>";
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>FK Management System</title>

    <link rel="stylesheet" href="StyleLogin.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="main-container">

    <div class="left-panel">

        <img src="images/logo.png" class="logo-image">

        <h1 class="system-title">
            FK Management System
        </h1>

        <p class="system-text">
            Empowering campus communities through smarter management.
        </p>

    </div>

    <div class="right-panel">

        <div class="login-container">

            <h1 class="login-title">
                Welcome Back
            </h1>

            <p class="login-subtitle">
                Login to continue
            </p>

            <form method="POST">

                <input type="email"
                       name="email"
                       class="input-box"
                       placeholder="Enter Email"
                       required>

                <input type="password"
                       name="password"
                       class="input-box"
                       placeholder="Enter Password"
                       required>

                <button type="submit"
                        name="login"
                        class="btn">

                    Login

                </button>

            </form>

        </div>

    </div>

</div>

</body>

</html>