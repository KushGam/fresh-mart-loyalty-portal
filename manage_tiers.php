// Get tier statistics
$stats_query = "SELECT 
                lt.tier_name,
                lt.points_required,
                lt.points_multiplier,
                lt.special_benefits,
                COUNT(DISTINCT CASE 
                    WHEN r.points >= lt.points_required AND 
                         (NOT EXISTS (
                             SELECT 1 FROM loyalty_tiers lt2 
                             WHERE lt2.points_required > lt.points_required 
                             AND lt2.points_required <= r.points
                         ))
                    THEN cd.user_id 
                    END) as member_count,
                COALESCE(AVG(CASE 
                    WHEN r.points >= lt.points_required AND 
                         (NOT EXISTS (
                             SELECT 1 FROM loyalty_tiers lt2 
                             WHERE lt2.points_required > lt.points_required 
                             AND lt2.points_required <= r.points
                         ))
                    THEN r.points 
                    END), 0) as avg_points
                FROM loyalty_tiers lt
                LEFT JOIN customer_details cd ON 1=1
                LEFT JOIN rewards r ON cd.user_id = r.user_id
                GROUP BY lt.id, lt.tier_name, lt.points_required, lt.points_multiplier, lt.special_benefits
                ORDER BY lt.points_required ASC"; 

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_tier':
                $tier_name = mysqli_real_escape_string($con, $_POST['tier_name']);
                $points_required = intval($_POST['points_required']);
                $points_multiplier = floatval($_POST['points_multiplier']);
                // Clean up special benefits text
                $special_benefits = str_replace('\\r\\n', "\n", $_POST['special_benefits']);
                $special_benefits = str_replace('\r\n', "\n", $special_benefits);
                $special_benefits = mysqli_real_escape_string($con, $special_benefits);
                
                $query = "UPDATE loyalty_tiers SET 
                         points_required = ?,
                         points_multiplier = ?,
                         special_benefits = ?
                         WHERE tier_name = ?";
                $stmt = $con->prepare($query);
                $stmt->bind_param("idss", $points_required, $points_multiplier, $special_benefits, $tier_name);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Tier updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating tier: " . $stmt->error;
                }
                break;
        }
    }
}



// In the HTML form section, update the textarea:
?>
                                <div class="form-group">
                                    <label>Special Benefits</label>
                                    <textarea name="special_benefits" rows="4" style="white-space: pre-wrap;"><?php 
                                        $benefits = str_replace('\\r\\n', "\n", $tier['special_benefits']);
                                        $benefits = str_replace('\r\n', "\n", $benefits);
                                        echo htmlspecialchars($benefits); 
                                    ?></textarea>
                                </div>
