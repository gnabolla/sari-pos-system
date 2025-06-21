<?php
global $db;

if (!isset($_SESSION['tenant_id'])) {
    header('Location: /sari/login');
    exit();
}

$page_title = "Sari-Sari Store POS";
include 'views/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back, <?php echo $_SESSION['full_name'] ?? 'User'; ?>!</h1>
            <p class="text-gray-600">Here's what's happening with your store today.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Sales -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Today's Sales</p>
                        <p class="text-2xl font-bold text-green-600" id="today-sales">₱0.00</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="bi bi-cash-coin text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                        <p class="text-2xl font-bold text-red-600" id="low-stock">0</p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="bi bi-exclamation-triangle text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>

            <!-- Total Products -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-bold text-blue-600" id="total-products">0</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="bi bi-box text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- This Week Sales -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">This Week</p>
                        <p class="text-2xl font-bold text-purple-600" id="week-sales">₱0.00</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="bi bi-graph-up text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-4">
                    <a href="/sari/pos" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center space-x-2">
                        <i class="bi bi-cash-coin"></i>
                        <span>Point of Sale</span>
                    </a>
                    <a href="/sari/inventory" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center space-x-2">
                        <i class="bi bi-boxes"></i>
                        <span>Manage Inventory</span>
                    </a>
                    <a href="/sari/products" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center space-x-2">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Products</span>
                    </a>
                    <a href="/sari/reports" class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center space-x-2">
                        <i class="bi bi-graph-up"></i>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4" id="recent-activity">
                        <div class="text-center text-gray-400 py-8">
                            <i class="bi bi-activity text-3xl mb-2 block"></i>
                            <p class="text-sm">Loading recent activity...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load additional dashboard stats
async function loadDashboardData() {
    try {
        // Get total products count
        const productsResponse = await fetch('/sari/api/dashboard-stats');
        const data = await productsResponse.json();
        
        if (data.success) {
            document.getElementById('total-products').textContent = data.total_products || 0;
            document.getElementById('week-sales').textContent = '₱' + (data.week_sales || 0).toFixed(2);
        }
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Load recent activity
async function loadRecentActivity() {
    try {
        const response = await fetch('/sari/api/recent-activity');
        const data = await response.json();
        
        const container = document.getElementById('recent-activity');
        
        if (data.success && data.activities.length > 0) {
            let activityHTML = '';
            data.activities.forEach(activity => {
                activityHTML += `
                    <div class="flex items-start space-x-3">
                        <div class="bg-blue-100 p-2 rounded-full flex-shrink-0">
                            <i class="bi bi-${activity.icon} text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">${activity.title}</p>
                            <p class="text-xs text-gray-500">${activity.time}</p>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = activityHTML;
        } else {
            container.innerHTML = `
                <div class="text-center text-gray-400 py-4">
                    <i class="bi bi-clock-history text-2xl mb-2 block"></i>
                    <p class="text-sm">No recent activity</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading recent activity:', error);
        document.getElementById('recent-activity').innerHTML = `
            <div class="text-center text-gray-400 py-4">
                <p class="text-sm">Unable to load recent activity</p>
            </div>
        `;
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadRecentActivity();
});
</script>

<?php include 'views/footer.php'; ?>