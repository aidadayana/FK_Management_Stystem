<?php
session_start();
require_once 'db.php';

// Check if user is logged in and is a Committee member (R03) or Admin (R01)
if (!isset($_SESSION['UserID']) || ($_SESSION['RoleID'] !== 'R03' && $_SESSION['RoleID'] !== 'R01')) {
    header("Location: Login.php");
    exit();
}

$user_id = $_SESSION['UserID'];

// Fetch clubs managed by this committee/admin to populate the dropdown
// If admin, show all clubs. If committee, show only their club.
if ($_SESSION['RoleID'] === 'R01') {
    $club_query = "SELECT ClubID, ClubName FROM club WHERE ClubStatus = 'Active'";
    $stmt = $conn->prepare($club_query);
} else {
    $club_query = "SELECT c.ClubID, c.ClubName FROM club c 
                   JOIN membership m ON c.ClubID = m.ClubID 
                   WHERE m.UserID = ? AND m.MemberStatus = 'Active'";
    $stmt = $conn->prepare($club_query);
    $stmt->bind_param('s', $user_id);
}
$stmt->execute();
$clubs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['Title']);
    $description = trim($_POST['Description']);
    $event_date = $_POST['EventDate'];
    $event_time = $_POST['EventTime'];
    $venue = trim($_POST['Venue']);
    $max_participants = intval($_POST['MaxParticipants']);
    $club_id = $_POST['ClubID'];
    
    // LOGICAL FIX: Automatically set status to 'Upcoming' instead of getting it from the form
    $event_status = 'Upcoming';

    if (empty($title) || empty($description) || empty($event_date) || empty($event_time) || empty($venue) || empty($club_id)) {
        $error = 'All fields are required.';
    } elseif ($max_participants < 0) {
        $error = 'Maximum participants cannot be negative.';
    } else {
        // Generate Unique Event ID (e.g., EV20260615123045)
        $event_id = 'EV' . date('YmdHis');

        $insert_query = "INSERT INTO event (EventID, Title, Description, EventDate, EventTime, Venue, MaxParticipants, ClubID, EventStatus) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins_stmt = $conn->prepare($insert_query);
        $ins_stmt->bind_param('ssssssiss', $event_id, $title, $description, $event_date, $event_time, $venue, $max_participants, $club_id, $event_status);

        if ($ins_stmt->execute()) {
            $success = 'Event created successfully!';
            // Clear form fields
            $title = $description = $event_date = $event_time = $venue = '';
            $max_participants = 0;
        } else {
            $error = 'Failed to create event. Please try again. Error: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event — FK Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(215,183,163,0.4);
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            font-size: 0.9rem;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: var(--primary-maroon);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139,30,63,0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        .row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) {
            .row-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            font-weight: 500;
        }
        .alert-danger {
            background: #fdecea;
            color: #b71c1c;
            border-left: 4px solid #d32f2f;
        }
        .alert-success {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 4px solid #2e7d32;
        }
        .btn {
            padding: 11px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary {
            background: var(--primary-maroon);
            color: white;
        }
        .btn-primary:hover {
            filter: brightness(1.15);
        }
        .btn-ghost {
            background: white;
            border: 1px solid #ddd;
            color: #555;
            text-decoration: none;
        }
        .btn-ghost:hover {
            border-color: #999;
            color: #333;
        }
    </style>
</head>
<body>

<?php include 'Navigation.php'; ?>

<div class="main-content">
    <div style="margin-bottom: 25px;">
        <div class="club-subtitle">Event Management</div>
        <h2 style="margin: 5px 0 0 0; font-size: 1.6rem; color: var(--primary-maroon);">Create New Event</h2>
        <div style="font-size:0.85rem;color:#888;">Fill in the details to publish a new official event.</div>
    </div>

    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="ClubID">Organizing Club</label>
                <select name="ClubID" id="ClubID" class="form-control" required>
                    <option value="">-- Select Club --</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= htmlspecialchars($c['ClubID']) ?>" <?= (isset($club_id) && $club_id === $c['ClubID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['ClubName']) ?> (<?= htmlspecialchars($c['ClubID']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Title">Event Title</label>
                <input type="text" name="Title" id="Title" class="form-control" placeholder="e.g. HCI Workshop 2026" value="<?= isset($title) ? htmlspecialchars($title) : '' ?>" required>
            </div>

            <div class="form-group">
                <label for="Description">Description</label>
                <textarea name="Description" id="Description" class="form-control" placeholder="Provide clear details regarding schedule, rules, or objectives..." required><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="EventDate">Date</label>
                    <input type="date" name="EventDate" id="EventDate" class="form-control" value="<?= isset($event_date) ? htmlspecialchars($event_date) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="EventTime">Time</label>
                    <input type="time" name="EventTime" id="EventTime" class="form-control" value="<?= isset($event_time) ? htmlspecialchars($event_time) : '' ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="Venue">Venue / Location</label>
                <input type="text" name="Venue" id="Venue" class="form-control" placeholder="e.g. Auditorium 1 / Online (MS Teams)" value="<?= isset($venue) ? htmlspecialchars($venue) : '' ?>" required>
            </div>

            <div class="form-group">
                <label for="MaxParticipants">Maximum Participants (Capacity)</label>
                <input type="number" name="MaxParticipants" id="MaxParticipants" class="form-control" min="0" placeholder="e.g. 50 (Set 0 for unlimited)" value="<?= isset($max_participants) ? htmlspecialchars($max_participants) : 0 ?>" required>
                <small style="color: #888; font-size: 0.78rem; display: block; margin-top: 4px;">Once this capacity is reached, subsequent registrants will automatically go to the waitlist.</small>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="ManageEvent.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">✨ Publish Event</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>