<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

// Only admin can access settings
if (!is_admin()) {
    header('Location: /sari/');
    exit();
}

$page_title = "Store Settings";
$tenant_id = $_SESSION['tenant_id'];
$success_message = '';
$error_message = '';

// Get current tenant information
$tenant_query = "SELECT * FROM tenants WHERE id = ?";
$tenant_stmt = $db->prepare($tenant_query);
$tenant_stmt->execute([$tenant_id]);
$tenant = $tenant_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_store':
                $name = sanitize_input($_POST['name']);
                $contact_email = sanitize_input($_POST['contact_email']);
                $contact_phone = sanitize_input($_POST['contact_phone']);
                $address = sanitize_input($_POST['address']);
                
                $update_query = "UPDATE tenants SET name = ?, contact_email = ?, contact_phone = ?, address = ? WHERE id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$name, $contact_email, $contact_phone, $address, $tenant_id]);
                
                $_SESSION['tenant_name'] = $name;
                $success_message = "Store information updated successfully!";
                
                // Refresh tenant data
                $tenant_stmt->execute([$tenant_id]);
                $tenant = $tenant_stmt->fetch(PDO::FETCH_ASSOC);
                break;
                
            case 'add_category':
                $category_name = sanitize_input($_POST['category_name']);
                $category_description = sanitize_input($_POST['category_description']);
                
                $cat_query = "INSERT INTO categories (tenant_id, name, description) VALUES (?, ?, ?)";
                $cat_stmt = $db->prepare($cat_query);
                $cat_stmt->execute([$tenant_id, $category_name, $category_description]);
                
                $success_message = "Category added successfully!";
                break;
        }
    }
}

// Get categories
$categories_query = "SELECT * FROM categories WHERE tenant_id = ? ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute([$tenant_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get usage statistics
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM products WHERE tenant_id = ?) as total_products,
                    (SELECT COUNT(*) FROM users WHERE tenant_id = ?) as total_users,
                    (SELECT COUNT(*) FROM sales WHERE tenant_id = ?) as total_sales,
                    (SELECT SUM(total_amount) FROM sales WHERE tenant_id = ? AND payment_status = 'paid') as total_revenue";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$tenant_id, $tenant_id, $tenant_id, $tenant_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <h2 class="mb-4">Store Settings</h2>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Store Plan Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Store Plan & Usage</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Current Plan</h6>
                                <p class="mb-1">
                                    <span class="badge bg-success fs-6">
                                        <?php echo ucfirst($tenant['plan']); ?> Plan
                                    </span>
                                </p>
                                <?php if ($tenant['plan'] == 'free'): ?>
                                    <p class="text-muted">Enjoy full POS and inventory features forever!</p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Usage Statistics</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-box text-primary"></i> Products: <strong><?php echo $stats['total_products']; ?></strong> / 1,000</li>
                                    <li><i class="bi bi-people text-success"></i> Users: <strong><?php echo $stats['total_users']; ?></strong> / 3</li>
                                    <li><i class="bi bi-receipt text-info"></i> Total Sales: <strong><?php echo number_format($stats['total_sales']); ?></strong></li>
                                    <li><i class="bi bi-cash text-warning"></i> Revenue: <strong><?php echo format_currency($stats['total_revenue'] ?? 0); ?></strong></li>
                                </ul>
                            </div>
                        </div>
                        <?php if ($tenant['plan'] == 'free'): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle"></i> Need more features? <strong>Premium plans coming soon!</strong> 
                            You'll get unlimited products, advanced reports, SMS alerts, and more.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Store Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Store Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_store">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Store Name</label>
                                        <input type="text" class="form-control" name="name" id="name" 
                                               value="<?php echo htmlspecialchars($tenant['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_email" class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" name="contact_email" id="contact_email" 
                                               value="<?php echo htmlspecialchars($tenant['contact_email']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_phone" class="form-label">Contact Phone</label>
                                        <input type="text" class="form-control" name="contact_phone" id="contact_phone" 
                                               value="<?php echo htmlspecialchars($tenant['contact_phone']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="subdomain" class="form-label">Store ID</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($tenant['subdomain']); ?>" 
                                               readonly disabled>
                                        <small class="text-muted">This cannot be changed</small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Store Address</label>
                                        <textarea class="form-control" name="address" id="address" rows="2"><?php echo htmlspecialchars($tenant['address']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Product Categories -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Product Categories</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="bi bi-plus"></i> Add Category
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                                        <td>
                                            <?php
                                            $count_query = "SELECT COUNT(*) FROM products WHERE category_id = ?";
                                            $count_stmt = $db->prepare($count_query);
                                            $count_stmt->execute([$category['id']]);
                                            echo $count_stmt->fetchColumn();
                                            ?>
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" id="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" name="category_description" id="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>