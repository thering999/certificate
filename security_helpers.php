<?php
// CSRF Token Helper Functions

// สร้าง CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ตรวจสอบ CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// สร้าง input field สำหรับ CSRF token
function csrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Input Validation Helper Functions

// ทำความสะอาด string input
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ตรวจสอบ email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ตรวจสอบความยาว string
function validateLength($input, $min = 1, $max = 255) {
    $len = mb_strlen($input);
    return $len >= $min && $len <= $max;
}

// ตรวจสอบตัวเลขเท่านั้น
function validateNumeric($input) {
    return is_numeric($input);
}

// ตรวจสอบ URL
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Session Security Helper Functions

// ตั้งค่า session ที่ปลอดภัย
function secureSession() {
    // ใช้ secure และ httponly cookies
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // ใช้ session ID ที่ปลอดภัย
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID เพื่อป้องกัน session fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// ตรวจสอบ session timeout
function checkSessionTimeout($timeout = 3600) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Rate Limiting Helper Functions

// ตรวจสอบ rate limit
function checkRateLimit($action, $limit = 10, $period = 60) {
    $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset หากเกินช่วงเวลา
    if (time() - $data['start'] > $period) {
        $_SESSION[$key] = ['count' => 1, 'start' => time()];
        return true;
    }
    
    // ตรวจสอบจำนวนครั้ง
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Password Security Helper Functions

// ตรวจสอบความแข็งแรงของรหัสผ่าน
function validatePasswordStrength($password) {
    // อย่างน้อย 8 ตัวอักษร, มีตัวพิมพ์ใหญ่, ตัวพิมพ์เล็ก, ตัวเลข
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'รหัสผ่านต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว'];
    }
    return ['valid' => true, 'message' => 'รหัสผ่านปลอดภัย'];
}

// XSS Protection
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// SQL Injection Protection (ใช้ prepared statements เสมอ)
// ตัวอย่าง:
// $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
// $stmt->bind_param("i", $id);
// $stmt->execute();
?>
