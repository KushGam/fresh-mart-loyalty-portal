<?php
$message = strtolower(trim($_POST['message'] ?? ''));
$response = "Sorry, I didn't understand that. Can you rephrase?";

// Greeting
if (preg_match('/\bhello\b/', $message) || preg_match('/\bhi\b/', $message) || preg_match('/\bhey\b/', $message)) {
    $response = "Hello! 👋 How can I help you today?";
}
// Store hours
elseif (strpos($message, 'hours') !== false || strpos($message, 'open') !== false) {
    $response = "We're open 8:00 - 10:00, Sunday - Thursday.";
}
// Location/address
elseif (strpos($message, 'location') !== false || strpos($message, 'address') !== false) {
    $response = "Our address is 619 Reid Road.";
}
// Contact info
elseif (strpos($message, 'contact') !== false || strpos($message, 'phone') !== false) {
    $response = "You can call us at 1233-6969 or email Freshmart@contact.com.";
}
// Refund
elseif (strpos($message, 'refund') !== false) {
    $response = "If your items have damage, we agree to refund it. Please contact support.";
}
// Delivery
elseif (strpos($message, 'delivery') !== false) {
    $response = "We offer free delivery for orders over $50!";
}
// Rewards/points
elseif (strpos($message, 'earn points') !== false || strpos($message, 'rewards') !== false || strpos($message, 'points') !== false) {
    $response = "You earn 1 point for every $1 spent. 100 points = $1 redeemable value. Minimum 1000 points required to redeem.";
}
// Loyalty dashboard
elseif (strpos($message, 'loyalty dashboard') !== false) {
    $response = "The Loyalty Dashboard is your personal area to track your points, tier, and available offers.";
}
// Higher tier
elseif (strpos($message, 'higher tier') !== false) {
    $response = "You can move to a higher tier by earning more points through purchases. Each tier has its own requirements.";
}
// Redeem offers
elseif (strpos($message, 'redeem offers') !== false) {
    $response = "You can redeem offers instantly during checkout or in store at your next visit.";
}
// Unused offers
elseif (strpos($message, "don't use my offers") !== false || strpos($message, 'do not use my offers') !== false) {
    $response = "Unused offers may expire after their validity period. Check your dashboard for offer details.";
}
// Thank you
elseif (strpos($message, 'thank') !== false) {
    $response = "You're welcome! 😊";
}
// Add more rules as needed below

// Default fallback
// (already set as $response)
echo $response; 