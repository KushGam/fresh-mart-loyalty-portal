<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = intval($_POST['card_id']);
    
    // Verify the card belongs to the user
    $query = "SELECT * FROM saved_cards WHERE id = ? AND user_id = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param("ii", $card_id, $user_data['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete the card
        $delete_query = "DELETE FROM saved_cards WHERE id = ? AND user_id = ?";
        $stmt = $con->prepare($delete_query);
        $stmt->bind_param("ii", $card_id, $user_data['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Card deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting card']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Card not found or unauthorized']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?> 