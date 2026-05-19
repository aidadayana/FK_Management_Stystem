<?php
session_start();
require_once 'db.php';

// Only admin can access
if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R01') {
    header("Location: Login.php");
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $d = $conn->prepare("DELETE FROM events WHERE EventID=?");
    $d->bind_param('i', $_POST['delete_id']);
    $d->execute();
    header("Location: ManageEvents.php?msg=deleted"); exit();
}

// Fetch all events with participant count
$events = $conn->query("
    SELECT e.*, c.ClubName,
    (SELECT COUNT(*) FROM event_registrations er WHERE er.EventID = e.EventID AND er.RegStatus = 'Confirmed') as RegCount
    FROM events e
    JOIN clubs c ON e.ClubID = c.ClubID
    ORDER BY e.EventDate DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style1.css">
    <style>
        .table-wrap { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--border-color); }
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
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
        <div>
            <div class="club-subtitle">Manage Events</div>
            <div style="font-size:0.85rem;color:#888;">Create, edit and delete events</div>
        </div>
        <a href="CreateEvent.php" class="btn btn-primary">+ Create Event</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">✅ Event <?= $_GET['msg'] === 'deleted' ? 'deleted' : 'saved' ?> successfully.</div>
    <?php endif; ?>

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
                <tr><td colspan="8" style="text-align:center;color:#999;font-style:italic;padding:30px;">No events yet.</td></tr>
            <?php else: ?>
                <?php foreach ($events as $i => $ev): ?>
                <tr>
                    <td style="color:#aaa;"><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($ev['EventName']) ?></strong></td>
                    <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                    <td><?= date('d M Y', strtotime($ev['EventDate'])) ?></td>
                    <td><?= htmlspecialchars($ev['Venue'] ?? '—') ?></td>
                    <td>
                        <strong><?= $ev['RegCount'] ?></strong>
                        <?php if ($ev['MaxParticipants'] ?? 0): ?><span style="color:#aaa;"> / <?= $ev['MaxParticipants'] ?></span><?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $ev['Status'] ?>"><?= $ev['Status'] ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="ViewParticipants.php?id=<?= $ev['EventID'] ?>" class="btn btn-ghost btn-sm">👥 Participants</a>
                            <a href="CreateEvent.php?id=<?= $ev['EventID'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this event?');" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $ev['EventID'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </form>
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
