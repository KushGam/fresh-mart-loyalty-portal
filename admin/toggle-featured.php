<?php
include('../connection.php');
$id = intval($_POST['product_id']);
$featured = (isset($_POST['featured']) && $_POST['featured'] == '1') ? 1 : 0;
if ($featured === 1) {
    // Check if product is in daily_best_sells
    $check = $con->prepare('SELECT COUNT(*) FROM daily_best_sells WHERE product_id=?');
    $check->bind_param('i', $id);
    $check->execute();
    $check->bind_result($cnt);
    $check->fetch();
    $check->close();
    if ($cnt > 0) {
        echo json_encode(['success' => false, 'msg' => 'Product is in Daily Best Sells. Remove it from there first.']);
        exit();
    }
}
$stmt = $con->prepare("UPDATE products SET featured = ? WHERE id = ?");
$stmt->bind_param("ii", $featured, $id);
$stmt->execute();
echo json_encode(['success' => true]); 