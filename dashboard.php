<?php
session_start();
require 'db_connect.php'; 

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// ==========================================
// NEW: LEVEL CALCULATION FUNCTION
// ==========================================
function getUserLevelData($xp) {
    if ($xp <= 100) return ['emoji' => '🌱', 'title' => 'Seedling'];
    elseif ($xp <= 200) return ['emoji' => '🌿', 'title' => 'Sprout'];
    elseif ($xp <= 300) return ['emoji' => '🦋', 'title' => 'Wanderer'];
    elseif ($xp <= 400) return ['emoji' => '🧭', 'title' => 'Explorer'];
    elseif ($xp <= 500) return ['emoji' => '🏕️', 'title' => 'Adventurer'];
    
    elseif ($xp <= 1000) return ['emoji' => '⭐', 'title' => 'Rising Star'];
    elseif ($xp <= 1500) return ['emoji' => '☄️', 'title' => 'Comet'];
    elseif ($xp <= 2000) return ['emoji' => '🚀', 'title' => 'Achiever'];
    elseif ($xp <= 2500) return ['emoji' => '🔥', 'title' => 'Trailblazer'];
    elseif ($xp <= 3000) return ['emoji' => '🏆', 'title' => 'Champion'];
    
    elseif ($xp <= 4000) return ['emoji' => '🦸', 'title' => 'Hero'];
    elseif ($xp <= 5000) return ['emoji' => '🥷', 'title' => 'Ninja'];
    elseif ($xp <= 6000) return ['emoji' => '🧙', 'title' => 'Mystic'];
    elseif ($xp <= 7000) return ['emoji' => '🐉', 'title' => 'Legend'];
    elseif ($xp <= 8000) return ['emoji' => '👑', 'title' => 'Master'];
    
    elseif ($xp <= 13000) return ['emoji' => '💎', 'title' => 'Grandmaster'];
    elseif ($xp <= 18000) return ['emoji' => '⚡', 'title' => 'Titan'];
    elseif ($xp <= 23000) return ['emoji' => '🌌', 'title' => 'Celestial'];
    elseif ($xp <= 28000) return ['emoji' => '🧿', 'title' => 'Oracle'];
    
    else return ['emoji' => '♾️', 'title' => 'SafeSpace Guardian'];
}

