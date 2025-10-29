<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/product.php';

$db = new Database();
$productModel = new Product($db);

// Calculate today's total sales using Database class
$todayTotal = 0;
$result = $db->fetch("SELECT SUM(total) AS total FROM sales WHERE DATE(created_at) = CURDATE()");
if ($result) {
    $todayTotal = (float)($result['total'] ?? 0);
}

// Fetch products for the menu
$products = $productModel->all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>BulKas POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { font-family: 'Inter', sans-serif; }
    #menu-scroll::-webkit-scrollbar { width: 6px; }
    #menu-scroll::-webkit-scrollbar-thumb { background-color: #c4b09a; border-radius: 10px; }
    .add-btn:hover { background-color: #a08b6a; }
    .qty-btn:hover { background-color: #d1c4a7; }
    .confirm-btn:hover { background-color: #4caf50; }
    .cancel-btn:hover { background-color: #f44336; }
  </style>
</head>
<body class="bg-[rgba(255,255,255,0.7)] min-h-screen flex">
  <!-- Sidebar -->
  <aside class="bg-white bg-opacity-90 backdrop-blur-sm w-16 flex flex-col items-center py-6 space-y-8 shadow-lg">
    <button aria-label="Home" class="text-[#4B2E0E] text-xl" title="Home" type="button" onclick="window.location='pos.php'"><i class="fas fa-home"></i></button>
    <button aria-label="Transactions" class="text-[#4B2E0E] text-xl" title="Transactions" type="button" onclick="window.location='dashboard.php'"><i class="fas fa-list"></i></button>
    <button aria-label="Products" class="text-[#4B2E0E] text-xl" title="Products" type="button" onclick="window.location='product.php'"><i class="fas fa-box"></i></button>
  </aside>

  <!-- Main content -->
  <main class="flex-1 p-6 relative flex flex-col">
    <img alt="Background" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover opacity-20 -z-10" src="https://storage.googleapis.com/a1aa/image/22cccae8-cc1a-4fb3-7955-287078a4f8d4.jpg"/>

    <header class="mb-4 flex justify-between items-center">
      <div>
        <h1 class="text-[#4B2E0E] font-semibold text-xl">Welcome to BULDAKS</h1>
      </div>
      <div class="bg-[#4B2E0E] text-white rounded-xl px-4 py-2 shadow">
        <p class="text-sm">Today's Sales</p>
        <p class="text-2xl font-bold">₱ <?= number_format($todayTotal, 2) ?></p>
      </div>
    </header>

    <!-- Category buttons -->
    <nav id="category-nav" class="flex gap-3 mb-3 overflow-x-auto whitespace-nowrap scrollbar-thin scrollbar-thumb-[#c4b09a] scrollbar-track-transparent px-1">
      <button class="category-btn active flex items-center gap-2 bg-[#4B2E0E] text-white shadow-md rounded-full py-2 px-5 text-sm font-semibold" data-category="all"><i class="fas fa-grip-horizontal"></i> All</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-5 text-sm font-semibold" data-category="signatures"><i class="fas fa-fire"></i> Signatures</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-5 text-sm font-semibold" data-category="add-ons"><i class="fas fa-plus"></i> Add-ons</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-5 text-sm font-semibold" data-category="drinks"><i class="fas fa-glass-whiskey"></i> Drinks</button>
    </nav>

    <!-- Menu items -->
    <section class="bg-white bg-opacity-90 backdrop-blur-sm rounded-xl p-4 max-h-[600px] overflow-y-auto shadow-lg flex-1" id="menu-scroll">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" id="menu-items">
        <?php foreach ($products as $row): ?>
          <article class="bg-white rounded-lg shadow-md p-3 flex flex-col items-center menu-item"
            data-category="<?= htmlspecialchars($row['category']) ?>"
            data-id="<?= $row['id'] ?>"
            data-name="<?= htmlspecialchars($row['name']) ?>"
            data-price="<?= $row['price'] ?>">
            <img src="<?= $row['image'] ?: 'https://placehold.co/80x80/png?text=' . urlencode($row['name']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="mb-2" width="80" height="80">
            <h3 class="font-semibold text-sm text-[#4B2E0E] mb-1 text-center"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="font-semibold text-xs text-[#4B2E0E] mb-2">₱ <?= number_format($row['price'], 2) ?></p>
            <div class="item-controls w-full">
              <button class="bg-[#C4A07A] rounded-full w-full py-1 text-xs font-semibold text-white add-btn"
                data-id="<?= $row['id'] ?>"
                data-name="<?= htmlspecialchars($row['name']) ?>"
                data-price="<?= $row['price'] ?>">Add Item</button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  </main>

  <!-- Order Summary -->
  <aside class="w-80 bg-white bg-opacity-90 backdrop-blur-sm rounded-xl shadow-lg flex flex-col justify-between p-4">
    <div>
      <h2 class="font-semibold text-[#4B2E0E] mb-2">Order Summary</h2>
      <div class="text-xs text-gray-700" id="order-list"></div>
    </div>
    <div class="mt-6 text-center">
      <p class="font-semibold mb-1">Total:</p>
      <p class="text-4xl font-extrabold text-[#4B2E0E]" id="order-total">₱ 0.00</p>
    </div>
    <div class="mt-6 flex gap-4">
      <button class="flex-1 bg-green-500 text-white rounded-lg py-2 font-semibold confirm-btn" id="confirm-btn" disabled>Confirm</button>
      <button class="flex-1 bg-red-500 text-white rounded-lg py-2 font-semibold cancel-btn" id="cancel-btn" disabled>Cancel</button>
    </div>
  </aside>

  <script>
    let order = {};
    const orderList = document.getElementById("order-list");
    const orderTotalEl = document.getElementById("order-total");
    const confirmBtn = document.getElementById("confirm-btn");
    const cancelBtn = document.getElementById("cancel-btn");

    // Category filtering
    document.querySelectorAll('.category-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active', 'bg-[#4B2E0E]', 'text-white'));
        document.querySelectorAll('.category-btn').forEach(b => b.classList.add('bg-white', 'border', 'border-gray-300', 'text-gray-700'));
        btn.classList.add('active', 'bg-[#4B2E0E]', 'text-white');
        btn.classList.remove('bg-white', 'border', 'border-gray-300', 'text-gray-700');
        const category = btn.dataset.category;
        document.querySelectorAll('.menu-item').forEach(item => {
          item.style.display = category === 'all' || item.dataset.category === category ? 'flex' : 'none';
        });
      });
    });

    // Render controls and order summary
    function renderControls(article, id, name, price, quantity) {
      const controlsDiv = article.querySelector('.item-controls');
      controlsDiv.innerHTML = '';
      if (quantity > 0) {
        controlsDiv.className = 'item-controls w-full flex items-center justify-center gap-2';
        controlsDiv.innerHTML = `
          <button class='bg-gray-300 rounded-full w-7 h-7 text-gray-600 qty-btn' onclick='updateQuantity(${id},"${name}",${price},${quantity - 1},this)'>-</button>
          <span class='text-sm font-semibold text-[#4B2E0E]'>${quantity}</span>
          <button class='bg-[#C4A07A] rounded-full w-7 h-7 text-white font-bold qty-btn' onclick='updateQuantity(${id},"${name}",${price},${quantity + 1},this)'>+</button>`;
      } else {
        controlsDiv.innerHTML = `<button class="bg-[#C4A07A] rounded-full w-full py-1 text-xs font-semibold text-white add-btn" onclick='updateQuantity(${id},"${name}",${price},1,this.closest("article"))'>Add Item</button>`;
      }
    }

    function updateQuantity(id, name, price, newQty, btn) {
      const article = btn.closest('article');
      if (newQty <= 0) delete order[id];
      else order[id] = { name, price, quantity: newQty };
      renderControls(article, id, name, price, newQty > 0 ? newQty : 0);
      renderOrder();
    }

    function renderOrder() {
      orderList.innerHTML = '';
      const entries = Object.values(order);
      let total = 0;
      if (entries.length === 0) {
        orderTotalEl.textContent = "₱ 0.00";
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        return;
      }
      entries.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        orderList.innerHTML += `<div class='flex justify-between mb-1'>
          <span class='font-semibold'>${item.name}</span>
          <span>₱${subtotal.toFixed(2)} x${item.quantity}</span></div>`;
      });
      orderTotalEl.textContent = "₱ " + total.toFixed(2);
      confirmBtn.disabled = false;
      cancelBtn.disabled = false;
    }

    cancelBtn.addEventListener('click', () => {
      order = {};
      document.querySelectorAll('.menu-item').forEach(article => {
        const id = article.dataset.id;
        const name = article.dataset.name;
        const price = parseFloat(article.dataset.price);
        renderControls(article, id, name, price, 0);
      });
      renderOrder();
    });

    confirmBtn.addEventListener('click', () => {
      if (Object.keys(order).length === 0) return;
      const items = Object.values(order).map(item => ({
        product_id: parseInt(Object.keys(order).find(key => order[key].name === item.name)),
        quantity: item.quantity
      }));
      const total = parseFloat(orderTotalEl.textContent.replace(/[₱ ,]/g, '')) || 0;
      fetch('save_sale.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ customer_name: 'Guest', payment_amount: total, items })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Success!', 'Sale saved successfully!', 'success').then(() => location.reload());
        } else {
          Swal.fire('Error!', data.error, 'error');
        }
      })
      .catch(err => Swal.fire('Error!', 'Failed to save sale.', 'error'));
    });
  </script>
</body>
</html>