<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

$page_title = "Welcome to Your Store";
include 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="text-center mb-5">
                    <h1 class="display-4">Welcome to <?php echo htmlspecialchars($_SESSION['tenant_name']); ?>!</h1>
                    <p class="lead">Your store is now ready. Let's get you started!</p>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="display-1 text-primary mb-3">
                                    <i class="bi bi-box"></i>
                                </div>
                                <h4>Step 1: Add Products</h4>
                                <p>Start by adding your inventory items. Include names, prices, and barcodes.</p>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="bi bi-plus"></i> Add Products
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="display-1 text-success mb-3">
                                    <i class="bi bi-calculator"></i>
                                </div>
                                <h4>Step 2: Start Selling</h4>
                                <p>Use the POS to process sales. Works with barcode scanners too!</p>
                                <a href="pos.php" class="btn btn-success">
                                    <i class="bi bi-cart"></i> Open POS
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <div class="display-1 text-info mb-3">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <h4>Step 3: Track Progress</h4>
                                <p>Monitor sales, inventory levels, and generate reports.</p>
                                <a href="reports.php" class="btn btn-info">
                                    <i class="bi bi-bar-chart"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h5><i class="bi bi-lightbulb"></i> Quick Tips</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul>
                                    <li><strong>Barcode Scanner:</strong> Connect any USB barcode scanner for faster checkout</li>
                                    <li><strong>Low Stock Alerts:</strong> Set reorder levels to get notified when running low</li>
                                    <li><strong>Multiple Users:</strong> Add cashiers and managers with different permissions</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul>
                                    <li><strong>Daily Reports:</strong> Check your dashboard every morning for insights</li>
                                    <li><strong>Categories:</strong> We've added default categories, but you can customize them</li>
                                    <li><strong>Free Forever:</strong> Core POS and inventory features will always be free</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-speedometer"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>