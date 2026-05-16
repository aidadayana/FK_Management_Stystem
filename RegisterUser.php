<?php

session_start();

require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01')
{
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Register User</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <div class="header-row">

        <h1>REGISTER USER</h1>

    </div>

    <div class="modern-form-card">

        <form method="POST"
              action="SaveUser.php"
              class="form-grid">

            <div class="form-group full-width">

                <label>Full Name</label>

                <input type="text"
                       name="name"
                       required>

            </div>

            <div class="form-group full-width">

                <label>Email</label>

                <input type="email"
                       name="email"
                       required>

            </div>

            <div class="form-group full-width">

                <label>Password</label>

                <input type="text"
                       name="password"
                       required>

            </div>

            <div class="form-group full-width">

                <label>Role</label>

                <select name="role" required>

                    <option value="R01">
                        Admin
                    </option>

                    <option value="R02">
                        Student
                    </option>

                    <option value="R03">
                        Committee
                    </option>

                </select>

            </div>

            <div class="form-actions">

                <button type="submit"
                        class="btn btn-primary">

                    Register User

                </button>

                <a href="UserManagement.php"
                   class="btn-cancel">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>