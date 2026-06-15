<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R02') {
    header("Location: Login.php");
    exit();
}

$user_id      = $_SESSION['UserID'];
$student_name = $_SESSION['Name'] ?? 'Student';
$event_id     = trim($_GET['id']  ?? '');

if (!$event_id) { header("Location: StudentEvent.php"); exit(); }

// Fetch event
$s = $conn->prepare("SELECT e.*, c.ClubName FROM event e JOIN club c ON e.ClubID = c.ClubID WHERE e.EventID = ?");
$s->bind_param('s', $event_id);
$s->execute();
$event = $s->get_result()->fetch_assoc();
if (!$event || $event['EventStatus'] !== 'Upcoming') { header("Location: StudentEvent.php"); exit(); }

// Count confirmed registrations
$rc = $conn->prepare("SELECT COUNT(*) as cnt FROM event_registration WHERE EventID = ? AND RegStatus = 'Confirmed'");
$rc->bind_param('s', $event_id);
$rc->execute();
$reg_count = $rc->get_result()->fetch_assoc()['cnt'];

// Check if already registered
$chk = $conn->prepare("SELECT RegStatus FROM event_registration WHERE EventID = ? AND UserID = ?");
$chk->bind_param('ss', $event_id, $user_id);
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
        // Generate RegistrationID
        $reg_id = 'REG' . strtoupper(uniqid());
        $reg_date = date('Y-m-d');

        if ($existing) {
            // Re-confirm previously cancelled registration
            $upd = $conn->prepare("UPDATE event_registration SET RegStatus='Confirmed', RegistrationDate=? WHERE EventID=? AND UserID=?");
            $upd->bind_param('sss', $reg_date, $event_id, $user_id);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO event_registration (RegistrationID, EventID, UserID, StudentName, ClubID, RegistrationDate, RegStatus) VALUES (?,?,?,?,?,?,'Confirmed')");
            $ins->bind_param('ssssss', $reg_id, $event_id, $user_id, $student_name, $event['ClubID'], $reg_date);
            $ins->execute();
        }
        $success = true;
        $reg_count++;
    }
}

$slots_left = ($event['MaxParticipants'] > 0) ? max(0, $event['MaxParticipants'] - $reg_count) : 'Unlimited';
$is_full    = ($event['MaxParticipants'] > 0) && ($reg_count >= $event['MaxParticipants']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .card { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; }
        .card-title { font-size: 0.8rem; color: var(--primary-maroon); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid rgba(215,183,163,0.4); padding-bottom: 10px; margin-bottom: 18px; }
        .info-row { display: flex; gap: 10px; margin-bottom: 10px; font-size: 0.9rem; align-items: flex-start; }
        .info-label { color: #888; min-width: 100px; font-size: 0.78rem; text-transform: uppercase; font-weight: 600; padding-top: 2px; flex-shrink: 0; }
        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #2e7d32; }
        .alert-error   { background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }
        .alert-warning { background: #fff9e6; border-left: 4px solid #f0a500; color: #5c4000; }
        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .form-group { display: flex; flex-direction: column; margin-bottom: 14px; }
        .form-group label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #777; margin-bottom: 6px; letter-spacing: 0.04em; }
        .form-group input { font-family: 'Poppins', sans-serif; font-size: 0.9rem; color: #888; border: 1.5px solid #e0d6ce; padding: 11px 14px; border-radius: 8px; background: #f8f4f0; width: 100%; margin: 0; cursor: not-allowed; }
        .steps { display: flex; gap: 0; margin-bottom: 24px; }
        .step { flex: 1; text-align: center; padding: 12px; font-size: 0.78rem; font-weight: 600; border-bottom: 3px solid #e0d6ce; color: #aaa; }
        .step.active { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .step.done { border-color: #2e7d32; color: #2e7d32; }
        @media(max-width:768px) { .reg-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="margin-bottom:18px;">
        <a href="EventDetails.php?id=<?= urlencode($event_id) ?>" style="color:var(--primary-maroon);text-decoration:none;font-weight:600;">← Back to Event Details</a>
    </div>

    <div class="club-subtitle">Event Registration</div>

    <!-- Steps indicator -->
    <div class="steps">
        <div class="step <?= !$success ? 'active' : 'done' ?>">1. Review Details</div>
        <div class="step <?= $success ? 'active' : '' ?>">2. Confirm</div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <strong>Registration successful!</strong> You have been registered for <strong><?= htmlspecialchars($event['Title']) ?></strong>.
            <a href="MyRegistrations.php" style="color:var(--primary-maroon);font-weight:600;margin-left:6px;">View My Registrations →</a>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="reg-grid">

        <!-- Event Summary Card -->
        <div class="card">
            <div class="card-title">Event Summary</div>
            <div class="info-row"><span class="info-label">Event</span><strong><?= htmlspecialchars($event['Title']) ?></strong></div>
            <div class="info-row"><span class="info-label">Club</span><?= htmlspecialchars($event['ClubName']) ?></div>
            <div class="info-row"><span class="info-label">Date</span><?= date('d F Y', strtotime($event['EventDate'])) ?></div>
            <div class="info-row"><span class="info-label">Time</span><?= !empty($event['EventTime']) ? date('h:i A', strtotime($event['EventTime'])) : 'TBA' ?></div>
            <div class="info-row"><span class="info-label">Venue</span><?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></div>
            <div class="info-row"><span class="info-label">Description</span><span style="color:#666;font-size:0.85rem;"><?= htmlspecialchars($event['Description']) ?></span></div>

            <div class="alert alert-warning" style="margin-top:16px;margin-bottom:0;">
                <strong>Capacity</strong><br>
                <?= $reg_count ?> registered
                <?php if ($event['MaxParticipants'] > 0): ?>
                    / <?= $event['MaxParticipants'] ?> — <strong><?= $slots_left ?> slots left</strong>
                <?php else: ?>
                    — <strong>Unlimited slots</strong>
                <?php endif; ?>
            </div>
        </div>

        <!-- Student Info & Action Card -->
        <div class="card">
            <div class="card-title">Your Information</div>

            <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" value="<?= htmlspecialchars($user_id) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?= htmlspecialchars($student_name) ?>" disabled>
                </div>
                <p style="font-size:0.82rem;color:#888;margin-bottom:16px;">Your details are pre-filled from your account.</p>

                <?php if ($is_full): ?>
                    <div class="alert alert-error">❌ This event is fully booked. No slots available.</div>
                <?php elseif ($existing && $existing['RegStatus'] === 'Confirmed'): ?>
                    <div class="alert alert-success">✅ You are already registered for this event.</div>
                <?php else: ?>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary">✅ Confirm Registration</button>
                        <a href="EventDetails.php?id=<?= urlencode($event_id) ?>" class="btn btn-ghost">Cancel</a>
                    </div>
                <?php endif; ?>
            </form>
            <?php else: ?>
                <div style="text-align:center;padding:20px 0;">
                    <div style="font-size:3rem;margin-bottom:12px;">🎉</div>
                    <p style="color:#1b5e20;font-weight:600;font-size:1rem;margin-bottom:6px;">Registration Confirmed!</p>
                    <p style="color:#666;font-size:0.88rem;margin-bottom:20px;">We look forward to seeing you at the event.</p>
                    <a href="MyRegistrations.php" class="btn btn-primary">📋 View My Registrations</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>