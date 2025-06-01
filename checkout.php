<?php
session_start();
include("connection.php");
include("functions.php");
require_once("functions/send_order_email.php");
require_once 'functions/send_admin_notification_email.php';

$user_data = check_login($con);

// Get active offers for the user
function getActiveOffers($con, $user_id) {
    $query = "SELECT 
        po.id,
        po.title,
        po.description,
        po.minimum_purchase,
        po.discount_amount,
        po.discount_type,
        po.points_multiplier,
        po.usage_limit,
        uo.status as offer_status,
        (SELECT COUNT(*) FROM offer_usage 
         WHERE offer_id = po.id AND user_id = ?) as times_used
    FROM personalized_offers po
    LEFT JOIN user_offers uo ON po.id = uo.offer_id AND uo.user_id = ?
    WHERE po.is_active = 1
    AND po.start_date <= CURRENT_DATE
    AND po.end_date >= CURRENT_DATE
    AND (uo.status = 'active' OR uo.status IS NULL)
    HAVING (times_used < usage_limit OR usage_limit IS NULL OR usage_limit = 0)";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Calculate total with discounts
function calculateTotalWithDiscounts($subtotal, $offers) {
    $final_total = $subtotal;
    $applied_offers = [];
    $points_multipliers = []; // Track all multipliers separately
    
    while($offer = $offers->fetch_assoc()) {
        // Only apply offers that have been redeemed (status = 'active')
        if ($offer['offer_status'] === 'active' && $subtotal >= $offer['minimum_purchase']) {
            if ($offer['discount_type'] == 'percentage') {
                $discount = $subtotal * ($offer['discount_amount'] / 100);
            } else {
                $discount = $offer['discount_amount'];
            }
            $final_total -= $discount;
            
            // Track applied offer
            $applied_offers[] = [
                'title' => $offer['title'],
                'discount' => $discount,
                'offer_id' => $offer['id'],
                'points_multiplier' => floatval($offer['points_multiplier'])
            ];
            
            // Add multiplier to array if it's greater than 1
            if (floatval($offer['points_multiplier']) > 1) {
                $points_multipliers[] = floatval($offer['points_multiplier']);
            }
        }
    }
    
    // Calculate combined multiplier - multiply all multipliers together
    $combined_multiplier = empty($points_multipliers) ? 1 : array_reduce($points_multipliers, function($carry, $item) {
        return $carry * $item;
    }, 1);
    
    return [
        'total' => max(0, $final_total),
        'applied_offers' => $applied_offers,
        'points_multiplier' => $combined_multiplier,
        'offer_multipliers' => $points_multipliers // Pass individual multipliers for display
    ];
}

// Calculate cart total
$subtotal = 0;
$shipping = 5.00; // Default shipping cost

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// Make shipping free for orders over $100
if ($subtotal >= 100) {
    $shipping = 0.00;
}

// Get redemption amount from session
$redeem_amount = isset($_SESSION['checkout_redeem_amount']) ? $_SESSION['checkout_redeem_amount'] : 0;

// Calculate promotion discounts
$promotion_discount = 0;
$removed_promotions = [];

if(isset($_SESSION['redeemed_promotions']) && !empty($_SESSION['redeemed_promotions'])) {
    foreach($_SESSION['redeemed_promotions'] as $key => $promo_id) {
        // Get promotion details and check usage
        $promo_query = "SELECT p.*, 
                       (SELECT COUNT(*) FROM order_promotions op 
                        JOIN orders o ON op.order_id = o.id 
                        WHERE op.promotion_id = p.id AND o.user_id = ?) as user_usage_count
                       FROM promotions p 
                       WHERE p.id = ? 
                       AND p.is_active = 1";
        
        $stmt = $con->prepare($promo_query);
        $stmt->bind_param("ii", $user_data['id'], $promo_id);
        $stmt->execute();
        $promotion = $stmt->get_result()->fetch_assoc();
        
        if($promotion) {
            $can_use = true;
            
            // Check usage limits
            if($promotion['usage_limit'] !== NULL && $promotion['usage_limit'] > 0) {
                if($promotion['user_usage_count'] >= $promotion['usage_limit']) {
                    $can_use = false;
                    $removed_promotions[] = $promo_id;
                    unset($_SESSION['redeemed_promotions'][$key]);
                    continue;
                }
            }
            
            // Check minimum purchase and calculate discount if can use
            if($can_use && $subtotal >= $promotion['minimum_purchase']) {
                if($promotion['discount_type'] == 'percentage') {
                    $discount = $subtotal * ($promotion['discount_amount'] / 100);
                } else {
                    $discount = $promotion['discount_amount'];
                }
                $promotion_discount += $discount;
            } else {
                // Remove promotion if minimum purchase not met
                $removed_promotions[] = $promo_id;
                unset($_SESSION['redeemed_promotions'][$key]);
            }
        } else {
            // Remove invalid promotion
            $removed_promotions[] = $promo_id;
            unset($_SESSION['redeemed_promotions'][$key]);
        }
    }
    
    // Reindex array after removing invalid promotions
    $_SESSION['redeemed_promotions'] = array_values($_SESSION['redeemed_promotions']);
    
    // Show message if promotions were removed
    if(!empty($removed_promotions)) {
        $_SESSION['warning'] = "Some promotions were removed because they were invalid or usage limit was reached.";
    }
}

// Get active offers and calculate discounts
$active_offers = getActiveOffers($con, $user_data['id']);
$calculation = calculateTotalWithDiscounts($subtotal, $active_offers);
$discounted_total = $calculation['total'];
$applied_offers = $calculation['applied_offers'];
$points_multiplier = $calculation['points_multiplier'];

// Calculate final total by adding shipping and subtracting all discounts
$final_total = $discounted_total + $shipping - $redeem_amount - $promotion_discount;

// Ensure final total is not negative
$final_total = max(0, $final_total);

// Get customer details
$customer_details = null;
$query = "SELECT * FROM customer_details WHERE user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $customer_details = $result->fetch_assoc();
}

// Get user's birthday and today's date in MM-DD format
$user_birthday = null;
if ($customer_details && !empty($customer_details['birthday'])) {
    $user_birthday = date('m-d', strtotime($customer_details['birthday']));
}
$today = date('m-d');

// Get user's email from the users table
$email = $user_data['email'];

// Calculate points to be earned
$points_query = "SELECT ls.points_per_dollar, lt.points_multiplier as tier_multiplier
                FROM loyalty_settings ls
                CROSS JOIN customer_details cd 
                INNER JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
                WHERE cd.user_id = ?
                LIMIT 1";
