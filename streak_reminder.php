<?php
// streak_reminder.php
require 'db_connect.php';

// Adjust this path if your PHPMailer is located somewhere else
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// The SQL query we perfected
$sql = "
    SELECT 
        u.id, 
        u.full_name, 
        u.email, 
        u.streak, 
        u.freezes,
        MAX(da.assigned_date) as last_completed_date,
        (DATEDIFF(CURDATE(), MAX(da.assigned_date)) - 1) as missed_days
    FROM users u
    JOIN daily_assignments da ON u.id = da.user_id
    WHERE u.streak > 0 
      AND da.status = 'completed'
    GROUP BY u.id
    HAVING 
        missed_days <= u.freezes 
        AND last_completed_date < CURDATE()
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->CharSet = 'UTF-8';

        // --- YOUR SMTP SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Or your SMTP provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'safespace5149@gmail.com';
        $mail->Password   = 'tnyr kzyh vguc ykom';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@safespace.com', 'SafeSpace');
        $mail->isHTML(true);

        $emails_sent = 0;

        // Loop through the users who need a reminder
        while ($row = $result->fetch_assoc()) {
            $mail->clearAddresses();
            $mail->addAddress($row['email'], $row['full_name']);

            $firstName = explode(' ', trim($row['full_name']))[0];
            $mail->Subject = "🚨 Save your " . $row['streak'] . "-day streak, " . $firstName . "!";
            
            // Calculate remaining freezes AFTER today's potential loss
            $freezes_left_after_today = $row['freezes'] - $row['missed_days'] - 1;

            if ($freezes_left_after_today >= 0) {
                $freeze_warning = "<p style='color: #636e72;'>If you miss today, you will use a freeze. You have <strong>" . ($row['freezes'] - $row['missed_days']) . " freezes</strong> available right now.</p>";
            } else {
                $freeze_warning = "<p style='color: #ff4757; font-weight: bold;'>Warning: You are out of Freezes! If you miss today, your streak will drop to 0.</p>";
            }

            // Email Template
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 30px; border: 1px solid #dfe6e9; border-radius: 15px; background: #f8f9fa;'>
                    <h2 style='color: #6c5ce7; text-align: center;'>SafeSpace Reminder</h2>
                    <p style='color: #2d3436;'>Hi " . htmlspecialchars($firstName) . ",</p>
                    <p style='color: #2d3436;'>You've built an awesome <strong>" . $row['streak'] . "-day streak</strong>, but you haven't completed a quest today!</p>
                    " . $freeze_warning . "
                    <div style='text-align: center; margin: 40px 0;'>
                        <a href='http://localhost/safespace/login.php' style='background-color: #6c5ce7; color: white; padding: 14px 30px; text-decoration: none; border-radius: 30px; font-weight: bold; display: inline-block;'>Complete a Quest</a>
                    </div>
                </div>
            ";

            $mail->send();
            $emails_sent++;
            echo "Reminder sent to: " . $row['email'] . "<br>";
        }
        
        echo "<br>Total reminders sent: " . $emails_sent;

    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "No reminders needed right now. Everyone is either on track or their streaks are already lost!";
}
?>