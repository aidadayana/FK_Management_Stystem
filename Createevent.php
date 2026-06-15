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
$event    = null;
$is_edit  = false;

// Fetch event if editing
if ($event_id) {
    $s = $conn->prepare("SELECT * FROM event WHERE EventID=?");
    $s->bind_param('s', $event_id);
    $s->execute();
    $event   = $s->get_result()->fetch_assoc();
    $is_edit = (bool)$event;
}

// Fetch clubs — committee sees only their own club
if ($role === 'R01') {
    $clubs = $conn->query("SELECT ClubID, ClubName FROM club WHERE ClubStatus='Active' ORDER BY ClubName")->fetch_all(MYSQLI_ASSOC);
} else {
    $mc = $conn->prepare("SELECT c.ClubID, c.ClubName FROM membership m JOIN club c ON m.ClubID=c.ClubID WHERE m.UserID=? AND m.MemberStatus='Active' LIMIT 1");
    $mc->bind_param('s', $user_id);
    $mc->execute();
    $clubs = $mc->get_result()->fetch_all(MYSQLI_ASSOC);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['Title'] ?? '');
    $desc    = trim($_POST['Description'] ?? '');
    $date    = $_POST['EventDate'] ?? '';
    $time    = $_POST['EventTime'] ?? '';
    $venue   = trim($_POST['Venue'] ?? '');
    $club_id = trim($_POST['ClubID'] ?? '');
    $max_p   = (int)($_POST['MaxParticipants'] ?? 0);
    $status  = $_POST['EventStatus'] ?? 'Upcoming';

    if (!$title || !$date || !$club_id) {
        $error = 'Event Title, Date, and Club are required.';
    } else {
        if ($is_edit) {
            $u = $conn->prepare("UPDATE event SET Title=?, Description=?, EventDate=?, EventTime=?, Venue=?, ClubID=?, MaxParticipants=?, EventStatus=? WHERE EventID=?");
            $u->bind_param('sssssssis', $title, $desc, $date, $time, $venue, $club_id, $max_p, $status, $event_id);
            $u->execute();
        } else {
            // Generate EventID: EV + timestamp
            $new_id = 'EV' . date('YmdHis');
            $i = $conn->prepare("INSERT INTO event (EventID, Title, Description, EventDate, EventTime, Venue, ClubID, MaxParticipants, EventStatus) VALUES (?,?,?,?,?,?,?,?,?)");
            $i->bind_param('sssssssis', $new_id, $title, $desc, $date, $time, $venue, $club_id, $max_p, $status);
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
    <style>
        .form-card { background: white; padding: 35px; border-radius: 14px; max-width: 900px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #777; margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select { font-family: 'Poppins', sans-serif; font-size: 0.9rem; color: var(--text-dark); border: 1.5px solid #e0d6ce; padding: 11px 14px; border-radius: 8px; background: white; width: 100%; transition: border-color 0.2s; margin: 0; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--primary-maroon); }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--primary-maroon); font-weight: 700; border-bottom: 2px solid #f0ebe5; padding-bottom: 8px; margin-bottom: 18px; }
        .alert-error { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }
        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
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
                    <div class="section-title">Event Details</div>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Event Title *</label>
                            <input type="text" name="Title" placeholder="e.g. Annual Hackathon 2026" required
                                   value="<?= htmlspecialchars($event['Title'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Description</label>
                            <textarea name="Description" rows="4" placeholder="Describe the event…"><?= htmlspecialchars($event['Description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="EventDate" required
                                   value="<?= htmlspecialchars($event['EventDate'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="EventTime"
                                   value="<?= htmlspecialchars($event['EventTime'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Max Participants <span style="color:#aaa;font-weight:400;">(0 = unlimited)</span></label>
                            <input type="number" name="MaxParticipants" min="0"
                                   value="<?= $event['MaxParticipants'] ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <!-- Right: Venue & Club -->
                <div>
                    <div class="section-title">Venue & Club</div>
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Venue</label>
                            <input type="text" name="Venue" placeholder="e.g. Auditorium, Block A"
                                   value="<?= htmlspecialchars($event['Venue'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Organising Club *</label>
                            <select name="ClubID" required>
                                <option value="">-- Select Club --</option>
                                <?php foreach ($clubs as $c): ?>
                                    <option value="<?= htmlspecialchars($c['ClubID']) ?>"
                                        <?= ($event['ClubID'] ?? '') === $c['ClubID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['ClubName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Event Status</label>
                            <select name="EventStatus">
                                <option value="Upcoming"  <?= ($event['EventStatus'] ?? 'Upcoming') === 'Upcoming'  ? 'selected' : '' ?>>Upcoming</option>
                                <option value="Completed" <?= ($event['EventStatus'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= ($event['EventStatus'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <?php if (empty($clubs)): ?>
                        <div class="form-group full">
                            <div style="background:#fff9e6;border-left:4px solid #f0a500;padding:12px;border-radius:8px;font-size:0.85rem;color:#5c4000;">
                                ⚠️ No active club found. Please contact the administrator.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:28px;display:flex;align-items:center;gap:16px;border-top:1px solid #f0ebe5;padding-top:22px;">
                <button type="submit" class="btn btn-primary">
                    <?= $is_edit ? '💾 Update Event' : '✅ Create Event' ?>
                </button>
                <a href="ManageEvents.php" class="link-cancel">Cancel</a>
            </div>
        </form>
    </div>

</div>
</body>
</html>