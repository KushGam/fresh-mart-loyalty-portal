<?php 
session_start();

	include("connection.php");
	include("functions.php");
	include("get_user_tier.php");

	// Check if user is logged in and verified
	if (!isset($_SESSION['user']) || !isset($_SESSION['user']['is_verified']) || !$_SESSION['user']['is_verified']) {
		// Only unset customer session if it exists
		if (isset($_SESSION['user'])) {
			unset($_SESSION['user']);
		}
		header("Location: login.php");
		exit();
	}

	$user_data = $_SESSION['user'];
	$loyalty_info = getUserTierInfo($con, $user_data['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Mart - Homepage</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="filter.js"></script>
    <style>
    .toast-message {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        display: none;
        z-index: 1000;
        font-size: 14px;
        width: fit-content;
        height: fit-content;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateX(100%);
        text-align: center;
        white-space: nowrap;
    }

    .toast-message.show {
        display: block;
        animation: toast-in-right 0.7s;
    }

    @keyframes toast-in-right {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .cart-count-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff0000;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 15px;
        text-align: center;
    }

    .cart-icon {
        position: relative;
        display: inline-block;
    }

    .add-to-cart {
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .add-to-cart:hover {
        background-color: #45a049;
    }

    .categories {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .category-filters {
        display: flex;
        gap: 20px;
        justify-content: flex-end;
    }

    .filter-link {
        text-decoration: none;
        color: #333;
        padding: 8px 16px;
        border-radius: 20px;
        transition: background-color 0.3s;
    }

    .filter-link:hover, .filter-link.active {
        background-color: #4CAF50;
        color: white;
    }

    .category-grid {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        gap: 20px;
        justify-content: flex-start;
        align-items: stretch;
        margin-bottom: 20px;
    }

    .category-card {
        min-width: 100px;
        max-width: 175px;
        flex: 0 0 auto;
        background: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .category-card:hover {
        transform: translateY(-5px);
    }

    .category-card img {
        width: 100%;
        height: 150px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    .category-card h3 {
        font-size: 18px;
        margin: 10px 0 5px;
        color: #333;
    }

    .category-card p {
        color: #666;
        margin: 0;
    }

    .points-badge {
        background: #4CAF50;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        margin-left: 5px;
    }

    .nav-item .fas.fa-crown {
        color: #FFD700;
    }

    .nav-item .fas.fa-gift {
        color: #FF4081;
    }

    .bronze { color: #cd7f32; }
    .silver { color: #c0c0c0; }
    .gold { color: #ffd700; }
    .platinum { color: #e5e4e2; }

    .products-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .product-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
    }

    .product-card img {
        width: 100%;
        height: 200px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    .product-details {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        padding: 15px 0;
    }

    .product-details h3 {
        font-size: 18px;
        margin: 0 0 5px;
        color: #333;
    }

    .category {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .rating {
        color: #ffc107;
        margin-bottom: 10px;
    }

    .product-price {
        font-size: 24px;
        font-weight: bold;
        color: #2E7D32;
        margin: 0 0 15px 0;
    }

    .old-price {
        text-decoration: line-through;
        color: #999;
        font-size: 16px;
        margin-left: 8px;
    }

    .product-brand {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .add-to-cart {
        width: 100%;
        padding: 12px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background-color 0.3s ease;
    }

    .add-to-cart:hover {
        background: #388E3C;
    }

    .add-to-cart i {
        font-size: 18px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 0 10px;
    }

    .category-nav {
        display: flex;
        gap: 20px;
    }

    .category-nav a {
        color: #666;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 15px;
        transition: all 0.3s;
    }

    .category-nav a:hover,
    .category-nav a.active {
        color: #4CAF50;
        background: #e8f5e9;
    }

    .price-cart-container {
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        z-index: 1;
    }

    .badge-save {
        background-color: #FF6B6B;
        color: white;
    }

    .badge-best {
        background-color: #FF9F43;
        color: white;
    }

    .sold-info {
        color: #666;
        font-size: 13px;
        margin: 5px 0;
    }

    .daily-best-sells-section .product-card {
        position: relative;
        margin-bottom: 20px;
    }

    .daily-best-sells-section .price-cart-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .daily-best-sells-section .product-brand {
        margin: 0;
        color: #666;
        font-size: 13px;
    }

    /* Sidebar styles for left side */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0; /* Sidebar on the left */
        width: 320px;
        height: 100%;
        background: #fff;
        box-shadow: 2px 0 8px rgba(0,0,0,0.08);
        z-index: 2000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        padding: 0;
        overflow-y: auto;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 24px;
        border-bottom: 1px solid #eee;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #333;
    }
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .category-list li a {
        display: block;
        padding: 16px 24px;
        color: #333;
        text-decoration: none;
        border-bottom: 1px solid #f2f2f2;
        transition: background 0.2s;
    }
    .category-list li a:hover {
        background: #f5f5f5;
        color: #4CAF50;
    }
    #chatbot-header:hover .chatbot-tooltip {
        visibility: visible !important;
        opacity: 1 !important;
    }
    .featured-header-row {
        display: flex;
        align-items: right;
        gap: 330px;
        margin-bottom: 24px;
    }
    .category-nav {
        display: flex;
        gap: 1px;
    }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header>
        <div class="top-bar">
            <div class="logo">
                <!-- Use the image for the logo -->
                <img src="logo.png" alt="Fresh Mart Logo" class="logo-image">
                <div class="logo-text">
                    <h1>Fresh Mart</h1>
                    <p>GROCERY</p>
                </div>
            </div>
            <div class="search-bar">
                <!-- Dropdown for All Categories -->
                <div class="custom-select-wrapper">
                    <button class="custom-select-button">All Categories <i class="fas fa-caret-down"></i></button>
                </div>
                <!-- Search Input Field -->
                <input type="text" placeholder="Search for items...">
                <!-- Search Button with Icon -->
                <button class="search-button"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-info">
                <a href="#" class="icon-link">
                    <i class="fas fa-heart"></i> Wishlist
                </a>
                <a href="cart.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-cart"></i> My cart 
                    <span class="cart-count-badge">0</span>
                    <i class="fas fa-caret-down"></i>
                </a>
                <div class="profile">
                    <a href="profile.php">
                    <i class="fas fa-circle user-circle-icon" style="color: #FFB300;"></i>
                    <span><?php echo $user_data['user_name']; ?><i class="fas fa-caret-down"></i></span>
                    </a>
                </div>
                <a href="logout.php" class="logout-button">Log out</a>

            </div>
        </div>
    </header>
    <!-- Navigation Section -->
    <nav class="navigation-bar">
        <!-- Toggle Sidebar Button -->
        <button class="browse-button" onclick="toggleSidebar()">
            <i class="fas fa-th"></i> Browse All Categories
        </button>

        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="homepage.php" class="nav-item">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="rewards.php" class="nav-item">
                <i class="fas fa-fire"></i> Reward Points
            </a>
            <a href="loyalty-dashboard.php" class="nav-item">
                <i class="fas fa-crown"></i> 
                <?php echo $loyalty_info['tier_name']; ?> Member
                
            </a>
            <a href="my-offers.php" class="nav-item">
                <i class="fas fa-gift"></i> My Offers
            </a>
            <a href="promotions.php" class="nav-item">
                <i class="fas fa-percentage"></i> Promotions
            </a>
        </div>

        <!-- Phone Number on the Right -->
        <div class="contact-info">
            <a href="#" class="nav-item">
                <i class="fas fa-phone-alt"></i> <span class="phone-number">1234-6969</span> 24/7 support center
            </a>
        </div>
    </nav>

    <!-- Category Sidebar -->
<div id="categorySidebar" class="sidebar">
    <div class="sidebar-header">
        <h3>Browse Categories</h3>
        <button onclick="toggleSidebar()" class="close-btn">&times;</button>
    </div>
    <ul class="category-list">
        <li><a href="Category.php?filter=all">All</a></li>
        <li><a href="Category.php?filter=fruits">Fruits</a></li>
        <li><a href="Category.php?filter=vegetables">Vegetables</a></li>
        <li><a href="Category.php?filter=meat">Meat</a></li>
        <li><a href="Category.php?filter=dairy">Dairy</a></li>
        <li><a href="Category.php?filter=bakery">Bakery</a></li>
        <li><a href="Category.php?filter=beverages">Beverages</a></li>
    </ul>
</div>

<!-- Sidebar Toggle Script -->
<script>
function toggleSidebar() {
    document.getElementById("categorySidebar").classList.toggle("open");
}
</script>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Don't miss our daily amazing deals.</h2>
            <p>Handpicked goodness, just a click away.</p>
        </div>
        </section>
        
    <!-- Categories Section -->
    <section class="categories">
        <div class="section-header">
            <h2>Explore Categories</h2>
            <div class="category-filters">
                <a href="Category.php?filter=all" class="filter-link ">All</a>
                <a href="Category.php?filter=fruits" class="filter-link">Fruits</a>
                <a href="Category.php?filter=vegetables" class="filter-link">Vegetables</a>
                <a href="Category.php?filter=meat"" class="filter-link">Meat</a>
                <a href="Category.php?filter=dairy" class="filter-link">Dairy</a>
                <a href="Category.php?filter=bakery" class="filter-link">Bakery</a>
                <a href="Category.php?filter=beverages" class="filter-link">Beverages</a>
            </div>
        </div>

        <div class="category-grid">
            <a href="category.php?filter=fruits" class="category-card">
                <img src="Images Assets/fruits.jpg" alt="Fruits">
                <h3>Fruits</h3>
                <p>5 Items</p>
            </a>

            <a href="category.php?filter=vegetables" class="category-card">
                <img src="Images Assets/vegetables.png" alt="Vegetables">
                <h3>Vegetables</h3>
                <p>5 Items</p>
            </a>

            <a href="category.php?filter=meat" class="category-card">
                <img src="Images Assets/meat.jpg" alt="Meat">
                <h3>Meat</h3>
                <p>5 Items</p>
            </a>

            <a href="category.php?filter=dairy" class="category-card">
                <img src="Images Assets/dairy.jpg" alt="Dairy">
                <h3>Dairy</h3>
                <p>5 Items</p>
            </a>

            <a href="category.php?filter=bakery" class="category-card">
                <img src="Images Assets/bakery.jpg" alt="Bakery">
                <h3>Bakery</h3>
                <p>5 Items</p>
            </a>

            <a href="category.php?filter=beverages" class="category-card">
                <img src="Images Assets/beverage.jpg" alt="Beverages">
                <h3>Beverages</h3>
                <p>5 Items</p>
            </a>
        </div>
    </section>

     <!-- Featured Products Section -->
     <section class="featured-products">
        <div class="container">
            <div class="featured-header-row">
                <h2>Featured Products</h2>
                <nav class="category-nav" id="featured-category-nav">
                    <a href="#" class="active" data-category="all">All</a>
                    <a href="#" data-category="vegetables">Vegetables</a>
                    <a href="#" data-category="fruits">Fruits</a>
                    <a href="#" data-category="meat">Meat</a>
                    <a href="#" data-category="dairy">Dairy</a>
                    <a href="#" data-category="bakery">Bakery</a>
                    <a href="#" data-category="beverages">Beverages</a>
                </nav>
            </div>
            <div class="products-row" id="featured-products-list">
                <!-- Products will be loaded here by AJAX -->
            </div>
        </div>
    </section>

    <!-- Free Delivery Section -->
    <section class="free-delivery-section">
        <div class="container">
            <!-- Content Container -->
            <div class="delivery-content-wrapper">
                <!-- Left Side: Free Delivery Text -->
                <div class="delivery-text">
                    <span class="badge">Free delivery</span>
                    <h2>Free delivery over $100</h2>
                    <p>Shop $100 product and get goods delivery across the county.</p>
                    <button class="shop-now-button">Shop Now <span>&#8594;</span></button>
                </div>
                <!-- Right Side: Background Pattern Image -->
                <div class="delivery-pattern">
                    <img src="Images Assets/Background image.png" alt="Pattern Image">
                </div>
            </div>
        </div>
    </section>

    <!-- Daily Best Sells Section -->
    <section class="daily-best-sells-section">
        <div class="container">
            <!-- Section Header -->
            <div class="section-header">
                <h2>Daily Best Sells</h2>
                <nav class="category-nav" id="daily-best-nav">
                    <a href="#" class="active" data-type="featured">Featured</a>
                    <a href="#" data-type="popular">Popular</a>
                    <a href="#" data-type="new">New</a>
                </nav>
            </div>
            <!-- Content Wrapper with Product Cards -->
            <div class="products-row" id="daily-best-list">
                <!-- Products will be loaded here by AJAX -->
            </div>
        </div>
    </section>

    <!-- Top Sells, Top Rated, and Trending Items Section -->
<section class="product-categories-section">
    <div class="container">
        <!-- Product Category Rows -->
        <div class="product-category-row">
            <!-- Top Sells -->
            <div class="product-category">
                <h3>Top Sells</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Orange.jpg" alt="Orange 1kg">
                        <h4>Orange 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Spinach.jpg" alt="Asparagus 1kg">
                        <h4>Asparagus 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Strawberry.jpg" alt="Strawberry 1kg">
                        <h4>Strawberry 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Rated -->
            <div class="product-category">
                <h3>Top Rated</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Peach.jpg" alt="Peach 1kg">
                        <h4>Peach 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Broccoli.jpg" alt="Broccoli 1kg">
                        <h4>Broccoli 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Apples.jpg" alt="Apple 1kg">
                        <h4>Apple 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trending Items -->
            <div class="product-category">
                <h3>Trending Items</h3>
                <div class="product-list">
                    <!-- Product Card 1 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Carrot.jpg" alt="Carrot 1kg">
                        <h4>Carrot 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 2 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Potato.jpg" alt="Potato 1kg">
                        <h4>Potato 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                    <!-- Product Card 3 -->
                    <div class="mini-product-card">
                        <img src="Images Assets/Orange.jpg" alt="Orange 1kg">
                        <h4>Orange 1kg</h4>
                        <p class="product-price">$2.00 <span class="old-price">$3.99</span></p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            (4)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Benefits Section -->
<section class="benefits-section">
    <div class="container">
        <div class="benefits-row">
            <!-- Benefit 1: Best Prices & Deals -->
            <div class="benefit-item">
                <img src="Images Assets/Best Prices & Deals.png" alt="Best Prices & Deals Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Best Prices & Deals</h4>
                    <p>Don't miss our daily amazing deals and prices</p>
                </div>
            </div>
            <!-- Benefit 2: Refundable -->
            <div class="benefit-item">
                <img src="Images Assets/Refundable.png" alt="Refundable Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Refundable</h4>
                    <p>If your items have damage, we agree to refund it</p>
                </div>
            </div>
            <!-- Benefit 3: Free Delivery -->
            <div class="benefit-item">
                <img src="Images Assets/Free delivery.png" alt="Free Delivery Icon" class="benefit-icon">
                <div class="benefit-info">
                    <h4>Free Delivery</h4>
                    <p>Do purchase over $50 and get free delivery anywhere</p>
                </div>
            </div>
        </div>
    </div>
</section>
     <!-- Footer Section -->
     <footer class="footer">
        <!-- Top Section -->
        <div class="footer-top">
            <!-- Left Section: Logo and Contact Information -->
            <div class="footer-left">
                <img src="logo.png" alt="Fresh Mart Logo" class="footer-logo">
                <h2>Fresh Mart</h2>
                <p>GROCERY</p>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Address: 619 Reid Road</li>
                    <li><i class="fas fa-phone-alt"></i> Call Us: 1233-6969</li>
                    <li><i class="fas fa-envelope"></i> Email: Freshmart@contact.com</li>
                    <li><i class="fas fa-clock"></i> Work hours: 8:00 - 10:00, Sunday - Thursday</li>
                </ul>
            </div>

            <!-- Center Section: Account Links -->
            <div class="footer-section">
                <h3>Account</h3>
                <ul>
                    <li>Wishlist</li>
                    <li>Cart</li>
                    <li>Track Order</li>
                    <li>Shipping Details</li>
                </ul>
            </div>

            <!-- Useful Links Section -->
            <div class="footer-section">
                <h3>Useful links</h3>
                <ul>
                    <li>About Us</li>
                    <li>Contact</li>
                    <li>Hot deals</li>
                    <li>Promotions</li>
                    <li>New products</li>
                </ul>
            </div>

            <!-- Help Center Section -->
            <div class="footer-section">
                <h3>Help Center</h3>
                <ul>
                    <li>Payments</li>
                    <li>Refund</li>
                    <li>Checkout</li>
                    <li>Shipping</li>
                    <li>Q&A</li>
                    <li>Privacy Policy</li>
                </ul>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="footer-bottom">
            <!-- Left Section: Copyright -->
            <div class="footer-left-bottom">
                <p>© 2022, All rights reserved</p>
            </div>

            <!-- Center Section: Payment Methods -->
            <div class="footer-center">
                <img src="Images Assets/visa.png" alt="Visa" class="payment-icon">
                <img src="Images Assets/mastercard.png" alt="MasterCard" class="payment-icon">
                <img src="Images Assets/maestro.png" alt="Maestro" class="payment-icon">
                <img src="Images Assets/amex.png" alt="American Express" class="payment-icon">
            </div>

            <!-- Right Section: Social Media Icons -->
            <div class="footer-right">
                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
    </footer>
    <div class="toast-message" id="toastMessage"></div>
    <script>
    function showToast(message) {
        const toast = document.getElementById('toastMessage');
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function updateCartCount(count) {
        const cartCount = document.querySelector('.cart-count-badge');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }

    function addToCart(productId, productName, price) {
        fetch('cart_operations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add&product_id=${productId}&product_name=${encodeURIComponent(productName)}&price=${price}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Product added to cart!');
                updateCartCount(data.cart_count);
            } else {
                showToast('Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error adding product to cart');
        });
    }

    // Initialize cart count on page load
    fetch('cart_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
        }
    });

    // Update all add to cart buttons
    function setupAddToCartButtons() {
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.onclick = () => {
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                const price = parseFloat(button.dataset.productPrice);
                addToCart(productId, productName, price);
            };
        });
    }
    </script>
    <!-- Chatbot Widget -->
    <div id="chatbot-container" style="position:fixed;bottom:80px;right:30px;z-index:9999;">
      <div id="chatbot-header" style="background:#4CAF50;padding:0;border-radius:50%;width:60px;height:60px;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.15);position:relative;">
        <i class="fas fa-robot" style="color:#fff;font-size:32px;"></i>
        <span class="chatbot-tooltip" style="visibility:hidden;opacity:0;position:absolute;bottom:70px;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:6px 14px;border-radius:6px;font-size:15px;white-space:nowrap;transition:opacity 0.2s;pointer-events:none;">Chat with us</span>
      </div>
      <div id="chatbot-body" style="display:none;background:#fff;border:1px solid #ccc;border-radius:0 0 10px 10px;width:320px;max-height:400px;overflow-y:auto;">
        <div id="chatbot-messages" style="padding:10px;height:300px;overflow-y:auto;"></div>
        <form id="chatbot-form" style="display:flex;border-top:1px solid #eee;">
          <input type="text" id="chatbot-input" placeholder="Type your message..." style="flex:1;padding:10px;border:none;">
          <button type="submit" style="background:#4CAF50;color:#fff;border:none;padding:10px 16px;">Send</button>
        </form>
      </div>
    </div>
    <script>
    function addMessage(sender, text) {
      var messages = document.getElementById('chatbot-messages');
      var div = document.createElement('div');
      div.innerHTML = '<b>' + sender + ':</b> ' + text;
      messages.appendChild(div);
      messages.scrollTop = messages.scrollHeight;
    }
    function addBotWelcome() {
      addMessage('Bot', `Hello! How can I help you today?<br>Here are some things you can ask me:<br>
        <button class='chatbot-suggest' data-q="What are your store hours?">What are your store hours?</button><br>
        <button class='chatbot-suggest' data-q="How do I track my order?">How do I track my order?</button><br>
        <button class='chatbot-suggest' data-q="What is your return policy?">What is your return policy?</button><br>
        <button class='chatbot-suggest' data-q="How do I access the loyalty dashboard?">How do I access the loyalty dashboard?</button><br>
        <button class='chatbot-suggest' data-q="How do I redeem my points?">How do I redeem my points?</button>`);
      setTimeout(function() {
        document.querySelectorAll('.chatbot-suggest').forEach(function(btn) {
          btn.onclick = function() {
            var q = btn.getAttribute('data-q');
            addMessage('You', q);
            fetch('chatbot-response.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: 'message=' + encodeURIComponent(q)
            })
            .then(res => res.text())
            .then(reply => addMessage('Bot', reply));
          };
        });
      }, 100);
    }
    document.getElementById('chatbot-header').onclick = function() {
      var body = document.getElementById('chatbot-body');
      var wasClosed = body.style.display === 'none';
      body.style.display = wasClosed ? 'block' : 'none';
      if (wasClosed && document.getElementById('chatbot-messages').children.length === 0) {
        addBotWelcome();
      }
    };
    // Add form submission handler for chatbot input
    document.getElementById('chatbot-form').onsubmit = function(e) {
      e.preventDefault();
      var input = document.getElementById('chatbot-input');
      var msg = input.value.trim();
      if (!msg) return;
      addMessage('You', msg);
      input.value = '';
      fetch('chatbot-response.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message=' + encodeURIComponent(msg)
      })
      .then(res => res.text())
      .then(reply => addMessage('Bot', reply));
    };
    </script>
    <script>
    function loadFeaturedProducts(category) {
        const list = document.getElementById('featured-products-list');
        list.innerHTML = '<p style="text-align:center;">Loading...</p>';
        fetch('featured-products.php?category=' + encodeURIComponent(category))
            .then(res => res.text())
            .then(html => {
                list.innerHTML = html;
                setupAddToCartButtons();
            });
    }
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('#featured-category-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                loadFeaturedProducts(this.dataset.category);
            });
        });
        loadFeaturedProducts('all'); // Load default
        setupAddToCartButtons();
    });
    </script>
    <script>
    function loadDailyBest(type) {
        const list = document.getElementById('daily-best-list');
        list.innerHTML = '<p style="text-align:center;">Loading...</p>';
        fetch('daily-best-sells.php?type=' + encodeURIComponent(type))
            .then(res => res.text())
            .then(html => {
                list.innerHTML = html;
                setupAddToCartButtons();
            });
    }
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('#daily-best-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                loadDailyBest(this.dataset.type);
            });
        });
        loadDailyBest('featured'); // Load default
    });
    </script>
</body>
</html>