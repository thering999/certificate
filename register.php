<?php
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';

session_start();

$error = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validation();
    
    // Validate username
    $validator->required('username', $_POST['username'] ?? '', 'ชื่อผู้ใช้');
    $validator->username('username', $_POST['username'] ?? '', 'ชื่อผู้ใช้');
    
    // Validate password
    $validator->required('password', $_POST['password'] ?? '', 'รหัสผ่าน');
    $validator->password('password', $_POST['password'] ?? '', 'รหัสผ่าน', 8);
    
    // Validate password confirmation
    $validator->required('password_confirm', $_POST['password_confirm'] ?? '', 'ยืนยันรหัสผ่าน');
    $validator->confirm('password_confirm', $_POST['password_confirm'] ?? '', 'password', $_POST['password'] ?? '', 'ยืนยันรหัสผ่าน');
    
    // Validate email if provided
    if (!empty($_POST['email'] ?? '')) {
        $validator->email('email', $_POST['email'], 'อีเมล');
    }
    
    if ($validator->isValid()) {
        $data = $validator->getValidatedData();
        $role = 'user';
        
        // Check if current user is admin
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare('SELECT role FROM users WHERE id=?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $stmt->bind_result($myrole);
            $stmt->fetch();
            $stmt->close();
            
            if ($myrole === 'admin' && isset($_POST['role']) && in_array($_POST['role'], ['admin', 'user'])) {
                $role = $_POST['role'];
            }
        }
        
        // Check if username already exists
        $check_stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check_stmt->bind_param('s', $data['username']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();
        
        if ($check_result->num_rows > 0) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่อผู้ใช้อื่น';
        } else {
            // Hash password
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Get email from validated data
            $email = $data['email'] ?? null;
            
            // Insert new user with email
            $stmt = $conn->prepare('INSERT INTO users (username, password, role, email) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $data['username'], $hash, $role, $email);
            
            if ($stmt->execute()) {
                ErrorHandler::log("User registered: {$data['username']}", 'INFO');
                
                // Send registration email (Phase 3 Enhancement) - Commented out if PHPMailer not installed
                // if (!empty($email)) {
                //     $emailNotification = new EmailNotification();
                //     $loginLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php';
                //     
                //     if ($emailNotification->sendRegistrationEmail($email, $data['username'], $loginLink)) {
                //         ErrorHandler::log("Registration email sent to: {$email}", 'INFO');
                //     } else {
                //         ErrorHandler::log("Registration email failed for: {$email}", 'INFO');
                //     }
                // }
                
                $_SESSION['alert'] = '✓ สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
                header('Location: register_success.php');
                exit;
            } else {
                ErrorHandler::logDB($stmt->error, 'INSERT INTO users');
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
            }
            $stmt->close();
        }
    } else {
        $errors = $validator->getErrors();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Certificate Designer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'TH Sarabun New', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #667eea;
            font-weight: bold;
            font-size: 28px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 10px 20px;
            font-size: 16px;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        .password-strength {
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength .strength-bar {
            height: 100%;
            background: red;
            width: 0%;
            transition: all 0.3s;
        }
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .error-list li {
            color: #721c24;
            margin: 5px 0;
            font-size: 14px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> สมัครสมาชิก</h2>
            <p class="text-muted">สร้างบัญชีใหม่เพื่อเริ่มใช้งาน</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error-list">
                <strong><i class="fas fa-check-circle"></i> โปรดแก้ไขข้อผิดพลาด:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $field => $messages): ?>
                        <?php foreach ($messages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" id="registerForm">
            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="fas fa-user"></i> ชื่อผู้ใช้
                </label>
                <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                       id="username" name="username" placeholder="3-50 ตัวอักษร (ตัวเลข underscore)" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <small class="form-text text-muted">ใช้ได้: a-z, 0-9, underscore</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="email">
                    <i class="fas fa-envelope"></i> อีเมล (ทางเลือก)
                </label>
                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                       id="email" name="email" placeholder="your@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="password">
                    <i class="fas fa-lock"></i> รหัสผ่าน
                </label>
                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                       id="password" name="password" placeholder="อย่างน้อย 8 ตัวอักษร" 
                       oninput="checkPasswordStrength()">
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <small class="form-text text-muted d-block mt-2">
                    <i class="fas fa-info-circle"></i> ต้องมี: ตัวเล็ก หรือ ตัวใหญ่ หรือ ตัวเลข หรือ สัญลักษณ์
                </small>
            </div>
            
            <div class="mb-3">
                <label class="form-label" for="password_confirm">
                    <i class="fas fa-check-circle"></i> ยืนยันรหัสผ่าน
                </label>
                <input type="password" class="form-control <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" 
                       id="password_confirm" name="password_confirm" placeholder="ยุนยันรหัสผ่าน">
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $stmt = $conn->prepare('SELECT role FROM users WHERE id=?');
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $stmt->bind_result($myrole);
                $stmt->fetch();
                $stmt->close();
                if ($myrole === 'admin'):
                ?>
                <div class="mb-3">
                    <label class="form-label" for="role">
                        <i class="fas fa-shield-alt"></i> สิทธิ์ผู้ใช้ (Admin Only)
                    </label>
                    <select name="role" id="role" class="form-select">
                        <option value="user">User - ผู้ใช้ทั่วไป</option>
                        <option value="admin">Admin - ผู้ดูแลระบบ</option>
                    </select>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-register w-100 mb-2">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </button>
        </form>
        
        <div class="login-link">
            <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const bar = document.getElementById('strengthBar');
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 25;
    if (/[a-z]/.test(password)) strength += 12.5;
    if (/[A-Z]/.test(password)) strength += 12.5;
    if (/[0-9]/.test(password)) strength += 12.5;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 12.5;
    
    bar.style.width = strength + '%';
    if (strength < 25) {
        bar.style.background = 'red';
    } else if (strength < 50) {
        bar.style.background = 'orange';
    } else if (strength < 75) {
        bar.style.background = 'yellow';
    } else {
        bar.style.background = 'green';
    }
}
</script>
</body>
</html>
