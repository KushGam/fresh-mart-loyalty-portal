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
            case 'add_offer':
                $title = mysqli_real_escape_string($con, $_POST['title']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $offer_type = mysqli_real_escape_string($con, $_POST['offer_type']);
                $points_multiplier = mysqli_real_escape_string($con, $_POST['points_multiplier']);
                $minimum_purchase = mysqli_real_escape_string($con, $_POST['minimum_purchase']);
                $discount_amount = mysqli_real_escape_string($con, $_POST['discount_amount']);
                $discount_type = mysqli_real_escape_string($con, $_POST['discount_type']);
                $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
                $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
                $loyalty_tier = isset($_POST['loyalty_tier']) ? mysqli_real_escape_string($con, $_POST['loyalty_tier']) : '';
                $usage_limit = !empty($_POST['usage_limit']) ? mysqli_real_escape_string($con, $_POST['usage_limit']) : null;
                
                try {
                    $query = "INSERT INTO personalized_offers (
                        title, description, offer_type, points_multiplier, 
                        minimum_purchase, discount_amount, discount_type,
                        start_date, end_date, loyalty_tier, usage_limit
                    ) VALUES (
                        '$title', '$description', '$offer_type', '$points_multiplier',
                        '$minimum_purchase', '$discount_amount', '$discount_type',
                        '$start_date', '$end_date', '$loyalty_tier', " . ($usage_limit ? "'$usage_limit'" : "NULL") . "
                    )";
                    
                    if(mysqli_query($con, $query)) {
                        $_SESSION['success'] = "Offer added successfully!";
                        header('Location: manage_offers.php');
                        exit();
                    } else {
                        throw new Exception(mysqli_error($con));
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error adding offer: " . $e->getMessage();
                }
                break;

            case 'delete_offer':
                $offer_id = intval($_POST['offer_id']);
                
                // Start transaction
                $con->begin_transaction();
                
                try {
                    // First update any orders that reference this offer to remove the reference
                    $update_orders = "UPDATE orders SET 
                                     offer_id = NULL,
                                     offer_discount = 0,
                                     offer_points_multiplier = 1.00
                                     WHERE offer_id = ?";
                    $stmt = $con->prepare($update_orders);
                    if (!$stmt) {
                        throw new Exception("Error preparing orders update: " . $con->error);
                    }
                    $stmt->bind_param("i", $offer_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating orders: " . $stmt->error);
                    }

                    // Delete from offer_usage
                    $delete_usage = "DELETE FROM offer_usage WHERE offer_id = ?";
                    $stmt = $con->prepare($delete_usage);
                    if (!$stmt) {
                        throw new Exception("Error preparing offer_usage delete: " . $con->error);
                    }
                    $stmt->bind_param("i", $offer_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting from offer_usage: " . $stmt->error);
                    }

                    // Delete from user_offers
                    $delete_user_offers = "DELETE FROM user_offers WHERE offer_id = ?";
                    $stmt = $con->prepare($delete_user_offers);
                    if (!$stmt) {
                        throw new Exception("Error preparing user_offers delete: " . $con->error);
                    }
                    $stmt->bind_param("i", $offer_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting from user_offers: " . $stmt->error);
                    }
                    
                    // Finally delete the offer
                    $delete_offer = "DELETE FROM personalized_offers WHERE id = ?";
                    $stmt = $con->prepare($delete_offer);
                    if (!$stmt) {
                        throw new Exception("Error preparing offer delete: " . $con->error);
                    }
                    $stmt->bind_param("i", $offer_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting offer: " . $stmt->error);
                    }
                    
                    // If we got here, everything worked
                    $con->commit();
                    $_SESSION['success'] = "Offer deleted successfully!";
                    
                } catch (Exception $e) {
                    // Rollback on any error
                    $con->rollback();
                    $_SESSION['error'] = "Error deleting offer: " . $e->getMessage();
                }
                break;

            case 'toggle_offer':
                $offer_id = intval($_POST['offer_id']);
                $is_active = intval($_POST['is_active']);
                $query = "UPDATE personalized_offers SET is_active = ? WHERE id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("ii", $is_active, $offer_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Offer status updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating offer status: " . $stmt->error;
                }
                break;

            case 'edit_offer':
                $offer_id = intval($_POST['offer_id']);
                $title = mysqli_real_escape_string($con, $_POST['title']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $offer_type = mysqli_real_escape_string($con, $_POST['offer_type']);
                $points_multiplier = floatval($_POST['points_multiplier']);
                $minimum_purchase = floatval($_POST['minimum_purchase']);
                $discount_amount = floatval($_POST['discount_amount']);
                $discount_type = mysqli_real_escape_string($con, $_POST['discount_type']);
                $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
                $end_date = mysqli_real_escape_string($con, $_POST['end_date']);
                $loyalty_tier = isset($_POST['loyalty_tier']) ? mysqli_real_escape_string($con, $_POST['loyalty_tier']) : NULL;
                $usage_limit = isset($_POST['usage_limit']) ? intval($_POST['usage_limit']) : NULL;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $query = "UPDATE personalized_offers SET 
                    title = ?, 
                    description = ?, 
                    offer_type = ?, 
                    points_multiplier = ?, 
                    minimum_purchase = ?, 
                    discount_amount = ?, 
                    discount_type = ?, 
                    start_date = ?, 
                    end_date = ?, 
                    loyalty_tier = ?,
                    usage_limit = ?,
                    is_active = ?
                    WHERE id = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("sssdddssssiii", $title, $description, $offer_type, $points_multiplier, 
                                $minimum_purchase, $discount_amount, $discount_type, $start_date, $end_date, 
                                $loyalty_tier, $usage_limit, $is_active, $offer_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Offer updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating offer: " . $stmt->error;
                }
                break;
        }
    }
}

