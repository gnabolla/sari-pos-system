<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

$page_title = "Sales History";
$tenant_id = $_SESSION['tenant_id'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get sales with pagination
$sales_query = "SELECT s.*, u.first_name, u.last_name 
                FROM sales s
                JOIN users u ON s.user_id = u.id
                WHERE s.tenant_id = :tenant_id
                ORDER BY s.created_at DESC
                LIMIT :limit OFFSET :offset";
$sales_stmt = $db->prepare($sales_query);
$sales_stmt->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
$sales_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$sales_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$sales_stmt->execute();
$sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM sales WHERE tenant_id = ?";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute([$tenant_id]);
$total_sales = $count_stmt->fetchColumn();
$total_pages = ceil($total_sales / $limit);

include 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <h2 class="mb-4">Sales History</h2>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Transaction #</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Discount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Cashier</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sale['transaction_number']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></td>
                                        <td><?php echo format_currency($sale['total_amount']); ?></td>
                                        <td><?php echo format_currency($sale['discount_amount']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($sale['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $sale['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo ucfirst($sale['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewSaleDetails(<?php echo $sale['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Sales pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sale Details Modal -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sale Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="saleDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewSaleDetails(saleId) {
    const modal = new bootstrap.Modal(document.getElementById('saleDetailsModal'));
    const content = document.getElementById('saleDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch sale details
    fetch(`api/sale_details.php?id=${saleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sale = data.sale;
                const items = data.items;
                
                let itemsHtml = '';
                items.forEach(item => {
                    itemsHtml += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Transaction Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Transaction #:</strong></td><td>${sale.transaction_number}</td></tr>
                                <tr><td><strong>Date:</strong></td><td>${new Date(sale.created_at).toLocaleString()}</td></tr>
                                <tr><td><strong>Payment Method:</strong></td><td>${sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1)}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge ${sale.payment_status === 'paid' ? 'bg-success' : 'bg-warning'}">${sale.payment_status.charAt(0).toUpperCase() + sale.payment_status.slice(1)}</span></td></tr>
                                <tr><td><strong>Cashier:</strong></td><td>${sale.cashier_name}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Summary</h6>
                            <table class="table table-sm">
                                <tr><td>Subtotal:</td><td>₱${(parseFloat(sale.total_amount) + parseFloat(sale.discount_amount)).toFixed(2)}</td></tr>
                                <tr><td>Discount:</td><td>-₱${parseFloat(sale.discount_amount).toFixed(2)}</td></tr>
                                <tr><td><strong>Total:</strong></td><td><strong>₱${parseFloat(sale.total_amount).toFixed(2)}</strong></td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <h6>Items Purchased</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading sale details: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    Error loading sale details. Please try again.
                </div>
            `;
        });
}
</script>

<?php include 'views/footer.php'; ?>