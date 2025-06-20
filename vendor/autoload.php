<?php
// Simple autoloader for FastRoute (since composer install failed)
// In production, run: composer install

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Simple FastRoute implementation for basic routing
if (!class_exists('FastRoute\\simpleDispatcher')) {
    class SimpleRouter {
        private $routes = [];
        
        public function get($route, $handler) {
            $this->routes['GET'][$route] = $handler;
        }
        
        public function post($route, $handler) {
            $this->routes['POST'][$route] = $handler;
        }
        
        public function dispatch($method, $uri) {
            // Remove query string
            if (($pos = strpos($uri, '?')) !== false) {
                $uri = substr($uri, 0, $pos);
            }
            
            // Remove base path
            $uri = str_replace('/sari', '', $uri);
            if (empty($uri)) $uri = '/';
            
            if (isset($this->routes[$method][$uri])) {
                return [1, $this->routes[$method][$uri], []]; // FOUND
            }
            
            return [0, null, []]; // NOT_FOUND
        }
    }
    
    function simpleDispatcher($callback) {
        $router = new SimpleRouter();
        $callback($router);
        return $router;
    }
    
    // Define constants as regular constants
    if (!defined('FASTROUTE_NOT_FOUND')) {
        define('FASTROUTE_NOT_FOUND', 0);
        define('FASTROUTE_METHOD_NOT_ALLOWED', 2);
        define('FASTROUTE_FOUND', 1);
    }
}
?>