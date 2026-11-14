<?php
// Active page highlighting logic helper
function is_active($current_page, $expected_page) {
    return $current_page === $expected_page ? 'active' : '';
}
?>
<div class="sidebar">
    <h2>SafeSpace Admin</h2>
    <a href="?page=dashboard" class="<?php echo is_active($page, 'dashboard'); ?>">Overview</a>
    <a href="?page=users" class="<?php echo is_active($page, 'users'); ?>">Manage Users</a>
    <a href="?page=articles" class="<?php echo is_active($page, 'articles'); ?>">Manage Articles</a>
    <a href="?page=challenges" class="<?php echo is_active($page, 'challenges'); ?>">Manage Challenges</a>
    <a href="?page=volunteering" class="<?php echo is_active($page, 'volunteering'); ?>">Manage Volunteering</a>
    <br><hr><br>
    <a href="login.php">Log Out</a>
</div>