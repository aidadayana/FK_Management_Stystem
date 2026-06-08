<?php
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R02', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$keyword = trim($_GET['keyword'] ?? '');
$status  = $_GET['status'] ?? '';

$where  = [];
$params = [];
$types  = '';

if ($keyword !== '') {
    $where[]  = "(e.Title LIKE ? OR e.Venue LIKE ? OR c.ClubName LIKE ?)";
    $like     = "%$keyword%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
if (in_array($status, ['Upcoming', 'Completed', 'Cancelled'])) {
    $where[]  = "e.EventStatus = ?";
    $params[] = $status;
    $types   .= 's';
}

$sql  = "SELECT e.*, c.ClubName FROM event e
         JOIN club c ON e.ClubID = c.ClubID"
      . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
      . " ORDER BY e.EventDate ASC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Nearest upcoming event for highlight banner
$highlight = $conn->query("
    SELECT e.*, c.ClubName FROM event e
    JOIN club c ON e.ClubID = c.ClubID
    WHERE e.EventStatus = 'Upcoming'
    ORDER BY e.EventDate ASC LIMIT 1
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 22px;
        }
        .event-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid rgba(139,30,63,0.08);
            display: flex;
            flex-direction: column;
            transition: 0.3s;
        }
        .event-card:hover { transform: translateY(-6px); box-shadow: 0 12px 28px rgba(139,30,63,0.13); }
        .event-card-banner {
            height: 130px;
            background: linear-gradient(135deg, var(--primary-maroon), #5a1228);
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; color: rgba(255,255,255,0.25);
        }
        .event-card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .event-card-body h3 { font-size: 0.98rem; color: var(--text-dark); margin: 0 0 4px 0; }
        .event-card-meta { font-size: 0.8rem; color: #777; }
        .event-card-footer { padding: 14px 18px; border-top: 1px solid rgba(215,183,163,0.35); }

        .highlight-banner {
            background: linear-gradient(135deg, var(--primary-maroon), #5a1228);
            color: white; padding: 28px 32px; border-radius: 16px;
            margin-bottom: 28px; display: flex;
            justify-content: space-between; align-items: center;
            box-shadow: 0 8px 25px rgba(139,30,63,0.25); gap: 20px; flex-wrap: wrap;
        }
        .highlight-banner h2 { margin: 0 0 6px 0; font-size: 1.4rem; }
        .highlight-banner p  { margin: 0; opacity: 0.8; font-size: 0.88rem; }
        .highlight-meta { font-size: 0.82rem; opacity: 0.7; margin-top: 8px; }

        .search-bar {
            background: white; padding: 16px 20px; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 22px;
            display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
        }
        .search-bar input, .search-bar select {
            padding: 9px 14px; border: 1.5px solid #e0d6ce; border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 0.88rem;
            flex: 1; min-width: 160px; color: var(--text-dark); margin: 0;
        }
        .search-bar input:focus, .search-bar select:focus { outline: none; border-color: var(--primary-maroon); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming  { background: #e3f2fd; color: #1565c0; }
        .badge-Completed { background: #f5f5f5; color: #616161; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: white; border: 1px solid #ddd; color: #555; }
        .btn-ghost:hover { border-color: var(--primary-maroon); color: var(--primary-maroon); }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }
        .btn-white { background: white; color: var(--primary-maroon); font-weight: 700; padding: 10px 22px; }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
        <div>
            <div class="club-subtitle">Browse Events</div>
            <div style="font-size:0.85rem;color:#888;">Find and register for upcoming events</div>
        </div>
        <a href="MyRegistrations.php" class="btn btn-ghost">📋 My Registrations</a>
    </div>

    <!-- Highlight Banner -->
    <?php if ($highlight): ?>
    <div class="highlight-banner">
        <div>
            <div style="font-size:0.72rem;text-transform:uppercase;opacity:0.7;margin-bottom:6px;">⭐ Next Upcoming Event</div>
            <h2><?= htmlspecialchars($highlight['Title']) ?></h2>
            <p><?= htmlspecialchars(mb_substr($highlight['Description'] ?? '', 0, 110)) ?><?= strlen($highlight['Description'] ?? '') > 110 ? '…' : '' ?></p>
            <div class="highlight-meta">
                📅 <?= date('d M Y', strtotime($highlight['EventDate'])) ?>
                &nbsp;|&nbsp; 📍 <?= htmlspecialchars($highlight['Venue'] ?? 'TBA') ?>
                &nbsp;|&nbsp; 🏛️ <?= htmlspecialchars($highlight['ClubName']) ?>
            </div>
        </div>
        <a href="EventDetails.php?id=<?= urlencode($highlight['EventID']) ?>" class="btn btn-white">View Details →</a>
    </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" style="display:contents;">
            <input type="text" name="keyword" placeholder="🔍 Search events, clubs, venues…" value="<?= htmlspecialchars($keyword) ?>">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="Upcoming"  <?= $status==='Upcoming'  ? 'selected':'' ?>>Upcoming</option>
                <option value="Completed" <?= $status==='Completed' ? 'selected':'' ?>>Completed</option>
                <option value="Cancelled" <?= $status==='Cancelled' ? 'selected':'' ?>>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="StudentEvent.php" class="btn btn-ghost">Reset</a>
        </form>
    </div>

    <!-- Event Grid -->
    <div class="event-grid">
    <?php if (empty($events)): ?>
        <p style="color:#999;font-style:italic;grid-column:1/-1;">No events found. Try adjusting your search.</p>
    <?php else: ?>
        <?php foreach ($events as $ev): ?>
        <div class="event-card">
            <div class="event-card-banner">🎉</div>
            <div class="event-card-body">
                <span class="badge badge-<?= htmlspecialchars($ev['EventStatus']) ?>"><?= htmlspecialchars($ev['EventStatus']) ?></span>
                <h3><?= htmlspecialchars($ev['Title']) ?></h3>
                <div class="event-card-meta">📅 <?= date('d M Y', strtotime($ev['EventDate'])) ?></div>
                <div class="event-card-meta">📍 <?= htmlspecialchars($ev['Venue'] ?? 'TBA') ?></div>
                <div class="event-card-meta">🏛️ <?= htmlspecialchars($ev['ClubName']) ?></div>
                <?php if ($ev['MaxParticipants'] > 0): ?>
                <div class="event-card-meta">👥 Max <?= $ev['MaxParticipants'] ?> participants</div>
                <?php endif; ?>
            </div>
            <div class="event-card-footer">
                <a href="EventDetails.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

</div>
</body>
</html>