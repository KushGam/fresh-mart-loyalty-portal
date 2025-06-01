<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Check if 'to' request is present
if (isset($_REQUEST['to'])) {
    $to = $_REQUEST['to'];
    $subject = $_REQUEST['subject'];
    $content = $_REQUEST['message'];
    send_otp($to, $subject, $content);
}

function send_otp($to, $subject, $content) {
    try {
        // Load mail configuration
        $config = require_once 'config/mail_config.php';
        
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_encryption'];
        $mail->Port = $config['smtp_port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Freshmart Login Verification';
        
        // Improved HTML email template
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #2C5F2D;">Freshmart</h1>
            </div>
            <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px;">
                <h2 style="color: #333; margin-bottom: 20px;">Verification Code</h2>
                <p style="color: #666; margin-bottom: 20px;">Your one-time verification code is:</p>
                <div style="background-color: #fff; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;">
                    <span style="font-size: 32px; letter-spacing: 5px; font-weight: bold; color: #2C5F2D;">' . $content . '</span>
                </div>
                <p style="color: #666; font-size: 14px;">This code is valid for one-time use only and will expire in 5 minutes.</p>
                <p style="color: #666; font-size: 14px;">If you did not request this code, please ignore this email.</p>
            </div>
            <div style="text-align: center; margin-top: 30px; color: #999; font-size: 12px;">
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>';
        
        // Plain text version
        $mail->AltBody = "Your Freshmart verification code is: $content\n\nThis code is valid for one-time use only and will expire in 5 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
