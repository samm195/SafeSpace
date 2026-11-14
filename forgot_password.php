<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

include 'db_connect.php';

$error_message = '';
$success_message = '';
//$dev_reset_link = ''; // For testing purposes before we add SMTP

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));

    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } else {
        $sql = "SELECT id, full_name FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // 1. Generate a secure, random token
            $token = bin2hex(random_bytes(32));
            
            // 2. Set the expiration time to 1 hour from now
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // 3. Save the token and expiration to the database
            $update_sql = "UPDATE users SET reset_token = '$token', reset_token_expires_at = '$expires_at' WHERE email = '$email'";
            
            if ($conn->query($update_sql) === TRUE) {
                // 4. Create the magical reset link
                $reset_link = "http://localhost/safespace/reset_password.php?token=" . $token;

                // ==========================================
                // PHPMailer SMTP Logic
                // ==========================================
                $mail = new PHPMailer(true);

                try {
                    $mail->CharSet = 'UTF-8';

                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'safespace5149@gmail.com'; // Put your Gmail here
                    $mail->Password   = 'tnyr kzyh vguc ykom'; // Put your App Password here
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom('no-reply@safespace.com', 'SafeSpace Support');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'SafeSpace - Reset Your Password';
                    $mail->Body    = "
                        <h2>Password Reset Request</h2>
                        <p>We received a request to reset your SafeSpace password.</p>
                        <p>Click the link below to create a new password:</p>
                        <a href='{$reset_link}' style='padding: 10px 15px; background-color: #6c5ce7; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                        <p><br>If you did not request this, please ignore this email.</p>
                    ";

                    $mail->send();
                    $success_message = "Success! An email with instructions to reset your password has been sent to your inbox.";
                } catch (Exception $e) {
                    $error_message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            } else {
                $error_message = "Database error. Please try again later.";
            }
        } else {
            $error_message = "We couldn't find an account associated with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SafeSpace</title>
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
        .dev-box { margin-top: 20px; padding: 15px; background: #e0f7fa; border: 1px dashed #00acc1; border-radius: 8px; font-size: 0.85rem; text-align: left;}
        .auth-footer { margin-top: 25px; font-size: 0.9rem; color: var(--text-light); }
        .auth-footer a { color: var(--purple-main); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

    <div class="auth-container">
        <a href="index.php"><img src="images/logo.png" alt="SafeSpace Logo" class="logo-img"></a>
        <h2>Reset Password</h2>
        <p>Enter your email address to receive a secure reset link.</p>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
            <?php if (!empty($dev_reset_link)): ?>
                <div class="dev-box"><?php echo $dev_reset_link; ?></div>
            <?php endif; ?>
        <?php else: ?>
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your registered email">
                </div>
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            Remembered your password? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>