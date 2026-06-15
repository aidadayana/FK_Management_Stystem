<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R02') {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['UserID'];

// Handle leave waitlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'])) {
    $upd = $conn->prepare("UPDATE waitlist SET WaitlistStatus='Cancelled' WHERE WaitlistID=? AND UserID=?");
    $upd->bind_param('ss', $_POST['leave_id'], $user_id);
    $upd->execute();
    header("Location: Waitlist.php?msg=left"); exit();
}

// Fetch all waiting entries for this user, with their queue position
$stmt = $conn->prepare("
    SELECT w.*, e.Title, e.EventDate, e.EventTime, e.Venue, e.EventStatus, e.MaxParticipants,
           (SELECT COUNT(*) FROM event_registration er WHERE er.EventID = w.EventID AND er.RegStatus = 'Confirmed') as RegCount
    FROM waitlist w
    JOIN event e ON w.EventID = e.EventID
    WHERE w.UserID = ?
    ORDER BY w.WaitJoinDate DESC
");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$waitlist_entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total    = count($waitlist_entries);
$waiting  = count(array_filter($waitlist_entries, fn($w) => $w['WaitlistStatus'] === 'Waiting'));
$promoted = count(array_filter($waitlist_entries, fn($w) => $w['WaitlistStatus'] === 'Promoted'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Waitlist — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-bar { background: white; padding: 18px 25px; border-radius: 12px; display: flex; gap: 30px; align-items: center; margin-bottom: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex-wrap: wrap; }
        .reg-stat { text-align: center; }
        .reg-stat-num { font-size: 1.9rem; font-weight: 700; color: var(--primary-maroon); display: block; }
        .reg-stat-label { font-size: 0.75rem; color: #888; text-transform: uppercase; font-weight: 600; }
        .reg-divider { height: 40px; width: 1px; background: #eee; }

        .table-wrap { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--border-color); }
        table.data-table td { padding: 13px 14px; border-bottom: 1px solid rgba(215,183,163,0.25); font-size: 0.88rem; }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:hover td { background: rgba(139,30,63,0.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming   { background: #e3f2fd; color: #1565c0; }
        .badge-Completed  { background: #f5f5f5; color: #616161; }
        .badge-Cancelled  { background: #fdecea; color: #b71c1c; }
        .badge-Waiting    { background: #fff3cd; color: #856404; }
        .badge-Promoted   { background: #e8f5e9; color: #1b5e20; }

        .queue-pos { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--primary-maroon); color: white; font-weight: 700; font-size: 0.85rem; }

        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; }
        .alert-info { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1565c0; }
        .alert-note { background: #fff9e6; border-left: 4px solid #f0a500; color: #5c4000; margin-bottom: 22px; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-danger { background: var(--danger-red); color: white; }
        .btn-danger:hover { filter: brightness(1.1); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px;">
        <div>
            <div class="club-subtitle">My Waitlist</div>
            <div style="font-size:0.85rem;color:#888;">Events you're waiting for a slot in</div>
        </div>
        <a href="StudentEvent.php" class="btn btn-primary">🔍 Browse Events</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'left'): ?>
        <div class="alert alert-info">ℹ️ You have left the waiting list.</div>
    <?php endif; ?>

    <?php if ($promoted > 0): ?>
        <div class="alert" style="background:#e8f5e9;color:#1b5e20;border-left:4px solid #2e7d32;">
            🎉 Good news! A slot opened up and you've been automatically moved from the waitlist to confirmed registration. Check <a href="MyRegistrations.php" style="color:#1b5e20;font-weight:600;">My Registrations</a>.
        </div>
    <?php endif; ?>

    <div class="alert alert-note">
        ⏳ When an event reaches its maximum participants, you can join its waiting list from the Register page. If a confirmed student cancels, the next person in line is <strong>automatically promoted</strong> to a confirmed registration.
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="reg-stat">
            <span class="reg-stat-num"><?= $total ?></span>
            <span class="reg-stat-label">Total Entries</span>
        </div>
        <div class="reg-divider"></div>
        <div class="reg-stat">
            <span class="reg-stat-num" style="color:#856404;"><?= $waiting ?></span>
            <span class="reg-stat-label">Currently Waiting</span>
        </div>
        <div class="reg-divider"></div>
        <div class="reg-stat">
            <span class="reg-stat-num" style="color:#1b5e20;"><?= $promoted ?></span>
            <span class="reg-stat-label">Promoted</span>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Event Status</th>
                    <th>Position</th>
                    <th>Capacity</th>
                    <th>Waitlist Status</th>
                    <th>Joined On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($waitlist_entries)): ?>
                <tr><td colspan="10" style="text-align:center;color:#999;font-style:italic;padding:40px;">
                    You're not on any waiting lists.<br>
                    <a href="StudentEvent.php" style="color:var(--primary-maroon);font-weight:600;">Browse Events →</a>
                </td></tr>
            <?php else: ?>
                <?php foreach ($waitlist_entries as $i => $w): ?>
                <tr>
                    <td style="color:#aaa;"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($w['Title']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($w['EventDate'])) ?></td>
                    <td><?= htmlspecialchars($w['Venue'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($w['EventStatus']) ?>"><?= htmlspecialchars($w['EventStatus']) ?></span></td>
                    <td><span class="queue-pos">#<?= $w['Queue'] ?></span></td>
                    <td><?= $w['RegCount'] ?> / <?= $w['MaxParticipants'] > 0 ? $w['MaxParticipants'] : '∞' ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($w['WaitlistStatus']) ?>"><?= htmlspecialchars($w['WaitlistStatus']) ?></span></td>
                    <td style="font-size:0.82rem;color:#888;"><?= date('d M Y', strtotime($w['WaitJoinDate'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="EventDetails.php?id=<?= urlencode($w['EventID']) ?>" class="btn btn-ghost btn-sm">View</a>
                            <?php if ($w['WaitlistStatus'] === 'Waiting'): ?>
                            <form method="POST" onsubmit="return confirm('Leave the waiting list for this event?');" style="display:inline;">
                                <input type="hidden" name="leave_id" value="<?= htmlspecialchars($w['WaitlistID']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Leave</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>