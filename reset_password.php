<?php
include 'db_connect.php';

$error_message = '';
$success_message = '';
$is_valid_token = false;
$user_id = null;

// 1. Check if the token is in the URL
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // 2. Check the database for this token AND make sure it hasn't expired
    $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_token_expires_at > NOW()";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $is_valid_token = true;
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
    } else {
        $error_message = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $error_message = "No reset token provided.";
}

// 3. Handle the form submission when they type a new password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_POST['user_id'];

    if (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
        $is_valid_token = true; // Keep form visible
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
        $is_valid_token = true; // Keep form visible
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password and clear the reset token so it can't be reused
        $update_sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_token_expires_at = NULL WHERE id = '$user_id'";
        
        if ($conn->query($update_sql) === TRUE) {
            $success_message = "Your password has been successfully reset! You can now <a href='login.php' style='color: #6c5ce7; font-weight: bold;'>Log In</a>.";
            $is_valid_token = false; // Hide the form
        } else {
            $error_message = "Error updating password. Please try again.";
            $is_valid_token = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Password - SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --purple-main: #6c5ce7; --text-dark: #2d3436; --text-light: #636e72; --bg-purple-light: #f5f3ff; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, var(--bg-purple-light) 0%, #fff 100%); color: var(--text-dark); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .auth-container { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        .logo-img { height: 70px; margin-bottom: 10px; }
        .auth-container h2 { font-size: 1.5rem; color: var(--purple-main); margin-bottom: 10px; }
        .auth-container p { color: var(--text-light); margin-bottom: 25px; font-size: 0.95rem; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #dfe6e9; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 1rem; transition: 0.3s; }
        .form-group input:focus { outline: none; border-color: var(--purple-main); box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1); }
        .btn-submit { width: 100%; background-color: var(--purple-main); color: #fff; font-weight: 600; padding: 12px; border: none; border-radius: 10px; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: #5a4bcf; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3); }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: left; }
        .error { background-color: #ffeaa7; color: #d63031; border: 1px solid #fdcb6e; }
        .success { background-color: #55efc4; color: #00b894; border: 1px solid #00cec9; }
    </style>
</head>
<body>

    <div class="auth-container">
        <a href="index.php"><img src="images/logo.png" alt="SafeSpace Logo" class="logo-img"></a>
        <h2>Create New Password</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
            <?php if (!$is_valid_token && empty($success_message)): ?>
                <a href="forgot_password.php" class="btn-submit" style="display:inline-block; text-decoration:none;">Request New Link</a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($is_valid_token): ?>
            <p>Please enter your new password below.</p>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" method="POST">
                
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Type password again">
                </div>

                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>