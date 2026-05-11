<?php
session_start();
include("connection.php");

if(isset($_POST['login']))
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM USER WHERE Email='$email' AND Password='$password'";
    $result = mysqli_query($conn,$query);

    if(mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['UserID'] = $row['UserID'];
        $_SESSION['Name'] = $row['Name'];
        $_SESSION['RoleID'] = $row['RoleID'];

        if($row['RoleID'] == 1)
        {
            header("Location: admin_dashboard.php");
        }
        elseif($row['RoleID'] == 2)
        {
            header("Location: student_dashboard.php");
        }
        else
        {
            header("Location: committee_dashboard.php");
        }
    }
    else
    {
        echo "<script>alert('Invalid Login');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="login-container">

    <h1 class="login-title">Club Management</h1>

    <form method="POST">

        <input type="email" name="email" class="input-box" placeholder="Enter Email" required>

        <input type="password" name="password" class="input-box" placeholder="Enter Password" required>

        <button type="submit" name="login" class="btn" style="width:100%; margin-top:20px;">
            Login
        </button>

    </form>

</div>

</body>
</html>