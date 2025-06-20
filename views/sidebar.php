<div class="sidebar bg-light p-3">
    <div class="nav flex-column nav-pills">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/sari/">
            <i class="bi bi-house"></i> Dashboard
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="/sari/pos">
            <i class="bi bi-calculator"></i> Point of Sale
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="/sari/products">
            <i class="bi bi-box"></i> Products
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="/sari/inventory">
            <i class="bi bi-boxes"></i> Inventory
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>" href="/sari/sales">
            <i class="bi bi-receipt"></i> Sales History
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="/sari/reports">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
        
        <?php if (is_admin()): ?>
        <hr>
        <h6 class="sidebar-heading">Admin</h6>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="/sari/users">
            <i class="bi bi-people"></i> Users
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="/sari/settings">
            <i class="bi bi-gear"></i> Settings
        </a>
        <?php endif; ?>
    </div>
</div>