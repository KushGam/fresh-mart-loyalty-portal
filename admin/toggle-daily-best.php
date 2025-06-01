<?php
include('../connection.php');
if (!isset($_POST['product_id'], $_POST['type'], $_POST['checked'])) {
    echo json_encode(['success'=>false, 'msg'=>'Missing params']);
    exit;
}
$product_id = intval($_POST['product_id']);
$type = $_POST['type'];
$checked = intval($_POST['checked']);
$discount = isset($_POST['discount']) ? intval($_POST['discount']) : 10;
$allowed_types = ['featured','popular','new'];
if (!in_array($type, $allowed_types)) {
    echo json_encode(['success'=>false, 'msg'=>'Invalid type']);
    exit;
}
// Backend safeguard: If product is featured, do not allow adding to daily_best_sells
if ($checked) {
    $check = $con->prepare('SELECT featured FROM products WHERE id=?');
    $check->bind_param('i', $product_id);
    $check->execute();
    $check->bind_result($is_featured);
    $check->fetch();
    $check->close();
    if ($is_featured == 1) {
        echo json_encode(['success'=>false, 'msg'=>'Product is already featured. Remove from featured first.']);
        exit;
    }
}
// Count current for this type
$count_q = $con->prepare('SELECT COUNT(*) FROM daily_best_sells WHERE type=?');
$count_q->bind_param('s', $type);
$count_q->execute();
$count_q->bind_result($count);
$count_q->fetch();
$count_q->close();
if ($checked && $count >= 3) {
    echo json_encode(['success'=>false, 'msg'=>'Max 3 per type']);
    exit;
}
if ($checked) {
    // Add or update
    $stmt = $con->prepare('INSERT INTO daily_best_sells (product_id, type, discount_percent) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE discount_percent=VALUES(discount_percent)');
    $stmt->bind_param('isi', $product_id, $type, $discount);
    $stmt->execute();
    $stmt->close();
} else {
    // Remove
    $stmt = $con->prepare('DELETE FROM daily_best_sells WHERE product_id=? AND type=?');
    $stmt->bind_param('is', $product_id, $type);
    $stmt->execute();
    $stmt->close();
}
echo json_encode(['success'=>true]); 