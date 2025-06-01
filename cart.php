<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Check if redemption has timed out (30 minutes)
if(isset($_SESSION['redeem_amount']) && isset($_SESSION['redeem_timestamp'])) {
    $timeout = 30 * 60; // 30 minutes in seconds
    if(time() - $_SESSION['redeem_timestamp'] > $timeout) {
        unset($_SESSION['redeem_amount']);
        unset($_SESSION['checkout_redeem_amount']);
        unset($_SESSION['redeem_timestamp']);
        $_SESSION['info'] = "Reward redemption expired. Please redeem again if desired.";
        header("Location: cart.php");
        exit();
    }
}

// Calculate cart total
$subtotal = 0;
$shipping = 5.00; // Default shipping cost

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// Make shipping free for orders over $100
if ($subtotal >= 100) {
    $shipping = 0.00;
}

// Get redeemable amount if set
$redeem_amount = isset($_SESSION['redeem_amount']) ? $_SESSION['redeem_amount'] : 0;

// Verify user still has enough points for redemption
if($redeem_amount > 0) {
    $points_needed = $redeem_amount * 100; // $1 = 100 points
    $verify_points = "SELECT points FROM rewards WHERE user_id = ? AND points >= ?";
    $stmt = $con->prepare($verify_points);
    $stmt->bind_param("ii", $user_data['id'], $points_needed);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If user doesn't have enough points, clear the redemption
    if($result->num_rows === 0) {
        $redeem_amount = 0;
        unset($_SESSION['redeem_amount']);
        unset($_SESSION['checkout_redeem_amount']);
        $_SESSION['error'] = "Rewards discount removed - insufficient points.";
        header("Location: cart.php");
        exit();
    }
}

// Store the redemption amount in the session for checkout
if($redeem_amount > 0) {
    $_SESSION['checkout_redeem_amount'] = $redeem_amount;
}

// Apply redemption if available
$total = $subtotal + $shipping - $redeem_amount;

// Handle redemption cancellation
if(isset($_POST['cancel_redemption'])) {
    unset($_SESSION['redeem_amount']);
    unset($_SESSION['checkout_redeem_amount']);
    header("Location: cart.php");
    exit();
}

// Handle quantity updates
if(isset($_POST['decrease_quantity'])) {
    $product_id = $_POST['product_id'];
    if(isset($_SESSION['cart'][$product_id])) {
        if($_SESSION['cart'][$product_id]['quantity'] > 1) {
            $_SESSION['cart'][$product_id]['quantity']--;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        header("Location: cart.php");
        exit();
    }
}

if(isset($_POST['increase_quantity'])) {
    $product_id = $_POST['product_id'];
    if(isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
        header("Location: cart.php");
        exit();
    }
}

// Handle delete item
if(isset($_POST['delete_item'])) {
    $product_id = $_POST['product_id'];
    if(isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        header("Location: cart.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .cart-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr;
            padding: 15px;
            background-color: #f8f9fa;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            align-items: center;
            background: white;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-info img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 5px;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background: #eee;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .quantity-btn:hover {
            background: #ddd;
        }

        .cart-summary {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            width: 300px;
            margin-left: auto;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
        }

        .checkout-btn:hover {
            background: #45a049;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            font-size: 18px;
            color: #666;
        }

        .rewards-applied {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .rewards-applied h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }

        .cancel-redemption {
            background: none;
            border: none;
            color: #f44336;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
            font-size: 14px;
            margin-top: 10px;
        }

        .cancel-redemption:hover {
            color: #d32f2f;
        }

        .delete-btn {
            color: #dc3545;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: color 0.3s;
        }

        .delete-btn:hover {
            color: #c82333;
        }

        .shipping-message {
            grid-column: 1 / -1;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php 
    include('header.php');
    include('navigation.php');
    ?>

    <div class="cart-container">
        <?php if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <div class="cart-header">
                <div>Product</div>
                <div>Price</div>
                <div>Quantity</div>
                <div>Total</div>
                <div></div>
            </div>

            <?php foreach($_SESSION['cart'] as $product_id => $item): ?>
                <div class="cart-item">
                    <div class="product-info">
                        <?php if (!empty($item['img'])): ?>
                            <img src="<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <img src="Images Assets/<?php echo htmlspecialchars($item['name']); ?>.png" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php endif; ?>
                        <div>
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        </div>
                    </div>
                    <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                    <div class="quantity-controls">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <button type="submit" name="decrease_quantity" class="quantity-btn">-</button>
                        </form>
                        <span><?php echo $item['quantity']; ?></span>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <button type="submit" name="increase_quantity" class="quantity-btn">+</button>
                        </form>
                    </div>
                    <div class="total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    <div>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <button type="submit" name="delete_item" class="delete-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-summary">
                <?php if($redeem_amount > 0): ?>
                    <div class="rewards-applied">
                        <h3>Rewards Applied</h3>
                        <p>You're saving $<?php echo number_format($redeem_amount, 2); ?> with your reward points!</p>
                        <form method="post">
                            <button type="submit" name="cancel_redemption" class="cancel-redemption">Cancel Redemption</button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <?php if ($subtotal >= 100): ?>
                        <span style="color: #4CAF50;">FREE</span>
                    <?php else: ?>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($subtotal < 100): ?>
                    <div class="shipping-message">
                        Free shipping on orders over $100!
                    </div>
                <?php endif; ?>
                <?php if($redeem_amount > 0): ?>
                    <div class="summary-row rewards-discount">
                        <span>Rewards Discount</span>
                        <span>-$<?php echo number_format($redeem_amount, 2); ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row total">
                    <strong>Total</strong>
                    <strong>$<?php echo number_format($total, 2); ?></strong>
                </div>
                
                <!-- Add hidden form fields to pass values to checkout -->
                <form action="checkout.php" method="GET">
                    <input type="hidden" name="subtotal" value="<?php echo $subtotal; ?>">
                    <input type="hidden" name="shipping" value="<?php echo $shipping; ?>">
                    <input type="hidden" name="redeem_amount" value="<?php echo $redeem_amount; ?>">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="homepage.php" class="checkout-btn">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
