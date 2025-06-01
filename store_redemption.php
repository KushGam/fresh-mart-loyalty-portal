<?php
session_start();
include("connection.php");
include("functions.php");
require 'vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

$user_data = check_login($con);

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

// Generate 16-digit unique code
function generateUniqueCode() {
    $code = '';
    for ($i = 0; $i < 16; $i++) {
        $code .= mt_rand(0, 9);
    }
    return $code;
}

// Check if user has points
$query = "SELECT points FROM rewards WHERE user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$result = $stmt->get_result();
$rewards = $result->fetch_assoc();

// Check if user has minimum required points
if (!$rewards || $rewards['points'] < $min_points_redeem) {
    $_SESSION['error'] = "You need a minimum of " . number_format($min_points_redeem) . " points to redeem. You currently have " . number_format($rewards['points'] ?? 0) . " points.";
    header("Location: rewards.php");
    exit();
}

// Generate and store redemption code
$redemption_code = generateUniqueCode();

// Calculate redeemable amount based on points and conversion rate
$points_to_redeem = $rewards['points'];
$redeemable_points = floor($points_to_redeem / $points_to_amount) * $points_to_amount;
$amount = $redeemable_points * $points_to_dollars_rate;

// Store the redemption code
$query = "INSERT INTO store_redemptions (user_id, redemption_code, points_redeemed, amount, status, expires_at) 
          VALUES (?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 30 DAY))";
$stmt = $con->prepare($query);
$stmt->bind_param("isid", $user_data['id'], $redemption_code, $redeemable_points, $amount);

if (!$stmt->execute()) {
    error_log("Error generating redemption code: " . $stmt->error);
    $_SESSION['error'] = "Error generating redemption code";
    header("Location: rewards.php");
    exit();
}

// Deduct only the redeemed points
$query = "UPDATE rewards SET points = points - ? WHERE user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("ii", $redeemable_points, $user_data['id']);
if (!$stmt->execute()) {
    error_log("Error deducting points: " . $stmt->error);
    $_SESSION['error'] = "Error deducting points";
    header("Location: rewards.php");
    exit();
}

// Generate Barcode
$generator = new BarcodeGeneratorPNG();
$barcode = $generator->getBarcode($redemption_code, $generator::TYPE_CODE_128);

// Convert barcode to base64 for direct embedding
$barcode_base64 = base64_encode($barcode);

// Send email with redemption code
require_once 'functions/send_redemption_email.php';
error_log("Starting email process for redemption code: " . $redemption_code);
error_log("User email: " . $user_data['email']);
error_log("User data: " . print_r($user_data, true));
error_log("Redemption amount: $" . number_format($amount, 2));

if (empty($user_data['email'])) {
    error_log("Error: User email is empty");
    $_SESSION['email_error'] = "Error: Unable to send email - no email address found. Please take a screenshot of this page for your records.";
} else {
    $email_sent = send_redemption_email($user_data['email'], $redemption_code, $amount);

    if (!$email_sent) {
        error_log("Failed to send redemption email to: " . $user_data['email']);
        $_SESSION['email_error'] = "Note: There was an issue sending the email. Please take a screenshot of this page for your records. If you need the code sent to your email, please contact support.";
    } else {
        error_log("Successfully sent redemption email to: " . $user_data['email']);
    }
}

// After sending the customer store redemption email, send admin notification
require_once 'functions/send_admin_notification_email.php';
$admin_subject = 'Points Redeemed (In-Store) - Redemption Code ' . $redemption_code;
$admin_body = "<h2>Points Redeemed (In-Store)</h2>"
    . "<p><strong>Redemption Code:</strong> {$redemption_code}</p>"
    . "<p><strong>Customer Name:</strong> {$user_data['first_name']} {$user_data['last_name']}</p>"
    . "<p><strong>Email:</strong> {$user_data['email']}</p>"
    . "<p><strong>Points Redeemed:</strong> " . number_format($redeemable_points) . "</p>"
    . "<p><strong>Amount:</strong> $" . number_format($amount, 2) . "</p>"
    . "<p><strong>Expiry Date:</strong> " . date('F j, Y', strtotime('+30 days')) . "</p>";
send_admin_notification_email($admin_subject, $admin_body);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Redemption Code - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .redemption-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            color: #4CAF50;
            font-size: 64px;
            margin-bottom: 20px;
        }

        .redemption-code {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .barcode {
            margin: 30px auto;
            padding: 20px;
            background: white;
            display: inline-block;
        }

        .barcode img {
            max-width: 100%;
            height: auto;
        }

        .instructions {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            text-align: left;
        }

        .continue-shopping {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background: #45a049;
        }

        .amount {
            font-size: 32px;
            color: #4CAF50;
            margin: 20px 0;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="redemption-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Store Redemption Code Generated</h1>
        
        <div class="amount">$<?php echo number_format($amount, 2); ?></div>
        
        <p>Show this code at the store to redeem your points:</p>
        
        <div class="redemption-code">
            <?php echo chunk_split($redemption_code, 4, ' '); ?>
        </div>

        <div class="barcode">
            <img src="data:image/png;base64,<?php echo $barcode_base64; ?>" 
                 alt="Barcode" 
                 style="max-width: 300px; height: auto; display: block; margin: 20px auto;">
        </div>

        <div class="instructions">
            <h3>How to Redeem:</h3>
            <ol>
                <li>Take a screenshot or photo of this barcode</li>
                <li>Show the barcode or code number to the cashier</li>
                <li>The cashier will scan or enter the code to apply your $<?php echo number_format($amount, 2); ?> discount</li>
                <li>Valid for 30 days from today</li>
            </ol>
        </div>

        <?php if (isset($_SESSION['email_error'])): ?>
            <div style="background-color: #fff3e0; padding: 15px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #ff9800;">
                <p style="margin: 0; color: #e65100;"><?php echo $_SESSION['email_error']; ?></p>
            </div>
            <?php unset($_SESSION['email_error']); ?>
        <?php else: ?>
            <p>We've also sent this code to your email for reference.</p>
        <?php endif; ?>

        <a href="homepage.php" class="continue-shopping">Continue Shopping</a>
    </div>

    <?php include('footer.php'); ?>
</body>
</html> 