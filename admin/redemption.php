<?php
// Start the session
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role_as'] != 1 || !isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
    // Only unset admin session if it exists
    if(isset($_SESSION['admin_user'])) {
        unset($_SESSION['admin_user']);
    }
    header('location: ../login.php');
    exit();
}

// Get user data from session
$user = $_SESSION['admin_user'];

include('../connection.php');

// Fetch all online redemption records with user details
$query_online = "SELECT r.*, u.email, cd.first_name, cd.last_name, o.order_number 
                FROM reward_redemptions r 
                JOIN users u ON r.user_id = u.id 
                JOIN customer_details cd ON u.id = cd.user_id 
                JOIN orders o ON r.order_id = o.id
                ORDER BY r.created_at DESC";
$result_online = mysqli_query($con, $query_online);

// Fetch all in-store redemption records with user details
$query_store = "SELECT r.*, u.email, cd.first_name, cd.last_name 
                FROM store_redemptions r 
                JOIN users u ON r.user_id = u.id 
                JOIN customer_details cd ON u.id = cd.user_id 
                ORDER BY r.created_at DESC";
$result_store = mysqli_query($con, $query_store);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redemption History - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <style>
        .dashboard_content {
            padding: 20px;
        }
        
        .redemption-table {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-redeemed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .tab-buttons {
            margin-bottom: 20px;
        }
        
        .tab-button {
            padding: 10px 20px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            margin-right: 10px;
            border-radius: 4px;
        }
        
        .tab-button.active {
            background: #4CAF50;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="redemption-table">
                    <h2>Redemption History</h2>
                    
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="message success">
                            <?php 
                                echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="message error">
                            <?php 
                                echo $_SESSION['error']; 
                                unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="showTab('online')">Online Redemptions</button>
                        <button class="tab-button" onclick="showTab('store')">In-Store Redemptions</button>
                    </div>
                    
                    <div id="online-tab" class="tab-content active">
                        <h3>Online Redemptions</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th>Order Number</th>
                                    <th>Points Redeemed</th>
                                    <th>Amount Saved</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result_online)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['points_redeemed']); ?></td>
                                        <td>$<?php echo htmlspecialchars($row['amount_saved']); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <?php if(mysqli_num_rows($result_online) == 0): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No online redemption records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="store-tab" class="tab-content">
                        <h3>In-Store Redemptions</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th>Redemption Code</th>
                                    <th>Points Redeemed</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result_store)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['redemption_code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['points_redeemed']); ?></td>
                                        <td>$<?php echo htmlspecialchars($row['amount']); ?></td>
                                        <td>
                                            <span class="status status-<?php echo strtolower($row['status']); ?>">
                                                <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                        <td><?php echo $row['expires_at'] ? date('Y-m-d H:i:s', strtotime($row['expires_at'])) : 'N/A'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                
                                <?php if(mysqli_num_rows($result_store) == 0): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No in-store redemption records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function showTab(tabName) {
            // Update button states
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update tab content visibility
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName + '-tab').classList.add('active');
        }
    </script>
</body>
</html> 