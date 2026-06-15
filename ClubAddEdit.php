<?php
require_once 'db.php';
session_start();

$isEdit = false;
$clubID = $name = $desc = $advisor = "";
$status = "Active";

//fetch existing data for Edit mode
if (isset($_GET['id'])) {
    $isEdit = true;
    $clubID = mysqli_real_escape_string($conn, $_GET['id']);
    
    $sql = "SELECT * FROM club WHERE ClubID = '$clubID'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $name    = $row['ClubName'];
        $desc    = $row['ClubDesc'];
        $advisor = $row['ClubAdvisor'];
        $status  = $row['ClubStatus'];
    }
}

// Auto-generate ClubID for new club
if (!$isEdit) {

    $sql = "SELECT MAX(CAST(SUBSTRING(ClubID,2) AS UNSIGNED)) AS maxID
            FROM club";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    $nextID = ($row['maxID'] ?? 0) + 1;

    $clubID = 'C' . str_pad($nextID, 3, '0', STR_PAD_LEFT);
}

// 2. Handle POST request to Save or Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $formID      = mysqli_real_escape_string($conn, $_POST['club_id']);
    $formName    = trim($_POST['club_name']);
    $formName    = mysqli_real_escape_string($conn, $formName);
    $formDesc    = mysqli_real_escape_string($conn, $_POST['club_desc']);
    $formAdvisor = mysqli_real_escape_string($conn, $_POST['club_advisor']);
    $formStatus  = mysqli_real_escape_string($conn, $_POST['club_status']);

    // Check for duplicate club name
    $check = mysqli_query(
        $conn,
        "SELECT ClubID FROM club
         WHERE ClubName = '$formName'
         AND ClubID != '$formID'"
    );

   if (mysqli_num_rows($check) > 0) {
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
        Swal.fire({
            icon: "error",
            title: "Duplicate Club",
            text: "Club name already exists!"
        }).then(() => {
            window.history.back();
        });
    </script>
    </body>
    </html>';
    exit();
    }
    
    if ($isEdit) {
        $sql = "UPDATE club SET 
                ClubName = '$formName', 
                ClubDesc = '$formDesc', 
                ClubAdvisor = '$formAdvisor', 
                ClubStatus = '$formStatus' 
                WHERE ClubID = '$formID'";
    } else {
        $sql = "INSERT INTO club (ClubID, ClubName, ClubDesc, ClubAdvisor, ClubStatus) 
                VALUES ('$formID', '$formName', '$formDesc', '$formAdvisor', '$formStatus')";
    }

    if (mysqli_query($conn, $sql)) {
        header("Location: ClubList.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $isEdit ? "Edit Club" : "Register Club"; ?> | Smart Campus</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include('Navigation.php'); ?>
    <div class="main-content">
        <div class="header-row">
            <h1><?php echo $isEdit ? "Edit Club Profile" : "Register New Club"; ?></h1>
                 <a href="ClubDetails.php?id=<?php echo $clubID; ?>" class="btn btn-outline">Back to Club Details</a>
        </div>

        <form method="POST" class="modern-form-card">
            <div class="form-grid">
                <div class="form-group">
                    <label>Club ID</label>
                    <input type="text"
                    name="club_id"
                    value="<?php echo htmlspecialchars($clubID); ?>"
                    readonly
                    class="readonly-input">
                    </div>

                <div class="form-group">
                    <label>Current Status</label>
                    <select name="club_status" class="status-select">
                        <option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($status == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Club Name</label>
                    <input type="text" name="club_name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label>Advisor Name</label>
                    <input type="text" name="club_advisor" value="<?php echo htmlspecialchars($advisor); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Club Description</label>
                    <textarea name="club_desc" rows="4"><?php echo htmlspecialchars($desc); ?></textarea>
                </div>

                </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?php echo $isEdit ? "Update Changes" : "Save Club"; ?></button>
                <a href="ClubList.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>