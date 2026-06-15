<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R02') {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['UserID'];
$error_msg = '';

// Handle cancel registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $cancel_id = trim($_POST['cancel_id']);
    
    // BULLETPROOF CANCEL EXECUTION: We bind as string 's' which safely parses both string IDs and integers in MySQL
    $upd = $conn->prepare("UPDATE event_registration SET RegStatus='Cancelled' WHERE RegistrationID=? AND UserID=?");
    $upd->bind_param('ss', $cancel_id, $user_id);
    
    if ($upd->execute()) {
        if ($upd->affected_rows > 0) {
            header("Location: Myregistrations.php?msg=cancelled"); 
            exit();
        } else {
            // If database executed but didn't change rows, catch it here
            $error_msg = "Could not find a matching active registration row to cancel.";
        }
    } else {
        $error_msg = "Database error during cancellation execution: " . $conn->error;
    }
}

// Fetch user registrations ordered by the latest date they joined
$stmt = $conn->prepare("
    SELECT er.*, e.Title, e.EventDate, e.EventTime, e.Venue, e.EventStatus, e.EventID
    FROM event_registration er
    JOIN event e ON er.EventID = e.EventID
    WHERE er.UserID = ?
    ORDER BY er.RegistrationDate DESC, er.RegistrationID DESC
");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total     = count($registrations);
$confirmed = count(array_filter($registrations, fn($r) => $r['RegStatus'] === 'Confirmed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registrations — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid rgba(215,183,163,0.3); box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .stat-val { font-size: 1.8rem; font-weight: 700; color: var(--primary-maroon); }
        .table-wrap { background: var(--white); border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.4); padding: 25px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 11px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.82rem; text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        table.data-table td { padding: 13px 14px; border-bottom: 1px solid rgba(215,183,163,0.25); font-size: 0.88rem; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming { background: #e3f2fd; color: #1565c0; }
        .badge-Completed { background: #e8f5e9; color: #1b5e20; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }
        .badge-Confirmed { background: #e8f5e9; color: #1b5e20; }
        .btn { padding: 7px 14px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 0.8rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-danger { background: var(--danger-red); color: white; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 25px;">
        <div class="club-subtitle">Student Portal</div>
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">My Registered Events</h2>
        <div style="font-size:0.85rem;color:#888;">Track your active and historical event registrations.</div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
        <div style="background:#e8f5e9; color:#1b5e20; padding:12px; margin-bottom:20px; border-radius:8px;">✅ Registration successfully cancelled.</div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div style="background:#fdecea; color:#b71c1c; padding:12px; margin-bottom:20px; border-radius:8px;">❌ <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size:0.85rem;color:#666;">Total Registrations</div>
            <div class="stat-val"><?= $total ?></div>
        </div>
        <div class="stat-card">
            <div style="font-size:0.85rem;color:#666;">Confirmed Slots</div>
            <div class="stat-val" style="color: #2e7d32;"><?= $confirmed ?></div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event Title</th>
                    <th>Event Date & Time</th>
                    <th>Venue</th>
                    <th>Event Status</th>
                    <th>Your Status</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registrations)): ?>
                <tr><td colspan="7" style="text-align:center;color:#999;padding:40px;">You haven't registered for any events yet.</td></tr>
            <?php else: ?>
                <?php foreach ($registrations as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['Title']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['EventDate'])) ?><br><small style="color:#777;"><?= date('h:i A', strtotime($r['EventTime'])) ?></small></td>
                    <td><?= htmlspecialchars($r['Venue'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($r['EventStatus']) ?>"><?= htmlspecialchars($r['EventStatus']) ?></span></td>
                    <td><span class="badge badge-<?= htmlspecialchars($r['RegStatus']) ?>"><?= htmlspecialchars($r['RegStatus']) ?></span></td>
                    <td style="font-weight:500;"><?= date('d M Y', strtotime($r['RegistrationDate'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="EventDetails.php?id=<?= urlencode($r['EventID']) ?>" class="btn btn-ghost btn-sm">View</a>
                            
                            <?php if ($r['RegStatus'] === 'Confirmed' && $r['EventStatus'] === 'Upcoming'): ?>
                            <form method="POST" action="" onsubmit="return confirm('Cancel your registration for this event?');" style="display:inline;">
                                <input type="hidden" name="cancel_id" value="<?= htmlspecialchars($r['RegistrationID']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
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