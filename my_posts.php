<?php
session_start();
require 'db_connect.php'; 

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details and verify they are a creator
$stmt = $conn->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['role'] !== 'creator') {
    // Kick normal users out back to the dashboard
    header("Location: dashboard.php");
    exit;
}

// Handle Post Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = intval($_POST['article_id']);
    // Ensure the post belongs to the logged-in creator before deleting
    $del_stmt = $conn->prepare("DELETE FROM articles WHERE id = ? AND creator_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $user_id);
    $del_stmt->execute();
    
    // Refresh to clear POST data
    header("Location: my_posts.php");
    exit;
}

// ==========================================
// SEARCH & PAGINATION LOGIC
// ==========================================
$search = $_GET['search'] ?? '';
$month_year = $_GET['month_year'] ?? ''; // Format: 'YYYY-MM'

// Pagination setup
$posts_per_page = 6; // Change this number to show more/less posts per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $posts_per_page;

// Base query for both Counting and Fetching
$base_sql = "FROM articles WHERE creator_id = ?";
$params = [$user_id];
$types = "i";

if (!empty($search)) {
    $base_sql .= " AND title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if (!empty($month_year)) {
    $base_sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = ?";
    $params[] = $month_year;
    $types .= "s";
}

// 1. Get Total Count for Pagination
$count_sql = "SELECT COUNT(*) as total " . $base_sql;
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// 2. Fetch the Actual Posts for the Current Page
$fetch_sql = "SELECT * " . $base_sql . " ORDER BY created_at DESC LIMIT ?, ?";
// Add Limit & Offset to parameters
$fetch_params = $params;
$fetch_params[] = $offset;
$fetch_params[] = $posts_per_page;
$fetch_types = $types . "ii";

$stmt = $conn->prepare($fetch_sql);
$stmt->bind_param($fetch_types, ...$fetch_params);
$stmt->execute();
$articles = $stmt->get_result();

