<?php
require_once '../db.php';

$page_title  = 'Browse Events';
$active_page = 'events';

$keyword = trim($_GET['keyword'] ?? '');
$status  = $_GET['status'] ?? '';

$where = []; $params = []; $types = '';

if ($keyword !== '') {
    $where[] = "(e.title LIKE ? OR e.venue LIKE ? OR c.club_name LIKE ?)";
    $like = "%$keyword%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if (in_array($status, ['upcoming','completed','cancelled'])) {
    $where[] = "e.status = ?";
    $params[] = $status; $types .= 's';
}

$sql  = "SELECT e.*, c.club_name FROM events e JOIN clubs c ON e.club_id=c.club_id"
       . ($where ? ' WHERE '.implode(' AND ',$where) : '')
       . " ORDER BY e.event_date ASC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/student_sidebar.php';
?>

<div class="page-header">
    <div>
        <div class="page-title">Browse Events</div>
        <div class="page-subtitle">Find and register for upcoming events</div>
    </div>
</div>

<div class="search-bar">
    <form method="GET" style="display:contents;">
        <input type="text" name="keyword" placeholder="🔍 Search events, clubs, venues…" value="<?= htmlspecialchars($keyword) ?>">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="upcoming"  <?= $status==='upcoming'  ? 'selected':'' ?>>Upcoming</option>
            <option value="completed" <?= $status==='completed' ? 'selected':'' ?>>Completed</option>
            <option value="cancelled" <?= $status==='cancelled' ? 'selected':'' ?>>Cancelled</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="events.php" class="btn btn-ghost">Reset</a>
    </form>
</div>

<div class="event-grid">
<?php if (empty($events)): ?>
    <p style="color:#999;font-style:italic;">No events found.</p>
<?php else: ?>
    <?php foreach ($events as $ev): ?>
    <div class="event-card">
        <div class="event-card-banner"></div>
        <div class="event-card-body">
            <span class="badge badge-<?= $ev['status'] ?>"><?= $ev['status'] ?></span>
            <h3><?= htmlspecialchars($ev['title']) ?></h3>
            <div class="event-card-meta"> <?= date('d M Y', strtotime($ev['event_date'])) ?></div>
            <div class="event-card-meta"> <?= htmlspecialchars($ev['venue'] ?? 'TBA') ?></div>
            <div class="event-card-meta"> <?= htmlspecialchars($ev['club_name']) ?></div>
        </div>
        <div class="event-card-footer">
            <a href="event_details.php?id=<?= $ev['event_id'] ?>" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">View Details</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>