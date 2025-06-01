<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/vendor/autoload.php";

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->SMTPDebug = 2; // Enable verbose debug output (can be set to 0 or 1 for less verbosity)
$mail->SMTPAuth = true;
$mail->Host = 'smtp.gmail.com';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->Username = 'freshmarket2002@gmail.com';
$mail->Password = 'moqj wwom syvu uzkz';

$mail->isHtml(true); // Set email format to HTML
$mail->setFrom('freshmarket2002@gmail.com', 'Fresh Mart');

return $mail; // Ensure you return the PHPMailer instance
