<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Products Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus"></i> Add Product
                    </button>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Barcode</th>
                                        <th>Unit</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'table-warning' : ''; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($product['name']); ?>
                                            <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                                <small class="text-danger"><br>Low Stock!</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                        <td>
                                            <?php if (!empty($product['barcode'])): ?>
                                                <i class="bi bi-upc-scan text-success me-1"></i>
                                                <?php echo htmlspecialchars($product['barcode']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No barcode</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                        <td><?php echo format_currency($product['cost_price']); ?></td>
                                        <td><?php echo format_currency($product['selling_price']); ?></td>
                                        <td>
                                            <span class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'low-stock' : ''; ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo ucfirst($product['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
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
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="barcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" name="barcode">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit" class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" value="piece">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price *</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="selling_price" class="form-label">Selling Price *</label>
                                <input type="number" class="form-control" name="selling_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Initial Stock</label>
                                <input type="number" class="form-control" name="stock_quantity" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" name="reorder_level" value="5">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editProductForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_category_id" class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="edit_category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_barcode" class="form-label">Barcode</label>
                                <input type="text" class="form-control" name="barcode" id="edit_barcode">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_unit" class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" id="edit_unit">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cost_price" class="form-label">Cost Price *</label>
                                <input type="number" class="form-control" name="cost_price" id="edit_cost_price" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_selling_price" class="form-label">Selling Price *</label>
                                <input type="number" class="form-control" name="selling_price" id="edit_selling_price" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock_quantity" id="edit_stock_quantity">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" name="reorder_level" id="edit_reorder_level">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>

<?php include 'views/footer.php'; ?>