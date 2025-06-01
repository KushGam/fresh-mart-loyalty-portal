<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Insert order details
        $user_id = $user_data['id'];
        $total_amount = $_POST['total_amount'];
        $shipping_address = $_POST['shipping_address'];
        
        $order_query = "INSERT INTO orders (user_id, total_amount, shipping_address, status) 
                       VALUES (?, ?, ?, 'pending')";
        $stmt = $con->prepare($order_query);
        $stmt->bind_param("ids", $user_id, $total_amount, $shipping_address);
        $stmt->execute();
        
        $order_id = $con->insert_id;
        
        // Insert order items
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $quantity = $item['quantity'];
            $price = $item['price'];
            
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                          VALUES (?, ?, ?, ?)";
            $stmt = $con->prepare($item_query);
            $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $stmt->execute();
            
            // Update product stock
            $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $con->prepare($update_stock);
            $stmt->bind_param("ii", $quantity, $product_id);
            $stmt->execute();
        }
        
        // Process applied promotions
        if (isset($_SESSION['redeemed_promotions']) && !empty($_SESSION['redeemed_promotions'])) {
            foreach ($_SESSION['redeemed_promotions'] as $promotion_id) {
                // Get promotion details
                $promo_query = "SELECT * FROM promotions WHERE id = ?";
                $stmt = $con->prepare($promo_query);
                $stmt->bind_param("i", $promotion_id);
                $stmt->execute();
                $promotion = $stmt->get_result()->fetch_assoc();
                
                if ($promotion && $total_amount >= $promotion['minimum_purchase']) {
                    // Calculate discount
                    $discount = 0;
                    if ($promotion['discount_type'] == 'percentage') {
                        $discount = $total_amount * ($promotion['discount_amount'] / 100);
                    } else {
                        $discount = $promotion['discount_amount'];
                    }
                    
                    // Record promotion usage
                    $usage_query = "INSERT INTO order_promotions (order_id, promotion_id, discount_amount) 
                                  VALUES (?, ?, ?)";
                    $stmt = $con->prepare($usage_query);
                    $stmt->bind_param("iid", $order_id, $promotion_id, $discount);
                    $stmt->execute();
                    
                    // Update promotion usage count
                    $update_usage = "UPDATE promotions SET usage_count = usage_count + 1 WHERE id = ?";
                    $stmt = $con->prepare($update_usage);
                    $stmt->bind_param("i", $promotion_id);
                    $stmt->execute();
                }
            }
        }
        
        // Commit transaction
        $con->commit();
        
        // Clear cart and redeemed promotions
        unset($_SESSION['cart']);
        unset($_SESSION['redeemed_promotions']);
        
        // Redirect to success page
        $_SESSION['success_message'] = "Order placed successfully!";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $con->rollback();
        $_SESSION['error_message'] = "Error processing order: " . $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}
?> 