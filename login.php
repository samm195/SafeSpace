<?php
// Start the session at the very top!
session_start();

// Include the database connection
include 'db_connect.php';

$error_message = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        // Look for the user in the database
        $sql = "SELECT id, full_name, password, role FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify the hashed password
            if (password_verify($password, $user['password'])) {
                // Password is correct! Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on their role
                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                }else {
                    header("Location: dashboard.php");
                }
                exit(); 
            } else {
                $error_message = "Incorrect password. Please try again.";
            }
        } else {
            $error_message = "No account found with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - SafeSpace</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --purple-main: #6c5ce7;
            --pink-main: #fd79a8;
            --text-dark: #2d3436;
            --text-light: #636e72;
            --bg-purple-light: #f5f3ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-purple-light) 0%, #fff 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .auth-container {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logo-img {
            height: 70px;
            margin-bottom: 10px;
        }

        .auth-container h2 {
            font-size: 1.8rem;
            color: var(--purple-main);
            margin-bottom: 5px;
        }

        .auth-container p {
            color: var(--text-light);
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--purple-main);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }

        .forgot-password {
            display: block;
            text-align: right;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
            text-decoration: none;
        }

        .forgot-password:hover {
            color: var(--purple-main);
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            background-color: var(--purple-main);
            color: #fff;
            font-weight: 600;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #5a4bcf;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3);
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .error {
            background-color: #ffeaa7;
            color: #d63031;
            border: 1px solid #fdcb6e;
        }

        .auth-footer {
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .auth-footer a {
            color: var(--purple-main);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <a href="index.php">
            <img src="images/logo.png" alt="SafeSpace Logo" class="logo-img">
        </a>
        <h2>Welcome Back</h2>
        <p>Log in to protect your streak.</p>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="john@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-submit">Log In</button>
        </form>

        <div class="auth-footer">
            Don't have an account yet? <a href="register.php">Sign Up</a>
        </div>
    </div>

</body>
</html>