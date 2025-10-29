<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/product.php';

$db = new Database();
$product = new Product($db);

$products = $product->all();
echo json_encode(['success' => true, 'products' => $products]);

?>