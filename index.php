<?php
// Use simple bootstrap for now
require_once __DIR__ . '/bootstrap-simple.php';

// Load routing dependencies
require_once __DIR__ . '/vendor/autoload.php';

$dispatcher = simpleDispatcher(function($r) {
    // Public routes
    $r->get('/', 'HomeController');
    $r->get('/login', 'LoginController');
    $r->post('/login', 'LoginController');
    $r->get('/register', 'RegisterController');
    $r->post('/register', 'RegisterController');
    $r->get('/logout', 'LogoutController');
    
    // Protected routes
    $r->get('/dashboard', 'DashboardController');
    $r->get('/pos', 'PosController');
    $r->get('/inventory', 'InventoryController');
    $r->post('/inventory', 'InventoryController');
    $r->get('/products', 'ProductsController');
    $r->post('/products', 'ProductsController');
    $r->get('/sales', 'SalesController');
    $r->get('/reports', 'ReportsController');
    $r->get('/users', 'UsersController');
    $r->post('/users', 'UsersController');
    $r->get('/settings', 'SettingsController');
    $r->post('/settings', 'SettingsController');
    $r->get('/welcome', 'WelcomeController');
    
    // API routes
    $r->post('/api/process-sale', 'ProcessSaleController');
    $r->get('/api/dashboard-stats', 'DashboardStatsController');
    $r->get('/api/sale-details', 'SaleDetailsController');
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
        echo '<div class="min-h-screen flex items-center justify-center px-4"><div class="max-w-md w-full text-center"><div class="mb-8"><h1 class="text-9xl font-bold text-gray-300">404</h1><h2 class="text-2xl font-semibold text-gray-800 mb-4">Page Not Found</h2><p class="text-gray-600 mb-8">The page you are looking for does not exist.</p><a href="/sari/" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition duration-200">Go to Dashboard</a></div></div></div>';
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
    
    if (in_array($handler, $protectedRoutes)) {
        require_login('/sari/login');
    }
    
    switch ($handler) {
        case 'HomeController':
            if (is_logged_in()) {
                header('Location: /sari/dashboard');
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
        default:
            http_response_code(404);
            echo 'Handler not found';
            break;
    }
}
?>