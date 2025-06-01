<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Handle promotion redemption
if(isset($_POST['redeem_promotion'])) {
    $promotion_id = $_POST['promotion_id'];
    
    // Get promotion details and check usage limits
    $promotion_query = "SELECT p.*, 
                       (SELECT COUNT(*) FROM order_promotions op 
                        WHERE op.promotion_id = p.id 
                        AND op.user_id = ?) as user_usage_count
                       FROM promotions p 
                       WHERE p.id = ? 
                       AND p.is_active = 1
                       AND p.start_date <= CURRENT_DATE 
                       AND p.end_date >= CURRENT_DATE";
    
    $stmt = $con->prepare($promotion_query);
    $stmt->bind_param("ii", $user_data['id'], $promotion_id);
    $stmt->execute();
    $promotion = $stmt->get_result()->fetch_assoc();
    
    if($promotion) {
        $can_use = true;
        
        // Only check usage limit if it's set and greater than 0
        if($promotion['usage_limit'] !== NULL && $promotion['usage_limit'] > 0) {
            // Check if user has exceeded their personal usage limit
            if($promotion['user_usage_count'] >= $promotion['usage_limit']) {
                $can_use = false;
                $_SESSION['error_message'] = "You have reached the usage limit for this promotion.";
            }
        }
        
        if($can_use) {
            // Initialize session array for redeemed promotions if it doesn't exist
            if(!isset($_SESSION['redeemed_promotions'])) {
                $_SESSION['redeemed_promotions'] = array();
            }
            
            // Add promotion to redeemed promotions if not already redeemed
            if(!in_array($promotion_id, $_SESSION['redeemed_promotions'])) {
                $_SESSION['redeemed_promotions'][] = $promotion_id;
                $_SESSION['success_message'] = "Promotion redeemed successfully!";
            } else {
                $_SESSION['error_message'] = "You have already redeemed this promotion for your current cart.";
            }
        }
    } else {
        $_SESSION['error_message'] = "Invalid or expired promotion.";
    }
    
    header("Location: promotions.php");
    exit();
}

// Get active promotions
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM order_promotions op 
           JOIN orders o ON op.order_id = o.id 
           WHERE op.promotion_id = p.id AND o.user_id = ?) as user_usage_count
          FROM promotions p 
          WHERE p.is_active = 1 
          AND start_date <= CURRENT_DATE 
          AND end_date >= CURRENT_DATE";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$result = $stmt->get_result();

// Get current promotions
$current_result = $result;

// Get upcoming promotions
$upcoming_query = "SELECT * FROM promotions 
          WHERE is_active = 1 
          AND start_date > CURRENT_DATE 
          ORDER BY start_date ASC";
