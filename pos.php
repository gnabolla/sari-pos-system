<?php
session_start();
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
                <h2 class="mb-4">Point of Sale</h2>
                
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

<script>
let cart = [];
let cartTotal = 0;

// Product search and filtering
document.getElementById('product-search').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    filterProducts();
});

// Barcode scanner support - scanners typically send Enter key after scanning
document.getElementById('product-search').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const barcode = this.value.trim();
        
        // Try to find product by exact barcode match
        if (barcode) {
            const products = document.querySelectorAll('.product-item');
            let foundProduct = null;
            
            products.forEach(product => {
                const productData = JSON.parse(product.dataset.product);
                if (productData.barcode && productData.barcode === barcode) {
                    foundProduct = productData;
                }
            });
            
            if (foundProduct) {
                addToCart(foundProduct);
                this.value = ''; // Clear the search field
                showSuccessMessage('Product added: ' + foundProduct.name);
                refocusSearch(); // Keep focus for continuous scanning
            } else {
                // If no exact barcode match, keep the normal search behavior
                filterProducts();
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

function processCheckout() {
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
        total_amount: cartTotal
    };
    
    fetch('api/process_sale.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sale processed successfully!\nTransaction: ' + data.transaction_number);
            cart = [];
            updateCartDisplay();
            updateTotals();
            document.getElementById('discount-amount').value = '';
            document.getElementById('cash-received').value = '';
        } else {
            alert('Error processing sale: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error processing sale. Please try again.');
    });
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

// Initialize
document.getElementById('payment-method').addEventListener('change', calculateChange);
document.addEventListener('DOMContentLoaded', function() {
    initializeMobileView();
});
</script>

<?php include 'views/footer.php'; ?>