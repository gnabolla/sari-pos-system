<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sari-Sari Store POS'; ?></title>
    
    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#6b7280',
                        success: '#10b981',
                        danger: '#ef4444',
                        warning: '#f59e0b',
                        info: '#06b6d4'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php if (isset($_SESSION['user_id'])): ?>
    <!-- Top Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg relative z-30">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Mobile menu button -->
                    <button type="button" class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-blue-200 hover:text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white mr-3" onclick="toggleSidebar()">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    
                    <a href="/sari/" class="flex items-center space-x-2 text-white hover:text-blue-200">
                        <i class="bi bi-shop text-xl"></i>
                        <span class="font-semibold text-lg"><?php echo $_SESSION['tenant_name'] ?? 'Sari-Sari Store'; ?></span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center space-x-2 text-white hover:text-blue-200 focus:outline-none" onclick="toggleDropdown()">
                            <i class="bi bi-person-circle text-xl"></i>
                            <span class="hidden sm:block"><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="/sari/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="bi bi-person mr-2"></i>Profile
                            </a>
                            <hr class="my-1">
                            <a href="/sari/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="bi bi-box-arrow-right mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="flex">
        <!-- Mobile sidebar overlay -->
        <div id="sidebarOverlay" class="fixed inset-0 z-20 bg-gray-600 bg-opacity-75 lg:hidden hidden" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            <div class="flex flex-col h-full">
                <!-- Sidebar header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 lg:hidden">
                    <span class="text-lg font-semibold text-gray-800">Menu</span>
                    <button onclick="toggleSidebar()" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                
                <!-- Navigation items -->
                <nav class="flex-1 px-4 py-6 space-y-2">
                    <?php
                    $current_page = basename($_SERVER['REQUEST_URI'], '.php');
                    $nav_items = [
                        ['href' => '/sari/', 'icon' => 'bi-house', 'text' => 'Dashboard', 'active' => $current_page == '' || $current_page == 'dashboard'],
                        ['href' => '/sari/pos', 'icon' => 'bi-cash-coin', 'text' => 'Point of Sale', 'active' => $current_page == 'pos'],
                        ['href' => '/sari/products', 'icon' => 'bi-box', 'text' => 'Products', 'active' => $current_page == 'products'],
                        ['href' => '/sari/inventory', 'icon' => 'bi-boxes', 'text' => 'Inventory', 'active' => $current_page == 'inventory'],
                        ['href' => '/sari/reports', 'icon' => 'bi-graph-up', 'text' => 'Reports', 'active' => $current_page == 'reports'],
                    ];
                    
                    foreach ($nav_items as $item):
                        $active_classes = $item['active'] ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900';
                    ?>
                    <a href="<?php echo $item['href']; ?>" class="group flex items-center px-3 py-2 text-sm font-medium rounded-l-lg transition-colors duration-200 <?php echo $active_classes; ?>">
                        <i class="<?php echo $item['icon']; ?> mr-3 text-lg"></i>
                        <?php echo $item['text']; ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
                
                <!-- Sidebar footer -->
                <div class="p-4 border-t border-gray-200">
                    <div class="text-xs text-gray-500">
                        <div class="font-semibold"><?php echo $_SESSION['tenant_name'] ?? 'Store'; ?></div>
                        <div><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content wrapper -->
        <div class="flex-1 lg:ml-0">
    <?php endif; ?>