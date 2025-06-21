<?php
global $db;

// Create inventory_movements table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS inventory_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
        quantity INT NOT NULL,
        reference_type ENUM('sale', 'purchase', 'adjustment', 'return') NOT NULL,
        reference_id INT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Table creation failed, but continue - maybe it already exists
}

check_session();

$page_title = "Inventory Management";
$tenant_id = $_SESSION['tenant_id'];

// Handle inventory adjustments
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'adjust_stock') {
        $product_id = intval($_POST['product_id']);
        $adjustment_type = $_POST['adjustment_type'];
        $quantity = intval($_POST['quantity']);
        $notes = sanitize_input($_POST['notes']);
        
        try {
            $db->beginTransaction();
            
            // Get current stock
            $current_stock_query = "SELECT stock_quantity FROM products WHERE id = ? AND tenant_id = ?";
            $stmt = $db->prepare($current_stock_query);
            $stmt->execute([$product_id, $tenant_id]);
            $current_stock = $stmt->fetchColumn();
            
            if ($current_stock === false) {
                throw new Exception("Product not found");
            }
            
            // Calculate new stock
            $new_stock = $current_stock;
            $movement_type = '';
            
            switch ($adjustment_type) {
                case 'add':
                    $new_stock += $quantity;
                    $movement_type = 'in';
                    break;
                case 'subtract':
                    $new_stock -= $quantity;
                    $movement_type = 'out';
                    break;
                case 'set':
                    $new_stock = $quantity;
                    $movement_type = $quantity > $current_stock ? 'in' : 'out';
                    $quantity = abs($quantity - $current_stock);
                    break;
            }
            
            if ($new_stock < 0) {
                throw new Exception("Stock cannot be negative");
            }
            
            // Update product stock
            $update_query = "UPDATE products SET stock_quantity = ? WHERE id = ? AND tenant_id = ?";
            $stmt = $db->prepare($update_query);
            $stmt->execute([$new_stock, $product_id, $tenant_id]);
            
            // Record inventory movement
            $movement_query = "INSERT INTO inventory_movements (tenant_id, product_id, user_id, movement_type, quantity, reference_type, notes) 
                               VALUES (?, ?, ?, ?, ?, 'adjustment', ?)";
            $stmt = $db->prepare($movement_query);
            $stmt->execute([$tenant_id, $product_id, $_SESSION['user_id'], $movement_type, $quantity, $notes]);
            
            $db->commit();
            $success_message = "Stock adjusted successfully!";
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get products with low stock
$low_stock_query = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.tenant_id = ? AND p.stock_quantity <= p.reorder_level AND p.status = 'active'
                    ORDER BY p.stock_quantity ASC";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute([$tenant_id]);
$low_stock_products = $low_stock_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products for inventory management
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.tenant_id = ? AND p.status = 'active'
                   ORDER BY p.name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$tenant_id]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent inventory movements
try {
    $movements_query = "SELECT im.*, p.name as product_name, u.full_name 
                        FROM inventory_movements im
                        JOIN products p ON im.product_id = p.id
                        JOIN users u ON im.user_id = u.id
                        WHERE im.tenant_id = ?
                        ORDER BY im.created_at DESC
                        LIMIT 20";
    $movements_stmt = $db->prepare($movements_query);
    $movements_stmt->execute([$tenant_id]);
    $recent_movements = $movements_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_movements = []; // If table doesn't exist, show empty movements
}

include 'views/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4 sm:mb-0">Inventory Management</h1>
            <button onclick="openAdjustModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200 flex items-center space-x-2">
                <i class="bi bi-plus-minus"></i>
                <span>Adjust Stock</span>
            </button>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="bi bi-exclamation-triangle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $error_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
                
        <!-- Low Stock Alert -->
        <?php if (!empty($low_stock_products)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-yellow-800">Low Stock Alert</h3>
                    <p class="text-sm text-yellow-700 mt-1">The following products are running low on stock:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 mt-2 space-y-1">
                        <?php foreach ($low_stock_products as $product): ?>
                            <li><?php echo htmlspecialchars($product['name']); ?> - Only <span class="font-semibold"><?php echo $product['stock_quantity']; ?></span> left (Reorder at <?php echo $product['reorder_level']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
                
        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Inventory Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Current Inventory</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                <tr class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'bg-yellow-50' : 'hover:bg-gray-50'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['unit']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm <?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'text-red-600 font-semibold' : 'text-gray-900'; ?>">
                                            <?php echo $product['stock_quantity']; ?> units
                                        </div>
                                        <div class="text-xs text-gray-500">Reorder at: <?php echo $product['reorder_level']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo format_currency($product['stock_quantity'] * $product['cost_price']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($product['stock_quantity'] <= 0): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Low Stock</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-3">
                                            <button onclick="adjustStock(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock_quantity']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 transition cursor-pointer p-1"
                                                    title="Adjust Stock">
                                                <i class="bi bi-gear text-lg"></i>
                                            </button>
                                            <a href="/sari/products" 
                                               class="text-gray-600 hover:text-gray-800 transition cursor-pointer p-1"
                                               title="Edit Product">
                                                <i class="bi bi-pencil text-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Movements -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Movements</h3>
                    </div>
                    <div class="p-6">
                        <div class="max-h-96 overflow-y-auto space-y-4">
                            <?php if (empty($recent_movements)): ?>
                                <div class="text-center text-gray-400 py-8">
                                    <i class="bi bi-clock-history text-3xl mb-2 block"></i>
                                    <p class="text-sm">No recent movements</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_movements as $movement): ?>
                                <div class="border-b border-gray-200 pb-3 last:border-b-0">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($movement['created_at'])); ?></span>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $movement['movement_type'] == 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $movement['movement_type'] == 'in' ? '+' : '-'; ?><?php echo $movement['quantity']; ?>
                                        </span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($movement['product_name']); ?></div>
                                    <div class="text-xs text-gray-500 mb-1"><?php echo ucfirst($movement['reference_type']); ?> by <?php echo htmlspecialchars($movement['full_name'] ?? 'Unknown'); ?></div>
                                    <?php if ($movement['notes']): ?>
                                        <div class="text-xs text-blue-600 italic"><?php echo htmlspecialchars($movement['notes']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div id="adjustStockModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">Adjust Stock</h3>
            <button onclick="closeAdjustModal()" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="adjust_stock">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                    <select name="product_id" id="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" data-current-stock="<?php echo $product['stock_quantity']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (Current: <?php echo $product['stock_quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="add" checked class="text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Add Stock</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="subtract" class="text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Remove Stock</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="set" class="text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Set Stock To</span>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="0" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <div class="text-sm text-gray-500 mt-1" id="current-stock-info"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Reason for adjustment..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAdjustModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Adjust Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdjustModal() {
    document.getElementById('adjustStockModal').classList.remove('hidden');
}

function closeAdjustModal() {
    document.getElementById('adjustStockModal').classList.add('hidden');
}

function adjustStock(productId, productName, currentStock) {
    document.getElementById('product_id').value = productId;
    document.getElementById('current-stock-info').textContent = `Current stock: ${currentStock}`;
    openAdjustModal();
}

document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const currentStock = selectedOption.getAttribute('data-current-stock');
    if (currentStock) {
        document.getElementById('current-stock-info').textContent = `Current stock: ${currentStock}`;
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('adjustStockModal');
    if (event.target === modal) {
        closeAdjustModal();
    }
});
</script>

<?php include 'views/footer.php'; ?>