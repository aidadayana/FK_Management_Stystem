<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];

// Handle delete (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $role === 'R01') {
    $d = $conn->prepare("DELETE FROM event WHERE EventID=?");
    $d->bind_param('s', $_POST['delete_id']);
    $d->execute();
    header("Location: ManageEvents.php?msg=deleted"); exit();
}

// Fetch events — committee sees only their club's events
if ($role === 'R01') {
    $events = $conn->query("
        SELECT e.*, c.ClubName,
        (SELECT COUNT(*) FROM event_registration er WHERE er.EventID = e.EventID AND er.RegStatus = 'Confirmed') as RegCount
        FROM event e
        JOIN club c ON e.ClubID = c.ClubID
        ORDER BY e.EventDate DESC
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    // Committee: get their club from membership
    $mc = $conn->prepare("SELECT ClubID FROM membership WHERE UserID=? AND MemberStatus='Active' LIMIT 1");
    $mc->bind_param('s', $user_id);
    $mc->execute();
    $membership = $mc->get_result()->fetch_assoc();
    $club_id    = $membership['ClubID'] ?? null;

    if ($club_id) {
        $st = $conn->prepare("
            SELECT e.*, c.ClubName,
            (SELECT COUNT(*) FROM event_registration er WHERE er.EventID = e.EventID AND er.RegStatus = 'Confirmed') as RegCount
            FROM event e
            JOIN club c ON e.ClubID = c.ClubID
            WHERE e.ClubID = ?
            ORDER BY e.EventDate DESC
        ");
        $st->bind_param('s', $club_id);
        $st->execute();
        $events = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $events = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-wrap { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--border-color); white-space: nowrap; }
        table.data-table td { padding: 13px 14px; border-bottom: 1px solid rgba(215,183,163,0.25); font-size: 0.88rem; }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:hover td { background: rgba(139,30,63,0.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming  { background: #e3f2fd; color: #1565c0; }
        .badge-Completed { background: #f5f5f5; color: #616161; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }

        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; }
        .alert-success { background: #e8f5e9; color: #1b5e20; border-left: 4px solid #2e7d32; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-outline { border: 1.5px solid var(--primary-maroon); color: var(--primary-maroon); background: transparent; }
        .btn-outline:hover { background: var(--primary-maroon); color: white; }
        .btn-danger { background: var(--danger-red); color: white; }
        .btn-danger:hover { filter: brightness(1.1); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }

        .capacity-pill { display: inline-flex; align-items: center; gap: 4px; font-size: 0.82rem; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px;">
        <div>
            <div class="club-subtitle">Manage Events</div>
            <div style="font-size:0.85rem;color:#888;">
                <?= $role === 'R01' ? 'Create, edit and delete all events' : 'Manage your club\'s events' ?>
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="events.php" class="btn btn-ghost">📊 Dashboard</a>
            <a href="CreateEvent.php" class="btn btn-primary">+ Create Event</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">✅ Event <?= $_GET['msg'] === 'deleted' ? 'deleted' : 'saved' ?> successfully.</div>
    <?php endif; ?>

    <?php if (empty($events) && $role === 'R03'): ?>
        <div style="background:white;border-radius:14px;padding:40px;text-align:center;color:#888;">
            <div style="font-size:2.5rem;margin-bottom:12px;">📋</div>
            <p>You are not assigned to any club yet, or your club has no events.</p>
            <p style="font-size:0.85rem;">Contact your administrator to be assigned to a club.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event Title</th>
                    <th>Club</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Participants</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="8" style="text-align:center;color:#999;font-style:italic;padding:30px;">No events yet. <a href="CreateEvent.php" style="color:var(--primary-maroon);">Create one →</a></td></tr>
            <?php else: ?>
                <?php foreach ($events as $i => $ev): ?>
                <tr>
                    <td style="color:#aaa;"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($ev['Title']) ?></strong>
                        <?php if ($ev['Description']): ?>
                        <div style="font-size:0.78rem;color:#aaa;margin-top:2px;"><?= htmlspecialchars(mb_substr($ev['Description'], 0, 60)) ?><?= strlen($ev['Description']) > 60 ? '…' : '' ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                    <td><?= date('d M Y', strtotime($ev['EventDate'])) ?></td>
                    <td><?= htmlspecialchars($ev['Venue'] ?? '—') ?></td>
                    <td>
                        <span class="capacity-pill">
                            <strong><?= $ev['RegCount'] ?></strong>
                            <?php if ($ev['MaxParticipants'] > 0): ?>
                                <span style="color:#aaa;">/ <?= $ev['MaxParticipants'] ?></span>
                            <?php else: ?>
                                <span style="color:#aaa;"> registered</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= htmlspecialchars($ev['EventStatus']) ?>"><?= htmlspecialchars($ev['EventStatus']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px;flex-wrap:wrap;">
                            <a href="EventDetails.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-ghost btn-sm">👁️ View</a>
                            <a href="ViewParticipants.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-ghost btn-sm">👥 Participants</a>
                            <a href="CreateEvent.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                            <?php if ($role === 'R01'): ?>
                            <form method="POST" onsubmit="return confirm('Permanently delete this event?');" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($ev['EventID']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
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
    <?php endif; ?>

</div>
</body>
</html>