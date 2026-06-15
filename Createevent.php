<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];

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
    $event_status = 'Upcoming'; 

    if (!$title || !$description || !$event_date || !$event_time || !$venue || !$club_id) {
        $error = "All fields are required.";
    } elseif ($max_participants < 0) {
        $error = "Maximum participants cannot be negative.";
    } else {
        $new_event_id = 'EV' . date('YmdHis');
        $i_stmt = $conn->prepare("INSERT INTO event (EventID, Title, Description, EventDate, EventTime, Venue, MaxParticipants, ClubID, EventStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $i_stmt->bind_param('ssssssiss', $new_event_id, $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status);
        
        if ($i_stmt->execute()) {
            $success = "Event successfully built and posted!";
            $title = $description = $event_date = $event_time = $venue = '';
            $max_participants = 0;
        } else {
            $error = "Failed to insert event.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event — FK Management</title>
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
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">✨ Create New Event</h2>
    </div>

    <div class="form-container">
        <?php if ($error): ?><div style="color:red; margin-bottom:15px;">❌ <?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:green; margin-bottom:15px;">✅ <?= $success ?></div><?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Organizing Club</label>
                <select name="ClubID" class="form-control" required>
                    <option value="">-- Select Club --</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= $c['ClubID'] ?>"><?= htmlspecialchars($c['ClubName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Event Title</label>
                <input type="text" name="Title" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="Description" class="form-control" required></textarea>
            </div>
            <div class="row-grid">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="EventDate" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="EventTime" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Venue / Location</label>
                <input type="text" name="Venue" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Capacity</label>
                <input type="number" name="MaxParticipants" class="form-control" min="0" required>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px;">
                <a href="Manageevents.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Publish Event</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>