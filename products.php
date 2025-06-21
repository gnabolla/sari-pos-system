<?php
global $db;

if (!isset($_SESSION['tenant_id'])) {
    header('Location: /sari/login');
    exit();
}

$page_title = "Products Management";
$tenant_id = $_SESSION['tenant_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize_input($_POST['name']);
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $description = sanitize_input($_POST['description']);
                $barcode = sanitize_input($_POST['barcode']);
                $unit = sanitize_input($_POST['unit']);
                $cost_price = floatval($_POST['cost_price']);
                $selling_price = floatval($_POST['selling_price']);
                $stock_quantity = intval($_POST['stock_quantity']);
                $reorder_level = intval($_POST['reorder_level']);
                
                $query = "INSERT INTO products (tenant_id, category_id, name, description, barcode, unit, cost_price, selling_price, stock_quantity, reorder_level) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$tenant_id, $category_id, $name, $description, $barcode, $unit, $cost_price, $selling_price, $stock_quantity, $reorder_level]);
                $success_message = "Product added successfully!";
                break;
                
            case 'edit':
                $id = intval($_POST['id']);
                $name = sanitize_input($_POST['name']);
                $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $description = sanitize_input($_POST['description']);
                $barcode = sanitize_input($_POST['barcode']);
                $unit = sanitize_input($_POST['unit']);
                $cost_price = floatval($_POST['cost_price']);
                $selling_price = floatval($_POST['selling_price']);
                $stock_quantity = intval($_POST['stock_quantity']);
                $reorder_level = intval($_POST['reorder_level']);
                $status = $_POST['status'];
                
                $query = "UPDATE products SET category_id = ?, name = ?, description = ?, barcode = ?, unit = ?, 
                          cost_price = ?, selling_price = ?, stock_quantity = ?, reorder_level = ?, status = ? 
                          WHERE id = ? AND tenant_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$category_id, $name, $description, $barcode, $unit, $cost_price, $selling_price, $stock_quantity, $reorder_level, $status, $id, $tenant_id]);
                $success_message = "Product updated successfully!";
                break;
        }
    }
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories WHERE tenant_id = ? ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute([$tenant_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with category names
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.tenant_id = ? 
                   ORDER BY p.name";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$tenant_id]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'views/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-4 sm:mb-0">Products Management</h1>
            <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200 flex items-center space-x-2">
                <i class="bi bi-plus"></i>
                <span>Add Product</span>
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
        
        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barcode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                        <tr class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'bg-yellow-50' : 'hover:bg-gray-50'; ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['unit']); ?></div>
                                    <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                        <div class="text-xs text-red-600 font-medium">⚠️ Low Stock!</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($product['barcode'])): ?>
                                    <div class="flex items-center">
                                        <i class="bi bi-upc-scan text-green-600 mr-2"></i>
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($product['barcode']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">No barcode</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div>Cost: <span class="font-medium"><?php echo format_currency($product['cost_price']); ?></span></div>
                                    <div>Sell: <span class="font-medium text-green-600"><?php echo format_currency($product['selling_price']); ?></span></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'text-red-600 font-semibold' : 'text-gray-900'; ?>">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </div>
                                    <div class="text-xs text-gray-500">Reorder at: <?php echo $product['reorder_level']; ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $product['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-600 p-2 rounded-lg transition">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">Add New Product</h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                    <input type="text" name="barcode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                    <input type="text" name="unit" value="piece" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price *</label>
                    <input type="number" name="cost_price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price *</label>
                    <input type="number" name="selling_price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Initial Stock</label>
                    <input type="number" name="stock_quantity" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                    <input type="number" name="reorder_level" value="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">Edit Product</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" id="edit_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                    <input type="text" name="barcode" id="edit_barcode" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                    <input type="text" name="unit" id="edit_unit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price *</label>
                    <input type="number" name="cost_price" id="edit_cost_price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price *</label>
                    <input type="number" name="selling_price" id="edit_selling_price" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                    <input type="number" name="stock_quantity" id="edit_stock_quantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                    <input type="number" name="reorder_level" id="edit_reorder_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition">Cancel</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Update Product</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addProductModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addProductModal').classList.add('hidden');
}

function openEditModal() {
    document.getElementById('editProductModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editProductModal').classList.add('hidden');
}

function editProduct(product) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_category_id').value = product.category_id || '';
    document.getElementById('edit_barcode').value = product.barcode || '';
    document.getElementById('edit_unit').value = product.unit;
    document.getElementById('edit_cost_price').value = product.cost_price;
    document.getElementById('edit_selling_price').value = product.selling_price;
    document.getElementById('edit_stock_quantity').value = product.stock_quantity;
    document.getElementById('edit_reorder_level').value = product.reorder_level;
    document.getElementById('edit_status').value = product.status;
    document.getElementById('edit_description').value = product.description || '';
    
    openEditModal();
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const addModal = document.getElementById('addProductModal');
    const editModal = document.getElementById('editProductModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
});
</script>

<?php include 'views/footer.php'; ?>