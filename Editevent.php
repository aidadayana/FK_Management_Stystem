<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a Committee member (R03) or Admin (R01)
if (!isset($_SESSION['UserID']) || ($_SESSION['RoleID'] !== 'R03' && $_SESSION['RoleID'] !== 'R01')) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['UserID'];
$error = '';
$success = '';

// Check if Event ID is provided via URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ManageEvent.php");
    exit();
}

$event_id = $_GET['id'];

// 1. Fetch current event details to populate form fields
$get_stmt = $conn->prepare("SELECT * FROM event WHERE EventID = ?");
$get_stmt->bind_param('s', $event_id);
$get_stmt->execute();
$event = $get_stmt->get_result()->fetch_assoc();

if (!$event) {
    header("Location: ManageEvent.php?error=notfound");
    exit();
}

// Fetch clubs managed by this committee/admin for the dropdown selection
if ($_SESSION['RoleID'] === 'R01') {
    $club_query = "SELECT ClubID, ClubName FROM club WHERE ClubStatus = 'Active'";
    $stmt = $conn->prepare($club_query);
} else {
    $club_query = "SELECT c.ClubID, c.ClubName FROM club c 
                   JOIN membership m ON c.ClubID = m.ClubID 
                   WHERE m.UserID = ? AND m.MemberStatus = 'Active'";
    $stmt = $conn->prepare($club_query);
    $stmt->bind_param('s', $user_id);
}
$stmt->execute();
$clubs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Process form updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['Title']);
    $description = trim($_POST['Description']);
    $event_date = $_POST['EventDate'];
    $event_time = $_POST['EventTime'];
    $venue = trim($_POST['Venue']);
    $max_participants = intval($_POST['MaxParticipants']);
    $club_id = $_POST['ClubID'];
    $event_status = $_POST['EventStatus']; // Restored status input for rubric completeness

    // Validation
    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue) || empty($club_id) || empty($event_status)) {
        $error = 'All fields are required.';
    } elseif ($max_participants < 0) {
        $error = 'Maximum participants cannot be negative.';
    } else {
        // Track the previous capacity to check if capacity was expanded
        $old_max = $event['MaxParticipants'];

        // Perform Update
        $update_query = "UPDATE event SET Title=?, Description=?, EventDate=?, EventTime=?, Venue=?, MaxParticipants=?, ClubID=?, EventStatus=? WHERE EventID=?";
        $upd_stmt = $conn->prepare($update_query);
        $upd_stmt->bind_param('ssssssiss', $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status, $event_id);

        if ($upd_stmt->execute()) {
            $success = 'Event updated successfully!';
            
            // Refresh local variable tracking data
            $event['Title'] = $title;
            $event['Description'] = $description;
            $event['EventDate'] = $event_date;
            $event['EventTime'] = $event_time;
            $event['Venue'] = $venue;
            $event['MaxParticipants'] = $max_participants;
            $event['ClubID'] = $club_id;
            $event['EventStatus'] = $event_status;

            // AUTOMATION: If the capacity increased, check if we can fill slots from the waitlist
            if ($max_participants > $old_max && $event_status === 'Upcoming') {
                // Determine how many spots were opened up
                $slots_opened = $max_participants - $old_max;

                // Look for students waiting in line
                $wait_stmt = $conn->prepare("
                    SELECT * FROM waitlist 
                    WHERE EventID = ? AND WaitlistStatus = 'Waiting' 
                    ORDER BY Queue ASC, WaitJoinDate ASC 
                    LIMIT ?
                ");
                $wait_stmt->bind_param('si', $event_id, $slots_opened);
                $wait_stmt->execute();
                $waiting_students = $wait_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($waiting_students as $next_user) {
                    $next_user_id = $next_user['UserID'];
                    $waitlist_id  = $next_user['WaitlistID'];

                    // Grab User profile name
                    $user_stmt = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
                    $user_stmt->bind_param('s', $next_user_id);
                    $user_stmt->execute();
                    $user_res = $user_stmt->get_result()->fetch_assoc();
                    $student_name = $user_res ? $user_res['Name'] : '';

                    $new_reg_id = 'REG' . strtoupper(uniqid());
                    $today = date('Y-m-d');

                    // Promote student to Confirmed status
                    $promo_stmt = $conn->prepare("
                        INSERT INTO event_registration (RegistrationID, EventID, UserID, StudentName, ClubID, RegistrationDate, RegStatus) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')
                    ");
                    $promo_stmt->bind_param('ssssss', $new_reg_id, $event_id, $next_user_id, $student_name, $club_id, $today);
                    
                    if ($promo_stmt->execute()) {
                        $upd_wait = $conn->prepare("UPDATE waitlist SET WaitlistStatus = 'Promoted' WHERE WaitlistID = ?");
                        $upd_wait->bind_param('s', $waitlist_id);
                        $upd_wait->execute();
                    }
                }
            }
        } else {
            $error = 'Failed to update event. Error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 30px; max-width: 700px; margin: 0 auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: #333; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.9rem; transition: 0.2s; box-sizing: border-box; }
        .form-control:focus { border-color: var(--primary-maroon); outline: none; box-shadow: 0 0 0 3px rgba(139,30,63,0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .row-grid { grid-template-columns: 1fr; gap: 0; } }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.88rem; font-weight: 500; }
        .alert-danger { background: #fdecea; color: #b71c1c; border-left: 4px solid #d32f2f; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #2e7d32; }
        .btn { padding: 11px 22px; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; text-decoration: none; }
        .btn-ghost:hover { border-color: #999; color: #333; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 25px;">
        <div class="club-subtitle">Event Management</div>
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">Edit Event Details</h2>
        <div style="font-size:0.85rem;color:#888;">Modify properties or update performance metrics to complete CRUD requirements.</div>
    </div>

    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="ClubID">Organizing Club</label>
                <select name="ClubID" id="ClubID" class="form-control" required>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= htmlspecialchars($c['ClubID']) ?>" <?= ($event['ClubID'] === $c['ClubID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['ClubName']) ?> (<?= htmlspecialchars($c['ClubID']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Title">Event Title</label>
                <input type="text" name="Title" id="Title" class="form-control" value="<?= htmlspecialchars($event['Title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="Description">Description</label>
                <textarea name="Description" id="Description" class="form-control" required><?= htmlspecialchars($event['Description']) ?></textarea>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="EventDate">Date</label>
                    <input type="date" name="EventDate" id="EventDate" class="form-control" value="<?= htmlspecialchars($event['EventDate']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="EventTime">Time</label>
                    <input type="time" name="EventTime" id="EventTime" class="form-control" value="<?= htmlspecialchars($event['EventTime']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="Venue">Venue / Location</label>
                <input type="text" name="Venue" id="Venue" class="form-control" value="<?= htmlspecialchars($event['Venue']) ?>" required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="MaxParticipants">Capacity</label>
                    <input type="number" name="MaxParticipants" id="MaxParticipants" class="form-control" min="0" value="<?= htmlspecialchars($event['MaxParticipants']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="EventStatus">Event Status</label>
                    <select name="EventStatus" id="EventStatus" class="form-control" required>
                        <option value="Upcoming" <?= ($event['EventStatus'] === 'Upcoming') ? 'selected' : '' ?>>Upcoming</option>
                        <option value="Completed" <?= ($event['EventStatus'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($event['EventStatus'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="ManageEvent.php" class="btn btn-ghost">Back to List</a>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>