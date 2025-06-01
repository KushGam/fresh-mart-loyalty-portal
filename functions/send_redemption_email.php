<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Picqer\Barcode\BarcodeGeneratorPNG;

require_once __DIR__ . '/../vendor/autoload.php';

function send_redemption_email($email, $redemption_code, $amount) {
    try {
        error_log("Attempting to send redemption email to: " . $email);
        $mail = new PHPMailer(true);

        // Server settings for Mailjet
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'freshmarket2002@gmail.com';
        $mail->Password = 'moqj wwom syvu uzkz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Debug settings
        $mail->SMTPDebug = 3;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer debug: " . $str);
        };

        // Basic settings
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        
        // From and Reply-To
        $mail->setFrom('freshmarket2002@gmail.com', 'Fresh Mart');
        $mail->addReplyTo('freshmarket2002@gmail.com', 'Fresh Mart Support');
        
        // Add recipient
        error_log("Adding recipient: " . $email);
        $mail->addAddress($email);
        
        // Email content
        $mail->Subject = 'Your Fresh Mart Store Redemption Code';
        
        // Generate barcode
        error_log("Generating barcode for code: " . $redemption_code);
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($redemption_code, $generator::TYPE_CODE_128);
        
        // Save barcode to file with absolute path
        $barcode_dir = dirname(__DIR__) . '/barcodes/';
        if (!file_exists($barcode_dir)) {
            mkdir($barcode_dir, 0777, true);
            error_log("Created barcodes directory at: " . $barcode_dir);
        }
        
        $barcode_filename = $barcode_dir . $redemption_code . '.png';
        error_log("Saving barcode to: " . $barcode_filename);
        
        if (file_put_contents($barcode_filename, $barcode) === false) {
            throw new Exception("Failed to save barcode file");
        }
        
        if (!file_exists($barcode_filename)) {
            throw new Exception("Barcode file was not created");
        }
        
        // Attach barcode to email
        error_log("Attaching barcode from: " . $barcode_filename);
        if (!$mail->addAttachment($barcode_filename, 'redemption_barcode.png')) {
            throw new Exception("Failed to attach barcode file");
        }

        // Format redemption code
        $formatted_code = chunk_split($redemption_code, 4, ' ');
        
        // Create message
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #4CAF50;'>Your Store Redemption Code</h2>
            
            <p style='font-size: 16px;'><strong>Important:</strong> Please save this email for your records.</p>
            
            <div style='background-color: #f9f9f9; padding: 20px; margin: 20px 0; text-align: center; border: 2px solid #4CAF50; border-radius: 8px;'>
                <h3 style='margin-top: 0; color: #2E7D32;'>Redemption Amount</h3>
                <div style='font-size: 32px; color: #4CAF50; margin: 10px 0; font-weight: bold;'>$" . number_format($amount, 2) . "</div>
                
                <h3 style='color: #2E7D32;'>Your Redemption Code:</h3>
                <div style='font-size: 24px; font-weight: bold; letter-spacing: 2px; background: #fff; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;'>
                    {$formatted_code}
                </div>

                <p style='margin: 20px 0; color: #666;'>Your barcode is attached to this email.</p>
            </div>

            <div style='margin: 20px 0; background: #f5f5f5; padding: 20px; border-radius: 8px;'>
                <h3 style='color: #2E7D32;'>How to Redeem:</h3>
                <ol style='color: #333; font-size: 16px; line-height: 1.6;'>
                    <li>Visit any Fresh Mart store</li>
                    <li>Show the barcode to the cashier for scanning (see attachment)</li>
                    <li>Or show the code number for manual entry</li>
                    <li>The cashier will apply your $" . number_format($amount, 2) . " discount</li>
                </ol>
            </div>

            <div style='background-color: #fff3e0; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ff9800;'>
                <p style='margin: 0; color: #e65100;'><strong>Important:</strong> This code is valid for 30 days from today.</p>
            </div>
        </div>";

        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));
        
        error_log("Attempting to send email...");
        if(!$mail->send()) {
            error_log("Email sending failed. Error: " . $mail->ErrorInfo);
            throw new Exception("Failed to send email: " . $mail->ErrorInfo);
        }
        
        // Clean up - delete the barcode file
        if (file_exists($barcode_filename)) {
            unlink($barcode_filename);
            error_log("Deleted barcode file: " . $barcode_filename);
        }
        
        error_log("Email sent successfully to: " . $email);
        return true;
    } catch (Exception $e) {
        error_log("Error in send_redemption_email: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        // Clean up in case of error
        if (isset($barcode_filename) && file_exists($barcode_filename)) {
            unlink($barcode_filename);
        }
        return false;
    }
} 