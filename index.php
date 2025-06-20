<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['tenant_id'])) {
    header('Location: login.php');
    exit();
}

$page_title = "Sari-Sari Store POS";
include 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Today's Sales</h6>
                                        <h4 id="today-sales">₱0.00</h4>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Low Stock Items</h6>
                                        <h4 id="low-stock">0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <a href="pos.php" class="btn btn-primary btn-block mb-2">Point of Sale</a>
                                <a href="inventory.php" class="btn btn-success btn-block mb-2">Manage Inventory</a>
                                <a href="reports.php" class="btn btn-info btn-block">View Reports</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/footer.php'; ?>