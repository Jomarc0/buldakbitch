<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/product.php';

$db = new Database();
$productModel = new Product($db);

// Handle POST requests (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['success' => false];

    if ($action === 'add') {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $image = $_POST['image'] ?? '';

        if ($sku && $name && $price > 0 && $category) {
            $db->execute("INSERT INTO products (sku, name, description, price, category, image, stock, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                          [$sku, $name, $description, $price, $category, $image, $stock]);
            $response = ['success' => true];
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $image = $_POST['image'] ?? '';

        if ($id && $sku && $name && $price > 0 && $category) {
            $db->execute("UPDATE products 
                          SET sku = ?, name = ?, description = ?, price = ?, category = ?, image = ?, stock = ?, updated_at = NOW() 
                          WHERE id = ?",
                          [$sku, $name, $description, $price, $category, $image, $stock, $id]);
            $response = ['success' => true];
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $db->execute("DELETE FROM products WHERE id = ?", [$id]);
            $response = ['success' => true];
        }
    }

    echo json_encode($response);
    exit;
}

// Fetch all products
$products = $productModel->all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BulKas POS - Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-[rgba(255,255,255,0.7)] min-h-screen flex flex-col md:flex-row">
    <!-- Mobile Menu Toggle -->
    <button id="mobile-menu-toggle" class="md:hidden fixed top-4 left-4 z-50 bg-[#4B2E0E] text-white p-2 rounded-lg shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside id="sidebar" class="bg-white bg-opacity-90 backdrop-blur-sm w-16 md:w-16 flex flex-col items-center py-6 space-y-8 shadow-lg fixed md:static top-0 left-0 h-full md:h-auto z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
        <button aria-label="Home" class="text-[#4B2E0E] text-xl" title="Home" onclick="window.location='pos.php'">
            <i class="fas fa-home"></i>
        </button>
        <button aria-label="Transactions" class="text-[#4B2E0E] text-xl" title="Transactions" onclick="window.location='dashboard.php'">
            <i class="fas fa-list"></i>
        </button>
        <button aria-label="Products" class="text-[#4B2E0E] text-xl" title="Products" onclick="window.location='product.php'">
            <i class="fas fa-box"></i>
        </button>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-6 relative pt-16 md:pt-0">
        <img alt="Background" aria-hidden="true"
             class="absolute inset-0 w-full h-full object-cover opacity-20 -z-10"
             src="https://storage.googleapis.com/a1aa/image/22cccae8-cc1a-4fb3-7955-287078a4f8d4.jpg"/>

        <header class="mb-4 md:mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Manage Your Products</p>
                <h1 class="text-[#4B2E0E] font-semibold text-xl md:text-2xl">Products</h1>
            </div>
            <button id="add-product-btn"
                    class="bg-[#C4A07A] text-white px-4 py-2 rounded-lg font-semibold hover:bg-[#a17850] transition shadow-md min-h-[44px] mt-2 sm:mt-0">
                Add Product
            </button>
        </header>

        <!-- Products Table -->
        <div class="bg-white bg-opacity-90 backdrop-blur-sm rounded-xl p-4 shadow-lg overflow-x-auto">
            <table class="w-full text-sm text-left min-w-[600px]">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-4 md:px-6 py-3">Image</th>
                        <th class="px-4 md:px-6 py-3">SKU</th>
                        <th class="px-4 md:px-6 py-3">Name</th>
                        <th class="px-4 md:px-6 py-3">Category</th>
                        <th class="px-4 md:px-6 py-3">Price</th>
                        <th class="px-4 md:px-6 py-3">Stock</th>
                        <th class="px-4 md:px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr class="border-b">
                            <td class="px-4 md:px-6 py-4">
                                <img src="<?= $p['image'] ?: 'https://placehold.co/50x50/png?text=' . urlencode($p['name']) ?>"
                                     alt="<?= htmlspecialchars($p['name']) ?>" class="w-10 h-10 md:w-12 md:h-12 object-cover rounded">
                            </td>
                            <td class="px-4 md:px-6 py-4 text-xs md:text-sm"><?= htmlspecialchars($p['sku']) ?></td>
                            <td class="px-4 md:px-6 py-4 font-medium text-gray-900 text-xs md:text-sm"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="px-4 md:px-6 py-4 text-xs md:text-sm"><?= htmlspecialchars($p['category']) ?></td>
                            <td class="px-4 md:px-6 py-4 text-xs md:text-sm">₱ <?= number_format($p['price'], 2) ?></td>
                            <td class="px-4 md:px-6 py-4 text-xs md:text-sm"><?= $p['stock'] ?></td>
                            <td class="px-4 md:px-6 py-4">
                                <button class="text-blue-600 hover:text-blue-800 edit-btn min-h-[44px] min-w-[44px]"
                                    data-id="<?= $p['id'] ?>"
                                    data-sku="<?= htmlspecialchars($p['sku']) ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-description="<?= htmlspecialchars($p['description']) ?>"
                                    data-price="<?= $p['price'] ?>"
                                    data-category="<?= htmlspecialchars($p['category']) ?>"
                                    data-stock="<?= $p['stock'] ?>"
                                    data-image="<?= htmlspecialchars($p['image']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="text-red-600 hover:text-red-800 ml-2 delete-btn min-h-[44px] min-w-[44px]"
                                    data-id="<?= $p['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal -->
    <div id="product-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg p-4 md:p-6 w-full max-w-md md:max-w-lg">
            <h2 id="modal-title" class="text-lg md:text-xl font-semibold mb-4">Add Product</h2>
            <form id="product-form">
                <input type="hidden" id="product-id" name="id">
                <input type="hidden" name="action" id="action">
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">SKU</label>
                    <input type="text" id="product-sku" name="sku" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="product-name" name="name" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="product-description" name="description" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <input type="number" step="0.01" id="product-price" name="price" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" id="product-category" name="category" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Stock</label>
                    <input type="number" id="product-stock" name="stock" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Image URL</label>
                    <input type="url" id="product-image" name="image" class="w-full border-gray-300 rounded-md shadow-sm px-3 py-2 min-h-[44px]">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancel-modal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded min-h-[44px]">Cancel</button>
                    <button type="submit" class="bg-[#C4A07A] text-white px-4 py-2 rounded min-h-[44px]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('mobile-menu-toggle');
        toggle.addEventListener('click', () => {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full');
            toggle.style.display = isOpen ? 'block' : 'none';  // Hide toggle when sidebar is open
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target) && window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
                toggle.style.display = 'block';
            }
        });

        const modal = document.getElementById('product-modal');
        const form = document.getElementById('product-form');
        const modalTitle = document.getElementById('modal-title');
        const actionInput = document.getElementById('action');

        document.getElementById('add-product-btn').addEventListener('click', () => {
            modalTitle.textContent = 'Add Product';
            actionInput.value = 'add';
            form.reset();
            modal.classList.remove('hidden');
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = 'Edit Product';
                actionInput.value = 'edit';
                document.getElementById('product-id').value = btn.dataset.id;
                document.getElementById('product-sku').value = btn.dataset.sku;
                document.getElementById('product-name').value = btn.dataset.name;
                document.getElementById('product-description').value = btn.dataset.description;
                document.getElementById('product-price').value = btn.dataset.price;
                document.getElementById('product-category').value = btn.dataset.category;
                document.getElementById('product-stock').value = btn.dataset.stock;
                document.getElementById('product-image').value = btn.dataset.image;
                modal.classList.remove('hidden');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will permanently delete the product.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch('product.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ action: 'delete', id: btn.dataset.id })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', 'Product removed.', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error!', 'Failed to delete product.', 'error');
                            }
                        });
                    }
                });
            });
        });

        document.getElementById('cancel-modal').addEventListener('click', () => modal.classList.add('hidden'));

        form.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('product.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success!', 'Product saved successfully!', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error!', 'Failed to save product.', 'error');
                    }
                });
        });
    </script>
</body>
</html>
