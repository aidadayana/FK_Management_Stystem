<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <?php include('Navigation.php'); ?>
    <div class="main-content">
        <h1>Register / Edit Club</h1>
        <form action="ClubListPage.php" style="background:white; padding:30px; border-radius:10px; max-width:600px;">
            <label>Club Name</label><input type="text" required>
            <label>Club Description</label><textarea rows="4"></textarea>
            <label>Advisor Name</label><input type="text">
            <label>Club Icon</label><input type="file">
            <label>Status</label>
            <select><option>Active</option><option>Inactive</option></select>
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="reset" class="btn">Reset</button>
                <a href="club_list.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>