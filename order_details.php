<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

if (!isset($_GET['order'])) {
    header("Location: orders.php");
    exit();
}

$order_number = $_GET['order'];

// Get order details
$query = "SELECT o.*, cd.address, cd.city, cd.state, cd.zip_code, cd.phone 
          FROM orders o 
          LEFT JOIN customer_details cd ON o.user_id = cd.user_id 
          WHERE o.order_number = ? AND o.user_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("si", $order_number, $user_data['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Get order items
$query = "SELECT oi.*, p.name as product_name, p.image as product_image 
          FROM order_items oi 
          LEFT JOIN products p ON oi.product_id = p.id 
          WHERE oi.order_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-link {
            color: #666;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .back-link:hover {
            color: #333;
        }

        .order-status-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .status-label {
            color: #666;
            margin-bottom: 10px;
        }

        .status-bar {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0;
        }

        .status-step {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .status-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .status-text {
            font-size: 14px;
            color: #666;
        }

        .status-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }

        .completed .status-icon {
            background: #4CAF50;
            border-color: #4CAF50;
            color: white;
        }

        .completed .status-text {
            color: #4CAF50;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .info-card p {
            color: #666;
            margin: 5px 0;
        }

        .items-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .item-card {
            display: flex;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .item-card:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 20px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-quantity {
            color: #666;
            font-size: 14px;
        }

        .item-price {
            font-weight: 600;
            color: #333;
        }

        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-weight: 600;
            color: #333;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="order-details-container">
        <div class="page-header">
            <a href="orders.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Orders
            </a>
            <h1>Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
        </div>

        <div class="order-status-header">
            <div class="status-label">Order Status</div>
            <div class="status-bar">
                <div class="status-line"></div>
                <div class="status-step completed">
                    <div class="status-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="status-text">Order Placed</div>
                </div>
                <div class="status-step <?php echo $order['status'] !== 'pending' ? 'completed' : ''; ?>">
                    <div class="status-icon">
                        <i class="fas <?php echo $order['status'] !== 'pending' ? 'fa-check' : 'fa-clock'; ?>"></i>
                    </div>
                    <div class="status-text">Processing</div>
                </div>
                <div class="status-step <?php echo $order['status'] === 'delivered' ? 'completed' : ''; ?>">
                    <div class="status-icon">
                        <i class="fas <?php echo $order['status'] === 'delivered' ? 'fa-check' : 'fa-truck'; ?>"></i>
                    </div>
                    <div class="status-text">Out for Delivery</div>
                </div>
                <div class="status-step <?php echo $order['status'] === 'delivered' ? 'completed' : ''; ?>">
                    <div class="status-icon">
                        <i class="fas <?php echo $order['status'] === 'delivered' ? 'fa-check' : 'fa-home'; ?>"></i>
                    </div>
                    <div class="status-text">Delivered</div>
                </div>
            </div>
        </div>

        <div class="order-info-grid">
            <div class="info-card">
                <h3>Delivery Address</h3>
                <p><?php echo htmlspecialchars($order['address']); ?></p>
                <p><?php echo htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ' ' . htmlspecialchars($order['zip_code']); ?></p>
                <p>Phone: <?php echo htmlspecialchars($order['phone']); ?></p>
            </div>
            <div class="info-card">
                <h3>Order Information</h3>
                <p>Order Date: <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                <p>Expected Delivery: <?php echo date('F j, Y', strtotime($order['delivery_time'])); ?></p>
                <p>Payment Method: Credit Card</p>
            </div>
        </div>

        <div class="items-list">
            <?php foreach ($order_items as $item): ?>
                <div class="item-card">
                    <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    <div class="item-price">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping Fee</span>
                <span>$<?php echo number_format($order['shipping_fee'], 2); ?></span>
            </div>
            <div class="total-row">
                <span>Total</span>
                <span>$<?php echo number_format($order['total_amount'] + $order['shipping_fee'], 2); ?></span>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html> 