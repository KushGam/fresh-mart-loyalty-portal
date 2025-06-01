<?php
session_start();
include("connection.php");
include("functions.php");

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0,
    'cart_total' => 0
];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the action from POST request
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        // Get product details from POST
        $product_id = intval($_POST['product_id'] ?? 0);
        $product_name = $_POST['product_name'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        // Validate input
        if ($product_id <= 0 || empty($product_name) || $price <= 0) {
            $response['message'] = 'Invalid product data';
            break;
        }

        // Verify product exists in database
        $check_product = "SELECT id, product_name, img FROM products WHERE id = ?";
        $check_stmt = $con->prepare($check_product);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'Product not found in database';
            break;
        }

        $product = $result->fetch_assoc();
        
        // Use the product name and image from database but keep the passed price for discounts
        $product_name = $product['product_name'];
        $product_img = $product['img'];

        // Check if product already exists in cart
        if (isset($_SESSION['cart'][$product_id])) {
            // Update quantity
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            // Add new product to cart
            $_SESSION['cart'][$product_id] = [
                'id' => $product_id,
                'name' => $product_name,
                'img' => $product_img,
                'price' => $price,
                'quantity' => $quantity
            ];
        }

        $response['success'] = true;
        $response['message'] = 'Product added to cart';
        break;

    case 'remove':
        $product_id = $_POST['product_id'] ?? '';
        
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $response['success'] = true;
            $response['message'] = 'Product removed from cart';
        }
        break;

    case 'update':
        $product_id = $_POST['product_id'] ?? '';
        $quantity = intval($_POST['quantity'] ?? 0);

        if (isset($_SESSION['cart'][$product_id]) && $quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            $response['success'] = true;
            $response['message'] = 'Cart updated';
        }
        break;

    case 'get_cart':
        $response['success'] = true;
        break;

    default:
        $response['message'] = 'Invalid action';
        break;
}

// Calculate cart totals
$cart_count = 0;
$cart_total = 0;

foreach ($_SESSION['cart'] as $item) {
    $cart_count += $item['quantity'];
    $cart_total += $item['price'] * $item['quantity'];
}

$response['cart_count'] = $cart_count;
$response['cart_total'] = $cart_total;

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 