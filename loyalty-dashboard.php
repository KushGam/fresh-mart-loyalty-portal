<?php
session_start();
include("connection.php");

// Add CSRF token generation at the top of the file after session_start()
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if(!isset($_SESSION['user'])) {
    header('location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Update monthly spending before displaying dashboard
include_once("functions.php");
update_monthly_spending($con, $user_id);

// Get user's loyalty tier and points
$query = "SELECT u.*, cd.first_name, cd.last_name, cd.birthday, lt.tier_name, lt.spending_required 
          FROM users u 
          LEFT JOIN customer_details cd ON u.id = cd.user_id
          LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
          WHERE u.id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_id);
    $stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get today's date in MM-DD format for birthday offers
$today = date('m-d');
$user_birthday = $user_data['birthday'] ? date('m-d', strtotime($user_data['birthday'])) : null;

// Get user's birthday from customer_details
$birthday_query = "SELECT DATE_FORMAT(birthday, '%m-%d') as birthday_mmdd FROM customer_details WHERE user_id = ?";
$stmt = $con->prepare($birthday_query);
$stmt->bind_param("i", $user_id);
        $stmt->execute();
$birthday_result = $stmt->get_result();
$user_birthday = $birthday_result->fetch_assoc()['birthday_mmdd'];

// Get user's current tier and spending info
$query = "SELECT cd.monthly_spending, lt.tier_name, lt.spending_required,
          COALESCE(
              (SELECT MIN(spending_required) 
               FROM loyalty_tiers 
               WHERE spending_required > lt.spending_required
              ), 
              lt.spending_required
          ) as next_tier_spending,
          COALESCE(
              (SELECT tier_name 
               FROM loyalty_tiers 
               WHERE spending_required > lt.spending_required
               ORDER BY spending_required ASC 
               LIMIT 1
              ),
              lt.tier_name
          ) as next_tier_name
          FROM customer_details cd
          JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
          WHERE cd.user_id = ?";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Calculate progress percentage with null checks
$current_spending = $result['monthly_spending'] ?? 0;
$next_tier_spending = $result['next_tier_spending'] ?? 0;
$current_tier_spending = $result['spending_required'] ?? 0;

// Avoid division by zero
if ($next_tier_spending > $current_tier_spending) {
    $progress = ($current_spending - $current_tier_spending) / ($next_tier_spending - $current_tier_spending) * 100;
    $progress = max(0, min(100, $progress)); // Ensure progress is between 0 and 100
            } else {
    $progress = 100; // If there's no next tier, show as 100%
}

// Get user's loyalty information
$query = "SELECT 
            u.id,
            u.user_name,
            cd.loyalty_tier,
            lt.tier_name,
            lt.points_multiplier,
            lt.special_benefits,
            lt.spending_required,
            COALESCE(
                (SELECT SUM(total_amount) 
                 FROM orders 
                 WHERE user_id = u.id 
                 AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                ), 0
            ) as monthly_spending,
            (
                SELECT lt2.spending_required 
                FROM loyalty_tiers lt2 
                WHERE lt2.spending_required > COALESCE(
                    (SELECT SUM(total_amount) 
                     FROM orders 
                     WHERE user_id = u.id 
                     AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    ), 0
                )
                ORDER BY lt2.spending_required ASC 
                LIMIT 1
            ) as next_tier_spending
          FROM users u
          LEFT JOIN customer_details cd ON u.id = cd.user_id
          LEFT JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
          WHERE u.id = ?";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();

// Get user's tier
$tier_query = "SELECT lt.tier_name FROM customer_details cd 
               JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id 
               WHERE cd.user_id = ?";
$tier_stmt = $con->prepare($tier_query);
$tier_stmt->bind_param("i", $user_id);
$tier_stmt->execute();
$tier_result = $tier_stmt->get_result();
$user_tier = $tier_result->fetch_assoc()['tier_name'];

// Get available offers for the user
$current_date = date('Y-m-d');
$query = "SELECT po.*
          FROM personalized_offers po
          WHERE po.is_active = 1
          AND ? BETWEEN po.start_date AND po.end_date
          AND (po.loyalty_tier IS NULL OR po.loyalty_tier = '' OR po.loyalty_tier = ?)
          AND (po.usage_limit IS NULL OR po.usage_count < po.usage_limit)
          AND NOT EXISTS (
              SELECT 1 FROM user_offers uo 
              WHERE uo.offer_id = po.id 
              AND uo.user_id = ?
              AND (uo.status = 'used' OR uo.status = 'active')
          )
          ORDER BY 
            CASE WHEN po.offer_type = 'birthday' THEN 0 ELSE 1 END,
            po.created_at DESC";

$stmt = $con->prepare($query);
$stmt->bind_param("ssi", $current_date, $user_tier, $user_id);
$stmt->execute();
$offers_result = $stmt->get_result();

// Handle offer redemption
if (isset($_POST['redeem_offer'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission - CSRF token mismatch.";
        header('Location: loyalty-dashboard.php');
        exit();
    }

    $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
    
    if ($offer_id <= 0) {
        $_SESSION['error'] = "Invalid offer ID: " . htmlspecialchars($offer_id);
        header('Location: loyalty-dashboard.php');
        exit();
    }
    
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Verify offer eligibility again
        $verify_query = "SELECT * FROM personalized_offers 
                        WHERE id = ? 
                        AND is_active = 1 
                        AND CURRENT_DATE BETWEEN start_date AND end_date";
        $verify_stmt = $con->prepare($verify_query);
        if (!$verify_stmt) {
            throw new Exception("Prepare failed: " . $con->error);
        }
        $verify_stmt->bind_param("i", $offer_id);
        if (!$verify_stmt->execute()) {
            throw new Exception("Execute failed: " . $verify_stmt->error);
        }
        $result = $verify_stmt->get_result();
        if (!$result) {
            throw new Exception("Get result failed: " . $verify_stmt->error);
        }
        $offer = $result->fetch_assoc();
        
        if ($offer) {
            // Check if user has already redeemed this offer
            $check_query = "SELECT * FROM user_offers 
                          WHERE user_id = ? 
                          AND offer_id = ? 
                          AND (status = 'active' OR status = 'used')";
            $check_stmt = $con->prepare($check_query);
            if (!$check_stmt) {
                throw new Exception("Check prepare failed: " . $con->error);
            }
            $check_stmt->bind_param("ii", $user_id, $offer_id);
            if (!$check_stmt->execute()) {
                throw new Exception("Check execute failed: " . $check_stmt->error);
            }
            $check_result = $check_stmt->get_result();
            if (!$check_result) {
                throw new Exception("Check get result failed: " . $check_stmt->error);
            }
            
            if ($check_result->num_rows == 0) {
                // For birthday offers, check if it's user's birthday
                if ($offer['offer_type'] == 'birthday') {
                    $can_redeem = $user_birthday == date('m-d');
                } else {
                    $can_redeem = true;
                }
                
                if ($can_redeem) {
                    // Insert into user_offers table
                    $insert_query = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                                   VALUES (?, ?, 'active', NOW())";
                    $insert_stmt = $con->prepare($insert_query);
                    if (!$insert_stmt) {
                        throw new Exception("Insert prepare failed: " . $con->error);
                    }
                    $insert_stmt->bind_param("ii", $user_id, $offer_id);
                    if (!$insert_stmt->execute()) {
                        throw new Exception("Insert execute failed: " . $insert_stmt->error);
                    }
                    
                    // Update usage count in personalized_offers
                    $update_query = "UPDATE personalized_offers 
                                   SET usage_count = COALESCE(usage_count, 0) + 1 
                                   WHERE id = ?";
                    $update_stmt = $con->prepare($update_query);
                    if (!$update_stmt) {
                        throw new Exception("Update prepare failed: " . $con->error);
                    }
                    $update_stmt->bind_param("i", $offer_id);
                    if (!$update_stmt->execute()) {
                        throw new Exception("Update execute failed: " . $update_stmt->error);
                    }
                    
                    $con->commit();
                    $_SESSION['success'] = "Offer redeemed successfully! Check 'My Offers' to view and use it.";
                } else {
                    $con->rollback();
                    $_SESSION['error'] = "This birthday offer can only be redeemed on your birthday.";
                }
            } else {
                $con->rollback();
                $_SESSION['error'] = "You have already redeemed this offer.";
            }
        } else {
            $con->rollback();
            $_SESSION['error'] = "Offer not found or no longer active.";
        }
    } catch (Exception $e) {
        $con->rollback();
        error_log("Offer redemption error: " . $e->getMessage());
        $_SESSION['error'] = "Error redeeming offer: " . $e->getMessage();
    }
    
    header('Location: loyalty-dashboard.php');
    exit();
}

// Special handling for welcome offer for new users
if (!isset($_SESSION['welcome_offer_checked'])) {
    // Check if user has welcome offer
    $welcome_query = "SELECT id FROM personalized_offers WHERE title = 'Welcome offer' AND is_active = 1";
    $welcome_result = $con->query($welcome_query);
    $welcome_offer = $welcome_result->fetch_assoc();

    if ($welcome_offer) {
        $check_query = "SELECT id FROM user_offers WHERE user_id = ? AND offer_id = ?";
        $check_stmt = $con->prepare($check_query);
        $check_stmt->bind_param("ii", $user_id, $welcome_offer['id']);
        $check_stmt->execute();
        $existing = $check_stmt->get_result();

        if ($existing->num_rows == 0) {
            // Automatically assign welcome offer to new user
            $insert_query = "INSERT INTO user_offers (user_id, offer_id, status, created_at) 
                            VALUES (?, ?, 'active', NOW())";
            $stmt = $con->prepare($insert_query);
            $stmt->bind_param("ii", $user_id, $welcome_offer['id']);
            $stmt->execute();
        }
    }
    $_SESSION['welcome_offer_checked'] = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loyalty Dashboard - Freshmart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 32px;
            font-weight: bold;
        }

        .tier-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #e0e0e0;
        }

        .tier-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .tier-icon {
            font-size: 48px;
            margin-right: 15px;
        }

        .tier-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000000 !important;
        }

        .tier-welcome {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }

        .points-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .points-box {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .points-label {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .points-value {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
        }

        .progress-container {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .progress-label {
            text-align: left;
            margin-bottom: 10px;
            font-weight: 500;
            color: #333;
        }

        .progress-bar {
            height: 24px;
            background: #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            transition: width 0.3s ease;
            border-radius: 12px;
        }

        .benefits-list {
            text-align: left;
            margin: 20px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .benefits-list h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
            font-weight: bold;
        }

        .benefits-list ul {
            list-style-type: none;
            padding: 0;
        }

        .benefits-list li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
            font-size: 16px;
            color: #555;
        }

        .benefits-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4CAF50;
            font-weight: bold;
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 40px 0 20px;
            text-align: center;
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .offer-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.2s ease;
        }

        .offer-card:hover {
            transform: translateY(-5px);
        }

        .offer-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .offer-details {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .offer-validity {
            font-size: 14px;
            color: #888;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .bronze { color: #cd7f32; }
        .silver { color: #c0c0c0; }
        .gold { color: #ffd700; }
        .platinum { color: #e5e4e2; }

        .redeem-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .redeem-btn:hover {
            background: #45a049;
        }
        
        .redeem-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .points-info {
                grid-template-columns: 1fr;
            }

            .offers-grid {
                grid-template-columns: 1fr;
            }
        }

        .progress-stats {
            display: flex;
            justify-content: center;
            margin-top: 8px;
            color: #666;
            font-size: 14px;
        }

        .next-tier-preview {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .next-tier-preview h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .next-tier-preview p {
            color: #666;
            margin-bottom: 10px;
        }

        .next-tier-benefit {
            color: #666;
            font-style: italic;
        }

        .next-tier-benefit::before {
            content: "★";
            color: #4CAF50;
            margin-right: 8px;
        }

        .debug-border {
            display: none;
        }
        
        .offer-card:empty {
            display: none;
        }
        
        .offers-grid:empty {
            display: none;
        }

        .birthday-offer {
            border: 2px solid #FFD700;
            background: #FFFDF0;
        }

        .birthday-icon {
            color: #FFD700;
            margin-right: 5px;
        }

        .crown-icon {
            filter: drop-shadow(0 0 3px rgba(0, 0, 0, 0.2));
            color: #FFD700 !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        .member-title {
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            color: #000000;
            font-weight: 600;
        }

        .member-welcome {
            background: linear-gradient(45deg, #f3f3f3, #ffffff);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .tier-icon.bronze,
        .tier-icon.silver,
        .tier-icon.gold,
        .tier-icon.platinum {
            color: #FFD700 !important;
        }

        .tier-name.bronze,
        .tier-name.silver,
        .tier-name.gold,
        .tier-name.platinum {
            color: #000000 !important;
        }

        .faq-section {
            margin: 50px auto 30px auto;
            max-width: 900px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .faq-list {
            margin-top: 20px;
        }
        .faq-item {
            margin-bottom: 20px;
        }
        .faq-question {
            font-weight: bold;
            color: #333;
            cursor: pointer;
            margin-bottom: 5px;
        }
        .faq-answer {
            color: #555;
            margin-left: 15px;
            margin-bottom: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="dashboard-container">
        <h1 class="page-title">My Loyalty Dashboard</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['tier_update'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['tier_update'];
                    unset($_SESSION['tier_update']);
                ?>
            </div>
        <?php endif; ?>

        <div class="tier-card">
            <div class="tier-header">
                <i class="fas fa-crown tier-icon <?php echo strtolower($user_info['tier_name'] ?? 'bronze'); ?>"></i>
                <div>
                    <div class="tier-name <?php echo strtolower($user_info['tier_name'] ?? 'bronze'); ?>">
                        <?php echo ucfirst($user_info['tier_name'] ?? 'Bronze'); ?> Member
                    </div>
                    <div class="tier-welcome">
                        Welcome, <?php echo htmlspecialchars($user_info['user_name']); ?>!
                    </div>
                </div>
            </div>

            <div class="points-info">
                <div class="points-box">
                    <div class="points-label">Monthly Spending</div>
                    <div class="points-value">$<?php echo number_format($user_info['monthly_spending'], 2); ?></div>
                </div>
                <div class="points-box">
                    <div class="points-label">Points Multiplier</div>
                    <div class="points-value"><?php echo number_format($user_info['points_multiplier'] ?? 1, 1); ?>x</div>
                </div>
                <?php if ($user_info['next_tier_spending']): ?>
                <div class="points-box">
                    <div class="points-label">Spending to Next Tier</div>
                    <div class="points-value">
                        $<?php echo number_format($user_info['next_tier_spending'] - $user_info['monthly_spending'], 2); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($user_info['next_tier_spending']): ?>
            <div class="progress-container">
                <div class="progress-label">
                    Progress to <?php 
                        $next_tier_query = "SELECT tier_name FROM loyalty_tiers 
                                          WHERE spending_required = ?";
                        $stmt = $con->prepare($next_tier_query);
                        $stmt->bind_param("d", $user_info['next_tier_spending']);
                        $stmt->execute();
                        $next_tier = $stmt->get_result()->fetch_assoc();
                        echo $next_tier['tier_name'];
                    ?> Tier
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php 
                        echo min(100, ($user_info['monthly_spending'] / $user_info['next_tier_spending']) * 100);
                    ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span>$<?php echo number_format($user_info['monthly_spending'], 2); ?> / $<?php echo number_format($user_info['next_tier_spending'], 2); ?> monthly spending</span>
                </div>
            </div>
            <?php endif; ?>

            <div class="benefits-list">
                <h3>Your <?php echo ucfirst($user_info['tier_name'] ?? 'Bronze'); ?> Benefits</h3>
                <ul>
                    <?php 
                    $benefits = str_replace('\\r\\n', "\n", $user_info['special_benefits'] ?? '');
                    $benefits = str_replace('\r\n', "\n", $benefits);
                    $benefits = explode("\n", $benefits);
                    foreach ($benefits as $benefit):
                        if (trim($benefit)):
                    ?>
                        <li><?php echo htmlspecialchars(trim($benefit)); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>

            <?php if ($user_info['next_tier_spending']): ?>
            <div class="next-tier-preview">
                <h3>Next Tier Benefits</h3>
                <p>Reach <?php echo number_format($user_info['next_tier_spending']); ?> spending to unlock:</p>
                <ul>
                    <?php 
                    $next_tier_benefits_query = "SELECT special_benefits FROM loyalty_tiers 
                                               WHERE spending_required = ?";
                    $stmt = $con->prepare($next_tier_benefits_query);
                    $stmt->bind_param("d", $user_info['next_tier_spending']);
                    $stmt->execute();
                    $next_tier_benefits = $stmt->get_result()->fetch_assoc()['special_benefits'];
                    $next_benefits = str_replace('\\r\\n', "\n", $next_tier_benefits ?? '');
                    $next_benefits = str_replace('\r\n', "\n", $next_benefits);
                    $next_benefits = explode("\n", $next_benefits);
                    foreach ($next_benefits as $benefit):
                        if (trim($benefit)):
                    ?>
                        <li class="next-tier-benefit"><?php echo htmlspecialchars(trim($benefit)); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <h2 class="section-title">Available Offers</h2>
        <div class="offers-grid">
            <?php 
            $has_offers = false;
            while ($offer = $offers_result->fetch_assoc()): 
                $is_eligible = false;
                if ($offer['offer_type'] == 'birthday') {
                    $is_eligible = ($user_birthday == $today);
                } else {
                    $is_eligible = true;
                }
                if ($is_eligible): 
                    $has_offers = true;
            ?>
                <div class="offer-card <?php echo $offer['offer_type'] == 'birthday' ? 'birthday-offer' : ''; ?>">
                    <div class="offer-title">
                        <?php if ($offer['offer_type'] == 'birthday'): ?>
                            <i class="fas fa-birthday-cake birthday-icon"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($offer['title']); ?>
                    </div>
                    <div class="offer-details">
                        <?php echo htmlspecialchars($offer['description']); ?>
                    </div>
                    <div class="offer-details">
                        <div>
                            <?php if ($offer['discount_type'] == 'percentage'): ?>
                                <?php echo $offer['discount_amount']; ?>% off
                            <?php else: ?>
                                $<?php echo number_format($offer['discount_amount'], 2); ?> off
                        <?php endif; ?>
                            <?php if ($offer['minimum_purchase'] > 0): ?>
                                (Min. purchase: $<?php echo number_format($offer['minimum_purchase'], 2); ?>)
                            <?php endif; ?>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                            <button type="submit" name="redeem_offer" class="redeem-btn">Redeem Now</button>
                        </form>
                    </div>
                </div>
            <?php 
                endif;
            endwhile; 
            
            if (!$has_offers):
            ?>
                <div style="text-align: center; grid-column: 1/-1; padding: 40px;">
                    <i class="fas fa-gift" style="font-size: 48px; color: #4CAF50; margin-bottom: 20px; display: block;"></i>
                    <p style="color: #666; margin: 0;">
                        No special offers available at the moment.<br>
                        Keep earning points to unlock more exclusive offers!
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="faq-section">
        <h2 class="section-title">Loyalty Dashboard FAQ</h2>
        <div class="faq-list">
            <div class="faq-item">
                <div class="faq-question">What is the Loyalty Dashboard?</div>
                <div class="faq-answer">The Loyalty Dashboard shows your current membership tier, monthly spending, points multiplier, and available exclusive offers.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How do I earn points?</div>
                <div class="faq-answer">You earn points for every purchase you make. The higher your tier, the more points you earn per dollar spent.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How can I move to a higher tier?</div>
                <div class="faq-answer">Increase your monthly spending to reach the next tier. Each tier unlocks new benefits and higher points multipliers.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How do I redeem offers?</div>
                <div class="faq-answer">Click the "Redeem Now" button on any available offer. Redeemed offers can be used during checkout.</div>
            </div>
            <div class="faq-item">
                <div class="faq-question">What happens if I don't use my offers?</div>
                <div class="faq-answer">Offers may expire if not used within their validity period. Check the offer details for expiration dates.</div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.faq-question').forEach(function(q) {
            q.addEventListener('click', function() {
                var answer = this.nextElementSibling;
                answer.style.display = (answer.style.display === 'block') ? 'none' : 'block';
            });
        });
    });
    </script>
    <?php include('footer.php'); ?>
</body>
</html> 