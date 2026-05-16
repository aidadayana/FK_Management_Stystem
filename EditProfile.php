<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$stmt = $conn->prepare("
    SELECT Name, Email
    FROM user
    WHERE UserID = ?
");

$stmt->bind_param("s", $userID);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Profile</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <div class="header-row">
        <h1>EDIT PROFILE</h1>
    </div>

    <div class="modern-form-card">

        <form method="POST"
              action="UpdateProfile.php"
              class="form-grid">

            <div class="form-group full-width">

                <label>Name</label>

                <input type="text"
                       name="name"
                       value="<?php echo $user['Name']; ?>"
                       required>

            </div>

            <div class="form-group full-width">

                <label>Email</label>

                <input type="email"
                       name="email"
                       value="<?php echo $user['Email']; ?>"
                       required>

            </div>

            <div class="form-actions">

                <button type="submit"
                        class="btn btn-primary">

                    Save Changes

                </button>

                <a href="Profile.php"
                   class="btn-cancel">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>