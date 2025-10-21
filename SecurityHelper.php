<?php
/**
 * Security Helper Functions
 * ฟังก์ชั่นสำหรับป้องกันการโจมตี
 */

require_once 'security_config.php';

class SecurityHelper {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * 1️⃣ ตรวจสอบ Rate Limiting (Brute Force Protection)
     */
    public function checkLoginAttempts($username) {
        $table = 'login_attempts';
        
        // สร้าง Table ถ้ายังไม่มี
        $this->conn->query("CREATE TABLE IF NOT EXISTS $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            ip_address VARCHAR(50),
            attempt_time DATETIME,
            INDEX idx_username_ip (username, ip_address, attempt_time)
        ) ENGINE=InnoDB");
        
        $ip = $this->getClientIP();
        $current_time = time();
        $window_time = $current_time - LOGIN_ATTEMPT_WINDOW;
        
        // ดึงจำนวนครั้งที่ login ผิดในช่วง 15 นาที
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM $table 
            WHERE username = ? AND ip_address = ? AND attempt_time > FROM_UNIXTIME(?)");
        $stmt->bind_param("ssi", $username, $ip, $window_time);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempts'];
    }
    
    /**
     * 2️⃣ บันทึก Failed Login Attempt
     */
    public function recordFailedLogin($username) {
        $ip = $this->getClientIP();
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (username, ip_address, attempt_time) 
            VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $username, $ip);
        $stmt->execute();
        
        // Log เข้า Audit Log
        $this->auditLog('LOGIN_FAILED', "Failed login attempt for user: $username from IP: $ip");
    }
    
    /**
     * 3️⃣ ตรวจสอบ IP Whitelist
     */
    public function isIPAllowed($ip = null) {
        if (!ENABLE_IP_FILTER) return true;
        
        $ip = $ip ?? $this->getClientIP();
        return in_array($ip, ADMIN_IP_WHITELIST);
    }
    
    /**
     * 4️⃣ ตรวจสอบคุณภาพรหัสผ่าน
     */
    public function validatePasswordStrength($password) {
        $errors = array();
        
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = "รหัสผ่านต้องยาวอย่างน้อย " . MIN_PASSWORD_LENGTH . " ตัวอักษร";
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "รหัสผ่านต้องมีตัวพิมพ์ใหญ่ (A-Z)";
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "รหัสผ่านต้องมีตัวพิมพ์เล็ก (a-z)";
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "รหัสผ่านต้องมีตัวเลข (0-9)";
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = "รหัสผ่านต้องมี Special character (!@#$%...)";
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * 5️⃣ ตรวจสอบ CSRF Token
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token'])) return false;
        
        // ตรวจสอบ Token
        if ($token !== $_SESSION['csrf_token']) return false;
        
        // ตรวจสอบ Lifetime
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
            unset($_SESSION['csrf_token']);
            return false;
        }
        
        return true;
    }
    
    /**
     * 6️⃣ ตรวจสอบ Session Timeout
     */
    public function checkSessionTimeout() {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        $timeout = isset($_SESSION['role']) && $_SESSION['role'] === 'admin' 
            ? ADMIN_SESSION_TIMEOUT 
            : SESSION_TIMEOUT;
        
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * 7️⃣ Regenerate Session ID (ป้องกัน Session Fixation)
     */
    public function regenerateSessionID() {
        if (!isset($_SESSION['session_created'])) {
            $_SESSION['session_created'] = time();
        }
        
        if (time() - $_SESSION['session_created'] > SESSION_REGENERATE_INTERVAL) {
            session_regenerate_id(true);
            $_SESSION['session_created'] = time();
        }
    }
    
    /**
     * 8️⃣ Apply Security Headers
     */
    public function applySecurityHeaders() {
        foreach (SECURITY_HEADERS as $header => $value) {
            header("$header: $value");
        }
    }
    
    /**
     * 9️⃣ Audit Log
     */
    public function auditLog($action, $details, $user_id = null) {
        if (!ENABLE_AUDIT_LOG) return;
        
        $user_id = $user_id ?? $_SESSION['user_id'] ?? null;
        $ip = $this->getClientIP();
        $timestamp = date('Y-m-d H:i:s');
        
        // สร้าง Table ถ้ายังไม่มี
        $this->conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(50),
            user_agent VARCHAR(255),
            created_at DATETIME,
            INDEX idx_user_action (user_id, action, created_at)
        ) ENGINE=InnoDB");
        
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        
        $stmt = $this->conn->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $action, $details, $ip, $user_agent, $timestamp);
        $stmt->execute();
    }
    
    /**
     * 🔟 ป้องกัน SQL Injection
     */
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map(array($this, 'sanitizeInput'), $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 1️⃣1️⃣ ป้องกัน XSS
     */
    public function escapeOutput($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 1️⃣2️⃣ ดึง Client IP
     */
    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }
    
    /**
     * 1️⃣3️⃣ Generate Secure Random Token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
}

?>
