<?php
// Start the session.
session_start();
include("../connection.php");

// Debug session
if(!isset($_SESSION['admin_user'])) {
    $_SESSION['error'] = "No admin user session found";
    header('location: ../login.php');
    exit();
}

if($_SESSION['admin_user']['role_as'] != 1) {
    $_SESSION['error'] = "User is not an admin";
    header('location: ../login.php');
    exit();
}

if(!isset($_SESSION['admin_user']['is_verified']) || !$_SESSION['admin_user']['is_verified']) {
    $_SESSION['error'] = "User is not verified";
    header('location: ../login.php');
    exit();
}

// Get user data from session
$user = $_SESSION['admin_user'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_tier':
                $tier_name = mysqli_real_escape_string($con, $_POST['tier_name']);
                $spending_required = floatval($_POST['spending_required']);
                $points_multiplier = floatval($_POST['points_multiplier']);
                $special_benefits = mysqli_real_escape_string($con, $_POST['special_benefits']);
                
                $query = "UPDATE loyalty_tiers SET 
                         spending_required = ?,
                         points_multiplier = ?,
                         special_benefits = ?
                         WHERE tier_name = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("idss", $spending_required, $points_multiplier, $special_benefits, $tier_name);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Tier updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating tier: " . $stmt->error;
                }
                break;
        }
    }
}

// Get tier statistics
$stats_query = "SELECT 
                lt.tier_name,
                lt.spending_required,
                lt.points_multiplier,
                lt.special_benefits,
                COUNT(cd.user_id) as member_count,
                COALESCE(AVG(
                    (SELECT SUM(total_amount) 
                     FROM orders o 
                     WHERE o.user_id = cd.user_id 
                     AND o.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY))
                ), 0) as avg_monthly_spending
                FROM loyalty_tiers lt
                LEFT JOIN customer_details cd ON lt.id = cd.loyalty_tier
                GROUP BY lt.id, lt.tier_name, lt.spending_required, lt.points_multiplier, lt.special_benefits
                ORDER BY lt.spending_required ASC";
$stats_result = mysqli_query($con, $stats_query);
$tier_stats = mysqli_fetch_all($stats_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Loyalty Tiers - Freshmart Admin</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <style>
        .tiers-container {
            padding: 20px;
        }

        .tier-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .tier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .tier-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .tier-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .tier-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-update {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-update:hover {
            background: #45a049;
        }

        .bronze { color: #cd7f32; }
        .silver { color: #c0c0c0; }
        .gold { color: #ffd700; }
        .platinum { color: #e5e4e2; }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <div class="dashboard_topNav">
                <a href="#" id="toggleBtn"><i class="fa fa-navicon"></i></a>
                <a href="#" id="logoutBtn"><i class="fa fa-power-off"></i> Log-out</a>
            </div>
            <div class="dashboard_content">
                <div class="tiers-container">
                    <h2>Manage Loyalty Tiers</h2>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error">
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($tier_stats as $tier): ?>
                        <div class="tier-card">
                            <div class="tier-header">
                                <h3 class="tier-name <?php echo $tier['tier_name']; ?>">
                                    <i class="fas fa-crown"></i>
                                    <?php echo ucfirst($tier['tier_name']); ?> Tier
                                </h3>
                            </div>

                            <div class="tier-stats">
                                <div class="stat-box">
                                    <div class="stat-label">Members</div>
                                    <div class="stat-value"><?php echo number_format($tier['member_count']); ?></div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Avg. Monthly Spending</div>
                                    <div class="stat-value">$<?php echo number_format($tier['avg_monthly_spending'], 2); ?></div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Points Multiplier</div>
                                    <div class="stat-value"><?php echo number_format($tier['points_multiplier'], 2); ?>x</div>
                                </div>
                            </div>

                            <form class="tier-form" method="POST">
                                <input type="hidden" name="action" value="update_tier">
                                <input type="hidden" name="tier_name" value="<?php echo $tier['tier_name']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Required Monthly Spending ($)</label>
                                        <input type="number" name="spending_required" step="0.01"
                                               value="<?php echo $tier['spending_required']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Points Multiplier</label>
                                        <input type="number" name="points_multiplier" step="0.01" 
                                               value="<?php echo $tier['points_multiplier']; ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Special Benefits</label>
                                    <textarea name="special_benefits" rows="2"><?php echo htmlspecialchars($tier['special_benefits']); ?></textarea>
                                </div>
                                <button type="submit" class="btn-update">Update Tier</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html> 