<?php
// Include the database connection
include 'db_connect.php';

// Variables to hold messages
$error_message = '';
$success_message = '';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and grab the input data
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if the email is already registered
        $check_email_sql = "SELECT id FROM users WHERE email = '$email'";
        $result = $conn->query($check_email_sql);

        if ($result->num_rows > 0) {
            $error_message = "An account with this email already exists.";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database
            // Note: role, title, points, streak, and freezes will use the default values set in our SQL table
            $insert_sql = "INSERT INTO users (full_name, email, password) VALUES ('$full_name', '$email', '$hashed_password')";

            if ($conn->query($insert_sql) === TRUE) {
                $success_message = "Registration successful! You can now <a href='login.php' style='color: #6c5ce7; font-weight: bold;'>Log In</a>.";
            } else {
                $error_message = "Error: Something went wrong. " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SafeSpace</title>
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
            max-width: 450px;
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

        .success {
            background-color: #55efc4;
            color: #00b894;
            border: 1px solid #00cec9;
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
        <h2>Join SafeSpace</h2>
        <p>Start your journey to better habits today.</p>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php else: ?>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="John Doe">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Type password again">
                </div>

                <button type="submit" class="btn-submit">Sign Up</button>
            </form>

        <?php endif; ?>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

</body>
</html>