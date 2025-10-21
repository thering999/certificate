<?php
/**
 * Validation Class - Certificate Designer System
 * Provides comprehensive input validation and sanitization
 * Version: 1.0.0
 * Last Updated: October 2025
 */

class Validation {
    
    private $errors = [];
    private $validated_data = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->errors = [];
        $this->validated_data = [];
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get all validated data
     */
    public function getValidatedData() {
        return $this->validated_data;
    }
    
    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
    
    /**
     * Add error message
     */
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $value, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (empty(trim($value))) {
            $this->addError($field, "$label จำเป็นต้องกรอก");
            return false;
        }
        
        $this->validated_data[$field] = trim($value);
        return true;
    }
    
    /**
     * Validate email format
     */
    public function email($field, $value, $label = null) {
        $label = $label ?? 'Email';
        
        if (empty($value)) {
            return true; // Optional field
        }
        
        $value = trim($value);
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "$label ไม่ถูกต้อง");
            return false;
        }
        
        // Prevent extremely long emails
        if (strlen($value) > 100) {
            $this->addError($field, "$label ยาวเกินไป");
            return false;
        }
        
        $this->validated_data[$field] = strtolower($value);
        return true;
    }
    
    /**
     * Validate username format
     */
    public function username($field, $value, $label = null) {
        $label = $label ?? 'ชื่อผู้ใช้';
        
        if (empty($value)) {
            return true;
        }
        
        $value = trim($value);
        
        // Username must be 3-50 characters, alphanumeric and underscore only
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $value)) {
            $this->addError($field, "$label ต้องมี 3-50 ตัวอักษร ตัวเลข และ underscore เท่านั้น");
            return false;
        }
        
        $this->validated_data[$field] = strtolower($value);
        return true;
    }
    
    /**
     * Validate password strength
     */
    public function password($field, $value, $label = null, $min_length = 8) {
        $label = $label ?? 'รหัสผ่าน';
        
        if (empty($value)) {
            return true;
        }
        
        $value = $value; // Don't trim password
        
        // Check length
        if (strlen($value) < $min_length) {
            $this->addError($field, "$label ต้องมีอย่างน้อย $min_length ตัวอักษร");
            return false;
        }
        
        if (strlen($value) > 255) {
            $this->addError($field, "$label ยาวเกินไป");
            return false;
        }
        
        // Check password strength (optional strong pattern)
        $has_upper = preg_match('/[A-Z]/', $value);
        $has_lower = preg_match('/[a-z]/', $value);
        $has_digit = preg_match('/[0-9]/', $value);
        $has_special = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value);
        
        // For stronger security, require at least 3 of the 4 criteria
        $strength = $has_upper + $has_lower + $has_digit + $has_special;
        
        if ($strength < 2) {
            $this->addError($field, "$label อ่อนแอเกินไป ใช้ตัวพิมพ์ใหญ่ ตัวเลข และสัญลักษณ์");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate password confirmation
     */
    public function confirm($field, $value, $compare_field, $compare_value, $label = null) {
        $label = $label ?? 'รหัสผ่าน';
        
        if ($value !== $compare_value) {
            $this->addError($field, "$label ไม่ตรงกัน");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate string length
     */
    public function length($field, $value, $min, $max = null, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (empty($value)) {
            return true;
        }
        
        $value = trim($value);
        $len = strlen($value);
        
        if ($len < $min) {
            $this->addError($field, "$label ต้องมีอย่างน้อย $min ตัวอักษร");
            return false;
        }
        
        if ($max !== null && $len > $max) {
            $this->addError($field, "$label ไม่ควรเกิน $max ตัวอักษร");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate integer
     */
    public function integer($field, $value, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (empty($value)) {
            return true;
        }
        
        if (!is_numeric($value) || intval($value) != $value) {
            $this->addError($field, "$label ต้องเป็นตัวเลข");
            return false;
        }
        
        $this->validated_data[$field] = intval($value);
        return true;
    }
    
    /**
     * Validate numeric value with range
     */
    public function range($field, $value, $min, $max, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (!$this->integer($field, $value, $label)) {
            return false;
        }
        
        $value = intval($value);
        
        if ($value < $min || $value > $max) {
            $this->addError($field, "$label ต้องอยู่ระหว่าง $min ถึง $max");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate file upload
     */
    public function file($field, $file_array, $allowed_types = [], $max_size = 5242880, $label = null) {
        $label = $label ?? 'ไฟล์';
        
        // Check if file was uploaded
        if (!isset($file_array['error']) || $file_array['error'] === UPLOAD_ERR_NO_FILE) {
            return true; // Optional field
        }
        
        // Check upload errors
        if ($file_array['error'] !== UPLOAD_ERR_OK) {
            $this->addError($field, "เกิดข้อผิดพลาดในการอัปโหลด $label (รหัส: {$file_array['error']})");
            return false;
        }
        
        // Check file size
        if ($file_array['size'] > $max_size) {
            $max_mb = round($max_size / 1048576, 2);
            $this->addError($field, "$label ไม่ควรเกิน {$max_mb} MB");
            return false;
        }
        
        // Check file type (MIME type)
        if (!empty($allowed_types)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_array['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $allowed = implode(', ', $allowed_types);
                $this->addError($field, "$label ไม่รองรับ. ประเภทที่อนุญาต: $allowed");
                return false;
            }
        }
        
        // Store file info
        $this->validated_data[$field] = [
            'name' => basename($file_array['name']),
            'tmp_name' => $file_array['tmp_name'],
            'size' => $file_array['size'],
            'type' => $file_array['type']
        ];
        
        return true;
    }
    
    /**
     * Validate image file
     */
    public function image($field, $file_array, $max_size = 5242880, $label = null) {
        $label = $label ?? 'รูปภาพ';
        
        $allowed_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        return $this->file($field, $file_array, $allowed_types, $max_size, $label);
    }
    
    /**
     * Validate CSV file
     */
    public function csv($field, $file_array, $max_size = 5242880, $label = null) {
        $label = $label ?? 'ไฟล์ CSV';
        
        // Allow CSV MIME types
        $allowed_types = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel'
        ];
        
        return $this->file($field, $file_array, $allowed_types, $max_size, $label);
    }
    
    /**
     * Validate Excel file
     */
    public function excel($field, $file_array, $max_size = 5242880, $label = null) {
        $label = $label ?? 'ไฟล์ Excel';
        
        $allowed_types = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/x-excel',
            'application/x-msexcel'
        ];
        
        return $this->file($field, $file_array, $allowed_types, $max_size, $label);
    }
    
    /**
     * Validate phone number
     */
    public function phone($field, $value, $label = null) {
        $label = $label ?? 'หมายเลขโทรศัพท์';
        
        if (empty($value)) {
            return true;
        }
        
        $value = trim($value);
        
        // Thai phone format: 08X-XXXX-XXXX or similar
        if (!preg_match('/^(\+66|0)[0-9]{9,10}$/', str_replace(['-', ' '], '', $value))) {
            $this->addError($field, "$label ไม่ถูกต้อง");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate date format
     */
    public function date($field, $value, $format = 'Y-m-d', $label = null) {
        $label = $label ?? 'วันที่';
        
        if (empty($value)) {
            return true;
        }
        
        $value = trim($value);
        $date = \DateTime::createFromFormat($format, $value);
        
        if (!$date || $date->format($format) !== $value) {
            $this->addError($field, "$label ไม่ถูกต้อง (รูปแบบ: $format)");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate enum/select value
     */
    public function enum($field, $value, $allowed_values = [], $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (empty($value)) {
            return true;
        }
        
        if (!in_array($value, $allowed_values)) {
            $this->addError($field, "$label ไม่ถูกต้อง");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Validate Thai text (alphanumeric + Thai characters)
     */
    public function thai($field, $value, $allow_numbers = true, $label = null) {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        
        if (empty($value)) {
            return true;
        }
        
        $value = trim($value);
        
        // Thai text pattern
        $pattern = $allow_numbers 
            ? '/^[ก-๙a-zA-Z0-9\s\-\.]/u' 
            : '/^[ก-๙a-zA-Z\s\-\.]/u';
        
        if (!preg_match($pattern, $value)) {
            $this->addError($field, "$label มีตัวอักษรไม่ถูกต้อง");
            return false;
        }
        
        $this->validated_data[$field] = $value;
        return true;
    }
    
    /**
     * Sanitize output for display
     */
    public static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize for database (basic escaping)
     */
    public static function sanitizeForDB($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitizeForDB'], $value);
        }
        return addslashes(strip_tags($value));
    }
    
    /**
     * Clean and prepare data
     */
    public function clean($field, $value) {
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlspecialchars($value);
        
        $this->validated_data[$field] = $value;
        return $value;
    }
    
    /**
     * Get validation report as JSON
     */
    public function toJSON() {
        return json_encode([
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'data' => $this->validated_data,
            'error_count' => count($this->errors)
        ]);
    }
    
    /**
     * Get validation report as HTML (for debugging)
     */
    public function toHTML() {
        $html = '<div class="validation-report">';
        $html .= '<p><strong>Valid:</strong> ' . ($this->isValid() ? 'Yes' : 'No') . '</p>';
        
        if (!empty($this->errors)) {
            $html .= '<ul class="errors">';
            foreach ($this->errors as $field => $messages) {
                foreach ($messages as $message) {
                    $html .= '<li><strong>' . htmlspecialchars($field) . ':</strong> ' . htmlspecialchars($message) . '</li>';
                }
            }
            $html .= '</ul>';
        }
        
        $html .= '</div>';
        return $html;
    }
}

/**
 * Shortcut functions for common validations
 */

function validate_email($email) {
    $v = new Validation();
    $v->email('email', $email);
    return $v->isValid();
}

function validate_username($username) {
    $v = new Validation();
    $v->username('username', $username);
    return $v->isValid();
}

function sanitize_output($value) {
    return Validation::sanitize($value);
}

function sanitize_db($value) {
    return Validation::sanitizeForDB($value);
}