// ==========================================
// 1. AJAX HANDLER: COMPLETING A QUEST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_quest') {
    $assignment_id = intval($_POST['assignment_id']);
    
    // Get user stats
    $stmt = $conn->prepare("SELECT points, streak, freezes FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    // Check if they already completed a challenge today BEFORE marking this one
    $stmt = $conn->prepare("SELECT COUNT(*) as completed_count FROM daily_assignments WHERE user_id = ? AND assigned_date = ? AND status = 'completed'");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $today_completions = $stmt->get_result()->fetch_assoc()['completed_count'];

    // Mark the assignment as completed
    $stmt = $conn->prepare("UPDATE daily_assignments SET status = 'completed' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $assignment_id, $user_id);
    $stmt->execute();

    // Update Points (+10 per challenge)
    $new_points = $user['points'] + 10;
    $new_streak = $user['streak'];
    $new_freezes = $user['freezes'];
    $streak_saved_today = false;

    // If this is the FIRST completion of the day
    if ($today_completions == 0) {
        $new_streak += 1;
        $streak_saved_today = true;
        // Reward a freeze every 7 days
        if ($new_streak % 7 == 0) {
            $new_freezes += 1;
        }
    }

    // Save new stats to database
    $stmt = $conn->prepare("UPDATE users SET points = ?, streak = ?, freezes = ? WHERE id = ?");
    $stmt->bind_param("iiii", $new_points, $new_streak, $new_freezes, $user_id);
    $stmt->execute();

    // Calculate their NEW level data based on their updated points
    $newLevelData = getUserLevelData($new_points);

    // Return JSON response to the JavaScript
    echo json_encode([
        "success" => true,
        "new_xp" => $new_points,
        "new_streak" => $new_streak,
        "new_freezes" => $new_freezes,
        "streak_saved_today" => $streak_saved_today,
        "new_title" => $newLevelData['title'],
        "new_emoji" => $newLevelData['emoji']
    ]);
    exit;
}

// ==========================================
// 2. THE PENALTY CHECK (STREAK LOGIC)
// ==========================================
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$last_check_date = $user['last_check_date'];

if ($last_check_date && $last_check_date !== $today) {
    // Calculate difference in days
    $diff_days = floor((strtotime($today) - strtotime($last_check_date)) / (60 * 60 * 24));
    
    $current_streak = $user['streak'];
    $current_freezes = $user['freezes'];

    if ($diff_days == 1) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM daily_assignments WHERE user_id = ? AND assigned_date = ? AND status = 'completed'");
        $stmt->bind_param("is", $user_id, $yesterday);
        $stmt->execute();
        $did_complete_yesterday = $stmt->get_result()->fetch_assoc()['count'] > 0;

        if (!$did_complete_yesterday) {
            if ($current_freezes > 0) {
                $current_freezes -= 1; 
            } else {
                $current_streak = 0; 
            }
        }
    } elseif ($diff_days > 1) {
        if ($current_freezes >= $diff_days) {
            $current_freezes -= $diff_days; 
        } else {
            $current_streak = 0; 
            $current_freezes = 0;
        }
    }

    $stmt = $conn->prepare("UPDATE users SET streak = ?, freezes = ?, last_check_date = ? WHERE id = ?");
    $stmt->bind_param("iisi", $current_streak, $current_freezes, $today, $user_id);
    $stmt->execute();
    
    $user['streak'] = $current_streak;
    $user['freezes'] = $current_freezes;
} elseif (!$last_check_date) {
    $stmt = $conn->prepare("UPDATE users SET last_check_date = ? WHERE id = ?");
    $stmt->bind_param("si", $today, $user_id);
    $stmt->execute();
}

// ==========================================
// 3. DAILY ASSIGNMENTS GENERATOR
// ==========================================
$stmt = $conn->prepare("SELECT da.id as assignment_id, da.status, c.category, c.description 
                        FROM daily_assignments da 
                        JOIN challenges c ON da.challenge_id = c.id 
                        WHERE da.user_id = ? AND da.assigned_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}

