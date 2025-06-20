<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sari-Sari Store POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .pricing-card {
            border: 2px solid #dee2e6;
            transition: all 0.3s;
        }
        .pricing-card.featured {
            border-color: #007bff;
            transform: scale(1.05);
        }
        .pricing-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/sari/">
                <i class="fas fa-store text-primary"></i> Sari-Sari POS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/sari/login">Login</a>
                <a class="nav-link btn btn-primary text-white px-3 ms-2" href="/sari/register">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Modernize Your Sari-Sari Store</h1>
            <p class="lead mb-5">Complete FREE POS system designed specifically for Filipino sari-sari stores. Manage inventory, track sales, and grow your business.</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <a href="/sari/register" class="btn btn-light btn-lg me-3">Get Started Free</a>
                    <a href="#features" class="btn btn-outline-light btn-lg">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Everything You Need to Run Your Store - Completely Free!</h2>
                <p class="text-muted">Powerful features designed for Filipino sari-sari stores at no cost</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-cash-register fa-3x text-primary mb-3"></i>
                        <h5>Point of Sale</h5>
                        <p class="text-muted">Fast and easy checkout process with barcode scanning and receipt printing.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-boxes fa-3x text-success mb-3"></i>
                        <h5>Inventory Management</h5>
                        <p class="text-muted">Track stock levels, get low-stock alerts, and manage suppliers efficiently.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                        <h5>Sales Reports</h5>
                        <p class="text-muted">Detailed analytics and reports to help you understand your business better.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h5>Customer Management</h5>
                        <p class="text-muted">Keep track of regular customers and their purchase history.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-mobile-alt fa-3x text-danger mb-3"></i>
                        <h5>Mobile Friendly</h5>
                        <p class="text-muted">Access your store data anywhere, anytime from any device.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <i class="fas fa-shield-alt fa-3x text-secondary mb-3"></i>
                        <h5>Secure & Reliable</h5>
                        <p class="text-muted">Your data is safe with automatic backups and secure storage.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Why Choose Our Free POS System?</h2>
                <p class="text-muted">Join thousands of successful sari-sari store owners</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-lg p-5 text-center">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-gift fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">100% Free Forever</h5>
                                        <small class="text-muted">No hidden fees, no subscriptions</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-rocket fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">Quick Setup</h5>
                                        <small class="text-muted">Start using in minutes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-headset fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">Free Support</h5>
                                        <small class="text-muted">Help when you need it</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">Made for Pinoys</h5>
                                        <small class="text-muted">Built specifically for Filipino stores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="/sari/register" class="btn btn-primary btn-lg px-5">Start Using Now - It's Free!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">Ready to Transform Your Sari-Sari Store?</h2>
            <p class="lead text-muted mb-4">Join thousands of store owners who have modernized their business with our completely free POS system.</p>
            <a href="/sari/register" class="btn btn-primary btn-lg">Get Started Free - No Payment Required</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-store"></i> Sari-Sari POS</h6>
                    <p class="text-muted">Empowering Filipino entrepreneurs with modern technology.</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6">
                    <small>&copy; 2024 Sari-Sari POS. All rights reserved.</small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        <a href="#" class="text-white me-3">Privacy Policy</a>
                        <a href="#" class="text-white">Terms of Service</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>