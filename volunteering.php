<?php
session_start();
require 'db_connect.php'; 

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch current user data for the header
$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$full_name = $user['full_name'];

// 2. Fetch Volunteering Opportunities from the Database
$volunteering_places = [];
$vol_query = "SELECT id, title, description, location, external_link FROM volunteering ORDER BY id DESC";
$vol_result = $conn->query($vol_query);

if ($vol_result && $vol_result->num_rows > 0) {
    while ($row = $vol_result->fetch_assoc()) {
        $volunteering_places[] = $row;
    }
}

// 3. Array of colors to cycle through (since color isn't in your DB)
$theme_colors = ['#6c5ce7', '#fd79a8', '#00b894', '#0984e3', '#e17055', '#f39c12'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteering – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --purple-main: #6c5ce7; 
            --pink-main: #fd79a8; 
            --text-dark: #2d3436; 
            --text-light: #636e72; 
            --bg-main: #f8f9fa; 
            --sidebar-width: 250px; 
            --bg-purple-light: #f5f3ff; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* SIDEBAR & HEADER CSS */
        .sidebar { width: var(--sidebar-width); background-color: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; z-index: 200; transition: transform 0.3s ease; }
        .sidebar a { padding: 15px 30px; color: var(--text-light); text-decoration: none; font-weight: 600; transition: 0.3s; display: block; }
        .sidebar a:hover, .sidebar a.active { color: var(--purple-main); background-color: var(--bg-main); border-right: 4px solid var(--purple-main); }
        .sidebar a.logout { color: #ff4757; margin-top: auto; border-right: none; }
        .sidebar a.logout:hover { background-color: #ffeaa7; color: #ff4757; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); z-index: 150; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }
        .mobile-close { display: none; font-size: 1.5rem; color: var(--text-light); text-align: right; padding: 0 20px 10px; cursor: pointer; }

        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        header { background-color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02); position: sticky; top: 0; z-index: 90; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { display: none; font-size: 1.8rem; color: var(--purple-main); cursor: pointer; user-select: none; }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; border-radius: 8px; }
        .header-logo span { font-size: 1.4rem; font-weight: 700; color: var(--purple-main); }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-profile img { height: 45px; width: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--purple-main); }
        .user-profile span { font-weight: 600; }

        /* VOLUNTEERING SPECIFIC CSS */
        .page-container { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; }
        
        .page-header { text-align: center; margin-bottom: 40px; }
        .page-header h1 { font-size: 2.2rem; color: var(--purple-main); margin-bottom: 10px; }
        .page-header p { color: var(--text-light); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        .search-bar-container { display: flex; justify-content: center; margin-bottom: 40px; }
        .search-input { width: 100%; max-width: 500px; padding: 15px 25px; border-radius: 30px; border: 1px solid #dfe6e9; font-size: 1rem; font-family: 'Poppins', sans-serif; box-shadow: 0 5px 15px rgba(0,0,0,0.03); outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--purple-main); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.15); }

        .volunteering-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }
        
        .volunteer-card { background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column; height: 100%; border-top: 5px solid var(--purple-main); margin-top: 15px; }
        .volunteer-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        
        .volunteer-card h3 { font-size: 1.3rem; color: var(--text-dark); margin-bottom: 5px; }
        .location { font-size: 0.9rem; color: var(--text-light); margin-bottom: 15px; display: flex; align-items: center; gap: 5px; }
        .location::before { content: "📍"; font-size: 1rem; }
        
        .description { font-size: 0.95rem; color: var(--text-light); line-height: 1.6; margin-bottom: 25px; flex-grow: 1; }
        
        .btn-contact { background: var(--bg-purple-light); color: var(--purple-main); text-decoration: none; padding: 12px; border-radius: 10px; font-weight: 600; text-align: center; transition: 0.3s; display: block; border: 1px solid transparent; }
        .btn-contact:hover { background: var(--purple-main); color: #fff; }

        .empty-state { text-align: center; color: var(--text-light); font-size: 1.1rem; margin-top: 40px; grid-column: 1 / -1; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-close { display: block; }
            .main-content { margin-left: 0; }
            .menu-btn { display: block; }
            header { padding: 15px 20px; }
            .header-logo span, .user-profile span { display: none; }
            .page-container { padding: 20px; }
            .page-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

    <aside class="sidebar" id="sidebarMenu">
        <div class="mobile-close" onclick="toggleMobileMenu()">✖</div>
        <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
        <a href="articles.php">Articles</a>

        <?php if (isset($user['role']) && $user['role'] === 'creator'): ?>
            <a href="my_posts.php" class="<?= ($current_page == 'my_posts.php') ? 'active' : ''; ?>">My Posts</a>
        <?php endif; ?>

        <a href="profile.php" class="<?= ($current_page == 'profile.php') ? 'active' : ''; ?>">Profile</a>
        <a href="liked_articles.php">Liked Articles</a>
        <a href="volunteering.php" class="<?= ($current_page == 'volunteering.php') ? 'active' : ''; ?>">Volunteering</a>
        <a href="game_rules.php" class="<?= ($current_page == 'game_rules.php') ? 'active' : ''; ?>">Game Rules</a>
        <a href="about.php" class="<?= ($current_page == 'about.php') ? 'active' : ''; ?>">About Us</a>
        <a href="login.php" class="logout">Logout</a>
    </aside>
    
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileMenu()"></div>

    <div class="main-content">
        <header>
            <div class="header-left">
                <div class="menu-btn" onclick="toggleMobileMenu()">☰</div>
                <div class="header-logo">
                    <img src="images/logo.png" alt="Logo">
                    <span>SafeSpace</span>
                </div>
            </div>
            <div class="user-profile">
                <span><?= htmlspecialchars($full_name) ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($full_name) ?>&background=6c5ce7&color=fff" alt="Profile Picture">
            </div>
        </header>

        <div class="page-container">
            <div class="page-header">
                <h1>Make a Difference</h1>
                <p>Explore volunteering opportunities and community initiatives where your time and effort can help change lives.</p>
            </div>

            <div class="search-bar-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, location, or keyword..." onkeyup="filterCards()">
            </div>

            <div class="volunteering-grid" id="volunteerGrid">
                <?php if (empty($volunteering_places)): ?>
                    <div class="empty-state">
                        <p>No volunteering opportunities available at the moment. Check back soon!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($volunteering_places as $index => $place): ?>
                        <?php 
                            // Cycle through the colors array based on the current loop index
                            $card_color = $theme_colors[$index % count($theme_colors)]; 
                        ?>
                        <div class="volunteer-card" style="border-top-color: <?= $card_color ?>;">
                            <h3 class="card-title"><?= htmlspecialchars($place['title']) ?></h3>
                            <div class="location card-location"><?= htmlspecialchars($place['location']) ?></div>
                            <p class="description card-desc">
                                <?= nl2br(htmlspecialchars($place['description'])) ?>
                            </p>
                            
                            <?php if (!empty($place['external_link'])): ?>
                                <a href="<?= htmlspecialchars($place['external_link']) ?>" target="_blank" rel="noopener noreferrer" class="btn-contact">
                                    🔗 View Opportunity
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile Sidebar Toggle
        function toggleMobileMenu() {
            document.getElementById('sidebarMenu').classList.toggle('mobile-open'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }

        // Live Search Filter functionality
        function filterCards() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.volunteer-card');

            cards.forEach(card => {
                // Safely grab text content, fallback to empty string if missing
                const title = card.querySelector('.card-title')?.textContent.toLowerCase() || "";
                const location = card.querySelector('.card-location')?.textContent.toLowerCase() || "";
                const desc = card.querySelector('.card-desc')?.textContent.toLowerCase() || "";

                // Check if the input exists in any of the fields
                if (title.includes(input) || location.includes(input) || desc.includes(input)) {
                    card.style.display = ""; // Show card (restores its default flex layout)
                } else {
                    card.style.display = "none"; // Hide card
                }
            });
        }
    </script>
</body>
</html>