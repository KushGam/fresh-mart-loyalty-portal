<?php
session_start();
include("../connection.php");

header('Content-Type: application/json');

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Session expired or unauthorized. Please login as admin.']);
    exit();
}

try {
    if (!isset($_POST['code']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $code = mysqli_real_escape_string($con, $_POST['code']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $admin_id = $_SESSION['admin_user']['id'];

    // Begin transaction
    $con->begin_transaction();

    // Update the status and set redeemed_at
    $query = "UPDATE store_redemptions 
              SET status = ?, 
                  redeemed_at = CASE 
                      WHEN ? = 'redeemed' THEN CURRENT_TIMESTAMP 
                      ELSE redeemed_at 
                  END,
                  admin_id = ? 
              WHERE redemption_code = ?";

    $stmt = $con->prepare($query);
    $stmt->bind_param("ssss", $status, $status, $admin_id, $code);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update status");
    }

    // Commit transaction
    $con->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($con->connect_errno) {
        $con->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$con->close(); 