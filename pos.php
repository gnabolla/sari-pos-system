<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Point of Sale</h2>
                    <div class="d-flex gap-2">
                        <div id="offline-sales-counter" class="badge bg-info" style="display: none;">
                            <i class="bi bi-cloud-arrow-up"></i> <span id="offline-count">0</span> pending sync
                        </div>
                        <div id="network-status" class="badge bg-success">
                            <i class="bi bi-wifi"></i> Online
                        </div>
                    </div>
                </div>
                
                <!-- Mobile-First Layout -->
                <div class="row pos-container">
                    <!-- Mobile: Primary Controls Section (Always Visible) -->
                    <div class="col-12 order-1">
                        <div class="card mb-3 mobile-primary-controls">
                            <div class="card-body p-2">
                                <!-- Barcode Scanner Input - Most Important for Mobile -->
                                <div class="mb-2">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" class="form-control" id="product-search" placeholder="Scan barcode or search..." autofocus>
                                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1">Scan barcode to add items instantly</small>
                                </div>
                                <!-- Quick Total Display -->
                                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                    <span class="fw-bold">Total:</span>
                                    <span class="fw-bold text-success fs-5" id="mobile-total">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile: Cart Section (High Priority) -->
                    <div class="col-lg-5 col-12 order-2 order-lg-3">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Cart (<span id="cart-count">0</span> items)</h6>
                                    <div>
                                        <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm d-lg-none" onclick="toggleProducts()">
                                            <i class="bi bi-grid"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <div class="cart-container" id="cart-items">
                                    <div class="text-center text-muted py-3">
                                        <i class="bi bi-cart3 fs-2"></i>
                                        <p class="mb-0 small">Cart is empty</p>
                                    </div>
                                </div>
                                
                                <!-- Mobile-Optimized Cart Total -->
                                <div class="cart-total">
                                    <div class="row">
                                        <div class="col-6"><strong>Subtotal:</strong></div>
                                        <div class="col-6 text-end"><strong id="cart-subtotal">₱0.00</strong></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">Discount:</div>
                                        <div class="col-6 text-end">
                                            <input type="number" class="form-control form-control-sm text-end" id="discount-amount" placeholder="0.00" step="0.01" min="0" onchange="updateTotals()">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6"><h6>Total:</h6></div>
                                        <div class="col-6 text-end"><h6 id="cart-total">₱0.00</h6></div>
                                    </div>
                                </div>
                                
                                <!-- Mobile-Optimized Payment Section -->
                                <div class="mt-3">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select class="form-select form-select-sm" id="payment-method">
                                                <option value="cash">💵 Cash</option>
                                                <option value="card">💳 Card</option>
                                                <option value="digital">📱 Digital Payment</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-2" id="cash-received-group">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Cash</span>
                                            <input type="number" class="form-control" id="cash-received" placeholder="0.00" step="0.01" min="0" onchange="calculateChange()">
                                        </div>
                                        <small class="text-muted">Change: <span id="change-amount" class="fw-bold">₱0.00</span></small>
                                    </div>
                                    <button class="btn btn-success btn-lg w-100 mt-2" id="checkout-btn" onclick="processCheckout()" disabled>
                                        <i class="bi bi-credit-card"></i> Process Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Section (Mobile: Collapsible, Desktop: Always Visible) -->
                    <div class="col-lg-7 col-12 order-3 order-lg-2" id="products-section">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center g-2">
                                    <div class="col-6 col-md-6">
                                        <h6 class="mb-0">Products</h6>
                                    </div>
                                    <div class="col-6 col-md-6">
                                        <select class="form-select form-select-sm" id="category-filter">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <div class="product-grid" id="product-grid">
                                    <?php foreach ($products as $product): ?>
                                    <div class="product-item" data-category="<?php echo $product['category_id']; ?>" data-product='<?php echo htmlspecialchars(json_encode($product)); ?>'>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 small"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></small>
                                                <div class="mt-1">
                                                    <span class="fw-bold text-success small"><?php echo format_currency($product['selling_price']); ?></span>
                                                    <span class="text-muted ms-2 small">Stock: <?php echo $product['stock_quantity']; ?></span>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
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
</div>

