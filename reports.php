<?php
global $db;

if (!isset($_SESSION['tenant_id'])) {
    header('Location: /sari/login');
    exit();
}

$page_title = "Reports";
$tenant_id = $_SESSION['tenant_id'];

// Date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Sales summary
$sales_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_sales,
                    SUM(discount_amount) as total_discounts,
                    AVG(total_amount) as avg_transaction
                FROM sales 
                WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->execute([$tenant_id, $start_date, $end_date]);
$sales_summary = $sales_stmt->fetch(PDO::FETCH_ASSOC);

// Daily sales chart data
$daily_sales_query = "SELECT 
                        DATE(created_at) as sale_date,
                        COUNT(*) as transactions,
                        SUM(total_amount) as daily_total
                      FROM sales 
                      WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'
                      GROUP BY DATE(created_at)
                      ORDER BY DATE(created_at)";
$daily_sales_stmt = $db->prepare($daily_sales_query);
$daily_sales_stmt->execute([$tenant_id, $start_date, $end_date]);
$daily_sales = $daily_sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Top selling products
$top_products_query = "SELECT 
                        p.name,
                        SUM(si.quantity) as total_sold,
                        SUM(si.total_price) as total_revenue
                       FROM sale_items si
                       JOIN products p ON si.product_id = p.id
                       JOIN sales s ON si.sale_id = s.id
                       WHERE s.tenant_id = ? AND DATE(s.created_at) BETWEEN ? AND ? AND s.payment_status = 'paid'
                       GROUP BY p.id, p.name
                       ORDER BY total_sold DESC
                       LIMIT 10";
$top_products_stmt = $db->prepare($top_products_query);
$top_products_stmt->execute([$tenant_id, $start_date, $end_date]);
$top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$low_stock_query = "SELECT name, stock_quantity, reorder_level 
                    FROM products 
                    WHERE tenant_id = ? AND stock_quantity <= reorder_level AND status = 'active'
                    ORDER BY stock_quantity ASC";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute([$tenant_id]);
$low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// Payment methods breakdown
$payment_methods_query = "SELECT 
                            payment_method,
                            COUNT(*) as count,
                            SUM(total_amount) as total
                          FROM sales 
                          WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'
                          GROUP BY payment_method";
$payment_methods_stmt = $db->prepare($payment_methods_query);
$payment_methods_stmt->execute([$tenant_id, $start_date, $end_date]);
$payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'views/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header with Date Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Reports & Analytics</h1>
                <p class="text-gray-600">Track your business performance and insights</p>
            </div>
            <form method="GET" class="flex flex-col sm:flex-row gap-3 mt-4 sm:mt-0">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">From:</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">To:</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200">
                    Apply Filter
                </button>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Sales -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Sales</p>
                        <p class="text-2xl font-bold"><?php echo format_currency($sales_summary['total_sales'] ?? 0); ?></p>
                    </div>
                    <div class="bg-blue-400 bg-opacity-30 p-3 rounded-full">
                        <i class="bi bi-currency-dollar text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Transactions</p>
                        <p class="text-2xl font-bold"><?php echo number_format($sales_summary['total_transactions'] ?? 0); ?></p>
                    </div>
                    <div class="bg-green-400 bg-opacity-30 p-3 rounded-full">
                        <i class="bi bi-receipt text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Average Transaction -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Avg Transaction</p>
                        <p class="text-2xl font-bold"><?php echo format_currency($sales_summary['avg_transaction'] ?? 0); ?></p>
                    </div>
                    <div class="bg-purple-400 bg-opacity-30 p-3 rounded-full">
                        <i class="bi bi-calculator text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Discounts -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Total Discounts</p>
                        <p class="text-2xl font-bold"><?php echo format_currency($sales_summary['total_discounts'] ?? 0); ?></p>
                    </div>
                    <div class="bg-orange-400 bg-opacity-30 p-3 rounded-full">
                        <i class="bi bi-percent text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Daily Sales Chart -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Daily Sales Trend</h3>
                </div>
                <div class="p-6">
                    <canvas id="dailySalesChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Payment Methods</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($payment_methods)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="bi bi-credit-card text-3xl mb-2 block"></i>
                            <p class="text-sm">No transactions found</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($payment_methods as $method): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full <?php echo $method['payment_method'] == 'cash' ? 'bg-green-500' : ($method['payment_method'] == 'card' ? 'bg-blue-500' : 'bg-purple-500'); ?>"></div>
                                    <span class="font-medium text-gray-900"><?php echo ucfirst($method['payment_method']); ?></span>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900"><?php echo format_currency($method['total']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $method['count']; ?> transactions</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bottom Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Selling Products -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Top Selling Products</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($top_products)): ?>
                        <div class="text-center text-gray-400 py-8">
                            <i class="bi bi-graph-up text-3xl mb-2 block"></i>
                            <p class="text-sm">No sales data available</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 text-sm font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="text-left py-3 text-sm font-medium text-gray-500 uppercase tracking-wider">Qty Sold</th>
                                        <th class="text-left py-3 text-sm font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($top_products as $index => $product): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 pr-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                                    <span class="text-xs font-semibold text-blue-600"><?php echo $index + 1; ?></span>
                                                </div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-sm text-gray-900"><?php echo number_format($product['total_sold']); ?></td>
                                        <td class="py-3 text-sm font-semibold text-green-600"><?php echo format_currency($product['total_revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Low Stock Alert</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="text-center text-green-400 py-8">
                            <i class="bi bi-check-circle text-4xl mb-3 block"></i>
                            <p class="text-lg font-medium text-green-600 mb-1">All Good!</p>
                            <p class="text-sm text-gray-500">All products are well stocked</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($low_stock_products as $product): ?>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-800"><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p class="text-xs text-yellow-600 mt-1">Reorder level: <?php echo $product['reorder_level']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            <?php echo $product['stock_quantity']; ?> left
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart with enhanced styling
const dailySalesData = <?php echo json_encode($daily_sales); ?>;
const ctx = document.getElementById('dailySalesChart').getContext('2d');

const labels = dailySalesData.map(item => {
    const date = new Date(item.sale_date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

const salesData = dailySalesData.map(item => parseFloat(item.daily_total));
const transactionData = dailySalesData.map(item => parseInt(item.transactions));

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Daily Sales (₱)',
            data: salesData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            yAxisID: 'y'
        }, {
            label: 'Transactions',
            data: transactionData,
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: 'rgb(16, 185, 129)',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            return 'Sales: ₱' + context.parsed.y.toLocaleString();
                        } else {
                            return 'Transactions: ' + context.parsed.y;
                        }
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    },
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280',
                    font: {
                        size: 11
                    }
                }
            }
        }
    }
});
</script>

<?php include 'views/footer.php'; ?>