$stmt = $con->prepare($points_query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$points_result = $stmt->get_result();
$points_settings = $points_result->fetch_assoc();
$points_per_dollar = floatval($points_settings['points_per_dollar'] ?? 1.0);
$tier_multiplier = floatval($points_settings['tier_multiplier'] ?? 1.0);

// Ensure points_multiplier is treated as float
$points_multiplier = floatval($points_multiplier);

// Update the points calculation section
// Calculate final multiplier by combining tier and offer multipliers
$offer_multiplier = $calculation['points_multiplier'];
$final_multiplier = round($tier_multiplier * $offer_multiplier, 2);

// Calculate base points and apply final multiplier
$base_points = floor($final_total * $points_per_dollar);
$points_to_earn = floor($base_points * $final_multiplier);

// Get user's active offers
$offers_query = "SELECT po.*, uo.status
                FROM personalized_offers po
                LEFT JOIN user_offers uo ON po.id = uo.offer_id AND uo.user_id = ?
                LEFT JOIN customer_details cd ON cd.user_id = ?
                LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
                WHERE po.is_active = 1
                AND po.start_date <= CURRENT_DATE
                AND po.end_date >= CURRENT_DATE
                AND po.minimum_purchase <= ?
                AND (uo.status IS NULL OR uo.status = 'active')
                AND (po.loyalty_tier IS NULL OR po.loyalty_tier = '' OR po.loyalty_tier = lt.tier_name)
                AND (
                    po.usage_limit IS NULL 
                    OR (
                        SELECT COUNT(*) FROM offer_usage 
                        WHERE offer_id = po.id 
                        AND user_id = ?
                    ) < po.usage_limit
                )";
$offers_stmt = $con->prepare($offers_query);
$offers_stmt->bind_param("iidi", $user_data['id'], $user_data['id'], $final_total, $user_data['id']);
$offers_stmt->execute();
$available_offers = $offers_stmt->get_result();

// Display available offers for this purchase
if ($available_offers->num_rows > 0) {
    // Reset the result pointer
    $available_offers->data_seek(0);
    
    // First check if there are any unredeemed offers
    $has_unredeemed_offers = false;
    while ($offer = $available_offers->fetch_assoc()) {
        // Filter out birthday offers if today is not the user's birthday
        if ($offer['offer_type'] === 'birthday' && $user_birthday !== $today) {
            continue;
        }
        if ($offer['status'] === NULL) {  // NULL status means offer hasn't been redeemed
            $has_unredeemed_offers = true;
            break;
        }
    }
}

// Get customer's current tier information
$tier_query = "SELECT lt.tier_name, lt.points_multiplier 
               FROM customer_details cd
               JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
               WHERE cd.user_id = ?";
$stmt = $con->prepare($tier_query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$tier_info = $stmt->get_result()->fetch_assoc();

$current_tier_name = $tier_info['tier_name'] ?? 'Bronze';
$current_tier_multiplier = $tier_info['points_multiplier'] ?? 1.00;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    $message = '';
    
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Get payment method
        $payment_method = $_POST['payment_method'] ?? '';
        $card_number = '';
        $expiry_date = '';
        $cvv = '';
        
        // Handle payment method
        if (strpos($payment_method, 'saved_card_') === 0) {
            // Using a saved card
            $card_id = substr($payment_method, 11); // Remove 'saved_card_' prefix
            $get_card = "SELECT * FROM saved_cards WHERE id = ? AND user_id = ?";
            $stmt = $con->prepare($get_card);
            $stmt->bind_param("ii", $card_id, $user_data['id']);
            $stmt->execute();
            $card = $stmt->get_result()->fetch_assoc();
            
            if (!$card) {
                throw new Exception("Invalid saved card selected");
            }
            
            $card_number = $card['card_number'];
            $expiry_date = $card['expiry_date'];
            // CVV still required for security
            $cvv = $_POST['cvv'];
            
            if (empty($cvv) || !preg_match("/^[0-9]{3,4}$/", $cvv)) {
                throw new Exception("Please enter a valid CVV");
            }
        } else {
            // Using a new card
            $card_number = $_POST['card_number'] ?? '';
            $expiry_date = $_POST['expiry_date'] ?? '';
            $cvv = $_POST['new_card_cvv'] ?? '';
            
            // Validate card details
            if (empty($card_number) || !preg_match("/^[0-9]{16}$/", $card_number)) {
                throw new Exception("Please enter a valid card number");
            }
            if (empty($expiry_date) || !preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $expiry_date)) {
                throw new Exception("Please enter a valid expiry date");
            }
            if (empty($cvv) || !preg_match("/^[0-9]{3,4}$/", $cvv)) {
                throw new Exception("Please enter a valid CVV");
            }
            
            // Save card if requested
            if (isset($_POST['save_card']) && $_POST['save_card'] == '1') {
                $make_default = isset($_POST['make_default']) && $_POST['make_default'] == '1';
                
                // If making this card default, unset any existing default
                if ($make_default) {
                    $unset_default = "UPDATE saved_cards SET is_default = 0 WHERE user_id = ?";
                    $stmt = $con->prepare($unset_default);
                    $stmt->bind_param("i", $user_data['id']);
                    $stmt->execute();
                }
                
                // Save the new card
                $save_card = "INSERT INTO saved_cards (user_id, card_number, expiry_date, last_four_digits, is_default) 
                             VALUES (?, ?, ?, ?, ?)";
                $stmt = $con->prepare($save_card);
                $last_four = substr($card_number, -4);
                $stmt->bind_param("isssi", $user_data['id'], $card_number, $expiry_date, $last_four, $make_default);
                $stmt->execute();
            }
        }
        
    // Validate required delivery information only for new customers
    if (!$customer_details) {
        $required_fields = ['first_name', 'last_name', 'phone_number', 'address'];
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                $missing_fields[] = str_replace('_', ' ', ucfirst($field));
            }
        }
            // Phone number length validation
            if (isset($_POST['phone_number']) && strlen(preg_replace('/\D/', '', $_POST['phone_number'])) != 10) {
                $missing_fields[] = 'a valid 10-digit phone number';
            }
        if (!empty($missing_fields)) {
                $error_message = "Please provide the following: " . implode(", ", $missing_fields);
                $message = $error_message;
            $success = false;
        }
    }
    
    // If validation passed (or customer details exist), proceed with order processing
    if (empty($missing_fields)) {
        $success = false;
        
            // For new customers, get the POSTed values
            if (!$customer_details) {
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $phone_number = trim($_POST['phone_number']);
                $address = trim($_POST['address']);
            } else {
                // For existing customers, use their saved details
                $first_name = $customer_details['first_name'];
                $last_name = $customer_details['last_name'];
                $phone_number = $customer_details['phone_number'];
                $address = $customer_details['address'];
            }
            
            $email = isset($_POST['email']) ? trim($_POST['email']) : $user_data['email'];

            // Update user email if it has changed
            if($email !== $user_data['email']) {
                $update_user = "UPDATE users SET email = ? WHERE id = ?";
                $stmt = $con->prepare($update_user);
                $stmt->bind_param("si", $email, $user_data['id']);
                $stmt->execute();
            }

            // Update or insert customer details
            if($customer_details) {
                $query = "UPDATE customer_details SET first_name = ?, last_name = ?, phone_number = ?, address = ? WHERE user_id = ?";
            } else {
                $query = "INSERT INTO customer_details (first_name, last_name, phone_number, address, user_id) VALUES (?, ?, ?, ?, ?)";
            }
            
            $stmt = $con->prepare($query);
            $stmt->bind_param("ssssi", $first_name, $last_name, $phone_number, $address, $user_data['id']);
            $stmt->execute();

            // Create order
            $order_number = 'FM' . date('YmdHis') . rand(100, 999);
            $delivery_time = date('Y-m-d H:i:s', strtotime('+2 days')); // Delivery in 2 days

            // Get the first applied offer (since we currently only support one offer at a time)
            $offer_id = null;
            $offer_discount = 0.00;
            $offer_points_multiplier = 1.00;
            if (!empty($applied_offers)) {
                $first_offer = $applied_offers[0];
                $offer_id = $first_offer['offer_id'];
                $offer_discount = $first_offer['discount'];
                
                // Get the offer's points multiplier
                $offer_query = "SELECT points_multiplier FROM personalized_offers WHERE id = ?";
                $stmt = $con->prepare($offer_query);
                $stmt->bind_param("i", $offer_id);
                $stmt->execute();
                $offer_result = $stmt->get_result();
                if ($offer_data = $offer_result->fetch_assoc()) {
                    $offer_points_multiplier = floatval($offer_data['points_multiplier']);
                }
            }

            // Insert order into database with offer information
            $query = "INSERT INTO orders (user_id, order_number, total_amount, shipping_fee, delivery_time, rewards_discount, 
                      tier_name, tier_multiplier, offer_id, offer_discount, offer_points_multiplier) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($query);
            $stmt->bind_param("isddssddidi", $user_data['id'], $order_number, $final_total, $shipping, $delivery_time, 
                              $redeem_amount, $current_tier_name, $current_tier_multiplier, $offer_id, $offer_discount, $offer_points_multiplier);
            $stmt->execute();
            
            $order_id = $con->insert_id;

            // Record offer usage if any offers were applied
            if (!empty($applied_offers)) {
                $record_usage = "INSERT INTO offer_usage (user_id, offer_id, order_id) VALUES (?, ?, ?)";
                $usage_stmt = $con->prepare($record_usage);
                
                foreach ($applied_offers as $offer) {
                    $usage_stmt->bind_param("iii", $user_data['id'], $offer['offer_id'], $order_id);
                    $usage_stmt->execute();
                }
            }

            // Save order items
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $con->prepare($query);

            $order_items = array();
            foreach ($_SESSION['cart'] as $product_id => $item) {
                // Debug cart data
                error_log("Processing cart item - Product ID: " . $product_id);
                error_log("Cart item data: " . print_r($item, true));
                
                // Get the product ID from the array key
                $product_id = intval($product_id);
                
                if (!$product_id) {
                    throw new Exception("Invalid product ID in cart");
                }

                // Verify product exists in database
                $check_product = "SELECT id, product_name, price FROM products WHERE id = ?";
                $check_stmt = $con->prepare($check_product);
                $check_stmt->bind_param("i", $product_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows === 0) {
                    error_log("Product not found in database - ID: " . $product_id);
                    throw new Exception("Product ID {$product_id} not found in database");
                }
                
                $product = $result->fetch_assoc();
                error_log("Found product in database: " . print_r($product, true));
                
                $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                $stmt->execute();

                // Prepare items for email
                $order_items[] = array(
                    'product_name' => $product['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                );

                // Update product stock
                $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stock_stmt = $con->prepare($update_stock);
                $stock_stmt->bind_param("ii", $item['quantity'], $product_id);
                $stock_stmt->execute();
            }

            // Send confirmation email
            $customer_name = $first_name . ' ' . $last_name;

            // Prepare points info for email
            $points_info = [
                'base_points' => $base_points,
                'multiplier' => $final_multiplier,
                'total_points' => $points_to_earn,
                'tier_name' => $current_tier_name,
                'tier_multiplier' => $tier_multiplier,
                'offer_multiplier' => $offer_multiplier
            ];

            // Prepare offers and promotions info for email
            $email_offers = [];
            if (!empty($applied_offers)) {
                foreach ($applied_offers as $offer) {
                    if (isset($offer['title']) && isset($offer['discount'])) {
                        $email_offers[] = [
                            'title' => $offer['title'],
                            'discount' => $offer['discount']
                        ];
                    }
                }
            }

            // Add promotions to the offers array
            if (isset($_SESSION['redeemed_promotions']) && !empty($_SESSION['redeemed_promotions'])) {
                $email_offers['promotions'] = [];
                foreach ($_SESSION['redeemed_promotions'] as $promo_id) {
                    $promo_query = "SELECT title FROM promotions WHERE id = ?";
                    $stmt = $con->prepare($promo_query);
                    $stmt->bind_param("i", $promo_id);
                    $stmt->execute();
                    $promo = $stmt->get_result()->fetch_assoc();
                    if ($promo) {
                        $email_offers['promotions'][] = [
                            'name' => $promo['title'],
                            'discount' => $promotion_discount
                        ];
                    }
                }
            }

            if(send_order_confirmation($email, $order_number, $order_items, $subtotal, $shipping, $delivery_time, $customer_name, $redeem_amount, $points_info, $email_offers)) {
                // Record promotion usage and update usage count
                if(isset($_SESSION['redeemed_promotions']) && !empty($_SESSION['redeemed_promotions'])) {
                    foreach($_SESSION['redeemed_promotions'] as $promo_id) {
                        // Record the promotion usage with user_id
                        $record_usage = "INSERT INTO order_promotions (order_id, promotion_id, user_id, discount_amount) VALUES (?, ?, ?, ?)";
                        $stmt = $con->prepare($record_usage);
                        $stmt->bind_param("iiid", $order_id, $promo_id, $user_data['id'], $promotion_discount);
                        $stmt->execute();
                        
                        // Update the promotion total usage count
                        $update_usage = "UPDATE promotions SET total_usage_count = total_usage_count + 1 WHERE id = ?";
                        $stmt = $con->prepare($update_usage);
                        $stmt->bind_param("i", $promo_id);
                        $stmt->execute();
                    }
                }

                // If redeeming points
                if($redeem_amount > 0) {
                    error_log("Deducting points for redemption. Amount: $redeem_amount");
                    $points_to_deduct = $redeem_amount * 100; // $10 = 1000 points
                    
                    // Verify user has enough points
                    $verify_points = "SELECT points FROM rewards WHERE user_id = ? AND points >= ?";
                    $stmt = $con->prepare($verify_points);
                    $stmt->bind_param("ii", $user_data['id'], $points_to_deduct);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if($result->num_rows === 0) {
                        throw new Exception("Insufficient points for redemption");
                    }
                    
                    // Deduct points
                    $deduct_points = "UPDATE rewards SET points = points - ? WHERE user_id = ?";
                    $stmt = $con->prepare($deduct_points);
                    $stmt->bind_param("ii", $points_to_deduct, $user_data['id']);
                    if(!$stmt->execute()) {
                        throw new Exception("Failed to deduct points");
                    }
                    
                    error_log("Points deducted successfully: $points_to_deduct points");
                    
                    // Record the redemption
                    $record_redemption = "INSERT INTO reward_redemptions (user_id, order_id, points_redeemed, amount_saved) VALUES (?, ?, ?, ?)";
                    $stmt = $con->prepare($record_redemption);
                    $stmt->bind_param("iiid", $user_data['id'], $order_id, $points_to_deduct, $redeem_amount);
                    if(!$stmt->execute()) {
                        throw new Exception("Failed to record redemption");
                    }

                    // Get remaining points
                    $get_remaining = "SELECT points FROM rewards WHERE user_id = ?";
                    $stmt = $con->prepare($get_remaining);
                    $stmt->bind_param("i", $user_data['id']);
                    $stmt->execute();
                    $remaining_points = $stmt->get_result()->fetch_assoc()['points'];

                    // Send points redemption email
                    require_once 'functions/send_points_redemption_email.php';
                    send_points_redemption_email(
                        $email,
                        $customer_name,
                        $points_to_deduct,
                        $redeem_amount,
                        $remaining_points,
                        $order_number
                    );

                    error_log("Points redemption email sent successfully");
                    // Send admin notification
                    require_once 'functions/send_admin_notification_email.php';
                    $admin_subject = 'Points Redeemed (Online) - Order #' . $order_number;
                    $admin_body = "<h2>Points Redeemed (Online)</h2>"
                        . "<p><strong>Order Number:</strong> {$order_number}</p>"
                        . "<p><strong>Customer Name:</strong> {$customer_name}</p>"
                        . "<p><strong>Email:</strong> {$email}</p>"
                        . "<p><strong>Points Redeemed:</strong> " . number_format($points_to_deduct) . "</p>"
                        . "<p><strong>Amount Saved:</strong> $" . number_format($redeem_amount, 2) . "</p>"
                        . "<p><strong>Remaining Points:</strong> " . number_format($remaining_points) . "</p>";
                    send_admin_notification_email($admin_subject, $admin_body);
                }

                // Add new reward points for the purchase
                $points_to_add = floor($base_points * $final_multiplier);
                error_log("Adding points: $points_to_add");
                
                $update_points = "UPDATE rewards SET points = points + ? WHERE user_id = ?";
                $stmt = $con->prepare($update_points);
                $stmt->bind_param("ii", $points_to_add, $user_data['id']);
                if(!$stmt->execute()) {
                    throw new Exception("Failed to add reward points");
                }

                // Check and update loyalty tier
                check_and_update_loyalty_tier($con, $user_data['id']);

                // Clear session variables
                unset($_SESSION['redeem_amount']);
                unset($_SESSION['checkout_redeem_amount']);
                unset($_SESSION['redeemed_promotions']);
                $_SESSION['cart'] = array();
                
                // Set success flags for order confirmation
                $_SESSION['order_success'] = true;
                $_SESSION['order_number'] = $order_number;

                // Commit the transaction
                $con->commit();
                
                // Debug log
                error_log("Order processed successfully. Redirecting to order_confirmation.php");
                
                // Send admin notification email BEFORE redirect
                $admin_subject = 'New Order Placed - Order #' . $order_number;
                $admin_body = "<h2>New Order Placed</h2>"
                    . "<p><strong>Order Number:</strong> {$order_number}</p>"
                    . "<p><strong>Customer Name:</strong> {$customer_name}</p>"
                    . "<p><strong>Email:</strong> {$email}</p>"
                    . "<p><strong>Phone:</strong> {$phone_number}</p>"
                    . "<p><strong>Address:</strong> {$address}</p>"
                    . "<p><strong>Order Total:</strong> $" . number_format($final_total, 2) . "</p>"
                    . "<h3>Items:</h3><ul>";
                foreach ($order_items as $item) {
                    $admin_body .= "<li>{$item['product_name']} (Qty: {$item['quantity']}, Price: $" . number_format($item['price'], 2) . ")</li>";
                }
                $admin_body .= "</ul>";
                send_admin_notification_email($admin_subject, $admin_body);

                // Redirect to order confirmation
                header("Location: order_confirmation.php");
                exit();
            } else {
                throw new Exception("Error sending confirmation email");
            }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $con->rollback();
            $message = "Error processing your order: " . $e->getMessage();
            error_log("Checkout error: " . $e->getMessage());
            $success = false;
    }
}

