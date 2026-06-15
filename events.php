<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['UserID']) || !in_array($_SESSION['RoleID'], ['R01', 'R03'])) {
    header("Location: Login.php");
    exit();
}

$role    = $_SESSION['RoleID'];
$user_id = $_SESSION['UserID'];

// For committee: get their club
$committee_club = null;
if ($role === 'R03') {
    $mc = $conn->prepare("SELECT m.ClubID, c.ClubName FROM membership m JOIN club c ON m.ClubID=c.ClubID WHERE m.UserID=? AND m.MemberStatus='Active' LIMIT 1");
    $mc->bind_param('s', $user_id);
    $mc->execute();
    $committee_club = $mc->get_result()->fetch_assoc();
}

// ── STATS ──────────────────────────────────────────────────────────────────
$total_events    = $conn->query("SELECT COUNT(*) FROM event" . ($committee_club ? " WHERE ClubID='" . $conn->real_escape_string($committee_club['ClubID']) . "'" : ""))->fetch_row()[0];
$upcoming_events = $conn->query("SELECT COUNT(*) FROM event WHERE EventStatus='Upcoming'" . ($committee_club ? " AND ClubID='" . $conn->real_escape_string($committee_club['ClubID']) . "'" : ""))->fetch_row()[0];
$completed_events= $conn->query("SELECT COUNT(*) FROM event WHERE EventStatus='Completed'" . ($committee_club ? " AND ClubID='" . $conn->real_escape_string($committee_club['ClubID']) . "'" : ""))->fetch_row()[0];
$total_reg       = $conn->query("SELECT COUNT(*) FROM event_registration WHERE RegStatus='Confirmed'")->fetch_row()[0];

// ── CHART 1: Events per Club (bar) ─────────────────────────────────────────
$epc_result = $conn->query("SELECT c.ClubName, COUNT(e.EventID) as cnt FROM event e JOIN club c ON e.ClubID=c.ClubID GROUP BY c.ClubName ORDER BY cnt DESC");
$epc_labels = []; $epc_data = [];
while ($row = $epc_result->fetch_assoc()) { $epc_labels[] = $row['ClubName']; $epc_data[] = $row['cnt']; }

// ── CHART 2: Participants per Event (bar, top 6) ───────────────────────────
$ppe_result = $conn->query("SELECT e.Title, COUNT(er.RegistrationID) as cnt FROM event e LEFT JOIN event_registration er ON e.EventID=er.EventID AND er.RegStatus='Confirmed' GROUP BY e.EventID, e.Title ORDER BY cnt DESC LIMIT 6");
$ppe_labels = []; $ppe_data = [];
while ($row = $ppe_result->fetch_assoc()) { $ppe_labels[] = mb_substr($row['Title'], 0, 20) . (strlen($row['Title']) > 20 ? '…' : ''); $ppe_data[] = $row['cnt']; }

// ── CHART 3: Status breakdown (pie) ───────────────────────────────────────
$pie_data = [
    'Upcoming'  => (int)$upcoming_events,
    'Completed' => (int)$completed_events,
    'Cancelled' => (int)$conn->query("SELECT COUNT(*) FROM event WHERE EventStatus='Cancelled'" . ($committee_club ? " AND ClubID='" . $conn->real_escape_string($committee_club['ClubID']) . "'" : ""))->fetch_row()[0],
];

// ── CHART 4: Monthly event trend ──────────────────────────────────────────
$monthly = $conn->query("
    SELECT DATE_FORMAT(EventDate,'%b %Y') as month, MONTH(EventDate) as mnum, YEAR(EventDate) as yr, COUNT(*) as cnt
    FROM event
    GROUP BY yr, mnum
    ORDER BY yr ASC, mnum ASC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);
$monthly_labels = array_column($monthly, 'month');
$monthly_data   = array_column($monthly, 'cnt');

