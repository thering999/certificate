<?php
/**
 * ExportHistoryManager - จัดการประวัติการส่งออก
 * 
 * Usage:
 * $manager = new ExportHistoryManager($conn);
 * $manager->recordExport(1, 'csv', 100, 'file.csv', 5120);
 */
class ExportHistoryManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * บันทึกการส่งออก
     */
    public function recordExport($user_id, $export_type, $total_records, $file_name, $file_size, $file_path = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO export_history 
            (user_id, export_type, total_records, file_name, file_size, file_path, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->bind_param("isiiiss", $user_id, $export_type, $total_records, $file_name, $file_size, $file_path);
        $result = $stmt->execute();
        $stmt->close();
        
        return $this->conn->insert_id;
    }
    
    /**
     * อัปเดตสถานะการส่งออก
     */
    public function updateStatus($export_id, $status, $notes = null) {
        $completed = ($status === 'completed') ? 'NOW()' : 'NULL';
        $sql = "UPDATE export_history 
                SET status = ?, notes = ?, completed_at = $completed
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $notes, $export_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดูประวัติการส่งออก
     */
    public function getHistory($user_id = null, $limit = 50) {
        $sql = "SELECT * FROM export_history WHERE 1=1";
        
        if ($user_id) {
            $sql .= " AND user_id = $user_id";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $limit";
        
        $result = $this->conn->query($sql);
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * สถิติการส่งออก
     */
    public function getStatistics($days = 30) {
        $sql = "SELECT 
                    export_type,
                    COUNT(*) as count,
                    SUM(total_records) as total_records,
                    SUM(file_size) as total_size,
                    AVG(total_records) as avg_records
                FROM export_history
                WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY export_type";
        
        $result = $this->conn->query($sql);
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

/**
 * AuditLogManager - จัดการ Audit Logs
 */
class AuditLogManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * บันทึก action
     */
    public function logAction($user_id, $action, $table_name, $record_id, $old_value = null, $new_value = null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $old_json = $old_value ? json_encode($old_value) : null;
        $new_json = $new_value ? json_encode($new_value) : null;
        
        $stmt = $this->conn->prepare("
            INSERT INTO audit_logs 
            (user_id, action, table_name, record_id, old_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("issiisss", $user_id, $action, $table_name, $record_id, $old_json, $new_json, $ip_address, $user_agent);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดู audit trail
     */
    public function getAuditTrail($user_id = null, $action = null, $limit = 100) {
        $sql = "SELECT * FROM audit_logs WHERE 1=1";
        
        if ($user_id) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        
        if ($action) {
            $sql .= " AND action = '" . $this->conn->real_escape_string($action) . "'";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit);
        
        $result = $this->conn->query($sql);
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['old_value'] = json_decode($row['old_value'], true);
            $row['new_value'] = json_decode($row['new_value'], true);
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * เปรียบเทียบการเปลี่ยนแปลง
     */
    public function getChangeHistory($table_name, $record_id) {
        $sql = "SELECT old_value, new_value, created_at, user_id
                FROM audit_logs
                WHERE table_name = ? AND record_id = ?
                ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $table_name, $record_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $changes = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['old_value'] = json_decode($row['old_value'], true);
            $row['new_value'] = json_decode($row['new_value'], true);
            $changes[] = $row;
        }
        
        $stmt->close();
        return $changes;
    }
}

/**
 * APIKeyManager - จัดการ API Keys
 */
class APIKeyManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * สร้าง API Key ใหม่
     */
    public function createKey($user_id, $name, $rate_limit = 1000, $expires_at = null) {
        $api_key = 'sk_' . bin2hex(random_bytes(16));
        $api_secret = hash('sha256', random_bytes(32));
        
        $stmt = $this->conn->prepare("
            INSERT INTO api_keys 
            (user_id, api_key, api_secret, name, rate_limit, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("isisss", $user_id, $api_key, $api_secret, $name, $rate_limit, $expires_at);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return [
                'api_key' => $api_key,
                'api_secret' => $api_secret,
                'id' => $this->conn->insert_id
            ];
        }
        
        return false;
    }
    
    /**
     * ตรวจสอบ API Key
     */
    public function validateKey($api_key) {
        $stmt = $this->conn->prepare("
            SELECT * FROM api_keys 
            WHERE api_key = ? AND is_active = TRUE 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $key = $result->fetch_assoc();
        $stmt->close();
        
        return $key;
    }
    
    /**
     * บันทึกการใช้ API
     */
    public function recordUsage($api_key_id) {
        $stmt = $this->conn->prepare("
            UPDATE api_keys 
            SET last_used = NOW() 
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $api_key_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดูรายการ API Keys ของผู้ใช้
     */
    public function getUserKeys($user_id) {
        $stmt = $this->conn->prepare("
            SELECT id, name, api_key, is_active, last_used, rate_limit, created_at, expires_at
            FROM api_keys 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $keys = [];
        
        while ($row = $result->fetch_assoc()) {
            // ซ่อน api_secret
            $row['api_key'] = substr($row['api_key'], 0, 10) . '...';
            $keys[] = $row;
        }
        
        $stmt->close();
        return $keys;
    }
}

/**
 * BatchJobManager - จัดการงาน batch
 */
class BatchJobManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * สร้างงาน batch ใหม่
     */
    public function createJob($user_id, $job_type, $total_items, $file_path = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO batch_jobs 
            (user_id, job_type, status, total_items, file_path, created_at)
            VALUES (?, ?, 'pending', ?, ?, NOW())
        ");
        
        $stmt->bind_param("isis", $user_id, $job_type, $total_items, $file_path);
        $result = $stmt->execute();
        $stmt->close();
        
        return $this->conn->insert_id;
    }
    
    /**
     * อัปเดตความคืบหน้า
     */
    public function updateProgress($job_id, $processed_items, $error_message = null) {
        // Get total items
        $result = $this->conn->query("SELECT total_items FROM batch_jobs WHERE id = " . intval($job_id));
        $job = $result->fetch_assoc();
        $total = $job['total_items'];
        
        $percentage = ($total > 0) ? intval(($processed_items / $total) * 100) : 0;
        
        $stmt = $this->conn->prepare("
            UPDATE batch_jobs 
            SET processed_items = ?, progress_percentage = ?, error_message = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param("iisi", $processed_items, $percentage, $error_message, $job_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * เสร็จสิ้นงาน
     */
    public function completeJob($job_id, $status = 'completed') {
        $stmt = $this->conn->prepare("
            UPDATE batch_jobs 
            SET status = ?, completed_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("si", $status, $job_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดูงาน batch ที่กำลังทำอยู่
     */
    public function getActiveJobs($user_id = null) {
        $sql = "SELECT * FROM batch_jobs 
                WHERE status IN ('pending', 'processing')";
        
        if ($user_id) {
            $sql .= " AND user_id = " . intval($user_id);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $result = $this->conn->query($sql);
        $jobs = [];
        
        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }
        
        return $jobs;
    }
}

/**
 * FileStorageManager - ติดตามไฟล์ที่ส่งออก
 */
class FileStorageManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * บันทึกไฟล์ที่ส่งออก
     */
    public function recordFile($export_history_id, $file_name, $file_path, $file_size, $file_type, $mime_type) {
        $checksum = hash_file('sha256', $file_path) ?: null;
        
        $stmt = $this->conn->prepare("
            INSERT INTO file_storage 
            (export_history_id, file_name, file_path, file_size, file_type, mime_type, checksum, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("issiisss", $export_history_id, $file_name, $file_path, $file_size, $file_type, $mime_type, $checksum);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดูไฟล์ที่หมดอายุ
     */
    public function getExpiredFiles() {
        $sql = "SELECT * FROM file_storage 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW()
                AND is_deleted = FALSE";
        
        $result = $this->conn->query($sql);
        $files = [];
        
        while ($row = $result->fetch_assoc()) {
            $files[] = $row;
        }
        
        return $files;
    }
    
    /**
     * ลบไฟล์เก่า
     */
    public function deleteFile($file_id) {
        // Get file path
        $result = $this->conn->query("SELECT file_path FROM file_storage WHERE id = " . intval($file_id));
        $file = $result->fetch_assoc();
        
        if ($file && file_exists($file['file_path'])) {
            @unlink($file['file_path']);
        }
        
        // Mark as deleted
        $stmt = $this->conn->prepare("
            UPDATE file_storage 
            SET is_deleted = TRUE, deleted_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("i", $file_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ดูสถิติการใช้พื้นที่
     */
    public function getStorageStats() {
        $sql = "SELECT 
                    COUNT(*) as total_files,
                    SUM(file_size) as total_size,
                    COUNT(DISTINCT export_history_id) as unique_exports,
                    GROUP_CONCAT(DISTINCT file_type) as file_types
                FROM file_storage
                WHERE is_deleted = FALSE";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
}

// Usage example:
/*
require_once 'db.php';

// Export History
$export_mgr = new ExportHistoryManager($conn);
$export_id = $export_mgr->recordExport(1, 'csv', 100, 'certificates.csv', 5120, '/tmp/export/');
$export_mgr->updateStatus($export_id, 'completed');
$stats = $export_mgr->getStatistics(30);

// Audit Logs
$audit_mgr = new AuditLogManager($conn);
$audit_mgr->logAction(1, 'create', 'certificate_names', 123, null, ['name' => 'John Doe']);

// API Keys
$api_mgr = new APIKeyManager($conn);
$key = $api_mgr->createKey(1, 'My API Key');
$valid = $api_mgr->validateKey($_SERVER['HTTP_X_API_KEY'] ?? '');

// Batch Jobs
$batch_mgr = new BatchJobManager($conn);
$job_id = $batch_mgr->createJob(1, 'export', 1000);
$batch_mgr->updateProgress($job_id, 500);
$batch_mgr->completeJob($job_id, 'completed');

// File Storage
$file_mgr = new FileStorageManager($conn);
$file_mgr->recordFile($export_id, 'certs.csv', '/tmp/certs.csv', 5120, 'csv', 'text/csv');
*/
?>
