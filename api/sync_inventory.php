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
    $required_fields = ['product_id', 'transaction_type', 'quantity', 'new_quantity'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit();
        }
    }
    
    $db->beginTransaction();
    
    // Update product stock
    $update_query = "UPDATE products SET stock_quantity = ? WHERE id = ? AND tenant_id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([
        $input['new_quantity'],
        $input['product_id'], 
        $tenant_id
    ]);
    
    // Log inventory transaction
    $log_query = "INSERT INTO inventory_transactions (tenant_id, product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, unit_cost, notes, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $previous_quantity = isset($input['previous_quantity']) ? $input['previous_quantity'] : 0;
    $unit_cost = isset($input['unit_cost']) ? $input['unit_cost'] : null;
    $notes = isset($input['notes']) ? $input['notes'] : 'Offline inventory update - synced';
    $created_at = isset($input['timestamp']) ? date('Y-m-d H:i:s', strtotime($input['timestamp'])) : date('Y-m-d H:i:s');
    
    $log_stmt = $db->prepare($log_query);
    $log_stmt->execute([
        $tenant_id,
        $input['product_id'],
        $user_id,
        $input['transaction_type'],
        $input['quantity'],
        $previous_quantity,
        $input['new_quantity'],
        $unit_cost,
        $notes,
        $created_at
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Inventory updated successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>