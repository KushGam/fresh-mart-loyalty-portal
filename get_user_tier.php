<?php
// Get user's loyalty information
function getUserTierInfo($con, $user_id) {
    // Calculate user's monthly spending from orders
    $spending_query = "SELECT 
        COALESCE(SUM(total_amount), 0) as monthly_spending
        FROM orders 
        WHERE user_id = ? 
        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
    
    $stmt = $con->prepare($spending_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $spending_info = $result->fetch_assoc();
    
    // Get monthly spending
    $monthly_spending = $spending_info['monthly_spending'];
    
    // Get the appropriate tier based on spending
    $tier_query = "SELECT lt.id, lt.tier_name, lt.points_multiplier
                  FROM loyalty_tiers lt
                  WHERE lt.spending_required <= ? 
                  ORDER BY lt.spending_required DESC 
                  LIMIT 1";
    
    $stmt = $con->prepare($tier_query);
    $stmt->bind_param("d", $monthly_spending);
    $stmt->execute();
    $tier_result = $stmt->get_result();
    $tier_info = $tier_result->fetch_assoc();
    
    // If no tier found (should never happen as Bronze should have 0 spending required)
    if (!$tier_info) {
        $default_tier_query = "SELECT id, tier_name, points_multiplier 
                              FROM loyalty_tiers 
                              WHERE tier_name = 'Bronze' LIMIT 1";
        $default_result = $con->query($default_tier_query);
        $tier_info = $default_result->fetch_assoc();
    }
    
    // Check if customer details exist
    $check_details_query = "SELECT id FROM customer_details WHERE user_id = ? LIMIT 1";
    $check_stmt = $con->prepare($check_details_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $details_result = $check_stmt->get_result();
    
    if ($details_result->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE customer_details SET loyalty_tier = ? WHERE user_id = ?";
        $update_stmt = $con->prepare($update_query);
        $update_stmt->bind_param("ii", $tier_info['id'], $user_id);
        $update_stmt->execute();
    } else {
        // Insert new record only if one doesn't exist
        $insert_query = "INSERT INTO customer_details (user_id, loyalty_tier) VALUES (?, ?)";
        $insert_stmt = $con->prepare($insert_query);
        $insert_stmt->bind_param("ii", $user_id, $tier_info['id']);
        $insert_stmt->execute();
    }
    
    // Add monthly spending to tier_info before returning
    $tier_info['monthly_spending'] = $monthly_spending;
    return $tier_info;
}
?> 