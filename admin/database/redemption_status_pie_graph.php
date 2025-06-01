<?php

require_once(__DIR__ . '/../../connection.php');

// Query to get redemption status counts
$query = "SELECT 
            status,
            COUNT(*) as count
          FROM store_redemptions 
          GROUP BY status";

$result = mysqli_query($con, $query);
$results = [];

// Colors for different statuses
$colors = [
    'pending' => '#FFA500',    // Orange for pending
    'redeemed' => '#4CAF50',   // Green for redeemed
    'expired' => '#F44336'     // Red for expired
];

while($row = mysqli_fetch_assoc($result)) {
    $results[] = [
        'name' => ucfirst($row['status']),
        'y' => (int)$row['count'],
        'color' => $colors[$row['status']]
    ];
}

// If no data found, return empty array
if(empty($results)) {
    $results = [
        ['name' => 'No Redemptions', 'y' => 0, 'color' => '#CCCCCC']
    ];
} 