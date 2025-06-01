<?php
	// Start the session.
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

	// Get all products.
	include('../connection.php');
	$query = "SELECT * FROM products ORDER BY created_at DESC";
	$result = mysqli_query($con, $query);
	$products = mysqli_fetch_all($result, MYSQLI_ASSOC);

	// Get unique categories for tabs
	$categories = array_unique(array_map(function($p) { return ucfirst($p['category']); }, $products));
	sort($categories);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Products - Admin Dashboard</title>
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <style>
        .dashboard_content {
            padding: 20px;
        }
        .product-management {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .product-table th, .product-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .product-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .product-table tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .add-product-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            display: none;
        }
        .product-img {
            max-width: 80px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            margin-right: 16px;
            color: #333;
        }
        .section_header {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-tabs {
            margin-bottom: 20px;
        }
        .category-tab {
            background: #f5f5f5;
            border: none;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 15px;
            cursor: pointer;
            color: #333;
            transition: background 0.2s, color 0.2s;
        }
        .category-tab.active, .category-tab:hover {
            background: #4CAF50;
            color: #fff;
        }
        .btn-filter { background:#f5f5f5; border:1px solid #ccc; padding:8px 18px; border-radius:4px; font-weight:500; cursor:pointer; transition:background 0.2s; }
        .btn-filter.active, .btn-filter:hover { background:#4CAF50; color:#fff; border-color:#388E3C; }
    </style>
</head>
<body>
    <div id="dashboardMainContainer">
        <?php include('partials/app-sidebar.php') ?>
        <div class="dasboard_content_container" id="dasboard_content_container">
            <?php include('partials/app-topnav.php') ?>
            <div class="dashboard_content">
                <div class="product-management">
                    <div class="header-actions">
                        <div class="section_header">
                            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fa fa-bars"></i></button>
                            <h2>Product Management</h2>
                        </div>
                        <a href="product-add.php" class="add-product-btn">Add New Product</a>
                    </div>
                    <!-- Category Tabs -->
                    <div class="category-tabs" style="display:flex;gap:10px;margin-bottom:20px;">
                        <button class="category-tab active" data-category="all">All</button>
                        <?php foreach($categories as $cat): ?>
                            <button class="category-tab" data-category="<?php echo strtolower($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
                        <?php endforeach; ?>
                        <button class="category-tab" data-category="featured">Featured Products</button>
                        <button class="category-tab" data-category="dailybest">Daily Best Sells</button>
                    </div>
                            <?php
                                if(isset($_SESSION['response'])){
                                    $response_message = $_SESSION['response']['message'];
                                    $is_success = $_SESSION['response']['success'];
                            ?>
                        <div class="message" id="message" style="display:block;background-color:<?= $is_success ? '#d4edda' : '#f8d7da' ?>;color:<?= $is_success ? '#155724' : '#721c24' ?>;border:1px solid <?= $is_success ? '#c3e6cb' : '#f5c6cb' ?>;">
                                    <?= $response_message ?>
                                </div>
                            <?php 
                                unset($_SESSION['response']);
                                } 
                            ?>
                    <table class="product-table" id="productTable">
                                        <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Description</th>
                                <th>Category</th>
                                                <th>Actions</th>
                                <th>Featured</th>
                                <th>Daily Best</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($products as $product): ?>
                            <tr data-category="<?php echo strtolower($product['category']); ?>">
                                                <td>
                                                    <img src="../<?php echo htmlspecialchars($product['img']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                         class="product-img">
                                                </td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($product['category'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="product-edit.php?id=<?php echo $product['id']; ?>" class="btn btn-edit scroll-save">Edit</a>
                                        <form method="GET" action="delete_product.php" style="display: inline;" onsubmit="return confirmDelete(<?php echo $product['id']; ?>);">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <input type="checkbox" class="featured-toggle" data-product-id="<?php echo $product['id']; ?>" <?php if(isset($product['featured']) && $product['featured']) echo 'checked'; ?>>
                                </td>
                                <td>
                                    <div class="daily-best-dropdown">
                                        <button type="button" class="daily-best-toggle-btn">Select <span style="font-size:12px;">&#9660;</span></button>
                                        <div class="daily-best-menu" style="display:none;position:absolute;background:#fff;border:1px solid #ccc;padding:8px 16px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.08);z-index:10;min-width:170px;">
                                            <label style="display:flex;align-items:center;margin-bottom:4px;gap:6px;">
                                                <input type="checkbox" class="daily-best-toggle" data-type="featured" data-product-id="<?php echo $product['id']; ?>">
                                                Featured
                                                <input type="number" min="0" max="100" step="1" class="daily-best-discount" data-type="featured" data-product-id="<?php echo $product['id']; ?>" value="10" style="width:50px;">%
                                            </label>
                                            <label style="display:flex;align-items:center;margin-bottom:4px;gap:6px;">
                                                <input type="checkbox" class="daily-best-toggle" data-type="popular" data-product-id="<?php echo $product['id']; ?>">
                                                Popular
                                                <input type="number" min="0" max="100" step="1" class="daily-best-discount" data-type="popular" data-product-id="<?php echo $product['id']; ?>" value="10" style="width:50px;">%
                                            </label>
                                            <label style="display:flex;align-items:center;gap:6px;">
                                                <input type="checkbox" class="daily-best-toggle" data-type="new" data-product-id="<?php echo $product['id']; ?>">
                                                New
                                                <input type="number" min="0" max="100" step="1" class="daily-best-discount" data-type="new" data-product-id="<?php echo $product['id']; ?>" value="10" style="width:50px;">%
                                            </label>
                                        </div>
                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
    <script src="js/script.js"></script>
    <script>
        // Show message if it exists
        const message = document.getElementById('message');
        if(message) {
            setTimeout(() => {
                message.style.display = 'none';
            }, 3000);
        }
        function confirmDelete(productId) {
            return confirm('Are you sure you want to delete this product? This action cannot be undone.');
        }
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            if(sidebar) sidebar.classList.toggle('open');
        }
        // Category tab filtering
        const tabs = document.querySelectorAll('.category-tab');
        const rows = document.querySelectorAll('#productTable tbody tr');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const cat = this.dataset.category;
                rows.forEach(row => {
                    if(cat === 'all') {
                        row.style.display = '';
                    } else if (cat === 'featured') {
                        const featured = row.querySelector('.featured-toggle');
                        row.style.display = (featured && featured.checked) ? '' : 'none';
                    } else if (cat === 'dailybest') {
                        const dailyBest = row.querySelector('.daily-best-toggle:checked');
                        row.style.display = dailyBest ? '' : 'none';
                    } else {
                        row.style.display = (row.dataset.category === cat) ? '' : 'none';
                    }
                });
            });
        });
        function updateDailyBestCheckboxes(dailyBestData) {
            document.querySelectorAll('.daily-best-toggle').forEach(function(checkbox) {
                const pid = checkbox.dataset.productId;
                const type = checkbox.dataset.type;
                checkbox.checked = dailyBestData[type] && dailyBestData[type].map(String).includes(pid);
            });
            // Set discount values
            if (window.dailyBestDiscounts) {
                document.querySelectorAll('.daily-best-discount').forEach(function(input) {
                    const pid = input.dataset.productId;
                    const type = input.dataset.type;
                    if (window.dailyBestDiscounts[type] && window.dailyBestDiscounts[type][pid] !== undefined) {
                        input.value = window.dailyBestDiscounts[type][pid];
                    } else {
                        input.value = 10;
                    }
                });
            }
            // Disable featured checkbox for products in any daily best type
            document.querySelectorAll('.featured-toggle').forEach(function(featuredCheckbox) {
                const pid = featuredCheckbox.dataset.productId;
                let inDailyBest = false;
                ['featured','popular','new'].forEach(function(type) {
                    if (dailyBestData[type] && dailyBestData[type].map(String).includes(pid)) {
                        inDailyBest = true;
                    }
                });
                featuredCheckbox.disabled = inDailyBest;
            });
        }
        function enforceMaxPerType(dailyBestData) {
            ['featured','popular','new'].forEach(type => {
                const checked = dailyBestData[type] ? dailyBestData[type].length : 0;
                document.querySelectorAll('.daily-best-toggle[data-type="'+type+'"]:not(:checked)').forEach(cb => {
                    cb.disabled = checked >= 3;
                });
            });
        }
        function fetchDailyBestStatus() {
            fetch('get-daily-best-status.php')
                .then(res => res.json())
                .then(data => {
                    window.dailyBestDiscounts = data.discounts || {};
                    updateDailyBestCheckboxes(data);
                    enforceMaxPerType(data);
                });
        }
        function enforceFeaturedDailyBestExclusivity() {
            document.querySelectorAll('tr').forEach(function(row) {
                const featuredCheckbox = row.querySelector('.featured-toggle');
                const dailyBestCheckboxes = row.querySelectorAll('.daily-best-toggle');
                const dailyBestDiscounts = row.querySelectorAll('.daily-best-discount');
                if (!featuredCheckbox || dailyBestCheckboxes.length === 0) return;
                // If featured is checked, disable all daily best
                if (featuredCheckbox.checked) {
                    dailyBestCheckboxes.forEach(cb => { cb.disabled = true; });
                    dailyBestDiscounts.forEach(inp => { inp.disabled = true; });
                } else {
                    // If any daily best is checked, disable featured
                    let anyDailyBest = false;
                    dailyBestCheckboxes.forEach(cb => { if (cb.checked) anyDailyBest = true; });
                    // featuredCheckbox.disabled = anyDailyBest; // Now handled in updateDailyBestCheckboxes
                    dailyBestCheckboxes.forEach(cb => { cb.disabled = false; });
                    dailyBestDiscounts.forEach(inp => { inp.disabled = false; });
                    if (anyDailyBest) {
                        dailyBestDiscounts.forEach(inp => { if (!inp.closest('label').querySelector('.daily-best-toggle').checked) inp.disabled = true; });
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown logic
            document.querySelectorAll('.daily-best-toggle-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('.daily-best-menu').forEach(menu => menu.style.display = 'none');
                    const menu = btn.nextElementSibling;
                    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
                });
            });
            // Prevent menu from closing when interacting inside it
            document.querySelectorAll('.daily-best-menu').forEach(function(menu) {
                menu.addEventListener('mousedown', function(e) {
                    e.stopPropagation();
                });
                menu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
            document.addEventListener('click', function(e) {
                // Only close if click is outside any .daily-best-dropdown
                if (!e.target.closest('.daily-best-dropdown')) {
                    document.querySelectorAll('.daily-best-menu').forEach(menu => menu.style.display = 'none');
        }
            });
            fetchDailyBestStatus();
            document.querySelectorAll('.daily-best-toggle').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    enforceFeaturedDailyBestExclusivity();
                    const productId = this.dataset.productId;
                    const type = this.dataset.type;
                    const checked = this.checked ? 1 : 0;
                    const discountInput = document.querySelector('.daily-best-discount[data-type="'+type+'"][data-product-id="'+productId+'"]');
                    const discount = discountInput ? parseInt(discountInput.value) || 0 : 10;
                    fetch('toggle-daily-best.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'product_id=' + encodeURIComponent(productId) + '&type=' + encodeURIComponent(type) + '&checked=' + checked + '&discount=' + discount
                    })
                    .then(res => res.json())
                    .then(data => {
                        fetchDailyBestStatus();
                        // Visual feedback for discount input
                        if (discountInput && checked) {
                            if (data.success) {
                                discountInput.style.background = '#d4edda';
                                setTimeout(() => { discountInput.style.background = ''; }, 600);
                            } else {
                                discountInput.style.background = '#f8d7da';
                                setTimeout(() => { discountInput.style.background = ''; }, 1200);
                            }
                        }
                    });
                });
            });
            document.querySelectorAll('.daily-best-discount').forEach(function(input) {
                input.addEventListener('change', function() {
                    enforceFeaturedDailyBestExclusivity();
                    const productId = this.dataset.productId;
                    const type = this.dataset.type;
                    const discount = parseInt(this.value) || 0;
                    const checkbox = document.querySelector('.daily-best-toggle[data-type="'+type+'"][data-product-id="'+productId+'"]');
                    if (checkbox && checkbox.checked) {
                        fetch('toggle-daily-best.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'product_id=' + encodeURIComponent(productId) + '&type=' + encodeURIComponent(type) + '&checked=1&discount=' + discount
                        })
                        .then(res => res.json())
                        .then(data => {
                            fetchDailyBestStatus();
                            // Visual feedback
                            if (data.success) {
                                input.style.background = '#d4edda';
                                setTimeout(() => { input.style.background = ''; }, 600);
                            } else {
                                input.style.background = '#f8d7da';
                                setTimeout(() => { input.style.background = ''; }, 1200);
                            }
                        });
                    }
                });
            });
            // Featured toggle logic
            document.querySelectorAll('.featured-toggle').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    enforceFeaturedDailyBestExclusivity();
                    const productId = this.dataset.productId;
                    const featured = this.checked ? 1 : 0;
                    fetch('toggle-featured.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'product_id=' + encodeURIComponent(productId) + '&featured=' + featured
                    });
                });
            });
            enforceFeaturedDailyBestExclusivity();
            // Save scroll position when clicking Edit
            document.querySelectorAll('.scroll-save').forEach(btn => {
                btn.addEventListener('click', function() {
                    sessionStorage.setItem('productScrollPos', window.scrollY);
                });
            });
            // Restore scroll position on page load
            window.addEventListener('DOMContentLoaded', function() {
                const scrollPos = sessionStorage.getItem('productScrollPos');
                if (scrollPos !== null) {
                    window.scrollTo(0, parseInt(scrollPos, 10));
                    sessionStorage.removeItem('productScrollPos');
                }
            });
        });
    </script>
</body>
</html>
