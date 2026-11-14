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

// 1. Fetch current user data for profile AND header
$stmt = $conn->prepare("SELECT full_name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$role = $user['role'];
$full_name = $user['full_name'];
$email = $user['email'];

$error_message = '';
$success_message = '';
$rating_message = '';

// --- NEW: Handle App Rating Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_rating'])) {
    $rating_value = (int)$_POST['rating_value'];
    if ($rating_value >= 1 && $rating_value <= 5) {
        // Insert new rating, or update if the user already rated
        $stmt = $conn->prepare("INSERT INTO app_ratings (user_id, rating) VALUES (?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->bind_param("iii", $user_id, $rating_value, $rating_value);
        if ($stmt->execute()) {
            $rating_message = "Thank you! Your rating has been saved.";
        }
    }
}

// Fetch user's current app rating (if they have one)
$stmt = $conn->prepare("SELECT rating FROM app_ratings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rating_res = $stmt->get_result();
$current_rating = $rating_res->num_rows > 0 ? $rating_res->fetch_assoc()['rating'] : 0;


// 2. Handle Creator Request Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_creator_request']) && $role == 'user') {
    $description = $conn->real_escape_string(trim($_POST['description']));
    
    if (empty($description)) {
        $error_message = "Please provide a brief professional description.";
    } else {
        $new_file_name = NULL;
        $upload_ok = true;

        if (isset($_FILES['credential_file']) && $_FILES['credential_file']['error'] != UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['credential_file'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = array("pdf", "jpeg", "jpg", "png");
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $error_message = "Invalid file type. Only PDF, JPEG, JPG, and PNG are allowed.";
                $upload_ok = false;
            } else {
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                
                $new_file_name = "creator_req_" . $user_id . "_" . time() . "." . $file_ext;
                $upload_path = "uploads/" . $new_file_name;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $error_message = "Failed to upload file to server.";
                    $upload_ok = false;
                }
            }
        }

        if ($upload_ok) {
            $file_name_sql = $new_file_name ? "'$new_file_name'" : "NULL";
            $insert_sql = "INSERT INTO creator_requests (user_id, description, file_name, status) 
                           VALUES ('$user_id', '$description', $file_name_sql, 'pending')";
                           
            if ($conn->query($insert_sql) === TRUE) {
                $success_message = "Your application has been successfully submitted to the Admin team!";
            } else {
                $error_message = "Database error saving request.";
            }
        }
    }
}

// 3. Check if a creator request already exists
$request_check = "SELECT status FROM creator_requests WHERE user_id = '$user_id' ORDER BY id DESC LIMIT 1";
$request_result = $conn->query($request_check);
$existing_request = $request_result->num_rows > 0 ? $request_result->fetch_assoc() : null;

// 4. Data Extraction for Charts (All Users: Category Breakdown)
$category_labels = [];
$category_counts = [];
$chart_query = "SELECT c.category, COUNT(da.id) as total 
                FROM daily_assignments da 
                JOIN challenges c ON da.challenge_id = c.id 
                WHERE da.user_id = '$user_id' AND da.status = 'completed' 
                GROUP BY c.category";
$chart_result = $conn->query($chart_query);
while ($row = $chart_result->fetch_assoc()) {
    $category_labels[] = $row['category'];
    $category_counts[] = (int)$row['total'];
}

// 5. Data Extraction for Creator Charts (Creators Only)
$creator_articles = [];
if ($role == 'creator') {
    // We now fetch created_at to allow filtering by month in JavaScript
    $creator_chart_query = "SELECT title, likes, created_at FROM articles WHERE creator_id = '$user_id' ORDER BY created_at ASC";
    $creator_chart_result = $conn->query($creator_chart_query);
    while ($row = $creator_chart_result->fetch_assoc()) {
        $creator_articles[] = [
            'title' => strlen($row['title']) > 15 ? substr($row['title'], 0, 15) . '...' : $row['title'],
            'likes' => (int)$row['likes'],
            'date' => $row['created_at'] // e.g. "2024-05-12 14:30:00"
        ];
    }
}

