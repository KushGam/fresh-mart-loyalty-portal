<?php
function canUsePromotion($user_id, $promotion_id, $con) {
    // Check if promotion exists, is active, and is within valid date range
    $query = "SELECT p.*, 
              (SELECT COUNT(*) FROM order_promotions 
               WHERE promotion_id = p.id AND user_id = ?) as times_used
              FROM promotions p 
              WHERE p.id = ? 
              AND p.is_active = 1 
              AND p.is_redeemable = 1
              AND CURRENT_DATE() BETWEEN p.start_date AND p.end_date";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $user_id, $promotion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promo = $result->fetch_assoc();
    
    // If promotion doesn't exist or isn't active/valid
    if (!$promo) {
        return false;
    }
    
    // If promotion has no usage limit (NULL or 0), user can use it
    if ($promo['usage_limit'] === null || $promo['usage_limit'] == 0) {
        return true;
    }
    
    // Check if user has used this promotion less than the limit
    return $promo['times_used'] < $promo['usage_limit'];
}

function recordPromotionUsage($user_id, $order_id, $promotion_id, $discount_amount, $con) {
    $query = "INSERT INTO order_promotions (order_id, promotion_id, user_id, discount_amount) 
              VALUES (?, ?, ?, ?)";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("iiid", $order_id, $promotion_id, $user_id, $discount_amount);
    return $stmt->execute();
}

function getPromotionUsageCount($user_id, $promotion_id, $con) {
    $query = "SELECT COUNT(*) as count 
              FROM order_promotions 
              WHERE user_id = ? AND promotion_id = ?";
    
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $user_id, $promotion_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
} 