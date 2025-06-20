<div class="sidebar bg-light p-3">
    <div class="nav flex-column nav-pills">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
            <i class="bi bi-house"></i> Dashboard
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
            <i class="bi bi-calculator"></i> Point of Sale
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="bi bi-box"></i> Products
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
            <i class="bi bi-boxes"></i> Inventory
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>" href="sales.php">
            <i class="bi bi-receipt"></i> Sales History
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        
        <?php if (is_admin()): ?>
        <hr>
        <h6 class="sidebar-heading">Admin</h6>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
            <i class="bi bi-people"></i> Users
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
            <i class="bi bi-gear"></i> Settings
        </a>
        <?php endif; ?>
    </div>
</div>