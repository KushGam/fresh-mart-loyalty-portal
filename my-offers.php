<?php
session_start();
include("connection.php");

// Check if user is logged in
if(!isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Get user's offers
$query = "SELECT 
            uo.*,
            po.title,
            po.description,
            po.discount_type,
            po.discount_amount,
            po.minimum_purchase,
            po.offer_type,
            po.points_multiplier,
            po.end_date,
            po.usage_limit,
            (SELECT COUNT(*) FROM offer_usage 
             WHERE offer_id = po.id AND user_id = ?) as times_used,
            (SELECT used_at FROM offer_usage 
             WHERE offer_id = po.id AND user_id = ? 
             ORDER BY used_at DESC LIMIT 1) as last_used_date,
            CASE 
                WHEN po.end_date < CURDATE() THEN 'expired'
                WHEN po.usage_limit IS NOT NULL 
                     AND po.usage_limit > 0 
                     AND (SELECT COUNT(*) FROM offer_usage 
                          WHERE offer_id = po.id AND user_id = ?) >= po.usage_limit 
                     THEN 'used'
                WHEN uo.status = 'used' OR uo.order_id IS NOT NULL THEN 'used'
                ELSE 'active'
            END as display_status
          FROM user_offers uo
          JOIN personalized_offers po ON uo.offer_id = po.id
          WHERE uo.user_id = ?
          ORDER BY uo.created_at DESC";

$stmt = $con->prepare($query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$offers = $stmt->get_result();

// Get store redemptions with updated query
$redemptions_query = "SELECT 
                        sr.*,
                        CASE 
                            WHEN sr.status = 'pending' THEN 'Pending'
                            WHEN sr.status = 'redeemed' THEN 'Redeemed'
                            ELSE 'Expired'
                        END as status_text,
                        COALESCE(u.user_name, 'System') as redeemed_by
                     FROM store_redemptions sr
                     LEFT JOIN users u ON sr.admin_id = u.id
                     WHERE sr.user_id = ?
                     ORDER BY sr.created_at DESC";
$stmt = $con->prepare($redemptions_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$store_redemptions = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Offers - Freshmart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-right: 20px;
        }

        .tab.active {
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .offer-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }

        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .offer-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .offer-status {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-used {
            background: #eeeeee;
            color: #616161;
        }

        .status-expired {
            background: #ffebee;
            color: #c62828;
        }

        .offer-details {
            margin: 15px 0;
            color: #555;
            line-height: 1.6;
        }

        .offer-meta {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .redemption-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .redemption-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .redemption-code {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .redemption-date {
            color: #888;
            font-size: 14px;
        }

        .redemption-details {
            color: #666;
            line-height: 1.6;
        }

        .redemption-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .status-pending {
            background: #fff3e0;
            color: #e65100;
        }

        .status-redeemed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .no-items {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-items i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-items p {
            margin: 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="container">
        <div class="tabs">
            <div class="tab active" onclick="switchTab('special-offers')">Special Offers History</div>
            <div class="tab" onclick="switchTab('store-redemptions')">Store Redemptions</div>
        </div>

        <div id="special-offers" class="tab-content active">
            <h2>My Special Offers History</h2>
            <?php if ($offers->num_rows > 0): ?>
                <?php while ($offer = $offers->fetch_assoc()): ?>
                    <div class="offer-card">
                        <div class="offer-header">
                            <h3 class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></h3>
                            <span class="offer-status <?php 
                                echo $offer['display_status'] == 'active' ? 'status-active' : 
                                     ($offer['display_status'] == 'used' ? 'status-used' : 'status-expired'); 
                            ?>">
                                <?php 
                                    if ($offer['display_status'] == 'active') {
                                        echo 'Active';
                                    } elseif ($offer['display_status'] == 'used') {
                                        if ($offer['usage_limit'] === null || $offer['usage_limit'] == 0) {
                                            echo 'Used (' . $offer['times_used'] . ')';
                                        } else {
                                            echo 'Used (' . $offer['times_used'] . '/' . $offer['usage_limit'] . ')';
                                        }
                                    } else {
                                        echo 'Expired';
                                    }
                                ?>
                            </span>
                        </div>
                        
                        <div class="offer-details">
                            <?php echo htmlspecialchars($offer['description']); ?>
                            <br><br>
                            <strong>Discount:</strong> 
                            <?php if ($offer['discount_type'] == 'percentage'): ?>
                                <?php echo $offer['discount_amount']; ?>% off
                            <?php else: ?>
                                $<?php echo number_format($offer['discount_amount'], 2); ?> off
                            <?php endif; ?>
                            <?php if ($offer['points_multiplier'] > 1): ?>
                                <br><strong>Points multiplier:</strong> <?php echo number_format($offer['points_multiplier'], 1); ?>x
                            <?php endif; ?>
                            <?php if ($offer['minimum_purchase'] > 0): ?>
                                <br><strong>Minimum purchase:</strong> $<?php echo number_format($offer['minimum_purchase'], 2); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="offer-meta">
                            <?php if ($offer['display_status'] == 'used'): ?>
                                <?php if ($offer['last_used_date']): ?>
                                    <span>Redeemed on <?php echo date('M j, Y', strtotime($offer['last_used_date'])); ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <span>Valid until <?php echo date('M j, Y', strtotime($offer['end_date'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-gift"></i>
                    <p>You haven't redeemed any special offers yet.<br>
                    Check the loyalty dashboard for available offers!</p>
                </div>
            <?php endif; ?>
        </div>

        <div id="store-redemptions" class="tab-content">
            <h2>Store Redemptions History</h2>
            <?php if ($store_redemptions->num_rows > 0): ?>
                <?php while ($redemption = $store_redemptions->fetch_assoc()): ?>
                    <div class="redemption-card">
                        <div class="redemption-header">
                            <div class="redemption-code">
                                Code: <?php echo htmlspecialchars($redemption['redemption_code']); ?>
                            </div>
                            <div class="redemption-date">
                                <?php echo date('M j, Y', strtotime($redemption['created_at'])); ?>
                            </div>
                        </div>
                        <div class="redemption-status status-<?php echo strtolower($redemption['status']); ?>">
                            <?php echo $redemption['status_text']; ?>
                        </div>
                        <div class="redemption-details">
                            <p><strong>Points redeemed:</strong> <?php echo number_format($redemption['points_redeemed']); ?></p>
                            <p><strong>Amount:</strong> $<?php echo number_format($redemption['amount'], 2); ?></p>
                            <?php if ($redemption['status'] == 'redeemed'): ?>
                                <p><strong>Redeemed by:</strong> <?php echo htmlspecialchars($redemption['redeemed_by']); ?></p>
                                <p><strong>Redeemed on:</strong> <?php echo date('M j, Y', strtotime($redemption['redeemed_at'])); ?></p>
                            <?php endif; ?>
                            <p><strong>Expires:</strong> <?php echo date('M j, Y', strtotime($redemption['expires_at'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-store"></i>
                    <p>No store redemptions yet.<br>
                    Visit our stores to start earning points!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function switchTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Deactivate all tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab content
        document.getElementById(tabId).classList.add('active');
        
        // Activate selected tab
        document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
    }
    </script>

    <?php include('footer.php'); ?>
</body>
</html> 