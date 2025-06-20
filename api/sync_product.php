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
    if (!isset($input['product_id']) || !isset($input['updates'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }
    
    $updates = $input['updates'];
    $product_id = $input['product_id'];
    
    // Build update query
    $set_clauses = [];
    $params = [];
    
    $allowed_fields = ['name', 'unit_price', 'cost_price', 'stock_quantity', 'reorder_level', 'status'];
    
    foreach ($updates as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $set_clauses[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($set_clauses)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid update fields provided']);
        exit();
    }
    
    // Add WHERE clause parameters
    $params[] = $product_id;
    $params[] = $tenant_id;
    
    $query = "UPDATE products SET " . implode(', ', $set_clauses) . " WHERE id = ? AND tenant_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found or no changes made']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>