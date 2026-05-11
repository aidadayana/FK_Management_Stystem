<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <?php include('Navigation.php'); ?>
    <div class="main-content">
        <h1>CLUB DASHBOARD</h1>
        <div class="summary-grid">
            <div class="summary-card"><h3>12</h3><p>Total Clubs</p></div>
            <div class="summary-card"><h3>8</h3><p>Active Clubs</p></div>
            <div class="summary-card"><h3>120</h3><p>Total Students</p></div>
        </div>
        <div class="header-row"><h3>Statistics</h3></div>
        <div class="summary-grid">
            <div style="background:white; padding:20px; height:200px; border-radius:10px;">[Pie Chart: Active/Inactive]</div>
            <div style="background:white; padding:20px; height:200px; border-radius:10px;">[Bar Chart: Members per Club]</div>
            <div style="background:white; padding:20px; height:200px; border-radius:10px;">[Column Chart: Distribution]</div>
        </div>
    </div>
</body>
</html>