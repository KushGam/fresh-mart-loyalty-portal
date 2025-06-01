<?php
// Get user's current points
$user_id = $_SESSION['user']['id'];
$points_query = "SELECT 
    u.points,
    CASE 
        WHEN u.points >= 10000 THEN 'Diamond'
        WHEN u.points >= 5000 THEN 'Gold'
        WHEN u.points >= 2000 THEN 'Silver'
        ELSE 'Bronze'
    END as tier
FROM users u 
WHERE u.id = ?";

$stmt = $con->prepare($points_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

$points = number_format($user_data['points']);
$tier = $user_data['tier'];
?>

<!-- Replace the existing member display with: -->
<span class="member-status">
    <i class="fa fa-crown"></i> <?php echo $tier; ?> 
    <span class="points-badge"><?php echo $points; ?> Points</span>
</span> 