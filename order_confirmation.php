<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

if(!isset($_SESSION['order_success']) || !isset($_SESSION['order_number'])) {
    header("Location: homepage.php");
    exit();
}

// Get order details with customer information
$query = "SELECT o.*, cd.address, cd.phone_number,
          COALESCE(o.birthday_discount, 0.00) as birthday_discount,
          COALESCE(o.offer_discount, 0.00) as offer_discount,
          COALESCE(o.promo_discount, 0.00) as promo_discount,
          COALESCE(o.rewards_discount, 0.00) as rewards_discount,
          COALESCE(o.shipping_fee, 0.00) as shipping_fee,
          COALESCE(o.offer_points_multiplier, 1.00) as offer_points_multiplier,
          COALESCE(o.tier_multiplier, 1.00) as tier_multiplier,
          (SELECT SUM(oi.quantity * oi.price) FROM order_items oi WHERE oi.order_id = o.id) as subtotal,
          lt.tier_name,
          lt.points_multiplier as tier_points_multiplier,
          po.title as offer_title,
          po.discount_amount as offer_discount_amount,
          po.discount_type as offer_discount_type,
          po.points_multiplier as offer_multiplier,
          p.title as promotion_name,
          p.discount_amount as promotion_discount_amount,
          p.discount_type as promotion_discount_type
   FROM orders o 
   LEFT JOIN customer_details cd ON o.user_id = cd.user_id
   LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
   LEFT JOIN order_promotions op ON o.id = op.order_id
   LEFT JOIN promotions p ON op.promotion_id = p.id
   LEFT JOIN offer_usage ou ON o.id = ou.order_id
   LEFT JOIN personalized_offers po ON ou.offer_id = po.id
   WHERE o.order_number = ? AND o.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("si", $_SESSION['order_number'], $user_data['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if(!$order) {
    header("Location: homepage.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.img 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $con->prepare($items_query);
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$items = $stmt->get_result();

// Get all discounts for this order
$discounts_query = "SELECT 
    COALESCE(o.offer_discount, 0) as offer_discount,
    COALESCE(o.rewards_discount, 0) as rewards_discount,
    (SELECT COALESCE(SUM(op.discount_amount), 0) 
     FROM order_promotions op 
     WHERE op.order_id = o.id) as promotion_discount,
    lt.tier_name,
    lt.points_multiplier as tier_multiplier,
    po.points_multiplier as offer_multiplier
FROM orders o
LEFT JOIN customer_details cd ON o.user_id = cd.user_id
LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
LEFT JOIN offer_usage ou ON o.id = ou.order_id
LEFT JOIN personalized_offers po ON ou.offer_id = po.id
WHERE o.order_number = ? AND o.user_id = ?";

$stmt = $con->prepare($discounts_query);
$stmt->bind_param("si", $_SESSION['order_number'], $user_data['id']);
$stmt->execute();
$discount_result = $stmt->get_result()->fetch_assoc();

// Calculate total discount dynamically
$total_discount = $discount_result['offer_discount'] + 
                 $discount_result['promotion_discount'] + 
                 $discount_result['rewards_discount'];

// Get tier and multiplier info
$tier_name = $order['tier_name'] ?? $discount_result['tier_name'] ?? 'Bronze';
$tier_multiplier = floatval($order['tier_multiplier'] ?? $discount_result['tier_multiplier'] ?? 1.0);
$offer_multiplier = floatval($order['offer_points_multiplier'] ?? $discount_result['offer_multiplier'] ?? 1.0);
$final_multiplier = $tier_multiplier * $offer_multiplier;

// Calculate points the same way as checkout page
$points_query = "SELECT ls.points_per_dollar, lt.points_multiplier as tier_multiplier,
                po.points_multiplier as offer_multiplier
                FROM loyalty_settings ls
                CROSS JOIN customer_details cd 
                INNER JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
                LEFT JOIN orders o ON o.user_id = cd.user_id
                LEFT JOIN offer_usage ou ON ou.order_id = o.id
                LEFT JOIN personalized_offers po ON ou.offer_id = po.id
                WHERE cd.user_id = ? AND o.order_number = ?
                LIMIT 1";
$stmt = $con->prepare($points_query);
$stmt->bind_param("is", $user_data['id'], $_SESSION['order_number']);
$stmt->execute();
$points_result = $stmt->get_result();
$points_settings = $points_result->fetch_assoc();

$points_per_dollar = floatval($points_settings['points_per_dollar'] ?? 1.0);
$base_points = floor($order['total_amount'] * $points_per_dollar);

// Fetch all applied offers for this order
$applied_offers = [];
$offer_multipliers = [];
$offers_query = "SELECT po.title, po.points_multiplier FROM offer_usage ou JOIN personalized_offers po ON ou.offer_id = po.id WHERE ou.order_id = ?";
$stmt = $con->prepare($offers_query);
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$offers_result = $stmt->get_result();
while ($offer = $offers_result->fetch_assoc()) {
    $applied_offers[] = $offer;
    if (floatval($offer['points_multiplier']) > 1) {
        $offer_multipliers[] = floatval($offer['points_multiplier']);
    }
}
// Calculate combined offer multiplier
$combined_offer_multiplier = empty($offer_multipliers) ? 1 : array_reduce($offer_multipliers, function($carry, $item) { return $carry * $item; }, 1);
// Calculate final multiplier
$final_multiplier = $tier_multiplier * $combined_offer_multiplier;
// Calculate total points
$total_points = floor($base_points * $final_multiplier);

// Fetch all applied promotions for display
$promotions_query = "SELECT p.title as promotion_name, op.discount_amount
                    FROM order_promotions op
                    JOIN promotions p ON op.promotion_id = p.id
                    WHERE op.order_id = ?";
$stmt = $con->prepare($promotions_query);
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$promotions = $stmt->get_result();

// Clear the success session variables
unset($_SESSION['order_success']);
unset($_SESSION['order_number']);
unset($_SESSION['redeemed_promotions']);
unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .success-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            color: #4CAF50;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .confirmation-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
        }

        .order-details {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .order-items {
            margin: 30px 0;
        }

        .item-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 5px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-price {
            color: #666;
        }

        .total-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .total-row.final {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-top: 10px;
        }

        .delivery-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .continue-shopping {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 30px;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background: #45a049;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }

            .item-card {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                margin: 0 0 15px 0;
            }
        }

        .points-earned {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .points-details {
            margin-top: 15px;
        }

        .points-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .multiplier-breakdown {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
            text-align: right;
        }

        .total-points {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            color: #4CAF50;
        }

        .whats-next {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .continue-shopping:hover {
            background: #45a049;
        }

        .applied-offers,
        .applied-promotions {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .applied-offers h4,
        .applied-promotions h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }

        .multiplier-breakdown {
            padding-left: 15px;
            color: #666;
            line-height: 1.5;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 4px 0;
        }

        .total-discount {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-weight: 600;
        }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <div style="flex: 1 0 auto;">
    <?php include('header.php'); ?>

    <div class="confirmation-container">
        <div class="success-header">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p>Thank you for your purchase. We've sent a confirmation email to <?php echo htmlspecialchars($user_data['email']); ?></p>
        </div>

        <div class="order-details">
            <h2>Order Details</h2>
            <p><strong>Order Number:</strong> #<?php echo htmlspecialchars($order['order_number']); ?></p>
            <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['address'] ?? 'Not provided'); ?></p>
            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($order['phone_number'] ?? 'Not provided'); ?></p>
            <p><strong>Estimated Delivery:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['delivery_time'])); ?></p>
        </div>

        <div class="order-items">
            <h2>Items Ordered</h2>
            <?php while ($item = $items->fetch_assoc()): ?>
                <div class="item-card">
                    <img src="<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-price">
                            <?php echo $item['quantity']; ?> x $<?php echo number_format($item['price'], 2); ?>
                        </div>
                    </div>
                    <div class="item-total">
                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endwhile; ?>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?php echo $order['shipping_fee'] > 0 ? '$' . number_format($order['shipping_fee'], 2) : '<span style="color: #4CAF50;">FREE</span>'; ?></span>
                </div>

                <!-- Applied Offers Section -->
                <?php if($order['offer_discount'] > 0): ?>
                <div class="applied-offers">
                    <h4>Applied Offers</h4>
                    <div class="summary-row offer-row">
                        <span><?php echo htmlspecialchars($order['offer_title']); ?></span>
                        <span class="discount">-$<?php echo number_format($order['offer_discount'], 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Applied Promotions Section -->
                <?php if($promotions->num_rows > 0): ?>
                <div class="applied-promotions">
                    <h4>Applied Promotions</h4>
                    <?php while($promotion = $promotions->fetch_assoc()): ?>
                    <div class="summary-row" style="color: #4CAF50;">
                        <span><?php echo htmlspecialchars($promotion['promotion_name']); ?></span>
                        <span>-$<?php echo number_format($promotion['discount_amount'], 2); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>

                <!-- Rewards Discount Section -->
                <?php if($discount_result['rewards_discount'] > 0): ?>
                <div class="rewards-discount">
                    <div class="summary-row" style="color: #4CAF50;">
                        <span>Rewards Discount</span>
                        <span>-$<?php echo number_format($discount_result['rewards_discount'], 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Total Discount Section -->
                <?php if($total_discount > 0): ?>
                <div class="total-discount" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                    <div class="summary-row" style="color: #4CAF50;">
                        <span>Total Discount</span>
                        <span>-$<?php echo number_format($total_discount, 2); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Total Section -->
                <div class="summary-row total-row">
                    <span><strong>Total</strong></span>
                    <span><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></span>
            </div>

                <!-- Points Section -->
            <div class="points-earned">
                <h3>Points You'll Earn</h3>
                <div class="points-details">
                    <div class="points-row">
                        <span>Base Points:</span>
                        <span><?php echo number_format($base_points); ?> points</span>
                    </div>
                    <?php
                    // Dynamically show multiplier breakdown like checkout.php
                    $multiplier_breakdown = [];
                    if ($tier_multiplier > 1) {
                        $multiplier_breakdown[] = '• ' . htmlspecialchars($tier_name) . ' Tier: ' . number_format($tier_multiplier, 2) . 'x';
                    }
                    foreach ($applied_offers as $offer) {
                        if (floatval($offer['points_multiplier']) > 1) {
                            $multiplier_breakdown[] = '• Offer: ' . htmlspecialchars($offer['title']) . ': ' . number_format($offer['points_multiplier'], 2) . 'x';
                        }
                    }
                    ?>
                    <?php if (!empty($multiplier_breakdown)): ?>
                    <div class="points-row">
                        <span>Points Multiplier Breakdown:</span>
                        <div class="multiplier-breakdown">
                            <?php echo implode('<br>', $multiplier_breakdown); ?><br>
                            Total: <?php echo number_format($final_multiplier, 2); ?>x
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="points-row total">
                        <span>Total Points:</span>
                        <span class="total-points"><?php echo number_format($total_points); ?> points</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="delivery-info">
            <h3>What's Next?</h3>
            <p>We'll send you shipping confirmation and tracking number when your order ships.</p>
            <p>Expected delivery: <?php echo date('F j, Y', strtotime($order['delivery_time'])); ?></p>
        </div>

        <div style="text-align: center;">
            <a href="homepage.php" class="continue-shopping">Continue Shopping</a>
        </div>
    </div>
    </div>
    <?php include('footer.php'); ?>
</body>
</html> 