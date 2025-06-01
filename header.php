<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once("connection.php");
include_once("functions.php");

// Check if user is logged in and get user data
$user_data = check_login($con);

// Get cart count
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;

// Include tier information
include_once("get_user_tier.php");
$loyalty_info = getUserTierInfo($con, $_SESSION['user']['id']);
?>
<!-- Header Section -->
<header>
    <div class="top-bar">
        <div class="logo">
            <img src="logo.png" alt="Fresh Mart Logo" class="logo-image">
            <div class="logo-text">
                <h1>Fresh Mart</h1>
                <p>GROCERY</p>
            </div>
        </div>
        <div class="search-bar">
            <div class="custom-select-wrapper">
                <button class="custom-select-button">All Categories <i class="fas fa-caret-down"></i></button>
            </div>
            <input type="text" placeholder="Search for items...">
            <button class="search-button"><i class="fas fa-search"></i></button>
        </div>
        <div class="user-info">
            <a href="wishlist.php" class="icon-link">
                <i class="fas fa-heart"></i> Wishlist
            </a>
            <a href="cart.php" class="icon-link cart-icon">
                <i class="fas fa-shopping-cart"></i> My cart 
                <span class="cart-count-badge"><?php echo $cart_count; ?></span>
            </a>
            <div class="user-account">
                <a href="profile.php">
                <i class="fas fa-circle user-circle-icon" style="color: #FFB300;"></i>
                <span class="user-name"><?php echo htmlspecialchars($user_data['user_name']); ?></span>
                <i class="fas fa-caret-down"></i>
            </div>
            <a href="logout.php" class="logout-btn">Log out</a>
        </div>
    </div>
</header>

<style>
.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 30px;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.icon-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #333;
    gap: 5px;
}

.cart-count-badge {
    background: #4CAF50;
    color: white;
    padding: 2px 6px;
    border-radius: 50%;
    font-size: 12px;
    margin-left: 5px;
}

.user-account {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.logout-btn {
    padding: 8px 16px;
    background: #333;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background: #555;
}

.user-circle-icon {
    font-size: 20px;
}

.user-name {
    margin: 0 8px;
    color: #333;
}

.profile {
    position: relative;
    display: inline-block;
}

.profile-dropdown {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.profile-dropdown:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 200px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 4px;
    z-index: 1000;
}

.profile-dropdown:hover .dropdown-content {
    display: block;
}

.dropdown-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: background-color 0.3s;
}

.dropdown-content a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.dropdown-content a:hover {
    background-color: #f5f5f5;
}
</style> 