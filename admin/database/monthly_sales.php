<?php

require_once(__DIR__ . '/../../connection.php');

// Get monthly sales for the last 12 months
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as total_sales
          FROM orders
          WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month ASC";

$result = mysqli_query($con, $query);

$monthly_categories = [];
$monthly_data = [];

while($row = mysqli_fetch_assoc($result)) {
    // Format the month for display (e.g., "Jan 2024")
    $date = date_create($row['month'] . '-01');
    $monthly_categories[] = date_format($date, 'M Y');
    $monthly_data[] = round(floatval($row['total_sales']), 2);
}

// If less than 12 months of data, pad with previous months
if(count($monthly_categories) < 12) {
    $current_count = count($monthly_categories);
    $needed_months = 12 - $current_count;
    
    $last_date = date_create(date('Y-m-01'));
    for($i = 0; $i < $needed_months; $i++) {
        date_sub($last_date, date_interval_create_from_date_string('1 month'));
        array_unshift($monthly_categories, date_format($last_date, 'M Y'));
        array_unshift($monthly_data, 0);
    }
} 