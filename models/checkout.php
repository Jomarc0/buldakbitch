<?php
// checkout.php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/product.php';

header('Content-Type: application/json');

$db = new Database();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['payment_amount']) || !isset($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$payment_amount = (float) $input['payment_amount'];
$items = $input['items'];

if ($payment_amount <= 0 || empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid payment amount or items']);
    exit;
}

try {
    // Assuming Database class has beginTransaction, commit, rollback methods
    $db->beginTransaction();

    // Insert into sales table using Database class execute method (assuming it exists and supports parameterized queries)
    // If not, you'll need to add an insert method to Database class or use raw PDO.
    $db->execute("INSERT INTO sales (total, created_at) VALUES (?, ?)", [$payment_amount, date('Y-m-d H:i:s')]);
    // Assuming execute returns the last insert ID or you can fetch it
    $sale_id = $db->fetch("SELECT LAST_INSERT_ID() AS id")['id'];

    if (!$sale_id) {
        throw new Exception('Failed to insert sale');
    }

    // Assuming there's a sales_items table with columns: id, sale_id, product_id, quantity, price
    // If not, adjust accordingly.
    foreach ($items as $item) {
        $product_id = (int) $item['product_id'];
        $quantity = (int) $item['quantity'];

        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception('Invalid item data');
        }

        // Fetch product price using Database class fetch method
        $product = $db->fetch("SELECT price FROM products WHERE id = ?", [$product_id]);
        if (!$product) {
            throw new Exception('Product not found');
        }
        $price = (float) $product['price'];

        // Insert into sales_items using execute
        $db->execute("INSERT INTO sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)", [$sale_id, $product_id, $quantity, $price]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
