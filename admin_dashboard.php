<?php
session_start();
require 'db_connect.php';

// SECURITY LOCK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$message = "";
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// PAGINATION VARIABLES CONFIGURATION
$limit = 5; // Records per page. Adjust this to 10 or 20 for production.
$current_p = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($current_p - 1) * $limit;

// Reusable Navigation Link Generator that saves active search filters
function get_page_url($page_num)
{
  $current_get = $_GET;
  $current_get['p'] = $page_num; // Inject new page number
  return '?' . http_build_query($current_get);
}

// Collect all dynamic custom challenge categories from DB
$cat_res = $conn->query("SELECT DISTINCT category FROM challenges WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = [];
while ($c_row = $cat_res->fetch_assoc()) {
  $categories[] = $c_row['category'];
}
if (empty($categories)) {
  $categories = ['Study & Academics', 'Deep Productivity', 'Sleep Hygiene', 'Nutrition & Hydration', 'Physical Vitality', 'Mental Calmness & Stress'];
}

// DATA ACTION SUBMISSIONS HANDLER (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'approve_creator') {
    $req_user_id = (int) $_POST['req_user_id'];
    $request_id = (int) $_POST['request_id'];
    $conn->query("UPDATE users SET role = 'creator' WHERE id = $req_user_id");
    $conn->query("DELETE FROM creator_requests WHERE id = $request_id");
    $message = "Success: User upgraded to Creator!";
  } elseif ($_POST['action'] === 'reject_creator') {
    $request_id = (int) $_POST['request_id'];
    $conn->query("DELETE FROM creator_requests WHERE id = $request_id");
    $message = "Creator request rejected.";
  } elseif ($_POST['action'] === 'delete_user') {
    $delete_id = (int) $_POST['delete_user_id'];
    $conn->query("DELETE FROM users WHERE id = $delete_id");
    $message = "User has been permanently deleted.";
  } elseif ($_POST['action'] === 'delete_article') {
    $delete_id = (int) $_POST['delete_article_id'];
    $conn->query("DELETE FROM articles WHERE id = $delete_id");
    $message = "Article has been removed.";
  } elseif ($_POST['action'] === 'add_challenge') {
    $category = ($_POST['category'] === '__custom__') ? $_POST['custom_category'] : $_POST['category'];
    $category = $conn->real_escape_string($category);
    $description = $conn->real_escape_string($_POST['description']);
    $conn->query("INSERT INTO challenges (category, description) VALUES ('$category', '$description')");
    header("Location: admin_dashboard.php?page=challenges");
    exit;
  } elseif ($_POST['action'] === 'edit_challenge') {
    $edit_id = (int) $_POST['challenge_id'];
    $category = ($_POST['category'] === '__custom__') ? $_POST['custom_category'] : $_POST['category'];
    $category = $conn->real_escape_string($category);
    $description = $conn->real_escape_string($_POST['description']);
    $conn->query("UPDATE challenges SET category='$category', description='$description' WHERE id=$edit_id");
    header("Location: admin_dashboard.php?page=challenges");
    exit;
  } elseif ($_POST['action'] === 'delete_challenge') {
    $delete_id = (int) $_POST['delete_challenge_id'];
    $conn->query("DELETE FROM challenges WHERE id = $delete_id");
    header("Location: admin_dashboard.php?page=challenges");
    exit;
  } elseif ($_POST['action'] === 'add_volunteering') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $external_link = $conn->real_escape_string($_POST['external_link']);
    $conn->query("INSERT INTO volunteering (title, description, location, external_link) VALUES ('$title', '$description', '$location', '$external_link')");
  } elseif ($_POST['action'] === 'edit_volunteering') {
    $edit_id = (int) $_POST['volunteering_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $location = $conn->real_escape_string($_POST['location']);
    $external_link = $conn->real_escape_string($_POST['external_link']);
    $conn->query("UPDATE volunteering SET title='$title', description='$description', location='$location', external_link='$external_link' WHERE id=$edit_id");
    header("Location: admin_dashboard.php?page=volunteering");
    exit;
  } elseif ($_POST['action'] === 'delete_volunteering') {
    $delete_id = (int) $_POST['delete_volunteering_id'];
    $conn->query("DELETE FROM volunteering WHERE id = $delete_id");
  }
}

// RETRIEVE DATA WITH PAGINATION CONFIGURATIONS (GET)
$data = [];
$total_rows = 0;

