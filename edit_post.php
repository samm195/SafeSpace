<?php
session_start();
require 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Verify they are a creator
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['role'] !== 'creator') {
    header("Location: dashboard.php");
    exit;
}

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_msg = "";

// Fetch the existing article, making sure it belongs to THIS creator
$stmt = $conn->prepare("SELECT title, content FROM articles WHERE id = ? AND creator_id = ?");
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) {
    header("Location: my_posts.php");
    exit; // Stop execution if the article doesn't exist or isn't theirs
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $update_stmt = $conn->prepare("UPDATE articles SET title = ?, content = ? WHERE id = ? AND creator_id = ?");
        $update_stmt->bind_param("ssii", $title, $content, $article_id, $user_id);
        
        if ($update_stmt->execute()) {
            header("Location: my_posts.php");
            exit;
        } else {
            $error_msg = "Error updating post. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --purple-main: #6c5ce7; --text-dark: #2d3436; --text-light: #636e72; --bg-main: #f8f9fa; --sidebar-width: 250px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); color: var(--text-dark); display: flex; min-height: 100vh; }
        
        .sidebar { width: var(--sidebar-width); background-color: #fff; box-shadow: 2px 0 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; padding: 30px 0; position: fixed; height: 100vh; }
        .sidebar a { padding: 15px 30px; color: var(--text-light); text-decoration: none; font-weight: 600; transition: 0.3s; display: block; }
        .sidebar a:hover, .sidebar a.active { color: var(--purple-main); background-color: var(--bg-main); border-right: 4px solid var(--purple-main); }
        .sidebar a.logout { color: #ff4757; margin-top: auto; }
        
        .main-content { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        header { background-color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; border-radius: 8px; }
        .header-logo span { font-size: 1.4rem; font-weight: 700; color: var(--purple-main); }
        
        .container { padding: 40px; max-width: 800px; margin: 0 auto; width: 100%; }
        .form-card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .form-card h1 { color: var(--purple-main); margin-bottom: 20px; font-size: 1.8rem; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-dark); }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #dfe6e9; border-radius: 10px; font-family: inherit; font-size: 1rem; outline: none; transition: border-color 0.3s; }
        .form-control:focus { border-color: var(--purple-main); }
        textarea.form-control { min-height: 200px; resize: vertical; }
        
        .btn-submit { background-color: var(--purple-main); color: #fff; padding: 12px 30px; border-radius: 10px; border: none; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #5b4bc4; box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .alert-error { background: #ffeaa7; color: #d63031; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="dashboard.php">Dashboard</a>
        <a href="articles.php">Articles</a>
        <a href="my_posts.php" class="active">My Posts</a>
        <a href="profile.php">Profile</a>
        <a href="liked_articles.php">Liked Articles</a>
        <a href="volunteering.php">Volunteering</a>
        <a href="game_rules.php">Game Rules</a>
        <a href="about.php">About Us</a>
        <a href="login.php" class="logout">Logout</a>
    </aside>

    <div class="main-content">
        <header>
            <div class="header-logo">
                <img src="images/logo.png" alt="Logo">
                <span>SafeSpace</span>
            </div>
        </header>

        <div class="container">
            <div class="form-card">
                <h1>Edit Post</h1>
                
                <?php if ($error_msg): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($article['title']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" required><?= htmlspecialchars($article['content']) ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Changes</button>
                    <a href="my_posts.php" style="margin-left: 15px; color: var(--text-light); text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>