// Get all offers
$query = "SELECT * FROM personalized_offers ORDER BY created_at DESC";
$offers_result = mysqli_query($con, $query);

// Get loyalty tiers for dropdown
$query = "SELECT * FROM loyalty_tiers ORDER BY spending_required ASC";
$tiers_result = mysqli_query($con, $query);
$loyalty_tiers = mysqli_fetch_all($tiers_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Offers - Freshmart Admin</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <style>
        .offers-container {
            padding: 20px;
        }

        .add-offer-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .offers-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        .offers-table th,
        .offers-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .offers-table th {
            background: #f5f5f5;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #4CAF50;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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
                <div class="offers-container">
                    <h2>Manage Personalized Offers</h2>
                    
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

                    <?php if(isset($_SESSION['info'])): ?>
                        <div class="alert alert-info">
                            <?php 
                                echo $_SESSION['info'];
                                unset($_SESSION['info']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="add-offer-form">
                        <h3>Add New Offer</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_offer">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>Offer Type</label>
                                    <select name="offer_type" required>
                                        <option value="birthday">Birthday Reward</option>
                                        <option value="loyalty_tier">Loyalty Tier Reward</option>
                                        <option value="special">Special Offer</option>
                                        <option value="seasonal">Seasonal Offer</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Points Multiplier</label>
                                    <input type="number" name="points_multiplier" step="0.01" value="1.00" required>
                                </div>
                                <div class="form-group">
                                    <label>Minimum Purchase</label>
                                    <input type="number" name="minimum_purchase" step="0.01" value="0.00" required>
                                </div>
                                <div class="form-group">
                                    <label>Discount Amount</label>
                                    <input type="number" name="discount_amount" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Discount Type</label>
                                    <select name="discount_type" required>
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" required>
                                </div>
                                <div class="form-group">
                                    <label>Loyalty Tier (Optional)</label>
                                    <select name="loyalty_tier">
                                        <option value="">All Tiers</option>
                                        <?php foreach ($loyalty_tiers as $tier): ?>
                                            <option value="<?php echo htmlspecialchars($tier['tier_name']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($tier['tier_name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Usage Limit (Optional)</label>
                                    <input type="number" name="usage_limit" min="0" placeholder="Leave empty for unlimited uses">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Offer</button>
                        </form>
                    </div>

                    <!-- Edit Offer Modal -->
                    <div id="editOfferModal" class="modal">
                        <div class="modal-content">
                            <span class="close">&times;</span>
                            <h2>Edit Offer</h2>
                            <form id="editOfferForm" method="POST">
                                <input type="hidden" name="action" value="edit_offer">
                                <input type="hidden" name="offer_id" id="edit_offer_id">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>TITLE</label>
                                        <input type="text" name="title" id="edit_title" required>
                                    </div>
                                    <div class="form-group">
                                        <label>TYPE</label>
                                        <select name="offer_type" id="edit_offer_type" required>
                                            <option value="birthday">Birthday</option>
                                            <option value="loyalty_tier">Loyalty Tier</option>
                                            <option value="special">Special</option>
                                            <option value="seasonal">Seasonal</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>POINTS MULTIPLIER</label>
                                        <input type="number" name="points_multiplier" id="edit_points_multiplier" step="0.01" min="1" value="1.00">
                                    </div>
                                    <div class="form-group">
                                        <label>MINIMUM PURCHASE</label>
                                        <input type="number" name="minimum_purchase" id="edit_minimum_purchase" step="0.01" min="0" value="0.00">
                                    </div>
                                    <div class="form-group">
                                        <label>DISCOUNT AMOUNT</label>
                                        <input type="number" name="discount_amount" id="edit_discount_amount" step="0.01" required>
                                    </div>
                                    <div class="form-group">
                                        <label>DISCOUNT TYPE</label>
                                        <select name="discount_type" id="edit_discount_type" required>
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed Amount</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>START DATE</label>
                                        <input type="date" name="start_date" id="edit_start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label>END DATE</label>
                                        <input type="date" name="end_date" id="edit_end_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label>LOYALTY TIER (OPTIONAL)</label>
                                        <select name="loyalty_tier" id="edit_loyalty_tier">
                                            <option value="">All Tiers</option>
                                            <?php foreach ($loyalty_tiers as $tier): ?>
                                                <option value="<?php echo htmlspecialchars($tier['tier_name']); ?>">
                                                    <?php echo htmlspecialchars($tier['tier_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>USAGE LIMIT (OPTIONAL)</label>
                                        <input type="number" name="usage_limit" id="edit_usage_limit" min="0" placeholder="Leave empty for unlimited uses">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>DESCRIPTION</label>
                                    <textarea name="description" id="edit_description" rows="4"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>STATUS</label>
                                    <label class="status-toggle">
                                        <input type="checkbox" name="is_active" id="edit_is_active">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Offer</button>
                            </form>
                        </div>
                    </div>

                    <table class="offers-table">
                        <thead>
                            <tr>
                                <th>TITLE</th>
                                <th>TYPE</th>
                                <th>DISCOUNT</th>
                                <th>VALID PERIOD</th>
                                <th>TIER</th>
                                <th>LIMIT</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($offer = mysqli_fetch_assoc($offers_result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($offer['title']); ?></td>
                                    <td><?php echo htmlspecialchars($offer['offer_type']); ?></td>
                                    <td><?php 
                                        if ($offer['discount_type'] === 'percentage') {
                                            echo htmlspecialchars($offer['discount_amount']) . '%';
                                        } else {
                                            echo '$' . htmlspecialchars(number_format($offer['discount_amount'], 2));
                                        }
                                    ?></td>
                                    <td><?php echo htmlspecialchars($offer['start_date'] . ' - ' . $offer['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($offer['loyalty_tier'] ?? 'All Tiers'); ?></td>
                                    <td><?php echo $offer['usage_limit'] ? htmlspecialchars($offer['usage_limit']) : 'Unlimited'; ?></td>
                                    <td>
                                        <button class="btn btn-primary" onclick="editOffer(<?php echo htmlspecialchars(json_encode($offer)); ?>)">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_offer">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this offer?')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
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
    <script>
    // Get the modal
    var modal = document.getElementById("editOfferModal");
    var span = document.getElementsByClassName("close")[0];

    function toggleOfferStatus(offerId, isActive) {
        // Create form data
        var formData = new FormData();
        formData.append('action', 'toggle_offer');
        formData.append('offer_id', offerId);
        formData.append('is_active', isActive ? 1 : 0);

        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Reload the page to show updated status
            window.location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating offer status');
        });
    }

    function editOffer(offer) {
        // Populate the form with offer data
        document.getElementById('edit_offer_id').value = offer.id;
        document.getElementById('edit_title').value = offer.title;
        document.getElementById('edit_offer_type').value = offer.offer_type;
        document.getElementById('edit_points_multiplier').value = offer.points_multiplier;
        document.getElementById('edit_minimum_purchase').value = offer.minimum_purchase;
        document.getElementById('edit_discount_amount').value = offer.discount_amount;
        document.getElementById('edit_discount_type').value = offer.discount_type;
        document.getElementById('edit_start_date').value = offer.start_date;
        document.getElementById('edit_end_date').value = offer.end_date;
        document.getElementById('edit_loyalty_tier').value = offer.loyalty_tier || '';
        document.getElementById('edit_usage_limit').value = offer.usage_limit || '';
        document.getElementById('edit_description').value = offer.description;
        document.getElementById('edit_is_active').checked = offer.is_active == 1;
        
        // Show the modal
        modal.style.display = "block";
    }

    // Close modal when clicking the x
    span.onclick = function() {
        modal.style.display = "none";
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Show/hide birth date field based on offer type
    document.querySelector('select[name="offer_type"]').addEventListener('change', function() {
        const birthDateGroup = document.querySelector('.birth-date-group');
        if (this.value === 'birthday') {
            birthDateGroup.style.display = 'block';
        } else {
            birthDateGroup.style.display = 'none';
        }
    });

    // Initialize on page load
    if (document.querySelector('select[name="offer_type"]').value === 'birthday') {
        document.querySelector('.birth-date-group').style.display = 'block';
    }
    </script>
</body>
</html> 