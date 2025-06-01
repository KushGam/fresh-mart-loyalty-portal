<?php
if (!isset($_SESSION)) {
    session_start();
}

// Include tier information if not already included
if (!function_exists('getUserTierInfo')) {
    include_once("get_user_tier.php");
}

// Get user's tier info
$loyalty_info = getUserTierInfo($con, $_SESSION['user']['id']);

// Get next tier info
$next_tier_query = "SELECT tier_name, spending_required 
                    FROM loyalty_tiers 
                    WHERE spending_required > ? 
                    ORDER BY spending_required ASC 
                    LIMIT 1";
$stmt = $con->prepare($next_tier_query);
$stmt->bind_param("d", $loyalty_info['monthly_spending']);
$stmt->execute();
$next_tier = $stmt->get_result()->fetch_assoc();

// Determine which page is active
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Section -->
<nav class="navigation-bar">
    <button class="browse-button" onclick="toggleSidebar()">
        <i class="fas fa-th"></i> Browse All Categories
    </button>

    <div class="nav-links">
        <a href="homepage.php" class="nav-item <?php echo $current_page == 'homepage.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="rewards.php" class="nav-item <?php echo $current_page == 'rewards.php' ? 'active' : ''; ?>">
            <i class="fas fa-fire"></i> Reward Points
        </a>
        <a href="loyalty-dashboard.php" class="nav-item <?php echo $current_page == 'loyalty-dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-crown"></i> 
            <?php echo $loyalty_info['tier_name']; ?> Member
        </a>
        <a href="my-offers.php" class="nav-item <?php echo $current_page == 'my-offers.php' ? 'active' : ''; ?>">
            <i class="fas fa-gift"></i> My Offers
        </a>
        <a href="promotions.php" class="nav-item <?php echo $current_page == 'promotions.php' ? 'active' : ''; ?>">
            <i class="fas fa-percentage"></i> Promotions
        </a>
    </div>

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

<style>
.navigation-bar {
    background: #fff;
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.nav-links {
    display: flex;
    gap: 20px;
    align-items: center;
}

.nav-item {
    text-decoration: none;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-item:hover, .nav-item.active {
    background-color: #f5f5f5;
    color: #4CAF50;
}

.nav-item i {
    font-size: 16px;
}

.browse-button {
    background: #4CAF50;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.browse-button:hover {
    background: #45a049;
}

.contact-info {
    color: #666;
    font-size: 14px;
}

.phone-number {
    font-weight: bold;
    color: #4CAF50;
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
</style> 