if ($page === 'dashboard') {
  $data['total_users'] = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
  $data['total_articles'] = $conn->query("SELECT COUNT(*) as total FROM articles")->fetch_assoc()['total'];
  $data['total_challenges'] = $conn->query("SELECT COUNT(*) as total FROM challenges")->fetch_assoc()['total'];

  // Total calculation for creator requests
  $total_rows = $conn->query("SELECT COUNT(*) as total FROM creator_requests WHERE status='pending'")->fetch_assoc()['total'];
  $data['pending_requests'] = $conn->query("SELECT cr.id as request_id, cr.user_id, cr.file_name, cr.description, u.full_name, u.email FROM creator_requests cr JOIN users u ON cr.user_id = u.id WHERE cr.status='pending' ORDER BY cr.id DESC LIMIT $limit OFFSET $offset");

  // Distribution chart
  $chart_query = $conn->query("SELECT category, COUNT(*) as count FROM challenges GROUP BY category");
  $chart_labels = [];
  $chart_counts = [];
  while ($row = $chart_query->fetch_assoc()) {
    $chart_labels[] = $row['category'] ? $row['category'] : 'Unassigned';
    $chart_counts[] = (int) $row['count'];
  }

  // Play Store Style Rating Chart Logic (Database Integrated)
  $rating_data = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
  $rating_query = $conn->query("SELECT rating, COUNT(*) as count FROM app_ratings GROUP BY rating");
  
  if ($rating_query) {
      while ($r_row = $rating_query->fetch_assoc()) {
          $rating_data[(int)$r_row['rating']] = (int)$r_row['count'];
      }
  }

  $total_ratings = array_sum($rating_data);
  $average_rating = 0;
  
  if ($total_ratings > 0) {
      $sum = 0;
      foreach ($rating_data as $stars => $count) {
          $sum += $stars * $count;
      }
      $average_rating = round($sum / $total_ratings, 1);
  }

} elseif ($page === 'users') {
  $search_name = isset($_GET['search_name']) ? $conn->real_escape_string($_GET['search_name']) : '';
  $filter_role = isset($_GET['filter_role']) ? $conn->real_escape_string($_GET['filter_role']) : '';

  $where_clause = " WHERE 1=1";
  if ($search_name)
    $where_clause .= " AND full_name LIKE '%$search_name%'";
  if ($filter_role)
    $where_clause .= " AND role = '$filter_role'";

  $total_rows = $conn->query("SELECT COUNT(*) as total FROM users $where_clause")->fetch_assoc()['total'];
  $data['users_list'] = $conn->query("SELECT id, full_name, email, role, points FROM users $where_clause ORDER BY role, full_name LIMIT $limit OFFSET $offset");
} elseif ($page === 'articles') {
  $search_title = isset($_GET['search_title']) ? $conn->real_escape_string($_GET['search_title']) : '';

  $where_clause = " WHERE 1=1";
  if ($search_title)
    $where_clause .= " AND a.title LIKE '%$search_title%'";

  $total_rows = $conn->query("SELECT COUNT(*) as total FROM articles a JOIN users u ON a.creator_id = u.id $where_clause")->fetch_assoc()['total'];
  $data['articles_list'] = $conn->query("SELECT a.id, a.title, a.content, a.likes, u.full_name as author FROM articles a JOIN users u ON a.creator_id = u.id $where_clause ORDER BY a.id DESC LIMIT $limit OFFSET $offset");
} elseif ($page === 'challenges') {
  $filter_cat = isset($_GET['filter_cat']) ? $conn->real_escape_string($_GET['filter_cat']) : '';

  $where_clause = " WHERE 1=1";
  if ($filter_cat)
    $where_clause .= " AND category = '$filter_cat'";

  $total_rows = $conn->query("SELECT COUNT(*) as total FROM challenges $where_clause")->fetch_assoc()['total'];
  $data['challenges_list'] = $conn->query("SELECT * FROM challenges $where_clause ORDER BY category, id DESC LIMIT $limit OFFSET $offset");

  if (isset($_GET['edit_challenge_id'])) {
    $data['edit_challenge'] = $conn->query("SELECT * FROM challenges WHERE id = " . (int) $_GET['edit_challenge_id'])->fetch_assoc();
  }
} elseif ($page === 'volunteering') {
  $search_title = isset($_GET['search_title']) ? $conn->real_escape_string($_GET['search_title']) : '';
  $search_loc = isset($_GET['search_loc']) ? $conn->real_escape_string($_GET['search_loc']) : '';

  $where_clause = " WHERE 1=1";
  if ($search_title)
    $where_clause .= " AND title LIKE '%$search_title%'";
  if ($search_loc)
    $where_clause .= " AND location LIKE '%$search_loc%'";

  $total_rows = $conn->query("SELECT COUNT(*) as total FROM volunteering $where_clause")->fetch_assoc()['total'];
  $data['volunteering_list'] = $conn->query("SELECT * FROM volunteering $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");

  if (isset($_GET['edit_volunteering_id'])) {
    $data['edit_volunteering'] = $conn->query("SELECT * FROM volunteering WHERE id = " . (int) $_GET['edit_volunteering_id'])->fetch_assoc();
  }
}

