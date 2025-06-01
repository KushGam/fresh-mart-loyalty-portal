<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function generatePasswordResetToken($user_id) {
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Set expiration time (24 hours from now)
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store token in database
    global $con;
    $query = "UPDATE users SET 
              reset_token_hash = ?, 
              reset_token_expires_at = ? 
              WHERE id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ssi", $token, $expires, $user_id);
    $stmt->execute();
    
    return $token;
}

function send_account_creation_email($email, $username, $temporary_password, $user_id) {
    try {
        // Generate password reset token
        $token = generatePasswordResetToken($user_id);
        
        // Create the password change URL with the correct path
        $reset_url = "http://" . $_SERVER['HTTP_HOST'] . "/Freshmart/change-password.php?token=" . $token;
        
        $mail = new PHPMailer(true);

        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'freshmarket2002@gmail.com';
        $mail->Password = 'moqj wwom syvu uzkz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Disable debug output

        // Recipients
        $mail->setFrom('freshmarket2002@gmail.com', 'Fresh Mart');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Fresh Mart - Your Account Has Been Created';

        // Build the email content
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
                .warning { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Fresh Mart!</h1>
                </div>
                <div class='content'>
                    <p>Dear {$username},</p>
                    <p>Your account has been successfully created. For security reasons, you need to change your password on your first login.</p>
                    <p>Here are your temporary login credentials:</p>
                    <ul>
                        <li><strong>Username:</strong> {$username}</li>
                        <li><strong>Temporary Password:</strong> {$temporary_password}</li>
                    </ul>
                    <p class='warning'>Please change your password immediately after logging in for the first time.</p>
                    <p>You can change your password by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='{$reset_url}' class='button'>Change Password</a>
                    </p>
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                    <p>Best regards,<br>The Fresh Mart Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $message;
        $mail->AltBody = "Welcome to Fresh Mart!\n\nYour account has been created. Please log in with your temporary credentials and change your password immediately.\n\nUsername: {$username}\nTemporary Password: {$temporary_password}\n\nTo change your password, visit: {$reset_url}\n\nThis link will expire in 24 hours.\n\nBest regards,\nThe Fresh Mart Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error sending account creation email: " . $e->getMessage());
        return false;
    }
}
?> 