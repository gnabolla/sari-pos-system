<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tenant_id = $_SESSION['tenant_id'];

try {
    $today_sales = get_today_sales($db, $tenant_id);
    $low_stock_count = get_low_stock_count($db, $tenant_id);
    
    echo json_encode([
        'success' => true,
        'today_sales' => $today_sales,
        'low_stock_count' => $low_stock_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>