<?php
require_once __DIR__ . '/database.php';

class Product {
    private Database $db;
    public function __construct(Database $db) { $this->db = $db; }

    public function all(): array {
        return $this->db->fetchAll("SELECT id, sku, name, description, price, category, image, stock FROM products ORDER BY category, name");
    }

    public function find(int $id): ?array {
        return $this->db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
    }
    public function findByName(string $name): ?array {
        return $this->db->fetch("SELECT * FROM products WHERE name = ?", [$name]);
    }

    public function decreaseStock(int $id, int $qty): bool {
        return $this->db->execute("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?", [$qty, $id, $qty]);
    }
}
