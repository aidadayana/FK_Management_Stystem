<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];
$event_id = trim($_GET['id'] ?? '');

if (!$event_id) {
    header("Location: Manageevents.php");
    exit();
}

// Fetch event details
$s = $conn->prepare("SELECT * FROM event WHERE EventID=?");
$s->bind_param('s', $event_id);
$s->execute();
$event = $s->get_result()->fetch_assoc();

if (!$event) {
    header("Location: Manageevents.php");
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['Title'] ?? '');
    $description = trim($_POST['Description'] ?? '');
    $event_date = $_POST['EventDate'] ?? '';
    $event_time = $_POST['EventTime'] ?? '';
    $venue = trim($_POST['Venue'] ?? '');
    $max_participants = intval($_POST['MaxParticipants'] ?? 0);
    $club_id = $_POST['ClubID'] ?? '';
    $event_status = $_POST['EventStatus'] ?? 'Upcoming';

    if (!$title || !$description || !$event_date || !$event_time || !$venue || !$club_id) {
        $error = "All fields are required.";
    } else {
        $old_max = $event['MaxParticipants'];
        $u_stmt = $conn->prepare("UPDATE event SET Title=?, Description=?, EventDate=?, EventTime=?, Venue=?, MaxParticipants=?, ClubID=?, EventStatus=? WHERE EventID=?");
        $u_stmt->bind_param('ssssssiss', $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status, $event_id);
        
        if ($u_stmt->execute()) {
            $success = "Event updated successfully!";
            $event['Title'] = $title;
            $event['Description'] = $description;
            $event['EventDate'] = $event_date;
            $event['EventTime'] = $event_time;
            $event['Venue'] = $venue;
            $event['MaxParticipants'] = $max_participants;
            $event['ClubID'] = $club_id;
            $event['EventStatus'] = $event_status;

            // Waitlist Promotion Hook
            if ($max_participants > $old_max && $event_status === 'Upcoming') {
                $slots_opened = $max_participants - $old_max;
                $wait_stmt = $conn->prepare("SELECT * FROM waitlist WHERE EventID = ? AND WaitlistStatus = 'Waiting' ORDER BY Queue ASC, WaitJoinDate ASC LIMIT ?");
                $wait_stmt->bind_param('si', $event_id, $slots_opened);
                $wait_stmt->execute();
                $waiting = $wait_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($waiting as $next_user) {
                    $u_lookup = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
                    $u_lookup->bind_param('s', $next_user['UserID']);
                    $u_lookup->execute();
                    $u_res = $u_lookup->get_result()->fetch_assoc();
                    $student_name = $u_res ? $u_res['Name'] : 'Student';

                    $new_reg_id = 'REG' . strtoupper(uniqid());
                    $today = date('Y-m-d');

                    $promo_stmt = $conn->prepare("INSERT INTO event_registration (RegistrationID, EventID, UserID, StudentName, ClubID, RegistrationDate, RegStatus) VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')");
                    $promo_stmt->bind_param('ssssss', $new_reg_id, $event_id, $next_user['UserID'], $student_name, $club_id, $today);
                    
                    if ($promo_stmt->execute()) {
                        $upd_wait = $conn->prepare("UPDATE waitlist SET WaitlistStatus = 'Promoted' WHERE WaitlistID = ?");
                        $upd_wait->bind_param('s', $next_user['WaitlistID']);
                        $upd_wait->execute();
                    }
                }
            }
        } else {
            $error = "Update execution breakdown.";
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
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins', sans-serif; box-sizing: border-box; }
        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { padding: 11px 22px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; text-decoration: none; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
    </style>
</head>
<body>
<?php include 'Navigation.php'; ?>
<div class="main-content">
    <div style="margin-bottom: 25px;">
        <div class="club-subtitle">Event Management</div>
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">⚙️ Edit Event Details</h2>
    </div>

    <div class="form-container">
        <?php if ($error): ?><div style="color:red; margin-bottom:15px;">❌ <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:green; margin-bottom:15px;">✅ <?= $success ?></div><?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Organizing Club</label>
                <select name="ClubID" class="form-control" required>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= $c['ClubID'] ?>" <?= $event['ClubID'] === $c['ClubID'] ? 'selected' : '' ?>><?= htmlspecialchars($c['ClubName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Event Title</label>
                <input type="text" name="Title" class="form-control" value="<?= htmlspecialchars($event['Title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="Description" class="form-control" required><?= htmlspecialchars($event['Description']) ?></textarea>
            </div>
            <div class="row-grid">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="EventDate" class="form-control" value="<?= htmlspecialchars($event['EventDate']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="EventTime" class="form-control" value="<?= htmlspecialchars($event['EventTime']) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Venue / Location</label>
                <input type="text" name="Venue" class="form-control" value="<?= htmlspecialchars($event['Venue']) ?>" required>
            </div>
            <div class="row-grid">
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="MaxParticipants" class="form-control" value="<?= htmlspecialchars($event['MaxParticipants']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Event Status</label>
                    <select name="EventStatus" class="form-control" required>
                        <option value="Upcoming" <?= $event['EventStatus'] === 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="Completed" <?= $event['EventStatus'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= $event['EventStatus'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                <a href="Manageevents.php" class="btn btn-ghost">Back to List</a>
                <button type="submit" class="btn btn-primary">Save Updates</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>