<?php
/**
 * Email Configuration - Phase 3 Enhancement
 * PHPMailer Integration for Email Notifications
 */

// Email Configuration
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'your-email@gmail.com');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: 'your-app-password');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@certificate.local');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Certificate Designer System');
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls'); // tls or ssl

// Email Features Toggle
define('ENABLE_EMAIL_NOTIFICATIONS', getenv('ENABLE_EMAIL_NOTIFICATIONS') ?: true);
define('SEND_REGISTRATION_EMAIL', true);
define('SEND_PASSWORD_RESET_EMAIL', true);
define('SEND_CERTIFICATE_READY_EMAIL', true);
define('SEND_BATCH_COMPLETE_EMAIL', true);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', dirname(__FILE__) . '/../email_templates/');

// Mail Settings
define('MAIL_RETRIES', 3);
define('MAIL_RETRY_DELAY', 5); // seconds

// Verify PHPMailer is installed
if (!file_exists(dirname(__FILE__) . '/../vendor/autoload.php')) {
    // Log warning but don't fail
    error_log('Warning: PHPMailer not installed. Email functionality disabled.');
}
?>
