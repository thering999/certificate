<?php
/**
 * Error Handler - Certificate Designer System
 * Global error and exception handling
 * Version: 1.0.0
 * Last Updated: October 2025
 */

class ErrorHandler {
    
    private static $instance;
    private static $log_dir = 'logs';
    
    /**
     * Singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Set up error handling
     */
    private function __construct() {
        // Create logs directory if not exists
        if (!is_dir(self::$log_dir)) {
            mkdir(self::$log_dir, 0755, true);
        }
        
        // Set error handlers
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Initialize error handler
     */
    public static function init($log_dir = 'logs') {
        self::$log_dir = $log_dir;
        self::getInstance();
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        // Don't handle suppressed errors
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        // Log error
        $this->logError($errno, $errstr, $errfile, $errline);
        
        // Display friendly error message
        if (ini_get('display_errors')) {
            $this->displayError($errno, $errstr, $errfile, $errline);
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        // Log exception
        $this->logException($exception);
        
        // Display error page
        $this->displayErrorPage(500, $exception->getMessage());
        exit(1);
    }
    
    /**
     * Handle fatal errors
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logError($error['type'], $error['message'], $error['file'], $error['line']);
            $this->displayErrorPage(500, 'Fatal error occurred');
        }
    }
    
    /**
     * Log error to file
     */
    private function logError($errno, $errstr, $errfile, $errline) {
        $error_types = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE_ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        $type = $error_types[$errno] ?? 'UNKNOWN';
        
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $type,
            $errstr,
            $errfile,
            $errline
        );
        
        $this->writeLog('error.log', $message);
    }
    
    /**
     * Log exception
     */
    private function logException($exception) {
        $message = sprintf(
            "[%s] Exception: %s\nFile: %s\nLine: %d\nTrace: %s\n\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        $this->writeLog('exception.log', $message);
    }
    
    /**
     * Log to file
     */
    private function writeLog($filename, $message) {
        $filepath = self::$log_dir . '/' . $filename;
        
        // Rotate log files (max 5 files, max 10MB each)
        if (file_exists($filepath) && filesize($filepath) > 10485760) {
            for ($i = 4; $i > 0; $i--) {
                $old = self::$log_dir . '/' . basename($filename, '.log') . "_$i.log";
                $new = self::$log_dir . '/' . basename($filename, '.log') . "_" . ($i + 1) . ".log";
                if (file_exists($old)) {
                    rename($old, $new);
                }
            }
            rename($filepath, self::$log_dir . '/' . basename($filename, '.log') . "_1.log");
        }
        
        error_log($message, 3, $filepath);
    }
    
    /**
     * Display error message (for development)
     */
    private function displayError($errno, $errstr, $errfile, $errline) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
            echo '<div style="border:1px solid #ccc; padding:10px; margin:10px; background:#f5f5f5;">';
            echo '<strong>Error [' . $errno . ']:</strong> ' . htmlspecialchars($errstr) . '<br>';
            echo '<small>' . htmlspecialchars($errfile) . ':' . $errline . '</small>';
            echo '</div>';
        }
    }
    
    /**
     * Display error page
     */
    public function displayErrorPage($code = 500, $message = '') {
        // Set HTTP status
        http_response_code($code);
        
        // Map error codes to pages
        switch ($code) {
            case 404:
                $title = 'หน้าไม่พบ';
                $description = 'ขออภัย หน้าที่คุณขอไม่พบในระบบ';
                break;
            case 403:
                $title = 'ไม่อนุญาต';
                $description = 'ขออภัย คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
                break;
            case 500:
            default:
                $title = 'เกิดข้อผิดพลาดในระบบ';
                $description = 'ขออภัย เกิดข้อผิดพลาดบางอย่าง กรุณาลองใหม่อีกครั้ง';
                $message = $message ?: 'Internal Server Error';
                break;
        }
        
        // Include error page
        if (file_exists('errors/error_page.php')) {
            include 'errors/error_page.php';
        } else {
            // Fallback error display
            echo "<!DOCTYPE html>";
            echo "<html>";
            echo "<head><title>Error</title></head>";
            echo "<body>";
            echo "<h1>$title</h1>";
            echo "<p>$description</p>";
            echo "</body>";
            echo "</html>";
        }
    }
    
    /**
     * Log custom message
     */
    public static function log($message, $level = 'INFO') {
        $handler = self::getInstance();
        $log_message = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        $handler->writeLog(strtolower($level) . '.log', $log_message);
    }
    
    /**
     * Log database error
     */
    public static function logDB($error_msg, $query = '') {
        $message = "Database Error: $error_msg";
        if (!empty($query)) {
            $message .= "\nQuery: $query";
        }
        self::log($message, 'DATABASE');
    }
    
    /**
     * Log API error
     */
    public static function logAPI($endpoint, $error_msg, $method = 'GET') {
        $message = "API Error [$method $endpoint]: $error_msg";
        self::log($message, 'API');
    }
    
    /**
     * Log security issue
     */
    public static function logSecurity($issue_type, $details = '') {
        $message = "Security Issue [$issue_type]: $details\nIP: {$_SERVER['REMOTE_ADDR']}\nUser-Agent: {$_SERVER['HTTP_USER_AGENT']}";
        self::log($message, 'SECURITY');
    }
}

// Auto-initialize when included
ErrorHandler::init();
