<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

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
$movements_query = "SELECT im.*, p.name as product_name, u.first_name, u.last_name 
                    FROM inventory_movements im
                    JOIN products p ON im.product_id = p.id
                    JOIN users u ON im.user_id = u.id
                    WHERE im.tenant_id = ?
                    ORDER BY im.created_at DESC
                    LIMIT 20";
$movements_stmt = $db->prepare($movements_query);
$movements_stmt->execute([$tenant_id]);
$recent_movements = $movements_stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <h2>Inventory Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                        <i class="bi bi-plus-minus"></i> Adjust Stock
                    </button>
                </div>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($low_stock_products)): ?>
                <div class="alert alert-warning">
                    <h6><i class="bi bi-exclamation-triangle"></i> Low Stock Alert</h6>
                    <p>The following products are running low on stock:</p>
                    <ul class="mb-0">
                        <?php foreach ($low_stock_products as $product): ?>
                            <li><?php echo htmlspecialchars($product['name']); ?> - Only <?php echo $product['stock_quantity']; ?> left (Reorder at <?php echo $product['reorder_level']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>Current Inventory</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Current Stock</th>
                                                <th>Reorder Level</th>
                                                <th>Value</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): ?>
                                            <tr class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'table-warning' : ''; ?>">
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                                <td>
                                                    <span class="<?php echo $product['stock_quantity'] <= $product['reorder_level'] ? 'low-stock' : ''; ?>">
                                                        <?php echo $product['stock_quantity']; ?> <?php echo htmlspecialchars($product['unit']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $product['reorder_level']; ?></td>
                                                <td><?php echo format_currency($product['stock_quantity'] * $product['cost_price']); ?></td>
                                                <td>
                                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    <?php elseif ($product['stock_quantity'] <= $product['reorder_level']): ?>
                                                        <span class="badge bg-warning">Low Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">In Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="adjustStock(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['stock_quantity']; ?>)">
                                                        <i class="bi bi-plus-minus"></i>
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
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Recent Inventory Movements</h5>
                            </div>
                            <div class="card-body">
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($recent_movements as $movement): ?>
                                    <div class="border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($movement['created_at'])); ?></small>
                                            <span class="badge <?php echo $movement['movement_type'] == 'in' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $movement['movement_type'] == 'in' ? '+' : '-'; ?><?php echo $movement['quantity']; ?>
                                            </span>
                                        </div>
                                        <div><strong><?php echo htmlspecialchars($movement['product_name']); ?></strong></div>
                                        <div><small class="text-muted"><?php echo ucfirst($movement['reference_type']); ?> by <?php echo htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name']); ?></small></div>
                                        <?php if ($movement['notes']): ?>
                                            <div><small class="text-info"><?php echo htmlspecialchars($movement['notes']); ?></small></div>
                                        <?php endif; ?>
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="adjust_stock">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-select" name="product_id" id="product_id" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" data-current-stock="<?php echo $product['stock_quantity']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> (Current: <?php echo $product['stock_quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="adjustment_type" id="add" value="add" checked>
                                <label class="form-check-label" for="add">Add Stock</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="adjustment_type" id="subtract" value="subtract">
                                <label class="form-check-label" for="subtract">Remove Stock</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="adjustment_type" id="set" value="set">
                                <label class="form-check-label" for="set">Set Stock To</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="quantity" id="quantity" min="0" required>
                        <div class="form-text" id="current-stock-info"></div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Reason for adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adjustStock(productId, productName, currentStock) {
    document.getElementById('product_id').value = productId;
    document.getElementById('current-stock-info').textContent = `Current stock: ${currentStock}`;
    new bootstrap.Modal(document.getElementById('adjustStockModal')).show();
}

document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const currentStock = selectedOption.getAttribute('data-current-stock');
    if (currentStock) {
        document.getElementById('current-stock-info').textContent = `Current stock: ${currentStock}`;
    }
});
</script>

<?php include 'views/footer.php'; ?>