<?php
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];

$event_id = trim($_GET['id'] ?? '');
if (!$event_id) { header("Location: ManageEvents.php"); exit(); }

$s = $conn->prepare("SELECT e.*, c.ClubName FROM event e JOIN club c ON e.ClubID=c.ClubID WHERE e.EventID=?");
$s->bind_param('s', $event_id);
$s->execute();
$event = $s->get_result()->fetch_assoc();
if (!$event) { header("Location: ManageEvents.php"); exit(); }

// Get participants
$p = $conn->prepare("
    SELECT er.*, u.Name, u.Email, u.UserID as UID
    FROM event_registration er
    JOIN user u ON er.UserID = u.UserID
    WHERE er.EventID = ?
    ORDER BY er.RegistrationDate ASC
");
$p->bind_param('s', $event_id);
$p->execute();
$participants = $p->get_result()->fetch_all(MYSQLI_ASSOC);

$confirmed = count(array_filter($participants, fn($x) => $x['RegStatus'] === 'Confirmed'));
$cancelled = count(array_filter($participants, fn($x) => $x['RegStatus'] === 'Cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 22px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; border-top: 4px solid var(--primary-maroon); box-shadow: 0 4px 10px rgba(0,0,0,0.06); text-align: center; }
        .stat-card .stat-num { font-size: 2rem; font-weight: 700; color: var(--primary-maroon); }
        .stat-card .stat-label { font-size: 0.78rem; color: #888; margin-top: 4px; text-transform: uppercase; }

        .event-info-bar { background: white; padding: 18px 22px; border-radius: 12px; margin-bottom: 20px; display: flex; gap: 24px; flex-wrap: wrap; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .info-item { font-size: 0.85rem; color: #666; }
        .info-item strong { color: var(--text-dark); }

        .table-wrap { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--border-color); }
        table.data-table td { padding: 13px 14px; border-bottom: 1px solid rgba(215,183,163,0.25); font-size: 0.88rem; }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:hover td { background: rgba(139,30,63,0.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Confirmed { background: #e8f5e9; color: #1b5e20; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }

        @media(max-width:768px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="margin-bottom:18px;">
        <a href="ManageEvents.php" style="color:var(--primary-maroon);text-decoration:none;font-weight:600;">← Back to Manage Events</a>
    </div>

    <div class="club-subtitle">👥 Participants: <?= htmlspecialchars($event['Title']) ?></div>

    <!-- Event Info Bar -->
    <div class="event-info-bar">
        <div class="info-item">📅 <strong><?= date('d M Y', strtotime($event['EventDate'])) ?></strong></div>
        <div class="info-item">🕐 <strong><?= $event['EventTime'] ? date('h:i A', strtotime($event['EventTime'])) : 'TBA' ?></strong></div>
        <div class="info-item">📍 <strong><?= htmlspecialchars($event['Venue'] ?? 'TBA') ?></strong></div>
        <div class="info-item">🏛️ <strong><?= htmlspecialchars($event['ClubName']) ?></strong></div>
        <div class="info-item">
            <span class="badge badge-<?= htmlspecialchars($event['EventStatus']) ?>" style="font-size:0.78rem;"><?= htmlspecialchars($event['EventStatus']) ?></span>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-num"><?= count($participants) ?></div>
            <div class="stat-label">Total Registered</div>
        </div>
        <div class="stat-card" style="border-top-color:#1b5e20;">
            <div class="stat-num" style="color:#1b5e20;"><?= $confirmed ?></div>
            <div class="stat-label">Confirmed</div>
        </div>
        <div class="stat-card" style="border-top-color:var(--danger-red);">
            <div class="stat-num" style="color:var(--danger-red);"><?= $cancelled ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $event['MaxParticipants'] > 0 ? $event['MaxParticipants'] : '∞' ?></div>
            <div class="stat-label">Max Capacity</div>
        </div>
    </div>

    <!-- Participants Table -->
    <div class="table-wrap">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-size:0.85rem;color:#888;"><?= count($participants) ?> participant(s) found</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Reg Status</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($participants)): ?>
                <tr><td colspan="6" style="text-align:center;color:#999;padding:30px;font-style:italic;">No participants registered yet.</td></tr>
            <?php else: ?>
                <?php foreach ($participants as $i => $pt): ?>
                <tr>
                    <td style="color:#aaa;"><?= $i + 1 ?></td>
                    <td><code style="background:#f0f0f0;padding:2px 8px;border-radius:4px;font-size:0.85rem;"><?= htmlspecialchars($pt['UserID']) ?></code></td>
                    <td><strong><?= htmlspecialchars($pt['Name']) ?></strong></td>
                    <td style="color:#666;"><?= htmlspecialchars($pt['Email'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($pt['RegStatus']) ?>"><?= htmlspecialchars($pt['RegStatus']) ?></span></td>
                    <td style="font-size:0.82rem;color:#888;"><?= date('d M Y', strtotime($pt['RegistrationDate'])) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>