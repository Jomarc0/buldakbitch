<?php
session_start();

require_once __DIR__ .'/../classes/database.php';
require_once __DIR__ .'/../models/transaction.php';
$con = new Database();
$transaction = new Transaction($con);

$cashierName = 'Cashier'; 

if (isset($_SESSION['CashierName'])) {
    $cashierName = $_SESSION['CashierName'];
}


$totalSales = $transaction->getTotalSales(30);
$totalOrders = $transaction->getTotalOrders(30);

$todaySales = 0;
$result = $con->fetch("SELECT SUM(total_amount) AS total FROM sales WHERE DATE(created_at) = CURDATE()");
if ($result && isset($result['total'])) {
    $todaySales = (float) $result['total'];
}

$salesData = $transaction->getSalesOverviewData(30);
$topProducts = $transaction->getTopProducts(30);

$topSellerName = 'N/A';
if (!empty($topProducts['labels'][0])) {
    $topSellerName = $topProducts['labels'][0];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveAmiah - POS Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: rgba(255, 255, 255, 0.7);
        }
        .main-content {
            flex-grow: 1;
            padding: 1rem;
            position: relative;
        }
        .sidebar {
            width: 90px;
            background-color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 30px;
            gap: 35px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar a, .sidebar button {
            color: #4B2E0E;
            font-size: 26px;
            text-decoration: none;
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }
        .sidebar a:hover, .sidebar button:hover {
            color: #C4A07A;
        }
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: rgba(200, 200, 200, 0.3);
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background-color: #C4A07A;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background-color: #a17850;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row">
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

    <!-- Main Content -->
    <div class="main-content flex flex-col pt-16 md:pt-0">
        <!-- Background Image -->
        <img src="https://storage.googleapis.com/a1aa/image/22cccae8-cc1a-4fb3-7955-287078a4f8d4.jpg" alt="Background image of coffee beans" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover opacity-20 -z-10" />

        <header class="mb-4 md:mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center z-10">
            <div>
                <p class="text-xs text-gray-700 mb-0.5">Welcome, <?= htmlspecialchars($cashierName); ?></p>
                <h1 class="text-[#4B2E0E] font-semibold text-xl md:text-2xl">POS Dashboard</h1>
            </div>
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 mt-2 sm:mt-0">
                <button class="bg-[#C4A07A] text-white px-4 py-2 rounded-lg font-semibold hover:bg-[#a17850] transition shadow-md min-h-[44px]" id="refreshBtn">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
                <button class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-semibold hover:bg-gray-400 transition shadow-md min-h-[44px]" id="exportBtn">
                    <i class="fas fa-download mr-2"></i> Export Sales Report
                </button>
            </div>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 z-10">
            <div class="bg-white rounded-lg shadow-md p-4">
                <h5 class="text-lg font-semibold text-gray-700">Today's Sales</h5>
                <p class="text-2xl md:text-3xl font-bold text-[#4B2E0E]">₱<?= number_format($todaySales, 2); ?></p>
                <small class="text-gray-500">Resets daily</small>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <h5 class="text-lg font-semibold text-gray-700">Total Sales</h5>
                <p class="text-2xl md:text-3xl font-bold text-[#4B2E0E]" id="totalSales">₱<?= number_format($totalSales, 2); ?></p>
                <small class="text-gray-500">Last 30 days</small>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <h5 class="text-lg font-semibold text-gray-700">Total Orders</h5>
                <p class="text-2xl md:text-3xl font-bold text-[#4B2E0E]" id="totalOrders"><?= $totalOrders; ?></p>
                <small class="text-gray-500">Last 30 days</small>
            </div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <h5 class="text-lg font-semibold text-gray-700">Top Seller</h5>
                <p class="text-lg md:text-xl font-bold text-[#4B2E0E] overflow-hidden whitespace-nowrap overflow-ellipsis" title="<?= htmlspecialchars($topSellerName); ?>">
                    <?= htmlspecialchars($topSellerName); ?>
                </p>
                <small class="text-gray-500">Last 30 days</small>
            </div>
        </div>

        <!-- Sales Overview Chart -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6 z-10 max-w-4xl mx-auto w-full">
            <h5 class="text-lg font-semibold text-gray-700 mb-4">Sales Overview (Last 30 Days)</h5>
            <div class="h-[250px] md:h-[300px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        
        <!-- Low Stock Alerts section REMOVED from here -->

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
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($salesData['labels']); ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?php echo json_encode($salesData['data']); ?>,
                    borderColor: '#C4A07A',
                    backgroundColor: 'rgba(196, 160, 122, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#4B2E0E'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '₱' + context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#4B2E0E' },
                        grid: { color: 'rgba(0, 0, 0, 0.1)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            color: '#4B2E0E'
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.1)' }
                    }
                }
            }
        });

        // Refresh button functionality
        document.getElementById('refreshBtn').addEventListener('click', function() {
            location.reload();
        });

        // Export button functionality
        document.getElementById('exportBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Export Sales Report',
                text: 'This feature is under development.',
                icon: 'info',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-[#4B2E0E] text-white'
                },
                buttonsStyling: false
            });
        });

        // Logout button functionality
        document.getElementById("logout-btn").addEventListener("click", () => {
            Swal.fire({
                title: 'Are you sure you want to log out?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4B2E0E',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "../all/logout.php";
                }
            });
        });
    </script>
</body>
</html>
