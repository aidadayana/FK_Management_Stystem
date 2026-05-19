<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R01') {
    header("Location: Login.php");
    exit();
}

$event_id = (int)($_GET['id'] ?? 0);
$event    = null;
$is_edit  = false;

if ($event_id) {
    $s = $conn->prepare("SELECT * FROM events WHERE EventID=?");
    $s->bind_param('i', $event_id);
    $s->execute();
    $event   = $s->get_result()->fetch_assoc();
    $is_edit = (bool)$event;
}

$clubs = $conn->query("SELECT ClubID, ClubName FROM clubs WHERE Status='Active' ORDER BY ClubName")->fetch_all(MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['EventName'] ?? '');
    $desc    = trim($_POST['Description'] ?? '');
    $date    = $_POST['EventDate'] ?? '';
    $time    = $_POST['EventTime'] ?? '';
    $venue   = trim($_POST['Venue'] ?? '');
    $club_id = (int)($_POST['ClubID'] ?? 0);
    $max_p   = (int)($_POST['MaxParticipants'] ?? 0);
    $status  = $_POST['Status'] ?? 'Upcoming';

    if (!$name || !$date || !$club_id) {
        $error = 'Event Name, Date, and Club are required.';
    } else {
        if ($is_edit) {
            $u = $conn->prepare("UPDATE events SET EventName=?,Description=?,EventDate=?,EventTime=?,Venue=?,ClubID=?,MaxParticipants=?,Status=? WHERE EventID=?");
            $u->bind_param('sssssiisi', $name,$desc,$date,$time,$venue,$club_id,$max_p,$status,$event_id);
            $u->execute();
        } else {
            $i = $conn->prepare("INSERT INTO events (EventName,Description,EventDate,EventTime,Venue,ClubID,MaxParticipants,Status) VALUES (?,?,?,?,?,?,?,?)");
            $i->bind_param('sssssiis', $name,$desc,$date,$time,$venue,$club_id,$max_p,$status);
            $i->execute();
        }
        header("Location: ManageEvents.php?msg=saved"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Create' ?> Event — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style1.css">
    <style>
        .form-card { background: white; padding: 35px; border-radius: 14px; max-width: 860px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #777; margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select { font-family: 'Poppins', sans-serif; font-size: 0.9rem; color: var(--text-dark); border: 1.5px solid #e0d6ce; padding: 11px 14px; border-radius: 8px; background: white; width: 100%; transition: border-color 0.2s; margin: 0; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--primary-maroon); }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--primary-maroon); font-weight: 700; border-bottom: 2px solid var(--light-gray); padding-bottom: 8px; margin-bottom: 18px; }
        .alert-error { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }
        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .link-cancel { text-decoration: none; color: #999; font-size: 0.88rem; font-weight: 500; }
        .link-cancel:hover { color: #444; }
        @media(max-width:768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">

    <div style="margin-bottom:18px;">
        <a href="ManageEvents.php" style="color:var(--primary-maroon);text-decoration:none;font-weight:600;">← Back to Manage Events</a>
    </div>

    <div class="club-subtitle"><?= $is_edit ? 'Edit Event' : 'Create New Event' ?></div>

    <?php if ($error): ?><div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:35px;">

                <!-- Left: Event Details -->
                <div>
                    <div class="form-section-title">Event Details</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Event Name *</label>
                            <input type="text" name="EventName" placeholder="e.g. Annual Hackathon 2025" required value="<?= htmlspecialchars($event['EventName'] ?? '') ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="Description" rows="4" placeholder="Describe the event…"><?= htmlspecialchars($event['Description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="EventDate" required value="<?= htmlspecialchars($event['EventDate'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="EventTime" value="<?= htmlspecialchars($event['EventTime'] ?? '') ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>Max Participants (0 = unlimited)</label>
                            <input type="number" name="MaxParticipants" min="0" value="<?= $event['MaxParticipants'] ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <!-- Right: Venue & Club -->
                <div>
                    <div class="form-section-title">Venue & Club</div>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Venue</label>
                            <input type="text" name="Venue" placeholder="e.g. Main Hall, Block A" value="<?= htmlspecialchars($event['Venue'] ?? '') ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>Organising Club *</label>
                            <select name="ClubID" required>
                                <option value="">-- Select Club --</option>
                                <?php foreach ($clubs as $c): ?>
                                    <option value="<?= $c['ClubID'] ?>" <?= ($event['ClubID'] ?? 0) == $c['ClubID'] ? 'selected':'' ?>>
                                        <?= htmlspecialchars($c['ClubName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Status</label>
                            <select name="Status">
                                <option value="Upcoming"  <?= ($event['Status'] ?? 'Upcoming') === 'Upcoming'  ? 'selected':'' ?>>Upcoming</option>
                                <option value="Completed" <?= ($event['Status'] ?? '') === 'Completed' ? 'selected':'' ?>>Completed</option>
                                <option value="Cancelled" <?= ($event['Status'] ?? '') === 'Cancelled' ? 'selected':'' ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:28px;display:flex;align-items:center;gap:16px;">
                <button type="submit" class="btn btn-primary"><?= $is_edit ? '💾 Update Event' : '✅ Create Event' ?></button>
                <a href="ManageEvents.php" class="link-cancel">Cancel</a>
            </div>
        </form>
    </div>

</div>
</body>
</html>
