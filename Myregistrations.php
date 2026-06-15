<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || $_SESSION['RoleID'] !== 'R02') {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['UserID'];

// Handle cancel registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    
    // 1. Get the EventID first before cancelling so we know which event freed up a slot
    $evt_stmt = $conn->prepare("SELECT EventID, ClubID FROM event_registration WHERE RegistrationID=? AND UserID=?");
    $evt_stmt->bind_param('ss', $_POST['cancel_id'], $user_id);
    $evt_stmt->execute();
    $evt_res = $evt_stmt->get_result()->fetch_assoc();
    
    if ($evt_res) {
        $event_id = $evt_res['EventID'];
        $club_id  = $evt_res['ClubID'];

        // 2. Cancel the current user's registration
        $upd = $conn->prepare("UPDATE event_registration SET RegStatus='Cancelled' WHERE RegistrationID=? AND UserID=?");
        $upd->bind_param('ss', $_POST['cancel_id'], $user_id);
        $upd->execute();

        // 3. AUTOMATIC WAITLIST PROMOTION
        // Find the oldest active waitlisted user for this event (First In, First Out based on Queue)
        $wait_stmt = $conn->prepare("
            SELECT * FROM waitlist 
            WHERE EventID = ? AND WaitlistStatus = 'Waiting' 
            ORDER BY Queue ASC, WaitJoinDate ASC 
            LIMIT 1
        ");
        $wait_stmt->bind_param('s', $event_id);
        $wait_stmt->execute();
        $next_user = $wait_stmt->get_result()->fetch_assoc();

        if ($next_user) {
            $next_user_id = $next_user['UserID'];
            $waitlist_id  = $next_user['WaitlistID'];

            // Fetch student name from the user table to keep data consistent with event_registration structure
            $user_stmt = $conn->prepare("SELECT Name FROM user WHERE UserID = ?");
            $user_stmt->bind_param('s', $next_user_id);
            $user_stmt->execute();
            $user_res = $user_stmt->get_result()->fetch_assoc();
            $student_name = $user_res ? $user_res['Name'] : '';

            // Generate a fresh unique Registration ID
            $new_reg_id = 'REG' . strtoupper(uniqid());
            $today = date('Y-m-d');

            // Insert the waitlisted user into event_registration as 'Confirmed'
            $promo_stmt = $conn->prepare("
                INSERT INTO event_registration (RegistrationID, EventID, UserID, StudentName, ClubID, RegistrationDate, RegStatus) 
                VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')
            ");
            $promo_stmt->bind_param('ssssss', $new_reg_id, $event_id, $next_user_id, $student_name, $club_id, $today);
            
            if ($promo_stmt->execute()) {
                // Update waitlist entry to reflect that they have been promoted
                $upd_wait = $conn->prepare("UPDATE waitlist SET WaitlistStatus = 'Promoted' WHERE WaitlistID = ?");
                $upd_wait->bind_param('s', $waitlist_id);
                $upd_wait->execute();
            }
        }
    }

    header("Location: MyRegistrations.php?msg=cancelled"); 
    exit();
}

// Fetch all registrations for this user
$stmt = $conn->prepare("
    SELECT er.*, e.Title, e.EventDate, e.EventTime, e.Venue, e.EventStatus, e.EventID
    FROM event_registration er
    JOIN event e ON er.EventID = e.EventID
    WHERE er.UserID = ?
    ORDER BY e.EventDate DESC
");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$registrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total     = count($registrations);
$confirmed = count(array_filter($registrations, fn($r) => $r['RegStatus'] === 'Confirmed'));
$cancelled = count(array_filter($registrations, fn($r) => $r['RegStatus'] === 'Cancelled'));
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
        .badge-Confirmed  { background: #e8f5e9; color: #1b5e20; }

        .alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; }
        .alert-info { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1565c0; }

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
            <div class="club-subtitle">My Registrations</div>
            <div style="font-size:0.85rem;color:#888;">Track all your event registrations</div>
        </div>
        <a href="StudentEvent.php" class="btn btn-primary">🔍 Browse Events</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
        <div class="alert alert-info">ℹ️ Your registration has been cancelled successfully.</div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="reg-stat">
            <span class="reg-stat-num"><?= $total ?></span>
            <span class="reg-stat-label">Total</span>
        </div>
        <div class="reg-divider"></div>
        <div class="reg-stat">
            <span class="reg-stat-num" style="color:#1b5e20;"><?= $confirmed ?></span>
            <span class="reg-stat-label">Confirmed</span>
        </div>
        <div class="reg-divider"></div>
        <div class="reg-stat">
            <span class="reg-stat-num" style="color:var(--danger-red);"><?= $cancelled ?></span>
            <span class="reg-stat-label">Cancelled</span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Event Status</th>
                    <th>Reg Status</th>
                    <th>Registered On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registrations)): ?>
                <tr><td colspan="8" style="text-align:center;color:#999;font-style:italic;padding:40px;">
                    You haven't registered for any events yet.<br>
                    <a href="StudentEvent.php" style="color:var(--primary-maroon);font-weight:600;">Browse Events →</a>
                </td></tr>
            <?php else: ?>
                <?php foreach ($registrations as $i => $r): ?>
                <tr>
                    <td style="color:#aaa;"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($r['Title']) ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['EventDate'])) ?></td>
                    <td><?= htmlspecialchars($r['Venue'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($r['EventStatus']) ?>"><?= htmlspecialchars($r['EventStatus']) ?></span></td>
                    <td><span class="badge badge-<?= htmlspecialchars($r['RegStatus']) ?>"><?= htmlspecialchars($r['RegStatus']) ?></span></td>
                    <td style="font-size:0.82rem;color:#888;"><?= date('d M Y', strtotime($r['RegistrationDate'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="EventDetails.php?id=<?= urlencode($r['EventID']) ?>" class="btn btn-ghost btn-sm">View</a>
                            <?php if ($r['RegStatus'] === 'Confirmed' && $r['EventStatus'] === 'Upcoming'): ?>
                            <form method="POST" onsubmit="return confirm('Cancel your registration for this event?');" style="display:inline;">
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