$total_pages = max(1, ceil($total_rows / $limit));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - SafeSpace</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <?php include 'includes/admin_styles.php'; ?>
  <style>
    /* Play Store Style Rating Chart */
    .rating-card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    .rating-container {
      display: flex;
      align-items: center;
      gap: 30px;
      margin-top: 15px;
    }
    .rating-overview {
      text-align: center;
      min-width: 120px;
    }
    .rating-overview h2 {
      font-size: 3.5rem;
      margin: 0;
      color: #333;
      line-height: 1;
      font-weight: 700;
    }
    .rating-stars {
      color: #01875f;
      font-size: 1.2rem;
      letter-spacing: 2px;
      margin: 5px 0;
    }
    .rating-total {
      font-size: 0.85rem;
      color: #888;
    }
    .rating-bars {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .rating-bar-row {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
      color: #555;
      font-weight: 600;
    }
    .rating-bar-row .star-label {
      width: 10px;
      text-align: right;
    }
    .rating-progress {
      flex-grow: 1;
      height: 12px;
      background: #e8eaed;
      border-radius: 10px;
      overflow: hidden;
    }
    .rating-progress-fill {
      height: 100%;
      background: #01875f;
      border-radius: 10px;
      transition: width 1s ease-in-out;
    }
  </style>
</head>

<body>

  <?php include 'includes/admin_sidebar.php'; ?>

  <div class="main-content">
    <?php if ($message): ?>
      <div class="alert">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <?php if ($page === 'dashboard'): ?>
      <div class="header-row">
        <h1>Overview</h1>
        <p>Welcome back, Admin</p>
      </div>
      <div class="grid-cards">
        <div class="stat-card">
          <p>Total Users</p>
          <h3 class="animated-counter" data-target="<?php echo $data['total_users']; ?>">0</h3>
        </div>
        <div class="stat-card">
          <p>Total Articles</p>
          <h3 class="animated-counter" data-target="<?php echo $data['total_articles']; ?>">0</h3>
        </div>
        <div class="stat-card">
          <p>Total Challenges</p>
          <h3 class="animated-counter" data-target="<?php echo $data['total_challenges']; ?>">0</h3>
        </div>
      </div>
      
      <div class="chart-box">
        <h2>Challenges Distribution Per Category</h2>
        <canvas id="categoryChart"></canvas>
      </div>

      <div class="rating-card">
        <h2>User Satisfaction</h2>
        <div class="rating-container">
          
          <div class="rating-overview">
            <h2><?php echo number_format($average_rating, 1); ?></h2>
            <div class="rating-stars">
              <?php 
                $full_stars = round($average_rating);
                for($s=1; $s<=5; $s++) {
                  echo $s <= $full_stars ? '★' : '☆';
                }
              ?>
            </div>
            <div class="rating-total"><?php echo number_format($total_ratings); ?> ratings</div>
          </div>

          <div class="rating-bars">
            <?php for($i = 5; $i >= 1; $i--): 
                $count = isset($rating_data[$i]) ? $rating_data[$i] : 0;
                $percent = $total_ratings > 0 ? ($count / $total_ratings) * 100 : 0;
            ?>
            <div class="rating-bar-row">
              <span class="star-label"><?php echo $i; ?></span>
              <div class="rating-progress">
                <div class="rating-progress-fill" style="width: <?php echo $percent; ?>%;"></div>
              </div>
            </div>
            <?php endfor; ?>
          </div>

        </div>
      </div>
      <br>

      <h2>Pending Creator Requests</h2><br>
      <?php if ($total_rows > 0): ?>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>File</th>
                <th>Description</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $data['pending_requests']->fetch_assoc()): ?>
                <tr>
                  <td><strong>
                      <?php echo htmlspecialchars($row['full_name']); ?>
                    </strong></td>
                  <td>
                    <?php echo htmlspecialchars($row['email']); ?>
                  </td>
                  <td><code><?php echo htmlspecialchars($row['file_name']); ?></code></td>
                  <td>
                    <div class="text-truncate-cell">
                      <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                    </div>
                  </td>
                  <td>
                    <form method="POST">
                      <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>"><input type="hidden"
                        name="req_user_id" value="<?php echo $row['user_id']; ?>">
                      <button type="submit" name="action" value="approve_creator" class="btn btn-success"
                        style="width:100%; margin-bottom:4px;">Approve</button>
                      <button type="submit" name="action" value="reject_creator" class="btn btn-danger"
                        style="width:100%;">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p style="margin-bottom: 20px;">No pending creator requests found.</p>
      <?php endif; ?>

    <?php elseif ($page === 'users'): ?>
      <div class="header-row">
        <h1>Manage Users</h1>
      </div>
      <form method="GET" class="filter-bar">
        <input type="hidden" name="page" value="users">
        <input type="text" name="search_name" placeholder="Search by name..."
          value="<?php echo isset($_GET['search_name']) ? htmlspecialchars($_GET['search_name']) : ''; ?>">
        <select name="filter_role">
          <option value="">All Roles</option>
          <option value="admin" <?php echo (isset($_GET['filter_role']) && $_GET['filter_role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
          <option value="creator" <?php echo (isset($_GET['filter_role']) && $_GET['filter_role'] == 'creator') ? 'selected' : ''; ?>>Creator</option>
          <option value="user" <?php echo (isset($_GET['filter_role']) && $_GET['filter_role'] == 'user') ? 'selected' : ''; ?>>User</option>
        </select>
        <button type="submit" class="btn btn-primary" style="margin:0;">Apply Filter</button>
        <a href="?page=users" class="btn btn-warning" style="margin:0;">Clear</a>
      </form>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Points</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total_rows > 0):
              while ($row = $data['users_list']->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars($row['full_name']); ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($row['email']); ?>
                  </td>
                  <td><strong>
                      <?php echo strtoupper($row['role']); ?>
                    </strong></td>
                  <td>
                    <?php echo $row['points']; ?>
                  </td>
                  <td>
                    <?php if ($row['role'] !== 'admin'): ?>
                      <form method="POST" onsubmit="return confirm('Delete user?');"><input type="hidden" name="delete_user_id"
                          value="<?php echo $row['id']; ?>"><button type="submit" name="action" value="delete_user"
                          class="btn btn-danger">Delete</button></form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; else:
              echo "<tr><td colspan='5'>No users matched criteria.</td></tr>"; endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($page === 'articles'): ?>
      <div class="header-row">
        <h1>Manage Articles</h1>
      </div>
      <form method="GET" class="filter-bar">
        <input type="hidden" name="page" value="articles">
        <input type="text" name="search_title" placeholder="Search title..."
          value="<?php echo isset($_GET['search_title']) ? htmlspecialchars($_GET['search_title']) : ''; ?>">
        <button type="submit" class="btn btn-primary" style="margin:0;">Apply Filter</button>
        <a href="?page=articles" class="btn btn-warning" style="margin:0;">Clear</a>
      </form>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th style="width:25%;">Title</th>
              <th style="width:15%;">Author</th>
              <th style="width:45%;">Content</th>
              <th style="width:7%;">Likes</th>
              <th style="width:8%;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total_rows > 0):
              while ($row = $data['articles_list']->fetch_assoc()): ?>
                <tr>
                  <td><strong>
                      <?php echo htmlspecialchars($row['title']); ?>
                    </strong></td>
                  <td>
                    <?php echo htmlspecialchars($row['author']); ?>
                  </td>
                  <td>
                    <div class="text-truncate-cell">
                      <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                    </div>
                  </td>
                  <td>
                    <?php echo $row['likes']; ?>
                  </td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Delete article?');"><input type="hidden"
                        name="delete_article_id" value="<?php echo $row['id']; ?>"><button type="submit" name="action"
                        value="delete_article" class="btn btn-danger">Remove</button></form>
                  </td>
                </tr>
              <?php endwhile; else:
              echo "<tr><td colspan='5'>No articles found.</td></tr>"; endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($page === 'challenges'): ?>
      <div class="header-row">
        <h1>Manage Challenges</h1>
      </div>
      <div class="admin-form">
        <?php $is_edit = isset($data['edit_challenge']); ?>
        <h3>
          <?php echo $is_edit ? "Edit Challenge" : "Create New Challenge"; ?>
        </h3>
        <form method="POST">
          <?php if ($is_edit): ?><input type="hidden" name="challenge_id"
              value="<?php echo $data['edit_challenge']['id']; ?>"><?php endif; ?>
          <label>Category:</label>
          <select name="category" onchange="checkCustomCategory(this.value)" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($is_edit && $data['edit_challenge']['category'] == $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
            <option value="__custom__">+ Add a Completely New Category</option>
          </select>
          <div id="custom_category_wrapper"
            style="display:none; background:#f0efff; padding:15px; border-radius:8px; margin-bottom:15px;">
            <label>Type New Category Name:</label><input type="text" name="custom_category" id="custom_category_input">
          </div>
          <label>Description:</label><textarea name="description" rows="3"
            required><?php echo $is_edit ? htmlspecialchars($data['edit_challenge']['description']) : ''; ?></textarea>
          <button type="submit" name="action" value="<?php echo $is_edit ? 'edit_challenge' : 'add_challenge'; ?>"
            class="btn btn-primary">Save Challenge</button>
          <?php if ($is_edit): ?><a href="?page=challenges" class="btn btn-warning">Cancel</a>
          <?php endif; ?>
        </form>
      </div>

      <form method="GET" class="filter-bar">
        <input type="hidden" name="page" value="challenges">
        <select name="filter_cat">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['filter_cat']) && $_GET['filter_cat'] == $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary" style="margin:0;">Apply Filter</button>
        <a href="?page=challenges" class="btn btn-warning" style="margin:0;">Clear</a>
      </form>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th style="width:8%;">ID</th>
              <th style="width:25%;">Category</th>
              <th style="width:52%;">Description</th>
              <th style="width:15%;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total_rows > 0):
              while ($row = $data['challenges_list']->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php echo $row['id']; ?>
                  </td>
                  <td><span
                      style="background:#e8e6ff; color:var(--purple-main); padding:4px 8px; border-radius:6px; font-size:0.85rem; font-weight:600;">
                      <?php echo htmlspecialchars($row['category']); ?>
                    </span></td>
                  <td>
                    <?php echo htmlspecialchars($row['description']); ?>
                  </td>
                  <td>
                    <a href="?page=challenges&edit_challenge_id=<?php echo $row['id']; ?>" class="btn btn-warning"
                      style="padding:4px 8px; font-size:0.8rem;">Edit</a>
                    <form method="POST" style="display:inline;"><input type="hidden" name="delete_challenge_id"
                        value="<?php echo $row['id']; ?>"><button type="submit" name="action" value="delete_challenge"
                        class="btn btn-danger" style="padding:4px 8px; font-size:0.8rem;">Delete</button></form>
                  </td>
                </tr>
              <?php endwhile; else:
              echo "<tr><td colspan='4'>No matches found.</td></tr>"; endif; ?>
          </tbody>
        </table>
      </div>

    <?php elseif ($page === 'volunteering'): ?>
      <div class="header-row">
        <h1>Manage Volunteering</h1>
      </div>
      <div class="admin-form">
        <?php $is_edit = isset($data['edit_volunteering']); ?>
        <h3>
          <?php echo $is_edit ? "Edit Opportunity" : "Post Opportunity"; ?>
        </h3>
        <form method="POST">
          <?php if ($is_edit): ?><input type="hidden" name="volunteering_id"
              value="<?php echo $data['edit_volunteering']['id']; ?>"><?php endif; ?>
          <label>Title:</label><input type="text" name="title" required
            value="<?php echo $is_edit ? htmlspecialchars($data['edit_volunteering']['title']) : ''; ?>">
          <label>Location:</label><input type="text" name="location" required
            value="<?php echo $is_edit ? htmlspecialchars($data['edit_volunteering']['location']) : ''; ?>">
          <label>Link:</label><input type="url" name="external_link" required
            value="<?php echo $is_edit ? htmlspecialchars($data['edit_volunteering']['external_link']) : ''; ?>">
          <label>Description:</label><textarea name="description" rows="3"
            required><?php echo $is_edit ? htmlspecialchars($data['edit_volunteering']['description']) : ''; ?></textarea>
          <button type="submit" name="action" value="<?php echo $is_edit ? 'edit_volunteering' : 'add_volunteering'; ?>"
            class="btn btn-primary">Publish</button>
        </form>
      </div>

      <form method="GET" class="filter-bar">
        <input type="hidden" name="page" value="volunteering">
        <input type="text" name="search_title" placeholder="Search title..."
          value="<?php echo isset($_GET['search_title']) ? htmlspecialchars($_GET['search_title']) : ''; ?>">
        <input type="text" name="search_loc" placeholder="Search location..."
          value="<?php echo isset($_GET['search_loc']) ? htmlspecialchars($_GET['search_loc']) : ''; ?>">
        <button type="submit" class="btn btn-primary" style="margin:0;">Filter</button>
      </form>

      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Location</th>
              <th>Description</th>
              <th>Link</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($total_rows > 0):
              while ($row = $data['volunteering_list']->fetch_assoc()): ?>
                <tr>
                  <td><strong>
                      <?php echo htmlspecialchars($row['title']); ?>
                    </strong></td>
                  <td>
                    <?php echo htmlspecialchars($row['location']); ?>
                  </td>
                  <td>
                    <div class="text-truncate-cell">
                      <?php echo nl2br(htmlspecialchars($row['description'])); ?>
                    </div>
                  </td>
                  <td><a href="<?php echo htmlspecialchars($row['external_link']); ?>" target="_blank" class="btn btn-primary"
                      style="font-size:0.75rem; padding:4px 8px;">Link</a></td>
                  <td>
                    <a href="?page=volunteering&edit_volunteering_id=<?php echo $row['id']; ?>" class="btn btn-warning"
                      style="padding:4px 6px; font-size:0.8rem;">Edit</a>
                    <form method="POST" style="display:inline;"><input type="hidden" name="delete_volunteering_id"
                        value="<?php echo $row['id']; ?>"><button type="submit" name="action" value="delete_volunteering"
                        class="btn btn-danger" style="padding:4px 6px; font-size:0.8rem;">Delete</button></form>
                  </td>
                </tr>
              <?php endwhile; else:
              echo "<tr><td colspan='5'>No entries found.</td></tr>"; endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if ($total_rows > 0 || $page === 'dashboard'): ?>
      <?php if ($total_rows > 0): ?>
        <div class="pagination-container">
          <div class="pagination-info">
            Showing
            <?php echo $offset + 1; ?> to
            <?php echo min($total_rows, $offset + $limit); ?> of
            <?php echo $total_rows; ?> entries
          </div>
          <div class="pagination-buttons">
            <a href="<?php echo get_page_url($current_p - 1); ?>"
              class="pagination-btn <?php echo ($current_p <= 1) ? 'disabled' : ''; ?>">« Prev</a>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <a href="<?php echo get_page_url($i); ?>"
                class="pagination-btn <?php echo ($current_p == $i) ? 'active' : ''; ?>">
                <?php echo $i; ?>
              </a>
            <?php endfor; ?>

            <a href="<?php echo get_page_url($current_p + 1); ?>"
              class="pagination-btn <?php echo ($current_p >= $total_pages) ? 'disabled' : ''; ?>">Next »</a>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </div>

  <script>
    function checkCustomCategory(val) {
      const wrapper = document.getElementById('custom_category_wrapper');
      const input = document.getElementById('custom_category_input');
      if (val === '__custom__') { wrapper.style.display = 'block'; input.required = true; input.focus(); }
      else { wrapper.style.display = 'none'; input.required = false; }
    }

    document.addEventListener("DOMContentLoaded", () => {
      // Counter system
      document.querySelectorAll('.animated-counter').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'), 10) || 0;
        let current = 0;
        if (target === 0) return;
        const timer = setInterval(() => {
          current += Math.ceil(target / 30);
          if (current >= target) { counter.innerText = target; clearInterval(timer); }
          else { counter.innerText = current; }
        }, 20);
      });

      // Chart script
      <?php if ($page === 'dashboard'): ?>
        new Chart(document.getElementById('categoryChart').getContext('2d'), {
          type: 'bar',
          data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
              data: <?php echo json_encode($chart_counts); ?>,
              backgroundColor: 'rgba(108, 92, 231, 0.25)', borderColor: 'rgba(108, 92, 231, 1)', borderWidth: 2, borderRadius: 6
            }]
          },
          options: { responsive: true, plugins: { legend: { display: false } } }
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>