// Build URL query string for pagination links so filters aren't lost when changing pages
$url_filters = http_build_query(['search' => $search, 'month_year' => $month_year]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --purple-main: #6c5ce7; --pink-main: #fd79a8; --text-dark: #2d3436; --text-light: #636e72; --bg-main: #f8f9fa; --card-gray: #f1f2f6; --sidebar-width: 250px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; overflow-x: hidden; }
        
        .sidebar { width: var(--sidebar-width); background-color: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; z-index: 200; transition: transform 0.3s ease;}
        .sidebar a { padding: 15px 30px; color: var(--text-light); text-decoration: none; font-weight: 600; transition: 0.3s; display: block; }
        .sidebar a:hover, .sidebar a.active { color: var(--purple-main); background-color: var(--bg-main); border-right: 4px solid var(--purple-main); }
        .sidebar a.logout { color: #ff4757; margin-top: auto; border-right: none; }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); z-index: 150; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.active { display: block; opacity: 1; }
        .mobile-close { display: none; font-size: 1.5rem; color: var(--text-light); text-align: right; padding: 0 20px 10px; cursor: pointer; }

        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; transition: margin-left 0.3s ease;}
        header { background-color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02); position: sticky; top: 0; z-index: 90; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-btn { display: none; font-size: 1.8rem; color: var(--purple-main); cursor: pointer; user-select: none; }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; border-radius: 8px; }
        .header-logo span { font-size: 1.4rem; font-weight: 700; color: var(--purple-main); }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-profile img { height: 45px; width: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--purple-main); }
        .user-profile span { font-weight: 600; }

        .container { padding: 40px; max-width: 1000px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;}
        .page-title { font-size: 2rem; color: var(--purple-main); }
        .btn-create { background-color: var(--purple-main); color: #fff; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: 600; transition: 0.3s; border: none; cursor: pointer;}
        .btn-create:hover { background-color: #5b4bc4; box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3); }

        .filters-bar { background: #fff; padding: 20px; border-radius: 15px; display: flex; gap: 15px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); flex-wrap: wrap; }
        .filter-input { flex: 1; min-width: 200px; padding: 12px 15px; border: 1px solid #dfe6e9; border-radius: 10px; font-family: inherit; font-size: 1rem; outline: none; }
        .filter-input:focus { border-color: var(--purple-main); }
        
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .post-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; }
        .post-title { font-size: 1.2rem; font-weight: 700; margin-bottom: 10px; color: var(--text-dark); }
        .post-meta { font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px; display: flex; justify-content: space-between; }
        
        /* Expandable text styles */
        .post-content { color: var(--text-light); font-size: 0.95rem; margin-bottom: 20px; flex-grow: 1; line-height: 1.6; }
        .read-more-btn { color: var(--purple-main); font-weight: 600; cursor: pointer; text-decoration: underline; margin-left: 5px; background: none; border: none; font-size: 0.95rem; font-family: inherit; padding: 0; }
        .read-more-btn:hover { color: #5b4bc4; }

        .card-actions { display: flex; gap: 10px; border-top: 1px solid #f1f2f6; padding-top: 15px; }
        .btn-action { flex: 1; padding: 8px; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; border: none; cursor: pointer;}
        .btn-edit { background: var(--card-gray); color: var(--text-dark); }
        .btn-edit:hover { background: #dfe4ea; }
        .btn-delete { background: #ffeaa7; color: #d63031; }
        .btn-delete:hover { background: #ff7675; color: #fff; }

        .empty-state { text-align: center; padding: 50px 20px; color: var(--text-light); grid-column: 1 / -1; }

        /* Pagination Styles */
        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 40px; flex-wrap: wrap; }
        .page-link { padding: 10px 18px; border-radius: 10px; background: #fff; color: var(--text-dark); text-decoration: none; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid transparent; }
        .page-link:hover { border-color: var(--purple-main); color: var(--purple-main); }
        .page-link.active { background: var(--purple-main); color: #fff; box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-close { display: block; }
            .main-content { margin-left: 0; }
            .menu-btn { display: block; }
            header { padding: 15px 20px; }
            .header-logo span { display: none; }
            .user-profile span { display: none; }
            .container { padding: 20px; }
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

        <div class="container">
            <div class="page-header">
                <h1 class="page-title">My Published Posts</h1>
                <a href="create_post.php" class="btn-create">+ Write New Post</a>
            </div>

            <form method="GET" action="my_posts.php" class="filters-bar">
                <input type="text" name="search" class="filter-input" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>">
                
                <input type="month" name="month_year" class="filter-input" value="<?= htmlspecialchars($month_year) ?>">
                
                <button type="submit" class="btn-create" style="border-radius: 10px;">Filter</button>
                <?php if(!empty($search) || !empty($month_year)): ?>
                    <a href="my_posts.php" class="btn-edit" style="padding: 12px 20px; display:flex; align-items:center;">Clear</a>
                <?php endif; ?>
            </form>

            <div class="posts-grid">
                <?php if ($articles->num_rows > 0): ?>
                    <?php while ($row = $articles->fetch_assoc()): ?>
                        <div class="post-card">
                            <div class="post-title"><?= htmlspecialchars($row['title']) ?></div>
                            <div class="post-meta">
                                <span>📅 <?= date('M d, Y', strtotime($row['created_at'])) ?></span>
                                <span>❤️ <?= $row['likes'] ?> Likes</span>
                            </div>
                            
                            <div class="post-content">
                                <?php 
                                    $full_text = htmlspecialchars($row['content']);
                                    // Use mb_strlen for safe character counting (handles emojis/accents)
                                    if (mb_strlen($row['content']) > 120): 
                                        $short_text = mb_substr($row['content'], 0, 120) . '...';
                                ?>
                                    <span class="text-short"><?= nl2br(htmlspecialchars($short_text)) ?></span>
                                    <span class="text-full" style="display: none;"><?= nl2br($full_text) ?></span>
                                    <button class="read-more-btn" onclick="toggleText(this)">Read More</button>
                                <?php else: ?>
                                    <span><?= nl2br($full_text) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-actions">
                                <a href="edit_post.php?id=<?= $row['id'] ?>" class="btn-action btn-edit">Edit</a>
                                
                                <form method="POST" action="my_posts.php" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this post forever?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="article_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn-action btn-delete" style="width: 100%;">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No posts found!</h3>
                        <p>Adjust your filters or write a new article to share with the community.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= $url_filters ?>" class="page-link">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&<?= $url_filters ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= $url_filters ?>" class="page-link">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        function toggleMobileMenu() {
            document.getElementById('sidebarMenu').classList.toggle('mobile-open'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }

        // Expand / Collapse Post Content
        function toggleText(btn) {
            const container = btn.parentElement;
            const shortText = container.querySelector('.text-short');
            const fullText = container.querySelector('.text-full');

            if (fullText.style.display === 'none') {
                fullText.style.display = 'inline';
                shortText.style.display = 'none';
                btn.innerText = 'Show Less';
            } else {
                fullText.style.display = 'none';
                shortText.style.display = 'inline';
                btn.innerText = 'Read More';
            }
        }
    </script>
</body>
</html>