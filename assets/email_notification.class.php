<?php
/**
 * Email Notification Class - Phase 3 Enhancement
 * Handles all email sending with PHPMailer
 * 
 * @version 1.0.0
 * @date October 19, 2025
 */

require_once 'email_config.php';
require_once 'error_handler.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailNotification {
    private $mail;
    private $enabled;
    private $errors = [];
    
    /**
     * Constructor - Initialize PHPMailer
     */
    public function __construct() {
        $this->enabled = ENABLE_EMAIL_NOTIFICATIONS && file_exists(dirname(__FILE__) . '/../vendor/autoload.php');
        
        if ($this->enabled) {
            try {
                require_once dirname(__FILE__) . '/../vendor/autoload.php';
                $this->mail = new PHPMailer(true);
                $this->configureMail();
            } catch (Exception $e) {
                $this->enabled = false;
                ErrorHandler::logDB('PHPMailer initialization failed', $e->getMessage());
            }
        }
    }
    
    /**
     * Configure PHPMailer settings
     */
    private function configureMail() {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = MAIL_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = MAIL_USERNAME;
            $this->mail->Password = MAIL_PASSWORD;
            $this->mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = MAIL_PORT;
            $this->mail->CharSet = 'UTF-8';
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        } catch (Exception $e) {
            ErrorHandler::logDB('PHPMailer configuration failed', $e->getMessage());
        }
    }
    
    /**
     * Send Registration Confirmation Email
     * 
     * @param string $email User email
     * @param string $username Username
     * @param string $activationLink Activation link
     * @return bool
     */
    public function sendRegistrationEmail($email, $username, $activationLink = '') {
        if (!$this->enabled || !SEND_REGISTRATION_EMAIL) {
            return true; // Silently succeed if disabled
        }
        
        try {
            $subject = 'ยินดีต้อนรับ! - Certificate Designer System';
            $body = $this->loadTemplate('registration', [
                'username' => htmlspecialchars($username),
                'email' => htmlspecialchars($email),
                'activation_link' => htmlspecialchars($activationLink),
                'system_name' => MAIL_FROM_NAME,
                'current_year' => date('Y')
            ]);
            
            return $this->send($email, $subject, $body);
        } catch (Exception $e) {
            ErrorHandler::logDB('Registration email error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Password Reset Email
     * 
     * @param string $email User email
     * @param string $username Username
     * @param string $resetLink Reset link
     * @return bool
     */
    public function sendPasswordResetEmail($email, $username, $resetLink) {
        if (!$this->enabled || !SEND_PASSWORD_RESET_EMAIL) {
            return true;
        }
        
        try {
            $subject = 'รีเซ็ตรหัสผ่าน - Certificate Designer System';
            $body = $this->loadTemplate('password_reset', [
                'username' => htmlspecialchars($username),
                'email' => htmlspecialchars($email),
                'reset_link' => htmlspecialchars($resetLink),
                'expiry_time' => '1 ชั่วโมง',
                'system_name' => MAIL_FROM_NAME
            ]);
            
            return $this->send($email, $subject, $body);
        } catch (Exception $e) {
            ErrorHandler::logDB('Password reset email error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Certificate Ready Notification
     * 
     * @param string $email User email
     * @param string $username Username
     * @param string $certificateName Certificate name
     * @param string $downloadLink Download link
     * @return bool
     */
    public function sendCertificateReadyEmail($email, $username, $certificateName, $downloadLink) {
        if (!$this->enabled || !SEND_CERTIFICATE_READY_EMAIL) {
            return true;
        }
        
        try {
            $subject = 'ใบประกาศพร้อมแล้ว! - Certificate Designer System';
            $body = $this->loadTemplate('certificate_ready', [
                'username' => htmlspecialchars($username),
                'email' => htmlspecialchars($email),
                'certificate_name' => htmlspecialchars($certificateName),
                'download_link' => htmlspecialchars($downloadLink),
                'system_name' => MAIL_FROM_NAME,
                'expiry_days' => '30'
            ]);
            
            return $this->send($email, $subject, $body);
        } catch (Exception $e) {
            ErrorHandler::logDB('Certificate ready email error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Batch Processing Complete Email
     * 
     * @param string $email User email
     * @param string $username Username
     * @param int $totalProcessed Total records processed
     * @param int $successCount Success count
     * @param int $failCount Failed count
     * @param string $reportLink Report link
     * @return bool
     */
    public function sendBatchCompleteEmail($email, $username, $totalProcessed, $successCount, $failCount, $reportLink) {
        if (!$this->enabled || !SEND_BATCH_COMPLETE_EMAIL) {
            return true;
        }
        
        try {
            $subject = 'การประมวลผลเสร็จสิ้น - Certificate Designer System';
            $body = $this->loadTemplate('batch_complete', [
                'username' => htmlspecialchars($username),
                'email' => htmlspecialchars($email),
                'total_processed' => $totalProcessed,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'success_rate' => $totalProcessed > 0 ? round(($successCount / $totalProcessed) * 100, 2) : 0,
                'report_link' => htmlspecialchars($reportLink),
                'system_name' => MAIL_FROM_NAME
            ]);
            
            return $this->send($email, $subject, $body);
        } catch (Exception $e) {
            ErrorHandler::logDB('Batch complete email error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email with retry logic
     * 
     * @param string $recipientEmail Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $attachments Optional attachments
     * @return bool
     */
    private function send($recipientEmail, $subject, $body, $attachments = []) {
        if (!$this->enabled) {
            return true;
        }
        
        $retries = 0;
        $maxRetries = MAIL_RETRIES;
        
        while ($retries < $maxRetries) {
            try {
                // Clear previous recipients
                $this->mail->clearAddresses();
                $this->mail->clearAllRecipients();
                
                // Set recipient
                $this->mail->addAddress($recipientEmail);
                
                // Set subject and body
                $this->mail->Subject = $subject;
                $this->mail->isHTML(true);
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                
                // Add attachments if provided
                if (!empty($attachments)) {
                    foreach ($attachments as $file) {
                        if (file_exists($file)) {
                            $this->mail->addAttachment($file);
                        }
                    }
                }
                
                // Send email
                if ($this->mail->send()) {
                    ErrorHandler::log('Email sent to: ' . $recipientEmail, 'INFO');
                    return true;
                }
            } catch (Exception $e) {
                $retries++;
                if ($retries < $maxRetries) {
                    sleep(MAIL_RETRY_DELAY);
                    ErrorHandler::log('Email send retry ' . $retries . ' for: ' . $recipientEmail, 'INFO');
                } else {
                    ErrorHandler::logDB('Email send failed after retries', 'To: ' . $recipientEmail . ', Error: ' . $e->getMessage());
                    return false;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Load email template
     * 
     * @param string $template Template name (without .html)
     * @param array $variables Variables for template
     * @return string
     */
    private function loadTemplate($template, $variables = []) {
        $templatePath = EMAIL_TEMPLATES_DIR . $template . '.html';
        
        if (!file_exists($templatePath)) {
            ErrorHandler::log('Email template not found: ' . $templatePath, 'INFO');
            return $this->getFallbackTemplate($template, $variables);
        }
        
        $content = file_get_contents($templatePath);
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Fallback template if file not found
     * 
     * @param string $template Template name
     * @param array $variables Variables
     * @return string
     */
    private function getFallbackTemplate($template, $variables) {
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<h2>' . htmlspecialchars($variables['system_name'] ?? 'System') . '</h2>';
        $html .= '<p>Template: ' . htmlspecialchars($template) . '</p>';
        $html .= '<hr>';
        $html .= '<p><strong>Variables:</strong></p>';
        $html .= '<ul>';
        foreach ($variables as $key => $value) {
            $html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
        }
        $html .= '</ul>';
        $html .= '</body></html>';
        return $html;
    }
    
    /**
     * Check if email system is enabled
     * 
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Get errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Add custom error
     * 
     * @param string $error
     */
    public function addError($error) {
        $this->errors[] = $error;
    }
}
?>
