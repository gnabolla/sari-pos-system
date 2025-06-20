<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

check_session();

// Only admin can manage users
if (!is_admin()) {
    header('Location: index.php');
    exit();
}

$page_title = "User Management";
$tenant_id = $_SESSION['tenant_id'];
$success_message = '';
$error_message = '';

// Check user limit for free plan
$user_count_query = "SELECT COUNT(*) FROM users WHERE tenant_id = ?";
$user_count_stmt = $db->prepare($user_count_query);
$user_count_stmt->execute([$tenant_id]);
$current_user_count = $user_count_stmt->fetchColumn();

$tenant_plan_query = "SELECT plan FROM tenants WHERE id = ?";
$tenant_plan_stmt = $db->prepare($tenant_plan_query);
$tenant_plan_stmt->execute([$tenant_id]);
$tenant_plan = $tenant_plan_stmt->fetchColumn();

$user_limit = ($tenant_plan == 'free') ? 3 : 999;
$can_add_users = $current_user_count < $user_limit;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                if (!$can_add_users) {
                    $error_message = "User limit reached. Upgrade to premium for unlimited users.";
                } else {
                    $username = sanitize_input($_POST['username']);
                    $email = sanitize_input($_POST['email']);
                    $first_name = sanitize_input($_POST['first_name']);
                    $last_name = sanitize_input($_POST['last_name']);
                    $role = $_POST['role'];
                    $password = $_POST['password'];
                    
                    // Check if username or email exists
                    $check_query = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->execute([$username, $email]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_message = "Username or email already exists.";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $insert_query = "INSERT INTO users (tenant_id, username, email, password, first_name, last_name, role) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $insert_stmt = $db->prepare($insert_query);
                        $insert_stmt->execute([$tenant_id, $username, $email, $password_hash, $first_name, $last_name, $role]);
                        $success_message = "User added successfully!";
                    }
                }
                break;
                
            case 'update_status':
                $user_id = intval($_POST['user_id']);
                $status = $_POST['status'];
                
                // Can't deactivate yourself
                if ($user_id == $_SESSION['user_id'] && $status == 'inactive') {
                    $error_message = "You cannot deactivate your own account.";
                } else {
                    $update_query = "UPDATE users SET status = ? WHERE id = ? AND tenant_id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$status, $user_id, $tenant_id]);
                    $success_message = "User status updated.";
                }
                break;
                
            case 'reset_password':
                $user_id = intval($_POST['user_id']);
                $new_password = $_POST['new_password'];
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_query = "UPDATE users SET password = ? WHERE id = ? AND tenant_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->execute([$password_hash, $user_id, $tenant_id]);
                $success_message = "Password reset successfully!";
                break;
        }
    }
}

// Get all users for this tenant
$users_query = "SELECT * FROM users WHERE tenant_id = ? ORDER BY created_at DESC";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute([$tenant_id]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'views/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <?php include 'views/sidebar.php'; ?>
        </div>
        <div class="col-md-9">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>User Management</h2>
                    <?php if ($can_add_users): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus"></i> Add User
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-info-circle"></i> User limit reached (<?php echo $current_user_count; ?>/<?php echo $user_limit; ?>). 
                            <strong>Upgrade to premium for unlimited users.</strong>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <li>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <input type="hidden" name="status" value="<?php echo $user['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-<?php echo $user['status'] == 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                                                    <?php echo $user['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                            <i class="bi bi-key"></i> Reset Password
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h5>User Roles Explained</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="bi bi-shield-check"></i> Admin</h6>
                                    <p class="mb-0 small">Full access to all features including settings and user management</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="bi bi-briefcase"></i> Manager</h6>
                                    <p class="mb-0 small">Can manage products, inventory, and view all reports</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6><i class="bi bi-calculator"></i> Cashier</h6>
                                    <p class="mb-0 small">Can process sales and view their own sales reports</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required minlength="4">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="cashier">Cashier</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <p>Reset password for: <strong id="reset_username"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<?php include 'views/footer.php'; ?>