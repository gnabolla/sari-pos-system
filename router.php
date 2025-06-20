<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
$route = trim($route, '/');

$publicRoutes = ['login', 'register', 'logout'];
$protectedRoutes = [
    '' => 'dashboard.php',
    'dashboard' => 'dashboard.php',
    'pos' => 'pos.php',
    'inventory' => 'inventory.php',
    'products' => 'products.php',
    'sales' => 'sales.php',
    'reports' => 'reports.php',
    'users' => 'users.php',
    'settings' => 'settings.php',
    'welcome' => 'welcome.php'
];

$apiRoutes = [
    'api/process-sale' => 'api/process_sale.php',
    'api/dashboard-stats' => 'api/dashboard_stats.php',
    'api/sale-details' => 'api/sale_details.php'
];

if (empty($route)) {
    require 'landing.php';
    exit();
}

if ($route == 'dashboard') {
    if (!isset($_SESSION['tenant_id'])) {
        header('Location: /sari/login');
        exit();
    }
    require 'dashboard.php';
    exit();
}

if (in_array($route, $publicRoutes)) {
    require $route . '.php';
    exit();
}

if (isset($apiRoutes[$route])) {
    require $apiRoutes[$route];
    exit();
}

if (!isset($_SESSION['tenant_id']) && !in_array($route, $publicRoutes)) {
    header('Location: /sari/login');
    exit();
}

if (isset($protectedRoutes[$route])) {
    require $protectedRoutes[$route];
    exit();
}

http_response_code(404);
$page_title = "404 - Page Not Found";
include 'views/header.php';
?>
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="display-1">404</h1>
                    <h3>Page Not Found</h3>
                    <p>The page you are looking for does not exist.</p>
                    <a href="/sari/" class="btn btn-primary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'views/footer.php'; ?>