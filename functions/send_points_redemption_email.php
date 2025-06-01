<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function send_points_redemption_email($email, $customer_name, $points_redeemed, $amount_saved, $remaining_points, $order_number) {
    try {
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
        $mail->addAddress($email, $customer_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Points Redeemed - Order #' . $order_number;

        // Build the email content
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #4CAF50; padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>Points Redeemed Successfully</h1>
            </div>

            <div style='padding: 20px; background: white;'>
                <p>Dear {$customer_name},</p>
                
                <p>Your rewards points have been successfully redeemed for your order #{$order_number}.</p>

                <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 8px;'>
                    <h3 style='color: #4CAF50; margin-top: 0;'>Redemption Details:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0;'>Points Redeemed:</td>
                            <td style='text-align: right; font-weight: bold;'>" . number_format($points_redeemed) . " points</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'>Amount Saved:</td>
                            <td style='text-align: right; font-weight: bold; color: #4CAF50;'>$" . number_format($amount_saved, 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0;'>Remaining Points Balance:</td>
                            <td style='text-align: right; font-weight: bold;'>" . number_format($remaining_points) . " points</td>
                        </tr>
                    </table>
                </div>

                <div style='background-color: #fff3e0; padding: 15px; margin: 20px 0; border-radius: 8px;'>
                    <p style='margin: 0; color: #e65100;'>
                        <strong>Note:</strong> You can view your updated points balance and redemption history in your account dashboard.
                    </p>
                </div>

                <p>Thank you for shopping with Fresh Mart!</p>
            </div>

            <div style='background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 14px; color: #666;'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>";

        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $message));

        return $mail->send();
    } catch (Exception $e) {
        error_log("Points redemption email error: " . $e->getMessage());
        return false;
    }
} 