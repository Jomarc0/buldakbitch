<?php
require_once __DIR__ . '/database.php';

class Sale {
    private Database $db;
    public function __construct(Database $db) { $this->db = $db; }

    public function create(float $paymentAmount, array $items): int {
        $this->db->beginTransaction();
        try {
            $total = array_reduce($items, fn($sum, $it) => $sum + ($it['unit_price'] * $it['quantity']), 0);
            $change = $paymentAmount - $total;
            if ($change < 0) throw new Exception("Payment amount is less than total.");

            $this->db->execute(
                "INSERT INTO sales (total, total_amount, payment_amount, change_amount, status) VALUES (?, ?, ?, ?, ?)",
                [$total, $total, $paymentAmount, $change, 'completed']
            );
            $saleId = (int)$this->db->lastInsertId();

            foreach ($items as $it) {
                $subtotal = $it['unit_price'] * $it['quantity'];
                
                $this->db->execute(
                    "INSERT INTO sales_total (sale_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)",
                    [$saleId, $it['product_id'], $it['product_name'], $it['unit_price'], $it['quantity'], $subtotal]
                );
                
                $updated = $this->db->execute(
                    "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?", 
                    [$it['quantity'], $it['product_id'], $it['quantity']]
                );
                
                if (!$updated) {
                    $product = $this->db->fetch("SELECT stock, name FROM products WHERE id = ?", [$it['product_id']]);
                    if ($product && $product['stock'] < $it['quantity']) {
                        throw new Exception("Insufficient stock for {$product['name']}. Available: {$product['stock']}, Required: {$it['quantity']}");
                    }
                }
            }

            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recent(int $limit = 20): array {
        return $this->db->fetchAll(
            "SELECT id, total, payment_amount, change_amount, status, created_at FROM sales ORDER BY created_at DESC LIMIT ?", 
            [$limit]
        );
    }

    public function salesLastDays(int $days = 30): array {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as day, SUM(total) as total
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC", 
            [$days]
        );
    }
}
?>