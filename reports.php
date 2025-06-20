<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Reports & Analytics</h2>
                    <form method="GET" class="d-flex gap-2">
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control form-control-sm">
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control form-control-sm">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>
                
                <!-- Sales Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Sales</h6>
                                        <h4><?php echo format_currency($sales_summary['total_sales'] ?? 0); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-currency-dollar display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Transactions</h6>
                                        <h4><?php echo number_format($sales_summary['total_transactions'] ?? 0); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-receipt display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Avg Transaction</h6>
                                        <h4><?php echo format_currency($sales_summary['avg_transaction'] ?? 0); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-calculator display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Total Discounts</h6>
                                        <h4><?php echo format_currency($sales_summary['total_discounts'] ?? 0); ?></h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-percent display-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Daily Sales Chart -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Daily Sales Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="dailySalesChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Payment Methods</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($payment_methods as $method): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo ucfirst($method['payment_method']); ?></span>
                                    <div class="text-end">
                                        <div><strong><?php echo format_currency($method['total']); ?></strong></div>
                                        <small class="text-muted"><?php echo $method['count']; ?> transactions</small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <!-- Top Selling Products -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty Sold</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo number_format($product['total_sold']); ?></td>
                                                <td><?php echo format_currency($product['total_revenue']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Alert -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Low Stock Alert</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($low_stock_products)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle display-4 text-success"></i>
                                        <p>All products are well stocked!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Current</th>
                                                    <th>Reorder At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($low_stock_products as $product): ?>
                                                <tr class="table-warning">
                                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                    <td><span class="low-stock"><?php echo $product['stock_quantity']; ?></span></td>
                                                    <td><?php echo $product['reorder_level']; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
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
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            yAxisID: 'y'
        }, {
            label: 'Transactions',
            data: transactionData,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Date'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Sales Amount (₱)'
                },
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toFixed(2);
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Number of Transactions'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            return 'Sales: ₱' + context.parsed.y.toFixed(2);
                        } else {
                            return 'Transactions: ' + context.parsed.y;
                        }
                    }
                }
            }
        }
    }
});
</script>

<?php include 'views/footer.php'; ?>