<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Get all orders for the user
$query = "SELECT o.*, COUNT(oi.id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          WHERE o.user_id = ? 
          GROUP BY o.id 
          ORDER BY o.created_at DESC";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_data['id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Fresh Mart</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .page-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-number {
            font-weight: 600;
            color: #333;
        }

        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .order-content {
            padding: 20px;
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-info {
            flex-grow: 1;
        }

        .order-items {
            color: #666;
            margin: 5px 0;
        }

        .delivery-info {
            color: #666;
            margin: 5px 0;
        }

        .order-total {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-left: 20px;
        }

        .view-details {
            display: inline-block;
            padding: 8px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .view-details:hover {
            background-color: #45a049;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-orders i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-orders h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .no-orders p {
            color: #666;
            margin-bottom: 20px;
        }

        .start-shopping {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .start-shopping:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="orders-container">
        <h1 class="page-title">My Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Yet</h3>
                <p>Looks like you haven't placed any orders yet.</p>
                <a href="products.php" class="start-shopping">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="order-status <?php echo $order['status'] === 'pending' ? 'status-pending' : 'status-delivered'; ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                        </span>
                    </div>
                    <div class="order-content">
                        <div class="order-summary">
                            <div class="order-info">
                                <div class="order-items"><?php echo $order['item_count']; ?> items</div>
                                <div class="delivery-info">
                                    Delivery expected by <?php echo date('F j, Y', strtotime($order['delivery_time'])); ?>
                                </div>
                            </div>
                            <div class="order-total">
                                $<?php echo number_format($order['total_amount'] + $order['shipping_fee'], 2); ?>
                            </div>
                        </div>
                        <a href="order_details.php?order=<?php echo $order['order_number']; ?>" class="view-details">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>
</body>
</html> 