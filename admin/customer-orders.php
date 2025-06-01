<?php
// Start the session
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1 || !isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
    if(isset($_SESSION['admin_user'])) {
        unset($_SESSION['admin_user']);
    }
    header('location: ../login.php');
    exit();
}

$user = $_SESSION['admin_user'];
include("../connection.php");

// Fetch all orders with reward, promotion, and offer usage
$query = "SELECT o.*, u.user_name, u.email, 
    COALESCE(o.rewards_discount, 0) as rewards_discount, 
    COALESCE(o.promo_discount, 0) as promo_discount, 
    COALESCE(o.offer_discount, 0) as offer_discount, 
    po.title as offer_title, 
    p.title as promo_title
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN personalized_offers po ON o.offer_id = po.id
LEFT JOIN promotions p ON (
    SELECT promotion_id FROM order_promotions op WHERE op.order_id = o.id LIMIT 1
) = p.id
ORDER BY o.created_at DESC";
$result = mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Order Details - Admin Dashboard</title>
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <style>
        .dashboard_content { padding: 20px; }
        .user-management {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .order-table th, .order-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .order-table th { background-color: #f8f9fa; font-weight: 600; }
        .order-table tr:hover { background-color: #f5f5f5; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="user-management">
                    <div class="header-actions">
                        <h2>Customer Order Details</h2>
                    </div>
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Total Amount</th>
                                <th>Placed At</th>
                                <th>Reward Discount Used</th>
                                <th>Promotion Used</th>
                                <th>Offer Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['order_number']) ?></td>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>$<?= number_format($row['total_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                <td><?= $row['rewards_discount'] > 0 ? '$' . number_format($row['rewards_discount'], 2) : '-' ?></td>
                                <td>
                                    <?php
                                    if (!empty($row['promo_title']) && $row['promo_discount'] > 0) {
                                        echo htmlspecialchars($row['promo_title']) . ' ($' . number_format($row['promo_discount'], 2) . ')';
                                    } elseif (!empty($row['promo_title'])) {
                                        echo htmlspecialchars($row['promo_title']);
                                    } elseif ($row['promo_discount'] > 0) {
                                        echo '$' . number_format($row['promo_discount'], 2);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if (!empty($row['offer_title']) && $row['offer_discount'] > 0) {
                                        echo htmlspecialchars($row['offer_title']) . ' ($' . number_format($row['offer_discount'], 2) . ')';
                                    } elseif (!empty($row['offer_title'])) {
                                        echo htmlspecialchars($row['offer_title']);
                                    } elseif ($row['offer_discount'] > 0) {
                                        echo '$' . number_format($row['offer_discount'], 2);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html> 