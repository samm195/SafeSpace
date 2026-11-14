<?php
session_start();
require 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 1. Fetch current user data for the header
$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$full_name = $user['full_name'];

// 2. HANDLE AJAX LIKE SYSTEM (No page reload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_like'])) {
    header('Content-Type: application/json');
    $article_id = (int)$_POST['article_id'];
    
    // Check if the user already liked this article
    $check_stmt = $conn->prepare("SELECT id FROM article_likes WHERE user_id = ? AND article_id = ?");
    $check_stmt->bind_param("ii", $user_id, $article_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Already liked -> UNLIKE
        $conn->query("DELETE FROM article_likes WHERE user_id = $user_id AND article_id = $article_id");
        $conn->query("UPDATE articles SET likes = GREATEST(likes - 1, 0) WHERE id = $article_id");
        echo json_encode(['status' => 'unliked']);
    } else {
        // Not liked -> LIKE
        $conn->query("INSERT INTO article_likes (user_id, article_id) VALUES ($user_id, $article_id)");
        $conn->query("UPDATE articles SET likes = likes + 1 WHERE id = $article_id");
        echo json_encode(['status' => 'liked']);
    }
    exit; // Stop executing the rest of the page for AJAX requests
}

// 3. SEARCH & FETCH ARTICLES
$search_query = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';

$where_clause = "WHERE 1=1";
if ($search_query !== '') {
    $where_clause .= " AND (a.title LIKE '%$search_query%' OR u.full_name LIKE '%$search_query%')";
}

// Ensure you have a chronological fallback (using 'created_at' if it exists, otherwise 'a.id DESC')
$sql = "
    SELECT 
        a.id, a.title, a.content, 
        COALESCE(a.created_at, 'Recently') as publish_date,
        u.full_name as author,
        (SELECT COUNT(*) FROM article_likes al WHERE al.article_id = a.id AND al.user_id = $user_id) as user_has_liked
    FROM articles a
    JOIN users u ON a.creator_id = u.id
    $where_clause
    ORDER BY a.id DESC
";

$articles_result = $conn->query($sql);

