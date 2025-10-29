<?php
require_once __DIR__ . '/../classes/database.php';

class Transaction {
    private Database $db;
    private $pdo;
    private $table = "sales";

    public function __construct(Database $db) {
        $this->db = $db;
        $this->pdo = $this->db->getPdo();
    }

    public function getTotalSales(int $days = 30): float {
        // Updated to use total_amount
        $sql = "SELECT SUM(total_amount) AS total_sales FROM {$this->table} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $result = $this->db->fetch($sql, [$days]);
        return (float)($result['total_sales'] ?? 0);
    }

    public function getTotalOrders(int $days = 30): int {
        $sql = "SELECT COUNT(*) AS total_orders FROM {$this->table} WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $result = $this->db->fetch($sql, [$days]);
        return (int)($result['total_orders'] ?? 0);
    }

    public function getTopSeller(int $days = 30): string {
        $sql = "SELECT p.name AS product_name, SUM(si.quantity) AS total_sold
                FROM sales_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY p.name
                ORDER BY total_sold DESC
                LIMIT 1";
        $result = $this->db->fetch($sql, [$days]);
        return $result['product_name'] ?? 'No Sales Yet';
    }

    public function getTopProducts(int $days = 30): array {
        $sql = "SELECT p.name AS product_name, SUM(si.quantity) AS total_sold
                FROM sales_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY p.name
                ORDER BY total_sold DESC
                LIMIT 5";
        $rows = $this->db->fetchAll($sql, [$days]);
        $labels = [];
        $data = [];
        foreach ($rows as $row) {
            $labels[] = $row['product_name'];
            $data[] = (int)$row['total_sold'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

    public function getSalesOverviewData(int $days = 30): array {
        // Updated to use total_amount
        $sql = "SELECT DATE(created_at) AS sale_date, SUM(total_amount) AS daily_sales
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY sale_date ORDER BY sale_date ASC";

        $rows = $this->db->fetchAll($sql, [$days]);
        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $labels[] = $row['sale_date'];
            $data[] = (float)$row['daily_sales'];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getCustomerCount(): int {
        $sql = "SELECT COUNT(*) AS total_customers FROM customers"; // Assuming a customers table
        $result = $this->db->fetch($sql);
        return (int)($result['total_customers'] ?? 0);
    }
}
?>