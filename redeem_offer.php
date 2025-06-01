<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to redeem offers']);
    exit;
}

if (!isset($_POST['offer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid offer']);
    exit;
}

$user_id = $_SESSION['user_id'];
$offer_id = $_POST['offer_id'];

// Get user's birthday and today's date
$user_query = "SELECT DATE_FORMAT(birthday, '%m-%d') as birthday FROM users WHERE id = ?";
$stmt = $con->prepare($user_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
$today = date('m-d');

// Check if offer exists and is valid
$offer_query = "SELECT * FROM personalized_offers WHERE id = ? AND is_active = 1 
                AND start_date <= CURDATE() AND end_date >= CURDATE()";
$stmt = $con->prepare($offer_query);
$stmt->bind_param('i', $offer_id);
$stmt->execute();
$offer_result = $stmt->get_result();
$offer = $offer_result->fetch_assoc();

if (!$offer) {
    echo json_encode(['success' => false, 'message' => 'Offer not found or expired']);
    exit;
}

// Check if it's a birthday offer
if ($offer['offer_type'] === 'birthday') {
    // Verify it's the user's birthday
    if ($today !== $user_data['birthday']) {
        echo json_encode(['success' => false, 'message' => 'Birthday offers can only be redeemed on your birthday']);
        exit;
    }
    
    // Check if already redeemed today
    $check_redemption = "SELECT id FROM offer_redemptions 
                        WHERE user_id = ? AND offer_id = ? 
                        AND DATE(redemption_date) = CURDATE()";
    $stmt = $con->prepare($check_redemption);
    $stmt->bind_param('ii', $user_id, $offer_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already redeemed your birthday offer today']);
        exit;
    }
}

// Record the redemption
$insert_redemption = "INSERT INTO offer_redemptions (user_id, offer_id, redemption_date) 
                     VALUES (?, ?, NOW())";
$stmt = $con->prepare($insert_redemption);
$stmt->bind_param('ii', $user_id, $offer_id);

if ($stmt->execute()) {
    // Add the offer details to the session for use during checkout
    $_SESSION['active_offer'] = [
        'id' => $offer['id'],
        'title' => $offer['title'],
        'discount_amount' => $offer['discount_amount'],
        'discount_type' => $offer['discount_type'],
        'points_multiplier' => $offer['points_multiplier'],
        'minimum_purchase' => $offer['minimum_purchase']
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Offer redeemed successfully! The discount will be applied at checkout.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error redeeming offer. Please try again.']);
}

$con->close();
?> 