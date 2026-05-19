<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R02') {
    header("Location: Login.php");
    exit();
}

$event_id    = (int)($_GET['id'] ?? 0);
$user_id     = $_SESSION['UserID'];
$student_name= $_SESSION['FullName'] ?? $_SESSION['Username'] ?? 'Student';
$student_id  = $_SESSION['StudentID'] ?? $user_id;

if (!$event_id) { header("Location: StudentEvent.php"); exit(); }

// Fetch event
$s = $conn->prepare("SELECT e.*, c.ClubName FROM events e JOIN clubs c ON e.ClubID = c.ClubID WHERE e.EventID = ?");
$s->bind_param('i', $event_id);
$s->execute();
$event = $s->get_result()->fetch_assoc();
if (!$event || $event['Status'] !== 'Upcoming') { header("Location: StudentEvent.php"); exit(); }

// Count confirmed registrations
$rc = $conn->prepare("SELECT COUNT(*) as cnt FROM event_registrations WHERE EventID = ? AND RegStatus = 'Confirmed'");
$rc->bind_param('i', $event_id);
$rc->execute();
$reg_count = $rc->get_result()->fetch_assoc()['cnt'];

// Check if already registered
$chk = $conn->prepare("SELECT RegStatus FROM event_registrations WHERE EventID = ? AND UserID = ?");
$chk->bind_param('ii', $event_id, $user_id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $max = $event['MaxParticipants'] ?? 0;
    if ($max > 0 && $reg_count >= $max) {
        $error = 'Sorry, this event is fully booked.';
    } elseif ($existing && $existing['RegStatus'] === 'Confirmed') {
        $error = 'You have already registered for this event.';
    } else {
        if ($existing) {
            // Re-confirm if previously cancelled
            $upd = $conn->prepare("UPDATE event_registrations SET RegStatus='Confirmed' WHERE EventID=? AND UserID=?");
            $upd->bind_param('ii', $event_id, $user_id);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO event_registrations (EventID, UserID, StudentName, RegStatus) VALUES (?,?,?,'Confirmed')");
            $ins->bind_param('iis', $event_id, $user_id, $student_name);
            $ins->execute();
        }
        $success = true;
        $reg_count++;
    }
}

$slots_left = ($event['MaxParticipants'] ?? 0) ? max(0, $event['MaxParticipants'] - $reg_count) : 'Unlimited';
$is_full    = ($event['MaxParticipants'] ?? 0) > 0 && $reg_count >= $event['MaxParticipants'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Event — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style1.css">
    <style>
        .reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .card { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; margin-bottom: 20px; }
        .card-title { font-size: 1rem; color: var(--primary-maroon); font-weight: 600; border-bottom: 2px solid var(--light-gray); padding-bottom: 10px; margin-bottom: 18px; }
        .info-row { display: flex; gap: 10px; margin-bottom: 10px; font-size: 0.9rem; align-items: flex-start; }
        .info-label { color: #888; min-width: 100px; font-size: 0.78rem; text-transform: uppercase; font-weight: 600; padding-top: 2px; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #2e7d32; }
        .alert-error   { background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }
        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 14px; }
        .form-group label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #777; margin-bottom: 6px; letter-spacing: 0.04em; }
        .form-group input { font-family: 'Poppins', sans-serif; font-size: 0.9rem; color: var(--text-dark); border: 1.5px solid #e0d6ce; padding: 11px 14px; border-radius: 8px; background: #f5f5f5; width: 100%; }
        @media(max-width:768px) { .reg-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">

    <div style="margin-bottom:18px;">
        <a href="EventDetails.php?id=<?= $event_id ?>" style="color:var(--primary-maroon);text-decoration:none;font-weight:600;">← Back to Event Details</a>
    </div>

    <div class="club-subtitle">Event Registration</div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ You have successfully registered! <a href="MyRegistrations.php" style="color:var(--primary-maroon);">View My Registrations →</a></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="reg-grid">

        <!-- Event Summary -->
        <div class="card">
            <div class="card-title">Event Summary</div>
            <div class="info-row"><span class="info-label">Event</span><strong><?= htmlspecialchars($event['EventName']) ?></strong></div>
            <div class="info-row"><span class="info-label">Club</span><?= htmlspecialchars($event['ClubName']) ?></div>
            <div class="info-row"><span class="info-label">Date</span><?= date('d F Y', strtotime($event['EventDate'])) ?></div>
            <div class="info-row"><span class="info-label">Time</span><?= !empty($event['EventTime']) ? date('h:i A', strtotime($event['EventTime'])) : 'TBA' ?></div>
            <div class="info-row"><span class="info-label">Venue</span><?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></div>

            <div style="background:#fff9e6;border-left:4px solid #f0a500;padding:12px 16px;border-radius:8px;margin-top:16px;font-size:0.88rem;">
                <strong>Registration Status</strong><br>
                <?= $reg_count ?> registered
                <?php if ($event['MaxParticipants'] ?? 0): ?>
                    / <?= $event['MaxParticipants'] ?> — <strong><?= $slots_left ?> slots left</strong>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Info -->
        <div class="card">
            <div class="card-title">Your Information</div>
            <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" value="<?= htmlspecialchars($student_id) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?= htmlspecialchars($student_name) ?>" disabled>
                </div>
                <p style="font-size:0.82rem;color:#888;margin-bottom:16px;">Your details are pre-filled from your account.</p>
                <?php if ($is_full): ?>
                    <div class="alert alert-error">❌ This event is fully booked.</div>
                <?php elseif ($existing && $existing['RegStatus'] === 'Confirmed'): ?>
                    <div class="alert alert-success">✅ You are already registered.</div>
                <?php else: ?>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn-primary">✅ Confirm Registration</button>
                        <a href="EventDetails.php?id=<?= $event_id ?>" class="btn btn-ghost">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
            <?php else: ?>
                <p style="color:#1b5e20;">Registration confirmed!</p>
                <a href="MyRegistrations.php" class="btn btn-primary" style="margin-top:10px;">View My Registrations</a>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>
