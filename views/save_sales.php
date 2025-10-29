<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/classes/database.php';
require_once __DIR__ . '/classes/product.php';
require_once __DIR__ . '/classes/sale.php';

$db = new Database();
$productModel = new Product($db);
$saleModel = new Sale($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['items']) || !is_array($body['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

// Removed customer_name handling
$paymentAmount = (float)($body['payment_amount'] ?? 0);
$items = $body['items'];

try {
    $prepared = [];
    foreach ($items as $it) {
        if (!isset($it['product_id'], $it['quantity'])) throw new Exception("Invalid item structure");
        $p = $productModel->find((int)$it['product_id']);
        if (!$p) throw new Exception("Product not found");
        if ($p['stock'] < $it['quantity']) throw new Exception("Insufficient stock for {$p['name']}");
        $prepared[] = [
            'product_id' => (int)$p['id'],
            'product_name' => $p['name'],
            'unit_price' => (float)$p['price'],
            'quantity' => (int)$it['quantity']
        ];
    }

    $saleId = $saleModel->create($paymentAmount, $prepared);  // Removed $customerName argument
    echo json_encode(['success' => true, 'sale_id' => $saleId]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>