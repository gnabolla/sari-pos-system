<?php
global $db;

check_session();

$page_title = "Point of Sale";
$tenant_id = $_SESSION['tenant_id'];

// Get active products for POS
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.tenant_id = ? AND p.status = 'active' AND p.stock_quantity > 0
                   ORDER BY p.name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$tenant_id]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filtering
$categories_query = "SELECT DISTINCT c.* FROM categories c 
                     JOIN products p ON c.id = p.category_id 
                     WHERE c.tenant_id = ? AND p.status = 'active' AND p.stock_quantity > 0
                     ORDER BY c.name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute([$tenant_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'views/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4 lg:mb-0">Point of Sale</h1>
        </div>
                
        <!-- Mobile-First Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Barcode Scanner & Quick Total (Mobile Priority) -->
            <div class="lg:col-span-12 order-1">
                <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                    <div class="space-y-4">
                        <!-- Barcode Scanner -->
                        <div>
                            <div class="flex space-x-2">
                                <div class="flex-1 relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="bi bi-upc-scan text-gray-400"></i>
                                    </div>
                                    <input type="text" id="product-search" placeholder="Scan barcode or search products..." autofocus
                                           class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <button onclick="clearSearch()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-3 rounded-lg transition">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500 mt-2"><i class="bi bi-info-circle mr-1"></i>Scan barcode to add items instantly</p>
                        </div>
                        <!-- Quick Total -->
                        <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg border border-green-200">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-700">Total:</span>
                                <span class="text-2xl font-bold text-green-600" id="mobile-total">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Section -->
            <div class="lg:col-span-5 order-2 lg:order-3">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 rounded-t-lg">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Cart (<span id="cart-count" class="text-blue-600">0</span> items)
                            </h3>
                            <div class="flex space-x-2">
                                <button onclick="clearCart()" class="bg-red-100 hover:bg-red-200 text-red-600 p-2 rounded-lg transition">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button onclick="toggleProducts()" class="lg:hidden bg-blue-100 hover:bg-blue-200 text-blue-600 p-2 rounded-lg transition">
                                    <i class="bi bi-grid"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="min-h-64 max-h-96 overflow-y-auto" id="cart-items">
                            <div class="text-center text-gray-400 py-8">
                                <i class="bi bi-cart3 text-4xl mb-3 block"></i>
                                <p class="text-sm">Cart is empty</p>
                            </div>
                        </div>
                                
                        <!-- Cart Total -->
                        <div class="border-t border-gray-200 pt-4 mt-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">Subtotal:</span>
                                <span class="font-semibold text-gray-900" id="cart-subtotal">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Discount:</span>
                                <div class="w-24">
                                    <input type="number" id="discount-amount" placeholder="0.00" step="0.01" min="0" onchange="updateTotals()"
                                           class="w-full text-right px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-gray-800">Total:</span>
                                    <span class="text-xl font-bold text-green-600" id="cart-total">₱0.00</span>
                                </div>
                            </div>
                        </div>
                                
                        <!-- Payment Section -->
                        <div class="mt-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                <select id="payment-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="cash">💵 Cash</option>
                                    <option value="card">💳 Card</option>
                                    <option value="digital">📱 Digital Payment</option>
                                </select>
                            </div>
                            <div id="cash-received-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cash Received</label>
                                <input type="number" id="cash-received" placeholder="0.00" step="0.01" min="0" onchange="calculateChange()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-sm text-gray-600 mt-1">Change: <span id="change-amount" class="font-semibold text-green-600">₱0.00</span></p>
                            </div>
                            <button id="checkout-btn" onclick="processCheckout()" disabled
                                    class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white py-3 px-4 rounded-lg font-medium transition duration-200 flex items-center justify-center space-x-2">
                                <i class="bi bi-credit-card"></i>
                                <span>Process Payment</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
                    
            <!-- Products Section -->
            <div class="lg:col-span-7 order-3 lg:order-2" id="products-section">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 rounded-t-lg">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                            <h3 class="text-lg font-semibold text-gray-800">Products</h3>
                            <div class="w-full sm:w-48">
                                <select id="category-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto" id="product-grid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-item bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 hover:shadow-md transition" 
                                 data-category="<?php echo $product['category_id']; ?>" 
                                 data-product='<?php echo htmlspecialchars(json_encode($product)); ?>'>
                                <div class="flex justify-between items-start h-full">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 text-sm mb-1 truncate">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h4>
                                        <p class="text-xs text-gray-500 mb-2">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                        </p>
                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold text-green-600">
                                                <?php echo format_currency($product['selling_price']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Stock: <span class="font-medium"><?php echo $product['stock_quantity']; ?></span>
                                            </p>
                                        </div>
                                    </div>
                                    <button onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="ml-3 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition flex-shrink-0">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Include JavaScript modules -->
<script src="assets/js/pos-manager.js"></script>

<style>
/* Custom POS styles */
.product-item:hover {
    transform: translateY(-1px);
}

/* Cart item styling */
.cart-item {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 12px;
}

.cart-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

/* Responsive grid adjustments */
@media (max-width: 640px) {
    #product-grid {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 641px) and (max-width: 1023px) {
    #product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Toast notifications */
.alert {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    border: none;
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-warning {
    background-color: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert-info {
    background-color: #dbeafe;
    color: #1e40af;
    border-left: 4px solid #3b82f6;
}

/* Offline mode indicator */
.offline-mode::after {
    content: "📴";
    position: absolute;
    top: -4px;
    right: -4px;
    font-size: 10px;
    background: #fbbf24;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
// Initialize POS with data from PHP
document.addEventListener('DOMContentLoaded', function() {
    if (window.posManager) {
        window.posManager.setTenantId(<?php echo json_encode($tenant_id); ?>);
        window.posManager.setProducts(<?php echo json_encode($products); ?>);
    }
});
</script>

<?php include 'views/footer.php'; ?>