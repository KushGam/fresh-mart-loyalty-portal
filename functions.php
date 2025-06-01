<?php

function check_login($con)
{
	if(isset($_SESSION['user']) && isset($_SESSION['user']['is_verified']) && $_SESSION['user']['is_verified'] === true)
	{
		return $_SESSION['user'];
	}

	//redirect to login
	header("Location: login.php");
	die;
}

function random_num($length)
{
	$text = "";
	if($length < 5)
	{
		$length = 5;
	}

	$len = rand(4,$length);

	for ($i=0; $i < $len; $i++) { 
		$text .= rand(0,9);
	}

	return $text;
}

function ensure_rewards_record($con, $user_id) {
	// Check if rewards record exists
	$query = "SELECT id FROM rewards WHERE user_id = ? LIMIT 1";
	$stmt = mysqli_prepare($con, $query);
	mysqli_stmt_bind_param($stmt, "i", $user_id);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	if (!$result || mysqli_num_rows($result) == 0) {
		// Create rewards record for new user
		$insert_query = "INSERT INTO rewards (user_id, points) VALUES (?, 0)";
		$stmt = mysqli_prepare($con, $insert_query);
	mysqli_stmt_bind_param($stmt, "i", $user_id);
	mysqli_stmt_execute($stmt);
	error_log("Created new rewards record for user ID: " . $user_id);
	}
}

function update_monthly_spending($con, $user_id) {
	// Calculate user's monthly spending
	$spending_query = "SELECT COALESCE(SUM(total_amount), 0) as monthly_spending
					  FROM orders 
					  WHERE user_id = ? 
					  AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
	$stmt = $con->prepare($spending_query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
	$monthly_spending = $result->fetch_assoc()['monthly_spending'];

	// Update the monthly_spending in customer_details
	$update_query = "UPDATE customer_details SET monthly_spending = ? WHERE user_id = ?";
	$stmt = $con->prepare($update_query);
	$stmt->bind_param("di", $monthly_spending, $user_id);
	$stmt->execute();

	return $monthly_spending;
}

function check_and_update_loyalty_tier($con, $user_id) {
	// First update the monthly spending
	update_monthly_spending($con, $user_id);
	
	// Get user's current tier and monthly spending
	$query = "SELECT cd.monthly_spending, cd.loyalty_tier, lt.spending_required
			  FROM customer_details cd
			  JOIN loyalty_tiers lt ON cd.loyalty_tier = lt.id
			  WHERE cd.user_id = ?";
	
	$stmt = $con->prepare($query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result()->fetch_assoc();
	
	if (!$result) {
		return; // User not found
	}
	
	$monthly_spending = $result['monthly_spending'];
	$current_tier_id = $result['loyalty_tier'];
	
	// Find if user qualifies for a higher tier
	$upgrade_query = "SELECT id, tier_name 
					 FROM loyalty_tiers 
					 WHERE spending_required <= ? 
					 AND spending_required > (
						 SELECT spending_required 
						 FROM loyalty_tiers 
						 WHERE id = ?
					 )
					 ORDER BY spending_required DESC 
					 LIMIT 1";
	
	$stmt = $con->prepare($upgrade_query);
	$stmt->bind_param("di", $monthly_spending, $current_tier_id);
	$stmt->execute();
	$upgrade_result = $stmt->get_result()->fetch_assoc();
	
	// Only upgrade, never downgrade
	if ($upgrade_result) {
		// User qualifies for an upgrade
		$new_tier_id = $upgrade_result['id'];
		
		// Update user's tier
		$update_query = "UPDATE customer_details SET loyalty_tier = ? WHERE user_id = ?";
		$stmt = $con->prepare($update_query);
		$stmt->bind_param("ii", $new_tier_id, $user_id);
		$stmt->execute();
		
		// Set session message for tier upgrade
		$_SESSION['tier_update'] = "Congratulations! You've been upgraded to " . $upgrade_result['tier_name'] . " tier!";
	}
}