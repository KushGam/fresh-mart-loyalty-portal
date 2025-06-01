<?php
// Function to clean up expired offers
function cleanupExpiredOffers($con) {
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Get expired offers
        $get_expired = "SELECT id FROM personalized_offers WHERE end_date < CURDATE()";
        $result = $con->query($get_expired);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $offer_id = $row['id'];
                
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
            }
            
            // Delete all expired offers
            $delete_expired = "DELETE FROM personalized_offers WHERE end_date < CURDATE()";
            if (!$con->query($delete_expired)) {
                throw new Exception("Error deleting expired offers: " . $con->error);
            }
            
            return true; // Return true if offers were cleaned up
        }
        
        $con->commit();
        return false; // Return false if no offers needed cleanup
    } catch (Exception $e) {
        $con->rollback();
        throw $e; // Re-throw the exception to be handled by the caller
    }
} 