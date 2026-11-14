<?php
session_start();
require 'db_connect.php'; // Added DB connection

// Fetch user data if they are logged in so the sidebar knows their role
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Rules – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --purple-main: #6c5ce7; --pink-main: #fd79a8; --text-dark: #2d3436; --text-light: #636e72; --bg-main: #f8f9fa; --card-gray: #f1f2f6; --sidebar-width: 250px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar Styles */
        .sidebar { width: var(--sidebar-width); background-color: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; z-index: 200; transition: transform 0.3s ease; }
        .sidebar a { padding: 15px 30px; color: var(--text-light); text-decoration: none; font-weight: 600; transition: 0.3s; display: block; }
        .sidebar a:hover, .sidebar a.active { color: var(--purple-main); background-color: var(--bg-main); border-right: 4px solid var(--purple-main); }
        .sidebar a.logout { color: #ff4757; margin-top: auto; border-right: none; }
        .sidebar a.logout:hover { background-color: #ffeaa7; color: #ff4757; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); z-index: 150; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }
        .mobile-close { display: none; font-size: 1.5rem; color: var(--text-light); text-align: right; padding: 0 20px 10px; cursor: pointer; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        header { background-color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02); position: sticky; top: 0; z-index: 90; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { display: none; font-size: 1.8rem; color: var(--purple-main); cursor: pointer; user-select: none; }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; border-radius: 8px; }
        .header-logo span { font-size: 1.4rem; font-weight: 700; color: var(--purple-main); }
        
        /* Page Specific Styles */
        .rules-container { padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .hero-banner { background: linear-gradient(135deg, var(--purple-main), var(--pink-main)); border-radius: 20px; padding: 40px; color: #fff; text-align: center; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2); }
        .hero-banner h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .hero-banner p { font-size: 1.1rem; opacity: 0.9; }

        .rule-card { background: #fff; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-left: 5px solid var(--purple-main); }
        .rule-card h2 { color: var(--purple-main); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .rule-card p { line-height: 1.6; color: var(--text-light); margin-bottom: 10px; }
        
        .rule-card.streak { border-left-color: #ff9f43; }
        .rule-card.streak h2 { color: #ff9f43; }
        .rule-card.freeze { border-left-color: #74b9ff; }
        .rule-card.freeze h2 { color: #74b9ff; }
        .rule-card.xp { border-left-color: #2ed573; }
        .rule-card.xp h2 { color: #2ed573; }

        /* Titles Grid */
        .titles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .level-card { background: var(--card-gray); border-radius: 12px; padding: 20px; text-align: center; transition: transform 0.3s; border: 1px solid #dfe4ea; display: flex; flex-direction: column; justify-content: center;}
        .level-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .level-emoji { font-size: 3rem; margin-bottom: 10px; }
        .level-name { font-weight: 700; font-size: 1.1rem; color: var(--purple-main); margin-bottom: 5px; }
        .level-req { font-size: 0.85rem; color: var(--text-light); font-weight: 600; background: #fff; padding: 5px 10px; border-radius: 20px; display: inline-block; margin-top: auto;}

        /* Special styling for Level 20 */
        .level-20 { grid-column: 1 / -1; background: linear-gradient(135deg, #1e272e, #000); border: 2px solid #feca57; }
        .level-20 .level-name { color: #feca57; font-size: 1.8rem; }
        .level-20 .level-req { background: #feca57; color: #000; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-close { display: block; }
            .main-content { margin-left: 0; }
            .menu-btn { display: block; }
            header { padding: 15px 20px; }
            .header-logo span { display: none; }
            .rules-container { padding: 20px; }
            .hero-banner { padding: 30px 20px; }
            .hero-banner h1 { font-size: 2rem; }
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
        
        <a href="profile.php">Profile</a>
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
        </header>

        <div class="rules-container">
            
            <div class="hero-banner">
                <h1>How to Play SafeSpace 🎮</h1>
                <p>Build healthy habits, level up your life, and become a Guardian.</p>
            </div>

            <div class="rule-card streak">
                <h2>🔥 The Streak</h2>
                <p>Consistency is key! Your streak represents how many days in a row you have completed at least <strong>one mission</strong>.</p>
                <p>Complete a mission every day to watch your streak grow. If you go a full day without completing anything (and you have no freezes left), your streak will break and reset back to 0!</p>
            </div>

            <div class="rule-card freeze">
                <h2>❄️ Streak Freezes</h2>
                <p>We know life happens. Streak Freezes are your safety net. If you forget to log in or simply need a rest day, a freeze will automatically be used to protect your streak from resetting.</p>
                <p><strong>How to earn them:</strong> You are granted 2 Freezes when you start. After that, you earn <strong>1 new Freeze every 7 days</strong> you keep your streak alive!</p>
            </div>

            <div class="rule-card xp">
                <h2>⭐ XP Points</h2>
                <p>Experience Points (XP) are a measure of your total effort on SafeSpace. Every time you complete a mission, you earn <strong>+10 XP</strong>.</p>
                <p>Unlike your streak, your XP never resets! It will only ever go up. Collecting XP allows you to level up and earn prestigious new titles.</p>
            </div>

            <h2 style="color: var(--text-dark); margin-bottom: 10px; margin-top: 40px;">🏆 The 20 Ranks of SafeSpace</h2>
            <p style="color: var(--text-light); margin-bottom: 30px;">Levels get progressively harder. Do you have what it takes to reach Level 20?</p>

            <div class="titles-grid">
                <div class="level-card"><div class="level-emoji">🌱</div><div class="level-name">Lvl 1: Seedling</div><div class="level-req">0 - 100 XP</div></div>
                <div class="level-card"><div class="level-emoji">🌿</div><div class="level-name">Lvl 2: Sprout</div><div class="level-req">101 - 200 XP</div></div>
                <div class="level-card"><div class="level-emoji">🦋</div><div class="level-name">Lvl 3: Wanderer</div><div class="level-req">201 - 300 XP</div></div>
                <div class="level-card"><div class="level-emoji">🧭</div><div class="level-name">Lvl 4: Explorer</div><div class="level-req">301 - 400 XP</div></div>
                <div class="level-card"><div class="level-emoji">🏕️</div><div class="level-name">Lvl 5: Adventurer</div><div class="level-req">401 - 500 XP</div></div>

                <div class="level-card"><div class="level-emoji">⭐</div><div class="level-name">Lvl 6: Rising Star</div><div class="level-req">501 - 1,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">☄️</div><div class="level-name">Lvl 7: Comet</div><div class="level-req">1,001 - 1,500 XP</div></div>
                <div class="level-card"><div class="level-emoji">🚀</div><div class="level-name">Lvl 8: Achiever</div><div class="level-req">1,501 - 2,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🔥</div><div class="level-name">Lvl 9: Trailblazer</div><div class="level-req">2,001 - 2,500 XP</div></div>
                <div class="level-card"><div class="level-emoji">🏆</div><div class="level-name">Lvl 10: Champion</div><div class="level-req">2,501 - 3,000 XP</div></div>

                <div class="level-card"><div class="level-emoji">🦸</div><div class="level-name">Lvl 11: Hero</div><div class="level-req">3,001 - 4,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🥷</div><div class="level-name">Lvl 12: Ninja</div><div class="level-req">4,001 - 5,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🧙</div><div class="level-name">Lvl 13: Mystic</div><div class="level-req">5,001 - 6,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🐉</div><div class="level-name">Lvl 14: Legend</div><div class="level-req">6,001 - 7,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">👑</div><div class="level-name">Lvl 15: Master</div><div class="level-req">7,001 - 8,000 XP</div></div>

                <div class="level-card"><div class="level-emoji">💎</div><div class="level-name">Lvl 16: Grandmaster</div><div class="level-req">8,001 - 13,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">⚡</div><div class="level-name">Lvl 17: Titan</div><div class="level-req">13,001 - 18,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🌌</div><div class="level-name">Lvl 18: Celestial</div><div class="level-req">18,001 - 23,000 XP</div></div>
                <div class="level-card"><div class="level-emoji">🧿</div><div class="level-name">Lvl 19: Oracle</div><div class="level-req">23,001 - 28,000 XP</div></div>
                
                <div class="level-card level-20">
                    <div class="level-emoji">♾️</div>
                    <div class="level-name">Level 20: SafeSpace Guardian</div>
                    <div class="level-req">28,001+ XP</div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('sidebarMenu').classList.toggle('mobile-open'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }
    </script>
</body>
</html>