if (count($assignments) == 0) {
    $cat_result = $conn->query("SELECT DISTINCT category FROM challenges");
    while ($cat_row = $cat_result->fetch_assoc()) {
        $category = $cat_row['category'];
        
        $rand_stmt = $conn->prepare("SELECT id FROM challenges WHERE category = ? ORDER BY RAND() LIMIT 1");
        $rand_stmt->bind_param("s", $category);
        $rand_stmt->execute();
        $challenge_id = $rand_stmt->get_result()->fetch_assoc()['id'];
        
        $insert_stmt = $conn->prepare("INSERT INTO daily_assignments (user_id, challenge_id, assigned_date, status) VALUES (?, ?, ?, 'pending')");
        $insert_stmt->bind_param("iis", $user_id, $challenge_id, $today);
        $insert_stmt->execute();
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}

$pending_quests = [];
$completed_quests = [];
foreach ($assignments as $task) {
    if ($task['status'] == 'completed') {
        $completed_quests[] = $task;
    } else {
        $pending_quests[] = $task;
    }
}

$currentLevelData = getUserLevelData($user['points']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --purple-main: #6c5ce7; --pink-main: #fd79a8; --text-dark: #2d3436; --text-light: #636e72; --bg-main: #f8f9fa; --card-gray: #f1f2f6; --card-green: #eafff5; --border-green: #2ed573; --sidebar-width: 250px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        
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

        .dashboard-container { padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .stats-banner { background: linear-gradient(135deg, var(--purple-main), var(--pink-main)); border-radius: 20px; padding: 30px; color: #fff; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 20px rgba(108, 92, 231, 0.2); margin-bottom: 40px; }
        .user-info h1 { font-size: 2rem; margin-bottom: 5px; }
        .user-info p { font-size: 1.1rem; opacity: 0.9; display: flex; gap: 5px; align-items: center;}
        .stats-badges { display: flex; gap: 20px; }
        .badge { background: rgba(255, 255, 255, 0.2); padding: 15px 25px; border-radius: 15px; text-align: center; backdrop-filter: blur(5px); min-width: 120px; }
        .badge h3 { font-size: 1.8rem; margin: 0; display: inline-block; }
        .badge span.label { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-top: 5px; }
        .badge span.sub-text { font-size: 0.75rem; opacity: 0.8; display: block; margin-top: 5px; }

        #streak-count { color: #fff; transition: color 0.5s; }
        #streak-count.active-today { color: #ffb050ff; text-shadow: 0 0 10px rgba(255, 159, 67, 0.5); }
        #freeze-count { color: #a2d7ffff; text-shadow: 0 0 10px rgba(116, 185, 255, 0.3); }

        .section-title { font-size: 1.5rem; color: var(--purple-main); margin-bottom: 20px; }
        .quests-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .quest-card { background-color: var(--card-gray); border: 2px solid #dfe4ea; border-radius: 15px; padding: 25px; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; flex-direction: column; justify-content: space-between; }
        .quest-category { font-size: 0.8rem; text-transform: uppercase; font-weight: 700; color: var(--text-light); margin-bottom: 10px; }
        .quest-desc { font-size: 1.1rem; margin-bottom: 20px; font-weight: 600; }
        .btn-done { background-color: #fff; border: 2px solid var(--purple-main); color: var(--purple-main); padding: 10px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem; }
        .btn-done:hover { background-color: var(--purple-main); color: #fff; }
        .quest-card.completed { background-color: var(--card-green); border-color: var(--border-green); transform: scale(0.98); box-shadow: 0 5px 15px rgba(46, 213, 115, 0.15); }
        .quest-card.completed .btn-done { background-color: var(--border-green); border-color: var(--border-green); color: #fff; cursor: default; }
        .quest-card.completed .quest-category { color: var(--border-green); }
        #empty-completed-msg { color: var(--text-light); font-style: italic; background: #fff; padding: 20px; border-radius: 10px; border: 1px dashed #b2bec3; text-align: center; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-close { display: block; }
            .main-content { margin-left: 0; }
            .menu-btn { display: block; }
            header { padding: 15px 20px; }
            .header-logo span { display: none; }
            .user-profile span { display: none; }
            .dashboard-container { padding: 20px; }
            .stats-banner { flex-direction: column; text-align: center; gap: 20px; }
            .user-info p { justify-content: center; }
            .stats-badges { flex-wrap: wrap; justify-content: center; width: 100%; }
            .badge { flex: 1; min-width: 100px; }
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
            <div class="user-profile">
                <span><?= htmlspecialchars($user['full_name']) ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=6c5ce7&color=fff" alt="Profile Picture">
            </div>
        </header>

        <div class="dashboard-container">
            <div class="stats-banner">
                <div class="user-info">
                    <h1>Ready, <?= htmlspecialchars(explode(' ', trim($user['full_name']))[0]) ?>?</h1>
                    <p>Level: <span id="user-emoji"><?= $currentLevelData['emoji'] ?></span> <span id="user-title"><?= htmlspecialchars($currentLevelData['title']) ?></span> • <span id="xp-count">0</span> XP</p>
                </div>
                <div class="stats-badges">
                    <div class="badge">
                        <h3><span id="streak-count">0</span> 🔥</h3>
                        <span class="label">Day Streak</span>
                    </div>
                    <div class="badge">
                        <h3><span id="freeze-count">0</span> ❄️</h3>
                        <span class="label">Freezes</span>
                        <span class="sub-text" id="freeze-text">Calculating...</span>
                    </div>
                </div>
            </div>

            <h2 class="section-title">Today's Missions</h2>
            <div class="quests-grid" id="pending-grid">
                <?php if (count($pending_quests) > 0): ?>
                    <?php foreach ($pending_quests as $quest): ?>
                        <div class="quest-card" id="card-<?= $quest['assignment_id'] ?>">
                            <div>
                                <div class="quest-category"><?= htmlspecialchars($quest['category']) ?></div>
                                <div class="quest-desc"><?= htmlspecialchars($quest['description']) ?></div>
                            </div>
                            <button class="btn-done" onclick="completeQuest('card-<?= $quest['assignment_id'] ?>', <?= $quest['assignment_id'] ?>)">Mark as Done</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-light); grid-column: 1 / -1;">Amazing! You finished all your missions today! 🎉</p>
                <?php endif; ?>
            </div>

            <h2 class="section-title" style="margin-top: 40px; color: var(--border-green);">Completed Today</h2>
            <p id="empty-completed-msg" style="<?= count($completed_quests) > 0 ? 'display:none;' : '' ?>">Complete a mission today to save your streak!</p>
            
            <div class="quests-grid" id="completed-grid">
                <?php foreach ($completed_quests as $quest): ?>
                    <div class="quest-card completed" id="card-<?= $quest['assignment_id'] ?>">
                        <div>
                            <div class="quest-category"><?= htmlspecialchars($quest['category']) ?></div>
                            <div class="quest-desc"><?= htmlspecialchars($quest['description']) ?></div>
                        </div>
                        <button class="btn-done" disabled>✔ +10 XP</button>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('sidebarMenu').classList.toggle('mobile-open'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }

        let currentXP = <?= $user['points'] ?>;
        let currentStreak = <?= $user['streak'] ?>;
        let currentFreezes = <?= $user['freezes'] ?>;
        let hasCompletedQuestToday = <?= count($completed_quests) > 0 ? 'true' : 'false' ?>;

        function animateValue(id, start, end, duration) {
            const obj = document.getElementById(id);
            if(start === end) { obj.innerHTML = end; return; }
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        }

        function updateFreezeText(streak) {
            let daysUntilFreeze = 7 - (streak % 7);
            document.getElementById('freeze-text').innerText = `Next freeze in ${daysUntilFreeze} days`;
        }

        window.onload = () => {
            animateValue("xp-count", 0, currentXP, 1000);
            animateValue("streak-count", 0, currentStreak, 1000);
            animateValue("freeze-count", 0, currentFreezes, 1000);
            updateFreezeText(currentStreak);
            
            if (hasCompletedQuestToday) {
                document.getElementById('streak-count').classList.add('active-today');
            }
        };

        async function completeQuest(cardId, assignmentId) {
            const isConfirmed = confirm("Are you sure you completed this mission in real life? Be honest with yourself! 🎮");
            if (!isConfirmed) return; 

            const card = document.getElementById(cardId);
            const button = card.querySelector('.btn-done');
            
            try {
                const response = await fetch('dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=complete_quest&assignment_id=${assignmentId}`
                });
                
                const data = await response.json();
                
                if(data.success) {
                    card.classList.add('completed');
                    button.innerHTML = '✔ +10 XP';
                    button.disabled = true;

                    document.getElementById('xp-count').innerText = data.new_xp;
                    document.getElementById('streak-count').innerText = data.new_streak;
                    document.getElementById('freeze-count').innerText = data.new_freezes;
                    
                    document.getElementById('user-title').innerText = data.new_title;
                    document.getElementById('user-emoji').innerText = data.new_emoji;
                    
                    updateFreezeText(data.new_streak);

                    if (data.streak_saved_today && !hasCompletedQuestToday) {
                        hasCompletedQuestToday = true;
                        document.getElementById('streak-count').classList.add('active-today'); 
                    }

                    setTimeout(() => {
                        document.getElementById('empty-completed-msg').style.display = 'none'; 
                        document.getElementById('completed-grid').appendChild(card); 
                    }, 400);
                }
            } catch (error) {
                console.error("Error completing quest:", error);
                alert("Oops! Something went wrong saving your progress. Check your connection.");
            }
        }
    </script>
</body>
</html>