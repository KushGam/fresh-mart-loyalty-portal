<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

function send_order_confirmation($email, $order_number, $order_items, $subtotal, $shipping, $delivery_time, $customer_name, $rewards_discount = 0, $points_info = [], $applied_offers = []) {
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
        $mail->SMTPDebug = 0; // Disable debug output

        // Recipients
        $mail->setFrom('freshmarket2002@gmail.com', 'Fresh Mart');
        $mail->addAddress($email, $customer_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - Order #' . $order_number;

        // Build the email content
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h1 style='color: #4CAF50; text-align: center;'>Thank you for your order!</h1>
            <p>Dear {$customer_name},</p>
            <p>Your order #{$order_number} has been received and is being processed.</p>

            <div style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                <h2 style='margin-top: 0;'>Order Summary</h2>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <th style='text-align: left; padding: 8px;'>Item</th>
                        <th style='text-align: center; padding: 8px;'>Quantity</th>
                        <th style='text-align: right; padding: 8px;'>Price</th>
                    </tr>";

        foreach ($order_items as $item) {
            $message .= "
                    <tr>
                        <td style='padding: 8px;'>{$item['product_name']}</td>
                        <td style='text-align: center; padding: 8px;'>{$item['quantity']}</td>
                        <td style='text-align: right; padding: 8px;'>\${$item['price']}</td>
                    </tr>";
        }

        $message .= "
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'>Subtotal:</td>
                        <td style='text-align: right; padding: 8px;'>\$" . number_format($subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'>Shipping Fee:</td>
                        <td style='text-align: right; padding: 8px;'>" . ($shipping > 0 ? '$' . number_format($shipping, 2) : '<span style="color: #4CAF50;">FREE</span>') . "</td>
                    </tr>";

        // Calculate total after all discounts
        $total_after_discounts = $subtotal + $shipping;

        // Add Applied Offers Section
        if (!empty($applied_offers)) {
            $message .= "
                    <tr>
                        <td colspan='3' style='padding: 8px; color: #4CAF50;'><strong>Applied Offers</strong></td>
                    </tr>";
            foreach ($applied_offers as $offer) {
                if (isset($offer['title']) && isset($offer['discount'])) {
                $message .= "
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'>{$offer['title']}:</td>
                        <td style='text-align: right; padding: 8px; color: #4CAF50;'>-\$" . number_format($offer['discount'], 2) . "</td>
                    </tr>";
                    $total_after_discounts -= $offer['discount'];
                }
            }
        }

        // Add Promotions Section
        if (isset($applied_offers['promotions']) && !empty($applied_offers['promotions'])) {
            $message .= "
                    <tr>
                        <td colspan='3' style='padding: 8px; color: #4CAF50;'><strong>Applied Promotions</strong></td>
                    </tr>";
            foreach ($applied_offers['promotions'] as $promotion) {
                if (isset($promotion['name']) && isset($promotion['discount'])) {
                    $message .= "
                        <tr>
                            <td colspan='2' style='text-align: right; padding: 8px;'>{$promotion['name']}:</td>
                            <td style='text-align: right; padding: 8px; color: #4CAF50;'>-\$" . number_format($promotion['discount'], 2) . "</td>
                        </tr>";
                    $total_after_discounts -= $promotion['discount'];
            }
        }
        }

        // Add Rewards Discount
        if ($rewards_discount > 0) {
            $message .= "
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'>Rewards Discount:</td>
                        <td style='text-align: right; padding: 8px; color: #4CAF50;'>-\$" . number_format($rewards_discount, 2) . "</td>
                    </tr>";
            $total_after_discounts -= $rewards_discount;
        }

        // Ensure total is not negative
        $total_after_discounts = max(0, $total_after_discounts);

        $message .= "
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px; border-top: 2px solid #ddd;'><strong>Total:</strong></td>
                        <td style='text-align: right; padding: 8px; border-top: 2px solid #ddd;'><strong>\$" . number_format($total_after_discounts, 2) . "</strong></td>
                    </tr>
                </table>";

        // Add points information
        if (!empty($points_info)) {
            $message .= "
                <div style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;'>
                    <h3 style='color: #4CAF50; margin-bottom: 15px;'>Points Earned</h3>
                    <table style='width: 100%;'>
                        <tr>
                            <td style='padding: 4px;'>Base Points:</td>
                            <td style='text-align: right; padding: 4px;'>{$points_info['base_points']} points</td>
                        </tr>";
            
            if (isset($points_info['multiplier']) && $points_info['multiplier'] > 1) {
                $message .= "
                        <tr>
                            <td style='padding: 4px;'>Points Multiplier Breakdown:</td>
                            <td style='text-align: right; padding: 4px;'>";
                
                // Add tier multiplier if exists
                if (isset($points_info['tier_name']) && isset($points_info['tier_multiplier']) && $points_info['tier_multiplier'] > 1) {
                    $message .= "• {$points_info['tier_name']} Tier: {$points_info['tier_multiplier']}x<br>";
                }
                
                // Add offer multiplier if exists
                if (isset($points_info['offer_multiplier']) && $points_info['offer_multiplier'] > 1) {
                    $message .= "• Offer: {$points_info['offer_multiplier']}x<br>";
                }
                
                $message .= "<strong>Total: {$points_info['multiplier']}x</strong></td>
                        </tr>";
            }
            
            $message .= "
                        <tr style='color: #4CAF50; font-weight: bold;'>
                            <td style='padding: 4px;'>Total Points:</td>
                            <td style='text-align: right; padding: 4px;'>{$points_info['total_points']} points</td>
                        </tr>
                    </table>
                </div>";
        }

        // Add Points Earned Section
        if (isset($points_earned) && $points_earned > 0) {
            $base_points = floor($total_after_discounts);
            $message .= "
                    <tr>
                        <td colspan='3' style='padding: 8px; color: #4CAF50;'><strong>Points Earned</strong></td>
                    </tr>
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'>Base Points:</td>
                        <td style='text-align: right; padding: 8px;'>{$base_points}</td>
                    </tr>";

            // Add multiplier breakdown if total multiplier is greater than 1
            if (isset($points_multiplier) && $points_multiplier > 1) {
                // Show tier multiplier if greater than 1
                if (isset($tier_multiplier) && $tier_multiplier > 1) {
                    $message .= "
                        <tr>
                            <td colspan='2' style='text-align: right; padding: 8px;'>Tier Multiplier ({$tier_name}):</td>
                            <td style='text-align: right; padding: 8px;'>{$tier_multiplier}x</td>
                        </tr>";
                }
                
                // Show offer multiplier if greater than 1
                if (isset($offer_multiplier) && $offer_multiplier > 1) {
                    $message .= "
                        <tr>
                            <td colspan='2' style='text-align: right; padding: 8px;'>Special Offer Multiplier:</td>
                            <td style='text-align: right; padding: 8px;'>{$offer_multiplier}x</td>
                        </tr>";
                }
                
                $message .= "
                    <tr>
                        <td colspan='2' style='text-align: right; padding: 8px;'><strong>Total Points Earned:</strong></td>
                        <td style='text-align: right; padding: 8px; color: #4CAF50;'><strong>{$points_earned}</strong></td>
                    </tr>";
            }
        }

        $message .= "
                <div style='margin-top: 20px;'>
                    <p><strong>Estimated Delivery Date:</strong> " . date('F j, Y', strtotime($delivery_time)) . "</p>
            </div>

                <div style='margin-top: 30px; padding: 20px; background: #4CAF50; color: white; text-align: center; border-radius: 5px;'>
                    <h3 style='margin: 0;'>Need help? Contact our support team:</h3>
                </div>
            </div>
        </div>";

        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</tr>'], "\n", $message));

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
} 