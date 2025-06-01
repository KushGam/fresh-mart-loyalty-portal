<?php
session_start();
include("../connection.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || !isset($_SESSION['admin_user']['role_as']) || $_SESSION['admin_user']['role_as'] !== 1) {
    echo json_encode(['valid' => false, 'message' => 'Session expired or unauthorized. Please login as admin.']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_POST['code'])) {
    echo json_encode(['valid' => false, 'message' => 'No code provided']);
    exit();
}

try {
    $code = trim($_POST['code']); // Remove any whitespace
    
    // Log the received code
    error_log("Received code: " . $code);
    
    // Simple query first to debug
    $query = "SELECT *,
              DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as formatted_created_at,
              DATE_FORMAT(redeemed_at, '%Y-%m-%d %H:%i:%s') as formatted_redeemed_at,
              DATE_FORMAT(expires_at, '%Y-%m-%d %H:%i:%s') as formatted_expires_at
              FROM store_redemptions 
              WHERE redemption_code = ?";
    
    $stmt = $con->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $con->error);
    }

    $stmt->bind_param("s", $code);
    
    // Log the actual query for debugging
    error_log("Query: " . str_replace('?', "'$code'", $query));
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    error_log("Number of rows found: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        $redemption = $result->fetch_assoc();
        
        // Format the response data
        $response = [
            'valid' => true,
            'redemption_code' => $redemption['redemption_code'],
            'points_redeemed' => $redemption['points_redeemed'],
            'amount' => $redemption['amount'],
            'status' => $redemption['status'],
            'created_at' => $redemption['formatted_created_at'],
            'redeemed_at' => $redemption['formatted_redeemed_at'],
            'expires_at' => $redemption['formatted_expires_at']
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'valid' => false,
            'message' => 'Invalid or expired redemption code',
            'debug_info' => [
                'code' => $code,
                'query' => str_replace('?', "'$code'", $query)
            ]
        ]);
    }
} catch (Exception $e) {
    error_log("Error in verify_redemption_code.php: " . $e->getMessage());
    echo json_encode([
        'valid' => false,
        'message' => 'An error occurred while verifying the code',
        'debug_info' => $e->getMessage()
    ]);
}
?> 