<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = $_POST["email"];

$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

$mysqli = require __DIR__ . "/database.php";

$sql = "UPDATE users
        SET reset_token_hash = ?,
            reset_token_expires_at = ?
        WHERE email = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sss", $token_hash, $expiry, $email);
$stmt->execute();

if ($mysqli->affected_rows) {
    $mail = require __DIR__ . "/mailer.php";

    // Debugging
    if (!($mail instanceof PHPMailer)) {
        echo "Mailer instance not created properly.";
        var_dump($mail);
        exit;
    }

    // Disable SMTP debugging
    $mail->SMTPDebug = 0; // Set debug level to 0 (no output)

    $mail->setFrom("freshmarket2002@gmail.com", "No Reply");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";
    $mail->Body = <<<END
    Click <a href="http://localhost/Freshmart/reset-password.php?token=$token">here</a> 
    to reset your password.
    END;

    try {
        $mail->send();
        echo "Reset email has been sent. check your email";
    } catch (Exception $e) {
        echo "Reset email could not be sent. Mailer error: {$mail->ErrorInfo}";
    }
} else {
    echo "Failed to update the user record.";
}
