<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale data']);
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$user_id = $_SESSION['user_id'];
$items = $input['items'];
$payment_method = $input['payment_method'] ?? 'cash';
$discount_amount = floatval($input['discount_amount'] ?? 0);
$total_amount = floatval($input['total_amount']);

try {
    $db->beginTransaction();
    
    // Generate transaction number
    $transaction_number = generate_transaction_number();
    
    // Insert sale record
    $sale_query = "INSERT INTO sales (tenant_id, user_id, transaction_number, total_amount, discount_amount, payment_method) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $sale_stmt = $db->prepare($sale_query);
    $sale_stmt->execute([$tenant_id, $user_id, $transaction_number, $total_amount, $discount_amount, $payment_method]);
    $sale_id = $db->lastInsertId();
    
    // Insert sale items and update stock
    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['selling_price']);
        $total_price = floatval($item['total']);
        
        // Check current stock
        $stock_query = "SELECT stock_quantity FROM products WHERE id = ? AND tenant_id = ?";
        $stock_stmt = $db->prepare($stock_query);
        $stock_stmt->execute([$product_id, $tenant_id]);
        $current_stock = $stock_stmt->fetchColumn();
        
        if ($current_stock === false) {
            throw new Exception("Product not found: " . $product_id);
        }
        
        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for product ID: " . $product_id);
        }
        
        // Insert sale item
        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                       VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->execute([$sale_id, $product_id, $quantity, $unit_price, $total_price]);
        
        // Update product stock
        $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND tenant_id = ?";
        $update_stock_stmt = $db->prepare($update_stock_query);
        $update_stock_stmt->execute([$quantity, $product_id, $tenant_id]);
        
        // Record inventory movement
        $movement_query = "INSERT INTO inventory_movements (tenant_id, product_id, user_id, movement_type, quantity, reference_type, reference_id) 
                           VALUES (?, ?, ?, 'out', ?, 'sale', ?)";
        $movement_stmt = $db->prepare($movement_query);
        $movement_stmt->execute([$tenant_id, $product_id, $user_id, $quantity, $sale_id]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Sale processed successfully',
        'transaction_number' => $transaction_number,
        'sale_id' => $sale_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>