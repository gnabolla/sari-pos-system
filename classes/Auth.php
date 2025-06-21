<?php
/**
 * Authentication Middleware Class
 * Handles session management and user authentication
 */
class Auth {
    private Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->startSession();
    }
    
    /**
     * Start secure session
     */
    private function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.cookie_samesite', 'Lax');
            
            session_start();
            
            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration'])) {
                $this->regenerateSession();
            } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                $this->regenerateSession();
            }
        }
    }
    
    /**
     * Regenerate session ID for security
     */
    private function regenerateSession(): void {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && isset($_SESSION['tenant_id']);
    }
    
    /**
     * Check if user is guest (not logged in)
     */
    public function isGuest(): bool {
        return !$this->isLoggedIn();
    }
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuth(string $redirectUrl = '/sari/login'): void {
        if ($this->isGuest()) {
            $this->redirect($redirectUrl);
        }
    }
    
    /**
     * Require guest - redirect to dashboard if already authenticated
     */
    public function requireGuest(string $redirectUrl = '/sari/'): void {
        if ($this->isLoggedIn()) {
            $this->redirect($redirectUrl);
        }
    }
    
    /**
     * Attempt to log in user
     */
    public function login(string $email, string $password): array {
        try {
            // Get user by email
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ? AND status = 'active'",
                [$email]
            );
            
            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Get tenant name
            $tenant = $this->db->fetchOne(
                "SELECT name FROM tenants WHERE id = ?",
                [$user['tenant_id']]
            );
            $_SESSION['tenant_name'] = $tenant['name'] ?? 'Store';
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Log out user
     */
    public function logout(): void {
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Get current user ID
     */
    public function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current tenant ID
     */
    public function getTenantId(): ?int {
        return $_SESSION['tenant_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public function getUser(): ?array {
        if ($this->isGuest()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'tenant_id' => $_SESSION['tenant_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'tenant_name' => $_SESSION['tenant_name']
        ];
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool {
        return ($_SESSION['role'] ?? '') === $role;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Redirect to URL
     */
    private function redirect(string $url): void {
        header("Location: {$url}");
        exit();
    }
    
    /**
     * Check session timeout (30 minutes of inactivity)
     */
    public function checkTimeout(int $timeoutMinutes = 30): bool {
        $timeout = $timeoutMinutes * 60;
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                $this->logout();
                return true;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return false;
    }
}