<!-- Include offline storage functionality -->
<script src="js/offline-storage.js"></script>

<style>
/* Offline status styles */
#network-status {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

#offline-sales-counter {
    font-size: 0.75rem;
    padding: 0.35rem 0.6rem;
    cursor: pointer;
}

#offline-sales-counter:hover {
    opacity: 0.8;
}

/* Toast notification styles */
.alert.position-fixed {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: none;
    border-radius: 8px;
}

.alert-success {
    background-color: #d1e7dd;
    color: #0a3622;
    border-left: 4px solid #198754;
}

.alert-warning {
    background-color: #fff3cd;
    color: #664d03;
    border-left: 4px solid #ffc107;
}

.alert-info {
    background-color: #cfe2ff;
    color: #055160;
    border-left: 4px solid #0dcaf0;
}

/* Mobile responsive badges */
@media (max-width: 576px) {
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.25rem !important;
    }
    
    #network-status, #offline-sales-counter {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

/* Offline mode visual cues */
.offline-mode {
    position: relative;
}

.offline-mode::after {
    content: "📴";
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 12px;
    background: #ffc107;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
let cart = [];
let cartTotal = 0;
let isOffline = false;
let offlineStorage = null;
let networkManager = null;
let tenantId = <?php echo json_encode($tenant_id); ?>;
let originalProducts = <?php echo json_encode($products); ?>;

// Initialize offline functionality
async function initializeOfflineMode() {
    try {
        // Wait for offline storage to be available
        while (!window.offlineStorage || !window.networkManager) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        offlineStorage = window.offlineStorage;
        networkManager = window.networkManager;
        
        // Wait for offline storage to initialize
        await offlineStorage.init();
        
        // Cache current products
        await offlineStorage.cacheProducts(originalProducts, tenantId);
        
        // Set up network status monitoring
        networkManager.onStatusChange(handleNetworkStatusChange);
        
        // Initial network status check
        updateNetworkStatus(navigator.onLine);
        
        // Sync any pending offline sales
        if (navigator.onLine) {
            await syncOfflineSales();
        }
        
        console.log('Offline mode initialized successfully');
    } catch (error) {
        console.error('Failed to initialize offline mode:', error);
    }
}

// Handle network status changes
function handleNetworkStatusChange(status) {
    const isOnline = status === 'online';
    updateNetworkStatus(isOnline);
    
    if (isOnline) {
        syncOfflineSales();
        showSuccessMessage('Connection restored! Syncing offline data...');
    } else {
        showWarningMessage('You are now offline. Sales will be stored locally.');
    }
}

// Update network status indicator
function updateNetworkStatus(online) {
    isOffline = !online;
    const statusElement = document.getElementById('network-status');
    
    if (online) {
        statusElement.className = 'badge bg-success';
        statusElement.innerHTML = '<i class="bi bi-wifi"></i> Online';
    } else {
        statusElement.className = 'badge bg-warning';
        statusElement.innerHTML = '<i class="bi bi-wifi-off"></i> Offline';
    }
    
    // Update offline sales counter
    updateOfflineSalesCounter();
}

// Update offline sales counter
async function updateOfflineSalesCounter() {
    if (!offlineStorage) return;
    
    try {
        const offlineSales = await offlineStorage.getOfflineSales();
        const pendingSales = offlineSales.filter(sale => !sale.synced);
        const counterElement = document.getElementById('offline-sales-counter');
        const countElement = document.getElementById('offline-count');
        
        if (pendingSales.length > 0) {
            countElement.textContent = pendingSales.length;
            counterElement.style.display = 'block';
        } else {
            counterElement.style.display = 'none';
        }
    } catch (error) {
        console.error('Error updating offline sales counter:', error);
    }
}

// Sync offline sales when connection is restored
async function syncOfflineSales() {
    if (!offlineStorage || isOffline) return;
    
    try {
        const offlineSales = await offlineStorage.getOfflineSales();
        
        for (const sale of offlineSales) {
            if (!sale.synced) {
                try {
                    const response = await fetch('/sari/api/process-sale', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ...sale,
                            offline_sale: true,
                            original_timestamp: sale.timestamp
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        await offlineStorage.removeSyncedSale(sale.id);
                        console.log('Synced offline sale:', sale.id);
                    } else {
                        console.error('Failed to sync sale:', sale.id, result.message);
                    }
                } catch (error) {
                    console.error('Error syncing sale:', sale.id, error);
                }
            }
        }
        
        const remainingSales = await offlineStorage.getOfflineSales();
        if (remainingSales.length === 0) {
            showSuccessMessage('All offline sales synced successfully!');
        }
        
        // Update counter after sync
        updateOfflineSalesCounter();
    } catch (error) {
        console.error('Error during sync:', error);
    }
}

// Load products from cache when offline
async function loadOfflineProducts() {
    if (!offlineStorage) return;
    
    try {
        const cachedProducts = await offlineStorage.getCachedProducts(tenantId);
        if (cachedProducts.length > 0) {
            updateProductGrid(cachedProducts);
            showInfoMessage('Showing cached products (offline mode)');
        }
    } catch (error) {
        console.error('Error loading offline products:', error);
    }
}

// Update product grid with new products
function updateProductGrid(products) {
    const productGrid = document.getElementById('product-grid');
    
    let gridHTML = '';
    products.forEach(product => {
        gridHTML += `
            <div class="product-item" data-category="${product.category_id}" data-product='${JSON.stringify(product).replace(/'/g, '&apos;')}'>
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 small">${escapeHtml(product.name)}</h6>
                        <small class="text-muted">${escapeHtml(product.category_name || 'No Category')}</small>
                        <div class="mt-1">
                            <span class="fw-bold text-success small">₱${parseFloat(product.selling_price).toFixed(2)}</span>
                            <span class="text-muted ms-2 small">Stock: ${product.stock_quantity}</span>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="addToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    productGrid.innerHTML = gridHTML;
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show different types of messages
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

function showWarningMessage(message) {
    showMessage(message, 'warning');
}

function showInfoMessage(message) {
    showMessage(message, 'info');
}

function showMessage(message, type) {
    // Create a toast-like notification
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Product search and filtering
document.getElementById('product-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    filterProducts();
});

// Barcode scanner support - scanners typically send Enter key after scanning
document.getElementById('product-search').addEventListener('keypress', async function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const barcode = this.value.trim();
        
        // Try to find product by exact barcode match
        if (barcode) {
            let foundProduct = null;
            
            // First try to find in currently displayed products
            const products = document.querySelectorAll('.product-item');
            products.forEach(product => {
                const productData = JSON.parse(product.dataset.product);
                if (productData.barcode && productData.barcode === barcode) {
                    foundProduct = productData;
                }
            });
            
            // If not found and offline storage is available, check cached products
            if (!foundProduct && offlineStorage) {
                try {
                    foundProduct = await offlineStorage.findProductByBarcode(barcode);
                } catch (error) {
                    console.error('Error searching offline products:', error);
                }
            }
            
            if (foundProduct) {
                addToCart(foundProduct);
                this.value = ''; // Clear the search field
                showSuccessMessage('Product added: ' + foundProduct.name);
                refocusSearch(); // Keep focus for continuous scanning
            } else {
                // If no exact barcode match, keep the normal search behavior
                filterProducts();
                if (isOffline) {
                    showWarningMessage('Product not found in offline cache');
                }
            }
        }
    }
});

document.getElementById('category-filter').addEventListener('change', function() {
    filterProducts();
});

function filterProducts() {
    const searchTerm = document.getElementById('product-search').value.toLowerCase();
    const categoryFilter = document.getElementById('category-filter').value;
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const productData = JSON.parse(product.dataset.product);
        const matchesSearch = productData.name.toLowerCase().includes(searchTerm) || 
                             (productData.barcode && productData.barcode.toLowerCase().includes(searchTerm));
        const matchesCategory = !categoryFilter || product.dataset.category === categoryFilter;
        
        product.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
    });
}

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock_quantity) {
            existingItem.quantity++;
            existingItem.total = existingItem.quantity * existingItem.selling_price;
        } else {
            alert('Not enough stock available!');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            selling_price: parseFloat(product.selling_price),
            quantity: 1,
            total: parseFloat(product.selling_price),
            stock_quantity: product.stock_quantity
        });
    }
    
    updateCartDisplay();
    updateTotals();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
    updateTotals();
}

function updateQuantity(productId, quantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        if (quantity <= 0) {
            removeFromCart(productId);
        } else if (quantity <= item.stock_quantity) {
            item.quantity = quantity;
            item.total = item.quantity * item.selling_price;
            updateCartDisplay();
            updateTotals();
        } else {
            alert('Not enough stock available!');
        }
    }
}

function updateCartDisplay() {
    const cartContainer = document.getElementById('cart-items');
    
    // Update cart count
    document.getElementById('cart-count').textContent = cart.length;
    
    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="bi bi-cart3 fs-2"></i>
                <p class="mb-0 small">Cart is empty</p>
            </div>
        `;
        document.getElementById('checkout-btn').disabled = true;
        return;
    }
    
    let cartHTML = '';
    cart.forEach(item => {
        cartHTML += `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 small">${item.name}</h6>
                        <small class="text-success">₱${item.selling_price.toFixed(2)} each</small>
                    </div>
                    <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row align-items-center mt-2">
                    <div class="col-7">
                        <div class="input-group input-group-sm">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                            <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="${item.stock_quantity}" onchange="updateQuantity(${item.id}, parseInt(this.value))">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <div class="col-5 text-end">
                        <strong class="small">₱${item.total.toFixed(2)}</strong>
                    </div>
                </div>
            </div>
        `;
    });
    
    cartContainer.innerHTML = cartHTML;
    document.getElementById('checkout-btn').disabled = false;
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
    const discount = parseFloat(document.getElementById('discount-amount').value) || 0;
    const total = subtotal - discount;
    
    document.getElementById('cart-subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('cart-total').textContent = '₱' + total.toFixed(2);
    
    // Update mobile total display
    document.getElementById('mobile-total').textContent = '₱' + total.toFixed(2);
    
    cartTotal = total;
    
    calculateChange();
}

function calculateChange() {
    const paymentMethod = document.getElementById('payment-method').value;
    const cashReceivedGroup = document.getElementById('cash-received-group');
    
    if (paymentMethod === 'cash') {
        cashReceivedGroup.style.display = 'block';
        const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
        const change = cashReceived - cartTotal;
        document.getElementById('change-amount').textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
    } else {
        cashReceivedGroup.style.display = 'none';
        document.getElementById('change-amount').textContent = '₱0.00';
    }
}

function clearCart() {
    if (cart.length > 0 && confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
        updateTotals();
    }
}

async function processCheckout() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    const paymentMethod = document.getElementById('payment-method').value;
    const discount = parseFloat(document.getElementById('discount-amount').value) || 0;
    
    if (paymentMethod === 'cash') {
        const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
        if (cashReceived < cartTotal) {
            alert('Insufficient cash received!');
            return;
        }
    }
    
    // Process the sale
    const saleData = {
        items: cart,
        payment_method: paymentMethod,
        discount_amount: discount,
        total_amount: cartTotal,
        tenant_id: tenantId
    };
    
    // If offline, save to local storage
    if (isOffline) {
        try {
            const offlineSale = await offlineStorage.saveSaleOffline(saleData);
            showSuccessMessage(`Sale saved offline!\nTransaction: ${offlineSale.id}`);
            
            // Clear cart
            cart = [];
            updateCartDisplay();
            updateTotals();
            document.getElementById('discount-amount').value = '';
            document.getElementById('cash-received').value = '';
            
            // Update offline counter
            updateOfflineSalesCounter();
            
            refocusSearch();
        } catch (error) {
            console.error('Error saving offline sale:', error);
            alert('Error saving offline sale. Please try again.');
        }
        return;
    }
    
    // Online processing
    try {
        const response = await fetch('/sari/api/process-sale', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(saleData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccessMessage('Sale processed successfully!\nTransaction: ' + data.transaction_number);
            cart = [];
            updateCartDisplay();
            updateTotals();
            document.getElementById('discount-amount').value = '';
            document.getElementById('cash-received').value = '';
            refocusSearch();
        } else {
            alert('Error processing sale: ' + data.message);
        }
    } catch (error) {
        // If online processing fails, try to save offline
        if (offlineStorage) {
            try {
                const offlineSale = await offlineStorage.saveSaleOffline(saleData);
                showWarningMessage(`Network error! Sale saved offline.\nTransaction: ${offlineSale.id}`);
                
                // Clear cart
                cart = [];
                updateCartDisplay();
                updateTotals();
                document.getElementById('discount-amount').value = '';
                document.getElementById('cash-received').value = '';
                
                // Update offline counter
                updateOfflineSalesCounter();
                
                refocusSearch();
            } catch (offlineError) {
                console.error('Error saving offline sale:', offlineError);
                alert('Error processing sale and saving offline. Please try again.');
            }
        } else {
            alert('Error processing sale. Please try again.');
        }
    }
}

// Clear search field
function clearSearch() {
    document.getElementById('product-search').value = '';
    filterProducts();
    document.getElementById('product-search').focus();
}

// Auto-focus on search field for quick scanning
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('product-search').focus();
});

// Keep focus on search field after adding product
function refocusSearch() {
    setTimeout(() => {
        document.getElementById('product-search').focus();
    }, 100);
}

// Mobile-specific functions
function toggleProducts() {
    const productsSection = document.getElementById('products-section');
    const isVisible = productsSection.style.display !== 'none';
    
    if (isVisible) {
        productsSection.style.display = 'none';
    } else {
        productsSection.style.display = 'block';
    }
}

// Initialize mobile view
function initializeMobileView() {
    const isMobile = window.innerWidth < 992; // Bootstrap lg breakpoint
    
    if (isMobile) {
        // On mobile, initially hide products section if cart has items
        if (cart.length > 0) {
            document.getElementById('products-section').style.display = 'none';
        }
    } else {
        // On desktop, always show products section
        document.getElementById('products-section').style.display = 'block';
    }
}

// Handle window resize
window.addEventListener('resize', function() {
    const isMobile = window.innerWidth < 992;
    if (!isMobile) {
        // Always show products section on desktop
        document.getElementById('products-section').style.display = 'block';
    }
});

// Mobile optimization: Override addToCart to include mobile-specific behavior
window.originalAddToCart = addToCart;
addToCart = function(product) {
    window.originalAddToCart(product);
    
    // On mobile, hide products section after adding first item to focus on cart
    const isMobile = window.innerWidth < 992;
    if (isMobile && cart.length === 1) {
        setTimeout(() => {
            document.getElementById('products-section').style.display = 'none';
        }, 500);
    }
};

// Add click handler for offline counter
document.getElementById('offline-sales-counter').addEventListener('click', async function() {
    if (!offlineStorage) return;
    
    try {
        const offlineSales = await offlineStorage.getOfflineSales();
        const pendingSales = offlineSales.filter(sale => !sale.synced);
        
        if (pendingSales.length > 0) {
            const message = `You have ${pendingSales.length} offline sale(s) waiting to sync:\n\n` +
                          pendingSales.map(sale => `• ${sale.id} - ₱${sale.total_amount.toFixed(2)}`).join('\n') +
                          '\n\nThese will sync automatically when internet connection is restored.';
            alert(message);
        }
    } catch (error) {
        console.error('Error showing offline sales details:', error);
    }
});

// Periodic sync check (every 30 seconds when online)
setInterval(() => {
    if (!isOffline && offlineStorage) {
        syncOfflineSales();
    }
}, 30000);

// Initialize
document.getElementById('payment-method').addEventListener('change', calculateChange);
document.addEventListener('DOMContentLoaded', function() {
    initializeMobileView();
    initializeOfflineMode();
});
</script>

<?php include 'views/footer.php'; ?>