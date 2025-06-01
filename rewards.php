<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Enable error logging
error_log("Fetching rewards for user ID: " . $user_data['id']);

// Get loyalty program settings
$settings_query = "SELECT * FROM loyalty_settings LIMIT 1";
$settings_result = mysqli_query($con, $settings_query);
$loyalty_settings = mysqli_fetch_assoc($settings_result);

// Set default values if no settings found
$points_per_dollar = $loyalty_settings['points_per_dollar'] ?? 1.00;
$min_points_redeem = $loyalty_settings['min_points_redeem'] ?? 100;
$points_to_amount = $loyalty_settings['points_to_amount'] ?? 100;

// Calculate conversion rate (how many dollars you get per point)
$points_to_dollars_rate = 1 / $points_to_amount;

// Get user's reward points
$query = "SELECT points FROM rewards WHERE user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$result = $stmt->get_result();

$points = 0;
if($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $points = $row['points'];
    error_log("Retrieved points: " . $points);
} else {
    error_log("No rewards record found for user ID " . $user_data['id'] . ". Creating new record.");
    // Create rewards record for new user with UNIQUE constraint
    $query = "INSERT IGNORE INTO rewards (user_id, points) VALUES (?, 0)";
    $stmt = $con->prepare($query);
    $stmt->bind_param("i", $user_data['id']);
    if($stmt->execute()) {
        error_log("Created new rewards record for user ID " . $user_data['id']);
    } else {
        error_log("Error creating rewards record: " . $stmt->error);
    }
}

// Calculate redeemable dollars based on points and minimum redemption requirement
$redeemable_dollars = 0;
if ($points >= $min_points_redeem) {
    $redeemable_points = floor($points / $points_to_amount) * $points_to_amount;
    $redeemable_dollars = $redeemable_points * $points_to_dollars_rate;
}

// Handle redeem online action
if(isset($_POST['redeem_online']) && $redeemable_dollars > 0) {
    error_log("User ID " . $user_data['id'] . " redeeming $" . $redeemable_dollars);
    $_SESSION['redeem_amount'] = $redeemable_dollars;
    $_SESSION['redeem_timestamp'] = time(); // Add timestamp for expiration check
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Points - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .rewards-section {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .rewards-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .rewards-header img {
            width: 80px;
            margin-bottom: 15px;
        }

        .rewards-header h2 {
            color: #4CAF50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .rewards-content {
            text-align: center;
        }

        .points-display {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .points-display label {
            display: block;
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .points-display .value {
            font-size: 36px;
            color: #4CAF50;
            font-weight: bold;
        }

        .info-box {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }

        .info-box h3 {
            color: #2e7d32;
            margin-bottom: 15px;
            font-size: 20px;
        }

        .info-box p {
            color: #333;
            margin: 10px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
        }

        .info-box p i {
            margin-right: 10px;
            color: #4CAF50;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .reward-button {
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
        }

        .reward-button i {
            margin-right: 8px;
        }

        .continue-shopping {
            background-color: #4CAF50;
            color: white;
        }

        .redeem-online {
            background-color: #2196F3;
            color: white;
        }

        .redeem-store {
            background-color: #FF9800;
            color: white;
        }

        .reward-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .reward-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .progress-bar {
            background: #e0e0e0;
            height: 20px;
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #4CAF50;
            width: <?php echo min(($points % $points_to_amount) / ($points_to_amount / 100), 100); ?>%;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        /* Add styles for disabled state */
        .reward-button.disabled {
            background-color: #cccccc !important;
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
            pointer-events: none;
            opacity: 0.7;
        }

        /* Add tooltip styles */
        [title] {
            position: relative;
        }

        [title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
            z-index: 100;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <main class="rewards-section">
        <div class="rewards-header">
            <img src="Images Assets/Rewards.png" alt="Rewards Icon">
            <h2>Rewards Points</h2>
            <p>Earn while you shop</p>
        </div>

        <div class="rewards-content">
            <div class="points-display">
                <label>Total Rewards Points:</label>
                <div class="value"><?php echo number_format($points); ?></div>
            </div>

            <div class="points-display">
                <label>Total Redeemable Dollars:</label>
                <div class="value">$<?php echo number_format($redeemable_dollars, 2); ?></div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="progress-text">
                <?php 
                $points_to_next = $points_to_amount - ($points % $points_to_amount);
                $dollars_to_next = $points_to_next * $points_to_dollars_rate;
                echo "Earn {$points_to_next} more points for your next \${$dollars_to_next} reward!";
                ?>
            </div>

            <div class="info-box">
                <h3>How Rewards Work</h3>
                <p><i class="fas fa-dollar-sign"></i> Earn <?php echo $points_per_dollar; ?> points for every $1 spent</p>
                <p><i class="fas fa-gift"></i> <?php echo $points_to_amount; ?> points = $1 redeemable value</p>
                <p><i class="fas fa-exclamation-circle"></i> Minimum <?php echo $min_points_redeem; ?> points required to redeem</p>
                <p><i class="fas fa-shopping-cart"></i> Redeem online instantly during checkout</p>
                <p><i class="fas fa-store"></i> Or redeem in store at your next visit</p>
            </div>

            <div class="buttons">
                <a href="homepage.php" class="reward-button continue-shopping">
                    <i class="fas fa-shopping-basket"></i> Continue Shopping
                </a>
                <form method="post" style="display: inline;">
                    <button type="submit" name="redeem_online" class="reward-button redeem-online" 
                            <?php echo ($points < $min_points_redeem) ? 'disabled title="You need ' . number_format($min_points_redeem) . ' points to redeem"' : ''; ?>>
                        <i class="fas fa-shopping-cart"></i> Redeem Online
                    </button>
                </form>
                <?php if ($points < $min_points_redeem): ?>
                    <span class="reward-button redeem-store disabled" 
                          title="You need <?php echo number_format($min_points_redeem); ?> points to redeem">
                        <i class="fas fa-store"></i> Redeem at Store
                    </span>
                <?php else: ?>
                    <a href="store_redemption.php" class="reward-button redeem-store">
                        <i class="fas fa-store"></i> Redeem at Store
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include('footer.php'); ?>
</body>
</html>