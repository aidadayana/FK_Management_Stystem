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

// BULLETPROOF ID CHECK: Check for both 'id' and 'EventID' to match whatever your dashboard sends
$event_id = trim($_GET['id'] ?? $_GET['EventID'] ?? '');
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

// 3. Process form submissions (Create OR Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title            = trim($_POST['Title'] ?? '');
    $description      = trim($_POST['Description'] ?? '');
    $event_date       = $_POST['EventDate'] ?? '';
    $event_time       = $_POST['EventTime'] ?? '';
    $venue            = trim($_POST['Venue'] ?? '');
    $max_participants = intval($_POST['MaxParticipants'] ?? 0);
    $club_id          = $_POST['ClubID'] ?? '';
    
    if ($is_edit) {
        $event_status = $_POST['EventStatus'] ?? 'Upcoming';
    } else {
        $event_status = 'Upcoming';
    }

    if (!$title || !$description || !$event_date || !$event_time || !$venue || !$club_id) {
        $error = "All fields are required.";
    } elseif ($max_participants < 0) {
        $error = "Maximum participants cannot be a negative value.";
    } else {
        if ($is_edit) {
            $old_max = $event['MaxParticipants'];

            // UPDATE CRUD EXECUTION
            $u_stmt = $conn->prepare("UPDATE event SET Title=?, Description=?, EventDate=?, EventTime=?, Venue=?, MaxParticipants=?, ClubID=?, EventStatus=? WHERE EventID=?");
            $u_stmt->bind_param('ssssssiss', $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status, $event_id);
            
            if ($u_stmt->execute()) {
                $success = "Event updated successfully!";
                
                // Refresh local data array
                $event['Title'] = $title;
                $event['Description'] = $description;
                $event['EventDate'] = $event_date;
                $event['EventTime'] = $event_time;
                $event['Venue'] = $venue;
                $event['MaxParticipants'] = $max_participants;
                $event['ClubID'] = $club_id;
                $event['EventStatus'] = $event_status;

                // AUTOMATIC WAITLIST PROMOTION
                if ($max_participants > $old_max && $event_status === 'Upcoming') {
                    $slots_opened = $max_participants - $old_max;

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

                        $user_stmt = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
                        $user_stmt->bind_param('s', $next_user_id);
                        $user_stmt->execute();
                        $user_res = $user_stmt->get_result()->fetch_assoc();
                        $student_name = $user_res ? $user_res['Name'] : 'Student';

                        $new_reg_id = 'REG' . strtoupper(uniqid());
                        $today = date('Y-m-d');

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
                $error = "Failed to update record details.";
            }
        } else {
            // CREATE CRUD EXECUTION
            $new_event_id = 'EV' . date('YmdHis');
            $i_stmt = $conn->prepare("INSERT INTO event (EventID, Title, Description, EventDate, EventTime, Venue, MaxParticipants, ClubID, EventStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $i_stmt->bind_param('ssssssiss', $new_event_id, $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status);
            
            if ($i_stmt->execute()) {
                $success = "Event successfully built and posted!";
                $title = $description = $event_date = $event_time = $venue = '';
                $max_participants = 0;
            } else {
                $error = "Failed to insert event row entry.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit Event' : 'Create Event' ?> — FK Management</title>
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
        
        <!-- Debugging Flag Indicator (Visible to you only during tracking error states) -->
        <?php if (!empty($event_id) && !$is_edit): ?>
            <div style="background:#fff3cd; color:#856404; padding:10px; margin-bottom:15px; border-radius:5px; font-size:0.8rem;">
                ⚠️ <strong>System Debug Alert:</strong> The file received an ID (<code><?= htmlspecialchars($event_id) ?></code>), but couldn't find a matching record in the database table. Check your database IDs!
            </div>
        <?php endif; ?>

        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">
            <?= $is_edit ? '⚙️ Edit Event Details' : '✨ Create New Event' ?>
        </h2>
        <div style="font-size:0.85rem;color:#888;">
            <?= $is_edit ? 'Modify properties or change performance parameters for existing records.' : 'Fill in the details to publish a new official event.' ?>
        </div>
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
                    <option value="">-- Select Club --</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= htmlspecialchars($c['ClubID']) ?>" 
                            <?= (($is_edit ? $event['ClubID'] : ($club_id ?? '')) === $c['ClubID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['ClubName']) ?> (<?= htmlspecialchars($c['ClubID']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Title">Event Title</label>
                <input type="text" name="Title" id="Title" class="form-control" 
                       value="<?= htmlspecialchars($is_edit ? $event['Title'] : ($title ?? '')) ?>" required>
            </div>

            <div class="form-group">
                <label for="Description">Description</label>
                <textarea name="Description" id="Description" class="form-control" required><?= htmlspecialchars($is_edit ? $event['Description'] : ($description ?? '')) ?></textarea>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="EventDate">Date</label>
                    <input type="date" name="EventDate" id="EventDate" class="form-control" 
                           value="<?= htmlspecialchars($is_edit ? $event['EventDate'] : ($event_date ?? '')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="EventTime">Time</label>
                    <input type="time" name="EventTime" id="EventTime" class="form-control" 
                           value="<?= htmlspecialchars($is_edit ? $event['EventTime'] : ($event_time ?? '')) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="Venue">Venue / Location</label>
                <input type="text" name="Venue" id="Venue" class="form-control" 
                       value="<?= htmlspecialchars($is_edit ? $event['Venue'] : ($venue ?? '')) ?>" required>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="MaxParticipants">Maximum Participants (Capacity)</label>
                    <input type="number" name="MaxParticipants" id="MaxParticipants" class="form-control" min="0" 
                           value="<?= htmlspecialchars($is_edit ? $event['MaxParticipants'] : ($max_participants ?? 0)) ?>" required>
                </div>
                
                <?php if ($is_edit): ?>
                <div class="form-group">
                    <label for="EventStatus">Event Status</label>
                    <select name="EventStatus" id="EventStatus" class="form-control" required>
                        <option value="Upcoming" <?= ($event['EventStatus'] === 'Upcoming') ? 'selected' : '' ?>>Upcoming</option>
                        <option value="Completed" <?= ($event['EventStatus'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($event['EventStatus'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="ManageEvents.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <?= $is_edit ? '💾 Save Updates' : '✨ Publish Event' ?>
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>