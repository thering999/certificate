<?php
/**
 * Security Configuration
 * ตั้งค่าความปลอดภัยต่างๆ ของระบบ
 */

// 1️⃣ Rate Limiting - ป้องกัน Brute Force Attack
define('LOGIN_ATTEMPTS_LIMIT', 5);          // จำกัดพยายาม Login 5 ครั้ง
define('LOGIN_ATTEMPT_WINDOW', 900);        // ใน 15 นาที (900 วินาที)
define('LOCKOUT_DURATION', 1800);           // Lock 30 นาที หลังจาก exceed limit

// 2️⃣ Session Security
define('SESSION_TIMEOUT', 3600);            // Session expire ใน 1 ชั่วโมง
define('SESSION_REGENERATE_INTERVAL', 300); // Regenerate session ID ทุก 5 นาที

// 3️⃣ CSRF Protection
define('CSRF_TOKEN_LIFETIME', 3600);        // CSRF Token valid 1 ชั่วโมง

// 4️⃣ Password Policy
define('MIN_PASSWORD_LENGTH', 8);           // ความยาวรหัสผ่านต่ำสุด 8 ตัว
define('PASSWORD_REQUIRE_UPPERCASE', true); // ต้องมีตัวพิมพ์ใหญ่
define('PASSWORD_REQUIRE_LOWERCASE', true); // ต้องมีตัวพิมพ์เล็ก
define('PASSWORD_REQUIRE_NUMBERS', true);   // ต้องมีตัวเลข
define('PASSWORD_REQUIRE_SPECIAL', true);   // ต้องมี Special character (!@#$%...)

// 5️⃣ IP Whitelist/Blacklist
define('ENABLE_IP_FILTER', true);           // เปิดใช้ IP Filter
define('ADMIN_IP_WHITELIST', array(
    '127.0.0.1',      // Localhost
    '::1',             // IPv6 Localhost
    // เพิ่ม IP ที่อนุญาต เช่น:
    // '192.168.1.100',
    // '203.0.113.0',
));

// 6️⃣ Security Headers
define('SECURITY_HEADERS', array(
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' data: https://fonts.googleapis.com https://cdnjs.cloudflare.com;",
));

// 7️⃣ Logging & Monitoring
define('ENABLE_AUDIT_LOG', true);           // เปิดใช้ Audit Log
define('LOG_SUSPICIOUS_ACTIVITY', true);    // บันทึกกิจกรรมที่น่าสงสัย
define('LOG_FILE_PATH', __DIR__ . '/logs/'); // Path เก็บ Log Files

// 8️⃣ 2FA Settings
define('ENABLE_2FA', true);                 // เปิดใช้ 2FA (Two-Factor Authentication)

// 9️⃣ Admin Panel Protection
define('ADMIN_PANEL_REQUIRE_2FA', true);    // Admin ต้อง 2FA
define('ADMIN_SESSION_TIMEOUT', 1800);      // Admin session expire ใน 30 นาที (เร็วกว่า user)

// 🔟 API Rate Limiting
define('API_RATE_LIMIT', 100);              // API requests 100 ต่อ minute
define('API_RATE_LIMIT_WINDOW', 60);        // ต่อ 1 นาที

return array(
    'login_attempts_limit' => LOGIN_ATTEMPTS_LIMIT,
    'login_attempt_window' => LOGIN_ATTEMPT_WINDOW,
    'lockout_duration' => LOCKOUT_DURATION,
    'session_timeout' => SESSION_TIMEOUT,
    'min_password_length' => MIN_PASSWORD_LENGTH,
);
?>
