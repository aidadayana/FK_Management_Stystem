<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a Committee member (R03) or Admin (R01)
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];

// Check for both 'id' and 'EventID' to match whatever your dashboard sends
$event_id = trim($_GET['id'] ?? $_POST['EventID'] ?? '');
$event    = null;
$is_edit  = false;

// 1. Fetch event if editing
if ($event_id !== '') {
    $s = $conn->prepare("SELECT * FROM event WHERE EventID=?");
    $s->bind_param('s', $event_id);
    $s->execute();
    $event   = $s->get_result()->fetch_assoc();
    $is_edit = (bool)$event;
}

// 2. Fetch active clubs dropdown items 
if ($role === 'R01') {
    $clubs = $conn->query("SELECT ClubID, ClubName FROM club WHERE ClubStatus='Active' ORDER BY ClubName")->fetch_all(MYSQLI_ASSOC);
} else {
    $mc = $conn->prepare("SELECT c.ClubID, c.ClubName FROM membership m JOIN club c ON m.ClubID=c.ClubID WHERE m.UserID=? AND m.MemberStatus='Active'");
    $mc->bind_param('s', $user_id);
    $mc->execute();
    $clubs = $mc->get_result()->fetch_all(MYSQLI_ASSOC);
}

$error = '';
$success = '';

// 3. Process Form Submission (Save Updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title            = trim($_POST['Title']);
    $description      = trim($_POST['Description']);
    $event_date       = $_POST['EventDate'];
    $event_time       = $_POST['EventTime'];
    $venue            = trim($_POST['Venue']);
    $max_participants = intval($_POST['MaxParticipants']);
    $club_id          = $_POST['ClubID'];
    $event_status     = $_POST['EventStatus'] ?? 'Upcoming';

    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue) || empty($club_id)) {
        $error = "All fields are required.";
    } else {
        // Track the old capacity before updating to calculate opened slots
        $old_max = intval($event['MaxParticipants']);

        // Update statement using 's' and 'i' parameters safely matching your table column types
        $u_stmt = $conn->prepare("UPDATE event SET Title=?, Description=?, EventDate=?, EventTime=?, Venue=?, MaxParticipants=?, ClubID=?, EventStatus=? WHERE EventID=?");
        $u_stmt->bind_param('sssssiiss', $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status, $event_id);
        
        if ($u_stmt->execute()) {
            
            // AUTOMATIC WAITLIST PROMOTION PIPELINE
            if ($max_participants > $old_max && $event_status === 'Upcoming') {
                $slots_opened = $max_participants - $old_max;

                // Find users next in line on the waiting list
                $wait_stmt = $conn->prepare("SELECT * FROM waitlist WHERE EventID = ? AND WaitlistStatus = 'Waiting' ORDER BY Queue ASC, WaitJoinDate ASC LIMIT ?");
                $wait_stmt->bind_param('si', $event_id, $slots_opened);
                $wait_stmt->execute();
                $waiting = $wait_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($waiting as $next_user) {
                    // Look up student name to complete the registration table row values
                    $u_lookup = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
                    $u_lookup->bind_param('s', $next_user['UserID']);
                    $u_lookup->execute();
                    $u_res = $u_lookup->get_result()->fetch_assoc();
                    $student_name = $u_res ? $u_res['Name'] : 'Student';

                    $new_reg_id = 'REG' . strtoupper(uniqid());
                    $today = date('Y-m-d');

                    // Insert the new confirmed registration record
                    $promo_stmt = $conn->prepare("INSERT INTO event_registration (RegistrationID, EventID, UserID, StudentName, ClubID, RegistrationDate, RegStatus) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')");
                    $promo_stmt->bind_param('sssssss', $new_reg_id, $event_id, $next_user['UserID'], $student_name, $club_id, $today);
                    
                    if ($promo_stmt->execute()) {
                        // Mark waitlist entry status as 'Promoted' so they exit the queue
                        $upd_wait = $conn->prepare("UPDATE waitlist SET WaitlistStatus = 'Promoted' WHERE WaitlistID = ?");
                        $upd_wait->bind_param('s', $next_user['WaitlistID']);
                        $upd_wait->execute();
                    }
                }
            }

            header("Location: Manageevents.php?msg=updated");
            exit();
        } else {
            $error = "Failed to update event: " . $conn->error;
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
</head>
<body>
<?php include 'Navigation.php'; ?>
<div class="main-content">
    <div style="margin-bottom: 25px;">
        <div class="club-subtitle">Event Management</div>
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">⚙️ Edit Event Details</h2>
    </div>

    <div class="form-container" style="background: var(--white); border-radius: 14px; padding: 30px; border: 1px solid rgba(215,183,163,0.4);">
        <?php if ($error): ?><div style="color:red; margin-bottom:15px;">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="EventID" value="<?= htmlspecialchars($event['EventID'] ?? '') ?>">
            
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Organizing Club</label>
                <select name="ClubID" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" required>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= htmlspecialchars($c['ClubID']) ?>" <?= ($event && $event['ClubID'] == $c['ClubID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['ClubName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Event Title</label>
                <input type="text" name="Title" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" value="<?= htmlspecialchars($event['Title'] ?? '') ?>" required>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Description</label>
                <textarea name="Description" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" required><?= htmlspecialchars($event['Description'] ?? '') ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <label style="display:block; margin-bottom:8px;">Date</label>
                    <input type="date" name="EventDate" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" value="<?= htmlspecialchars($event['EventDate'] ?? '') ?>" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px;">Time</label>
                    <input type="time" name="EventTime" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" value="<?= htmlspecialchars($event['EventTime'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px;">Venue</label>
                <input type="text" name="Venue" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" value="<?= htmlspecialchars($event['Venue'] ?? '') ?>" required>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <label style="display:block; margin-bottom:8px;">Maximum Capacity</label>
                    <input type="number" name="MaxParticipants" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" value="<?= htmlspecialchars($event['MaxParticipants'] ?? '0') ?>" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px;">Event Status</label>
                    <select name="EventStatus" class="form-control" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;" required>
                        <option value="Upcoming" <?= ($event && $event['EventStatus'] === 'Upcoming') ? 'selected' : '' ?>>Upcoming</option>
                        <option value="Completed" <?= ($event && $event['EventStatus'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($event && $event['EventStatus'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                <a href="Manageevents.php" class="btn btn-ghost" style="padding:10px 20px; border:1px solid #ddd; border-radius:8px; text-decoration:none; color:#555;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 20px; background:var(--primary-maroon); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">Save Updates</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>