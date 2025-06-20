<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /sari/');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $store_name = sanitize_input($_POST['store_name']);
    $store_address = sanitize_input($_POST['store_address']);
    $owner_first_name = sanitize_input($_POST['first_name']);
    $owner_last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (strlen($store_name) < 3) {
        $errors[] = "Store name must be at least 3 characters long";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Check if email already exists
            $check_query = "SELECT COUNT(*) FROM users WHERE email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "Email already exists";
            } else {
                // Generate subdomain from store name
                $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $store_name));
                $subdomain = substr($subdomain, 0, 50); // Limit length
                
                // Create tenant
                $tenant_query = "INSERT INTO tenants (name, email, phone, address, subscription_plan, subscription_status, trial_ends_at) 
                                VALUES (?, ?, ?, ?, 'free', 'trial', DATE_ADD(NOW(), INTERVAL 30 DAY))";
                $tenant_stmt = $db->prepare($tenant_query);
                $tenant_stmt->execute([$store_name, $email, $phone, $store_address]);
                $tenant_id = $db->lastInsertId();
                
                // Create admin user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $full_name = $owner_first_name . ' ' . $owner_last_name;
                $user_query = "INSERT INTO users (tenant_id, email, password, full_name, role) 
                              VALUES (?, ?, ?, ?, 'admin')";
                $user_stmt = $db->prepare($user_query);
                $user_stmt->execute([$tenant_id, $email, $password_hash, $full_name]);
                
                // Create default categories for the new tenant
                $default_categories = [
                    ['name' => 'Beverages', 'description' => 'Soft drinks, juices, water, coffee, tea'],
                    ['name' => 'Snacks', 'description' => 'Chips, crackers, cookies, candies'],
                    ['name' => 'Canned Goods', 'description' => 'Canned meat, fish, vegetables'],
                    ['name' => 'Instant Food', 'description' => 'Noodles, soups, ready-to-eat meals'],
                    ['name' => 'Personal Care', 'description' => 'Soap, shampoo, toothpaste, hygiene products'],
                    ['name' => 'Household', 'description' => 'Detergents, cleaning supplies, kitchen items'],
                    ['name' => 'School & Office', 'description' => 'Paper, pens, notebooks, supplies'],
                    ['name' => 'Others', 'description' => 'Miscellaneous items']
                ];
                
                $category_query = "INSERT INTO categories (tenant_id, name, description) VALUES (?, ?, ?)";
                $category_stmt = $db->prepare($category_query);
                
                foreach ($default_categories as $category) {
                    $category_stmt->execute([$tenant_id, $category['name'], $category['description']]);
                }
                
                $db->commit();
                
                $success_message = "Registration successful! You can now login with your credentials.";
                
                // Auto-login after registration
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['tenant_id'] = $tenant_id;
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $owner_first_name . ' ' . $owner_last_name;
                $_SESSION['role'] = 'admin';
                $_SESSION['tenant_name'] = $store_name;
                
                // Redirect to welcome/onboarding page
                header('Location: /sari/welcome');
                exit();
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Registration failed. Please try again.";
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Store - Sari-Sari POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .register-container {
            max-width: 600px;
            margin: 50px auto;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .feature-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .feature-list h5 {
            color: #5a67d8;
            margin-bottom: 1rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .feature-item i {
            color: #48bb78;
            margin-right: 0.5rem;
        }
        .hero-section {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }
        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <h1>Start Managing Your Sari-Sari Store</h1>
            <p>Free POS and Inventory System for Filipino Store Owners</p>
        </div>
        
        <div class="register-container">
            <div class="register-card">
                <h3 class="text-center mb-4">Register Your Store</h3>
                
                <div class="feature-list">
                    <h5><i class="bi bi-gift"></i> Free Forever Features:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Point of Sale (POS)</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Inventory Management</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Product Management</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Sales Tracking</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Basic Reports</span>
                            </div>
                            <div class="feature-item">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Multiple Users</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <h5 class="mb-3">Store Information</h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="store_name" class="form-label">Store Name *</label>
                            <input type="text" class="form-control" name="store_name" id="store_name" 
                                   placeholder="Juan's Sari-Sari Store" required 
                                   value="<?php echo isset($_POST['store_name']) ? htmlspecialchars($_POST['store_name']) : ''; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="store_address" class="form-label">Store Address *</label>
                            <textarea class="form-control" name="store_address" id="store_address" rows="2" 
                                      placeholder="123 Sample Street, Barangay, City" required><?php echo isset($_POST['store_address']) ? htmlspecialchars($_POST['store_address']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <h5 class="mb-3 mt-4">Owner Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="first_name" required
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="last_name" required
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="email" id="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" id="phone" placeholder="09123456789"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <h5 class="mb-3 mt-4">Login Credentials</h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" name="username" id="username" required
                                   minlength="4" placeholder="Choose a username (min 4 characters)"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" name="password" id="password" required
                                   minlength="6" placeholder="Min 6 characters">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-shop"></i> Create My Store
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="/sari/login">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Free Features</h6>
                    <p>Basic POS and Inventory features are free forever. We will never charge for these core features.</p>
                    
                    <h6>2. Data Privacy</h6>
                    <p>Your business data is yours. We will never sell or share your data with third parties.</p>
                    
                    <h6>3. Premium Features</h6>
                    <p>Optional premium features may be offered in the future but will never affect free features.</p>
                    
                    <h6>4. Fair Use</h6>
                    <p>This service is intended for legitimate sari-sari store businesses in the Philippines.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>