<?php
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    include 'errors/error_page.php';
    exit;
}

$error = null;
$success_count = 0;
$upload_dir = 'uploads/';

// Create upload directory if not exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validation();
    
    // Validate file upload
    $max_file_size = 5 * 1024 * 1024; // 5MB
    $validator->file('excelFile', $_FILES['excelFile'] ?? [], ['text/csv', 'text/plain', 'application/csv'], $max_file_size, 'ไฟล์ CSV');
    
    if (!$validator->isValid()) {
        $errors = $validator->getErrors();
        $error = implode(', ', $errors['excelFile'] ?? ['ไฟล์ไม่ถูกต้อง']);
        ErrorHandler::log("File upload validation failed: $error", 'WARNING');
    } elseif (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $error = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
        ErrorHandler::logAPI('/upload.php', $error, 'POST');
    } else {
        $file = $_FILES['excelFile'];
        $tmp = $file['tmp_name'];
        $names = [];
        
        try {
            // Read CSV file with UTF-8 encoding
            $csv = file_get_contents($tmp);
            if (mb_detect_encoding($csv, 'UTF-8', true) === false) {
                $csv = mb_convert_encoding($csv, 'UTF-8');
            }
            
            $tmp_utf8 = tempnam(sys_get_temp_dir(), 'csv_utf8_');
            file_put_contents($tmp_utf8, $csv);
            
            if (($handle = fopen($tmp_utf8, 'r')) !== false) {
                $rowIndex = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    // Skip header row
                    if ($rowIndex === 0) {
                        $rowIndex++;
                        continue;
                    }
                    
                    $name = trim($data[0] ?? '');
                    
                    // Validate name
                    if (empty($name)) {
                        ErrorHandler::log("Empty name in row $rowIndex", 'WARNING');
                        $rowIndex++;
                        continue;
                    }
                    
                    if (strlen($name) > 255) {
                        ErrorHandler::log("Name too long in row $rowIndex: " . mb_substr($name, 0, 50), 'WARNING');
                        $rowIndex++;
                        continue;
                    }
                    
                    // Sanitize and add name
                    $safe_name = Validation::sanitizeForDB($name);
                    $names[] = $safe_name;
                    $rowIndex++;
                }
                fclose($handle);
                unlink($tmp_utf8);
            }
            
            // Insert names into database
            if (count($names) > 0) {
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare('INSERT INTO certificate_names (user_id, name, created_at) VALUES (?, ?, NOW())');
                
                if (!$stmt) {
                    throw new Exception($conn->error);
                }
                
                foreach ($names as $name) {
                    $stmt->bind_param('is', $user_id, $name);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        ErrorHandler::logDB($stmt->error, "INSERT certificate_names");
                    }
                }
                $stmt->close();
                
                $_SESSION['alert'] = "นำเข้ารายชื่อสำเร็จ $success_count คน";
                ErrorHandler::log("Batch upload completed: $success_count names by user $user_id", 'INFO');
            } else {
                $error = 'ไม่พบชื่อในไฟล์';
            }
            
        } catch (Exception $e) {
            ErrorHandler::logDB($e->getMessage());
            $error = 'เกิดข้อผิดพลาดในการประมวลผลไฟล์';
        }
    }
    
    // Redirect after processing
    if (!$error && $success_count > 0) {
        header('Location: designer.php');
        exit;
    }
}