$upcoming_result = mysqli_query($con, $upcoming_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .promotions-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .promotions-section {
            margin-bottom: 60px;
        }

        .section-header {
            border-bottom: 2px solid #4CAF50;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.5em;
            margin: 0;
        }

        .promotions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .promotion-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .promotion-card:hover {
            transform: translateY(-5px);
        }

        .promotion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .current-badge {
            background-color: #4CAF50;
            color: white;
        }

        .upcoming-badge {
            background-color: #2196F3;
            color: white;
        }

        .promotion-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        .promotion-content {
            padding: 20px;
        }

        .promotion-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .promotion-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .promotion-dates {
            font-size: 0.9em;
            color: #888;
            margin-bottom: 15px;
        }

        .promotion-discount {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 12px;
            border-radius: 5px;
            display: inline-block;
            font-weight: 500;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h1 {
            font-size: 2em;
            color: #333;
            margin-bottom: 10px;
        }

        .section-title p {
            color: #666;
            font-size: 1.1em;
        }

        .no-promotions {
            text-align: center;
            padding: 30px;
            color: #666;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 20px;
        }

        .redeem-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .redeem-btn:hover {
            background-color: #45a049;
        }

        .redeem-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .already-redeemed {
            background-color: #e9ecef;
            color: #495057;
        }

        .uses-remaining {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .auto-applied {
            background-color: #4CAF50 !important;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="promotions-container">
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="message success-message">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="message error-message">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="section-title">
            <h1>Current & Upcoming Promotions</h1>
            <p>Don't miss out on these amazing deals!</p>
        </div>

        <!-- Current Promotions Section -->
        <div class="promotions-section">
            <div class="section-header">
                <h2><i class="fas fa-tag"></i> Current Promotions</h2>
            </div>
            <?php if(mysqli_num_rows($current_result) > 0): ?>
                <div class="promotions-grid">
                    <?php while($promotion = mysqli_fetch_assoc($current_result)): ?>
                        <div class="promotion-card">
                            <div class="promotion-badge current-badge">Current</div>
                            <?php if($promotion['banner_image']): ?>
                                <img src="<?php echo htmlspecialchars($promotion['banner_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($promotion['title']); ?>" 
                                     class="promotion-image"
                                     onerror="this.onerror=null; this.src='Images Assets/default_promotion.png';">
                            <?php endif; ?>
                            <div class="promotion-content">
                                <h2 class="promotion-title"><?php echo htmlspecialchars($promotion['title']); ?></h2>
                                <p class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></p>
                                <div class="promotion-dates">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php 
                                        $start_date = date('M d, Y', strtotime($promotion['start_date']));
                                        $end_date = date('M d, Y', strtotime($promotion['end_date']));
                                        echo "Valid from $start_date to $end_date";
                                    ?>
                                </div>
                                <div class="promotion-discount">
                                    <?php 
                                        if($promotion['discount_type'] == 'percentage') {
                                            echo number_format($promotion['discount_amount'], 0) . '% OFF';
                                        } else {
                                            echo '$' . number_format($promotion['discount_amount'], 2) . ' OFF';
                                        }
                                        
                                        if($promotion['minimum_purchase'] > 0) {
                                            echo ' on orders over $' . number_format($promotion['minimum_purchase'], 2);
                                        }

                                        // Show remaining uses if there's a limit
                                        if ($promotion['usage_limit'] > 0) {
                                            $uses_remaining = $promotion['usage_limit'] - $promotion['user_usage_count'];
                                            echo '<div class="uses-remaining">(' . $uses_remaining . ' uses remaining)</div>';
                                        }
                                    ?>
                                </div>
                                <?php
                                // Check if user has used this promotion before and still has uses remaining
                                $has_used_before = $promotion['user_usage_count'] > 0;
                                $is_redeemed = isset($_SESSION['redeemed_promotions']) && 
                                             in_array($promotion['id'], $_SESSION['redeemed_promotions']);
                                
                                // For unlimited usage promotions (usage_limit is null or 0)
                                $is_unlimited = $promotion['usage_limit'] === null || $promotion['usage_limit'] == 0;
                                
                                // Check usage limit only for limited-use promotions
                                $reached_limit = false;
                                if (!$is_unlimited && $promotion['usage_limit'] > 0) {
                                    $reached_limit = $promotion['user_usage_count'] >= $promotion['usage_limit'];
                                }

                                // Auto-apply if user has used it before and hasn't reached limit
                                if ($has_used_before && !$reached_limit && !$is_redeemed) {
                                    if(!isset($_SESSION['redeemed_promotions'])) {
                                        $_SESSION['redeemed_promotions'] = array();
                                    }
                                    $_SESSION['redeemed_promotions'][] = $promotion['id'];
                                    $is_redeemed = true;
                                }
                                ?>
                                <form method="POST">
                                    <input type="hidden" name="promotion_id" value="<?php echo $promotion['id']; ?>">
                                    <button type="submit" 
                                            name="redeem_promotion" 
                                            class="redeem-btn <?php echo $is_redeemed ? 'already-redeemed' : ''; ?>"
                                            <?php echo ($is_redeemed || (!$is_unlimited && $reached_limit)) ? 'disabled' : ''; ?>>
                                        <?php 
                                        if($is_redeemed) {
                                            echo $has_used_before ? 'Auto-Applied' : 'Already Redeemed';
                                        } elseif(!$is_unlimited && $reached_limit) {
                                            echo 'Usage Limit Reached';
                                        } else {
                                            echo 'Redeem Now';
                                        }
                                        ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-promotions">
                    <h3>No current promotions</h3>
                    <p>Check out our upcoming promotions below!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Promotions Section -->
        <div class="promotions-section">
            <div class="section-header">
                <h2><i class="fas fa-clock"></i> Upcoming Promotions</h2>
            </div>
            <?php if(mysqli_num_rows($upcoming_result) > 0): ?>
                <div class="promotions-grid">
                    <?php while($promotion = mysqli_fetch_assoc($upcoming_result)): ?>
                        <div class="promotion-card">
                            <div class="promotion-badge upcoming-badge">Upcoming</div>
                            <?php if($promotion['banner_image']): ?>
                                <img src="<?php echo htmlspecialchars($promotion['banner_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($promotion['title']); ?>" 
                                     class="promotion-image"
                                     onerror="this.onerror=null; this.src='Images Assets/default_promotion.png';">
                            <?php endif; ?>
                            <div class="promotion-content">
                                <h2 class="promotion-title"><?php echo htmlspecialchars($promotion['title']); ?></h2>
                                <p class="promotion-description"><?php echo htmlspecialchars($promotion['description']); ?></p>
                                <div class="promotion-dates">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php 
                                        $start_date = date('M d, Y', strtotime($promotion['start_date']));
                                        $end_date = date('M d, Y', strtotime($promotion['end_date']));
                                        echo "Starting from $start_date to $end_date";
                                    ?>
                                </div>
                                <div class="promotion-discount">
                                    <?php 
                                        if($promotion['discount_type'] == 'percentage') {
                                            echo number_format($promotion['discount_amount'], 0) . '% OFF';
                                        } else {
                                            echo '$' . number_format($promotion['discount_amount'], 2) . ' OFF';
                                        }
                                        
                                        if($promotion['minimum_purchase'] > 0) {
                                            echo ' on orders over $' . number_format($promotion['minimum_purchase'], 2);
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-promotions">
                    <h3>No upcoming promotions</h3>
                    <p>Check back soon for new promotions!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            var messages = document.getElementsByClassName('message');
            for(var i = 0; i < messages.length; i++) {
                messages[i].style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html> 