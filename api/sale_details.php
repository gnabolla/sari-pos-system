<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Sale ID required']);
    exit;
}

$sale_id = intval($_GET['id']);
$tenant_id = $_SESSION['tenant_id'];

try {
    // Get sale details
    $sale_query = "SELECT s.*, u.first_name, u.last_name 
                   FROM sales s
                   JOIN users u ON s.user_id = u.id
                   WHERE s.id = ? AND s.tenant_id = ?";
    $sale_stmt = $db->prepare($sale_query);
    $sale_stmt->execute([$sale_id, $tenant_id]);
    $sale = $sale_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }
    
    // Get sale items
    $items_query = "SELECT si.*, p.name as product_name 
                    FROM sale_items si
                    JOIN products p ON si.product_id = p.id
                    WHERE si.sale_id = ?
                    ORDER BY p.name";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$sale_id]);
    $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add cashier name to sale data
    $sale['cashier_name'] = $sale['first_name'] . ' ' . $sale['last_name'];
    
    echo json_encode([
        'success' => true,
        'sale' => $sale,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>