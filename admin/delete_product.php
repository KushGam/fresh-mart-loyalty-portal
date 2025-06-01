<?php
// Start the session
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1) {
    header('location: ../login.php');
    exit();
}

// Include database connection
include('../connection.php');

if(isset($_GET['id'])) {
    $product_id = mysqli_real_escape_string($con, $_GET['id']);
    
    // Begin transaction
    mysqli_begin_transaction($con);
    
    try {
        // First check if the product exists
        $check_query = "SELECT * FROM products WHERE id = ?";
        $stmt = mysqli_prepare($con, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 0) {
            throw new Exception("Product not found.");
        }
        
        // Check if product is referenced in order_items
        $check_orders = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $stmt = mysqli_prepare($con, $check_orders);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $order_result = mysqli_stmt_get_result($stmt);
        $order_count = mysqli_fetch_assoc($order_result)['count'];
        
        if($order_count > 0) {
            throw new Exception("Cannot delete product as it is referenced in orders. Consider marking it as out of stock instead.");
        }
        
        // If all checks pass, delete the product
        $delete_query = "DELETE FROM products WHERE id = ?";
        $stmt = mysqli_prepare($con, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting product.");
        }
        
        // Commit transaction
        mysqli_commit($con);
        
        $_SESSION['response'] = array(
            'success' => true,
            'message' => 'Product deleted successfully.'
        );
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($con);
        
        $_SESSION['response'] = array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
} else {
    $_SESSION['response'] = array(
        'success' => false,
        'message' => 'Invalid request.'
    );
}

// Redirect back to product view page
header('location: product-view.php');
exit();
?> 