<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dispatcher = simpleDispatcher(function($r) {
    // Public routes
    $r->get('/', 'HomeController');
    $r->get('/login', 'LoginController');
    $r->get('/register', 'RegisterController');
    $r->get('/logout', 'LogoutController');
    
    // Protected routes
    $r->get('/dashboard', 'DashboardController');
    $r->get('/pos', 'PosController');
    $r->get('/inventory', 'InventoryController');
    $r->get('/products', 'ProductsController');
    $r->get('/sales', 'SalesController');
    $r->get('/reports', 'ReportsController');
    $r->get('/users', 'UsersController');
    $r->get('/settings', 'SettingsController');
    $r->get('/welcome', 'WelcomeController');
    
    // API routes
    $r->post('/api/process-sale', 'ProcessSaleController');
    $r->get('/api/dashboard-stats', 'DashboardStatsController');
    $r->get('/api/sale-details', 'SaleDetailsController');
    $r->post('/api/sync-sale', 'SyncSaleController');
    $r->post('/api/sync-product', 'SyncProductController');
    $r->post('/api/sync-inventory', 'SyncInventoryController');
    $r->get('/api/ping', 'PingController');
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FASTROUTE_NOT_FOUND:
        http_response_code(404);
        $page_title = "404 - Page Not Found";
        include 'views/header.php';
        echo '<div class="container"><div class="row justify-content-center mt-5"><div class="col-md-6"><div class="card"><div class="card-body text-center"><h1 class="display-1">404</h1><h3>Page Not Found</h3><p>The page you are looking for does not exist.</p><a href="/" class="btn btn-primary">Go to Dashboard</a></div></div></div></div></div>';
        include 'views/footer.php';
        break;
    case FASTROUTE_METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case FASTROUTE_FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        handleRoute($handler, $vars);
        break;
}

function handleRoute($handler, $vars) {
    $protectedRoutes = ['DashboardController', 'PosController', 'InventoryController', 'ProductsController', 'SalesController', 'ReportsController', 'UsersController', 'SettingsController'];
    
    if (in_array($handler, $protectedRoutes) && !isset($_SESSION['tenant_id'])) {
        header('Location: /login');
        exit();
    }
    
    switch ($handler) {
        case 'HomeController':
            if (isset($_SESSION['user_id'])) {
                header('Location: /dashboard');
                exit();
            } else {
                require 'landing.php';
            }
            break;
        case 'LoginController':
            require 'login.php';
            break;
        case 'RegisterController':
            require 'register.php';
            break;
        case 'LogoutController':
            require 'logout.php';
            break;
        case 'DashboardController':
            require 'dashboard.php';
            break;
        case 'PosController':
            require 'pos.php';
            break;
        case 'InventoryController':
            require 'inventory.php';
            break;
        case 'ProductsController':
            require 'products.php';
            break;
        case 'SalesController':
            require 'sales.php';
            break;
        case 'ReportsController':
            require 'reports.php';
            break;
        case 'UsersController':
            require 'users.php';
            break;
        case 'SettingsController':
            require 'settings.php';
            break;
        case 'WelcomeController':
            require 'welcome.php';
            break;
        case 'ProcessSaleController':
            require 'api/process_sale.php';
            break;
        case 'DashboardStatsController':
            require 'api/dashboard_stats.php';
            break;
        case 'SaleDetailsController':
            require 'api/sale_details.php';
            break;
        case 'SyncSaleController':
            require 'api/sync_sale.php';
            break;
        case 'SyncProductController':
            require 'api/sync_product.php';
            break;
        case 'SyncInventoryController':
            require 'api/sync_inventory.php';
            break;
        case 'PingController':
            require 'api/ping.php';
            break;
        default:
            http_response_code(404);
            echo 'Handler not found';
            break;
    }
}
?>