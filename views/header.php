<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sari-Sari Store POS'; ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Complete POS system for Filipino sari-sari stores">
    <meta name="theme-color" content="#007bff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Sari POS">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/sari/manifest.json">
    
    <!-- PWA Icons -->
    <link rel="apple-touch-icon" sizes="192x192" href="/sari/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/sari/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/sari/icons/icon-16x16.png">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/sari/assets/css/style.css" rel="stylesheet">
    
    <!-- PWA Scripts -->
    <script src="/sari/js/offline-storage.js"></script>
    <script src="/sari/js/sync-manager.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/sari/">
                <i class="bi bi-shop"></i> <?php echo $_SESSION['tenant_name'] ?? 'Sari-Sari Store'; ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name'] ?? 'User'; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/sari/profile"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/sari/logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>