// 6. Data Extraction for Activity Calendar (Pulls ALL completed dates)
$completed_dates = [];
$cal_query = "SELECT DISTINCT assigned_date FROM daily_assignments WHERE user_id = '$user_id' AND status = 'completed'";
$cal_result = $conn->query($cal_query);
while ($row = $cal_result->fetch_assoc()) {
    $completed_dates[] = $row['assigned_date'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root { --purple-main: #6c5ce7; --pink-main: #fd79a8; --text-dark: #2d3436; --text-light: #636e72; --bg-main: #f8f9fa; --sidebar-width: 250px; --bg-purple-light: #f5f3ff; }
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

        /* PROFILE SPECIFIC CSS */
        .profile-container { padding: 40px; max-width: 1200px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 30px; }
        
        .profile-card { text-align: center; }
        .avatar { width: 100px; height: 100px; background-color: var(--bg-purple-light); border: 3px solid var(--purple-main); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 2.5rem; font-weight: 700; color: var(--purple-main); margin: 0 auto 20px; }
        .profile-card h2 { font-size: 1.4rem; margin-bottom: 5px; }
        .profile-card p { color: var(--text-light); font-size: 0.95rem; margin-bottom: 15px; }
        .role-badge { display: inline-block; padding: 5px 15px; background: var(--purple-main); color: #fff; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        
        .card h3 { font-size: 1.2rem; color: var(--purple-main); margin-bottom: 20px; border-bottom: 2px solid var(--bg-purple-light); padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; }
        .form-group textarea, .form-group input[type="file"] { width: 100%; padding: 12px; border: 1px solid #dfe6e9; border-radius: 10px; font-family: 'Poppins', sans-serif; }
        .btn-submit { background: var(--purple-main); color: #fff; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; width: 100%; transition: 0.3s; }
        .btn-submit:hover { background: #5a4bcf; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; text-align: left; }
        .alert-error { background: #ffeaa7; color: #d63031; }
        .alert-success { background: #55efc4; color: #00b894; }
        
        /* APP RATING CSS */
        .rating-container { display: flex; flex-direction: column; align-items: center; text-align: center; }
        .stars { display: flex; gap: 10px; font-size: 2.5rem; color: #dfe6e9; cursor: pointer; margin-bottom: 15px; }
        .stars span { transition: color 0.2s; }
        .stars span:hover, .stars span.active { color: #fdcb6e; }
        
        /* CHARTS & CALENDAR */
        .charts-container { display: grid; grid-template-columns: 1fr; gap: 30px; }
        .chart-box { min-height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; }
        canvas { max-width: 100% !important; }
        .chart-header-row { display: flex; justify-content: space-between; width: 100%; align-items: center; margin-bottom: 15px; }

        .calendar-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; font-weight: 600; }
        .nav-btn { background: var(--bg-purple-light); border: none; color: var(--purple-main); font-size: 1.2rem; cursor: pointer; border-radius: 5px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .nav-btn:hover { background: var(--purple-main); color: #fff; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; }
        .day-name { font-weight: 700; font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; margin-bottom: 5px; }
        .cal-day { padding: 12px 5px; border-radius: 10px; font-size: 0.9rem; transition: 0.3s; border: 1px solid transparent; }
        
        .cal-day.gray { background: #f1f2f6; color: var(--text-light); }
        .cal-day.orange { background: #ff9f43; color: #fff; font-weight: bold; box-shadow: 0 4px 10px rgba(255, 159, 67, 0.3); border-color: #ff9f43; }
        .cal-day.empty { background: transparent; }

        @media (max-width: 900px) { .profile-container { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .mobile-close { display: block; }
            .main-content { margin-left: 0; }
            .menu-btn { display: block; }
            header { padding: 15px 20px; }
            .header-logo span, .user-profile span { display: none; }
            .profile-container { padding: 20px; }
            .calendar-header { gap: 10px; }
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

        <div class="profile-container">
            <div class="left-panel">
                <div class="card profile-card">
                    <div class="avatar">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($full_name); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                    <span class="role-badge"><?php echo $role; ?></span>
                </div>

                <?php if ($role == 'user'): ?>
                    <div class="card">
                        <h3>Apply for Creator Role</h3>
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-error"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>

                        <?php if ($existing_request && $existing_request['status'] == 'pending'): ?>
                            <div class="alert" style="background-color: #e0f7fa; color: #00838f; border: 1px dashed #00acc1;">
                                <strong>Status: Pending Review</strong><br>The admin team is verifying your application.
                            </div>
                        <?php elseif ($existing_request && $existing_request['status'] == 'rejected'): ?>
                            <div class="alert alert-error">
                                <strong>Application Rejected</strong><br>Your previous credentials could not be verified. You may resubmit below.
                            </div>
                        <?php endif; ?>

                        <?php if (!$existing_request || $existing_request['status'] != 'pending'): ?>
                            <form action="profile.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="description">Professional Background / Bio</label>
                                    <textarea id="description" name="description" rows="3" required placeholder="Tell us why you'd like to be a creator..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="credential_file">Upload Verification Doc (Optional)</label>
                                    <input type="file" id="credential_file" name="credential_file" accept=".pdf,.png,.jpg,.jpeg">
                                </div>
                                <button type="submit" name="submit_creator_request" class="btn-submit">Submit Application</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="right-panel">
                
                <div class="card rating-container">
                    <h3>Rate Your SafeSpace Experience</h3>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px;">Your feedback helps us improve the platform for everyone.</p>

                    <?php if (!empty($rating_message)): ?>
                            <div class="alert alert-success"><?php echo $rating_message; ?></div>
                    <?php endif; ?>
                    
                    <form action="profile.php" method="POST" id="ratingForm">
                        <div class="stars" id="starContainer">
                            <span data-value="1">★</span>
                            <span data-value="2">★</span>
                            <span data-value="3">★</span>
                            <span data-value="4">★</span>
                            <span data-value="5">★</span>
                        </div>
                        <input type="hidden" name="rating_value" id="ratingInput" value="<?= $current_rating ?>">
                        <button type="submit" name="submit_rating" class="btn-submit" style="width: auto; padding: 10px 30px;">Submit Rating</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Growth Insights & Analytics</h3>
                    
                    <div style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px dashed var(--bg-purple-light);">
                        <div class="calendar-header">
                            <button id="prev-month" class="nav-btn">&lt;</button>
                            <span id="month-year-display"></span>
                            <button id="next-month" class="nav-btn">&gt;</button>
                            
                            <span style="font-size: 0.8rem; color: var(--text-light); margin-left: auto;">🔥 Streak Heatmap</span>
                        </div>
                        <div class="calendar-grid" id="calendar-grid">
                            </div>
                    </div>

                    <div class="charts-container">
                        <div class="chart-box">
                            <h4>Completed Challenges by Theme</h4>
                            <?php if(empty($category_labels)): ?>
                                <p style="color: var(--text-light); margin-top:20px;">No challenges completed yet. Complete tasks to see insights!</p>
                            <?php else: ?>
                                <canvas id="categoryChart"></canvas>
                            <?php endif; ?>
                        </div>

                        <?php if ($role == 'creator'): ?>
                            <div class="chart-box" style="margin-top: 40px; border-top: 2px dashed var(--bg-purple-light); padding-top: 30px;">
                                <div class="chart-header-row">
                                    <h4>Article Engagement</h4>
                                    <div class="calendar-header" style="margin-bottom: 0;">
                                        <button id="prev-chart-month" class="nav-btn">&lt;</button>
                                        <span id="chart-month-display" style="font-size: 0.9rem; min-width: 100px; text-align: center;"></span>
                                        <button id="next-chart-month" class="nav-btn">&gt;</button>
                                    </div>
                                </div>
                                
                                <?php if(empty($creator_articles)): ?>
                                    <p style="color: var(--text-light); margin-top:20px;">You haven't published any articles yet.</p>
                                <?php else: ?>
                                    <canvas id="creatorChart"></canvas>
                                    <p id="no-articles-msg" style="display:none; color: var(--text-light); margin-top:20px;">No articles published in this month.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            document.getElementById('sidebarMenu').classList.toggle('mobile-open'); 
            document.getElementById('sidebarOverlay').classList.toggle('active'); 
        }

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        // ==========================================
        // 0. APP RATING STAR LOGIC
        // ==========================================
        const stars = document.querySelectorAll('#starContainer span');
        const ratingInput = document.getElementById('ratingInput');
        let currentRating = parseInt(ratingInput.value) || 0;

        function updateStars(value) {
            stars.forEach(star => {
                if (parseInt(star.getAttribute('data-value')) <= value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Initialize existing rating
        updateStars(currentRating);

        stars.forEach(star => {
            star.addEventListener('mouseover', () => updateStars(star.getAttribute('data-value')));
            star.addEventListener('mouseout', () => updateStars(currentRating));
            star.addEventListener('click', () => {
                currentRating = star.getAttribute('data-value');
                ratingInput.value = currentRating;
                updateStars(currentRating);
            });
        });

        // ==========================================
        // 1. DYNAMIC CALENDAR HEATMAP GENERATOR
        // ==========================================
        const completedDates = <?php echo json_encode($completed_dates); ?>;
        
        const calGrid = document.getElementById('calendar-grid');
        const monthYearTxt = document.getElementById('month-year-display');
        
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth(); 
        let currentYear = currentDate.getFullYear();
        
        function renderCalendar(month, year) {
            calGrid.innerHTML = ''; 
            monthYearTxt.innerText = `${monthNames[month]} ${year}`;
            
            const firstDay = new Date(year, month, 1).getDay(); 
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            daysOfWeek.forEach(d => {
                const el = document.createElement('div');
                el.className = 'day-name';
                el.innerText = d;
                calGrid.appendChild(el);
            });
            
            for(let i = 0; i < firstDay; i++) {
                const el = document.createElement('div');
                el.className = 'cal-day empty';
                calGrid.appendChild(el);
            }
            
            for(let i = 1; i <= daysInMonth; i++) {
                const el = document.createElement('div');
                el.className = 'cal-day gray'; 
                el.innerText = i;
                
                const m = String(month + 1).padStart(2, '0');
                const d = String(i).padStart(2, '0');
                const dateStr = `${year}-${m}-${d}`;
                
                if (completedDates.includes(dateStr)) {
                    el.className = 'cal-day orange';
                }
                
                calGrid.appendChild(el);
            }
        }

        document.getElementById('prev-month').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('next-month').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderCalendar(currentMonth, currentYear);
        });

        renderCalendar(currentMonth, currentYear);


        // ==========================================
        // 2. CHART.JS CONFIGURATIONS
        // ==========================================
        <?php if(!empty($category_labels)): ?>
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_counts); ?>,
                    backgroundColor: ['#6c5ce7', '#00b894', '#00cec9', '#ff7675', '#fab1a0', '#fdcb6e'],
                    borderWidth: 2
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
        <?php endif; ?>

        // ==========================================
        // 3. DYNAMIC CREATOR CHART (FILTER BY MONTH)
        // ==========================================
        <?php if ($role == 'creator' && !empty($creator_articles)): ?>
        
        const allArticles = <?php echo json_encode($creator_articles); ?>;
        
        let chartMonth = new Date().getMonth();
        let chartYear = new Date().getFullYear();
        let creatorChartInstance = null;
        
        const ctxCreator = document.getElementById('creatorChart').getContext('2d');
        const chartMonthTxt = document.getElementById('chart-month-display');
        const noArticlesMsg = document.getElementById('no-articles-msg');
        
        function renderCreatorChart(month, year) {
            chartMonthTxt.innerText = `${monthNames[month]} ${year}`;
            
            // Filter articles by the selected month/year
            const filteredArticles = allArticles.filter(article => {
                const articleDate = new Date(article.date);
                return articleDate.getMonth() === month && articleDate.getFullYear() === year;
            });
            
            // Extract labels and data from filtered results
            const labels = filteredArticles.map(a => a.title);
            const data = filteredArticles.map(a => a.likes);
            
            // Show/Hide empty state message
            if (filteredArticles.length === 0) {
                document.getElementById('creatorChart').style.display = 'none';
                noArticlesMsg.style.display = 'block';
            } else {
                document.getElementById('creatorChart').style.display = 'block';
                noArticlesMsg.style.display = 'none';
            }
            
            // Destroy existing chart to prevent overlap
            if (creatorChartInstance) {
                creatorChartInstance.destroy();
            }
            
            // Create new chart
            if (filteredArticles.length > 0) {
                creatorChartInstance = new Chart(ctxCreator, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Likes Received',
                            data: data,
                            backgroundColor: 'rgba(108, 92, 231, 0.7)',
                            borderColor: '#6c5ce7',
                            borderWidth: 1
                        }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            }
        }

        // Event listeners for chart month navigation
        document.getElementById('prev-chart-month').addEventListener('click', () => {
            chartMonth--;
            if (chartMonth < 0) { chartMonth = 11; chartYear--; }
            renderCreatorChart(chartMonth, chartYear);
        });

        document.getElementById('next-chart-month').addEventListener('click', () => {
            chartMonth++;
            if (chartMonth > 11) { chartMonth = 0; chartYear++; }
            renderCreatorChart(chartMonth, chartYear);
        });

        // Initial render for the current month
        renderCreatorChart(chartMonth, chartYear);
        
        <?php endif; ?>
    </script>
</body>
</html>