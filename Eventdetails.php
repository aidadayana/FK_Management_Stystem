<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: Login.php");
    exit();
}

$user_id  = $_SESSION['UserID'];
$role     = $_SESSION['RoleID'];
$event_id = trim($_GET['id'] ?? '');

if (!$event_id) { header("Location: StudentEvent.php"); exit(); }

// Fetch event with club name
$s = $conn->prepare("SELECT e.*, c.ClubName, c.ClubAdvisor FROM event e JOIN club c ON e.ClubID = c.ClubID WHERE e.EventID = ?");
$s->bind_param('s', $event_id);
$s->execute();
$event = $s->get_result()->fetch_assoc();
if (!$event) { header("Location: StudentEvent.php"); exit(); }

// Count confirmed registrations
$rc = $conn->prepare("SELECT COUNT(*) as cnt FROM event_registration WHERE EventID = ? AND RegStatus = 'Confirmed'");
$rc->bind_param('s', $event_id);
$rc->execute();
$reg_count = $rc->get_result()->fetch_assoc()['cnt'];

// Check if current user is registered (students only)
$existing = null;
$existing_wait = null;
if ($role === 'R02') {
    $chk = $conn->prepare("SELECT RegStatus FROM event_registration WHERE EventID = ? AND UserID = ?");
    $chk->bind_param('ss', $event_id, $user_id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();

    $wchk = $conn->prepare("SELECT WaitlistID, Queue FROM waitlist WHERE EventID = ? AND UserID = ? AND WaitlistStatus = 'Waiting'");
    $wchk->bind_param('ss', $event_id, $user_id);
    $wchk->execute();
    $existing_wait = $wchk->get_result()->fetch_assoc();
}

$slots_left = ($event['MaxParticipants'] > 0) ? max(0, $event['MaxParticipants'] - $reg_count) : null;
$is_full    = ($event['MaxParticipants'] > 0) && ($reg_count >= $event['MaxParticipants']);
$is_upcoming = $event['EventStatus'] === 'Upcoming';

// Back URL depends on role
$back_url = ($role === 'R01') ? 'ManageEvents.php' : 'StudentEvent.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['Title']) ?> — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .event-hero {
            background: linear-gradient(135deg, var(--primary-maroon), #5a1228);
            border-radius: 16px; padding: 40px; color: white;
            margin-bottom: 24px; position: relative; overflow: hidden;
        }
        .event-hero::before {
            content: '🎉'; position: absolute; right: 40px; top: 50%;
            transform: translateY(-50%); font-size: 8rem; opacity: 0.08;
        }
        .event-hero h1 { margin: 0 0 10px 0; font-size: 1.8rem; }
        .event-hero .meta-row { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 14px; }
        .event-hero .meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.88rem; opacity: 0.9; }

        .detail-grid { display: grid; grid-template-columns: 1fr 340px; gap: 22px; }
        .card { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; }
        .card-title { font-size: 0.95rem; color: var(--primary-maroon); font-weight: 700; border-bottom: 2px solid rgba(215,183,163,0.4); padding-bottom: 10px; margin-bottom: 18px; text-transform: uppercase; letter-spacing: 0.04em; font-size: 0.8rem; }

        .info-row { display: flex; gap: 10px; margin-bottom: 12px; font-size: 0.9rem; }
        .info-label { color: #888; min-width: 110px; font-size: 0.78rem; text-transform: uppercase; font-weight: 600; padding-top: 2px; flex-shrink: 0; }

        .capacity-bar { background: #f0f0f0; border-radius: 20px; height: 8px; margin: 8px 0 4px; overflow: hidden; }
        .capacity-fill { height: 100%; border-radius: 20px; background: var(--primary-maroon); transition: width 0.5s; }
        .capacity-fill.full { background: var(--danger-red); }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming  { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.4); }
        .badge-Completed { background: rgba(0,0,0,0.2); color: rgba(255,255,255,0.7); }
        .badge-Cancelled { background: rgba(230,57,70,0.3); color: #ffcdd2; }

        .reg-status-box { padding: 14px 18px; border-radius: 10px; margin-bottom: 16px; font-size: 0.88rem; }
        .reg-already   { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #2e7d32; }
        .reg-full      { background: #fdecea; color: #b71c1c; border-left: 4px solid #c62828; }
        .reg-open      { background: #e3f2fd; color: #1565c0; border-left: 4px solid #1976d2; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; width: 100%; justify-content: center; padding: 12px; font-size: 0.95rem; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-outline { border: 1.5px solid var(--primary-maroon); color: var(--primary-maroon); background: transparent; }

        @media(max-width:900px) { .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="margin-bottom:18px;">
        <a href="<?= $back_url ?>" style="color:var(--primary-maroon);text-decoration:none;font-weight:600;">← Back</a>
    </div>

    <!-- Hero -->
    <div class="event-hero">
        <span class="badge badge-<?= htmlspecialchars($event['EventStatus']) ?>" style="margin-bottom:12px;display:inline-block;"><?= htmlspecialchars($event['EventStatus']) ?></span>
        <h1><?= htmlspecialchars($event['Title']) ?></h1>
        <p style="opacity:0.8;margin:0;font-size:0.9rem;"><?= htmlspecialchars($event['Description']) ?></p>
        <div class="meta-row">
            <div class="meta-item">📅 <?= date('l, d F Y', strtotime($event['EventDate'])) ?></div>
            <?php if ($event['EventTime']): ?><div class="meta-item">🕐 <?= date('h:i A', strtotime($event['EventTime'])) ?></div><?php endif; ?>
            <div class="meta-item">📍 <?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></div>
            <div class="meta-item">🏛️ <?= htmlspecialchars($event['ClubName']) ?></div>
        </div>
    </div>

    <div class="detail-grid">

        <!-- Left: Details -->
        <div>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-title">Event Information</div>
                <div class="info-row"><span class="info-label">Event ID</span><span><?= htmlspecialchars($event['EventID']) ?></span></div>
                <div class="info-row"><span class="info-label">Organiser</span><span><?= htmlspecialchars($event['ClubName']) ?></span></div>
                <div class="info-row"><span class="info-label">Advisor</span><span><?= htmlspecialchars($event['ClubAdvisor']) ?></span></div>
                <div class="info-row"><span class="info-label">Date</span><span><?= date('d F Y', strtotime($event['EventDate'])) ?></span></div>
                <div class="info-row"><span class="info-label">Time</span><span><?= $event['EventTime'] ? date('h:i A', strtotime($event['EventTime'])) : 'TBA' ?></span></div>
                <div class="info-row"><span class="info-label">Venue</span><span><?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></span></div>
                <div class="info-row"><span class="info-label">Status</span><span><?= htmlspecialchars($event['EventStatus']) ?></span></div>
            </div>

            <div class="card">
                <div class="card-title">Participant Summary</div>
                <div style="display:flex;gap:30px;margin-bottom:16px;">
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:var(--primary-maroon);"><?= $reg_count ?></div>
                        <div style="font-size:0.75rem;color:#888;text-transform:uppercase;">Registered</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:<?= $is_full ? 'var(--danger-red)' : '#1b5e20' ?>;"><?= $slots_left !== null ? $slots_left : '∞' ?></div>
                        <div style="font-size:0.75rem;color:#888;text-transform:uppercase;">Slots Left</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:#555;"><?= $event['MaxParticipants'] > 0 ? $event['MaxParticipants'] : '∞' ?></div>
                        <div style="font-size:0.75rem;color:#888;text-transform:uppercase;">Capacity</div>
                    </div>
                </div>
                <?php if ($event['MaxParticipants'] > 0): ?>
                <?php $pct = min(100, round($reg_count / $event['MaxParticipants'] * 100)); ?>
                <div class="capacity-bar"><div class="capacity-fill <?= $is_full ? 'full' : '' ?>" style="width:<?= $pct ?>%"></div></div>
                <div style="font-size:0.78rem;color:#888;"><?= $pct ?>% full</div>
                <?php endif; ?>

                <?php if ($role === 'R01'): ?>
                <div style="margin-top:16px;">
                    <a href="ViewParticipants.php?id=<?= urlencode($event_id) ?>" class="btn btn-outline" style="margin-top:8px;">👥 View All Participants</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Registration Action -->
        <div>
            <div class="card">
                <div class="card-title">Registration</div>

                <?php if ($role === 'R02'): ?>
                    <?php if ($existing && $existing['RegStatus'] === 'Confirmed'): ?>
                        <div class="reg-status-box reg-already">✅ You are registered for this event.</div>
                        <a href="MyRegistrations.php" class="btn btn-ghost" style="width:100%;justify-content:center;">View My Registrations</a>
                    <?php elseif ($existing_wait): ?>
                        <div class="reg-status-box reg-full">⏳ You are on the waiting list — position #<?= $existing_wait['Queue'] ?>.</div>
                        <a href="Waitlist.php" class="btn btn-ghost" style="width:100%;justify-content:center;">View My Waitlist</a>
                    <?php elseif (!$is_upcoming): ?>
                        <div class="reg-status-box reg-full">This event is <?= strtolower($event['EventStatus']) ?>. Registration is closed.</div>
                    <?php elseif ($is_full): ?>
                        <div class="reg-status-box reg-full">❌ This event is fully booked.</div>
                        <a href="RegisterEvent.php?id=<?= urlencode($event_id) ?>" class="btn btn-primary">⏳ Join Waiting List</a>
                    <?php else: ?>
                        <div class="reg-status-box reg-open">✅ Registration is open!</div>
                        <a href="RegisterEvent.php?id=<?= urlencode($event_id) ?>" class="btn btn-primary">Register Now →</a>
                    <?php endif; ?>

                <?php elseif ($role === 'R03'): ?>
                    <p style="font-size:0.88rem;color:#666;margin-bottom:12px;">You are viewing this event as a committee member.</p>
                    <a href="ManageEvents.php" class="btn btn-ghost" style="width:100%;justify-content:center;">Go to Manage Events</a>

                <?php elseif ($role === 'R01'): ?>
                    <p style="font-size:0.88rem;color:#666;margin-bottom:12px;">Admin view.</p>
                    <a href="CreateEvent.php?id=<?= urlencode($event_id) ?>" class="btn btn-ghost" style="width:100%;justify-content:center;margin-bottom:8px;">✏️ Edit Event</a>
                    <a href="ViewParticipants.php?id=<?= urlencode($event_id) ?>" class="btn btn-outline" style="display:flex;justify-content:center;margin-top:8px;">👥 Participants</a>
                <?php endif; ?>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(215,183,163,0.3);">
                    <div style="font-size:0.75rem;color:#aaa;text-transform:uppercase;font-weight:600;margin-bottom:8px;">Quick Info</div>
                    <div style="font-size:0.82rem;color:#666;display:flex;flex-direction:column;gap:5px;">
                        <span>📅 <?= date('d M Y', strtotime($event['EventDate'])) ?></span>
                        <span>🕐 <?= $event['EventTime'] ? date('h:i A', strtotime($event['EventTime'])) : 'TBA' ?></span>
                        <span>📍 <?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
</body>
</html>