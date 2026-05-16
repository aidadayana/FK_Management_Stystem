<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']) || $_SESSION['RoleID'] != 'R01') {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])) {
    header("Location: UserManagement.php");
    exit();
}

$userID = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM user WHERE UserID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $update = $conn->prepare("
        UPDATE user 
        SET Name = ?, Email = ?, RoleID = ?
        WHERE UserID = ?
    ");

    $update->bind_param("ssss", $name, $email, $role, $userID);

    if($update->execute()) {
        header("Location: UserManagement.php");
        exit();
    } else {
        echo "Error updating user";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include('Navigation.php'); ?>

<div class="main-content">

    <div class="header-row">
        <h1>EDIT USER</h1>
    </div>

    <div class="modern-form-card">

        <form method="POST" class="form-grid">

            <div class="form-group full-width">
                <label>Name</label>
                <input type="text" name="name"
                       value="<?php echo $user['Name']; ?>" required>
            </div>

            <div class="form-group full-width">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?php echo $user['Email']; ?>" required>
            </div>

            <div class="form-group full-width">
                <label>Role</label>
                <select name="role">

                    <option value="R01" <?php if($user['RoleID']=="R01") echo "selected"; ?>>
                        Admin
                    </option>

                    <option value="R02" <?php if($user['RoleID']=="R02") echo "selected"; ?>>
                        Student
                    </option>

                    <option value="R03" <?php if($user['RoleID']=="R03") echo "selected"; ?>>
                        Committee
                    </option>

                </select>
            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Update
                </button>

                <a href="UserManagement.php" class="btn-cancel">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>