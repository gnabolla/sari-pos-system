/**
 * POS Manager Module
 * Handles Point of Sale operations
 */
class POSManager {
    constructor() {
        this.cart = [];
        this.cartTotal = 0;
        this.tenantId = null;
        this.originalProducts = [];
        
        this.init();
    }
    
    init() {
        // Set up event listeners
        this.setupEventListeners();
        
        // Focus on search field
        this.refocusSearch();
        
        // Initialize mobile view
        this.initializeMobileView();
    }
    
    setupEventListeners() {
        // Product search
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.filterProducts());
            searchInput.addEventListener('keypress', (e) => this.handleBarcodeSearch(e));
        }
        
        // Category filter
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => this.filterProducts());
        }
        
        // Payment method change
        const paymentMethod = document.getElementById('payment-method');
        if (paymentMethod) {
            paymentMethod.addEventListener('change', () => this.calculateChange());
        }
        
        // Window resize
        window.addEventListener('resize', () => this.handleWindowResize());
    }
    
    handleBarcodeSearch(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const barcode = event.target.value.trim();
            
            if (barcode) {
                let foundProduct = null;
                
                // Try to find product by exact barcode match
                const products = document.querySelectorAll('.product-item');
                products.forEach(product => {
                    const productData = JSON.parse(product.dataset.product);
                    if (productData.barcode && productData.barcode === barcode) {
                        foundProduct = productData;
                    }
                });
                
                if (foundProduct) {
                    this.addToCart(foundProduct);
                    event.target.value = '';
                    this.showMessage('Product added: ' + foundProduct.name, 'success');
                    this.refocusSearch();
                } else {
                    this.filterProducts();
                }
            }
        }
    }
    
    filterProducts() {
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
    
    addToCart(product) {
        const existingItem = this.cart.find(item => item.id === product.id);
        
        if (existingItem) {
            if (existingItem.quantity < product.stock_quantity) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.selling_price;
            } else {
                alert('Not enough stock available!');
                return;
            }
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                selling_price: parseFloat(product.selling_price),
                quantity: 1,
                total: parseFloat(product.selling_price),
                stock_quantity: product.stock_quantity
            });
        }
        
        this.updateCartDisplay();
        this.updateTotals();
        
        // Mobile optimization: hide products section after adding first item
        const isMobile = window.innerWidth < 1024;
        if (isMobile && this.cart.length === 1) {
            setTimeout(() => {
                document.getElementById('products-section').classList.add('hidden');
            }, 500);
        }
    }
    
    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.updateCartDisplay();
        this.updateTotals();
    }
    
    updateQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else if (quantity <= item.stock_quantity) {
                item.quantity = quantity;
                item.total = item.quantity * item.selling_price;
                this.updateCartDisplay();
                this.updateTotals();
            } else {
                alert('Not enough stock available!');
            }
        }
    }
    
    updateCartDisplay() {
        const cartContainer = document.getElementById('cart-items');
        
        // Update cart count
        document.getElementById('cart-count').textContent = this.cart.length;
        
        if (this.cart.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center text-gray-400 py-8">
                    <i class="bi bi-cart3 text-4xl mb-3 block"></i>
                    <p class="text-sm">Cart is empty</p>
                </div>
            `;
            document.getElementById('checkout-btn').disabled = true;
            return;
        }
        
        let cartHTML = '';
        this.cart.forEach(item => {
            cartHTML += `
                <div class="cart-item border-b border-gray-200 pb-3 mb-3 last:border-b-0 last:mb-0">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-900 text-sm mb-1">${item.name}</h4>
                            <p class="text-xs text-green-600">₱${item.selling_price.toFixed(2)} each</p>
                        </div>
                        <button onclick="posManager.removeFromCart(${item.id})" class="bg-red-100 hover:bg-red-200 text-red-600 p-1 rounded transition ml-2">
                            <i class="bi bi-trash text-xs"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button onclick="posManager.updateQuantity(${item.id}, ${item.quantity - 1})" class="bg-gray-100 hover:bg-gray-200 text-gray-600 w-8 h-8 rounded flex items-center justify-center transition">-</button>
                            <input type="number" value="${item.quantity}" min="1" max="${item.stock_quantity}" onchange="posManager.updateQuantity(${item.id}, parseInt(this.value))" class="w-16 text-center px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button onclick="posManager.updateQuantity(${item.id}, ${item.quantity + 1})" class="bg-gray-100 hover:bg-gray-200 text-gray-600 w-8 h-8 rounded flex items-center justify-center transition">+</button>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-sm text-gray-900">₱${item.total.toFixed(2)}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartContainer.innerHTML = cartHTML;
        document.getElementById('checkout-btn').disabled = false;
    }
    
    updateTotals() {
        const subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
        const discount = parseFloat(document.getElementById('discount-amount').value) || 0;
        const total = subtotal - discount;
        
        document.getElementById('cart-subtotal').textContent = '₱' + subtotal.toFixed(2);
        document.getElementById('cart-total').textContent = '₱' + total.toFixed(2);
        
        // Update mobile total display
        const mobileTotal = document.getElementById('mobile-total');
        if (mobileTotal) {
            mobileTotal.textContent = '₱' + total.toFixed(2);
        }
        
        this.cartTotal = total;
        this.calculateChange();
    }
    
    calculateChange() {
        const paymentMethod = document.getElementById('payment-method').value;
        const cashReceivedGroup = document.getElementById('cash-received-group');
        
        if (paymentMethod === 'cash') {
            cashReceivedGroup.classList.remove('hidden');
            const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
            const change = cashReceived - this.cartTotal;
            document.getElementById('change-amount').textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
        } else {
            cashReceivedGroup.classList.add('hidden');
            document.getElementById('change-amount').textContent = '₱0.00';
        }
    }
    
    clearCart() {
        if (this.cart.length > 0 && confirm('Are you sure you want to clear the cart?')) {
            this.cart = [];
            this.updateCartDisplay();
            this.updateTotals();
        }
    }
    
    clearSearch() {
        document.getElementById('product-search').value = '';
        this.filterProducts();
        document.getElementById('product-search').focus();
    }
    
    refocusSearch() {
        setTimeout(() => {
            const searchInput = document.getElementById('product-search');
            if (searchInput) {
                searchInput.focus();
            }
        }, 100);
    }
    
    toggleProducts() {
        const productsSection = document.getElementById('products-section');
        productsSection.classList.toggle('hidden');
    }
    
    initializeMobileView() {
        const isMobile = window.innerWidth < 1024;
        
        if (isMobile) {
            if (this.cart.length > 0) {
                document.getElementById('products-section').classList.add('hidden');
            }
        } else {
            document.getElementById('products-section').classList.remove('hidden');
        }
    }
    
    handleWindowResize() {
        const isMobile = window.innerWidth < 1024;
        if (!isMobile) {
            document.getElementById('products-section').classList.remove('hidden');
        }
    }
    
    async processCheckout() {
        if (this.cart.length === 0) {
            alert('Cart is empty!');
            return;
        }
        
        const paymentMethod = document.getElementById('payment-method').value;
        const discount = parseFloat(document.getElementById('discount-amount').value) || 0;
        
        if (paymentMethod === 'cash') {
            const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
            if (cashReceived < this.cartTotal) {
                alert('Insufficient cash received!');
                return;
            }
        }
        
        const saleData = {
            items: this.cart,
            payment_method: paymentMethod,
            discount_amount: discount,
            total_amount: this.cartTotal,
            tenant_id: this.tenantId
        };
        
        // Process sale online
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
                this.showMessage('Sale processed successfully!\nTransaction: ' + data.transaction_number, 'success');
                this.clearCheckoutForm();
                this.refocusSearch();
            } else {
                alert('Error processing sale: ' + data.message);
            }
        } catch (error) {
            console.error('Error processing sale:', error);
            alert('Error processing sale. Please try again.');
        }
    }
    
    clearCheckoutForm() {
        this.cart = [];
        this.updateCartDisplay();
        this.updateTotals();
        document.getElementById('discount-amount').value = '';
        document.getElementById('cash-received').value = '';
    }
    
    showMessage(message, type) {
        // Create a toast-like notification
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    setTenantId(tenantId) {
        this.tenantId = tenantId;
    }
    
    setProducts(products) {
        this.originalProducts = products;
    }
}

// Initialize POS Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.posManager = new POSManager();
});

// Make functions available globally for backward compatibility
window.addToCart = function(product) {
    if (window.posManager) {
        window.posManager.addToCart(product);
    }
};

window.removeFromCart = function(productId) {
    if (window.posManager) {
        window.posManager.removeFromCart(productId);
    }
};

window.updateQuantity = function(productId, quantity) {
    if (window.posManager) {
        window.posManager.updateQuantity(productId, quantity);
    }
};

window.clearCart = function() {
    if (window.posManager) {
        window.posManager.clearCart();
    }
};

window.clearSearch = function() {
    if (window.posManager) {
        window.posManager.clearSearch();
    }
};

window.toggleProducts = function() {
    if (window.posManager) {
        window.posManager.toggleProducts();
    }
};

window.processCheckout = function() {
    if (window.posManager) {
        window.posManager.processCheckout();
    }
};