// Handle offer redemption
if(isset($_POST['redeem_offer'])) {
    $offer_id = intval($_POST['offer_id']);
    
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Check if offer exists and is valid
        $check_offer = "SELECT * FROM personalized_offers 
                       WHERE id = ? AND is_active = 1 
                       AND start_date <= CURRENT_DATE 
                       AND end_date >= CURRENT_DATE";
        $stmt = $con->prepare($check_offer);
        $stmt->bind_param("i", $offer_id);
        $stmt->execute();
        $offer = $stmt->get_result()->fetch_assoc();
        
        if($offer) {
            // Insert into user_offers
            $insert = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                      VALUES (?, ?, 'active', NOW())";
            $stmt = $con->prepare($insert);
            $stmt->bind_param("ii", $user_data['id'], $offer_id);
            $stmt->execute();
            
            $con->commit();
            $_SESSION['success'] = "Offer applied successfully!";
        }
    } catch (Exception $e) {
        $con->rollback();
        $_SESSION['error'] = "Error applying offer: " . $e->getMessage();
    }
    
    header("Location: checkout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            display: flex;
            gap: 30px;
        }

        .checkout-form {
            flex: 2;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-summary {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .form-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .card-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
        }

        .submit-payment {
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .submit-payment:hover {
            background: #45a049;
        }

        .error-message {
            color: #dc3545;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #fce4e4;
        }

        .info-message {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .delivery-info-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .delivery-info-summary p {
            margin: 5px 0;
            color: #333;
        }

        .edit-info-link {
            display: inline-block;
            margin-top: 15px;
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .edit-info-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .card-details {
                grid-template-columns: 1fr;
            }
        }

        .rewards-discount {
            color: #4CAF50;
            font-weight: 600;
        }

        .cart-items {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .item-details {
            display: flex;
            flex-direction: column;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .item-quantity {
            font-size: 0.9em;
            color: #666;
        }

        .item-price {
            font-weight: 500;
            color: #333;
        }

        .summary-section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .summary-section h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1em;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .offer-row {
            color: #4CAF50;
        }

        .discount {
            color: #4CAF50;
            font-weight: 500;
        }

        .total-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-size: 1.2em;
            color: #333;
        }

        .points-earning {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .total-points {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            color: #4CAF50;
        }

        .info-message {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .offers-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .offers-section h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .offer-option {
            background: white;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .offer-option:last-child {
            margin-bottom: 0;
        }

        .redeem-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .redeem-btn:hover {
            background: #45a049;
            color: white;
            text-decoration: none;
        }

        .info-message {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
            text-align: center;
        }

        .offer-row {
            color: #4CAF50;
        }

        .discount {
            font-weight: 500;
        }

        .save-info-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .save-info-btn:hover {
            background: #45a049;
        }

        .edit-info-btn {
            background: #2196F3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .edit-info-btn:hover {
            background: #1976D2;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .cancel-btn:hover {
            background: #c82333;
        }

        .edit-delivery-form {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f8f9fa;
        }

        .promotions {
            margin: 15px 0;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .promotions h4 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 1em;
        }

        .promotion-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #666;
            font-size: 0.9em;
        }

        .total-discount {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #eee;
            color: #4CAF50;
            font-weight: bold;
        }

        .saved-cards-section {
            margin-bottom: 25px;
        }

        .saved-cards-section h4 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .saved-card-option {
            position: relative;
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .delete-card-btn {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px;
            display: none;
            transition: color 0.3s;
        }

        .delete-card-btn:hover {
            color: #c82333;
        }

        .saved-card-option:hover .delete-card-btn {
            display: block;
        }

        .saved-card-label {
            flex: 1;
            padding-right: 40px; /* Make space for delete button */
        }

        .card-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .card-info i {
            color: #666;
            font-size: 20px;
        }

        .card-number {
            font-weight: 500;
            color: #333;
        }

        .card-expiry {
            color: #666;
            font-size: 14px;
        }

        .default-badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: auto;
        }

        .new-card-label {
            color: #4CAF50;
        }

        .new-card-label i {
            margin-right: 8px;
        }

        /* Style for radio buttons */
        .saved-card-option input[type="radio"] {
            display: none;
        }

        .saved-card-option input[type="radio"]:checked + label {
            background: #f0f9f0;
            border-color: #4CAF50;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .card-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .default-badge {
                margin-left: 0;
                margin-top: 5px;
            }
        }

        .cvv-input-container {
            position: relative;
            max-width: 150px;
        }

        .cvv-input-container input {
            padding-right: 35px;
        }

        .cvv-tooltip {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: help;
        }

        .cvv-tooltip:hover {
            color: #4CAF50;
        }

        #saved-card-cvv {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        #saved-card-cvv label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        #saved-card-cvv input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        #saved-card-cvv input:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }

        .promotion-details {
            display: flex;
            flex-direction: column;
        }

        .promotion-name {
            font-weight: 500;
            color: #333;
        }

        .uses-remaining {
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
        }

        .promotion-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .promotion-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="checkout-container">
        <div class="checkout-form">
            <form method="POST" action="" onsubmit="return validatePaymentInfo();">
                <?php if(isset($message)): ?>
                    <div class="error-message"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if(!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if(!$customer_details): ?>
                <!-- Show form for new customers -->
                <div class="form-section">
                    <h3>Delivery Information</h3>
                    <p class="info-message">Please provide your delivery details. All fields are required.</p>
                    <div id="delivery-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" required 
                                       placeholder="Enter your first name"
                                       oninvalid="this.setCustomValidity('Please enter your first name')"
                                       oninput="this.setCustomValidity('')">
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" required 
                                       placeholder="Enter your last name"
                                       oninvalid="this.setCustomValidity('Please enter your last name')"
                                       oninput="this.setCustomValidity('')">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="birthday" required 
                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                   oninvalid="this.setCustomValidity('Please enter your date of birth')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phone_number" required 
                                   pattern="[0-9]{10,}"
                                   placeholder="Enter your phone number"
                                   oninvalid="this.setCustomValidity('Please enter a valid phone number')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                        <div class="form-group">
                            <label>Delivery Address *</label>
                            <input type="text" name="address" required 
                                   placeholder="Enter your complete delivery address"
                                   oninvalid="this.setCustomValidity('Please enter your delivery address')"
                                   oninput="this.setCustomValidity('')">
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Show summary for returning customers -->
                <div class="form-section">
                    <h3>Delivery Information</h3>
                    <div id="delivery-summary" class="delivery-info-summary">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer_details['first_name'] . ' ' . $customer_details['last_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer_details['phone_number']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($customer_details['address']); ?></p>
                        <button type="button" class="edit-info-btn" onclick="toggleDeliveryEdit()">Edit Delivery Information</button>
                    </div>
                    
                    <!-- Hidden edit form -->
                    <div id="edit-delivery-form" class="edit-delivery-form" style="display: none;">
                        <!-- Add hidden fields to store existing customer details -->
                        <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($customer_details['first_name']); ?>">
                        <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($customer_details['last_name']); ?>">
                        <input type="hidden" name="phone_number" value="<?php echo htmlspecialchars($customer_details['phone_number']); ?>">
                        <input type="hidden" name="address" value="<?php echo htmlspecialchars($customer_details['address']); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="edit_first_name" 
                                       value="<?php echo htmlspecialchars($customer_details['first_name']); ?>"
                                       placeholder="Enter your first name">
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="edit_last_name" 
                                       value="<?php echo htmlspecialchars($customer_details['last_name']); ?>"
                                       placeholder="Enter your last name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="edit_birthday" required 
                                   value="<?php echo $customer_details['birthday']; ?>"
                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="edit_phone_number" required 
                                   value="<?php echo htmlspecialchars($customer_details['phone_number']); ?>"
                                   pattern="[0-9]{10,}"
                                   placeholder="Enter your phone number">
                        </div>
                        <div class="form-group">
                            <label>Delivery Address *</label>
                            <input type="text" name="edit_address" required 
                                   value="<?php echo htmlspecialchars($customer_details['address']); ?>"
                                   placeholder="Enter your complete delivery address">
                        </div>
                        <div class="button-group">
                            <button type="button" class="save-info-btn" onclick="saveDeliveryInfo()">Save Information</button>
                            <button type="button" class="cancel-btn" onclick="toggleDeliveryEdit()">Cancel</button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-section payment-section">
                    <h3>Payment Information</h3>
                    
                    <?php
                    // Check for saved cards
                    $saved_cards_query = "SELECT * FROM saved_cards WHERE user_id = ? ORDER BY is_default DESC";
                    $stmt = $con->prepare($saved_cards_query);
                    $stmt->bind_param("i", $user_data['id']);
                    $stmt->execute();
                    $saved_cards = $stmt->get_result();
                    
                    if($saved_cards->num_rows > 0): ?>
                        <div class="saved-cards-section">
                            <h4>Saved Payment Methods</h4>
                            <?php while($card = $saved_cards->fetch_assoc()): ?>
                                <div class="saved-card-option" id="card-container-<?php echo $card['id']; ?>">
                                    <input type="radio" name="payment_method" value="saved_card_<?php echo $card['id']; ?>" 
                                           id="card_<?php echo $card['id']; ?>" <?php echo $card['is_default'] ? 'checked' : ''; ?>>
                                    <label for="card_<?php echo $card['id']; ?>" class="saved-card-label">
                                        <div class="card-info">
                                            <i class="fas fa-credit-card"></i>
                                            <span class="card-number">•••• <?php echo htmlspecialchars($card['last_four_digits']); ?></span>
                                            <span class="card-expiry">Expires: <?php echo htmlspecialchars($card['expiry_date']); ?></span>
                                            <?php if($card['is_default']): ?>
                                                <span class="default-badge">Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                    <button type="button" class="delete-card-btn" onclick="deleteCard(<?php echo $card['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endwhile; ?>
                            <div class="saved-card-option">
                                <input type="radio" name="payment_method" value="new_card" id="new_card">
                                <label for="new_card" class="new-card-label">
                                    <i class="fas fa-plus-circle"></i>
                                    Use a new card
                                </label>
                            </div>
                        </div>

                        <!-- CVV field for saved cards -->
                        <div id="saved-card-cvv" class="form-group">
                            <label>CVV *</label>
                            <div class="cvv-input-container">
                                <input type="text" name="cvv" pattern="[0-9]{3,4}" maxlength="4"
                                       placeholder="Enter CVV" required
                                       oninvalid="this.setCustomValidity('Please enter a valid CVV')"
                                       oninput="this.setCustomValidity('')">
                                <span class="cvv-tooltip" title="The 3 or 4 digit security code on the back of your card">
                                    <i class="fas fa-question-circle"></i>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- New card form -->
                    <div id="new-card-form" <?php echo ($saved_cards->num_rows > 0) ? 'style="display: none;"' : ''; ?>>
                        <?php if($saved_cards->num_rows === 0): ?>
                            <input type="hidden" name="payment_method" value="new_card">
                        <?php endif; ?>
                    <div class="form-group">
                        <label>Card Number *</label>
                            <input type="text" name="card_number" pattern="[0-9]{16}" maxlength="16"
                            placeholder="Enter your 16-digit card number"
                                   <?php echo ($saved_cards->num_rows === 0) ? 'required' : ''; ?>
                            oninvalid="this.setCustomValidity('Please enter a valid 16-digit card number')"
                            oninput="this.setCustomValidity('')">
                    </div>
                    <div class="card-details">
                        <div class="form-group">
                            <label>Expiry Date *</label>
                                <input type="text" name="expiry_date" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" 
                                       placeholder="MM/YY" maxlength="5"
                                       <?php echo ($saved_cards->num_rows === 0) ? 'required' : ''; ?>
                                oninvalid="this.setCustomValidity('Please enter a valid expiry date (MM/YY)')"
                                oninput="this.setCustomValidity('')">
                        </div>
                        <div class="form-group">
                            <label>CVV *</label>
                                <div class="cvv-input-container">
                                    <input type="text" name="new_card_cvv" pattern="[0-9]{3,4}" maxlength="4"
                                placeholder="CVV"
                                           <?php echo ($saved_cards->num_rows === 0) ? 'required' : ''; ?>
                                oninvalid="this.setCustomValidity('Please enter a valid CVV')"
                                oninput="this.setCustomValidity('')">
                                    <span class="cvv-tooltip" title="The 3 or 4 digit security code on the back of your card">
                                        <i class="fas fa-question-circle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="save-card-container">
                            <div class="save-card-option">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="save_card" id="save_card" value="1">
                                    <span class="checkmark"></span>
                                    Save this card for future purchases
                                    <span class="tooltip-icon" title="Your card will be securely saved. Only the last 4 digits will be visible.">
                                        <i class="fas fa-info-circle"></i>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="save-card-default" style="display: none;">
                                <label class="checkbox-container">
                                    <input type="checkbox" name="make_default" id="make_default" value="1">
                                    <span class="checkmark"></span>
                                    Make this my default payment method
                                    <span class="tooltip-icon" title="This card will be automatically selected for future purchases.">
                                        <i class="fas fa-info-circle"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="place_order" class="submit-payment">Place Order</button>
                </div>
            </form>
        </div>

        <div class="order-summary">
            <h3>Order Summary</h3>
            <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <div class="cart-items">
                    <h4>Items</h4>
                    <?php foreach($_SESSION['cart'] as $item): ?>
                        <div class="cart-item">
                            <div class="item-details">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                            </div>
                            <span class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-section">
                    <h4>Price Summary</h4>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php if ($subtotal < 100): ?>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>$<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <p class="info-message" style="color: #666; font-size: 0.9em;">Free shipping on orders over $100!</p>
                    <?php else: ?>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span style="color: #4CAF50;">FREE</span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($applied_offers)): ?>
                        <div class="offers-section">
                            <h4>Applied Offers</h4>
                            <?php foreach($applied_offers as $offer): ?>
                                <div class="summary-row offer-row">
                                    <span><?php echo htmlspecialchars($offer['title']); ?></span>
                                    <span class="discount">-$<?php echo number_format($offer['discount'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['redeemed_promotions']) && !empty($_SESSION['redeemed_promotions'])): ?>
                        <div class="promotions">
                            <h4>Applied Promotions</h4>
                            <?php
                            $total_discount = 0;
                            foreach($_SESSION['redeemed_promotions'] as $promo_id) {
                                $promo_query = "SELECT p.*, 
                                              (SELECT COUNT(*) FROM order_promotions op 
                                               WHERE op.promotion_id = p.id 
                                               AND op.user_id = ?) as user_usage_count
                                              FROM promotions p 
                                              WHERE p.id = ?";
                                $stmt = $con->prepare($promo_query);
                                $stmt->bind_param("ii", $user_data['id'], $promo_id);
                                $stmt->execute();
                                $promotion = $stmt->get_result()->fetch_assoc();
                                
                                if($promotion) {
                                    $discount = 0;
                                    if($promotion['discount_type'] == 'percentage') {
                                        $discount = $subtotal * ($promotion['discount_amount'] / 100);
                                    } else {
                                        $discount = $promotion['discount_amount'];
                                    }
                                    $total_discount += $discount;

                                    // Calculate remaining uses
                                    $remaining_uses = $promotion['usage_limit'] > 0 ? 
                                        $promotion['usage_limit'] - $promotion['user_usage_count'] : 
                                        'Unlimited';
                                    ?>
                                    <div class="promotion-item">
                                        <div class="promotion-details">
                                            <span class="promotion-name"><?php echo htmlspecialchars($promotion['title']); ?></span>
                                            <?php if($promotion['usage_limit'] > 0): ?>
                                                <span class="uses-remaining">(<?php echo $remaining_uses; ?> uses remaining)</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="discount">-$<?php echo number_format($discount, 2); ?></span>
                                    </div>
                                    <?php
                                }
                            }
                            if($total_discount > 0): ?>
                                <div class="total-discount">
                                    <span>Total Discount</span>
                                    <span>-$<?php echo number_format($total_discount, 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($redeem_amount > 0): ?>
                        <div class="summary-row rewards-row">
                            <span>Rewards Discount</span>
                            <span class="rewards-discount">-$<?php echo number_format($redeem_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Check for unredeemed offers
                    $available_offers->data_seek(0); // Reset pointer to start
                    $has_unredeemed = false;
                    while ($offer = $available_offers->fetch_assoc()) {
                        // Filter out birthday offers if today is not the user's birthday
                        if ($offer['offer_type'] === 'birthday' && $user_birthday !== $today) {
                            continue;
                        }
                        if ($offer['status'] === NULL) { // Unredeemed offer
                            if (!$has_unredeemed) {
                                echo '<div class="summary-section" style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
                                echo '<h4 style="color: #4CAF50; margin-bottom: 10px;">Available Offers</h4>';
                                $has_unredeemed = true;
                            }
                            ?>
                            <div class="offer-option" style="margin-bottom: 8px;">
                                <div>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($offer['title']); ?></span>
                                    <br>
                                    <span style="font-size: 0.9em; color: #666;">
                                        <?php if ($offer['discount_type'] == 'percentage'): ?>
                                            <?php echo $offer['discount_amount']; ?>% off
                                        <?php else: ?>
                                            $<?php echo number_format($offer['discount_amount'], 2); ?> off
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <a href="loyalty-dashboard.php" class="redeem-btn">Redeem Now</a>
                            </div>
                            <?php
                        }
                    }
                    if ($has_unredeemed) {
                        echo '</div>';
                    }
                    ?>

                    <div class="summary-row total-row">
                        <span><strong>Total</strong></span>
                        <span><strong>$<?php echo number_format($final_total, 2); ?></strong></span>
                    </div>

                    <div class="points-earning">
                        <h4>Points You'll Earn</h4>
                        <div class="summary-row">
                            <span>Base Points</span>
                            <span><?php echo number_format($base_points); ?> points</span>
                        </div>
                        <?php if($final_multiplier > 1): ?>
                            <div class="summary-row">
                                <span>Points Multiplier Breakdown:</span>
                                <span>
                                    <?php if($tier_multiplier > 1): ?>
                                        <div style="font-size: 0.9em; color: #666;">• <?php echo $current_tier_name; ?> Tier: <?php echo number_format($tier_multiplier, 2); ?>x</div>
                                    <?php endif; ?>
                                    <?php foreach($calculation['offer_multipliers'] as $multiplier): ?>
                                        <div style="font-size: 0.9em; color: #666;">• Offer: <?php echo number_format($multiplier, 2); ?>x</div>
                                    <?php endforeach; ?>
                                    <div style="margin-top: 5px; font-weight: 500;">Total: <?php echo number_format($final_multiplier, 2); ?>x</div>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total-points">
                            <span><strong>Total Points</strong></span>
                            <span><strong><?php echo number_format($points_to_earn); ?> points</strong></span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p>Your cart is empty</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
    function toggleDeliveryEdit() {
        const summaryDiv = document.getElementById('delivery-summary');
        const formDiv = document.getElementById('edit-delivery-form');
        
        if (summaryDiv.style.display !== 'none') {
            summaryDiv.style.display = 'none';
            formDiv.style.display = 'block';
        } else {
            summaryDiv.style.display = 'block';
            formDiv.style.display = 'none';
        }
    }

    function showDeliveryError(message) {
        const errorDiv = document.getElementById('delivery-error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
    }

    function clearDeliveryError() {
        const errorDiv = document.getElementById('delivery-error-message');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
        }
    }

    function saveDeliveryInfo() {
        // Get values from the edit form
        const firstName = document.querySelector('input[name="edit_first_name"]').value;
        const lastName = document.querySelector('input[name="edit_last_name"]').value;
        const birthday = document.querySelector('input[name="edit_birthday"]').value;
        const phoneNumber = document.querySelector('input[name="edit_phone_number"]').value;
        const address = document.querySelector('input[name="edit_address"]').value;

        // Validate required fields
        if (!firstName || !lastName || !birthday || !phoneNumber || !address) {
            showDeliveryError('Please fill in all required fields.');
            return;
        }
        // Validate phone number length
        if (!/^\d{10}$/.test(phoneNumber)) {
            showDeliveryError('Please enter a valid 10-digit phone number.');
            return;
        }
        clearDeliveryError();

        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('birthday', birthday);
        formData.append('phone_number', phoneNumber);
        formData.append('address', address);

        // Send AJAX request
        fetch('save_delivery_info.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show updated information
                location.reload();
            } else {
                alert(data.message || 'Error saving delivery information. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving delivery information. Please try again.');
        });
    }

    // Validate payment information before submission
    function validatePaymentInfo() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked') || 
                             document.querySelector('input[name="payment_method"][type="hidden"]');
        
        if (!paymentMethod) {
            alert('Please select a payment method.');
            return false;
        }

        try {
            if (paymentMethod.value.startsWith('saved_card_')) {
                // Validate CVV for saved card
                const savedCardCvv = document.querySelector('input[name="cvv"]');
                if (!savedCardCvv || !savedCardCvv.value.match(/^\d{3,4}$/)) {
                    alert('Please enter a valid CVV (3 or 4 digits).');
                    savedCardCvv && savedCardCvv.focus();
                    return false;
                }
            } else if (paymentMethod.value === 'new_card') {
                // Validate new card details
                const cardNumber = document.querySelector('input[name="card_number"]');
                const expiryDate = document.querySelector('input[name="expiry_date"]');
                const newCardCvv = document.querySelector('input[name="new_card_cvv"]');

                if (!cardNumber || !cardNumber.value.match(/^\d{16}$/)) {
                    alert('Please enter a valid 16-digit card number.');
                    cardNumber && cardNumber.focus();
                    return false;
                }

                if (!expiryDate || !expiryDate.value.match(/^(0[1-9]|1[0-2])\/([0-9]{2})$/)) {
                    alert('Please enter a valid expiry date (MM/YY).');
                    expiryDate && expiryDate.focus();
                    return false;
                }

                // Validate expiry date is not in the past
                const [month, year] = expiryDate.value.split('/');
                const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const today = new Date();
                if (expiry < today) {
                    alert('Card has expired. Please use a valid card.');
                    expiryDate.focus();
                    return false;
                }

                if (!newCardCvv || !newCardCvv.value.match(/^\d{3,4}$/)) {
                    alert('Please enter a valid CVV (3 or 4 digits).');
                    newCardCvv && newCardCvv.focus();
                    return false;
                }
            }

            return true;
        } catch (error) {
            console.error('Validation error:', error);
            return false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.getElementsByName('payment_method');
        const newCardForm = document.getElementById('new-card-form');
        const savedCardCvv = document.getElementById('saved-card-cvv');
        const saveCardCheckbox = document.getElementById('save_card');
        const makeDefaultContainer = document.querySelector('.save-card-default');
        
        // Format card number input
        const cardNumberInput = document.querySelector('input[name="card_number"]');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 16);
            });
        }

        // Format expiry date input
        const expiryInput = document.querySelector('input[name="expiry_date"]');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                this.value = this.value
                    .replace(/\D/g, '')
                    .substring(0, 4)
                    .replace(/(\d{2})(\d)/, '$1/$2');
            });
        }

        // Format CVV inputs
        const cvvInputs = document.querySelectorAll('input[name="cvv"], input[name="new_card_cvv"]');
        cvvInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });
        });
        
        // Handle payment method selection
        if(paymentMethods.length > 0) {
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if(this.value === 'new_card') {
                        newCardForm.style.display = 'block';
                        savedCardCvv && (savedCardCvv.style.display = 'none');
                        // Update required attributes
                        document.querySelector('input[name="cvv"]')?.removeAttribute('required');
                        document.querySelector('input[name="card_number"]')?.setAttribute('required', '');
                        document.querySelector('input[name="expiry_date"]')?.setAttribute('required', '');
                        document.querySelector('input[name="new_card_cvv"]')?.setAttribute('required', '');
                    } else {
                        newCardForm.style.display = 'none';
                        savedCardCvv && (savedCardCvv.style.display = 'block');
                        // Update required attributes
                        document.querySelector('input[name="cvv"]')?.setAttribute('required', '');
                        document.querySelector('input[name="card_number"]')?.removeAttribute('required');
                        document.querySelector('input[name="expiry_date"]')?.removeAttribute('required');
                        document.querySelector('input[name="new_card_cvv"]')?.removeAttribute('required');
                    }
                });
            });

            // Trigger change event on the checked radio button to set initial state
            const checkedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (checkedMethod) {
                checkedMethod.dispatchEvent(new Event('change'));
            }
        }

        // Handle save card checkbox
        if(saveCardCheckbox && makeDefaultContainer) {
            saveCardCheckbox.addEventListener('change', function() {
                makeDefaultContainer.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Add form submit handler
    const checkoutForm = document.querySelector('form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
                if (!validatePaymentInfo()) {
                e.preventDefault();
                }
            });
        }
    });

    function deleteCard(cardId) {
        if (!confirm('Are you sure you want to delete this card?')) {
                    return;
                }

                const formData = new FormData();
        formData.append('card_id', cardId);
                
        fetch('delete_card.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                // Remove the card element from the DOM
                const cardElement = document.getElementById('card-container-' + cardId);
                cardElement.remove();
                
                // If no cards left, refresh the page to show new card form
                const remainingCards = document.querySelectorAll('.saved-card-option').length;
                if (remainingCards <= 1) { // 1 because of the "Use new card" option
                    location.reload();
                }
                
                // Show success message
                alert('Card deleted successfully');
                    } else {
                alert(data.message || 'Error deleting card');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
            alert('Error deleting card. Please try again.');
        });
    }

    // Add client-side validation for new customer delivery info form
    function validateDeliveryInfoForm() {
        const firstName = document.querySelector('input[name="first_name"]')?.value;
        const lastName = document.querySelector('input[name="last_name"]')?.value;
        const birthday = document.querySelector('input[name="birthday"]')?.value;
        const phoneNumber = document.querySelector('input[name="phone_number"]')?.value;
        const address = document.querySelector('input[name="address"]')?.value;
        if (!firstName || !lastName || !birthday || !phoneNumber || !address) {
            showDeliveryError('Please fill in all required delivery information.');
            return false;
        }
        if (!/^\d{10}$/.test(phoneNumber)) {
            showDeliveryError('Please enter a valid 10-digit phone number.');
            return false;
        }
        clearDeliveryError();
        return true;
    }

    // Attach to form submit for new customers
    const deliveryForm = document.getElementById('delivery-form');
    if (deliveryForm) {
        deliveryForm.closest('form').addEventListener('submit', function(e) {
            if (!validateDeliveryInfoForm()) {
                e.preventDefault();
            }
        });
        // Clear error on input
        deliveryForm.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', clearDeliveryError);
        });
    }
    </script>
</body>
</html> 