<?php
/**
 * Input Validation Class
 * Handles form validation and sanitization
 */
class Validator {
    private array $errors = [];
    private array $data = [];
    
    /**
     * Validate input data
     */
    public function validate(array $data, array $rules): bool {
        $this->errors = [];
        $this->data = $data;
        
        foreach ($rules as $field => $ruleSet) {
            $this->validateField($field, $ruleSet);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate individual field
     */
    private function validateField(string $field, string $rules): void {
        $rules = explode('|', $rules);
        $value = $this->data[$field] ?? null;
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply validation rule
     */
    private function applyRule(string $field, $value, string $rule): void {
        $params = [];
        
        // Parse rule parameters
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, ucfirst($field) . ' is required');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, ucfirst($field) . ' must be a valid email address');
                }
                break;
                
            case 'min':
                $min = (int) $params[0];
                if (!empty($value) && strlen($value) < $min) {
                    $this->addError($field, ucfirst($field) . " must be at least {$min} characters");
                }
                break;
                
            case 'max':
                $max = (int) $params[0];
                if (!empty($value) && strlen($value) > $max) {
                    $this->addError($field, ucfirst($field) . " must not exceed {$max} characters");
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, ucfirst($field) . ' must be a number');
                }
                break;
                
            case 'positive':
                if (!empty($value) && (!is_numeric($value) || (float) $value <= 0)) {
                    $this->addError($field, ucfirst($field) . ' must be a positive number');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, ucfirst($field) . ' must be an integer');
                }
                break;
                
            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters');
                }
                break;
                
            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum($value)) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters and numbers');
                }
                break;
                
            case 'unique':
                if (!empty($value) && isset($params[0], $params[1])) {
                    $table = $params[0];
                    $column = $params[1];
                    $excludeId = $params[2] ?? null;
                    
                    if ($this->checkUnique($table, $column, $value, $excludeId)) {
                        $this->addError($field, ucfirst($field) . ' already exists');
                    }
                }
                break;
                
            case 'exists':
                if (!empty($value) && isset($params[0], $params[1])) {
                    $table = $params[0];
                    $column = $params[1];
                    
                    if (!$this->checkExists($table, $column, $value)) {
                        $this->addError($field, ucfirst($field) . ' does not exist');
                    }
                }
                break;
        }
    }
    
    /**
     * Check if value is unique in database
     */
    private function checkUnique(string $table, string $column, $value, $excludeId = null): bool {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$value];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->query($sql, $params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if value exists in database
     */
    private function checkExists(string $table, string $column, $value): bool {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $stmt = $db->query($sql, [$value]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Add validation error
     */
    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     */
    public function getFieldErrors(string $field): array {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if field has errors
     */
    public function hasErrors(string $field = null): bool {
        if ($field) {
            return isset($this->errors[$field]);
        }
        return !empty($this->errors);
    }
    
    /**
     * Get first error message
     */
    public function getFirstError(): ?string {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize(array $data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize string value
     */
    public static function sanitizeString(string $value): string {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $value;
    }
    
    /**
     * Clean input for database (remove HTML)
     */
    public static function clean(string $value): string {
        // Strip HTML tags
        $value = strip_tags($value);
        
        // Remove extra whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        
        // Trim
        $value = trim($value);
        
        return $value;
    }
}