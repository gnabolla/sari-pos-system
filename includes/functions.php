<?php
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_transaction_number() {
    return 'TXN' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

function check_session() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
        header('Location: /sari/login');
        exit();
    }
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
}

function is_admin() {
    return get_user_role() === 'admin';
}

function is_manager() {
    return in_array(get_user_role(), ['admin', 'manager']);
}

function check_permission($required_role) {
    $current_role = get_user_role();
    $role_hierarchy = ['admin' => 3, 'manager' => 2, 'cashier' => 1];
    
    if (!isset($role_hierarchy[$current_role]) || !isset($role_hierarchy[$required_role])) {
        return false;
    }
    
    return $role_hierarchy[$current_role] >= $role_hierarchy[$required_role];
}

function get_low_stock_count($db, $tenant_id) {
    $query = "SELECT COUNT(*) as count FROM products WHERE tenant_id = ? AND stock_quantity <= reorder_level AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tenant_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function get_today_sales($db, $tenant_id) {
    $query = "SELECT SUM(total_amount) as total FROM sales WHERE tenant_id = ? AND DATE(created_at) = CURDATE() AND payment_status = 'paid'";
    $stmt = $db->prepare($query);
    $stmt->execute([$tenant_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}
?>