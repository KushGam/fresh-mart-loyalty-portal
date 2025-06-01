<?php
include('../connection.php');
$types = ['featured','popular','new'];
$data = ['featured'=>[], 'popular'=>[], 'new'=>[], 'discounts'=>['featured'=>[],'popular'=>[],'new'=>[]]];
$res = $con->query("SELECT product_id, type, discount_percent FROM daily_best_sells");
while($row = $res->fetch_assoc()) {
    $data[$row['type']][] = (string)$row['product_id'];
    $data['discounts'][$row['type']][$row['product_id']] = (int)$row['discount_percent'];
}
echo json_encode($data); 