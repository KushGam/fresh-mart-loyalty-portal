<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../connection.php';

function send_admin_notification_email($subject, $body_html) {
    try {
        global $con;
        $mail = new PHPMailer(true);

        // Server settings for Gmail
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

        // Fetch all admin emails from the database
        $query = "SELECT email, user_name FROM users WHERE role_as = 1";
        $result = mysqli_query($con, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                if (!empty($row['email'])) {
                    $mail->addAddress($row['email'], $row['user_name']);
                }
            }
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body_html;
        $mail->AltBody = strip_tags($body_html);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Admin email error: " . $e->getMessage());
        return false;
    }
} 