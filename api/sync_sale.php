<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit();
    }
    
    $tenant_id = $_SESSION['tenant_id'];
    
    // Validate required fields
    $required_fields = ['items', 'total_amount', 'payment_method', 'timestamp'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit();
        }
    }
    
    $db->beginTransaction();
    
    // Insert sale record
    $sale_query = "INSERT INTO sales (tenant_id, transaction_number, total_amount, payment_method, payment_status, notes, created_at) 
                   VALUES (?, ?, ?, ?, 'paid', ?, ?)";
    
    $transaction_number = generate_transaction_number();
    $notes = isset($input['notes']) ? $input['notes'] : 'Offline sale - synced';
    $created_at = date('Y-m-d H:i:s', strtotime($input['timestamp']));
    
    $stmt = $db->prepare($sale_query);
    $stmt->execute([
        $tenant_id,
        $transaction_number,
        $input['total_amount'],
        $input['payment_method'],
        $notes,
        $created_at
    ]);
    
    $sale_id = $db->lastInsertId();
    
    // Insert sale items
    $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                   VALUES (?, ?, ?, ?, ?)";
    $item_stmt = $db->prepare($item_query);
    
    // Update product stock
    $stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND tenant_id = ?";
    $stock_stmt = $db->prepare($stock_query);
    
    foreach ($input['items'] as $item) {
        // Insert sale item
        $item_stmt->execute([
            $sale_id,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total_price']
        ]);
        
        // Update stock
        $stock_stmt->execute([
            $item['quantity'],
            $item['product_id'],
            $tenant_id
        ]);
    }
    
    $db->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'sale_id' => $sale_id,
        'transaction_number' => $transaction_number,
        'message' => 'Sale synced successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>