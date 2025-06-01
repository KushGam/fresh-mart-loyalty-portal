<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function send_signup_email($email, $user_name) {
    try {
        $mail = new PHPMailer(true);

        // SMTP settings (same as send_order_email.php)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'freshmarket2002@gmail.com';
        $mail->Password = 'moqj wwom syvu uzkz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0;

        // Recipients
        $mail->setFrom('freshmarket2002@gmail.com', 'Fresh Mart');
        $mail->addAddress($email, $user_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Fresh Mart!';
        $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>"
            . "<h1 style='color: #4CAF50; text-align: center;'>Welcome to Fresh Mart!</h1>"
            . "<p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p>"
            . "<p>Thank you for signing up! We're excited to have you as part of our community. Start shopping and enjoy exclusive offers, reward points, and more!</p>"
            . "<p style='margin-top: 30px;'>Best regards,<br>Fresh Mart Team</p>"
            . "<div style='margin-top: 30px; padding: 20px; background: #4CAF50; color: white; text-align: center; border-radius: 5px;'>"
            . "<h3 style='margin: 0;'>Need help? Contact our support team!</h3>"
            . "</div></div>";
        $mail->AltBody = "Welcome to Fresh Mart! Dear $user_name, Thank you for signing up! We're excited to have you as part of our community. Start shopping and enjoy exclusive offers, reward points, and more! - Fresh Mart Team";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Signup email error: " . $e->getMessage());
        return false;
    }
} 