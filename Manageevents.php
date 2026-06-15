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
    header("Location: Manageevents.php?msg=deleted"); exit();
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
    $mc = $conn->prepare("SELECT ClubID FROM membership WHERE UserID=? AND MemberStatus='Active' LIMIT 1");
    $mc->bind_param('s', $user_id);
    $mc->execute();
    $res = $mc->get_result()->fetch_assoc();
    $club_id = $res ? $res['ClubID'] : '';

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
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        table.data-table td { padding: 13px 14px; border-bottom: 1px solid rgba(215,183,163,0.25); font-size: 0.88rem; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming { background: #e3f2fd; color: #1565c0; }
        .badge-Completed { background: #e8f5e9; color: #1b5e20; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }
        .btn { padding: 7px 14px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-outline { background: white; border: 1px solid var(--primary-maroon); color: var(--primary-maroon); }
        .btn-danger { background: var(--danger-red); color: white; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
        <div>
            <div class="club-subtitle">Dashboard</div>
            <h2 style="margin:5px 0 0 0;font-size:1.6rem;color:var(--primary-maroon);">Manage Events</h2>
        </div>
        <a href="Createevent.php" class="btn btn-primary">➕ Create New Event</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div style="background:#fdecea; color:#b71c1c; padding:12px; margin-bottom:20px; border-radius:8px;">🗑️ Event deleted successfully.</div>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event Details</th>
                    <th>Club</th>
                    <th>Date & Time</th>
                    <th>Venue</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:40px;">No events managed yet.</td></tr>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ev['Title']) ?></strong></td>
                    <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                    <td><?= date('d M Y', strtotime($ev['EventDate'])) ?><br><small style="color:#777;"><?= date('h:i A', strtotime($ev['EventTime'])) ?></small></td>
                    <td><?= htmlspecialchars($ev['Venue']) ?></td>
                    <td>
                        <span style="font-weight:600;color:var(--primary-maroon);">
                            <?= $ev['RegCount'] ?> / <?= $ev['MaxParticipants'] > 0 ? $ev['MaxParticipants'] : '∞' ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= htmlspecialchars($ev['EventStatus']) ?>"><?= htmlspecialchars($ev['EventStatus']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px;">
                            <a href="ViewParticipants.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-ghost">👥</a>
                            <a href="Editevent.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-outline">✏️ Edit</a>
                            <?php if ($role === 'R01'): ?>
                            <form method="POST" onsubmit=\"return confirm('Delete this event?');\" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= htmlspecialchars($ev['EventID']) ?>">
                                <button type="submit" class="btn btn-danger">🗑️</button>
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