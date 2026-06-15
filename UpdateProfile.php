<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['UserID']))
{
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// =============================
// EMAIL CHECK (avoid duplicate)
// =============================
$check = $conn->prepare("
    SELECT UserID FROM user
    WHERE Email = ? AND UserID != ?
");

$check->bind_param("ss", $email, $userID);
$check->execute();
$result = $check->get_result();

if($result->num_rows > 0)
{
    echo "Email already exists!";
    exit();
}

// =============================
// UPDATE LOGIC
// =============================
if(!empty($password))
{
    if($password !== $confirm)
    {
        echo "Password not match!";
        exit();
    }

    // 🔐 ENCRYPT PASSWORD
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE user
        SET Name = ?, Email = ?, Password = ?
        WHERE UserID = ?
    ");

    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $userID);

}
else
{
    // no password change
    $stmt = $conn->prepare("
        UPDATE user
        SET Name = ?, Email = ?
        WHERE UserID = ?
    ");

    $stmt->bind_param("sss", $name, $email, $userID);
}

// =============================
// EXECUTE
// =============================
if($stmt->execute())
{
    $_SESSION['Name'] = $name;
    header("Location: Profile.php");
    exit();
}
else
{
    echo "Update Failed: " . $conn->error;
}
?>