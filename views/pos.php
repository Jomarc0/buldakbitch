<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/product.php';
require_once __DIR__ . '/../classes/sale.php';  

$db = new Database();
$productModel = new Product($db);
$saleModel = new Sale($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['payment_amount']) || !isset($input['items']) || !is_array($input['items'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    $paymentAmount = (float) $input['payment_amount'];
    $items = $input['items'];
    $total = 0;
    $prepared = [];
    
    try {
        foreach ($items as $item) {
            $product = $productModel->find($item['product_id']);
            if (!$product) {
                throw new Exception('Product not found');
            }
            if ($product['stock'] < $item['quantity']) {
                throw new Exception('Insufficient stock for ' . $product['name']);
            }
            $prepared[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'unit_price' => $product['price'],
                'quantity' => $item['quantity']
            ];
            $total += $product['price'] * $item['quantity'];
        }
        
        if ($paymentAmount < $total) {
            throw new Exception('Payment amount is less than total. Total: ₱' . number_format($total, 2) . ', Paid: ₱' . number_format($paymentAmount, 2));
        }
        
        $saleId = $saleModel->create($paymentAmount, $prepared);  // Pass payment_amount to create method
        echo json_encode(['success' => true, 'sale_id' => $saleId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$todayTotal = 0;
$result = $db->fetch("SELECT SUM(total_amount) AS total FROM sales WHERE DATE(created_at) = CURDATE()");
if ($result) {
    $todayTotal = (float)($result['total'] ?? 0);
}

$products = $productModel->all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>BULDAKS</title>
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
<body class="bg-[rgba(255,255,255,0.7)] min-h-screen flex flex-col md:flex-row">
  <!-- Mobile Menu Toggle -->
  <button id="mobile-menu-toggle" class="md:hidden fixed top-4 left-4 z-50 bg-[#4B2E0E] text-white p-2 rounded-lg shadow-lg">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <aside id="sidebar" class="bg-white bg-opacity-90 backdrop-blur-sm w-16 md:w-16 flex flex-col items-center py-6 space-y-8 shadow-lg fixed md:static top-0 left-0 h-full md:h-auto z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-300">
    <button aria-label="Home" class="text-[#4B2E0E] text-xl" title="Home" type="button" onclick="window.location='pos.php'"><i class="fas fa-home"></i></button>
    <button aria-label="Transactions" class="text-[#4B2E0E] text-xl" title="Transactions" type="button" onclick="window.location='dashboard.php'"><i class="fas fa-list"></i></button>
    <button aria-label="Products" class="text-[#4B2E0E] text-xl" title="Products" type="button" onclick="window.location='product.php'"><i class="fas fa-box"></i></button>
  </aside>

  <!-- Main content -->
  <main class="flex-1 p-4 md:p-6 relative flex flex-col pt-16 md:pt-0">
    <img alt="Background" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover opacity-20 -z-10" src="https://storage.googleapis.com/a1aa/image/22cccae8-cc1a-4fb3-7955-287078a4f8d4.jpg"/>

    <header class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center">
      <div>
        <h1 class="text-[#4B2E0E] font-semibold text-xl md:text-2xl">Welcome to BULDAKS</h1>
      </div>
      <div class="bg-[#4B2E0E] text-white rounded-xl px-4 py-2 shadow mt-2 sm:mt-0">
        <p class="text-sm">Today's Sales</p>
        <p class="text-2xl font-bold">₱ <?= number_format($todayTotal, 2) ?></p>
      </div>
    </header>

    <!-- Category buttons -->
    <nav id="category-nav" class="flex gap-2 md:gap-3 mb-3 overflow-x-auto whitespace-nowrap scrollbar-thin scrollbar-thumb-[#c4b09a] scrollbar-track-transparent px-1">
      <button class="category-btn active flex items-center gap-2 bg-[#4B2E0E] text-white shadow-md rounded-full py-2 px-3 md:px-5 text-sm font-semibold" data-category="all"><i class="fas fa-grip-horizontal"></i> All</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-3 md:px-5 text-sm font-semibold" data-category="signatures"><i class="fas fa-fire"></i> Signatures</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-3 md:px-5 text-sm font-semibold" data-category="add-ons"><i class="fas fa-plus"></i> Add-ons</button>
      <button class="category-btn flex items-center gap-2 bg-white border border-gray-300 text-gray-700 rounded-full py-2 px-3 md:px-5 text-sm font-semibold" data-category="drinks"><i class="fas fa-glass-whiskey"></i> Drinks</button>
    </nav>

    <!-- Menu items -->
    <section class="bg-white bg-opacity-90 backdrop-blur-sm rounded-xl p-4 max-h-[400px] md:max-h-[600px] overflow-y-auto shadow-lg flex-1" id="menu-scroll">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="menu-items">
        <?php foreach ($products as $row): ?>
          <article class="bg-white rounded-lg shadow-md p-3 flex flex-col items-center menu-item"
            data-category="<?= htmlspecialchars($row['category']) ?>"
            data-id="<?= $row['id'] ?>"
            data-name="<?= htmlspecialchars($row['name']) ?>"
            data-price="<?= $row['price'] ?>">
            <img src="<?= $row['image'] ?: 'https://placehold.co/80x80/png?text=' . urlencode($row['name']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="mb-2 w-16 h-16 md:w-20 md:h-20 object-cover rounded">
            <h3 class="font-semibold text-sm text-[#4B2E0E] mb-1 text-center leading-tight"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="font-semibold text-xs text-[#4B2E0E] mb-2">₱ <?= number_format($row['price'], 2) ?></p>
            <div class="item-controls w-full">
              <button class="bg-[#C4A07A] rounded-full w-full py-2 text-xs font-semibold text-white add-btn min-h-[44px]"
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
  <aside class="w-full md:w-80 bg-white bg-opacity-90 backdrop-blur-sm rounded-xl shadow-lg flex flex-col justify-between p-4 mt-4 md:mt-0">
    <div>
      <h2 class="font-semibold text-[#4B2E0E] mb-2">Order Summary</h2>
      <div class="text-xs text-gray-700 max-h-40 overflow-y-auto" id="order-list"></div>
    </div>
    <div class="mt-6">
      <p class="font-semibold mb-1">Total:</p>
      <p class="text-3xl md:text-4xl font-extrabold text-[#4B2E0E]" id="order-total">₱ 0.00</p>
      <div class="mt-4">
        <label for="payment-amount" class="block text-sm font-medium text-gray-700">Payment Amount</label>
        <input type="number" id="payment-amount" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-3 mt-1 focus:outline-none focus:ring-2 focus:ring-[#4B2E0E] min-h-[44px]" placeholder="Enter payment amount">
      </div>
      <div class="mt-2">
        <p class="text-sm text-gray-600">Change: <span id="change-amount">₱ 0.00</span></p>
      </div>
    </div>
    <div class="mt-6 flex gap-4">
      <button class="flex-1 bg-green-500 text-white rounded-lg py-3 font-semibold confirm-btn min-h-[44px]" id="confirm-btn" disabled>Confirm</button>
      <button class="flex-1 bg-red-500 text-white rounded-lg py-3 font-semibold cancel-btn min-h-[44px]" id="cancel-btn" disabled>Cancel</button>
    </div>
  </aside>

  <script>
    // Mobile menu toggle
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('mobile-menu-toggle');
    toggle.addEventListener('click', () => {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full');
            toggle.style.display = isOpen ? 'block' : 'none';  // Hide toggle when sidebar is open
        });

    let order = {};
    const orderList = document.getElementById("order-list");
    const orderTotalEl = document.getElementById("order-total");
    const paymentAmountEl = document.getElementById("payment-amount");
    const changeAmountEl = document.getElementById("change-amount");
    const confirmBtn = document.getElementById("confirm-btn");
    const cancelBtn = document.getElementById("cancel-btn");

    function renderControls(article, id, name, price, quantity) {
      const controlsDiv = article.querySelector('.item-controls');
      controlsDiv.innerHTML = '';
      
      if (quantity > 0) {
        controlsDiv.className = 'item-controls w-full flex items-center justify-center gap-2';
        
        const minusBtn = document.createElement('button');
        minusBtn.className = 'bg-gray-300 rounded-full w-8 h-8 md:w-7 md:h-7 text-gray-600 qty-btn min-h-[44px] min-w-[44px]';
        minusBtn.textContent = '-';
        minusBtn.addEventListener('click', () => updateQuantity(id, name, price, quantity - 1, article));
        
        const qtySpan = document.createElement('span');
        qtySpan.className = 'text-sm font-semibold text-[#4B2E0E] px-2';
        qtySpan.textContent = quantity;
        
        const plusBtn = document.createElement('button');
        plusBtn.className = 'bg-[#C4A07A] rounded-full w-8 h-8 md:w-7 md:h-7 text-white font-bold qty-btn min-h-[44px] min-w-[44px]';
        plusBtn.textContent = '+';
        plusBtn.addEventListener('click', () => updateQuantity(id, name, price, quantity + 1, article));
        
        controlsDiv.appendChild(minusBtn);
        controlsDiv.appendChild(qtySpan);
        controlsDiv.appendChild(plusBtn);
      } else {
        controlsDiv.className = 'item-controls w-full';
        const addBtn = document.createElement('button');
        addBtn.className = 'bg-[#C4A07A] rounded-full w-full py-2 text-xs font-semibold text-white add-btn min-h-[44px]';
        addBtn.textContent = 'Add Item';
        addBtn.setAttribute('data-id', id);
        addBtn.setAttribute('data-name', name);
        addBtn.setAttribute('data-price', price);
        addBtn.addEventListener('click', () => updateQuantity(id, name, price, 1, article));
        controlsDiv.appendChild(addBtn);
      }
    }

    function updateQuantity(id, name, price, newQty, article) {
      if (newQty <= 0) {
        delete order[id];
      } else {
        order[id] = { name, price, quantity: newQty };
      }
      renderControls(article, id, name, price, newQty > 0 ? newQty : 0);
      renderOrder();
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
      const article = btn.closest('article');
      const id = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name');
      const price = parseFloat(btn.getAttribute('data-price'));
      btn.addEventListener('click', () => updateQuantity(id, name, price, 1, article));
    });

    document.querySelectorAll('.category-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.category-btn').forEach(b => {
          b.classList.remove('active', 'bg-[#4B2E0E]', 'text-white', 'shadow-md');
          b.classList.add('bg-white', 'border', 'border-gray-300', 'text-gray-700');
        });
        btn.classList.add('active', 'bg-[#4B2E0E]', 'text-white', 'shadow-md');
        btn.classList.remove('bg-white', 'border', 'border-gray-300', 'text-gray-700');
        
        const category = btn.dataset.category;
        document.querySelectorAll('.menu-item').forEach(item => {
          if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });

    function renderOrder() {
      orderList.innerHTML = '';
      const entries = Object.values(order);
      let total = 0;

      if (entries.length === 0) {
        orderTotalEl.textContent = "₱ 0.00";
        paymentAmountEl.value = '';
        changeAmountEl.textContent = "₱ 0.00";
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;
        return;
      }
      
      orderList.innerHTML += '<p class="font-semibold mb-1">CATEGORY</p>';
      entries.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        const div = document.createElement("div");
        div.className = "flex justify-between mb-1";
        const spanName = document.createElement("span");
        spanName.className = "font-semibold";
        spanName.textContent = item.name;
        const spanPriceQty = document.createElement("span");
        spanPriceQty.innerHTML = `<span>₱ ${subtotal.toFixed(2)}</span><span class="ml-1">x${item.quantity}</span>`;
        div.appendChild(spanName);
        div.appendChild(spanPriceQty);
        orderList.appendChild(div);
      });
      orderTotalEl.textContent = `₱ ${total.toFixed(2)}`;
      paymentAmountEl.value = total.toFixed(2); // Default to total
      updateChange();
      confirmBtn.disabled = false;
      cancelBtn.disabled = false;
    }

    function updateChange() {
      const total = parseFloat(orderTotalEl.textContent.replace(/[₱ ,]/g, '')) || 0;
      const payment = parseFloat(paymentAmountEl.value) || 0;
      const change = payment - total;
      changeAmountEl.textContent = `₱ ${change.toFixed(2)}`;
      if (change < 0) {
        changeAmountEl.classList.add('text-red-500');
        changeAmountEl.classList.remove('text-gray-600');
      } else {
        changeAmountEl.classList.remove('text-red-500');
        changeAmountEl.classList.add('text-gray-600');
      }
    }

    paymentAmountEl.addEventListener('input', updateChange);

    cancelBtn.addEventListener('click', () => {
      order = {};
      document.querySelectorAll('article.menu-item').forEach(article => {
        const id = article.dataset.id;
        const name = article.dataset.name;
        const price = parseFloat(article.dataset.price);
        if (id && name && !isNaN(price)) {
          renderControls(article, id, name, price, 0);
        }
      });
      renderOrder();
    });

    confirmBtn.addEventListener('click', () => {
      if (Object.keys(order).length === 0) {
        Swal.fire('No items in order!', 'Please add items before confirming.', 'warning');
        return;
      }

      const total = parseFloat(orderTotalEl.textContent.replace(/[₱ ,]/g, '')) || 0;
      const paymentAmount = parseFloat(paymentAmountEl.value) || 0;
      if (paymentAmount < total) {
        Swal.fire('Insufficient Payment!', 'Payment amount must be at least the total.', 'error');
        return;
      }

      const items = Object.keys(order).map(id => ({
        product_id: parseInt(id),
        quantity: order[id].quantity
      }));

      console.log('Sending data:', { payment_amount: paymentAmount, items: items });

      fetch(window.location.href, {  // Fetch to the same page (pos.php)
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ payment_amount: paymentAmount, items: items })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Success!', 'Sale saved successfully!', 'success').then(() => {
            order = {};
            document.querySelectorAll('article.menu-item').forEach(article => {
              const id = article.dataset.id;
              const name = article.dataset.name;
              const price = parseFloat(article.dataset.price);
              if (id && name && !isNaN(price)) {
                renderControls(article, id, name, price, 0);
              }
            });
            renderOrder();
            location.reload();
          });
        } else {
          Swal.fire('Error!', data.error || 'Failed to save sale.', 'error');
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        Swal.fire('Error!', 'An error occurred while trying to save the sale.', 'error');
      });
    });

    document.addEventListener('DOMContentLoaded', () => {
      const allButton = document.querySelector('.category-btn[data-category="all"]');
      if (allButton) {
        allButton.click();
      }
    });
  </script>
</body>
</html>