// ── RECENT EVENTS ─────────────────────────────────────────────────────────
$recent = $conn->query("
    SELECT e.*, c.ClubName,
    (SELECT COUNT(*) FROM event_registration er WHERE er.EventID=e.EventID AND er.RegStatus='Confirmed') as RegCount
    FROM event e JOIN club c ON e.ClubID=c.ClubID
    " . ($committee_club ? "WHERE e.ClubID='" . $conn->real_escape_string($committee_club['ClubID']) . "'" : "") . "
    ORDER BY e.EventDate DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Dashboard — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 22px 20px; border-radius: 14px; border-top: 4px solid var(--primary-maroon); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .stat-card .num { font-size: 2.2rem; font-weight: 700; color: var(--primary-maroon); line-height: 1; }
        .stat-card .label { font-size: 0.78rem; color: #888; margin-top: 6px; text-transform: uppercase; font-weight: 600; }
        .stat-card .icon { font-size: 1.6rem; margin-bottom: 8px; }

        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .chart-card { background: white; padding: 22px; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.3); }
        .chart-card h3 { font-size: 0.88rem; color: var(--primary-maroon); font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 18px 0; }
        .chart-wrap { position: relative; height: 220px; }

        .table-wrap { background: white; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid rgba(215,183,163,0.3); padding: 22px; overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th { text-align: left; padding: 10px 14px; background: #fafafa; color: var(--primary-maroon); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid var(--border-color); }
        table.data-table td { padding: 12px 14px; border-bottom: 1px solid rgba(215,183,163,0.2); font-size: 0.87rem; }
        table.data-table tr:last-child td { border-bottom: none; }
        table.data-table tr:hover td { background: rgba(139,30,63,0.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
        .badge-Upcoming  { background: #e3f2fd; color: #1565c0; }
        .badge-Completed { background: #f5f5f5; color: #616161; }
        .badge-Cancelled { background: #fdecea; color: #b71c1c; }

        .btn { padding: 9px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-family: 'Poppins', sans-serif; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: var(--primary-maroon); color: white; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-outline { border: 1.5px solid var(--primary-maroon); color: var(--primary-maroon); background: transparent; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }

        @media(max-width:900px) {
            .stats-grid  { grid-template-columns: repeat(2,1fr); }
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>




<div class="main-content">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px;">
        <div>
            <div class="club-subtitle">
                <?= $committee_club ? 'Event Dashboard — ' . htmlspecialchars($committee_club['ClubName']) : 'Event Management Dashboard' ?>
            </div>
            <div style="font-size:0.85rem;color:#888;">Overview of all events and registrations</div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="ManageEvents.php" class="btn btn-outline">📋 Manage Events</a>
            <a href="CreateEvent.php"  class="btn btn-primary">+ Create Event</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">📅</div>
            <div class="num"><?= $total_events ?></div>
            <div class="label">Total Events</div>
        </div>
        <div class="stat-card" style="border-top-color:#1565c0;">
            <div class="icon">⏳</div>
            <div class="num" style="color:#1565c0;"><?= $upcoming_events ?></div>
            <div class="label">Upcoming</div>
        </div>
        <div class="stat-card" style="border-top-color:#616161;">
            <div class="icon">✅</div>
            <div class="num" style="color:#616161;"><?= $completed_events ?></div>
            <div class="label">Completed</div>
        </div>
        <div class="stat-card" style="border-top-color:#1b5e20;">
            <div class="icon">👥</div>
            <div class="num" style="color:#1b5e20;"><?= $total_reg ?></div>
            <div class="label">Total Registrations</div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Events per Club</h3>
            <div class="chart-wrap"><canvas id="epcChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>Participants per Event (Top 6)</h3>
            <div class="chart-wrap"><canvas id="ppeChart"></canvas></div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Event Status Breakdown</h3>
            <div class="chart-wrap" style="height:200px;display:flex;justify-content:center;">
                <canvas id="pieChart" style="max-width:260px;"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>Monthly Event Trend</h3>
            <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
        </div>
    </div>

    <!-- Recent Events Table -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;margin-top:4px;">
        <h3 style="color:var(--primary-maroon);font-size:0.95rem;text-transform:uppercase;letter-spacing:0.04em;margin:0;">Recent Events</h3>
        <a href="ManageEvents.php" style="color:var(--primary-maroon);font-size:0.85rem;font-weight:600;text-decoration:none;">View All →</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Event</th><th>Club</th><th>Date</th><th>Registered</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="6" style="text-align:center;color:#999;padding:30px;font-style:italic;">No events yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recent as $ev): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ev['Title']) ?></strong></td>
                    <td><?= htmlspecialchars($ev['ClubName']) ?></td>
                    <td><?= date('d M Y', strtotime($ev['EventDate'])) ?></td>
                    <td><?= $ev['RegCount'] ?><?= $ev['MaxParticipants'] > 0 ? ' / ' . $ev['MaxParticipants'] : '' ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($ev['EventStatus']) ?>"><?= htmlspecialchars($ev['EventStatus']) ?></span></td>
                    <td>
                        <a href="EventDetails.php?id=<?= urlencode($ev['EventID']) ?>" class="btn btn-outline btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const maroon = '#8B1E3F';
const maroonLight = 'rgba(139,30,63,0.15)';
const blue   = '#1565c0';
const grey   = '#9e9e9e';
const red    = '#e63946';

// Chart 1: Events per Club
new Chart(document.getElementById('epcChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($epc_labels) ?>,
        datasets: [{ label: 'Events', data: <?= json_encode($epc_data) ?>, backgroundColor: maroon, borderRadius: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Chart 2: Participants per Event
new Chart(document.getElementById('ppeChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($ppe_labels) ?>,
        datasets: [{ label: 'Participants', data: <?= json_encode($ppe_data) ?>, backgroundColor: blue, borderRadius: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Chart 3: Pie status
new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Upcoming', 'Completed', 'Cancelled'],
        datasets: [{ data: [<?= $pie_data['Upcoming'] ?>, <?= $pie_data['Completed'] ?>, <?= $pie_data['Cancelled'] ?>], backgroundColor: [blue, grey, red], borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 } } } } }
});

// Chart 4: Monthly trend
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthly_labels) ?>,
        datasets: [{
            label: 'Events', data: <?= json_encode($monthly_data) ?>,
            borderColor: maroon, backgroundColor: maroonLight,
            tension: 0.4, fill: true, pointBackgroundColor: maroon, pointRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
</body>
</html>