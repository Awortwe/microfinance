<?php
/**
 * Validation Class
 * Handles all input validation and sanitization
 */

class Validation {
    private $errors = [];
    private $data = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->errors = [];
        $this->data = [];
    }

    /**
     * Validate required fields
     */
    public function required($field, $value, $field_name = null) {
        $field_name = $field_name ?? $field;
        $value = trim($value);
        
        if (empty($value)) {
            $this->errors[$field] = "{$field_name} is required";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate email
     */
    public function email($field, $value, $field_name = null) {
        $field_name = $field_name ?? $field;
        $value = trim($value);
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$field_name} is not a valid email address";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate phone number (Ghana format)
     */
    public function phone($field, $value, $field_name = null) {
        $field_name = $field_name ?? $field;
        $value = trim(str_replace(' ', '', $value));
        
        if (!empty($value)) {
            // Ghana phone numbers: +233XXXXXXXXX or 0XXXXXXXXX
            if (!preg_match('/^(\+233|0)[235467]\d{8}$/', $value)) {
                $this->errors[$field] = "{$field_name} is not a valid Ghana phone number";
                return false;
            }
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate minimum length
     */
    public function minLength($field, $value, $min, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (strlen(trim($value)) < $min) {
            $this->errors[$field] = "{$field_name} must be at least {$min} characters";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate maximum length
     */
    public function maxLength($field, $value, $max, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (strlen(trim($value)) > $max) {
            $this->errors[$field] = "{$field_name} must not exceed {$max} characters";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate numeric value
     */
    public function numeric($field, $value, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "{$field_name} must be a number";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate minimum value
     */
    public function minValue($field, $value, $min, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (is_numeric($value) && $value < $min) {
            $this->errors[$field] = "{$field_name} must be at least {$min}";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate maximum value
     */
    public function maxValue($field, $value, $max, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (is_numeric($value) && $value > $max) {
            $this->errors[$field] = "{$field_name} must not exceed {$max}";
            return false;
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate date format
     */
    public function date($field, $value, $format = 'Y-m-d', $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = "{$field_name} is not a valid date";
                return false;
            }
        }
        
        $this->data[$field] = $value;
        return true;
    }

    /**
     * Validate unique field in database
     */
    public function unique($field, $value, $table, $column, $exclude_id = null, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        try {
            $db = Database::getInstance();
            
            $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
            $params = [':value' => $value];
            
            if ($exclude_id) {
                $query .= " AND id != :id";
                $params[':id'] = $exclude_id;
            }
            
            $db->query($query);
            $db->bindMultiple($params);
            $result = $db->single();
            
            if ($result['count'] > 0) {
                $this->errors[$field] = "This {$field_name} is already taken";
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            $this->errors[$field] = "Validation error";
            return false;
        }
    }

    /**
     * Validate password strength
     */
    public function password($field, $value, $field_name = 'Password') {
        if (strlen($value) < PASSWORD_MIN_LENGTH) {
            $this->errors[$field] = "{$field_name} must be at least " . PASSWORD_MIN_LENGTH . " characters";
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $value)) {
            $this->errors[$field] = "{$field_name} must contain at least one uppercase letter";
            return false;
        }
        
        if (!preg_match('/[a-z]/', $value)) {
            $this->errors[$field] = "{$field_name} must contain at least one lowercase letter";
            return false;
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $this->errors[$field] = "{$field_name} must contain at least one number";
            return false;
        }
        
        return true;
    }

    /**
     * Validate password match
     */
    public function match($field, $value, $match_field, $match_value, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if ($value !== $match_value) {
            $this->errors[$field] = "{$field_name} does not match";
            return false;
        }
        
        return true;
    }

    /**
     * Validate username format
     */
    public function username($field, $value, $field_name = 'Username') {
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $value)) {
            $this->errors[$field] = "{$field_name} must be 3-50 characters and contain only letters, numbers, and underscores";
            return false;
        }
        
        return true;
    }

    /**
     * Validate customer code
     */
    public function customerCode($field, $value, $field_name = 'Customer Code') {
        if (!preg_match('/^CUS\d{6}$/', $value)) {
            $this->errors[$field] = "{$field_name} must be in format: CUS000001";
            return false;
        }
        
        return true;
    }

    /**
     * Custom validation rule
     */
    public function custom($field, $callback, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (!call_user_func($callback)) {
            $this->errors[$field] = "{$field_name} validation failed";
            return false;
        }
        
        return true;
    }

    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function firstError() {
        return reset($this->errors);
    }

    /**
     * Get error for specific field
     */
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }

    /**
     * Get validated data
     */
    public function validated() {
        return $this->data;
    }

    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for database
     */
    public static function sanitizeDB($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeDB'], $input);
        }
        return strip_tags(trim($input));
    }

    /**
     * Validate amount range
     */
    public function amountRange($field, $value, $min, $max, $field_name = null) {
        $field_name = $field_name ?? $field;
        
        if (!is_numeric($value)) {
            $this->errors[$field] = "{$field_name} must be a valid amount";
            return false;
        }
        
        if ($value < $min) {
            $this->errors[$field] = "{$field_name} must be at least " . formatMoney($min);
            return false;
        }
        
        if ($value > $max) {
            $this->errors[$field] = "{$field_name} must not exceed " . formatMoney($max);
            return false;
        }
        
        return true;
    }
}
?>