// 4. Array of colors to cycle through for article cards and avatars
$theme_colors = ['#6c5ce7', '#fd79a8', '#00b894', '#0984e3', '#e17055', '#f39c12'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Articles – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        
        /* SIDEBAR & HEADER CSS (Matching Volunteering) */
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

        /* ARTICLES SPECIFIC CSS */
        .page-container { padding: 40px; max-width: 900px; margin: 0 auto; width: 100%; }
        
        .page-header { text-align: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 2.2rem; color: var(--purple-main); margin-bottom: 10px; }
        .page-header p { color: var(--text-light); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        /* Search Bar */
        .search-form { display: flex; justify-content: center; margin-bottom: 40px; width: 100%; }
        .search-container { position: relative; width: 100%; max-width: 500px; }
        .search-input { width: 100%; padding: 15px 25px 15px 50px; border-radius: 30px; border: 1px solid #dfe6e9; font-size: 1rem; font-family: 'Poppins', sans-serif; box-shadow: 0 5px 15px rgba(0,0,0,0.03); outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--purple-main); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.15); }
        .search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; fill: #b2bec3; pointer-events: none; }
        .search-btn { display: none; } /* Submits on enter */

        /* Article Cards Feed */
        .article-feed { display: flex; flex-direction: column; gap: 35px; }
        .article-card { background: #fff; border-radius: 20px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: transform 0.3s ease, box-shadow 0.3s ease; border-top: 5px solid var(--purple-main); }
        .article-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }

        /* Author Meta */
        .author-meta { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .author-avatar { width: 48px; height: 48px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.2rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .author-details h4 { font-size: 1.05rem; color: var(--text-dark); margin: 0; font-weight: 700; }
        .author-details p { font-size: 0.85rem; color: var(--text-light); margin: 0; }

        /* Article Content */
        .article-title { font-size: 1.6rem; color: #1e272e; margin-bottom: 15px; font-weight: 700; line-height: 1.3; }
        .article-body { color: #485460; line-height: 1.8; font-size: 1rem; margin-bottom: 25px; white-space: pre-line; }

        /* Action Footer */
        .article-footer { display: flex; justify-content: flex-end; align-items: center; border-top: 1px solid #f1f2f6; padding-top: 15px; }
        
        .like-btn { background: none; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; border-radius: 50%; transition: background 0.2s; background: var(--bg-purple-light); }
        .like-btn:hover { background: #ffeaa7; }
        .like-btn svg { width: 24px; height: 24px; transition: transform 0.2s; }
        .like-btn:active svg { transform: scale(0.8); }
        
        .heart-empty { fill: none; stroke: var(--text-light); stroke-width: 2; }
        .heart-filled { fill: #ff4757; stroke: #ff4757; }

        .empty-state { text-align: center; color: var(--text-light); font-size: 1.1rem; padding: 40px; }

        /* RESPONSIVE DESIGN */
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
            .article-card { padding: 25px; }
        }
    </style>
</head>
<body>

    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

    <aside class="sidebar" id="sidebarMenu">
        <div class="mobile-close" onclick="toggleMobileMenu()">✖</div>
        <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
        <a href="articles.php" class="<?= ($current_page == 'articles.php') ? 'active' : ''; ?>">Articles</a>
        
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
                    <img src="images/logo.png" alt="Logo" onerror="this.style.display='none'"> 
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
                <h1>Explore Articles</h1>
                <p>Read inspiring stories, tips, and insights shared by our incredible creators.</p>
            </div>

            <form method="GET" class="search-form">
                <div class="search-container">
                    <svg class="search-icon" viewBox="0 0 24 24">
                        <path d="M10 18a7.952 7.952 0 0 0 4.897-1.688l4.396 4.396 1.414-1.414-4.396-4.396A7.952 7.952 0 0 0 18 10c0-4.411-3.589-8-8-8s-8 3.589-8 8 3.589 8 8 8zm0-14c3.309 0 6 2.691 6 6s-2.691 6-6 6-6-2.691-6-6 2.691-6 6-6z"/>
                    </svg>
                    <input type="text" name="q" class="search-input" placeholder="Search by article title or creator..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>

            <div class="article-feed">
                <?php if ($articles_result && $articles_result->num_rows > 0): ?>
                    <?php 
                        $card_index = 0;
                        while ($article = $articles_result->fetch_assoc()): 
                            // Extract initials
                            $words = explode(" ", trim($article['author']));
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            
                            // Format Date
                            $date_display = $article['publish_date'];
                            if (strtotime($date_display)) {
                                $date_display = date('F j, Y', strtotime($date_display));
                            }
                            
                            $is_liked = $article['user_has_liked'] > 0;

                            // Apply Theme Colors
                            $theme_color = $theme_colors[$card_index % count($theme_colors)];
                            $card_index++;
                    ?>
                        <div class="article-card" style="border-top-color: <?= $theme_color ?>;">
                            <div class="author-meta">
                                <div class="author-avatar" style="background-color: <?= $theme_color ?>;">
                                    <?= htmlspecialchars($initials); ?>
                                </div>
                                <div class="author-details">
                                    <h4><?= htmlspecialchars($article['author']); ?></h4>
                                    <p><?= $date_display; ?></p>
                                </div>
                            </div>

                            <h2 class="article-title"><?= htmlspecialchars($article['title']); ?></h2>
                            
                            <div class="article-body">
                                <?= htmlspecialchars($article['content']); ?>
                            </div>

                            <div class="article-footer">
                                <button class="like-btn" onclick="toggleLike(this, <?= $article['id']; ?>)" aria-label="Like Article">
                                    <svg viewBox="0 0 24 24" class="<?= $is_liked ? 'heart-filled' : 'heart-empty'; ?>">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h2>No articles found.</h2>
                        <p>Try searching for a different keyword or creator.</p>
                    </div>
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

        // AJAX Like Feature
        function toggleLike(btn, articleId) {
            const svg = btn.querySelector('svg');
            
            const formData = new FormData();
            formData.append('ajax_like', '1');
            formData.append('article_id', articleId);

            fetch('', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'liked') {
                    svg.classList.remove('heart-empty');
                    svg.classList.add('heart-filled');
                } else if (data.status === 'unliked') {
                    svg.classList.remove('heart-filled');
                    svg.classList.add('heart-empty');
                }
            })
            .catch(error => console.error('Error toggling like:', error));
        }
    </script>
</body>
</html>