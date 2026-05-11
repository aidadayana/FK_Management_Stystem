<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <?php include('Navigation.php'); ?>
    <div class="main-content">
        <div class="header-row">
            <h1>Club List</h1>
            <a href="AddEditPage.php" class="btn btn-primary">+ Add Club</a>
        </div>
        
        <div class="search-container">
            <input type="text" placeholder="Search Club..." class="search-input">
            <select class="status-select">
                <option>All Status</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
            <button class="btn btn-primary">Search</button>
        </div>

        <div class="club-grid">
            <?php for($i=1; $i<=4; $i++): ?>
            <div class="club-card">
                <div class="club-avatar"></div>
                <h4>Computing Club</h4>
                <a href="CLubDetailsPage.php" class="btn btn-outline">View</a>
                <a href="#" class="btn btn-primary">Join</a>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>