<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <?php include('Navigation.php'); ?>
    <div class="main-content">
    <div class="header-row">
        <h1>CLUB LIST</h1>
        <a href="ClubAddEdit.php" class="btn btn-primary">+ Add Club</a>
    </div>
    
    <div class="search-container">
        <input type="text" placeholder="Search Club..." class="search-input">
        <button class="btn btn-primary">Search</button>
    </div>

    <div class="slider-wrapper">
        <div class="club-slider" id="clubSlider">
            <?php 
            $clubs = [
                ["name" => "CyberSecurity Nexus", "desc" => "Network Security"],
                ["name" => "Mobile Dev Masters", "desc" => "Android Apps"],
                ["name" => "Unity Explorers", "desc" => "Game Design"],
                ["name" => "Web Wizards", "desc" => "PHP & MySQL"],
                ["name" => "AI Collective", "desc" => "Machine Learning"],
                ["name" => "Folklore Tech", "desc" => "Digital Stories"]
            ];

            foreach($clubs as $club): ?>
            <div class="club-card">
                <div class="club-avatar"></div>
                <h4><?php echo $club['name']; ?></h4>
                <p><small><?php echo $club['desc']; ?></small></p>
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                    <a href="CLubDetails.php" class="btn btn-outline" style="width: 100px !important;">View</a>
                    <a href="#" class="btn btn-primary" style="width: 100px !important;">Join</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="slider-controls">
        <button class="btn btn-back" onclick="slideLeft()">Previous</button>
        <button class="btn btn-primary" onclick="slideRight()">Next</button>
    </div>
</div>

<script>
    const slider = document.getElementById('clubSlider');
    
    function slideLeft() {
        // Slides back by the width of one card + gap
        slider.scrollBy({ left: -305, behavior: 'smooth' });
    }
    
    function slideRight() {
        // Slides forward by the width of one card + gap
        slider.scrollBy({ left: 305, behavior: 'smooth' });
    }
</script>
</body>
</html>